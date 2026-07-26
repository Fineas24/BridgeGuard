from machine import Pin
import sys, select, time, network, socket
from secrets import WIFI_SSID, WIFI_PASS

PICO_IP   = '192.168.100.61'
GATEWAY   = '192.168.100.1'
MASK      = '255.255.255.0'
DNS       = '192.168.100.1'
UDP_PORT  = 5008

EN     = Pin(2, Pin.OUT)

X_STEP = Pin(3, Pin.OUT)
X_DIR  = Pin(4, Pin.OUT)

Y_STEP = Pin(5, Pin.OUT)
Y_DIR  = Pin(6, Pin.OUT)

Z_STEP = Pin(7, Pin.OUT)
Z_DIR  = Pin(8, Pin.OUT)

EN.value(0)
Z_DIR.value(0)

VITEZA_US = 800
VITEZA_POMPA_US = 1000
TIMEOUT_MS = 300

DIR_FATA_X = 1
DIR_FATA_Y = 0

def conecteaza_wifi(max_incercari=10, pauza=3):
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    if PICO_IP:
        wlan.ifconfig((PICO_IP, MASK, GATEWAY, DNS))
    for i in range(max_incercari):
        if wlan.isconnected():
            break
        print('Conectare WiFi... incercarea', i + 1)
        wlan.connect(WIFI_SSID, WIFI_PASS)
        t0 = time.ticks_ms()
        while not wlan.isconnected() and time.ticks_diff(time.ticks_ms(), t0) < pauza * 1000:
            time.sleep_ms(100)
    if wlan.isconnected():
        print('WiFi OK, IP:', wlan.ifconfig()[0])
    else:
        print('WiFi ESUAT — merge doar controlul din Thonny')
    return wlan

wlan = conecteaza_wifi()

udp = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
udp.bind(('0.0.0.0', UDP_PORT))
udp.setblocking(False)

pompa_pornita = False
poll = select.poll()
poll.register(sys.stdin, select.POLLIN)

def citeste_tasta():
    if poll.poll(0):
        return sys.stdin.read(1), None
    try:
        data, addr = udp.recvfrom(16)
        return data.decode().strip().lower(), addr
    except OSError:
        return None, None

def pas(step_pin):
    step_pin.value(1)
    time.sleep_us(10)
    step_pin.value(0)

def seteaza_directie(tasta):
    if tasta == 'w':
        X_DIR.value(DIR_FATA_X)
        Y_DIR.value(DIR_FATA_Y)
    elif tasta == 's':
        X_DIR.value(1 - DIR_FATA_X)
        Y_DIR.value(1 - DIR_FATA_Y)
    elif tasta == 'a':
        X_DIR.value(1 - DIR_FATA_X)
        Y_DIR.value(DIR_FATA_Y)
    elif tasta == 'd':
        X_DIR.value(DIR_FATA_X)
        Y_DIR.value(1 - DIR_FATA_Y)

print("Control: W/A/S/D = miscare, X = stop, P = pompa ON/OFF, Q = iesire")
print("Site-ul trimite aceleasi comenzi prin UDP pe portul", UDP_PORT)

ultima_tasta = None
ultimul_input = 0
ultimul_pas_motoare = 0
ultimul_pas_pompa = 0
ultima_verificare_wifi = 0

while True:
    t, addr = citeste_tasta()
    acum = time.ticks_ms()

    if t:
        if t == 'q':
            print("Iesire.")
            EN.value(1)
            break
        elif t == 'ping':
            if addr:
                try:
                    udp.sendto(b'pong', addr)
                except OSError:
                    pass
        elif t == 'x':
            ultima_tasta = None
        elif t == 'p':
            pompa_pornita = not pompa_pornita
            print("Pompa:", "ON" if pompa_pornita else "OFF")
        elif t in 'wasd':
            if t != ultima_tasta:
                seteaza_directie(t)
                ultima_tasta = t
            ultimul_input = acum

    misca = ultima_tasta and time.ticks_diff(acum, ultimul_input) < TIMEOUT_MS

    acum_us = time.ticks_us()

    if misca and time.ticks_diff(acum_us, ultimul_pas_motoare) >= VITEZA_US:
        pas(X_STEP)
        pas(Y_STEP)
        ultimul_pas_motoare = acum_us

    if not misca:
        ultima_tasta = None

    if pompa_pornita and time.ticks_diff(acum_us, ultimul_pas_pompa) >= VITEZA_POMPA_US:
        pas(Z_STEP)
        ultimul_pas_pompa = acum_us

    if not misca and time.ticks_diff(acum, ultima_verificare_wifi) > 5000:
        ultima_verificare_wifi = acum
        if not wlan.isconnected():
            print("WiFi picat, reconectare...")
            wlan = conecteaza_wifi(max_incercari=1)
