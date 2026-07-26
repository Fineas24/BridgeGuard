from machine import Pin, PWM
from time import sleep_us, sleep


EN = Pin(2, Pin.OUT)

X_STEP = Pin(3, Pin.OUT)
X_DIR  = Pin(4, Pin.OUT)

Y_STEP = Pin(5, Pin.OUT)
Y_DIR  = Pin(6, Pin.OUT)

P_STEP = Pin(10, Pin.OUT)
P_DIR  = Pin(11, Pin.OUT)

CUTTER = Pin(16, Pin.OUT)

servo1 = PWM(Pin(20))
servo2 = PWM(Pin(19))

servo1.freq(50)
servo2.freq(50)

EN.value(0)

STEP_DELAY = 600


def angle(a):

    duty = int(1638 + (a/180)*6554)

    servo1.duty_u16(duty)
    servo2.duty_u16(duty)


def freza_jos():

    angle(90)
    print("Freza jos")


def freza_sus():

    angle(0)
    print("Freza sus")




def freza_on():

    CUTTER.value(1)
    print("Freza pornita")


def freza_off():

    CUTTER.value(0)
    print("Freza oprita")




def step(step_pin):

    step_pin.on()
    sleep_us(STEP_DELAY)
    step_pin.off()
    sleep_us(STEP_DELAY)


def move_motor(step_pin, dir_pin, steps):

    if steps >= 0:
        dir_pin.value(1)
    else:
        dir_pin.value(0)

    steps = abs(steps)

    for i in range(steps):
        step(step_pin)




def forward(steps):

    X_DIR.value(1)
    Y_DIR.value(0)     

    for i in range(steps):

        X_STEP.on()
        Y_STEP.on()

        sleep_us(STEP_DELAY)

        X_STEP.off()
        Y_STEP.off()

        sleep_us(STEP_DELAY)

    print("Inainte terminat")


def backward(steps):

    X_DIR.value(0)
    Y_DIR.value(1)

    for i in range(steps):

        X_STEP.on()
        Y_STEP.on()

        sleep_us(STEP_DELAY)

        X_STEP.off()
        Y_STEP.off()

        sleep_us(STEP_DELAY)

    print("Inapoi terminat")


def left(steps):

    X_DIR.value(0)
    Y_DIR.value(0)

    for i in range(steps):

        X_STEP.on()
        Y_STEP.on()

        sleep_us(STEP_DELAY)

        X_STEP.off()
        Y_STEP.off()

        sleep_us(STEP_DELAY)

    print("Stanga terminat")


def right(steps):

    X_DIR.value(1)
    Y_DIR.value(1)

    for i in range(steps):

        X_STEP.on()
        Y_STEP.on()

        sleep_us(STEP_DELAY)

        X_STEP.off()
        Y_STEP.off()

        sleep_us(STEP_DELAY)

    print("Dreapta terminat")




def pump(steps):

    move_motor(P_STEP, P_DIR, steps)

    print("Pompare terminata")




print("Robot Ready")


print("F1000  -> Inainte")
print("B1000  -> Inapoi")
print("L500   -> Stanga")
print("R500   -> Dreapta")
print("X500   -> Motor X")
print("Y500   -> Motor Y")
print("P500   -> Pompa")
print("ON     -> Freza ON")
print("OFF    -> Freza OFF")
print("J      -> Freza jos")
print("S      -> Freza sus")


while True:

    cmd = input(">>> ").strip().upper()

    if cmd.startswith("F"):
        forward(int(cmd[1:]))

    elif cmd.startswith("B"):
        backward(int(cmd[1:]))

    elif cmd.startswith("L"):
        left(int(cmd[1:]))

    elif cmd.startswith("R"):
        right(int(cmd[1:]))

    elif cmd.startswith("X"):
        move_motor(X_STEP, X_DIR, int(cmd[1:]))

    elif cmd.startswith("Y"):
        move_motor(Y_STEP, Y_DIR, int(cmd[1:]))

    elif cmd.startswith("P"):
        pump(int(cmd[1:]))

    elif cmd == "ON":
        freza_on()

    elif cmd == "OFF":
        freza_off()

    elif cmd == "J":
        freza_jos()

    elif cmd == "S":
        freza_sus()

    else:
        print("Comanda necunoscuta")
