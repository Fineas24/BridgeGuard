import os
import time
from roboflow import Roboflow

API_KEY = os.environ['ROBOFLOW_API_KEY']
rf = Roboflow(api_key=API_KEY)
project = rf.workspace().project("crack-btlnb")
model = project.version(1).model

nume_foto = "poza_camera.jpg"
print("Pornesc camera... te rog așteaptă.")

os.system(f"rpicam-still -o {nume_foto} -t 2000 --immediate")

if os.path.exists(nume_foto):
    print("Captură realizată cu succes!")
else:
    print("Eroare la cameră! Fișierul nu a putut fi generat.")
    exit()

print("Analizez poza trimisă la server...")
prediction = model.predict(nume_foto, confidence=40)

nume_rezultat = "fisura_detectata_camera.jpg"
prediction.save(nume_rezultat)

print("\n--- GATA! ---")
print("Rezultatul JSON:")
print(prediction.json())
print(f"\nRezultatul a fost salvat în: {nume_rezultat}")
print("Poți descărca fișierul prin FileZilla/WinSCP pentru a-l vizualiza.")
