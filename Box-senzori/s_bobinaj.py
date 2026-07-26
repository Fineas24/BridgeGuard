from machine import Pin
import time


buton = Pin(16, Pin.IN, Pin.PULL_UP)

while True:
    if buton.value() == 0:  
        print("Întrerupător ACTIVAT!")
    else:
        print("Întrerupător oprit...")
    
    time.sleep(0.2)
