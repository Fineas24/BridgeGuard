from machine import Pin, PWM
from time import sleep_us, sleep_ms, sleep


TEST = "POMPA"



ENABLE = Pin(2, Pin.OUT)
ENABLE.value(0)


X_STEP = Pin(3, Pin.OUT)
X_DIR = Pin(4, Pin.OUT)

Y_STEP = Pin(5, Pin.OUT)
Y_DIR = Pin(6, Pin.OUT)


Z_STEP = Pin(7, Pin.OUT)
Z_DIR = Pin(8, Pin.OUT)


servo1 = PWM(Pin(20))
servo2 = PWM(Pin(19))

servo1.freq(50)
servo2.freq(50)


FREZA = Pin(16, Pin.OUT)
FREZA.off()



def angle(servo, deg):

    duty = int(1638 + deg * (8192 - 1638) / 180)
    servo.duty_u16(duty)

def freza_sus():

    for a in range(90,29,-1):

        angle(servo1,a)
        angle(servo2,180-a)

        sleep(0.01)

def freza_jos():

    for a in range(30,91):

        angle(servo1,a)
        angle(servo2,180-a)

        sleep(0.01)


def freza_on():

    FREZA.on()

def freza_off():

    FREZA.off()



START_DELAY = 12000  
MIN_DELAY = 5000      
RAMP = 800            


def mers(xdir, ydir, pasi):

    X_DIR.value(xdir)
    Y_DIR.value(ydir)

    sleep_ms(10)

    for i in range(pasi):

        
        if i < RAMP:

            delay = START_DELAY - ((START_DELAY - MIN_DELAY) * i // RAMP)

        elif i < pasi - RAMP:

            delay = MIN_DELAY

        else:

            delay = START_DELAY - ((START_DELAY - MIN_DELAY) * (pasi-i) // RAMP)


              X_STEP.on()
        Y_STEP.on()

        sleep_us(5)

        X_STEP.off()
        Y_STEP.off()

        sleep_us(delay)

def pompeaza():

    Z_DIR.value(1)
    sleep_ms(20)

    for i in range(1000):

        Z_STEP.on()
        sleep_us(10)

        Z_STEP.off()
        sleep_us(3000)


if TEST == "SERVO":

    while True:

        print("FREZA SUS")

        freza_sus()

        sleep(2)

        print("FREZA JOS")

        freza_jos()

        sleep(2)

elif TEST == "FREZA":

    while True:

        print("ON")

        freza_on()

        sleep(5)

        print("OFF")

        freza_off()

        sleep(3)

elif TEST == "POMPA":

    while True:

        pompeaza()

        sleep(0)

elif TEST == "MERS":

    while True:

        print("INAINTE")

        mers(0,1,2500)

        sleep(2)