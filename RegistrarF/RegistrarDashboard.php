<?php
session_start();
include("../StudentLogin/db_conn.php");

if (isset($_GET['mark_ready'])) {
    $req_id = intval($_GET['mark_ready']);

    // update status
    $stmt = $conn->prepare("UPDATE document_requests SET status='Ready for Claiming' WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $stmt->close();

    // get student id and doc type
    $res = $conn->query("SELECT student_id, document_type FROM document_requests WHERE id = $req_id");
    if ($res && $data = $res->fetch_assoc()) {
        $msg = "📌 Your {$data['document_type']} is now ready for claiming at the Registrar’s Office.\nTap here for claiming instructions.";
        $stmt2 = $conn->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
        $stmt2->bind_param("ss", $data['student_id'], $msg);
        $stmt2->execute();
        $stmt2->close();
    }

    header("Location: registrarDashboard.php");
    exit();
}

// fetch latest requests
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registrar Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

  <div class="bg-white p-4 flex items-center justify-between border-b shadow-sm">
    <div class="text-2xl">&#9776;</div>
    <div class="text-gray-500 text-sm">Registrar Dashboard</div>
  </div>

  <div class="p-6 flex flex-col md:flex-row gap-6">
    <div class="bg-white rounded-lg p-6 w-full md:w-1/3 shadow-md">
      <div class="flex items-center mb-4">
        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-xl">👤</div>
        <div class="ml-3 font-medium"><?= htmlspecialchars($_SESSION['registrar_name'] ?? 'Employee name') ?></div>
      </div>
      <p class="text-sm text-gray-600 mb-1">ID: <?= htmlspecialchars($_SESSION['registrar_id']) ?></p>
      <p class="text-sm text-gray-600">Program:</p>
    </div>

    <div class="bg-white rounded-lg p-6 w-full md:w-2/3 shadow-md">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Student Requests</h2>
        <a href="Seemore.php" class="text-sm text-blue-500 hover:underline">See More</a>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="bg-gray-200 p-4 rounded mb-4">
            <p class="text-sm font-medium">Student requested for <?= htmlspecialchars($row['document_type']) ?></p>
            <p class="text-xs text-gray-700 italic">Requested by: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)</p>
            <div class="flex justify-between items-center mt-2">
              <span class="text-xs"><?= htmlspecialchars($row['purpose'] ?: 'Document request.') ?></span>
              <div class="flex items-center gap-3">
                <span class="text-xs"><?= htmlspecialchars($row['status']) ?></span>
                <?php if ($row['status'] === 'Pending'): ?>
                  <a href="?mark_ready=<?= $row['id'] ?>" class="text-sm text-blue-600 hover:underline">Mark Ready</a>
                <?php else: ?>
                  <span class="text-sm text-green-700">Ready</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="bg-gray-100 p-4 rounded">No requests yet.</div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
