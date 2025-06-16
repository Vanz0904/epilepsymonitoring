<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "epiguard";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check all required fields
    if (isset($_POST['patient_id'], $_POST['heart_rate'], $_POST['motion'], $_POST['device_id'], $_POST['fall'])) {
        $patient_id = intval($_POST['patient_id']);
        $heart_rate = intval($_POST['heart_rate']);
        $motion = $conn->real_escape_string($_POST['motion']);
        $device_id = $conn->real_escape_string($_POST['device_id']);
        $fall = $conn->real_escape_string($_POST['fall']);

        $stmt = $conn->prepare("INSERT INTO sensor_data (patient_id, heart_rate, motion, device_id, fall) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $patient_id, $heart_rate, $motion, $device_id, $fall);

        if ($stmt->execute()) {
            http_response_code(200);
            echo "Data inserted successfully";
        } else {
            http_response_code(500);
            echo "Database error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        http_response_code(400);
        echo "Missing required fields";
    }
} else {
    http_response_code(405);
    echo "Invalid request method";
}

$conn->close();
?>
