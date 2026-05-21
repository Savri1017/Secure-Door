#include <SPI.h>
#include <MFRC522.h>
#include <SoftwareSerial.h>

#define RST_PIN   9
#define SS_PIN    10
MFRC522 mfrc522(SS_PIN, RST_PIN);

#define TRIG_PIN  6
#define ECHO_PIN  7

SoftwareSerial bluetooth(2, 3);

const int ledWhite = 4;
const int ledRed   = 5;
const int buzzer   = 8;

bool bypassUltrasonic = false;
unsigned long bypassTimer = 0;
const unsigned long bypassDuration = 5000;

void setup() {
  Serial.begin(9600);
  bluetooth.begin(9600);
  
  SPI.begin();
  mfrc522.PCD_Init();

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(ledWhite, OUTPUT);
  pinMode(ledRed, OUTPUT);
  pinMode(buzzer, OUTPUT);

  digitalWrite(ledWhite, LOW);
  digitalWrite(ledRed, LOW);
  digitalWrite(buzzer, LOW);
}

void loop() {
  if (bypassUltrasonic && (millis() - bypassTimer >= bypassDuration)) {
    bypassUltrasonic = false;
    digitalWrite(ledWhite, LOW);
  }

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidString = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      uidString += String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
      uidString += String(mfrc522.uid.uidByte[i], HEX);
      if (i < mfrc522.uid.size - 1) uidString += " ";
    }
    uidString.toUpperCase();

    digitalWrite(buzzer, HIGH);
    delay(500);
    digitalWrite(buzzer, LOW);
    
    bluetooth.print("SCAN:" + uidString + "\n");
    Serial.println("Sent via BT: SCAN:" + uidString);
    
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    delay(1000);
  }

  if (bluetooth.available()) {
    String response = bluetooth.readStringUntil('\n');
    response.trim();
    Serial.println("Received from BT: " + response);

    if (response == "ACC_OK") {
      digitalWrite(ledWhite, HIGH);
      bypassUltrasonic = true;
      bypassTimer = millis();
      
    } else if (response == "REG_OK") {
      for(int i=0; i<2; i++){
        digitalWrite(ledWhite, HIGH);
        delay(150);
        digitalWrite(ledWhite, LOW);
        delay(150);
      }
      
    } else if (response == "ACC_DENIED") {
      digitalWrite(ledRed, HIGH);
      digitalWrite(buzzer, HIGH);
      delay(2000); 
      
      digitalWrite(buzzer, LOW);
      digitalWrite(ledRed, LOW);
    }
  }

  if (!bypassUltrasonic) {
    long duration, distance;
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);
    
    duration = pulseIn(ECHO_PIN, HIGH);
    distance = duration * 0.034 / 2;

    if (distance > 0 && distance < 5) { 
      bluetooth.print("INTRUDER_DETECTED\n");
      Serial.println("Sent via BT: INTRUDER_DETECTED");

      digitalWrite(ledRed, HIGH);
      for (int i = 0; i < 10; i++) { 
        digitalWrite(buzzer, HIGH);
        delay(250);
        digitalWrite(buzzer, LOW);
        delay(250);
      }
      digitalWrite(ledRed, LOW);
    }
  }
  
  delay(100);
}