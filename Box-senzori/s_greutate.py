from machine import Pin, enable_irq, disable_irq
import time


class HX711:
    def __init__(self, dout, pd_sck, gain=128):
        self.pSCK = Pin(pd_sck, mode=Pin.OUT)
        self.pOUT = Pin(dout, mode=Pin.IN, pull=Pin.PULL_DOWN)
        self.GAIN = 0
        self.OFFSET = 0
        self.SCALE = 1
        self.set_gain(gain)

    def set_gain(self, gain):
        if gain == 128: self.GAIN = 1
        elif gain == 64: self.GAIN = 3
        elif gain == 32: self.GAIN = 2
        self.pSCK.value(False)
        self.read()

    def read(self):
        while self.pOUT() == 1: pass
        result = 0
        for j in range(24 + self.GAIN):
            state = disable_irq()
            self.pSCK(True)
            self.pSCK(False)
            enable_irq(state)
            result = (result << 1) | self.pOUT()
        result >>= self.GAIN
        if result > 0x7fffff: result -= 0x1000000
        return result

    def tare(self, times=20):
        sum = 0
        for i in range(times):
            sum += self.read()
        self.OFFSET = sum / times

    def set_scale(self, scale):
        self.SCALE = scale

    def get_units(self, times=3):
        return (self.read() - self.OFFSET) / self.SCALE

hx = HX711(14, 15)
CALIBRARE_KG = 420000 
PRAG_AFISARE = 0.050   

hx.set_scale(CALIBRARE_KG)

print("Calibrare automată la 0... Te rog așteaptă.")
hx.tare()
print("Sistem pregătit. Apasă pe senzor pentru a vedea valorile.")

ultima_valoare = 0

while True:
    try:
        
        greutate_kg = hx.get_units(3)
        
        
        if abs(greutate_kg) > PRAG_AFISARE:
            print("Forță detectată: {:.3f} kg".format(abs(greutate_kg)))
            ultima_valoare = greutate_kg
        
        time.sleep(0.2) 
        
    except KeyboardInterrupt:
        break
