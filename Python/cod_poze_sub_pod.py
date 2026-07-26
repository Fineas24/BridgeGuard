import os
import time
from flask import Flask, request

app = Flask(__name__)

FOLDER_SALVARE = "imagini_capturate"
os.makedirs(FOLDER_SALVARE, exist_ok=True)

@app.route('/upload', methods=['POST'])
def upload_file():
    if request.content_type == 'image/jpeg':
        nume_fisier = f"{FOLDER_SALVARE}/foto_{int(time.time())}.jpg"
        
        with open(nume_fisier, 'wb') as f:
            f.write(request.data)
            
        print(f"[SERVER RPI 5] Am primit o poză nouă de la ESP32-CAM și am salvat-o în: {nume_fisier}")
        return "Imagine salvată cu succes pe Raspberry Pi 5!", 200
    else:
        print("[AVERTISMENT] Date primite în format incorect (nu sunt JPEG)!")
        return "Format invalid, serverul acceptă doar JPEG", 400

if __name__ == '__main__':
    print("--- Pornesc Serverul Web pe Raspberry Pi 5 ---")
    print("Aplicația ascultă pe portul 5001...")
    app.run(host='0.0.0.0', port=5002)
