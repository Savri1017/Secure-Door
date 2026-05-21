import serial
import mysql.connector
import requests
import time

# --- KONFIGURASI BOT TELEGRAM ---
BOT_TOKEN = "8554238488:AAHSG68GHaRPmbcwm9ZHjJsxTH2sREpQeRA"
CHAT_ID = "-5266286852"

# --- KONFIGURASI SERIAL DATA KABEL ---
SERIAL_PORT = 'COM4'  
BAUD_RATE = 9600

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'security_db'
}

def kirim_notif_telegram(pesan):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
    payload = {"chat_id": CHAT_ID, "text": pesan}
    try:
        response = requests.post(url, json=payload)
        if response.status_code == 200:
            print("[TELEGRAM] Berhasil mengirimkan notifikasi ke Telegram.")
        else:
            print(f"[TELEGRAM] Gagal mengirim pesan: {response.text}")
    except Exception as e:
        print(f"[TELEGRAM EROR]: Gagal terhubung ke Telegram ({e})")

def verifikasi_dan_catat_kartu(uid_terscan):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        # Ambil status mode sistem saat ini
        cursor.execute("SELECT secure_mode FROM settings WHERE id = 1")
        system_mode = cursor.fetchone()[0]

        # ----------------- LOGIKA REGISTRASI OTOMATIS INSTAN -----------------
        if system_mode == 'register':
            cursor.execute("SELECT owner_name FROM card_table WHERE card_uid = %s", (uid_terscan,))
            existing = cursor.fetchone()

            if not existing:
                cursor.execute("INSERT INTO card_table (card_uid, owner_name) VALUES (%s, 'New User (Silakan Edit)')", (uid_terscan,))
                cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Owner', 'Kartu baru berhasil terdaftar otomatis lewat Mode Registrasi.')", (uid_terscan,))
                conn.commit()
                print(f"\n✨ [REGISTRASI OTOMATIS] Sukses mendaftarkan kartu baru! UID: {uid_terscan}")
            else:
                print(f"\nℹ️ [REGISTRASI] Kartu UID {uid_terscan} sudah terdaftar sebelumnya.")

            arduino.write(b"REG_OK\n")

        # ----------------- LOGIKA MODE AMAN (SECURE) -----------------
        else:
            cursor.execute("SELECT owner_name FROM card_table WHERE card_uid = %s", (uid_terscan,))
            result = cursor.fetchone()

            if result:
                nama_pemilik = result[0]
                print(f"\n========= [LOG AKSES: PEMILIK] =========")
                print(f"| Status    : AKSES DITERIMA ✔️")
                print(f"| Pemilik   : {nama_pemilik}")
                print(f"| UID Kartu : {uid_terscan}")
                print(f"========================================")
                
                cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Owner', %s)", 
                               (uid_terscan, f"Pemilik ({nama_pemilik}) telah membuka pintu."))
                conn.commit()
                arduino.write(b"ACC_OK\n")
            else:
                print(f"\n⚠️ [DETEKSI ANOMALI: KARTU ILEGAL] ⚠️")
                print(f"========================================")
                print(f"| Status    : AKSES DITOLAK ❌")
                print(f"| UID       : {uid_terscan}")
                print(f"========================================")
                
                cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Access Denied', 'Mencoba membobol pintu menggunakan kartu ilegal.')", (uid_terscan,))
                conn.commit()
                arduino.write(b"ACC_DENIED\n")
                
                alert_msg = f"⚠️ SECUREDOOR WARNING! ⚠️\nTerdeteksi upaya akses menggunakan kartu ilegal.\nUID Kartu: {uid_terscan}"
                # FIX: Typo nama fungsi diperbaiki di bawah ini
                kirim_notif_telegram(alert_msg)

        cursor.close()
        conn.close()
    except Exception as e:
        print(f"[DATABASE EROR]: Gagal verifikasi kartu ({e})")

def tangani_maling():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        cursor.execute("SELECT secure_mode FROM settings WHERE id = 1")
        system_mode = cursor.fetchone()[0]

        # Hanya catat maling dan kirim telegram jika website ada di SECURE MODE
        if system_mode == 'secure':
            print(f"\n🚨 [DETEKSI ANOMALI: MALING] 🚨")
            print(f"========================================")
            print(f"| Peringatan: SEORANG PENYUSUP TERDETEKSI!")
            print(f"| Jarak     : < 5cm pada sensor Ultrasonik")
            print(f"========================================")
            
            # Memasukkan log maling ke database
            cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES ('-', 'Intruder!', 'Sensor perimeter SecureDoor mendeteksi pembobolan paksa!')")
            conn.commit()
            print("[DATABASE] Sukses memasukkan data penyusup ke access_log.")
            
            # Kirim Notifikasi Bahaya ke Telegram
            breach_msg = "🚨🚨 SOS! MALING TERDETEKSI DI SECUREDOOR PINTU DEPAN! 🚨🚨\nAda objek mendekat tanpa verifikasi kartu RFID!"
            kirim_notif_telegram(breach_msg)
        else:
            print(f"\n[INFO] Ultrasonik mendeteksi objek, dilewati karena sedang dalam Mode Registrasi.")

        cursor.close()
        conn.close()
    except Exception as e:
        print(f"[DATABASE EROR]: Gagal mencatat data maling ({e})")

if __name__ == '__main__':
    print("======================================================")
    print("       SECUREDOOR SYSTEM INTERFACE: ONLINE            ")
    print("======================================================")
    try:
        arduino = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
        time.sleep(2)
        print(f"[SISTEM] Hubungan serial di {SERIAL_PORT} sukses. Mendengarkan hardware...")
        print("------------------------------------------------------")
        
        while True:
            if arduino.in_waiting > 0:
                raw_payload = arduino.readline().decode('utf-8').strip()
                
                if raw_payload.startswith("SCAN:"):
                    verifikasi_dan_catat_kartu(raw_payload.split(":")[1])
                    
                elif raw_payload == "INTRUDER_DETECTED":
                    tangani_maling()
                    
            time.sleep(0.1)
    except Exception as err:
        print(f"\n[KONEKSI SERIAL GAGAL]: {err}")