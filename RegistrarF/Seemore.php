<?php
session_start();
include("../StudentLogin/db_conn.php");

// Fetch all student requests for registrar
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Documents</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

<!-- RFID Form -->
<form id="rfidForm" method="get" action="viewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<div class="bg-white p-4 border-b shadow-sm">
  <p class="text-gray-400 text-sm">Student Documents</p>
</div>

<div class="bg-white px-6 py-3 flex items-center gap-2 border-b">
  <a href="registrarDashboard.php" class="text-xl">&#8592;</a>
  <h1 class="text-lg font-medium">Request</h1>
</div>

<div class="p-6">
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow-md">
      <thead class="bg-black text-white text-left text-sm">
        <tr>
          <th class="px-4 py-2">Student No</th>
          <th class="px-4 py-2">Document Name</th>
          <th class="px-4 py-2">Date Submitted</th>
          <th class="px-4 py-2">Claimed At</th>
          <th class="px-4 py-2">Status</th>
        </tr>
      </thead>
      <tbody class="text-sm text-gray-800">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($r = $result->fetch_assoc()): ?>
            <tr class="<?= ($r['status'] === 'Pending') ? '' : 'bg-gray-100' ?>">
              <td class="px-4 py-2"><?= htmlspecialchars($r['student_id']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($r['document_type']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($r['date_requested']) ?></td>
              <td class="px-4 py-2">
                <?= ($r['status'] === 'Claimed' && $r['date_claimed']) ? htmlspecialchars($r['date_claimed']) : '---' ?>
              </td>
              <td class="px-4 py-2">
                <?php if ($r['status'] === 'Pending'): ?>
                  <span class="text-yellow-600 font-medium">Pending</span>
                <?php elseif ($r['status'] === 'Ready to Claim' || $r['status'] === 'Ready for Claiming'): ?>
                  <span class="text-green-600 font-medium">Ready to Claim</span>
                <?php elseif ($r['status'] === 'Claimed'): ?>
                  <span class="text-blue-600 font-medium">Claimed</span>
                <?php else: ?>
                  <?= htmlspecialchars($r['status']) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No requests found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
let rfidBuffer = "";
let lastKeyTime = Date.now();
const rfidInput = document.getElementById("rfid_input");
const rfidForm = document.getElementById("rfidForm");

// Listen to all key presses
document.addEventListener('keydown', (e) => {
    const currentTime = Date.now();

    // Reset buffer if keys are slow
    if (currentTime - lastKeyTime > 100) {
        rfidBuffer = "";
    }
    lastKeyTime = currentTime;

    // Ignore if focused on actual input/textarea
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }

    // Enter submits RFID
    if (e.key === 'Enter') {
        if (rfidBuffer.length >= 5) {
            rfidInput.value = rfidBuffer.trim();
            rfidForm.submit();
        }
        rfidBuffer = "";
        e.preventDefault();
    } else {
        // Add character keys to buffer
        if (e.key.length === 1) {
            rfidBuffer += e.key;
        }
    }
});
</script>

</body>
</html>
