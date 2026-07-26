from http.server import HTTPServer, BaseHTTPRequestHandler
import serial
import serial.tools.list_ports
import threading
import json
import time
import sys
import glob

BAUD_RATE = 115200
HTTP_PORT = 5002

ser = None
ser_lock = threading.Lock()
positions = [0, 0, 0, 0]


def find_arduino():
    ports = sorted(glob.glob('/dev/ttyACM*')) + sorted(glob.glob('/dev/ttyUSB*'))
    if ports:
        return ports[0]
    return None


def connect_serial():
    global ser
    port = find_arduino()
    if not port:
        print("[EROARE] Nu am gasit niciun Arduino conectat!")
        return False
    try:
        if ser and ser.is_open:
            ser.close()
        ser = serial.Serial(port, BAUD_RATE, timeout=2)
        time.sleep(2)
        while ser.in_waiting:
            ser.readline()
        print(f"[OK] Conectat la {port}")
        return True
    except Exception as e:
        print(f"[EROARE] Nu pot deschide {port}: {e}")
        return False


def send_command(cmd):
    global positions, ser
    with ser_lock:
        if ser is None or not ser.is_open:
            if not connect_serial():
                return {"ok": False, "error": "Arduino deconectat", "positions": positions[:]}
        try:
            ser.write(f"{cmd}\n".encode())
            time.sleep(0.3)
            lines = []
            while ser.in_waiting:
                line = ser.readline().decode(errors='ignore').strip()
                if line:
                    lines.append(line)
                    if "Pozitie actuala:" in line:
                        parts = line.split("Motor ")
                        if len(parts) > 1:
                            motor_num = int(parts[1][0]) - 1
                            pos = int(line.split("Pozitie actuala:")[1].strip())
                            if 0 <= motor_num < 4:
                                positions[motor_num] = pos
                    elif "pozitia initiala" in line.lower() or "pozitia 0" in line.lower():
                        positions = [0, 0, 0, 0]
            return {"ok": True, "response": "\n".join(lines), "positions": positions[:]}
        except Exception as e:
            try:
                ser.close()
            except:
                pass
            ser = None
            return {"ok": False, "error": str(e), "positions": positions[:]}


class Handler(BaseHTTPRequestHandler):
    def do_OPTIONS(self):
        self.send_response(200)
        self._cors()
        self.end_headers()

    def do_GET(self):
        self.send_response(200)
        self._cors()
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        connected = ser is not None and ser.is_open
        if not connected:
            connected = connect_serial()
        data = {"connected": connected, "positions": positions[:]}
        self.wfile.write(json.dumps(data).encode())

    def do_POST(self):
        length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(length)
        try:
            data = json.loads(body)
            cmd = data.get('command', '')
            result = send_command(cmd)
        except Exception as e:
            result = {"ok": False, "error": str(e)}

        self.send_response(200)
        self._cors()
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(result).encode())

    def _cors(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')

    def log_message(self, format, *args):
        print(f"[HTTP] {args[0]}")


if __name__ == '__main__':
    if not connect_serial():
        print("[WARN] Arduino nu e conectat — se va reconecta automat cand e disponibil")
    server = HTTPServer(('0.0.0.0', HTTP_PORT), Handler)
    print(f"[OK] Server HTTP pe port {HTTP_PORT}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n[STOP] Oprire server...")
        if ser and ser.is_open:
            ser.close()
