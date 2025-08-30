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
    include("Accounts/add_student.php");
}

// Handle success message from add_student.php
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) {
    unset($_SESSION['success_msg']);
}


// Default account type
$accountType = $_GET['type'] ?? 'student';

// Debug: Check what account type we're viewing
// echo "Current account type: " . $accountType;

// Fetch accounts based on type
switch ($accountType) {
    case 'registrar':
        $result = $conn->query("SELECT registrar_id, registrar_name, username FROM registrar_account ORDER BY registrar_name ASC");
        $columns = ['Registrar ID', 'Registrar Name', 'Username'];
        break;
    case 'cashier':
        $result = $conn->query("SELECT full_name, username FROM cashier_account ORDER BY full_name ASC");
        $columns = ['Full Name', 'Username'];
        break;
    case 'guidance':
        $result = $conn->query("SELECT username FROM guidance_account ORDER BY username ASC");
        $columns = ['Username'];
        break;
    case 'parent':
        $result = $conn->query("SELECT parent_id, parent_name, child_id FROM parent_account ORDER BY parent_name ASC");
        $columns = ['Parent ID', 'Parent Name', 'Child ID'];
        break;
    default:
        // student
        $result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track, grade_level FROM students ORDER BY last_name ASC");
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

<!-- Header -->
<div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center shadow-lg">
    <button onclick="window.location.href='registrardashboard.php'" class="text-2xl mr-4 hover:text-[#FBB917] transition">←</button>
    <h1 class="text-xl font-bold tracking-wide">Account List</h1>
</div>

<div class="max-w-7xl mx-auto mt-8 p-6">

    <!-- Top Controls -->
    <div class="flex flex-col md:flex-row gap-4 md:items-center justify-between mb-8 bg-white p-6 rounded-2xl shadow-md border border-[#0B2C62]/20">
        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Select Account:</label>
            <select id="accountType" class="border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#C41E3A]" onchange="changeType(this.value)">
                <option value="student" <?= $accountType==='student' ? 'selected' : '' ?>>Student</option>
                <option value="registrar" <?= $accountType==='registrar' ? 'selected' : '' ?>>Registrar</option>
                <option value="guidance" <?= $accountType==='guidance' ? 'selected' : '' ?>>Guidance</option>
                <option value="cashier" <?= $accountType==='cashier' ? 'selected' : '' ?>>Cashier</option>
                <option value="parent" <?= $accountType==='parent' ? 'selected' : '' ?>>Parent</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Show entries:</label>
            <input type="number" id="showEntries" min="1" value="10" class="w-20 border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#C41E3A]"/>
        </div>

        <div class="flex items-center">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0B2C62]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                    </svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-64 border border-[#0B2C62]/40 rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-[#C41E3A]"/>
            </div>
        </div>

        <?php if($accountType==='student'): ?>
        <button onclick="openModal()" class="bg-[#2F8D46] text-white px-5 py-2 rounded-xl shadow hover:bg-[##2F8D46] transition">
            + Add Student
        </button>
        <?php endif; ?>
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
                        <tr class="hover:bg-[#FBB917]/20 transition <?= $accountType === 'student' ? 'cursor-pointer' : '' ?>" <?= $accountType === 'student' ? 'onclick="viewStudent(\'' . htmlspecialchars($row['id_number']) . '\');"' : '' ?>>
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
<?php include("Accounts/add_student.php"); ?>

<script>
// Define viewStudent function at global scope immediately
window.viewStudent = function(studentId) {
    console.log('Clicked student ID:', studentId);
    console.log('Navigating to:', `Accounts/view_student.php?id=${studentId}`);
    window.location.href = `Accounts/view_student.php?id=${studentId}`;
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
