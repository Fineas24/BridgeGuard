import network
import urequests
import ujson
import utime
import ustruct
import math
from machine import Pin, I2C


WIFI_SSID = "***..."
WIFI_PASS = "***..."
RPI_IP    = "***..."


wlan = network.WLAN(network.STA_IF)
wlan.active(True)
wlan.connect(WIFI_SSID, WIFI_PASS)
print("Ma conectez...")
while not wlan.isconnected():
    utime.sleep(0.5)
print("Conectat! IP Pico:", wlan.ifconfig()[0])

ALB = "\033[0m"
GALBEN = "\033[93m"
ROSU = "\033[91m"

def get_color(angle):
    abs_angle = abs(angle)
    if abs_angle <= 10:
        return ALB
    elif abs_angle <= 15:
        return GALBEN
    else:
        return ROSU


i2c = I2C(1, sda=Pin(10), scl=Pin(11), freq=100000)
ADDR = 0x69

try:
    i2c.writeto_mem(ADDR, 0x06, b'\x01')
    utime.sleep(0.1)
except:
    print("Eroare hardware!")


while True:
    try:
        data = i2c.readfrom_mem(ADDR, 0x2D, 6)
        ax, ay, az = ustruct.unpack('>hhh', data)

        pitch = math.atan2(ay, az) * 180 / math.pi
        roll  = math.atan2(-ax, math.sqrt(ay**2 + az**2)) * 180 / math.pi
 
        col_p = get_color(pitch)
        col_r = get_color(roll)
        msg = f"Inclinare: fata-spate: {col_p}{pitch:>6.1f}°{ALB}, stanga-dreapta: {col_r}{roll:>6.1f}°{ALB}    "
        print(f"\r{msg}", end="")

        urequests.post(
            "http://" + RPI_IP + ":5000/date",
            data=ujson.dumps({
                "fata_spate": round(pitch, 1),
                "stanga_dreapta": round(roll, 1)
            }),
            headers={"Content-Type": "application/json"}
        )

    except KeyboardInterrupt:
        print(f"\n{ALB}Program oprit.")
        break
    except Exception as e:
        print("Eroare:", e)

    utime.sleep(0.1)