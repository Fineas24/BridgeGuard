import os
import time

print("Pornesc camera...")
os.system("rpicam-still -o poza_initiala.jpg --immediate")

if os.path.exists("poza_initiala.jpg"):
    print("Succes! Poza a fost salvată ca: poza_initiala.jpg")
else:
    print("Eroare: Camera nu a putut genera imaginea.")
