<?php
session_start();
include("db_conn.php");

$id_number = $_SESSION['id_number'];

$sql = "SELECT document_type, date_requested, student_id, status 
        FROM document_requests 
        WHERE student_id = ? 
        ORDER BY date_requested DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Requested Documents</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <div class="bg-white p-4 flex items-center shadow">
    <button onclick="history.back()" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Requested Documents</h1>
  </div>

  <div class="p-6">
    <div class="bg-white p-4 rounded-lg shadow overflow-x-auto">
      <table class="w-full table-auto text-left border border-gray-200">
        <thead>
          <tr class="bg-black text-white text-sm">
            <th class="py-2 px-4 border">Document Name</th>
            <th class="py-2 px-4 border">Date Submitted</th>
            <th class="py-2 px-4 border">Status</th>
          </tr>
        </thead>
        <tbody class="text-sm bg-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="py-2 px-4 border"><?= htmlspecialchars($row['document_type']) ?></td>
                <td class="py-2 px-4 border"><?= $row['date_requested'] ?: '---' ?></td>
                <td class="py-2 px-4 border">
                  <?php if ($row['status'] === 'Pending'): ?>
                    <span class="text-yellow-600 font-medium">Pending</span>
                  <?php elseif ($row['status'] === 'Ready for Claiming'): ?>
                    <span class="text-green-600 font-medium">Ready for Claiming</span>
                  <?php else: ?>
                    <?= htmlspecialchars($row['status']) ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="py-4 px-4 text-center border text-gray-500">
                No requested documents.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
<?php $stmt->close(); ?>
