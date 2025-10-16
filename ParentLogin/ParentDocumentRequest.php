<?php
session_start();
include('../StudentLogin/db_conn.php');

if (!isset($_SESSION['parent_id'])) {
    header("Location: ParentLogin.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Unknown';

// Get child information and check if Kinder
$child_query = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name, academic_track, grade_level FROM student_account WHERE id_number = ?");
$child_query->bind_param("s", $child_id);
$child_query->execute();
$child_result = $child_query->get_result();

if ($child_result->num_rows === 1) {
    $child = $child_result->fetch_assoc();
    $child_name = $child['full_name'];
    $child_program = $child['academic_track'];
    $child_year_section = $child['grade_level'];
} else {
    header("Location: ParentDashboard.php");
    exit();
}

// Check if child is in Kinder
$is_kinder = (stripos($child_program, 'kinder') !== false || 
              stripos($child_program, 'pre-elementary') !== false ||
              stripos($child_year_section, 'kinder') !== false);

// Redirect if not Kinder
if (!$is_kinder) {
    header("Location: ParentDashboard.php");
    exit();
}

// Fetch requestable document types
$available_request_options = [];
$resTypes = @$conn->query("SELECT name FROM document_types WHERE is_requestable = 1 ORDER BY name ASC");
if ($resTypes) {
  while ($r = $resTypes->fetch_assoc()) { $available_request_options[] = $r['name']; }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if ($document_type === '' || $purpose === '') {
        $error = "Please fill out all fields.";
    } elseif (!in_array($document_type, $available_request_options, true)) {
        $error = "This document type is not requestable.";
    } else {
        // Check for duplicate active requests
        $check = $conn->prepare("SELECT id FROM document_requests WHERE student_id = ? AND document_type = ? AND status IN ('Pending','Ready to Claim')");
        $check->bind_param("ss", $child_id, $document_type);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "There is already an active request for this document.";
        } else {
            // Insert new request
            $stmt2 = $conn->prepare("INSERT INTO document_requests (student_id, student_name, document_type, purpose, status, date_requested) VALUES (?, ?, ?, ?, 'Pending', NOW())");
            $stmt2->bind_param("ssss", $child_id, $child_name, $document_type, $purpose);
            $stmt2->execute();
            $stmt2->close();

            // Notification for parent
            $msg = "📤 You have submitted a document request for $document_type for your child. We'll notify you once it's processed.";
            $stmt3 = $conn->prepare("INSERT INTO parent_notifications (parent_id, child_id, message, date_sent, is_read) VALUES (?, ?, ?, NOW(), 0)");
            $stmt3->bind_param("sss", $parent_id, $child_id, $msg);
            $stmt3->execute();
            $stmt3->close();

            header("Location: ParentDashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request Document - CCI Parent Portal</title>
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
          <button onclick="window.location.href='ParentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold">Request Document</h1>
            <p class="text-sm text-blue-200">For: <?= htmlspecialchars($child_name) ?></p>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Parent Portal</p>
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
        <p class="text-gray-600">Submit a request for your child's academic documents</p>
        <div class="mt-3 inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Kinder Parent Request
        </div>
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
              <?php foreach ($available_request_options as $doc): ?>
                  <option value="<?= htmlspecialchars($doc) ?>"><?= htmlspecialchars($doc) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Purpose Field -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Purpose <span class="text-red-500">*</span>
            </label>
            <textarea name="purpose" class="w-full border border-gray-300 px-4 py-3 rounded-xl shadow-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all resize-none" rows="4" placeholder="Please specify the purpose for requesting this document (e.g., School transfer, Medical records, etc.)" required></textarea>
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
