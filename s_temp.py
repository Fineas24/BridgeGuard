import glob, time, os

senzor = glob.glob('/sys/bus/w1/devices/28-*')[0] + '/w1_slave'

while True:
    with open(senzor) as f:
        date = f.read()
    temp = float(date.split('t=')[1]) / 1000.0
    os.system('clear')
    print(f"  Temperatura: {temp:.1f} °C")
    time.sleep(1)