import time
import network
import urequests
from machine import Pin

WIFI_SSID = "NUMELE_RETELEI"
WIFI_PASS = "PAROLA_RETELEI"

RPI_IP  = "192.168.100.10"
URL_TEMP = "http://%s:5005/temperatura" % RPI_IP
URL_CONECTAT = "http://%s:5005/conectat" % RPI_IP

pin_sck = Pin(2, Pin.OUT)
pin_cs  = Pin(3, Pin.OUT)
pin_so  = Pin(4, Pin.IN)

led = Pin("LED", Pin.OUT)


def citeste_max6675():
    pin_cs.low()
    time.sleep_us(10)

    date = 0
    for i in range(16):
        pin_sck.high()
        time.sleep_us(10)
        date = (date << 1) | pin_so.value()
        pin_sck.low()
        time.sleep_us(10)

    pin_cs.high()

    if date & 0x4:
        return None

    valoare_temperatura = (date >> 3) & 0x0FFF
    return valoare_temperatura * 0.25


def conecteaza_wifi():
    wlan = network.WLAN(network.STA_IF)
    wlan.active(True)
    if not wlan.isconnected():
        print("Conectare la WiFi:", WIFI_SSID)
        wlan.connect(WIFI_SSID, WIFI_PASS)
        for _ in range(20):
            if wlan.isconnected():
                break
            led.toggle()
            time.sleep(0.5)
    if wlan.isconnected():
        ip = wlan.ifconfig()[0]
        led.on()
        print("WiFi OK, IP Pico:", ip)
        try:
            r = urequests.post(URL_CONECTAT, json={"ip_pico": ip})
            r.close()
        except Exception as e:
            print("Nu am putut anunta /conectat:", e)
        return wlan
    led.off()
    print("WiFi ESUAT!")
    return wlan


def trimite_temperatura(temp):
    try:
        r = urequests.post(URL_TEMP, json={"temperatura": temp})
        r.close()
        return True
    except Exception as e:
        print("Eroare trimitere:", e)
        return False


wlan = conecteaza_wifi()
print("Senzor MAX6675 pornit. Trimit temperatura catre", URL_TEMP)

while True:
    if not wlan.isconnected():
        wlan = conecteaza_wifi()

    temp = citeste_max6675()
    if temp is not None:
        if trimite_temperatura(temp):
            print("Trimis: %.2f C" % temp)
    else:
        print("Eroare senzor: termocuplu deconectat.")

    time.sleep(1)
