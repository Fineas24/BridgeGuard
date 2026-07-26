# BridgeGuard
**Sistem inteligent de monitorizare a podurilor**

**Problema:** Podurile vechi necesită monitorizare constantă a stării structurale, dar inspecțiile manuale sunt costisitoare și ineficiente.

**Soluția noastră:** Un sistem IoT integrat care asigură monitorizare continuă 24/7, control trafic inteligent și inspecție autonomă cu raportare în timp real.

**1. BoxKit Senzori de Precizie:**
Rețea distribuită de senzori (vibrație, ultrasonic, giroscop) instalați pe structura podului pentru a captura starea în fiecare zonă critică. Datele sunt transmise în timp real către dashboard-ul central prin intermediul unei Pico W, permițând identificarea rapidă a anomaliilor structurale și a trendurilor care indicau deteriorarea progresivă a materialelor.

**2. BoxKit Sistem de Control Trafic:**
Cântărește în timp real fiecare vehicul înainte de a intra pe pod. Dacă greutatea depășește limita admisă, semaforul devine roșu. În cazul încălcării restricției, camera ESP32-CAM realizează recunoașterea automată a plăcuței de înmatriculare și salvează datele în baza de date pentru sancționare ulterioară.

**3. SpiderCam:**
O cameră pe cablu cu capacitate de inspectare sub pod, capabil să detecteze fisuri în structura inferioară cu ajutorul unui model AI antrenat. La identificarea unei fisuri, imaginea este transmisă automat pe platformă, optimizând procesul de monitorizare și permițând intervenții prompte.

**4. RoboInspect:**
Robot mobil autonom care patrulează suprafața podului și analizează starea asfaltului. La detectarea fracturilor grave, dispozitivul poate tăia marginile și aplica o substanță de etanșare provizoriu, extindând durata de viață a infrastructurii până la intervenția unei echipe de specialiști pentru reparații definitive.
