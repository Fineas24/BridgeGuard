from machine import Pin
import time


sensor = Pin(26, Pin.IN)

print("Grafic setat pe scala 0-1000. Asigura-te ca View -> Plotter este deschis.")

intensitate = 0

while True:
    try:
        
        if sensor.value() == 1:
            intensitate = 1000  
        else:
            
            intensitate = intensitate * 0.92
            
       
        if intensitate < 1:
            intensitate = 0
            
     
        print((0, intensitate))
        
    
        time.sleep(0.01)
        
    except KeyboardInterrupt:
        break
  