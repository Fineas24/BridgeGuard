import time
import json
import lgpio

PIN_SO  = 22
PIN_CS  = 7
PIN_SCK = 18

TEMP_FILE = '/var/www/html/proiect/temp_latest.json'

h = lgpio.gpiochip_open(4)
lgpio.gpio_claim_input(h, PIN_SO)
lgpio.gpio_claim_output(h, PIN_CS, 1)
lgpio.gpio_claim_output(h, PIN_SCK, 0)

def citeste_temperatura():
    lgpio.gpio_write(h, PIN_CS, 0)
    time.sleep(0.001)

    valoare = 0
    for i in range(16):
        lgpio.gpio_write(h, PIN_SCK, 1)
        time.sleep(0.0001)
        bit = lgpio.gpio_read(h, PIN_SO)
        valoare = (valoare << 1) | bit
        lgpio.gpio_write(h, PIN_SCK, 0)
        time.sleep(0.0001)

    lgpio.gpio_write(h, PIN_CS, 1)

    if valoare & 0x4:
        print("Eroare: Termocuplul nu este conectat corect!")
        return None

    valoare_temperatura = (valoare >> 3) & 0x0FFF
    return valoare_temperatura * 0.25

def salveaza_temperatura(temp):
    data = {
        'temperatura': temp,
        'timestamp': int(time.time())
    }
    with open(TEMP_FILE, 'w') as f:
        json.dump(data, f)

try:
    print("Citire MAX6675 (SO=GPIO22, CS=GPIO7, SCK=GPIO18)...")
    print("Ctrl+C pentru oprire")
    while True:
        temp = citeste_temperatura()
        if temp is not None:
            salveaza_temperatura(temp)
            print(f"Temperatura: {temp:.2f} °C")
        time.sleep(1)

except KeyboardInterrupt:
    print("\nProgram oprit.")
finally:
    lgpio.gpiochip_close(h)
