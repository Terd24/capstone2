<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("Accounts/add_account.php");
}

// Handle success message from add_student.php
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) {
    unset($_SESSION['success_msg']);
}


// Default account type
$accountType = $_GET['type'] ?? 'student';

// Fetch accounts based on type
switch ($accountType) {
    case 'registrar':
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, username FROM registrar_account ORDER BY last_name ASC");
        $columns = ['ID Number', 'Full Name', 'Username'];
        break;
    case 'cashier':
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, username FROM cashier_account ORDER BY last_name ASC");
        $columns = ['ID Number', 'Full Name', 'Username'];
        break;
    case 'guidance':
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, username FROM guidance_account ORDER BY last_name ASC");
        $columns = ['ID Number', 'Full Name', 'Username'];
        break;
    case 'parent':
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, child_name FROM parent_account ORDER BY last_name ASC");
        $columns = ['ID Number', 'Full Name', 'Child Name'];
        break;
    default:
        // student
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track, grade_level FROM student_account ORDER BY last_name ASC");
        $columns = ['ID Number', 'Full Name','Academic Track', 'Grade Level'];
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account List - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="window.location.href='registrardashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
                <h1 class="text-xl font-bold">Account Management</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Grade Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto mt-8 p-6">

    <!-- Top Controls -->
    <div class="flex flex-col md:flex-row gap-4 md:items-center justify-between mb-8 bg-white p-6 rounded-2xl shadow-md border border-[#0B2C62]/20">
        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Select Account:</label>
            <select id="accountType" class="border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]" onchange="changeType(this.value)">
                <option value="student" <?= $accountType==='student' ? 'selected' : '' ?>>Student</option>
                <option value="registrar" <?= $accountType==='registrar' ? 'selected' : '' ?>>Registrar</option>
                <option value="guidance" <?= $accountType==='guidance' ? 'selected' : '' ?>>Guidance</option>
                <option value="cashier" <?= $accountType==='cashier' ? 'selected' : '' ?>>Cashier</option>
                <option value="parent" <?= $accountType==='parent' ? 'selected' : '' ?>>Parent</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Show entries:</label>
            <input type="number" id="showEntries" min="1" value="10" class="w-20 border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
        </div>

        <div class="flex items-center gap-3">
            <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-64 border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
            <button onclick="openModal()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg shadow hover:bg-[#256f37] transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Account
            </button>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="overflow-x-auto bg-white shadow-lg rounded-2xl p-4 border border-[#0B2C62]/20">
        <table class="min-w-full text-sm border-collapse">
            <thead class="bg-[#0B2C62] text-white">
                <tr>
                    <?php foreach($columns as $col): ?>
                        <th class="px-4 py-3 border text-left font-semibold"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="accountTable" class="divide-y divide-gray-200">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $clickable = true;
                        $onclick = '';
                        switch($accountType) {
                            case 'student':
                                $onclick = 'onclick="viewStudent(\'' . htmlspecialchars($row['id_number']) . '\');"';
                                break;
                            case 'registrar':
                                $onclick = 'onclick="viewRegistrar(\'' . htmlspecialchars($row['id_number']) . '\');"';
                                break;
                            case 'cashier':
                                $onclick = 'onclick="viewCashier(\'' . htmlspecialchars($row['id_number']) . '\');"';
                                break;
                            case 'guidance':
                                $onclick = 'onclick="viewGuidance(\'' . htmlspecialchars($row['id_number']) . '\');"';
                                break;
                            case 'parent':
                                $onclick = 'onclick="viewParent(\'' . htmlspecialchars($row['id_number']) . '\');"';
                                break;
                            default:
                                $clickable = false;
                        }
                        ?>
                        <tr class="hover:bg-[#FBB917]/20 transition <?= $clickable ? 'cursor-pointer' : '' ?>" <?= $clickable ? $onclick : '' ?>>
                            <?php foreach ($row as $value): ?>
                                <td class="px-4 py-3"><?= htmlspecialchars($value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-4 py-6 text-center text-gray-500 italic">No accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Success Notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="fixed top-4 right-4 bg-green-400 text-white px-4 py-2 rounded shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Include Modal -->
<?php include("Accounts/add_account.php"); ?>

<script>
// Define view functions at global scope immediately
window.viewStudent = function(studentId) {
    console.log('Clicked student ID:', studentId);
    console.log('Navigating to:', `Accounts/view_student.php?id=${studentId}`);
    window.location.href = `Accounts/view_student.php?id=${studentId}`;
};

window.viewRegistrar = function(registrarId) {
    console.log('Clicked registrar ID:', registrarId);
    console.log('Navigating to:', `Accounts/view_registrar.php?id=${registrarId}`);
    window.location.href = `Accounts/view_registrar.php?id=${registrarId}`;
};

window.viewCashier = function(cashierId) {
    console.log('Clicked cashier ID:', cashierId);
    console.log('Navigating to:', `Accounts/view_cashier.php?id=${cashierId}`);
    window.location.href = `Accounts/view_cashier.php?id=${cashierId}`;
};

window.viewGuidance = function(guidanceId) {
    console.log('Clicked guidance ID:', guidanceId);
    console.log('Navigating to:', `Accounts/view_guidance.php?id=${guidanceId}`);
    window.location.href = `Accounts/view_guidance.php?id=${guidanceId}`;
};

window.viewParent = function(parentId) {
    console.log('Clicked parent ID:', parentId);
    console.log('Navigating to:', `Accounts/view_parent.php?id=${parentId}`);
    window.location.href = `Accounts/view_parent.php?id=${parentId}`;
};

// Show success notification with animation
const notificationElement = document.getElementById("notif");
if (notificationElement) {
    // Show notification with slide-in effect
    setTimeout(() => {
        notificationElement.style.transform = 'translateX(0)';
        notificationElement.style.opacity = '1';
    }, 100);
    
    // Hide after 4 seconds with fade out
    setTimeout(() => {
        notificationElement.style.opacity = '0';
        notificationElement.style.transform = 'translateX(100px)';
        setTimeout(() => notificationElement.remove(), 300);
    }, 4000);
}

function changeType(type){ window.location.href = `AccountList.php?type=${type}`; }

const searchInput = document.getElementById('searchInput');
const showEntriesInput = document.getElementById('showEntries');
let tableRows = Array.from(document.querySelectorAll('#accountTable tr'));

function updateEntries() {
    const value = parseInt(showEntriesInput.value) || tableRows.length;
    let shown = 0;
    tableRows.forEach(row => row.style.display = '');
    const query = searchInput.value.toLowerCase().trim();
    tableRows.forEach(row => { if (!row.textContent.toLowerCase().includes(query)) row.style.display='none'; });
    shown = 0;
    tableRows.forEach(row => {
        if(row.style.display !== 'none'){ if(shown<value) row.style.display=''; else row.style.display='none'; shown++; }
    });
}
showEntriesInput.addEventListener('input', updateEntries);
searchInput.addEventListener('input', updateEntries);
updateEntries();

// Remove duplicate function definition since it's now at the top
</script>
</body>
</html>