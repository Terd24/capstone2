<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['id_number'])) {
  header("Location: index.php");
  exit();
}

$id_number = $_SESSION['id_number'];
$full_name = $_SESSION['full_name'];
$program = $_SESSION['program'];
$year_section = $_SESSION['year_section'];

// Get guidance record
$sql = "SELECT * FROM guidance_records WHERE id_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();

$guidance_data = [];
while ($row = $result->fetch_assoc()) {
  $guidance_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Guidance Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans p-6">

  <header class="mb-6">
    <div class="bg-white p-4 flex items-center shadow-md">
      <button onclick="window.location.replace('studentdashboard.php')" class="text-2xl mr-4">←</button>
      <h1 class="text-xl font-semibold text-gray-800">Guidance</h1>
    </div>
  </header>

  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-center text-lg font-medium mb-4 border-b pb-2">Behavior Record</h2>

    <div class="flex flex-col md:flex-row gap-6 mt-4">

      <!-- Student Info -->
      <div class="w-full md:w-1/3 bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="flex items-center mb-4">
          <div class="text-3xl bg-gray-300 rounded-full w-12 h-12 flex items-center justify-center">👤</div>
          <div class="ml-3">
            <p class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($full_name) ?></p>
          </div>
        </div>
        <div class="text-sm text-gray-600 space-y-1">
          <p><strong>ID:</strong> <?= htmlspecialchars($id_number) ?></p>
          <p><strong>Program:</strong> <?= htmlspecialchars($program) ?></p>
          <p><strong>Year & Section:</strong> <?= htmlspecialchars($year_section) ?></p>
        </div>
      </div>

      <!-- Guidance Record -->
      <div class="w-full md:w-2/3 bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-md font-semibold text-gray-800 mb-2">Student Guidance Record</h3>

        <?php if (count($guidance_data) === 0): ?>
          <p class="text-sm text-gray-700">No guidance records available.</p>
        <?php else: ?>
          <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
            <?php foreach ($guidance_data as $record): ?>
              <li>
                <?= htmlspecialchars($record['remarks']) ?> 
                (<?= htmlspecialchars(date("F j, Y", strtotime($record['record_date']))) ?>)
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      </div>

    </div>
  </div>

</body>
</html>
