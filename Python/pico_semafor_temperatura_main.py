import time
import network
import ujson
import usocket
from machine import Pin, WDT
from secrets import WIFI_SSID, WIFI_PASS

RPI5_IP   = '192.168.100.10'
RPI5_PORT = 5005

PIN_ROSU = Pin(7, Pin.OUT)
PIN_GALBEN = Pin(8, Pin.OUT)
PIN_VERDE = Pin(9, Pin.OUT)

PIN_SENZOR1 = Pin(14, Pin.IN, Pin.PULL_UP)
PIN_SENZOR2 = Pin(15, Pin.IN, Pin.PULL_UP)

PIN_CANTAR_DAT = Pin(26, Pin.IN)
PIN_CANTAR_SCK = Pin(27, Pin.OUT)

PIN_TEMP_SCK = Pin(2, Pin.OUT)
PIN_TEMP_CS  = Pin(3, Pin.OUT)
PIN_TEMP_SO  = Pin(4, Pin.IN)

CALIBRARE_KG = 420000
PRAG_GREUTATE = 0.250
TIMEOUT_SENZOR1 = 5

INTERVAL_TEMP_MS = 2000
ultima_temp_ms = 0

INTERVAL_STARE_MS = 3000
ultima_stare_ms = 0
culoare_curenta = 'verde'
greutate_curenta = 0.0


print('Pornire in 3 secunde... (apasa Stop in Thonny daca vrei sa opresti)')
time.sleep(3)


class HX711:
    def __init__(self, dat_pin, sck_pin, gain=128):
        self.dat = dat_pin
        self.sck = sck_pin
        self.OFFSET = 0
        self.SCALE = 1
        self.GAIN = 1 if gain == 128 else (3 if gain == 64 else 2)
        self.sck.value(0)

    def read(self):
        timeout = 1000
        while self.dat.value() == 1:
            time.sleep_ms(1)
            timeout -= 1
            if timeout <= 0:
                return 0
        result = 0
        for _ in range(24 + self.GAIN):
            self.sck.value(1)
            bit = self.dat.value()
            result = (result << 1) | bit
            self.sck.value(0)
        result >>= self.GAIN
        if result > 0x7fffff:
            result -= 0x1000000
        return result

    def tare(self, times=20):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep_ms(10)
        self.OFFSET = total / times

    def set_scale(self, scale):
        self.SCALE = scale

    def get_units(self, times=3):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep_ms(10)
        return ((total / times) - self.OFFSET) / self.SCALE


def citeste_max6675():
    PIN_TEMP_CS.low()
    time.sleep_us(10)

    date = 0
    for _ in range(16):
        PIN_TEMP_SCK.high()
        time.sleep_us(10)
        date = (date << 1) | PIN_TEMP_SO.value()
        PIN_TEMP_SCK.low()
        time.sleep_us(10)

    PIN_TEMP_CS.high()

    if date & 0x4:
        return None

    valoare_temperatura = (date >> 3) & 0x0FFF
    return valoare_temperatura * 0.25


def trimite_post(path, payload, timeout=3):
    body = ujson.dumps(payload)
    s = usocket.socket()
    s.settimeout(timeout)
    try:
        s.connect((RPI5_IP, RPI5_PORT))
        cerere = (
            "POST {0} HTTP/1.0\r\n"
            "Host: {1}\r\n"
            "Content-Type: application/json\r\n"
            "Content-Length: {2}\r\n"
            "Connection: close\r\n\r\n"
            "{3}"
        ).format(path, RPI5_IP, len(body), body)
        s.send(cerere.encode())
        return s.recv(256)
    finally:
        s.close()


def actualizeaza_temperatura():
    global ultima_temp_ms
    if time.ticks_diff(time.ticks_ms(), ultima_temp_ms) < INTERVAL_TEMP_MS:
        return
    ultima_temp_ms = time.ticks_ms()

    temp = citeste_max6675()
    if temp is None:
        return

    verifica_wifi()
    try:
        trimite_post('/temperatura', {'temperatura': temp})
    except Exception as e:
        print('  Temp: trimitere esuata:', e)


def conecteaza_wifi(max_incercari=10, pauza_intre_incercari=3, wdt=None):
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)

    if wlan.isconnected():
        return wlan

    for incercare in range(1, max_incercari + 1):
        print(f'Incercare conectare WiFi {incercare}/{max_incercari}...')
        try:
            wlan.connect(WIFI_SSID, WIFI_PASS)
        except Exception as e:
            print('  Eroare la connect():', e)

        timeout = 10
        while not wlan.isconnected() and timeout > 0:
            if wdt:
                wdt.feed()
            time.sleep(1)
            timeout -= 1
            print('.', end='')

        if wlan.isconnected():
            print('\nWiFi conectat! IP Pico:', wlan.ifconfig()[0])
            return wlan

        print(f'\n  Incercarea {incercare} a esuat. Astept {pauza_intre_incercari}s si reincerc...')
        if wdt:
            wdt.feed()
        time.sleep(pauza_intre_incercari)

    print('EROARE: nu m-am putut conecta la WiFi dupa toate incercarile.')
    return None


