import time
import json
import os
import lgpio
import requests
import MySQLdb
import base64
from datetime import datetime

PIN_DT = 17
PIN_SCK = 27
PIN_SHARP = 26
PIN_DIST2 = 22
PIN_ROSU = 19
PIN_GALBEN = 13
PIN_VERDE = 6

STATUS_FILE = '/var/www/html/proiect/Python/semafor_status.json'
POZE_DIR = '/var/www/html/proiect/poze_semafor'

CALIBRARE_KG = 420000
PRAG_GREUTATE = 0.250
TIMEOUT_SENZOR2 = 5
TIMEOUT_VERDE = 3

CAMERA_WIFI_URL = 'http://192.168.100.218/capture'
CAMERA_TIMEOUT = 10

CLAUDE_API_KEY = os.environ.get('CLAUDE_API_KEY')
CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages'

DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS')
DB_NAME = os.environ.get('DB_NAME', 'BridgeGuard')


class HX711:
    def __init__(self, chip, dout, sck, gain=128):
        self.h = chip
        self.dout = dout
        self.sck = sck
        self.OFFSET = 0
        self.SCALE = 1
        self.GAIN = 1 if gain == 128 else (3 if gain == 64 else 2)
        lgpio.gpio_claim_input(self.h, self.dout)
        lgpio.gpio_claim_output(self.h, self.sck, 0)

    def read(self):
        timeout = 1000
        while lgpio.gpio_read(self.h, self.dout) == 1:
            time.sleep(0.001)
            timeout -= 1
            if timeout <= 0:
                return 0
        result = 0
        for _ in range(24 + self.GAIN):
            lgpio.gpio_write(self.h, self.sck, 1)
            bit = lgpio.gpio_read(self.h, self.dout)
            result = (result << 1) | bit
            lgpio.gpio_write(self.h, self.sck, 0)
        result >>= self.GAIN
        if result > 0x7fffff:
            result -= 0x1000000
        return result

    def tare(self, times=20):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep(0.01)
        self.OFFSET = total / times

    def set_scale(self, scale):
        self.SCALE = scale

    def get_units(self, times=3):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep(0.01)
        return ((total / times) - self.OFFSET) / self.SCALE


def scrie_stare(culoare, detectat=False, greutate=0.0, mesaj=''):
    try:
        with open(STATUS_FILE, 'w') as f:
            json.dump({
                'culoare': culoare,
                'detectat': detectat,
                'greutate': round(greutate, 3),
                'mesaj': mesaj,
                'timestamp': time.time()
            }, f)
    except:
        pass

def semafor_verde(h, greutate=0.0):
    lgpio.gpio_write(h, PIN_ROSU, 0)
    lgpio.gpio_write(h, PIN_GALBEN, 0)
    lgpio.gpio_write(h, PIN_VERDE, 1)
    scrie_stare('verde', greutate=greutate)

def semafor_galben(h, greutate=0.0, mesaj=''):
    lgpio.gpio_write(h, PIN_ROSU, 0)
    lgpio.gpio_write(h, PIN_GALBEN, 1)
    lgpio.gpio_write(h, PIN_VERDE, 0)
    scrie_stare('galben', greutate=greutate, mesaj=mesaj)

def semafor_rosu(h, greutate=0.0, mesaj=''):
    lgpio.gpio_write(h, PIN_ROSU, 1)
    lgpio.gpio_write(h, PIN_GALBEN, 0)
    lgpio.gpio_write(h, PIN_VERDE, 0)
    scrie_stare('rosu', True, greutate=greutate, mesaj=mesaj)

def senzor1_detectat(h):
    return lgpio.gpio_read(h, PIN_SHARP) == 0

def senzor2_detectat(h):
    return lgpio.gpio_read(h, PIN_DIST2) == 0

def fa_poza():
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename = f'{POZE_DIR}/detectie_{timestamp}.jpg'

    try:
        r = requests.get(CAMERA_WIFI_URL, timeout=CAMERA_TIMEOUT)
        r.raise_for_status()
        with open(filename, 'wb') as f:
            f.write(r.content)
        os.chmod(filename, 0o666)
        print(f"  Poza salvata (WiFi {CAMERA_WIFI_URL}): {filename}")
    except Exception as e:
        print(f"  Eroare camera WiFi: {e}")
        return

    numar = citeste_numar(filename)
    if numar:
        salveaza_numar(numar, filename, timestamp)

