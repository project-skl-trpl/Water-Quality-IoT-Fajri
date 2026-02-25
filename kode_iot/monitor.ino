#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <EEPROM.h>

// ========================
// Pin Sensor
// ========================
#define PH_PIN 34
#define TDS_PIN 35
#define TURBIDITY_PIN 32

#define SAMPLES 30
#define MA_SIZE 20
#define EEPROM_SIZE 20

// ========================
// WIFI + FIREBASE
// ========================
const char* ssid = "Abu Ali";
const char* password = "kalandra123";
String firebaseHost = "https://monitoring-help-trpl-c-default-rtdb.asia-southeast1.firebasedatabase.app";
String devicePath = "/sensors/device_01";

// ========================
// Moving Average
// ========================
float ph_history[MA_SIZE], tds_history[MA_SIZE], turb_history[MA_SIZE];
int ph_ma_index = 0, tds_ma_index = 0, turb_ma_index = 0;
bool ph_ma_filled = false, tds_ma_filled = false, turb_ma_filled = false;

// ========================
// Kalibrasi pH
// ========================
float ph4_voltage = 0;
float ph7_voltage = 0;
float ph_suhu = 25.0;

// ========================
// Kalibrasi TDS
// ========================
float kFaktor = 1.0;
float tds_suhu = 25.0;

// ========================
// Kalibrasi Turbidity
// ========================
float voltageJernih = 0;
float voltageKeruh = 0;

// ========================
// Helper Functions
// ========================
float bacaVoltage(int pin) {
  int buffer[SAMPLES];
  for (int i = 0; i < SAMPLES; i++) {
    buffer[i] = analogRead(pin);
    delay(20);
  }
  for (int i = 0; i < SAMPLES - 1; i++) {
    for (int j = i + 1; j < SAMPLES; j++) {
      if (buffer[i] > buffer[j]) {
        int t = buffer[i];
        buffer[i] = buffer[j];
        buffer[j] = t;
      }
    }
  }
  long sum = 0;
  int start = SAMPLES / 4;
  int end = SAMPLES * 3 / 4;
  for (int i = start; i < end; i++) sum += buffer[i];

  return (float)sum / (end - start) * 3.3 / 4095.0;
}

float movingAverage(float val, float* history, int& index, bool& filled) {
  history[index] = val;
  index = (index + 1) % MA_SIZE;
  if (index == 0) filled = true;

  int count = filled ? MA_SIZE : index;
  float sum = 0;
  for (int i = 0; i < count; i++) sum += history[i];
  return sum / count;
}

// ========================
// pH Functions
// ========================
float voltageToPH(float voltage) {
  float kompensasi = 1.0 + 0.003 * (ph_suhu - 25.0);
  if (ph4_voltage == 0 || ph7_voltage == 0) {
    return (7.0 + ((2.5 - voltage) / 0.18)) * kompensasi;
  }
  float slope = (4.0 - 7.0) / (ph4_voltage - ph7_voltage);
  return (7.0 + slope * (voltage - ph7_voltage)) * kompensasi;
}

void loadSemuaKalibrasi() {
  EEPROM.get(0, ph4_voltage);
  EEPROM.get(4, ph7_voltage);
  EEPROM.get(8, kFaktor);
  EEPROM.get(12, voltageJernih);
  EEPROM.get(16, voltageKeruh);
  if (isnan(ph4_voltage) || ph4_voltage <= 0) ph4_voltage = 0;
  if (isnan(ph7_voltage) || ph7_voltage <= 0) ph7_voltage = 0;
  if (isnan(kFaktor) || kFaktor <= 0) kFaktor = 1.0;
  if (isnan(voltageJernih) || voltageJernih <= 0) voltageJernih = 0;
  if (isnan(voltageKeruh) || voltageKeruh <= 0) voltageKeruh = 0;
}

// ========================
// FIREBASE SEND
// ========================
void kirimKeFirebase(float ph, float tds, float turb) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  StaticJsonDocument<200> doc;
  doc["ph"] = ph;
  doc["tds"] = tds;
  doc["turbidity"] = turb;
  doc["timestamp"] = millis() / 1000;

  String jsonData;
  serializeJson(doc, jsonData);

  // Update current
  http.begin(firebaseHost + devicePath + "/current.json");
  http.addHeader("Content-Type", "application/json");
  http.PUT(jsonData);
  http.end();

  // Add logs
  http.begin(firebaseHost + devicePath + "/logs.json");
  http.addHeader("Content-Type", "application/json");
  http.POST(jsonData);
  http.end();
}

// ========================
// ARDUINO SETUP
// ========================
void setup() {
  Serial.begin(115200);
  EEPROM.begin(EEPROM_SIZE);
  loadSemuaKalibrasi();

  WiFi.begin(ssid, password);
  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected!");
}

// ========================
// MAIN LOOP
// ========================
unsigned long lastSend = 0;
const unsigned long interval = 10000; // 10 detik

void loop() {
  // Baca pH
  float ph_volt = bacaVoltage(PH_PIN);
  float ph_raw = voltageToPH(ph_volt);
  float ph_stabil = movingAverage(ph_raw, ph_history, ph_ma_index, ph_ma_filled);
  ph_stabil = constrain(ph_stabil, 0.0, 14.0);

  // Baca TDS
  float tds_volt = bacaVoltage(TDS_PIN);
  float tds_raw = tds_volt;  // langsung pakai volt
  float tds_stabil = movingAverage(tds_raw, tds_history, tds_ma_index, tds_ma_filled);

  // Baca Turbidity
  float turb_volt = bacaVoltage(TURBIDITY_PIN);
  float ntu_stabil = movingAverage(turb_volt, turb_history, turb_ma_index, turb_ma_filled);

  // Tampilkan Serial
  Serial.print("pH: "); Serial.println(ph_stabil, 2);
  Serial.print("TDS: "); Serial.println(tds_stabil, 1);
  Serial.print("Turbidity: "); Serial.println(ntu_stabil, 1);

  // Kirim tiap 10 detik
  if (millis() - lastSend >= interval) {
    lastSend = millis();
    kirimKeFirebase(ph_stabil, tds_stabil, ntu_stabil);
    Serial.println("Data terkirim ke Firebase!");
  }

  delay(1000);
}