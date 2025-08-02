<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['id_number'])) {
    $_SESSION['id_number'] = '24'; 
}

$id_number = $_SESSION['id_number'];

$sql = "SELECT document_name, date_submitted, remarks FROM submitted_documents WHERE id_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Submitted Documents</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <div class="bg-white p-4 flex items-center shadow">
    <button onclick="history.back()" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Documents</h1>
  </div>

  <div class="p-6">
    <div class="bg-white p-4 rounded-lg shadow overflow-x-auto">
      <table class="w-full table-auto text-left border border-gray-200">
        <thead>
          <tr class="bg-black text-white text-sm">
            <th class="py-2 px-4 border">Document Name</th>
            <th class="py-2 px-4 border">Date Submitted</th>
            <th class="py-2 px-4 border">Remarks</th>
          </tr>
        </thead>
        <tbody class="text-sm bg-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="py-2 px-4 border"><?= htmlspecialchars($row['document_name']) ?></td>
                <td class="py-2 px-4 border"><?= $row['date_submitted'] ?: '---' ?></td>
                <td class="py-2 px-4 border"><?= htmlspecialchars($row['remarks']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="py-4 px-4 text-center border text-gray-500">No documents submitted.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>