from machine import Pin, PWM
from time import sleep_us, sleep


EN     = Pin(2,  Pin.OUT)   
X_STEP = Pin(3,  Pin.OUT)
X_DIR  = Pin(4,  Pin.OUT)
Y_STEP = Pin(5,  Pin.OUT)
Y_DIR  = Pin(6,  Pin.OUT)
P_STEP = Pin(10, Pin.OUT)
P_DIR  = Pin(11, Pin.OUT)
CUTTER = Pin(16, Pin.OUT)

servo1 = PWM(Pin(20))
servo2 = PWM(Pin(21))
servo1.freq(50)
servo2.freq(50)

STEP_DELAY = 600         
SERVO_SETTLE = 0.6         
SERVO_MIRROR = False        


EN.value(1)                
CUTTER.value(0)



def drivers_on():
    EN.value(0)

def drivers_off():
    EN.value(1)



def _duty(a):
    a = max(0, min(180, a))
    return int(1638 + (a / 180) * 6554)

def angle(a, detach=True):
    servo1.duty_u16(_duty(a))
    servo2.duty_u16(_duty(180 - a) if SERVO_MIRROR else _duty(a))
    sleep(SERVO_SETTLE)
    if detach:                      
        servo1.duty_u16(0)
        servo2.duty_u16(0)

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



def _puls(*step_pins):
    for p in step_pins:
        p.on()
    sleep_us(STEP_DELAY)
    for p in step_pins:
        p.off()
    sleep_us(STEP_DELAY)

def _misca(steps, dirs, step_pins):
    """dirs = lista de tupluri (dir_pin, valoare)"""
    for dir_pin, val in dirs:
        dir_pin.value(val)
    drivers_on()
    try:
        for _ in range(abs(steps)):
            _puls(*step_pins)
    finally:
        drivers_off()

def move_motor(step_pin, dir_pin, steps):
    _misca(steps, [(dir_pin, 1 if steps >= 0 else 0)], [step_pin])



def forward(steps):
    _misca(steps, [(X_DIR, 1), (Y_DIR, 0)], [X_STEP, Y_STEP])
    print("Inainte terminat")

def backward(steps):
    _misca(steps, [(X_DIR, 0), (Y_DIR, 1)], [X_STEP, Y_STEP])
    print("Inapoi terminat")

def left(steps):
    _misca(steps, [(X_DIR, 0), (Y_DIR, 0)], [X_STEP, Y_STEP])
    print("Stanga terminat")

def right(steps):
    _misca(steps, [(X_DIR, 1), (Y_DIR, 1)], [X_STEP, Y_STEP])
    print("Dreapta terminat")



def pump(steps):
    move_motor(P_STEP, P_DIR, steps)
    print("Pompare terminata")




def stop_tot():
    CUTTER.value(0)
    drivers_off()
    servo1.duty_u16(0)
    servo2.duty_u16(0)
    print("Totul oprit")



_SIMPLE = {
    "ON":  freza_on,
    "OFF": freza_off,
    "J":   freza_jos,
    "S":   freza_sus,
}

_CU_NUMAR = {
    "F": forward,
    "B": backward,
    "L": left,
    "R": right,
    "X": lambda s: move_motor(X_STEP, X_DIR, s),
    "Y": lambda s: move_motor(Y_STEP, Y_DIR, s),
    "P": pump,
}

def meniu():
    print("=" * 30)
    print("Robot Ready")
    print("F/B/L/R + pasi  -> inainte/inapoi/stanga/dreapta")
    print("X/Y/P  + pasi   -> motor X / motor Y / pompa")
    print("ON / OFF        -> freza pornit / oprit")
    print("J / S           -> freza jos / sus")
    print("Q               -> iesire")
    print("=" * 30)

    while True:
        try:
            cmd = input(">>> ").strip().upper()

            if cmd == "Q":
                stop_tot()
                print("Iesire")
                break
            elif cmd in _SIMPLE:
                _SIMPLE[cmd]()
            elif cmd and cmd[0] in _CU_NUMAR:
                _CU_NUMAR[cmd[0]](int(cmd[1:]))
            elif cmd == "":
                continue
            else:
                print("Comanda necunoscuta")

        except ValueError:
            print("Numar invalid (ex: F1000)")
        except KeyboardInterrupt:
            stop_tot()
            print("\nIntrerupt")
            break
        except Exception as e:
            stop_tot()
            print("Eroare:", e)
            break