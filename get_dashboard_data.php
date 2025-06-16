<?php
header('Content-Type: application/json');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "epiguard";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

/**
 * Helper function to execute a COUNT(*) SQL query
 * Returns 0 if query fails or no result
 */
function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return isset($row['total']) ? (int)$row['total'] : 0;
    }
    return 0;
}

// 1. Total patients
$patients = getCount($conn, "SELECT COUNT(*) AS total FROM patients");

// 2. Total caregivers
$caregivers = getCount($conn, "SELECT COUNT(*) AS total FROM caregivers");

// 3. Active devices in last 5 minutes
$devices = getCount($conn, "SELECT COUNT(DISTINCT device_id) AS total FROM sensor_data WHERE created_at >= NOW() - INTERVAL 5 MINUTE");

// 4. Alerts (motion detected) in last 24 hours
$alerts = getCount($conn, "SELECT COUNT(*) AS total FROM sensor_data WHERE motion = 1 AND created_at >= NOW() - INTERVAL 1 DAY");

// 5. Seizure events (heart_rate > 800 OR fall = 1)
$events = getCount($conn, "SELECT COUNT(*) AS total FROM sensor_data WHERE heart_rate > 800 OR fall = 1");

// 6. Get recent 5 alerts (latest sensor_data entries)
$alertsList = [];
$recentAlertsQuery = "SELECT * FROM sensor_data ORDER BY created_at DESC LIMIT 5";
$recentAlerts = $conn->query($recentAlertsQuery);

if ($recentAlerts) {
    while ($row = $recentAlerts->fetch_assoc()) {
        $alertsList[] = $row;
    }
}

// 7. Seizure events overview for last month (grouped by date)
$seizureOverview = [];
$overviewQuery = "
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM sensor_data 
    WHERE (heart_rate > 800 OR fall = 1) 
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
";
$overviewResult = $conn->query($overviewQuery);
if ($overviewResult) {
    while ($row = $overviewResult->fetch_assoc()) {
        $seizureOverview[] = $row;
    }
}

// 8. Device status summary (online/offline)
// Assuming you have a device_status table or you can infer from last data received timestamp
// Here, devices sending data in last 5 minutes = online, else offline
$deviceStatus = [];
$deviceStatusQuery = "
    SELECT device_id,
           CASE 
               WHEN MAX(created_at) >= NOW() - INTERVAL 5 MINUTE THEN 'online' 
               ELSE 'offline' 
           END as status
    FROM sensor_data
    GROUP BY device_id
";
$deviceStatusResult = $conn->query($deviceStatusQuery);
if ($deviceStatusResult) {
    while ($row = $deviceStatusResult->fetch_assoc()) {
        $deviceStatus[] = $row;
    }
}

// Final output
$response = [
    "patients"       => $patients,
    "caregivers"     => $caregivers,
    "devices"        => $devices,
    "alerts"         => $alerts,
    "events"         => $events,  // This corresponds to monthly-events in JS
    "recent_alerts"  => $alertsList,
    "seizure_overview" => $seizureOverview,
    "device_status"  => $deviceStatus
];

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
