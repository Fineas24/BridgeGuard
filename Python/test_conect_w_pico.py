from flask import Flask, request, jsonify
from datetime import datetime
import json
import time
import os
import base64
import requests
import MySQLdb

app = Flask(__name__)

TEMP_FILE   = '/var/www/html/proiect/temp_latest.json'
STATUS_FILE = '/var/www/html/proiect/Python/semafor_status.json'

CAMERA_IP      = '192.168.100.60'
CAMERA_URL     = f'http://{CAMERA_IP}/capture'
CAMERA_TIMEOUT = 10
POZE_DIR       = '/var/www/html/proiect/poze_semafor'

CLAUDE_API_KEY = os.environ.get('CLAUDE_API_KEY')
CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages'

DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS')
DB_NAME = os.environ.get('DB_NAME', 'BridgeGuard')


@app.route('/conectat', methods=['POST'])
def pico_conectat():
    data = request.get_json()
    ip_pico = data.get('ip_pico', 'necunoscut')
    print(f'\n>>> PICO S-A CONECTAT! IP Pico: {ip_pico} [{datetime.now().strftime("%H:%M:%S")}]\n')
    return jsonify({'ok': True})

@app.route('/test', methods=['POST'])
def primeste_test():
    data = request.get_json()
    print(f'[{datetime.now().strftime("%H:%M:%S")}] Am primit de la Pico: {data}')
    return jsonify({'ok': True, 'mesaj': 'RPi5 a primit datele'})

@app.route('/temperatura', methods=['POST'])
def primeste_temperatura():
    data = request.get_json(silent=True) or {}
    temp = data.get('temperatura', None)

    if temp is None:
        print(f'[{datetime.now().strftime("%H:%M:%S")}] TEMP: eroare senzor (termocuplu deconectat)')
        return jsonify({'ok': False, 'error': 'temperatura lipsa'}), 400

    try:
        temp = float(temp)
    except (TypeError, ValueError):
        return jsonify({'ok': False, 'error': 'temperatura invalida'}), 400

    with open(TEMP_FILE, 'w') as f:
        json.dump({'temperatura': temp, 'timestamp': int(time.time())}, f)

    print(f'[{datetime.now().strftime("%H:%M:%S")}] TEMP: {temp:.2f} C')
    return jsonify({'ok': True})

@app.route('/stare', methods=['POST'])
def primeste_stare():
    data = request.get_json(silent=True) or {}
    culoare  = data.get('culoare', 'necunoscut')
    greutate = data.get('greutate', 0.0)
    mesaj    = data.get('mesaj', '')

    try:
        greutate = round(float(greutate), 3)
    except (TypeError, ValueError):
        greutate = 0.0

    with open(STATUS_FILE, 'w') as f:
        json.dump({
            'culoare':   culoare,
            'detectat':  culoare == 'rosu',
            'greutate':  greutate,
            'mesaj':     mesaj,
            'timestamp': time.time()
        }, f)

    print(f'[{datetime.now().strftime("%H:%M:%S")}] SEMAFOR: {culoare} | {greutate} kg | {mesaj}')
    return jsonify({'ok': True})

@app.route('/fa-poza', methods=['POST'])
def fa_poza():
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename  = f'{POZE_DIR}/detectie_{timestamp}.jpg'

    try:
        r = requests.get(CAMERA_URL, timeout=CAMERA_TIMEOUT)
        r.raise_for_status()
        with open(filename, 'wb') as f:
            f.write(r.content)
        os.chmod(filename, 0o666)
        print(f'[{datetime.now().strftime("%H:%M:%S")}] POZA salvata: {filename}')
    except Exception as e:
        print(f'  Eroare preluare poza de la camera ({CAMERA_URL}): {e}')
        return jsonify({'ok': False, 'eroare': str(e)}), 500

    numar = citeste_numar(filename)
    if numar:
        salveaza_numar(numar, filename, timestamp)
        return jsonify({'ok': True, 'numar': numar})

    return jsonify({'ok': True, 'numar': None})


def citeste_numar(poza_path):
    try:
        with open(poza_path, 'rb') as f:
            img_data = base64.standard_b64encode(f.read()).decode('utf-8')

        r = requests.post(
            CLAUDE_API_URL,
            headers={
                'x-api-key': CLAUDE_API_KEY,
                'anthropic-version': '2023-06-01',
                'content-type': 'application/json'
            },
            json={
                'model': 'claude-haiku-4-5-20251001',
                'max_tokens': 50,
                'messages': [{
                    'role': 'user',
                    'content': [
                        {'type': 'image', 'source': {'type': 'base64', 'media_type': 'image/jpeg', 'data': img_data}},
                        {'type': 'text', 'text': 'Citeste numarul de inmatriculare din aceasta poza. Raspunde DOAR cu numarul (ex: AR 24 CMF). Daca nu gasesti niciun numar, raspunde exact: NIMIC'}
                    ]
                }]
            },
            timeout=15
        )
        raspuns = r.json().get('content', [{}])[0].get('text', '').strip()
        print(f'  Claude raspuns: {raspuns}')
        if raspuns and raspuns != 'NIMIC' and len(raspuns) >= 4:
            print(f'  NUMAR DETECTAT: {raspuns}')
            return raspuns
        print('  Niciun numar detectat in poza.')
        return None
    except Exception as e:
        print(f'  Eroare Claude API: {e}')
        return None


def salveaza_numar(numar, poza_path, timestamp):
    try:
        conn = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME)
        cursor = conn.cursor()
        cursor.execute(
            'INSERT INTO numere_masini (numar, poza, data_detectie) VALUES (%s, %s, %s)',
            (numar, poza_path, datetime.now())
        )
        conn.commit()
        cursor.close()
        conn.close()
        print(f'  Salvat in DB: {numar}')
    except Exception as e:
        print(f'  Eroare salvare DB: {e}')


if __name__ == '__main__':
    print('Server pornit, astept date de la Pico pe portul 5005...')
    app.run(host='0.0.0.0', port=5005)
