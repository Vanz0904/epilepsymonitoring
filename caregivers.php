<?php
$mysqli = new mysqli("localhost", "root", "", "epiguard");

// Fetch users for dropdown
$users_result = $mysqli->query("SELECT userid, fullname FROM users");
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}

// Handle fetch caregivers via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $result = $mysqli->query("SELECT * FROM caregivers");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// Handle insert/update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $relationship_to_patient = $_POST['relationship_to_patient'];

    if (!empty($_POST['caregiver_id'])) {
        $caregiver_id = $_POST['caregiver_id'];
        $stmt = $mysqli->prepare("UPDATE caregivers SET user_id=?, phone_number=?, address=?, relationship_to_patient=? WHERE caregiver_id=?");
        $stmt->bind_param("isssi", $user_id, $phone_number, $address, $relationship_to_patient, $caregiver_id);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO caregivers (user_id, phone_number, address, relationship_to_patient) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $phone_number, $address, $relationship_to_patient);
    }

    $stmt->execute();
    header("Location: caregivers.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Caregivers Management</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body { font-family: Arial; margin: 40px; background-color: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; background-color: #fff; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        th { background-color:rgb(3, 31, 61); color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .action-btn { padding: 8px 12px; margin: 4px; background-color: #007BFF; color: white; border: none; cursor: pointer; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 20px; width: 400px; border-radius: 8px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Caregivers</h1>
    <button class="action-btn" onclick="openModal()">Add Caregiver</button>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Phone Number</th>
                <th>Address</th>
                <th>Relationship</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="caregiverTable">
            <!-- Dynamic content -->
        </tbody>
    </table>

    <!-- Modal -->
    <div id="caregiverModal" class="modal">
        <form class="modal-content" method="POST">
            <input type="hidden" name="caregiver_id" id="caregiver_id">
            <h2>Caregiver Details</h2>

            <label>User</label>
            <select name="user_id" id="user_id" required>
                <option value="">-- Select User --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['userid'] ?>"><?= htmlspecialchars($user['fullname']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" required>

            <label>Address</label>
            <input type="text" name="address" id="address" required>

            <label>Relationship to Patient</label>
            <input type="text" name="relationship_to_patient" id="relationship_to_patient" required>

            <button type="submit" class="action-btn">Save</button>
            <button type="button" class="action-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>

    <script>
        function fetchCaregivers() {
            fetch("caregivers.php?action=get")
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById("caregiverTable");
                    tbody.innerHTML = "";
                    data.forEach(c => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td>${c.caregiver_id}</td>
                            <td>${c.user_id}</td>
                            <td>${c.phone_number}</td>
                            <td>${c.address}</td>
                            <td>${c.relationship_to_patient}</td>
                            <td><button class="action-btn" onclick='openModal(${JSON.stringify(c)})'>Edit</button></td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        }

        function openModal(data = null) {
            const modal = document.getElementById("caregiverModal");
            modal.style.display = "block";
            const form = modal.querySelector("form");

            if (data) {
                form.caregiver_id.value = data.caregiver_id;
                form.user_id.value = data.user_id;
                form.phone_number.value = data.phone_number;
                form.address.value = data.address;
                form.relationship_to_patient.value = data.relationship_to_patient;
            } else {
                form.reset();
                form.caregiver_id.value = "";
            }
        }

        function closeModal() {
            document.getElementById("caregiverModal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("caregiverModal");
            if (event.target === modal) {
                closeModal();
            }
        }

        // Fetch caregivers on load
        fetchCaregivers();
    </script>
</body>
</html>
