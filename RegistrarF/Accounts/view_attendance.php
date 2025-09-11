<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$attendance_id = $_GET['id'] ?? '';
$attendance_data = null;

if (!empty($attendance_id)) {
    $stmt = $conn->prepare("SELECT * FROM attendance_account WHERE id = ?");
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_data = $result->fetch_assoc();
    $stmt->close();
}

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_attendance'])) {
    $delete_stmt = $conn->prepare("DELETE FROM attendance_account WHERE id = ?");
    $delete_stmt->bind_param("i", $attendance_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_msg'] = "Attendance account deleted successfully!";
        header("Location: ../AccountList.php?type=attendance");
        exit;
    } else {
        $error_msg = "Error deleting attendance account.";
    }
    $delete_stmt->close();
}

// Handle edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_attendance'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Update attendance record
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE attendance_account SET username = ?, password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $username, $hashed_password, $attendance_id);
    } else {
        // Update without changing password
        $update_sql = "UPDATE attendance_account SET username = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $username, $attendance_id);
    }

    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = "Attendance account updated successfully!";
        header("Location: ../AccountList.php?type=attendance");
        exit;
    } else {
        $error_msg = "Error updating attendance account.";
    }
    $update_stmt->close();
}

if (!$attendance_data) {
    header("Location: ../AccountList.php?type=attendance");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Account - CCI</title>
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
    <button onclick="window.location.href='../AccountList.php?type=attendance'" class="text-2xl mr-4 hover:text-[#FBB917] transition">←</button>
    <h1 class="text-xl font-bold tracking-wide">Attendance Account</h1>
</div>

<!-- Attendance Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-100" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
            <h2 class="text-lg font-semibold">Attendance Account Information</h2>
            <div class="flex gap-3">
                <button id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <button id="deleteBtn" onclick="confirmDelete()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete</button>
                <button onclick="window.location.href='../AccountList.php?type=attendance'" class="text-2xl font-bold hover:text-gray-300">&times;</button>
            </div>
        </div>

        <!-- Form -->
        <form id="attendanceForm" method="POST" class="px-6 py-6 space-y-6">
            <input type="hidden" name="update_attendance" value="1">

            <!-- Account Information -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Account Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Username *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($attendance_data['username'] ?? '') ?>" 
                           readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 attendance-field" required>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Password</label>
                    <input type="text" name="password" placeholder="Enter new password" 
                           readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 attendance-field">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                </div>
            </div>


            <!-- Submit Buttons -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="window.location.href='../AccountList.php?type=attendance'" 
                        class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">
                    Back to List
                </button>
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
    const fields = document.querySelectorAll('.attendance-field');
    
    const isEditing = editBtn.textContent === 'Cancel';
    
    if (isEditing) {
        // Cancel editing
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        saveBtn.classList.add('hidden');
        
        // Make fields readonly
        fields.forEach(field => {
            field.readOnly = true;
            field.classList.add('bg-gray-50');
            field.classList.remove('bg-white');
        });
    } else {
        // Enable editing
        editBtn.textContent = 'Cancel';
        editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
        saveBtn.classList.remove('hidden');
        
        // Make fields editable
        fields.forEach(field => {
            field.readOnly = false;
            field.classList.remove('bg-gray-50');
            field.classList.add('bg-white');
        });
    }
}

// Delete confirmation function
function confirmDelete() {
    showDeleteModal();
}

function showDeleteModal() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Attendance Account</h3>
                <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this attendance account? This action cannot be undone.</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Cancel
                    </button>
                    <button onclick="proceedDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeDeleteModal() {
    const modal = document.querySelector('[class*="z-[60]"]');
    if (modal) {
        modal.remove();
    }
}

function proceedDelete() {
    // Create a form to submit the delete request
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="delete_attendance" value="1">';
    document.body.appendChild(form);
    form.submit();
}
</script>

</body>
</html>
