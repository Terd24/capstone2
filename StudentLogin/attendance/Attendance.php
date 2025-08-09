<?php
session_start();
include '../db_conn.php';

if (!isset($_SESSION['id_number'])) {
    header("Location: index.php");
    exit();
}

$id_number = $_SESSION['id_number'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfid'])) {
    $rfid = strtoupper(trim($_POST['rfid']));

    // Verify RFID belongs to logged-in student
    $verify = $conn->prepare("
        SELECT id_number, TRIM(UPPER(rfid_uid)) AS db_rfid 
        FROM student_account 
        WHERE id_number = ?
    ");
    $verify->bind_param("s", $id_number);
    $verify->execute();
    $verifyResult = $verify->get_result();

    if ($verifyResult->num_rows === 0) {
        $_SESSION['error'] = "Student not found.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $row = $verifyResult->fetch_assoc();

    // Clean and compare RFID values to avoid whitespace/case issues
    $storedRFID = trim($row['db_rfid']);
    $inputRFID = trim($rfid);

    if ($storedRFID !== $inputRFID) {
        $_SESSION['error'] = "Card not registered to this account.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // RFID valid → record attendance
    date_default_timezone_set('Asia/Manila');
    $today = date("Y-m-d");
    $time = date("H:i:s");
    $day = date("l");
    $status = "Present";

    // Check if record exists for today
    $check = $conn->prepare("SELECT * FROM attendance_record WHERE id_number = ? AND date = ?");
    $check->bind_param("ss", $id_number, $today);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows === 0) {
        // No record today → Insert Time In
        $insert = $conn->prepare("INSERT INTO attendance_record (id_number, date, day, time_in, status) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("sssss", $id_number, $today, $day, $time, $status);
        $insert->execute();
        $_SESSION['error'] = "Time In recorded.";
    } else {
        // Record exists → Update time_out to latest tap
        $record = $res->fetch_assoc();
        $update = $conn->prepare("UPDATE attendance_record SET time_out = ? WHERE id = ?");
        $update->bind_param("si", $time, $record['id']);
        $update->execute();
        $_SESSION['error'] = "Time Out updated.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Attendance history query
$sql = "SELECT * FROM attendance_record WHERE id_number = ?";
$params = [$id_number];

if ($startDate && $endDate) {
    $sql .= " AND date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Attendance Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen p-6">

  <header class="text-gray-400 font-semibold text-lg mb-4">Attendance</header>
  <button onclick="window.location.href='../StudentDashboard.php'" class="mb-4 text-lg">←</button>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
      <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <!-- Hidden RFID form -->
  <form method="POST" id="rfidForm">
      <input type="text" name="rfid" id="rfidInput" autofocus class="opacity-0 absolute pointer-events-none">
  </form>

  <div class="bg-white rounded-lg shadow-md p-6">
    <form method="get" class="flex flex-col md:flex-row gap-4 mb-6">
      <div class="flex-1">
        <label for="start-date" class="text-sm text-gray-700 block mb-1">Select Start Date:</label>
        <input type="date" id="start-date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border border-gray-300 rounded px-3 py-2">
      </div>
      <div class="flex-1">
        <label for="end-date" class="text-sm text-gray-700 block mb-1">Select End Date:</label>
        <input type="date" id="end-date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border border-gray-300 rounded px-3 py-2">
      </div>
      <div class="flex items-end">
        <button type="submit" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">Generate</button>
      </div>
    </form>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left border border-gray-300">
        <thead class="bg-black text-white">
          <tr>
            <th class="px-4 py-2">Date</th>
            <th class="px-4 py-2">Day</th>
            <th class="px-4 py-2">Class Schedule</th>
            <th class="px-4 py-2">Time In</th>
            <th class="px-4 py-2">Time Out</th>
            <th class="px-4 py-2">Status</th>
          </tr>
        </thead>
        <tbody class="bg-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr class="border-t border-gray-300">
                <td class="px-4 py-2"><?= htmlspecialchars(date('F j, Y', strtotime($row['date']))) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['day']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['schedule'] ?? '--') ?></td>
                <td class="px-4 py-2"><?= $row['time_in'] ? date("h:i A", strtotime($row['time_in'])) : '--' ?></td>
                <td class="px-4 py-2"><?= $row['time_out'] ? date("h:i A", strtotime($row['time_out'])) : '--' ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['status']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="px-4 py-2 text-center text-gray-500">No records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("rfidInput");
    input.focus();
    input.addEventListener("input", function() {
        if (input.value.trim() !== "") {
            document.getElementById("rfidForm").submit();
        }
    });
});
</script>

</body>
</html>
