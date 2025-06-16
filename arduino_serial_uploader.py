import serial
import requests
import time

# Patient/device info
patient_id = 1
device_id = "Device123"

# Connect to ESP32 via serial (check the port in your Device Manager)
esp32 = serial.Serial('COM4', 9600)  # Change COM4 to your actual port if needed
time.sleep(5)  # Allow ESP32 to boot

print("Listening for data from ESP32...\n")

while True:
    try:
        # Initialize fields for one full data packet
        heart_rate = None
        seizure = None
        fall = None
        motion = None

        # Read lines until complete data packet is collected or timeout
        start_time = time.time()
        while True:
            if time.time() - start_time > 5:  # Timeout after 5 seconds
                break

            line = esp32.readline().decode(errors='ignore').strip()
            if not line:
                continue

            if line.startswith("Heart Rate (raw):"):
                heart_rate = line.split(":")[1].strip()
            elif line.startswith("Seizure Detected:"):
                seizure = line.split(":")[1].strip()
            elif line.startswith("Fall Detected:"):
                fall = line.split(":")[1].strip()
            elif line.startswith("Motion Detected:"):
                motion = line.split(":")[1].strip()
            elif line.startswith("-------------------------") or line.startswith("------ Sensor Data ------"):
                # End of packet
                break

        # Only send if all expected fields are available
        if None not in (heart_rate, seizure, fall, motion):
            data_to_send = {
                "patient_id": patient_id,
                "device_id": device_id,
                "heart_rate": heart_rate,
                "seizure": seizure,
                "fall": 1 if fall.upper() == "YES" else 0,
                "motion": 1 if motion.upper() == "YES" else 0
            }

            try:
                response = requests.post(
                    "http://localhost/epilepsy-monitoring-system/insert_data.php",
                    data=data_to_send
                )
                print(f"✅ Sent data: {data_to_send}, Status: {response.status_code}")
                if response.status_code != 200:
                    print("⚠️ Server Response:", response.text)
            except requests.exceptions.RequestException as net_err:
                print("🌐 Network error:", net_err)

        else:
            print("❌ Incomplete data packet, skipping...")

    except Exception as e:
        print("⚠️ General Error:", e)
