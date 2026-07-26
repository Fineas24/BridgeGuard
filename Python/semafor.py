import time
from gpiozero import DigitalInputDevice, LED

senzor_sharp = DigitalInputDevice(26, pull_up=False)

led_rosu = LED(19)
led_galben = LED(13)
led_verde = LED(6)

print("Sistemul de semafor inteligent a pornit!")
print("Apasă Ctrl+C în VS Code pentru oprire.")

try:
    led_verde.on()
    led_galben.off()
    led_rosu.off()

    while True:
        if senzor_sharp.value == 0:
            print("🛑 OBIECT DETECTAT! Schimbare instantanee pe ROȘU.")

            led_verde.off()
            led_galben.off()
            led_rosu.on()

            while senzor_sharp.value == 0:
                print("⏳ Obiectul este încă în fața senzorului... menținem ROȘU.")
                time.sleep(0.5)

            print("➡️ Obiectul a plecat. Pornire secvență de eliberare drum.")

            print("🛑 ROȘU (încă 2 secunde după plecare)")
            time.sleep(2)

            led_rosu.off()
            led_galben.on()
            print("🟡 GALBEN (Avertizare - 2 secunde)")
            time.sleep(2)

            led_galben.off()
            led_verde.on()
            print("🟢 VERDE (Cale liberă)")

        time.sleep(0.1)

except KeyboardInterrupt:
    print("\nSemafor oprit. Stingem toate LED-urile.")
    led_rosu.off()
    led_galben.off()
    led_verde.off()
