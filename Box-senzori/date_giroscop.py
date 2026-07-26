from machine import Pin, I2C
import utime
import ustruct

i2c = I2C(1, sda=Pin(10), scl=Pin(11), freq=100000)
ADDR = 0x69

def check_sensor():
    print("--- DIAGNOSTIC SENZOR ---")
    try:
        
        who_am_i = i2c.readfrom_mem(ADDR, 0x00, 1)[0]
        status = "ACTIV / OK" if who_am_i == 0xEA else "NECUNOSCUT"
        
       
        temp_raw = i2c.readfrom_mem(ADDR, 0x39, 2)
        temp_c = (ustruct.unpack('>h', temp_raw)[0] / 333.87) + 21.0
        
        print(f"Model ID: {hex(who_am_i)} ({status})")
        print(f"Temperatura Procesor: {temp_c:.2f}°C")
        print(f"Conexiune I2C: Stabila la 100kHz")
        print("-------------------------")
    except Exception as e:
        print(f"EROARE: Senzorul nu raspunde! ({e})")


check_sensor()
