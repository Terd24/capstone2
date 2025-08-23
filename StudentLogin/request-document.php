<?php
session_start();
include("db_conn.php"); // Connects $conn

if (!isset($_SESSION['id_number'])) {
    header("Location: index.html");
    exit();
}

$student_id = $_SESSION['id_number'];
$student_name = $_SESSION['full_name'] ?? 'Unknown';

// Fetch documents submitted by registrar AND not yet claimed by the student
$stmt = $conn->prepare("SELECT document_name FROM submitted_documents WHERE id_number = ? AND status='Submitted'");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$submitted_docs = [];
while ($row = $result->fetch_assoc()) {
    $submitted_docs[] = $row['document_name'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = $conn->real_escape_string($_POST['document_type'] ?? '');
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');

    // Check if student already requested this document and it's not cancelled
    $check = $conn->prepare("SELECT id FROM document_requests WHERE student_id = ? AND document_type = ? AND status != 'Cancelled'");
    $check->bind_param("ss", $student_id, $document_type);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "You have already requested this document.";
    } else {
        // Insert request
        $stmt2 = $conn->prepare("INSERT INTO document_requests (student_id, student_name, document_type, purpose, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt2->bind_param("ssss", $student_id, $student_name, $document_type, $purpose);
        $stmt2->execute();
        $stmt2->close();

        // Notification
        $msg = "📤 You have submitted a document request for $document_type. We’ll notify you once it’s processed.";
        $stmt3 = $conn->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
        $stmt3->bind_param("ss", $student_id, $msg);
        $stmt3->execute();
        $stmt3->close();

        header("Location: registrar.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Document</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <div class="bg-white p-4 flex items-center shadow">
    <button onclick="history.back()" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Request Document</h1>
  </div>

  <div class="p-6 flex justify-center">
    <div class="bg-white p-6 rounded-lg shadow w-full max-w-lg">
      <h2 class="text-center font-semibold text-lg mb-4">Request Document</h2>

      <?php if (!empty($error)): ?>
        <p class="text-red-600 mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form class="space-y-4" method="POST" action="request-document.php">
        <div>
          <label class="block text-sm font-medium mb-1">Document Type</label>
          <select name="document_type" class="w-full border border-gray-300 p-2 rounded shadow-sm" required>
            <option value="">Select Document</option>
            <?php foreach ($submitted_docs as $doc): ?>
                <option value="<?= htmlspecialchars($doc) ?>"><?= htmlspecialchars($doc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Purpose<span class="text-red-500">*</span></label>
          <textarea name="purpose" class="w-full border border-gray-300 p-3 rounded shadow-sm" rows="4" placeholder="Enter the purpose for requesting the document" required></textarea>
        </div>

        <button type="submit" class="w-full bg-black text-white py-2 rounded hover:bg-gray-800">Submit</button>
      </form>
    </div>
  </div>

</body>
</html>
