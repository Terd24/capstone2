<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['id_number'])) {
    header("Location: index.html");
    exit();
}

$id_number = $_SESSION['id_number'];
$full_name = $_SESSION['full_name'] ?? '';
$program = $_SESSION['program'] ?? '';
$year_section = $_SESSION['year_section'] ?? '';

// fetch notifications for this student
$stmt = $conn->prepare("SELECT message, date_sent, is_read FROM notifications WHERE student_id = ? ORDER BY date_sent DESC");
$stmt->bind_param("s", $id_number);
$stmt->execute();
$notifs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrar</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
  
  <div class="bg-white p-4 flex items-center shadow">
    <button onclick="history.back()" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Registrar</h1>
  </div>

  <div class="p-6 grid md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow flex flex-col gap-6">
      <div class="flex gap-4 items-start">
        <div class="text-4xl">👤</div>
        <div>
          <p class="font-semibold"><?= htmlspecialchars($full_name) ?></p>
          <p class="text-sm text-gray-500">ID: <?= htmlspecialchars($id_number) ?></p>
          <p class="text-sm text-gray-500">Program: <?= htmlspecialchars($program) ?></p>
          <p class="text-sm text-gray-500">Year & Section: <?= htmlspecialchars($year_section) ?></p>
        </div>
      </div>

      <div class="flex flex-col gap-3">
        <button onclick="location.href='documents.php'" class="bg-black text-white py-3 rounded-lg flex items-center justify-center hover:bg-gray-800">📄 Documents</button>
        <button onclick="location.href='requested_document.php'" class="bg-black text-white py-3 rounded-lg flex items-center justify-center hover:bg-gray-800">📝 Requested Document</button>
        <button onclick="location.href='request-document.php'" class="bg-black text-white py-3 rounded-lg flex items-center justify-center hover:bg-gray-800">📝 Request Document</button>
      </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
      <div class="flex justify-between mb-2">
        <p class="font-semibold">Notification</p>
        <span class="text-sm text-gray-400">Update</span>
      </div>
      <ul class="text-sm text-gray-700 space-y-2">
        <?php if ($notifs->num_rows > 0): ?>
          <?php while ($row = $notifs->fetch_assoc()): ?>
            <li>
              <?= nl2br(htmlspecialchars($row['message'])) ?>
              <br>
              <em class="text-gray-400 text-xs"><?= htmlspecialchars($row['date_sent']) ?></em>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li class="text-gray-500">No notifications.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

</body>
</html>
<?php $stmt->close(); ?>
