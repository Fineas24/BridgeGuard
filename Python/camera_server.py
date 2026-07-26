import os
import io
import json
import shutil
import time
import subprocess
import threading
from datetime import datetime
from flask import Flask, Response, jsonify
from PIL import Image, ImageDraw, ImageFont

app = Flask(__name__)

BASE_DIR = '/var/www/html/proiect'
CAPTURE_DIR = os.path.join(BASE_DIR, 'uploads', 'captures')
DETECT_DIR = os.path.join(BASE_DIR, 'uploads', 'detections')
VENV_PYTHON = os.path.join(BASE_DIR, 'Python', 'detect_poze', 'bin', 'python3')
TEST_IMAGE = os.path.join(BASE_DIR, 'Python', 'poza_camera.jpg')

os.makedirs(CAPTURE_DIR, exist_ok=True)
os.makedirs(DETECT_DIR, exist_ok=True)

picam2 = None
CAMERA_AVAILABLE = False

try:
    from picamera2 import Picamera2
    cam_list = Picamera2.global_camera_info()
    if len(cam_list) > 0:
        picam2 = Picamera2()
        video_config = picam2.create_video_configuration(
            main={"size": (1280, 720), "format": "RGB888"}
        )
        picam2.configure(video_config)
        picam2.start()
        CAMERA_AVAILABLE = True
        print("[OK] Camera detectată și pornită!")
    else:
        print("[WARN] Nicio cameră detectată — mod fallback activ")
except Exception as e:
    print(f"[WARN] Camera indisponibilă ({e}) — mod fallback activ")

camera_lock = threading.Lock()


