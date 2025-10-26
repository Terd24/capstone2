<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['id_number'])) {
    header("Location: index.html");
    exit();
}

$student_id = $_SESSION['id_number'];
$student_name = $_SESSION['full_name'] ?? 'Unknown';

// ✅ Fetch only currently active submitted docs for this student
$stmt = $conn->prepare("\n    SELECT document_name \n    FROM submitted_documents \n    WHERE id_number = ? \n    AND remarks = 'Submitted'\n");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$submitted_docs = [];
while ($row = $result->fetch_assoc()) {
    $submitted_docs[] = $row['document_name'];
}
$stmt->close();

// ✅ Fetch requestable document types from registrar configuration (independent of submitted docs)
$available_request_options = [];
$resTypes = @$conn->query("SELECT name FROM document_types WHERE is_requestable = 1 ORDER BY name ASC");
if ($resTypes) {
  while ($r = $resTypes->fetch_assoc()) { $available_request_options[] = $r['name']; }
}

// ✅ Handle form submission with server-side validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if ($document_type === '' || $purpose === '') {
        $error = "Please fill out all fields.";
    } elseif (!in_array($document_type, $available_request_options, true)) {
        $error = "This document type is not requestable.";
    } else {
        // Only block duplicate requests if still active (Pending or Ready to Claim)
        $check = $conn->prepare("SELECT id FROM document_requests WHERE student_id = ? AND document_type = ? AND status IN ('Pending','Ready to Claim')");
        $check->bind_param("ss", $student_id, $document_type);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "You already have an active request for this document.";
        } else {
            // Insert new request
            $stmt2 = $conn->prepare("INSERT INTO document_requests (student_id, student_name, document_type, purpose, status, date_requested) VALUES (?, ?, ?, ?, 'Pending', NOW())");
            $stmt2->bind_param("ssss", $student_id, $student_name, $document_type, $purpose);
            $stmt2->execute();
            $stmt2->close();

            // Notification
            $msg = "📤 You have submitted a document request for $document_type. We’ll notify you once it’s processed.";
            $stmt3 = $conn->prepare("INSERT INTO notifications (student_id, message, date_sent, is_read) VALUES (?, ?, NOW(), 0)");
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Request Document - CCI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }

    /* PWA Mobile Optimizations */
    @media (max-width: 640px) {
      /* Prevent horizontal scroll */
      body { overflow-x: hidden; }

      /* Ensure proper mobile viewport */
      html, body {
        height: 100%;
        width: 100%;
        position: relative;
      }

      /* Safe area insets for devices with notches */
      .container { padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right); }

      /* Prevent zoom on input focus */
      input, select, textarea {
        font-size: 16px !important;
        -webkit-text-size-adjust: 100%;
        -webkit-user-select: text;
      }
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-[#0B2C62] text-white shadow-lg">
      <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <div>
              <h1 class="text-lg sm:text-xl font-bold">Request Document</h1>
            </div>
          </div>
          <div class="flex items-center space-x-3 sm:space-x-4">
            <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-white p-1">
            <div class="hidden sm:block text-right">
              <h1 class="text-lg sm:text-xl font-bold">Cornerstone College Inc.</h1>
              <p class="text-blue-200 text-xs sm:text-sm">Student Portal</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 sm:px-6 py-6 sm:py-8">
      <div class="max-w-2xl mx-auto">
        <!-- Page Header -->
        <div class="text-center mb-6 sm:mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Document Request</h2>
          <p class="text-gray-600 text-sm sm:text-base">Submit a request for your academic documents</p>
        </div>

        <!-- Request Form Card -->
        <div class="bg-white rounded-2xl card-shadow p-6 sm:p-8">
          <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center min-h-[60px]">
              <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span class="text-sm sm:text-base font-medium break-words flex-1">
                <?= htmlspecialchars($error) ?>
              </span>
            </div>
          <?php endif; ?>

          <form class="space-y-6 sm:space-y-8" method="POST" action="">
            <!-- Document Type Selection -->
            <div class="space-y-3">
              <label class="block text-sm font-semibold text-gray-700">
                <div class="flex items-center mb-2">
                  <svg class="w-5 h-5 mr-2 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <span class="text-gray-800">Document Type</span>
                </div>
              </label>
              <select name="document_type" class="w-full border border-gray-300 px-4 py-3 sm:py-4 rounded-xl shadow-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all text-sm sm:text-base min-h-[48px] sm:min-h-[52px]" required>
                <option value="">Select a document type</option>
                <?php foreach ($available_request_options as $doc): ?>
                    <option value="<?= htmlspecialchars($doc) ?>" class="py-2 sm:py-3">
                      <?= htmlspecialchars($doc) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Purpose Field -->
            <div class="space-y-3">
              <label class="block text-sm font-semibold text-gray-700">
                <div class="flex items-start mb-2">
                  <svg class="w-5 h-5 mr-2 text-[#0B2C62] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                  <div class="flex-1">
                    <span class="text-gray-800">Purpose</span>
                    <span class="text-red-500 ml-1">*</span>
                  </div>
                </div>
              </label>
              <textarea name="purpose"
                        class="w-full border border-gray-300 px-4 py-3 sm:py-4 rounded-xl shadow-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all resize-none text-sm sm:text-base min-h-[120px] sm:min-h-[128px]"
                        rows="4"
                        placeholder="Please specify the purpose for requesting this document (e.g., Job application, School transfer, Scholarship application, etc.)"
                        required></textarea>
              <p class="text-xs text-gray-500 mt-2">Required field - Please provide details about why you need this document</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 sm:pt-8">
              <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-4 sm:py-5 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl min-h-[56px] sm:min-h-[60px]">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                <span class="text-base sm:text-lg">Submit Request</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
