#include <SPI.h>
#include <MFRC522.h>
#include <SoftwareSerial.h>

#define RST_PIN   9
#define SS_PIN    10
MFRC522 mfrc522(SS_PIN, RST_PIN);

#define TRIG_PIN  6
#define ECHO_PIN  7

SoftwareSerial BTSerial(2, 3); 

const int ledPutih = 4;
const int ledMerah = 5;
const int buzzer   = 8;

bool modeSiaga = true;
bool bypassUltrasonic = false;
unsigned long bypassTimer = 0;
const unsigned long bypassDuration = 7000; 

void setup() {
  Serial.begin(9600);   
  BTSerial.begin(9600); 
  
  SPI.begin();          
  mfrc522.PCD_Init();   

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(ledPutih, OUTPUT);
  pinMode(ledMerah, OUTPUT);
  pinMode(buzzer, OUTPUT);

  digitalWrite(ledPutih, LOW);
  digitalWrite(ledMerah, LOW);
  digitalWrite(buzzer, LOW);
  
  Serial.println("System Hardware Ready...");
}

void loop() {
  if (BTSerial.available() > 0) {
    String command = BTSerial.readStringUntil('\n');
    command.trim();
    
    if (command == "VALID") {
      aksesDiterima();
    } 
    else if (command == "INVALID") {
      aksesDitolak();
    } 
    else if (command == "SIAGA_ON") {
      modeSiaga = true;
    } 
    else if (command == "SIAGA_OFF") {
      modeSiaga = false;
    }
  }

  if (bypassUltrasonic && (millis() - bypassTimer > bypassDuration)) {
    bypassUltrasonic = false;
    digitalWrite(ledPutih, LOW); 
  }

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidString = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      uidString += String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
      uidString += String(mfrc522.uid.uidByte[i], HEX);
    }
    uidString.toUpperCase();
    
    BTSerial.println("SCAN:" + uidString);
    Serial.println("Kartu Ter-scan, UID: " + uidString);
    
    delay(1200); 
    mfrc522.PICC_HaltA();
    return;
  }

  if (modeSiaga && !bypassUltrasonic) {
    long durasi;
    int jarak;
    
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);
    
    durasi = pulseIn(ECHO_PIN, HIGH);
    jarak = durasi * 0.034 / 2; 

    if (jarak > 0 && jarak < 50) { 
      alarmMalingTerdeteksi();
    }
  }
  
  delay(100); 
}

void aksesDiterima() {
  bypassUltrasonic = true;
  bypassTimer = millis(); 
  
  digitalWrite(ledPutih, HIGH); 
  digitalWrite(buzzer, HIGH);   
  delay(500); 
  digitalWrite(buzzer, LOW);
}

void aksesDitolak() {
  digitalWrite(ledMerah, HIGH); 
  digitalWrite(buzzer, HIGH);   
  delay(2000);
  digitalWrite(buzzer, LOW);
  digitalWrite(ledMerah, LOW);
}

void alarmMalingTerdeteksi() {
  BTSerial.println("ALERT:MALING");
  Serial.println("⚠️ Maling Terdeteksi!");
  
  digitalWrite(ledMerah, HIGH); 
  
  for (int i = 0; i < 10; i++) { 
    digitalWrite(buzzer, HIGH);
    delay(250);
    digitalWrite(buzzer, LOW);
    delay(250);
  }
  
  digitalWrite(ledMerah, LOW);
}