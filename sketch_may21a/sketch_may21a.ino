#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN   9
#define SS_PIN    10
MFRC522 mfrc522(SS_PIN, RST_PIN);

#define TRIG_PIN  6
#define ECHO_PIN  7

const int ledWhite = 4;
const int ledRed   = 5;
const int buzzer   = 8;

bool bypassUltrasonic = false;
unsigned long bypassTimer = 0;
const unsigned long bypassDuration = 5000; // UPDATED: Ultrasonik mati selama 5 detik

void setup() {
  Serial.begin(9600);      
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
  // Cek apakah masa jeda (bypass) sensor ultrasonik 5 detik sudah habis
  if (bypassUltrasonic && (millis() - bypassTimer >= bypassDuration)) {
    bypassUltrasonic = false;
    digitalWrite(ledWhite, LOW); // Matikan lampu putih kembali setelah 5 detik
  }

  // 1. MEMBACA PINDAIAN KARTU RFID
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uidString = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      uidString += String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
      uidString += String(mfrc522.uid.uidByte[i], HEX);
      if (i < mfrc522.uid.size - 1) uidString += " ";
    }
    uidString.toUpperCase();

    // UPDATED: Setiap ada kartu terdeteksi, buzzer aktif 0.5 detik sebagai indikator membaca
    digitalWrite(buzzer, HIGH);
    delay(500);
    digitalWrite(buzzer, LOW);
    
    // Kirim data UID lewat Kabel USB ke Python
    Serial.print("SCAN:" + uidString + "\n");
    
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    delay(1000); // Mencegah double scan cepat
  }

  // 2. MENERIMA BALASAN KEPUTUSAN DARI PYTHON LEWAT KABEL USB
  if (Serial.available()) {
    String response = Serial.readStringUntil('\n');
    response.trim();

    if (response == "ACC_OK") {
      // Skenario 1.a: Pemilik Rumah Valid (Secure Mode)
      digitalWrite(ledWhite, HIGH);
      
      // Mengaktifkan bypass ultrasonik selama 5 detik agar bisa masuk
      bypassUltrasonic = true;
      bypassTimer = millis();
      
    } else if (response == "REG_OK") {
      // Skenario Mode Registrasi: Kartu berhasil ditangkap untuk didaftarkan
      // Nyalakan lampu putih berkedip 2 kali sebagai penanda sukses terbaca sistem pendaftaran
      for(int i=0; i<2; i++){
        digitalWrite(ledWhite, HIGH);
        delay(150);
        digitalWrite(ledWhite, LOW);
        delay(150);
      }
      
    } else if (response == "ACC_DENIED") {
      // Skenario 1.b: Kartu Salah / Ilegal (Buzzer menyala kembali 2 detik sebagai alarm peringatan)
      digitalWrite(ledRed, HIGH);
      digitalWrite(buzzer, HIGH);
      delay(2000); 
      digitalWrite(buzzer, LOW);
      digitalWrite(ledRed, LOW);
    }
  }

  // 3. DETEKSI PERGERAKAN PENYUSUP (ULTRASONIK)
  if (!bypassUltrasonic) {
    long duration, distance;
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);
    
    duration = pulseIn(ECHO_PIN, HIGH);
    distance = duration * 0.034 / 2;

    // Batasan jarak deteksi 5 cm
    if (distance > 0 && distance < 5) { 
      Serial.print("INTRUDER_DETECTED\n");

      // Alarm berbunyi keras patah-patah selama 5 detik & LED Merah Menyala
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