def citeste_numar(poza_path):
    try:
        with open(poza_path, 'rb') as f:
            img_data = base64.standard_b64encode(f.read()).decode('utf-8')

        r = requests.post(
            CLAUDE_API_URL,
            headers={
                'x-api-key': CLAUDE_API_KEY,
                'anthropic-version': '2023-06-01',
                'content-type': 'application/json'
            },
            json={
                'model': 'claude-haiku-4-5-20251001',
                'max_tokens': 50,
                'messages': [{
                    'role': 'user',
                    'content': [
                        {
                            'type': 'image',
                            'source': {
                                'type': 'base64',
                                'media_type': 'image/jpeg',
                                'data': img_data
                            }
                        },
                        {
                            'type': 'text',
                            'text': 'Citeste numarul de inmatriculare din aceasta poza. Raspunde DOAR cu numarul (ex: AR 24 CMF). Daca nu gasesti niciun numar, raspunde exact: NIMIC'
                        }
                    ]
                }]
            },
            timeout=15
        )

        rezultat = r.json()
        raspuns = rezultat.get('content', [{}])[0].get('text', '').strip()
        print(f"  Claude raspuns: {raspuns}")

        if raspuns and raspuns != 'NIMIC' and len(raspuns) >= 4:
            print(f"  NUMAR DETECTAT: {raspuns}")
            return raspuns
        else:
            print("  Niciun numar detectat in poza.")
            return None
    except Exception as e:
        print(f"  Eroare Claude API: {e}")
        return None

def salveaza_numar(numar, poza_path, timestamp):
    try:
        conn = MySQLdb.connect(
            host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME
        )
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO numere_masini (numar, poza, data_detectie) VALUES (%s, %s, %s)",
            (numar, poza_path, datetime.now())
        )
        conn.commit()
        cursor.close()
        conn.close()
        print(f"  Salvat in DB: {numar}")
    except Exception as e:
        print(f"  Eroare salvare DB: {e}")


h = lgpio.gpiochip_open(0)

lgpio.gpio_claim_output(h, PIN_ROSU, 0)
lgpio.gpio_claim_output(h, PIN_GALBEN, 0)
lgpio.gpio_claim_output(h, PIN_VERDE, 0)
lgpio.gpio_claim_input(h, PIN_SHARP)
lgpio.gpio_claim_input(h, PIN_DIST2)

hx = HX711(h, PIN_DT, PIN_SCK)
hx.set_scale(CALIBRARE_KG)

print("Calibrare greutate... Asteapta.")
hx.tare()
print("Sistem GDS pornit! Semafor VERDE.\n")

semafor_verde(h)

try:
    while True:
        greutate = abs(hx.get_units(3))
        scrie_stare('verde', greutate=greutate)

        if greutate > PRAG_GREUTATE:
            print(f"[GREUTATE] {greutate:.3f} kg -> ROSU")
            semafor_rosu(h, greutate=greutate, mesaj='Greutate detectata')

            print("  Monitorizare senzor 1...")
            poza_facuta = False
            while True:
                if senzor1_detectat(h):
                    semafor_rosu(h, greutate=greutate, mesaj='Obiect detectat')
                    print("  Senzor 1: detectie activa - ROSU")
                    while senzor1_detectat(h):
                        time.sleep(0.1)
                    print("  Senzor 1: s-a eliberat. Pornesc timer 5s...")

                eliberat = True
                start_timer = time.time()
                while time.time() - start_timer < 5:
                    if senzor1_detectat(h):
                        print("  Senzor 1: detectie in timer! Reset.")
                        eliberat = False
                        break
                    if not poza_facuta and senzor2_detectat(h):
                        print("  Senzor 2: DETECTIE! Fac poza cu camera 1...")
                        semafor_rosu(h, greutate=greutate, mesaj='Fotografiere!')
                        fa_poza()
                        poza_facuta = True
                    time.sleep(0.1)

                if eliberat:
                    print("  5s fara detectie senzor 1.")
                    break

            semafor_galben(h, greutate=greutate, mesaj='Eliberare')
            time.sleep(2)
            print("  VERDE - cale libera\n")
            semafor_verde(h, greutate=0.0)

        time.sleep(0.2)

except KeyboardInterrupt:
    print("\nOprit.")
finally:
    lgpio.gpio_write(h, PIN_ROSU, 0)
    lgpio.gpio_write(h, PIN_GALBEN, 0)
    lgpio.gpio_write(h, PIN_VERDE, 0)
    lgpio.gpiochip_close(h)
