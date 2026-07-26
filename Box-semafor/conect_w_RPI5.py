import time
import network
import urequests

WIFI_SSID = '****...'      
WIFI_PASS = '***...'      

RPI5_IP = '***...'            
RPI5_URL_TEST = f'http://{RPI5_IP}:5000/test'
RPI5_URL_CONECTAT = f'http://{RPI5_IP}:5000/conectat'



print('Pornire in 2 secunde... (apasa Stop in Thonny daca vrei sa opresti)')
time.sleep(2)


def conecteaza_wifi(max_incercari=10, pauza_intre_incercari=3):
    """Incearca sa se conecteze la wifi, cu reincercari daca nu merge din prima."""
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
            time.sleep(1)
            timeout -= 1
            print('.', end='')

        if wlan.isconnected():
            print('\nWiFi conectat! IP Pico:', wlan.ifconfig()[0])
            return wlan

        print(f'\n  Incercarea {incercare} a esuat. Astept {pauza_intre_incercari}s si reincerc...')
        time.sleep(pauza_intre_incercari)

    print('EROARE: nu m-am putut conecta la WiFi dupa toate incercarile.')
    return None


def trimite_cu_retry(url, payload, max_incercari=5, pauza=2):
    """Trimite un POST catre RPi5, reincercand daca serverul nu e inca sus."""
    for incercare in range(1, max_incercari + 1):
        try:
            r = urequests.post(url, json=payload, timeout=5)
            raspuns = r.text
            r.close()
            return raspuns
        except Exception as e:
            print(f'  Incercarea {incercare}/{max_incercari} catre {url} a esuat: {e}')
            time.sleep(pauza)
    print(f'  Nu am reusit sa trimit catre {url} dupa {max_incercari} incercari.')
    return None



wlan = conecteaza_wifi()
if wlan is None:
    raise SystemExit

ip_pico = wlan.ifconfig()[0]


raspuns = trimite_cu_retry(RPI5_URL_CONECTAT, {'ip_pico': ip_pico})
if raspuns:
    print('RPi5 a confirmat conectarea.')


contor = 0
while True:
    contor += 1

  
    if not wlan.isconnected():
        print('WiFi picat! Reincerc conectarea...')
        wlan = conecteaza_wifi()
        if wlan is None:
            time.sleep(5)
            continue

    raspuns = trimite_cu_retry(RPI5_URL_TEST, {'contor': contor, 'mesaj': 'salut de la Pico'}, max_incercari=1)
    if raspuns:
        print('Raspuns RPi5:', raspuns)

    time.sleep(3)