import network
import ujson
import utime
import ustruct
import math
import gc
import usocket
from machine import Pin, I2C, WDT
from secrets import WIFI_SSID, WIFI_PASS

RPI_HOST  = "192.168.100.10"
RPI_PORT  = 5000
RPI_PATH  = "/date"

i2c       = I2C(1, sda=Pin(10), scl=Pin(11), freq=100000)
GYRO_ADDR = 0x69

TRIG    = Pin(19, Pin.OUT)
ECHO    = Pin(18, Pin.IN)
VIBR    = Pin(26, Pin.IN)
BOBINAJ = Pin(16, Pin.IN, Pin.PULL_UP)

gyro_ok = False
try:
    i2c.writeto_mem(GYRO_ADDR, 0x06, b'\x01')
    utime.sleep_ms(100)
    gyro_ok = True
    print("Giroscop: OK")
except Exception as e:
    print("Giroscop: EROARE -", e)

intensitate_vibr = 0.0

def citeste_giroscop():
    if not gyro_ok:
        return None, None
    try:
        data = i2c.readfrom_mem(GYRO_ADDR, 0x2D, 6)
        ax, ay, az = ustruct.unpack('>hhh', data)
        pitch = math.atan2(ay, az) * 180 / math.pi
        roll  = math.atan2(-ax, math.sqrt(ay**2 + az**2)) * 180 / math.pi
        return round(pitch, 1), round(roll, 1)
    except:
        return None, None

def citeste_ultrasonic():
    TRIG.low()
    utime.sleep_us(2)
    TRIG.high()
    utime.sleep_us(10)
    TRIG.low()

    t0 = utime.ticks_us()
    while ECHO.value() == 0:
        if utime.ticks_diff(utime.ticks_us(), t0) > 30000:
            return -1.0
    start = utime.ticks_us()

    while ECHO.value() == 1:
        if utime.ticks_diff(utime.ticks_us(), start) > 30000:
            return -1.0
    end = utime.ticks_us()

    return round((utime.ticks_diff(end, start) * 0.0343) / 2, 1)

def citeste_vibratie():
    global intensitate_vibr
    if VIBR.value() == 1:
        intensitate_vibr = 1000.0
    else:
        intensitate_vibr *= 0.92
        if intensitate_vibr < 1:
            intensitate_vibr = 0.0
    return round(intensitate_vibr, 1)

def bobinaj_intact():
    return BOBINAJ.value() == 0

def trimite_date(payload):
    body = ujson.dumps(payload)
    s = usocket.socket()
    s.settimeout(5)
    try:
        s.connect((RPI_HOST, RPI_PORT))
        cerere = (
            "POST {0} HTTP/1.0\r\n"
            "Host: {1}\r\n"
            "Content-Type: application/json\r\n"
            "Content-Length: {2}\r\n"
            "Connection: close\r\n\r\n"
            "{3}"
        ).format(RPI_PATH, RPI_HOST, len(body), body)
        s.send(cerere.encode())
    finally:
        s.close()

def conecteaza_wifi(wdt=None):
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    if wlan.isconnected():
        return wlan
    print("Conectare la:", WIFI_SSID)
    wlan.connect(WIFI_SSID, WIFI_PASS)
    for _ in range(30):
        if wdt:
            wdt.feed()
        if wlan.isconnected():
            print("WiFi OK | IP:", wlan.ifconfig()[0])
            return wlan
        utime.sleep_ms(500)
        print(".", end="")
    print("\nWiFi: nu s-a putut conecta!")
    return wlan

wlan = conecteaza_wifi()

wdt = WDT(timeout=8000)

print("BridgeWatch pornit. Trimit date...")

while True:
    wdt.feed()
    try:
        if not wlan.isconnected():
            print("\nWiFi pierdut, reconectez...")
            wlan = conecteaza_wifi(wdt)
            utime.sleep_ms(2000)
            continue

        pitch, roll = citeste_giroscop()
        distanta    = citeste_ultrasonic()
        vibratie    = citeste_vibratie()
        cablu_ok    = bobinaj_intact()

        trimite_date({
            "gyro": {
                "fata_spate":     pitch,
                "stanga_dreapta": roll
            },
            "vibratie":   vibratie,
            "ultrasonic": distanta,
            "bobinaj":    1 if cablu_ok else 0
        })

        b_str = "OK " if cablu_ok else "RUP"
        print(f"G:{pitch}/{roll}deg U:{distanta}cm V:{int(vibratie)} B:{b_str}", end="\r")

    except KeyboardInterrupt:
        print("\nOprit manual.")
        break
    except Exception as e:
        print("\nEroare:", e)
        utime.sleep_ms(2000)

    gc.collect()
    utime.sleep_ms(100)
