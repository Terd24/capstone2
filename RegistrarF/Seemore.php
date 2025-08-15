<?php
session_start();
include("../StudentLogin/db_conn.php");

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
            <th class="px-4 py-2">Document Name</th>
            <th class="px-4 py-2">Date Submitted</th>
            <th class="px-4 py-2">Student No</th>
            <th class="px-4 py-2">Status</th>
          </tr>
        </thead>
        <tbody class="text-sm text-gray-800">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($r = $result->fetch_assoc()): ?>
              <tr class="<?= ($r['status'] === 'Pending') ? '' : 'bg-gray-100' ?>">
                <td class="px-4 py-2"><?= htmlspecialchars($r['document_type']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($r['date_requested']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($r['student_id']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($r['status']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="px-4 py-6 text-center text-gray-500">No requests found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
