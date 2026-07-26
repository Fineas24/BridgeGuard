import json
import os
import base64
import requests
import MySQLdb
from datetime import datetime
from flask import Flask, request, jsonify

app = Flask(__name__)

ESP32_CAM_IP = '192.168.100.218'
ESP32_CAM_CAPTURE_URL = f'http://{ESP32_CAM_IP}/capture'

STATUS_FILE = '/var/www/html/proiect/Python/semafor_status.json'
POZE_DIR = '/var/www/html/proiect/poze_semafor'

CLAUDE_API_KEY = os.environ.get('CLAUDE_API_KEY')
CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages'

DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = os.environ.get('DB_PASS')
DB_NAME = 'BridgeGuard'


@app.route('/stare', methods=['POST'])
def primeste_stare():
    data = request.get_json()
    culoare = data.get('culoare', 'necunoscut')
    greutate = data.get('greutate', 0.0)
    mesaj = data.get('mesaj', '')

    try:
        with open(STATUS_FILE, 'w') as f:
            json.dump({
                'culoare': culoare,
                'detectat': culoare == 'rosu',
                'greutate': round(greutate, 3),
                'mesaj': mesaj,
                'timestamp': datetime.now().timestamp()
            }, f)
    except Exception as e:
        print(f'Eroare scriere status: {e}')

    print(f'[STARE] {culoare} | {greutate} kg | {mesaj}')
    return jsonify({'ok': True})


@app.route('/fa-poza', methods=['POST'])
def fa_poza():
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename = f'{POZE_DIR}/detectie_{timestamp}.jpg'

    try:
        r = requests.get(ESP32_CAM_CAPTURE_URL, timeout=10)
        r.raise_for_status()
        with open(filename, 'wb') as f:
            f.write(r.content)
        os.chmod(filename, 0o666)
        print(f'  Poza salvata: {filename}')
    except Exception as e:
        print(f'  Eroare preluare poza de la ESP32-CAM: {e}')
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
                        {
                            'type': 'image',
                            'source': {
                                'type': 'base64',
                                'media_type': 'image/jpeg',
                                'data': img_data
                            }
                        },
                        {
                            'type': 'text',
                            'text': 'Citeste numarul de inmatriculare din aceasta poza. Raspunde DOAR cu numarul (ex: AR 24 CMF). Daca nu gasesti niciun numar, raspunde exact: NIMIC'
                        }
                    ]
                }]
            },
            timeout=15
        )

        rezultat = r.json()
        raspuns = rezultat.get('content', [{}])[0].get('text', '').strip()
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
    if not CLAUDE_API_KEY or not DB_PASS:
        print('ATENTIE: seteaza CLAUDE_API_KEY si DB_PASS ca variabile de mediu inainte de a porni!')
    app.run(host='0.0.0.0', port=5000)