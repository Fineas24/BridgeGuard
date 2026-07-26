import time
from gpiozero import DigitalInputDevice

senzor_sharp = DigitalInputDevice(26, pull_up=False)

try:
    print("Testare senzor Sharp... Apasă Ctrl+C pentru oprire.")
    while True:
        if senzor_sharp.value == 0:
            print("nu ok")
        else:
            print("ok")
            
        time.sleep(0.5)

except KeyboardInterrupt:
    print("\nTest oprit.")
