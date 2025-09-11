<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$parent_id = $_GET['id'] ?? '';
$parent_data = null;
$child_data = null;

if (!empty($parent_id)) {
    $stmt = $conn->prepare("SELECT * FROM parent_account WHERE id_number = ?");
    $stmt->bind_param("s", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $parent_data = $result->fetch_assoc();
    $stmt->close();
    
    // Get child information
    if ($parent_data && !empty($parent_data['child_id'])) {
        $child_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM student_account WHERE id_number = ?");
        $child_stmt->bind_param("s", $parent_data['child_id']);
        $child_stmt->execute();
        $child_result = $child_stmt->get_result();
        $child_data = $child_result->fetch_assoc();
        $child_stmt->close();
    }
}

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_parent'])) {
    $delete_stmt = $conn->prepare("DELETE FROM parent_account WHERE id_number = ?");
    $delete_stmt->bind_param("s", $parent_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_msg'] = "Parent account deleted successfully!";
        header("Location: ../AccountList.php?type=parent");
        exit;
    } else {
        $error_msg = "Error deleting parent account.";
    }
    $delete_stmt->close();
}

// Handle edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_parent'])) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $child_id = $_POST['child_id'] ?? '';
    $child_name = $_POST['child_name'] ?? '';
    $id_number = $_POST['id_number'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Update parent record
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE parent_account SET 
            first_name = ?, last_name = ?, middle_name = ?, 
            child_id = ?, child_name = ?, id_number = ?, username = ?, password = ?
            WHERE id_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "sssssssss",
            $first_name, $last_name, $middle_name,
            $child_id, $child_name, $id_number, $username, $hashed_password, $parent_id
        );
    } else {
        // Update without changing password
        $update_sql = "UPDATE parent_account SET 
            first_name = ?, last_name = ?, middle_name = ?, 
            child_id = ?, child_name = ?, id_number = ?, username = ?
            WHERE id_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "ssssssss",
            $first_name, $last_name, $middle_name,
            $child_id, $child_name, $id_number, $username, $parent_id
        );
    }

    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = "Parent information updated successfully!";
        header("Location: ../AccountList.php?type=parent");
        exit;
    } else {
        $error_msg = "Error updating parent information.";
    }
    $update_stmt->close();
}

if (!$parent_data) {
    header("Location: ../AccountList.php?type=parent");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parent Information - CCI</title>
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
    <button onclick="window.location.href='../AccountList.php?type=parent'" class="text-2xl mr-4 hover:text-[#FBB917] transition">←</button>
    <h1 class="text-xl font-bold tracking-wide">Parent Information</h1>
</div>

<!-- Parent Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-100" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
            <h2 class="text-lg font-semibold">Parent Information</h2>
            <div class="flex gap-3">
                <button id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <button id="deleteBtn" onclick="confirmDelete()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete</button>
                <button onclick="window.location.href='../AccountList.php?type=parent'" class="text-2xl font-bold hover:text-gray-300">&times;</button>
            </div>
        </div>

        <!-- Form -->
        <form id="parentForm" method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            <input type="hidden" name="update_parent" value="1">

            <!-- Personal Information Section -->
            <div class="col-span-3">
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Personal Information</h3>
            </div>

            <!-- Full Name -->
            <div>
                <label class="block text-sm font-semibold mb-1">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($parent_data['first_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($parent_data['last_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($parent_data['middle_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
            </div>

            <!-- Child Information Section -->
            <div class="col-span-3">
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Child Information</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Child's Student ID</label>
                <input type="text" name="child_id" value="<?= htmlspecialchars($parent_data['child_id'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field" onblur="fetchChildNameEdit(this.value)">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-1">Child's Full Name</label>
                <input type="text" id="child_name_display" value="<?= htmlspecialchars($child_data['full_name'] ?? $parent_data['child_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-100 text-gray-600">
                <input type="hidden" name="child_name" id="child_name_hidden" value="<?= htmlspecialchars($parent_data['child_name'] ?? '') ?>">
            </div>

            <!-- Account Information Section -->
            <div class="col-span-3">
                <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Account Information</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($parent_data['username'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ID Number</label>
                <input type="text" name="id_number" value="<?= htmlspecialchars($parent_data['id_number'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Password</label>
                <input type="text" name="password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 parent-field">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="window.location.href='../AccountList.php?type=parent'" class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">Back to List</button>
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
    const fields = document.querySelectorAll('.parent-field');
    
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

// Function to fetch child name during editing
function fetchChildNameEdit(childId) {
    if (!childId) {
        document.getElementById('child_name_display').value = '';
        document.getElementById('child_name_hidden').value = '';
        return;
    }
    
    fetch('get_child_name.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'child_id=' + encodeURIComponent(childId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('child_name_display').value = data.child_name;
            document.getElementById('child_name_hidden').value = data.child_name;
        } else {
            document.getElementById('child_name_display').value = 'Student not found';
            document.getElementById('child_name_hidden').value = '';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('child_name_display').value = 'Error fetching student';
        document.getElementById('child_name_hidden').value = '';
    });
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
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Parent Account</h3>
                <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this parent account? This action cannot be undone.</p>
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
    form.innerHTML = '<input type="hidden" name="delete_parent" value="1">';
    document.body.appendChild(form);
    form.submit();
}
</script>

</body>
</html>
