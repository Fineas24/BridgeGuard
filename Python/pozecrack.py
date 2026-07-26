import os
from roboflow import Roboflow

rf = Roboflow(api_key=os.environ['ROBOFLOW_API_KEY'])
project = rf.workspace().project("crack-btlnb")
model = project.version(1).model

print("Analizez poza reală...")
prediction = model.predict("fisura_asfalt.jpg", confidence=40)

prediction.save("fisura_detectata.jpg")

print(prediction.json())
print("Gata! Deschide fisura_detectata.jpg de pe desktop.")
