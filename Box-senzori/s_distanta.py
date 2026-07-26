from machine import Pin
import utime

TRIG = Pin(19, Pin.OUT)
ECHO = Pin(18, Pin.IN)

def get_distance():
    TRIG.low()
    utime.sleep_us(2)
    TRIG.high()
    utime.sleep_us(10)
    TRIG.low()

    while ECHO.value() == 0:
        pass
    start = utime.ticks_us()
    while ECHO.value() == 1:
        pass
    end = utime.ticks_us()

    return (utime.ticks_diff(end, start) * 0.0343) / 2

while True:
    dist = get_distance()
    if dist < 50:       
        print("RAU")
    else:
        print("BUN")
    utime.sleep(0.5)