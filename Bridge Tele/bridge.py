import serial
import requests
import time

# --- KONFIGURASI ---
TELEGRAM_TOKEN = "8554238488:AAHSG68GHaRPmbcwm9ZHjJsxTH2sREpQeRA"
CHAT_ID = "-5266286852"
NATIVE_API_URL = "http://localhost/iot-security/api.php"
COM_PORT = "COM4" 
BAUD_RATE = 9600

print("=== STARTING doorSecure PRO BRIDGE ===")

try:
    ser = serial.Serial(COM_PORT, BAUD_RATE, timeout=1)
    print(f"[SUCCESS] Terhubung ke Hardware di port {COM_PORT}")
    time.sleep(2)
except Exception as e:
    print(f"[ERROR] Gagal koneksi: {e}")
    exit()

def send_telegram(message):
    url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
    payload = {"chat_id": CHAT_ID, "text": message, "parse_mode": "Markdown"}
    try:
        requests.post(url, json=payload, timeout=5)
    except Exception as e:
        print(f"[TELEGRAM] Error: {e}")

# Variabel untuk melacak status terakhir (mencegah banjir serial data)
last_siaga_status = None
last_reg_status = None

def run_bridge():
    global last_siaga_status, last_reg_status
    
    while True:
        # 1. CEK DATA DARI HARDWARE (ARDUINO)
        if ser.in_waiting > 0:
            try:
                line = ser.readline().decode('utf-8').strip()
                if not line: continue
                
                print(f"[HARDWARE] Data: {line}")
                
                # --- LOGIKA SCAN KARTU ---
                if line.startswith("SCAN:"):
                    uid = line.split(":")[1].strip()
                    print(f"[PROCESS] Checking UID: {uid}")
                    
                    try:
                        response = requests.post(f"{NATIVE_API_URL}?action=check-card", json={"uid": uid}, timeout=5)
                        data = response.json()
                        status = data.get("status")
                        owner = data.get("owner", "Tidak Dikenal")
                        
                        if status == "VALID":
                            print(f"[API] Akses Diterima: {owner}")
                            ser.write(b"VALID\n")
                            send_telegram(f"✅ *AKSES DIIZINKAN*\nSelamat Datang, *{owner}*!\nPintu telah dibuka.")
                            
                        elif status == "INVALID":
                            print("[API] Kartu Tidak Terdaftar!")
                            ser.write(b"INVALID\n")
                            send_telegram(f"🚫 *AKSES DITOLAK*\nAda mencoba masuk dengan kartu asing!\n*UID:* `{uid}`")
                            
                        elif status == "REGISTERED":
                            print(f"[API] Kartu Baru Terdaftar: {uid}")
                            ser.write(b"VALID\n")
                            send_telegram(f"🆕 *KARTU BARU*\nUID `{uid}` berhasil didaftarkan ke sistem.")
                            
                    except Exception as e:
                        print(f"[API ERROR] {e}")

                # --- LOGIKA SENSOR MALING ---
                elif line == "ALERT:MALING":
                    print("[⚠️ ALERT] PENYUSUP TERDETEKSI!")
                    send_telegram("🚨 *BAHAYA! PENYUSUP!*\nSensor mendeteksi pergerakan melewati pintu tanpa kartu sah!")
                    try:
                        requests.post(f"{NATIVE_API_URL}?action=log-maling", timeout=5)
                    except: pass
                        
            except Exception as e:
                print(f"[SERIAL ERROR] {e}")

        # 2. SINKRONISASI STATUS DARI DASHBOARD KE HARDWARE
        try:
            res = requests.get(f"{NATIVE_API_URL}?action=status", timeout=3)
            if res.status_code == 200:
                data = res.json()
                current_siaga = data.get("mode_siaga", True)
                current_reg = data.get("registration_mode", False)

                # Kirim status SIAGA hanya jika berubah
                if current_siaga != last_siaga_status:
                    if current_siaga:
                        ser.write(b"SIAGA_ON\n")
                        print("[SYSTEM] Mode Siaga: AKTIF")
                    else:
                        ser.write(b"SIAGA_OFF\n")
                        print("[SYSTEM] Mode Siaga: NON-AKTIF")
                    last_siaga_status = current_siaga

                # Kirim status REGISTRASI hanya jika berubah
                if current_reg != last_reg_status:
                    if current_reg:
                        ser.write(b"REG_ON\n")
                        print("[SYSTEM] Mode Registrasi: SCAN KARTU BARU...")
                    else:
                        ser.write(b"REG_OFF\n")
                    last_reg_status = current_reg

        except Exception as e:
            pass

        time.sleep(0.5)

if __name__ == "__main__":
    try:
        run_bridge()
    except KeyboardInterrupt:
        print("\n[STOP] Bridge dihentikan.")
        ser.close()