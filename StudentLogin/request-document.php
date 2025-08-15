<?php
session_start();
include("db_conn.php"); // make sure this exists and connects $conn

if (!isset($_SESSION['id_number'])) {
    header("Location: index.html");
    exit();
}

$student_id = $_SESSION['id_number'];
$student_name = $_SESSION['full_name'] ?? 'Unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = $conn->real_escape_string($_POST['document_type'] ?? '');
    $purpose = $conn->real_escape_string($_POST['purpose'] ?? '');

    // insert into document_requests
    $stmt = $conn->prepare("INSERT INTO document_requests (student_id, student_name, document_type, purpose) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $student_id, $student_name, $document_type, $purpose);
    $stmt->execute();
    $stmt->close();

    // create student notification about submission
    $msg = "📤 You have submitted a document request for $document_type. We’ll notify you once it’s processed.";
    $stmt2 = $conn->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
    $stmt2->bind_param("ss", $student_id, $msg);
    $stmt2->execute();
    $stmt2->close();

    // redirect back to student registrar page
    header("Location: registrar.php");
    exit();
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

      <form class="space-y-4" method="POST" action="request-document.php">
        
        <div>
          <label class="block text-sm font-medium mb-1">Document Type</label>
          <select name="document_type" class="w-full border border-gray-300 p-2 rounded shadow-sm" required>
            <option>Form 137</option>
            <option>Transcript of Records</option>
            <option>Certificate of Enrollment</option>
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
