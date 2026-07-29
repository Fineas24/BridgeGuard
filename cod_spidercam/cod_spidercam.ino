#define EN_PIN 8

#define M1_STEP 2
#define M1_DIR  5
#define M2_STEP 3
#define M2_DIR  6
#define M3_STEP 4
#define M3_DIR  7
#define M4_STEP 12
#define M4_DIR  13

const int STEP_PINS[4] = { M1_STEP, M2_STEP, M3_STEP, M4_STEP };
const int DIR_PINS[4]  = { M1_DIR,  M2_DIR,  M3_DIR,  M4_DIR  };

const unsigned long STEP_INTERVAL_MIN_US = 700;
const unsigned long STEP_INTERVAL_MAX_US = 3000;
const unsigned long RAMP_STEP_US         = 20;
const unsigned long WATCHDOG_MS          = 400;

const int8_t CMD_FATA[4]    = { +1, -1, +1, -1 };
const int8_t CMD_SPATE[4]   = { -1, +1, -1, +1 };
const int8_t CMD_STANGA[4]  = { +1, +1, -1, -1 };
const int8_t CMD_DREAPTA[4] = { -1, -1, +1, +1 };
const int8_t CMD_SUS[4]     = { +1, +1, +1, +1 };
const int8_t CMD_JOS[4]     = { -1, -1, -1, -1 };

const int8_t MOTOR_POLARITY[4] = { +1, -1, -1, +1 };

int8_t activeDir[4] = { 0, 0, 0, 0 };
long   pos[4]       = { 0, 0, 0, 0 };
bool   stepHigh[4]  = { false, false, false, false };

bool moving = false;
bool stopping = false;
unsigned long currentIntervalUs = STEP_INTERVAL_MAX_US;
unsigned long lastStepUs = 0;
unsigned long lastCmdMs  = 0;

String inputLine = "";

void setup() {
  Serial.begin(115200);
  pinMode(EN_PIN, OUTPUT);
  digitalWrite(EN_PIN, LOW);

  for (int i = 0; i < 4; i++) {
    pinMode(STEP_PINS[i], OUTPUT);
    pinMode(DIR_PINS[i], OUTPUT);
    digitalWrite(STEP_PINS[i], LOW);
  }

  Serial.println("READY");
}

void applyCommand(const int8_t dirs[4]) {
  bool schimbata = false;
  for (int i = 0; i < 4; i++) {
    if (dirs[i] != activeDir[i]) schimbata = true;
  }
  for (int i = 0; i < 4; i++) {
    activeDir[i] = dirs[i];
    if (dirs[i] != 0) {
      int8_t dirFizic = dirs[i] * MOTOR_POLARITY[i];
      digitalWrite(DIR_PINS[i], dirFizic > 0 ? HIGH : LOW);
    }
  }
  if (schimbata || !moving) {
    currentIntervalUs = STEP_INTERVAL_MAX_US;
  }
  moving = true;
  stopping = false;
  lastCmdMs = millis();
}

void requestStop() {
  if (moving) stopping = true;
  else stopAll();
}

void stopAll() {
  moving = false;
  stopping = false;
  for (int i = 0; i < 4; i++) {
    activeDir[i] = 0;
    digitalWrite(STEP_PINS[i], LOW);
  }
}

void printPositions() {
  Serial.print("POS:");
  Serial.print(pos[0]); Serial.print(',');
  Serial.print(pos[1]); Serial.print(',');
  Serial.print(pos[2]); Serial.print(',');
  Serial.println(pos[3]);
}

void handleCommand(String cmd) {
  cmd.trim();
  cmd.toLowerCase();

  if (cmd == "fata")          applyCommand(CMD_FATA);
  else if (cmd == "spate")    applyCommand(CMD_SPATE);
  else if (cmd == "stanga")   applyCommand(CMD_STANGA);
  else if (cmd == "dreapta")  applyCommand(CMD_DREAPTA);
  else if (cmd == "sus")      applyCommand(CMD_SUS);
  else if (cmd == "jos")      applyCommand(CMD_JOS);
  else if (cmd == "stop")     requestStop();
  else if (cmd == "home") {
    stopAll();
    for (int i = 0; i < 4; i++) pos[i] = 0;
    Serial.println("HOME OK");
  }
  else if (cmd == "ping")     Serial.println("PONG");
  else if (cmd == "pos")      printPositions();
  else if (cmd.length() > 0)  Serial.println("EROARE: comanda necunoscuta");
}

void readSerial() {
  while (Serial.available()) {
    char c = Serial.read();
    if (c == '\n') {
      handleCommand(inputLine);
      inputLine = "";
    } else if (c != '\r') {
      inputLine += c;
    }
  }
}

void stepMotors() {
  unsigned long nowUs = micros();
  if (nowUs - lastStepUs < currentIntervalUs) return;
  lastStepUs = nowUs;

  for (int i = 0; i < 4; i++) {
    if (activeDir[i] == 0) continue;
    stepHigh[i] = !stepHigh[i];
    digitalWrite(STEP_PINS[i], stepHigh[i] ? HIGH : LOW);
    if (stepHigh[i]) {
      pos[i] += activeDir[i];
    }
  }

  if (stopping) {
    if (currentIntervalUs + RAMP_STEP_US >= STEP_INTERVAL_MAX_US) {
      stopAll();
    } else {
      currentIntervalUs += RAMP_STEP_US;
    }
  } else if (currentIntervalUs > STEP_INTERVAL_MIN_US) {
    unsigned long ramas = currentIntervalUs - STEP_INTERVAL_MIN_US;
    currentIntervalUs -= (ramas < RAMP_STEP_US) ? ramas : RAMP_STEP_US;
  }
}

void loop() {
  readSerial();

  if (moving) {
    if (!stopping && millis() - lastCmdMs > WATCHDOG_MS) {
      requestStop();
    }
    stepMotors();
  }
}
