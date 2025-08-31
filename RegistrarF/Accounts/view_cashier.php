<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$cashier_id = $_GET['id'] ?? '';
$cashier_data = null;

if (!empty($cashier_id)) {
    $stmt = $conn->prepare("SELECT * FROM cashier_account WHERE id = ?");
    $stmt->bind_param("i", $cashier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cashier_data = $result->fetch_assoc();
    $stmt->close();
}

// Handle edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cashier'])) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $birthplace = $_POST['birthplace'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $id_number = $_POST['id_number'] ?? '';
    $username = $_POST['username'] ?? '';

    // Update cashier record
    $update_sql = "UPDATE cashier_account SET 
        first_name = ?, last_name = ?, middle_name = ?, 
        dob = ?, birthplace = ?, gender = ?, address = ?,
        id_number = ?, username = ?
        WHERE id = ?";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param(
        "sssssssssi",
        $first_name, $last_name, $middle_name,
        $dob, $birthplace, $gender, $address,
        $id_number, $username, $cashier_id
    );

    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = "Cashier information updated successfully!";
        header("Location: ../AccountList.php?type=cashier");
        exit;
    } else {
        $error_msg = "Error updating cashier information.";
    }
    $update_stmt->close();
}

if (!$cashier_data) {
    header("Location: ../AccountList.php?type=cashier");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cashier Information - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<!-- Header -->
<div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center shadow-lg">
    <button onclick="window.location.href='../AccountList.php?type=cashier'" class="text-2xl mr-4 hover:text-[#FBB917] transition">←</button>
    <h1 class="text-xl font-bold tracking-wide">Cashier Information</h1>
</div>

<!-- Cashier Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-100" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
            <h2 class="text-lg font-semibold">Cashier Information</h2>
            <div class="flex gap-3">
                <button id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <button onclick="window.location.href='../AccountList.php?type=cashier'" class="text-2xl font-bold hover:text-gray-300">&times;</button>
            </div>
        </div>

        <!-- Form -->
        <form id="cashierForm" method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            <input type="hidden" name="update_cashier" value="1">

            <!-- Personal Information Section -->
            <div class="col-span-3">
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Personal Information</h3>
            </div>

            <!-- Full Name -->
            <div>
                <label class="block text-sm font-semibold mb-1">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($cashier_data['first_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($cashier_data['last_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($cashier_data['middle_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>

            <!-- Other Personal Info -->
            <div>
                <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($cashier_data['dob'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Birthplace</label>
                <input type="text" name="birthplace" value="<?= htmlspecialchars($cashier_data['birthplace'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Gender</label>
                <div class="flex items-center gap-6 mt-1">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="gender" value="M" <?= ($cashier_data['gender'] ?? '') === 'M' ? 'checked' : '' ?> disabled class="cashier-field"> Male
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="gender" value="F" <?= ($cashier_data['gender'] ?? '') === 'F' ? 'checked' : '' ?> disabled class="cashier-field"> Female
                    </label>
                </div>
            </div>

            <!-- Address -->
            <div class="col-span-3">
                <label class="block text-sm font-semibold mb-1">Complete Address</label>
                <textarea name="address" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field"><?= htmlspecialchars($cashier_data['address'] ?? '') ?></textarea>
            </div>

            <!-- Account Information Section -->
            <div class="col-span-3">
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Account Information</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($cashier_data['username'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ID Number</label>
                <input type="text" name="id_number" value="<?= htmlspecialchars($cashier_data['id_number'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cashier-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Password</label>
                <input type="password" value="••••••••" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50">
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="window.location.href='../AccountList.php?type=cashier'" class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">Back to List</button>
                <button type="submit" id="saveBtn" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition hidden">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEdit() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const fields = document.querySelectorAll('.cashier-field');
    
    const isEditing = editBtn.textContent === 'Cancel';
    
    if (isEditing) {
        // Cancel editing
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        saveBtn.classList.add('hidden');
        
        // Make fields readonly
        fields.forEach(field => {
            if (field.type === 'text' || field.type === 'number' || field.type === 'date' || field.tagName === 'TEXTAREA') {
                field.readOnly = true;
                field.classList.add('bg-gray-50');
                field.classList.remove('bg-white');
            } else if (field.type === 'radio' || field.type === 'checkbox' || field.tagName === 'SELECT') {
                field.disabled = true;
            }
        });
    } else {
        // Enable editing
        editBtn.textContent = 'Cancel';
        editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
        saveBtn.classList.remove('hidden');
        
        // Make fields editable
        fields.forEach(field => {
            if (field.type === 'text' || field.type === 'number' || field.type === 'date' || field.tagName === 'TEXTAREA') {
                field.readOnly = false;
                field.classList.remove('bg-gray-50');
                field.classList.add('bg-white');
            } else if (field.type === 'radio' || field.type === 'checkbox' || field.tagName === 'SELECT') {
                field.disabled = false;
            }
        });
    }
}
</script>

</body>
</html>
