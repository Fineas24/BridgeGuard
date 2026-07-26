from flask import Flask, request, jsonify
import json
import time

app = Flask(__name__)

SENSORS_FILE = '/var/www/html/proiect/sensors_latest.json'
GYRO_FILE    = '/var/www/html/proiect/gyro_latest.json'

@app.route('/date', methods=['POST'])
def primire():
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"status": "error", "msg": "Date invalide"}), 400

    ts = time.time()

    gyro       = data.get("gyro", {})
    vibratie   = data.get("vibratie", 0)
    ultrasonic = data.get("ultrasonic", -1)
    bobinaj    = data.get("bobinaj", 1)

    payload = {
        "timestamp":  ts,
        "gyro": {
            "fata_spate":     round(float(gyro.get("fata_spate") or 0), 1),
            "stanga_dreapta": round(float(gyro.get("stanga_dreapta") or 0), 1)
        },
        "vibratie":   round(float(vibratie), 1),
        "ultrasonic": round(float(ultrasonic), 1),
        "bobinaj":    int(bobinaj)
    }

    with open(SENSORS_FILE, 'w') as f:
        json.dump(payload, f)

    with open(GYRO_FILE, 'w') as f:
        json.dump({
            "fata_spate":     payload["gyro"]["fata_spate"],
            "stanga_dreapta": payload["gyro"]["stanga_dreapta"],
            "timestamp":      ts
        }, f)

    g = payload["gyro"]
    t = time.strftime('%H:%M:%S')
    b = "OK " if payload["bobinaj"] else "RUP"
    print(f"[{t}] Gyro:{g['fata_spate']}/{g['stanga_dreapta']}deg | Dist:{payload['ultrasonic']}cm | Vibr:{payload['vibratie']} | Cablu:{b}")

    return jsonify({"status": "ok"})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)
