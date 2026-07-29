import network, socket
from machine import Pin, PWM
from time import sleep_us, ticks_us, ticks_diff, ticks_add, sleep

PIN_MOTOR5V = 16
_g_motor5v = Pin(PIN_MOTOR5V, Pin.OUT, value=0)
sleep(0.05)

SSID = "Pico"
PAROLA = "bagiparolasidaienter"

IP_STATIC = "192.168.100.165"
MASCA     = "255.255.255.0"
GATEWAY   = "192.168.100.1"
DNS       = "8.8.8.8"

UDP_PORT = 5008

EN     = Pin(2, Pin.OUT)
X_STEP = Pin(3, Pin.OUT)
X_DIR  = Pin(4, Pin.OUT)
Y_STEP = Pin(5, Pin.OUT)
Y_DIR  = Pin(6, Pin.OUT)
Z_STEP = Pin(7, Pin.OUT)
Z_DIR  = Pin(8, Pin.OUT)

EN.value(1)

servo1 = PWM(Pin(18))
servo2 = PWM(Pin(19))
servo1.freq(50)
servo2.freq(50)

FREZA_SUS = 150
FREZA_JOS = 25


def _unghi_catre_duty(grade):
    ms = 0.5 + (grade / 180) * 2.0
    return int((ms / 20) * 65535)


def freza_seteaza(grade):
    grade = max(0, min(180, grade))
    servo1.duty_u16(_unghi_catre_duty(grade))
    servo2.duty_u16(_unghi_catre_duty(180 - grade))


freza_seteaza(FREZA_SUS)

FRECVENTA_MOTOR5V = 1000
PUTERE_MOTOR5V = 100

motor5v = PWM(_g_motor5v)
motor5v.freq(FRECVENTA_MOTOR5V)
motor5v.duty_u16(0)


def motor5v_seteaza(proc):
    proc = max(0, min(100, proc))
    motor5v.duty_u16(int(proc * 65535 / 100))


def motor5v_porneste():
    motor5v_seteaza(PUTERE_MOTOR5V)
    print("motor 5V PORNIT")


def motor5v_opreste():
    motor5v_seteaza(0)
    print("motor 5V OPRIT")


PASI_PER_CM_X = 175.0
PASI_PER_CM_Y = 350.0

V_MERS  = 3.0
V_VIRAJ = 2.0
PULS_US = 3

VITEZA_POMPA = 800.0
per_z = int(1000000 / VITEZA_POMPA)
tz = ticks_us()
pompa_merge = False

comanda = "stop"
per_x = per_y = 0
tx = ty = ticks_us()


def _en_actualizeaza():
    EN.value(0 if (comanda != "stop" or pompa_merge) else 1)


def _directie(cmd):
    if cmd == "fata":
        X_DIR.value(1); Y_DIR.value(0)
    elif cmd == "spate":
        X_DIR.value(0); Y_DIR.value(1)
    elif cmd == "stanga":
        X_DIR.value(1); Y_DIR.value(1)
    elif cmd == "dreapta":
        X_DIR.value(0); Y_DIR.value(0)


def seteaza(cmd):
    global comanda, per_x, per_y, tx, ty
    if cmd == "stop":
        comanda = "stop"
        _en_actualizeaza()
        print("stop")
        return

    v = V_VIRAJ if cmd in ("stanga", "dreapta") else V_MERS
    _directie(cmd)
    per_x = int(1000000 / (v * PASI_PER_CM_X))
    per_y = int(1000000 / (v * PASI_PER_CM_Y))
    tx = ty = ticks_us()
    comanda = cmd
    _en_actualizeaza()
    print(cmd, "la", v, "cm/s")


def pompa_porneste():
    global pompa_merge, tz, per_z
    per_z = int(1000000 / VITEZA_POMPA)
    Z_DIR.value(0)
    tz = ticks_us()
    pompa_merge = True
    _en_actualizeaza()
    print("pompa PORNITA")


def pompa_opreste():
    global pompa_merge
    pompa_merge = False
    _en_actualizeaza()
    print("pompa OPRITA")


def _pas(pin):
    pin.on()
    sleep_us(PULS_US)
    pin.off()


def pulseaza():
    global tx, ty, tz
    now = ticks_us()

    if comanda != "stop":
        d = ticks_diff(now, tx)
        if d >= per_x:
            _pas(X_STEP)
            tx = now if d > 2 * per_x else ticks_add(tx, per_x)

        d = ticks_diff(now, ty)
        if d >= per_y:
            _pas(Y_STEP)
            ty = now if d > 2 * per_y else ticks_add(ty, per_y)

    if pompa_merge:
        d = ticks_diff(now, tz)
        if d >= per_z:
            _pas(Z_STEP)
            tz = now if d > 2 * per_z else ticks_add(tz, per_z)


def log(msg):
    linie = "[{}ms] {}".format(ticks_us() // 1000, msg)
    print(linie)
    try:
        with open("log.txt", "a") as f:
            f.write(linie + "\n")
    except OSError:
        pass

try:
    open("log.txt", "w").close()
except OSError:
    pass

sleep(2)
log("pornire, alimentare stabilizata")

wlan = network.WLAN(network.STA_IF)
wlan.active(True)
wlan.ifconfig((IP_STATIC, MASCA, GATEWAY, DNS))

IP = None
incercare = 0
while IP is None:
    incercare += 1
    log("incercare conectare WiFi #{}".format(incercare))
    wlan.connect(SSID, PAROLA)
    t0 = ticks_us()
    while not wlan.isconnected() and ticks_diff(ticks_us(), t0) < 15_000_000:
        sleep(0.3)
    if wlan.isconnected():
        IP = wlan.ifconfig()[0]
        log("conectat, IP: " + IP)
    else:
        log("esuat dupa 15s (status={}), resetez adaptorul si reincerc".format(wlan.status()))
        wlan.active(False)
        sleep(1)
        wlan.active(True)
        wlan.ifconfig((IP_STATIC, MASCA, GATEWAY, DNS))

s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind(("0.0.0.0", UDP_PORT))
s.settimeout(0)

COMENZI_ROTI = ("fata", "spate", "stanga", "dreapta", "stop")

try:
    while True:
        try:
            date, addr = s.recvfrom(64)
            msg = date.decode().strip()

            if msg in COMENZI_ROTI:
                seteaza(msg)
            elif msg == "freza_jos":
                freza_seteaza(FREZA_JOS)
            elif msg == "freza_sus":
                freza_seteaza(FREZA_SUS)
            elif msg == "pompa_on":
                pompa_porneste()
            elif msg == "pompa_off":
                pompa_opreste()
            elif msg == "motor5v_on":
                motor5v_porneste()
            elif msg == "motor5v_off":
                motor5v_opreste()
            elif msg == "ping":
                s.sendto(b"pong", addr)
        except OSError:
            pass

        if comanda != "stop" or pompa_merge:
            pulseaza()

except KeyboardInterrupt:
    EN.value(1)
    motor5v.duty_u16(0)
    print("Intrerupt")
