<?php
session_start();
include("../StudentLogin/db_conn.php");

// 🔒 Prevent access if not logged in
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.html");
    exit;
}

// 🚫 Prevent browser caching (stops back after logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch all requests
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registrar Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .card { border: 1px solid #e5e7eb; border-radius: 0.5rem; }
</style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

<!-- RFID Form -->
<form id="rfidForm" method="get" action="viewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header -->
<div class="bg-white p-4 flex items-center justify-between shadow-sm relative">
    <h1 class="text-lg font-semibold text-gray-700 flex items-center gap-4">
        Registrar Dashboard
    </h1>

    <!-- Burger / Dropdown -->
    <div class="relative">
        <button id="menuBtn" class="p-2 rounded hover:bg-gray-100 focus:outline-none">
            ☰
        </button>

        <!-- Dropdown Menu -->
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-md">
            <a href="logout.php" class="block px-4 py-2 text-black-500 hover:bg-gray-100">Logout</a>
        </div>
    </div>
</div>

<!-- Content -->
<div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Registrar Info -->
    <div class="bg-white card p-6 shadow">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-2xl">👤</div>
            <div class="ml-3">
                <div class="font-medium text-gray-700"><?= htmlspecialchars($_SESSION['registrar_name'] ?? 'Employee Name') ?></div>
                <p class="text-xs text-gray-500">ID: <?= htmlspecialchars($_SESSION['registrar_id']) ?></p>
            </div>
        </div>
        <p class="text-xs text-gray-500 italic">Tap student ID to view info</p>
    </div>

    <!-- Requests -->
    <div class="bg-white card p-6 shadow md:col-span-2">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Recent Student Requests</h2>
            <a href="Seemore.php" class="text-sm text-blue-500 hover:underline">See More</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="bg-gray-50 p-4 rounded border mb-3">
                    <p class="font-medium text-gray-700"><?= htmlspecialchars($row['document_type']) ?></p>
                    <p class="text-xs text-gray-500">Requested by: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)</p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-xs"><?= htmlspecialchars($row['purpose'] ?: 'Document request.') ?></span>
                        <div class="flex items-center gap-3">
                            <a href="viewStudentInfo.php?student_id=<?= urlencode($row['student_id']) ?>" class="text-sm text-blue-600 hover:underline">More</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="bg-gray-50 p-4 rounded text-gray-500">No requests yet.</div>
        <?php endif; ?>
    </div>
</div>

<script>
// RFID Scanner Logic
let rfidBuffer = "";
let lastKeyTime = Date.now();
const rfidInput = document.getElementById("rfid_input");
const rfidForm = document.getElementById("rfidForm");

document.addEventListener('keydown', (e) => {
    const currentTime = Date.now();
    if (currentTime - lastKeyTime > 100) {
        rfidBuffer = "";
    }
    lastKeyTime = currentTime;

    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }

    if (e.key === 'Enter') {
        if (rfidBuffer.length >= 5) {
            rfidInput.value = rfidBuffer.trim();
            rfidForm.submit();
        }
        rfidBuffer = "";
        e.preventDefault();
    } else {
        if (e.key.length === 1) {
            rfidBuffer += e.key;
        }
    }
});

// Dropdown toggle
const menuBtn = document.getElementById("menuBtn");
const dropdownMenu = document.getElementById("dropdownMenu");

menuBtn.addEventListener("click", () => {
    dropdownMenu.classList.toggle("hidden");
});

// Hide menu when clicking outside
document.addEventListener("click", (e) => {
    if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.add("hidden");
    }
});
</script>

</body>
</html>