def make_fallback_frame(text="CAMERA OFFLINE"):
    img = Image.new('RGB', (1280, 720), color=(6, 10, 19))
    draw = ImageDraw.Draw(img)

    for x in range(0, 1280, 80):
        draw.line([(x, 0), (x, 720)], fill=(0, 180, 230, 20), width=1)
    for y in range(0, 720, 80):
        draw.line([(0, y), (1280, y)], fill=(0, 180, 230, 20), width=1)

    try:
        font_big = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf", 48)
        font_sm = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf", 20)
    except Exception:
        font_big = ImageFont.load_default()
        font_sm = font_big

    bbox = draw.textbbox((0, 0), text, font=font_big)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    draw.text(((1280 - tw) // 2, (720 - th) // 2 - 30), text, fill=(0, 212, 255), font=font_big)

    sub = "Conectează camera RPi5 și repornește serverul"
    bbox2 = draw.textbbox((0, 0), sub, font=font_sm)
    tw2 = bbox2[2] - bbox2[0]
    draw.text(((1280 - tw2) // 2, (720 + th) // 2 + 10), sub, fill=(90, 106, 128), font=font_sm)

    ts = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    draw.text((20, 690), ts, fill=(58, 74, 94), font=font_sm)

    return img


def generate_mjpeg():
    while True:
        try:
            if CAMERA_AVAILABLE:
                frame = picam2.capture_array()
                img = Image.fromarray(frame)
            else:
                img = make_fallback_frame()

            buf = io.BytesIO()
            img.save(buf, format='JPEG', quality=65)
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + buf.getvalue() + b'\r\n')

            time.sleep(0.05 if CAMERA_AVAILABLE else 1.0)
        except Exception:
            time.sleep(0.5)


@app.route('/stream')
def stream():
    return Response(
        generate_mjpeg(),
        mimetype='multipart/x-mixed-replace; boundary=frame'
    )


@app.route('/capture', methods=['POST'])
def capture():
    timestamp = datetime.now().strftime('%d-%m-%Y___%H-%M')
    filename = f'{timestamp}.jpg'
    filepath = os.path.join(CAPTURE_DIR, filename)

    if CAMERA_AVAILABLE:
        with camera_lock:
            picam2.capture_file(filepath)
    else:
        if os.path.exists(TEST_IMAGE):
            shutil.copy2(TEST_IMAGE, filepath)
        else:
            img = make_fallback_frame("CAPTURĂ TEST")
            img.save(filepath, 'JPEG', quality=90)

    return jsonify({
        'success': True,
        'filename': filename,
        'path': f'uploads/captures/{filename}',
        'timestamp': timestamp,
        'camera_live': CAMERA_AVAILABLE
    })


@app.route('/detect', methods=['POST'])
def detect():
    timestamp = datetime.now().strftime('%d-%m-%Y___%H-%M')
    cap_filename = f'{timestamp}.jpg'
    det_filename = f'{timestamp}_fisuri.jpg'
    cap_path = os.path.join(CAPTURE_DIR, cap_filename)
    det_path = os.path.join(DETECT_DIR, det_filename)

    if CAMERA_AVAILABLE:
        with camera_lock:
            picam2.capture_file(cap_path)
    else:
        if os.path.exists(TEST_IMAGE):
            shutil.copy2(TEST_IMAGE, cap_path)
        else:
            img = make_fallback_frame("CAPTURĂ TEST")
            img.save(cap_path, 'JPEG', quality=90)

    detect_script = f"""
import json, sys, os
from roboflow import Roboflow

rf = Roboflow(api_key=os.environ['ROBOFLOW_API_KEY'])
project = rf.workspace().project("crack-btlnb")
model = project.version(1).model

prediction = model.predict("{cap_path}", confidence=40)
prediction.save("{det_path}")

results = prediction.json()
predictions = results.get('predictions', []) if isinstance(results, dict) else results
output = {{
    'has_cracks': len(predictions) > 0,
    'count': len(predictions),
    'predictions': predictions
}}
print(json.dumps(output))
"""

    try:
        result = subprocess.run(
            [VENV_PYTHON, '-c', detect_script],
            capture_output=True, text=True, timeout=120,
            cwd=os.path.join(BASE_DIR, 'Python')
        )

        if result.returncode == 0:
            detection_data = json.loads(result.stdout.strip().split('\n')[-1])
        else:
            detection_data = {
                'has_cracks': False,
                'count': 0,
                'predictions': [],
                'error': result.stderr[-500:] if result.stderr else 'Unknown error'
            }
    except subprocess.TimeoutExpired:
        detection_data = {'has_cracks': False, 'count': 0, 'error': 'Timeout'}
    except Exception as e:
        detection_data = {'has_cracks': False, 'count': 0, 'error': str(e)}

    return jsonify({
        'success': True,
        'capture': f'uploads/captures/{cap_filename}',
        'detection': f'uploads/detections/{det_filename}',
        'cap_filename': cap_filename,
        'det_filename': det_filename,
        'timestamp': timestamp,
        'camera_live': CAMERA_AVAILABLE,
        **detection_data
    })


@app.route('/photos/captures')
def list_captures():
    files = []
    if os.path.exists(CAPTURE_DIR):
        for f in sorted(os.listdir(CAPTURE_DIR), reverse=True):
            if f.endswith('.jpg'):
                fpath = os.path.join(CAPTURE_DIR, f)
                stat = os.stat(fpath)
                files.append({
                    'filename': f,
                    'path': f'uploads/captures/{f}',
                    'size': stat.st_size,
                    'date': datetime.fromtimestamp(stat.st_mtime).strftime('%Y-%m-%d %H:%M:%S')
                })
    return jsonify(files)


@app.route('/photos/detections')
def list_detections():
    files = []
    if os.path.exists(DETECT_DIR):
        for f in sorted(os.listdir(DETECT_DIR), reverse=True):
            if f.endswith('.jpg'):
                fpath = os.path.join(DETECT_DIR, f)
                stat = os.stat(fpath)
                files.append({
                    'filename': f,
                    'path': f'uploads/detections/{f}',
                    'size': stat.st_size,
                    'date': datetime.fromtimestamp(stat.st_mtime).strftime('%Y-%m-%d %H:%M:%S')
                })
    return jsonify(files)


@app.route('/status')
def status():
    return jsonify({
        'online': True,
        'camera_available': CAMERA_AVAILABLE,
        'test_image_available': os.path.exists(TEST_IMAGE),
        'captures_count': len(os.listdir(CAPTURE_DIR)) if os.path.exists(CAPTURE_DIR) else 0,
        'detections_count': len(os.listdir(DETECT_DIR)) if os.path.exists(DETECT_DIR) else 0
    })


if __name__ == '__main__':
    print("=" * 50)
    print("  BridgeGuard Camera Server")
    print(f"  Camera: {'CONECTATĂ' if CAMERA_AVAILABLE else 'OFFLINE (mod fallback)'}")
    print(f"  Test image: {'DA' if os.path.exists(TEST_IMAGE) else 'NU'}")
    print("  Port: 5001")
    print("=" * 50)
    app.run(host='0.0.0.0', port=5001, threaded=True)
