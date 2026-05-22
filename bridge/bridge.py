import serial
import mysql.connector
import requests
import time
import os
from datetime import datetime

BOT_TOKEN = "8554238488:AAHSG68GHaRPmbcwm9ZHjJsxTH2sREpQeRA"
CHAT_ID = "-5266286852"

SERIAL_PORT = 'COM5'  
BAUD_RATE = 9600

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'security_db'
}

RESET = "\033[0m"
BOLD = "\033[1m"
MERAH = "\033[31m"
HIJAU = "\033[32m"
KUNING = "\033[33m"
BIRU = "\033[34m"
CYAN = "\033[36m"
BG_MERAH = "\033[41m\033[37m"

def kirim_notif_telegram(pesan):
    url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
    payload = {"chat_id": CHAT_ID, "text": pesan, "parse_mode": "Markdown"}
    try:
        response = requests.post(url, json=payload)
        if response.status_code == 200:
            print("[TELEGRAM] Berhasil mengirimkan notifikasi ke Telegram.")
        else:
            print(f"[TELEGRAM] Gagal mengirim pesan: {response.text}")
    except Exception as e:
        print(f"[TELEGRAM EROR]: Gagal terhubung ke Telegram ({e})")

def dapatkan_status_mode(conn, cursor):
    conn.commit()
    cursor.execute("SELECT secure_mode FROM settings WHERE id = 1")
    row = cursor.fetchone()
    return row[0] if row else 'secure'

def tampilkan_dashboard_terminal(mode, log_terakhir="Belum ada aktivitas baru"):
    os.system('cls' if os.name == 'nt' else 'clear')
    waktu_sekarang = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    
    print(f"{BIRU}{BOLD}======================================================={RESET}")
    print(f"{BIRU}{BOLD}          SECUREDOOR - ENGINE GATEWAY CORE             {RESET}")
    print(f"{BIRU}{BOLD}======================================================={RESET}")
    print(f"{BOLD} Waktu Sistem : {RESET}{CYAN}{waktu_sekarang} WIB{RESET}")
    
    if mode == 'secure':
        print(f"{BOLD} Status Mode  : {RESET}{HIJAU}{BOLD}[ 🔒 MODE AMAN - PROTEKSI PERIMETER AKTIF ]{RESET}")
    else:
        print(f"{BOLD} Status Mode  : {RESET}{KUNING}{BOLD}[ ⚡ MODE REGISTRASI - SIAP TERIMA KARTU ]{RESET}")
        
    print(f"{BIRU}-------------------------------------------------------{RESET}")
    print(f"{BOLD} EVENT LOG TERAKHIR:{RESET}")
    print(f" {log_terakhir}")
    print(f"{BIRU}======================================================={RESET}")
    print(f"{BOLD}Status:{RESET} Mendengarkan nirkabel HC-05 pada {KUNING}{SERIAL_PORT}{RESET}...")

def verifikasi_dan_catat_kartu(uid_terscan, system_mode, conn, cursor):
    waktu_log = datetime.now().strftime('%H:%M:%S')
    
    if system_mode == 'register':
        cursor.execute("SELECT owner_name FROM card_table WHERE card_uid = %s", (uid_terscan,))
        existing = cursor.fetchone()

        if not existing:
            cursor.execute("INSERT INTO card_table (card_uid, owner_name) VALUES (%s, 'New User (Silakan Edit)')", (uid_terscan,))
            cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Owner', 'Kartu baru terdaftar otomatis via Mode Registrasi.')", (uid_terscan,))
            conn.commit()
            arduino.write(b"REG_OK\n")
            return f"{HIJAU}{BOLD}[{waktu_log}] Registrasi Sukses! Kartu Baru: {uid_terscan}{RESET}"
        else:
            arduino.write(b"REG_OK\n")
            return f"{KUNING}[{waktu_log}] Kartu UID {uid_terscan} sudah ada di database.{RESET}"

    else:
        cursor.execute("SELECT owner_name FROM card_table WHERE card_uid = %s", (uid_terscan,))
        result = cursor.fetchone()

        if result:
            nama_pemilik = result[0]
            cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Owner', %s)", 
                           (uid_terscan, f"Pemilik ({nama_pemilik}) telah membuka pintu."))
            conn.commit()
            arduino.write(b"ACC_OK\n")
            return f"{HIJAU}[{waktu_log}] Akses Diterima: {nama_pemilik} ({uid_terscan}){RESET}"
        else:
            cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES (%s, 'Access Denied', 'Mencoba membobol pintu menggunakan kartu ilegal.')", (uid_terscan,))
            conn.commit()
            arduino.write(b"ACC_DENIED\n")
            
            waktu_jam = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            alert_msg = (
                f"⚠️ *SECUREDOOR ANOMALI ALERT!* ⚠️\n"
                f"───────────────────────\n"
                f"🚫 *Status:* AKSES DITOLAK (KARTU ILEGAL)\n"
                f"💳 *UID Kartu:* `{uid_terscan}`\n"
                f"⏰ *Waktu Kejadian:* {waktu_jam} WIB\n"
                f"📝 *Keterangan:* Ada seseorang mencoba membuka pintu paksa menggunakan kartu RFID tidak terdaftar!"
            )
            kirim_notif_telegram(alert_msg)
            return f"{MERAH}{BOLD}[{waktu_log}] Akses Ditolak! Kartu Ilegal: {uid_terscan}{RESET}"