def verifica_wifi():
    global wlan
    if not wlan or not wlan.isconnected():
        print('WiFi picat! Reincerc conectarea...')
        wlan = conecteaza_wifi(wdt=wdt)


def trimite_cu_retry(path, payload, max_incercari=3, pauza=1):
    for incercare in range(1, max_incercari + 1):
        try:
            return trimite_post(path, payload)
        except Exception as e:
            print(f'  Incercarea {incercare}/{max_incercari} catre {path} a esuat: {e}')
            time.sleep(pauza)
    return None


def trimite_stare(culoare, greutate=0.0, mesaj=''):
    global ultima_stare_ms, culoare_curenta, greutate_curenta
    culoare_curenta = culoare
    greutate_curenta = greutate
    ultima_stare_ms = time.ticks_ms()
    verifica_wifi()
    trimite_cu_retry(
        '/stare',
        {'culoare': culoare, 'greutate': round(greutate, 3), 'mesaj': mesaj}
    )

def heartbeat_semafor(greutate=None):
    if time.ticks_diff(time.ticks_ms(), ultima_stare_ms) < INTERVAL_STARE_MS:
        return
    g = greutate if greutate is not None else greutate_curenta
    trimite_stare(culoare_curenta, g, '')

def cere_poza_si_analiza():
    verifica_wifi()
    raspuns = trimite_cu_retry('/fa-poza', {})
    if raspuns:
        print('  Cerere poza trimisa catre RPi 5')
    else:
        print('  Eroare cerere poza catre RPi 5')


def semafor_verde(greutate=0.0):
    PIN_ROSU.value(0)
    PIN_GALBEN.value(0)
    PIN_VERDE.value(1)
    trimite_stare('verde', greutate)

def semafor_galben(greutate=0.0, mesaj=''):
    PIN_ROSU.value(0)
    PIN_GALBEN.value(1)
    PIN_VERDE.value(0)
    trimite_stare('galben', greutate, mesaj)

def semafor_rosu(greutate=0.0, mesaj=''):
    PIN_ROSU.value(1)
    PIN_GALBEN.value(0)
    PIN_VERDE.value(0)
    trimite_stare('rosu', greutate, mesaj)


def senzor1_detectat():
    return PIN_SENZOR1.value() == 0

def senzor2_detectat():
    return PIN_SENZOR2.value() == 0


wlan = conecteaza_wifi()

wdt = WDT(timeout=8000)

hx = HX711(PIN_CANTAR_DAT, PIN_CANTAR_SCK)
hx.set_scale(CALIBRARE_KG)

print('Calibrare greutate... asteapta.')
hx.tare()
print('Sistem pornit! Semafor VERDE.\n')

semafor_verde()

while True:
    wdt.feed()
    actualizeaza_temperatura()

    greutate = abs(hx.get_units(3))
    heartbeat_semafor(greutate)

    if greutate > PRAG_GREUTATE:
        print('[GREUTATE] {:.3f} kg -> ROSU'.format(greutate))
        semafor_rosu(greutate, 'Greutate detectata')

        poza_facuta = False
        while True:
            wdt.feed()
            if senzor1_detectat():
                semafor_rosu(greutate, 'Obiect detectat')
                print('  Senzor 1: detectie activa - ROSU')
                while senzor1_detectat():
                    wdt.feed()
                    actualizeaza_temperatura()
                    heartbeat_semafor()
                    time.sleep_ms(100)
                print('  Senzor 1: s-a eliberat. Pornesc timer 5s...')

            eliberat = True
            start_timer = time.ticks_ms()
            while time.ticks_diff(time.ticks_ms(), start_timer) < TIMEOUT_SENZOR1 * 1000:
                wdt.feed()
                actualizeaza_temperatura()
                heartbeat_semafor()
                if senzor1_detectat():
                    print('  Senzor 1: detectie in timer! Reset.')
                    eliberat = False
                    break
                if not poza_facuta and senzor2_detectat():
                    print('  Senzor 2: DETECTIE! Cer poza de la RPi 5...')
                    semafor_rosu(greutate, 'Fotografiere!')
                    cere_poza_si_analiza()
                    poza_facuta = True
                time.sleep_ms(100)

            if eliberat:
                print('  5s fara detectie senzor 1.')
                break

        semafor_galben(greutate, 'Eliberare')
        time.sleep(2)
        print('  VERDE - cale libera\n')
        semafor_verde(0.0)

    time.sleep_ms(200)
