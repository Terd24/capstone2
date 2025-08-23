<?php
session_start();
include '../StudentLogin/db_conn.php';

if (!isset($_SESSION['child_id'])) {
    header("Location: ParentLogin.html");
    exit();
}

$id_number = $_SESSION['child_id'];

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Build SQL with conditional date filter
$sql = "SELECT * FROM attendance_record WHERE id_number = ?";
$params = [$id_number];
$types = "s";

if ($startDate && $endDate) {
    $sql .= " AND date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$sql .= " ORDER BY date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parent Attendance Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen p-6">

  <header class="mb-6">
    <div class="bg-white p-4 flex items-center shadow-md">
      <button onclick="window.location.href='ParentDashboard.php'" class="text-2xl mr-4">←</button>
      <h1 class="text-xl font-semibold text-gray-800">Attendance</h1>
    </div>
  </header>

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
</body>
</html>
