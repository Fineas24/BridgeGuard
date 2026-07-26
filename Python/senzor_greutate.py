import time
import lgpio

PIN_DT = 17
PIN_SCK = 27

class HX711:
    def __init__(self, chip, dout, sck, gain=128):
        self.h = chip
        self.dout = dout
        self.sck = sck
        self.OFFSET = 0
        self.SCALE = 1
        self.GAIN = 1 if gain == 128 else (3 if gain == 64 else 2)

        lgpio.gpio_claim_input(self.h, self.dout)
        lgpio.gpio_claim_output(self.h, self.sck, 0)

    def read(self):
        timeout = 1000
        while lgpio.gpio_read(self.h, self.dout) == 1:
            time.sleep(0.001)
            timeout -= 1
            if timeout <= 0:
                print("TIMEOUT - HX711 nu raspunde! Verifica conexiunile.")
                return 0

        result = 0
        for _ in range(24 + self.GAIN):
            lgpio.gpio_write(self.h, self.sck, 1)
            bit = lgpio.gpio_read(self.h, self.dout)
            result = (result << 1) | bit
            lgpio.gpio_write(self.h, self.sck, 0)

        result >>= self.GAIN

        if result > 0x7fffff:
            result -= 0x1000000

        return result

    def tare(self, times=20):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep(0.01)
        self.OFFSET = total / times

    def set_scale(self, scale):
        self.SCALE = scale

    def get_units(self, times=3):
        total = 0
        for _ in range(times):
            total += self.read()
            time.sleep(0.01)
        return ((total / times) - self.OFFSET) / self.SCALE


h = lgpio.gpiochip_open(0)

hx = HX711(h, PIN_DT, PIN_SCK)

CALIBRARE_KG = 420000
PRAG_AFISARE = 0.050

hx.set_scale(CALIBRARE_KG)

print("Calibrare automata la 0... Asteapta.")
hx.tare()
print("Gata! Apasa pe senzor.")
print("(Ctrl+C pentru oprire)\n")

try:
    while True:
        greutate_kg = hx.get_units(3)

        if abs(greutate_kg) > PRAG_AFISARE:
            print("Forta: {:.3f} kg".format(abs(greutate_kg)))

        time.sleep(0.2)

except KeyboardInterrupt:
    print("\nOprit.")
finally:
    lgpio.gpiochip_close(h)
