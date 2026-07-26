from machine import Pin, PWM

servo1 = PWM(Pin(18))
servo2 = PWM(Pin(19))
servo1.freq(50)
servo2.freq(50)

def unghi_catre_duty(grade):
    ms = 0.5 + (grade / 180) * 2.0
    duty = int((ms / 20) * 65535)
    return duty

def misca_ambele(grade):
    grade = max(0, min(180, grade))   
    servo1.duty_u16(unghi_catre_duty(grade))          
    servo2.duty_u16(unghi_catre_duty(180 - grade))    

print("exemplu comanda: 90 si enter")
print("sus inseamna ca este 150 si pozitia corect jos e 25")

while True:
    cmd = input("> ").strip()
    if cmd == 'q':
        break
    try:
        grade = int(cmd)
        misca_ambele(grade)
        print("Servouri la unghiul", grade)
    except ValueError:
        print("un numar intre 0 si 180")