def tangani_maling(system_mode, conn, cursor):
    waktu_log = datetime.now().strftime('%H:%M:%S')
    
    if system_mode == 'secure':
        cursor.execute("INSERT INTO access_log (card_uid, status, details) VALUES ('-', 'Intruder!', 'Sensor perimeter SecureDoor mendeteksi pembobolan paksa!')")
        conn.commit()
        
        waktu_jam = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        breach_msg = (
            f"🚨🚨 *SECURITY BREACH DETECTED* 🚨🚨\n"
            f"⚠️ *PERGERAKAN ANOMALI TERDETEKSI* ⚠️\n"
            f"───────────────────────\n"
            f"👤 *Aktivitas:* Indikasi Penyusup / Pembobolan Paksa!\n"
            f"📏 *Sensor Jarak:* Terdeteksi Objek < 5 cm (Sangat Dekat)\n"
            f"⏰ *Waktu Kejadian:* {waktu_jam} WIB\n"
            f"📢 *Aksi Sistem:* Alarm Fisik & LED Red Aktif Penuh!\n"
            f"───────────────────────\n"
            f"‼️ *Peringatan:* Mohon segera periksa CCTV perimeter atau lokasi rumah sekarang juga!"
        )
        kirim_notif_telegram(breach_msg)
        return f"{BG_MERAH}{BOLD} ⚠️ [{waktu_log}] Terdeteksi Pergerakan Anomali! (Maling) {RESET}"
    else:
        return f"{KUNING}[{waktu_log}] Ultrasonik mendeteksi objek, diabaikan pada Mode Registrasi.{RESET}"

if __name__ == '__main__':
    print("Menginisialisasi koneksi perangkat nirkabel...")
    try:
        arduino = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
        time.sleep(2)
        
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        log_status = f"{HIJAU}Sistem terhubung. Memulai pemantauan nirkabel...{RESET}"
        waktu_terakhir_cek_mode = 0
        current_mode = dapatkan_status_mode(conn, cursor)

        while True:
            waktu_loop = time.time()
            
            if waktu_loop - waktu_terakhir_cek_mode > 1.0:
                current_mode = dapatkan_status_mode(conn, cursor)
                tampilkan_dashboard_terminal(current_mode, log_status)
                waktu_terakhir_cek_mode = waktu_loop
            
            if arduino.in_waiting > 0:
                raw_payload = arduino.readline().decode('utf-8', errors='ignore').strip()
                if not raw_payload:
                    continue
                
                current_mode = dapatkan_status_mode(conn, cursor)
                
                if raw_payload.startswith("SCAN:"):
                    uid_clean = raw_payload.split("SCAN:")[1].strip()
                    log_status = verifikasi_dan_catat_kartu(uid_clean, current_mode, conn, cursor)
                    tampilkan_dashboard_terminal(current_mode, log_status)
                    
                elif raw_payload == "INTRUDER_DETECTED":
                    log_status = tangani_maling(current_mode, conn, cursor)
                    tampilkan_dashboard_terminal(current_mode, log_status)
                    
            time.sleep(0.01)
            
    except serial.SerialException:
        print(f"\n{MERAH}Eror: Gagal koneksi ke Bluetooth {SERIAL_PORT}. Pastikan HC-05 sudah menyala.{RESET}")
    except mysql.connector.Error as err:
        print(f"\n{MERAH}Eror Database: {err}{RESET}")
    except KeyboardInterrupt:
        print(f"\n{KUNING}Engine dihentikan manual.{RESET}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()