<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['id_number'])) {
    header("Location: index.html");
    exit();
}

$student_id = $_SESSION['id_number'];
$student_name = $_SESSION['full_name'] ?? 'Unknown';

// ✅ Fetch only currently active submitted docs
$stmt = $conn->prepare("
    SELECT document_name 
    FROM submitted_documents 
    WHERE id_number = ? 
    AND remarks = 'Submitted'
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$submitted_docs = [];
while ($row = $result->fetch_assoc()) {
    $submitted_docs[] = $row['document_name'];
}
$stmt->close();

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if ($document_type === '' || $purpose === '') {
        $error = "Please fill out all fields.";
    } else {
        // 🔹 Only block duplicate requests if still active (Pending or Ready to Claim)
        $check = $conn->prepare("
            SELECT id 
            FROM document_requests 
            WHERE student_id = ? AND document_type = ? 
            AND status IN ('Pending','Ready to Claim')
        ");
        $check->bind_param("ss", $student_id, $document_type);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "You already have an active request for this document.";
        } else {
            // Insert new request
            $stmt2 = $conn->prepare("
                INSERT INTO document_requests (student_id, student_name, document_type, purpose, status, date_requested) 
                VALUES (?, ?, ?, ?, 'Pending', NOW())
            ");
            $stmt2->bind_param("ssss", $student_id, $student_name, $document_type, $purpose);
            $stmt2->execute();
            $stmt2->close();

            // Notification
            $msg = "📤 You have submitted a document request for $document_type. We’ll notify you once it’s processed.";
            $stmt3 = $conn->prepare("
                INSERT INTO notifications (student_id, message, date_sent, is_read) 
                VALUES (?, ?, NOW(), 0)
            ");
            $stmt3->bind_param("ss", $student_id, $msg);
            $stmt3->execute();
            $stmt3->close();

            header("Location: studentDashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Document - CCI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold">Request Document</h1>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Student Portal</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Document Request</h2>
        <p class="text-gray-600">Submit a request for your academic documents</p>
      </div>

      <!-- Request Form Card -->
      <div class="bg-white rounded-2xl card-shadow p-8">
        <?php if (!empty($error)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form class="space-y-6" method="POST" action="">
          <!-- Document Type Selection -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Document Type
            </label>
            <select name="document_type" class="w-full border border-gray-300 px-4 py-3 rounded-xl shadow-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all" required>
              <option value="">Select a document type</option>
              <?php foreach ($submitted_docs as $doc): ?>
                  <option value="<?= htmlspecialchars($doc) ?>"><?= htmlspecialchars($doc) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Only documents you have submitted are available for request</p>
          </div>

          <!-- Purpose Field -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Purpose <span class="text-red-500">*</span>
            </label>
            <textarea name="purpose" class="w-full border border-gray-300 px-4 py-3 rounded-xl shadow-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all resize-none" rows="4" placeholder="Please specify the purpose for requesting this document (e.g., Job application, School transfer, Scholarship application, etc.)" required></textarea>
          </div>

          <!-- Submit Button -->
          <div class="pt-4">
            <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-4 rounded-xl font-semibold transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center space-x-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
              </svg>
              <span>Submit Request</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

</body>
</html>
