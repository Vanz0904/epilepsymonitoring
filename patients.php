<?php
$mysqli = new mysqli("localhost", "root", "", "epiguard");

// Fetch all patients
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $result = $mysqli->query("SELECT * FROM patients");
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    echo json_encode($patients);
    exit;
}

// Delete a patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $patient_id = $_POST['patient_id'];
    $stmt = $mysqli->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Add or update a patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $fullname = $_POST['fullname'];
    $birthdate = $_POST['birthdate'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $emergency_contact = $_POST['emergency_contact'];
    $caregiver_id = $_POST['caregiver_id'];
    $user_id = $_POST['user_id'];

    if (isset($_POST['patient_id']) && $_POST['patient_id'] !== '') {
        // Update
        $patient_id = $_POST['patient_id'];
        $stmt = $mysqli->prepare("UPDATE patients SET fullname=?, birthdate=?, address=?, contact_number=?, emergency_contact=?, caregiver_id=?, user_id=? WHERE patient_id=?");
        $stmt->bind_param("ssssssii", $fullname, $birthdate, $address, $contact_number, $emergency_contact, $caregiver_id, $user_id, $patient_id);
    } else {
        // Insert
        $stmt = $mysqli->prepare("INSERT INTO patients (fullname, birthdate, address, contact_number, emergency_contact, caregiver_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $fullname, $birthdate, $address, $contact_number, $emergency_contact, $caregiver_id, $user_id);
    }

    $stmt->execute();
    header("Location: patients.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patients</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="app-container">
    <main class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1>Patients</h1>
                <button class="action-btn" onclick="openModal()">+ Add Patient</button>
            </div>
        </div>

        <div class="table-container large">
            <div class="table-header">
                <h3>Patient List</h3>
            </div>
            <table class="data-table" id="patients-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Birthdate</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Emergency</th>
                        <th>Caregiver ID</th>
                        <th>User ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="patientModal" class="modal" style="display:none;">
    <form class="modal-content" method="POST">
        <input type="hidden" name="patient_id" id="patient_id">
        <h2>Patient Details</h2>
        <input name="fullname" placeholder="Full Name" required>
        <input name="birthdate" type="date" required>
        <input name="address" placeholder="Address" required>
        <input name="contact_number" placeholder="Contact Number" required>
        <input name="emergency_contact" placeholder="Emergency Contact" required>
        <input name="caregiver_id" placeholder="Caregiver ID" required>
        <input name="user_id" placeholder="User ID" required>
        <button type="submit" class="action-btn">Save</button>
        <button type="button" class="action-btn" onclick="closeModal()">Cancel</button>
    </form>
</div>

<script>
function openModal(data = null) {
    document.getElementById("patientModal").style.display = "block";
    const form = document.querySelector("form");
    form.reset();
    if (data) {
        document.getElementById("patient_id").value = data.patient_id;
        form.fullname.value = data.fullname;
        form.birthdate.value = data.birthdate;
        form.address.value = data.address;
        form.contact_number.value = data.contact_number;
        form.emergency_contact.value = data.emergency_contact;
        form.caregiver_id.value = data.caregiver_id;
        form.user_id.value = data.user_id;
    }
}

function closeModal() {
    document.getElementById("patientModal").style.display = "none";
}

function deletePatient(id) {
    if (!confirm("Are you sure you want to delete this patient?")) return;
    fetch("patients.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=delete&patient_id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            loadPatients();
        }
    });
}

function loadPatients() {
    fetch("patients.php?action=get")
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector("#patients-table tbody");
            tbody.innerHTML = "";
            data.forEach(p => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${p.fullname}</td>
                    <td>${p.birthdate}</td>
                    <td>${p.address}</td>
                    <td>${p.contact_number}</td>
                    <td>${p.emergency_contact}</td>
                    <td>${p.caregiver_id}</td>
                    <td>${p.user_id}</td>
                    <td>
                        <button class="action-btn" onclick='openModal(${JSON.stringify(p)})'>Edit</button>
                        <button class="action-btn" onclick='deletePatient(${p.patient_id})'>Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

loadPatients();
</script>
</body>
</html>
