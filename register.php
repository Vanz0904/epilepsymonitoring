<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Check if input is valid
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit;
}

// Extract and sanitize input
$firstName = trim($data["firstName"] ?? '');
$lastName = trim($data["lastName"] ?? '');
$email = trim($data["email"] ?? '');
$password = trim($data["password"] ?? '');
$role = trim($data["role"] ?? 'patient'); // default to patient if not given

// Combine names
$fullname = $firstName . ' ' . $lastName;

// Validate required fields
if (!$firstName || !$lastName || !$email || !$password || !$role) {
    echo json_encode(["success" => false, "message" => "All required fields must be filled."]);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password_db = "";  
$database = "epiguard";  

$conn = new mysqli($servername, $username, $password_db, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Check for existing email
$stmt = $conn->prepare("SELECT userid FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists."]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $fullname, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Registration successful."]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed. Please try again."]);
}

$stmt->close();
$conn->close();
?>
