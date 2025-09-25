<?php
session_start();
include("../StudentLogin/db_conn.php");

// ✅ Handle Add Submitted Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_submitted'])) {
    $id_number     = $_POST['id_number'];
    $document_name = $_POST['document_name'];
    $remarks       = $_POST['remarks'] ?? 'Submitted';

    // 🔹 Always create a NEW ROW (no duplicate check, no overwrite)
    $stmtInsert = $conn->prepare("
        INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks, status)
        VALUES (?, ?, NOW(), ?, 'Submitted')
    ");
    $stmtInsert->bind_param("sss", $id_number, $document_name, $remarks);
    $stmtInsert->execute();
    $stmtInsert->close();

    // Notification
    $message = "📤 Your document $document_name has been successfully submitted.";
    $stmtNotif = $conn->prepare("
        INSERT INTO notifications (student_id, message, date_sent, is_read)
        VALUES (?, ?, NOW(), 0)
    ");
    $stmtNotif->bind_param("ss", $id_number, $message);
    $stmtNotif->execute();
    $stmtNotif->close();
    header("Location: ViewStudentInfo.php?student_id=" . urlencode($id_number) . "&type=submitted");
    exit;
}

// ✅ Handle Delete Requested Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_requested'])) {
    $id_number = $_POST['id_number'] ?? '';
    $doc_id    = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
    if ($doc_id > 0 && $id_number !== '') {
        $del = $conn->prepare("DELETE FROM document_requests WHERE id = ? AND student_id = ?");
        $del->bind_param("is", $doc_id, $id_number);
        $del->execute();
        $del->close();
    }
    header("Location: ViewStudentInfo.php?student_id=" . urlencode($id_number) . "&type=requested");
    exit;
}
// ✅ Handle Delete Submitted Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submitted'])) {
    $id_number = $_POST['id_number'] ?? '';
    $doc_id    = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
    if ($doc_id > 0 && $id_number !== '') {
        // Find the document_name before deletion
        $docName = null;
        if ($stmtGet = $conn->prepare("SELECT document_name FROM submitted_documents WHERE id = ? AND id_number = ? LIMIT 1")) {
            $stmtGet->bind_param("is", $doc_id, $id_number);
            if ($stmtGet->execute()) {
                $res = $stmtGet->get_result();
                if ($row = $res->fetch_assoc()) {
                    $docName = $row['document_name'];
                }
            }
            $stmtGet->close();
        }

        // Delete the submitted document row
        $del = $conn->prepare("DELETE FROM submitted_documents WHERE id = ? AND id_number = ?");
        $del->bind_param("is", $doc_id, $id_number);
        $del->execute();
        $del->close();

        // Also remove from student_account.credentials if present
        if (!empty($docName)) {
            if ($stmtCred = $conn->prepare("SELECT credentials FROM student_account WHERE id_number = ? LIMIT 1")) {
                $stmtCred->bind_param("s", $id_number);
                if ($stmtCred->execute()) {
                    $credRes = $stmtCred->get_result();
                    if ($credRow = $credRes->fetch_assoc()) {
                        $credStr = $credRow['credentials'] ?? '';
                        $list = array_filter(array_map('trim', explode(',', $credStr)));
                        // Remove the deleted document name from the credentials list
                        $newList = [];
                        foreach ($list as $item) {
                            if (strcasecmp($item, $docName) !== 0) { // case-insensitive compare
                                $newList[] = $item;
                            }
                        }
                        $updated = implode(',', $newList);
                        if ($upd = $conn->prepare("UPDATE student_account SET credentials = ? WHERE id_number = ?")) {
                            $upd->bind_param("ss", $updated, $id_number);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                }
                $stmtCred->close();
            }
        }
    }
    header("Location: ViewStudentInfo.php?student_id=" . urlencode($id_number) . "&type=submitted");
    exit;
}

// ✅ Fetch Student Info
$student_id = $_GET['student_id'] ?? '';
if (!$student_id) { 
    echo "No student selected."; 
    exit; 
}

$stmt = $conn->prepare("
    SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track as program, grade_level as year_section, rfid_uid 
    FROM student_account 
    WHERE id_number=? OR rfid_uid=?"
);
$stmt->bind_param("ss", $student_id, $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) { 
    echo "Student not found."; 
    exit; 
}

$docType = $_GET['type'] ?? 'requested';

// Load available document types for dropdown (dashboard-managed)
$docTypes = [];
$resTypes = @$conn->query("SELECT name, is_submittable FROM document_types WHERE is_submittable = 1 ORDER BY name ASC");
if ($resTypes) {
    while ($row = $resTypes->fetch_assoc()) {
        $docTypes[] = $row; // ['name'=>..., 'is_requestable'=>0/1]
    }
}

// ✅ Fetch Submitted or Requested Docs
if ($docType === 'submitted') {
    $stmt2 = $conn->prepare("
        SELECT id, document_name, date_submitted, remarks, status 
        FROM submitted_documents 
        WHERE id_number=? 
        ORDER BY date_submitted DESC
    ");
    $stmt2->bind_param("s", $student['id_number']);
    $stmt2->execute();
    $docs = $stmt2->get_result();

    // ✅ Fetch already submitted docs (exclude active unless Claimed)
    $stmtDocs = $conn->prepare("
        SELECT document_name, remarks 
        FROM submitted_documents 
        WHERE id_number=? 
        ORDER BY date_submitted DESC
    ");
    $stmtDocs->bind_param("s", $student['id_number']);
    $stmtDocs->execute();
    $submittedDocsRes = $stmtDocs->get_result();
    $submittedDocs = [];
    while ($row = $submittedDocsRes->fetch_assoc()) {
        if ($row['remarks'] !== 'Claimed') {
            $submittedDocs[] = $row['document_name'];
        }
    }
    $stmtDocs->close();

} else {
    $stmt3 = $conn->prepare("
        SELECT id, document_type, purpose, date_requested, status, date_claimed 
        FROM document_requests 
        WHERE student_id=? 
        ORDER BY date_requested DESC
    ");
    $stmt3->bind_param("s", $student['id_number']);
    $stmt3->execute();
    $docs = $stmt3->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Information - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>

<!-- Delete Requested Document Modal -->
<div id="deleteRequestedModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[2147483647] flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w/full max-w-md mx-4 p-6">
    <div class="flex flex-col items-center text-center">
      <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86l-8.02 13.86A2 2 0 003.99 20h16.02a2 2 0 001.72-3.28L13.71 3.86a2 2 0 00-3.42 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800 mb-1">Delete Requested Document</h3>
      <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete "<span id="delReqDocName" class="font-semibold"></span>"? This action cannot be undone.</p>
      <form id="deleteRequestedForm" method="POST" class="w-full">
        <input type="hidden" name="delete_requested" value="1">
        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
        <input type="hidden" name="doc_id" id="delReqDocId" value="">
        <div class="flex gap-3 w-full mt-2">
          <button type="button" onclick="closeDeleteRequestedModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">Delete</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<script>
  function showDeleteRequestedModal(id, name){
    document.getElementById('delReqDocId').value = id;
    document.getElementById('delReqDocName').textContent = name;
    document.getElementById('deleteRequestedModal').classList.remove('hidden');
  }
  function closeDeleteRequestedModal(){
    document.getElementById('deleteRequestedModal').classList.add('hidden');
  }
</script>
<!-- Delete Submitted Document Modal -->
<div id="deleteSubmittedModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[2147483647] flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
    <div class="flex flex-col items-center text-center">
      <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86l-8.02 13.86A2 2 0 003.99 20h16.02a2 2 0 001.72-3.28L13.71 3.86a2 2 0 00-3.42 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800 mb-1">Delete Submitted Document</h3>
      <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete "<span id="delDocName" class="font-semibold"></span>"? This action cannot be undone.</p>
      <form id="deleteSubmittedForm" method="POST" class="w-full">
        <input type="hidden" name="delete_submitted" value="1">
        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
        <input type="hidden" name="doc_id" id="delDocId" value="">
        <div class="flex gap-3 w-full mt-2">
          <button type="button" onclick="closeDeleteSubmittedModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">Delete</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<script>
  function showDeleteSubmittedModal(id, name){
    document.getElementById('delDocId').value = id;
    document.getElementById('delDocName').textContent = name;
    document.getElementById('deleteSubmittedModal').classList.remove('hidden');
  }
  function closeDeleteSubmittedModal(){
    document.getElementById('deleteSubmittedModal').classList.add('hidden');
  }
</script>
    <link rel="icon" type="image/png" href="../images/Logo.png">
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
    <script>
        function updateStatus(docId, select) {
            const status = select.value;
            fetch('update_document_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${docId}&status=${encodeURIComponent(status)}`
            });
        }
        function switchTab(type) {
            const url = `ViewStudentInfo.php?student_id=<?= $student['id_number'] ?>&type=${type}`;
            window.location.replace(url);
        }
    </script>
</head>
<body class="bg-gray-50 font-sans min-h-screen">

<!-- RFID Form -->
<form id="rfidForm" method="get" action="ViewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header with School Branding -->
<header class="bg-[#0B2C62] text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <button onclick="window.location.href='RegistrarDashboard.php'" class="text-white hover:text-blue-200 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div class="text-left">
          <h1 class="text-xl font-bold">Student Documents</h1>
          <p class="text-blue-200 text-sm">Document Management</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div class="text-right">
          <h2 class="text-lg font-bold">Cornerstone College Inc.</h2>
          <p class="text-blue-200 text-sm">Registrar Portal</p>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Student Info Card -->
    <div class="bg-white rounded-2xl card-shadow p-6 mb-6">
        <div class="flex items-center justify-center mb-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-[#0B2C62] rounded-full flex items-center justify-center mb-3 mx-auto">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-3"><?= htmlspecialchars($student['full_name']) ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="font-medium text-gray-800">Student ID:</span><br>
                        <?= htmlspecialchars($student['id_number']) ?>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="font-medium text-gray-800">Program:</span><br>
                        <?= htmlspecialchars($student['program']) ?>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="font-medium text-gray-800">Year & Section:</span><br>
                        <?= htmlspecialchars($student['year_section']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-col md:flex-row gap-2 w-full justify-center">
            <button onclick="switchTab('requested')" 
                    class="py-3 px-6 rounded-lg flex-1 text-center font-semibold transition-all duration-200 <?= $docType==='requested' ? 'bg-[#0B2C62] text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Requested Documents
            </button>
            <button onclick="switchTab('submitted')" 
                    class="py-3 px-6 rounded-lg flex-1 text-center font-semibold transition-all duration-200 <?= $docType==='submitted' ? 'bg-[#0B2C62] text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Submitted Documents
            </button>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="bg-white rounded-2xl card-shadow p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <?= $docType === 'submitted' ? 'Submitted Documents' : 'Requested Documents' ?>
            </h3>
        </div>

        <?php if($docType==='submitted'): ?>
            <!-- Add Submitted Document -->
            <div class="mb-6">
                <button onclick="document.getElementById('addSubmittedForm').classList.toggle('hidden')" 
                        class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Submitted Document
                </button>
                <div id="addSubmittedForm" class="hidden mt-4 bg-gray-50 p-6 rounded-xl border">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
                        <input type="hidden" name="add_submitted" value="1">
                        <input type="hidden" name="remarks" value="Submitted">

                        <div>
                            <label class="block text-sm font-medium mb-1">Document Name</label>
                            <select name="document_name" class="w-full border border-gray-300 p-2 rounded" required>
                                <option value="">Select Document</option>
                                <?php if (!empty($docTypes)):
                                    foreach ($docTypes as $t):
                                        $name = $t['name'];
                                        if (!in_array($name, $submittedDocs)) {
                                            echo "<option value='".htmlspecialchars($name)."'>".htmlspecialchars($name)."</option>";
                                        }

// ✅ Handle Delete Requested Document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_requested'])) {
    $id_number = $_POST['id_number'] ?? '';
    $doc_id    = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
    if ($doc_id > 0 && $id_number !== '') {
        $del = $conn->prepare("DELETE FROM document_requests WHERE id = ? AND student_id = ?");
        $del->bind_param("is", $doc_id, $id_number);
        $del->execute();
        $del->close();
    }
    header("Location: ViewStudentInfo.php?student_id=" . urlencode($id_number) . "&type=requested");
    exit;
}
                                    endforeach;
                                  else:
                                    // Fallback to legacy list if no types configured yet
                                    $legacy = ["Form 137","Transcript of Records","Certificate of Enrollment","Good Moral Certificate"];
                                    foreach ($legacy as $docOption) {
                                        if (!in_array($docOption, $submittedDocs)) {
                                            echo "<option value='".htmlspecialchars($docOption)."'>".htmlspecialchars($docOption)."</option>";
                                        }
                                    }
                                  endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-3 rounded-lg font-medium transition-colors duration-200">
                            Submit Document
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

<!-- Table -->
<table class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden">
    <thead class="bg-[#0B2C62] text-white">
        <tr>
            <?php if($docType==='requested'): ?>
                <th class="py-4 px-6 text-left font-semibold">Document Type</th>
                <th class="py-4 px-6 text-left font-semibold">Purpose</th>
                <th class="py-4 px-6 text-left font-semibold">Date Requested</th>
                <th class="py-4 px-6 text-left font-semibold">Claimed At</th>
                <th class="py-4 px-6 text-left font-semibold">Status</th>
                <th class="py-4 px-6 text-center font-semibold">Actions</th>
            <?php else: ?>
                <th class="py-4 px-6 text-left font-semibold">Document Name</th>
                <th class="py-4 px-6 text-left font-semibold">Date Submitted</th>
                <th class="py-4 px-6 text-left font-semibold">Remarks</th>
                <th class="py-4 px-6 text-center font-semibold">Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        <?php if ($docs->num_rows > 0): ?>
            <?php while($doc=$docs->fetch_assoc()): ?>
                <tr class="cursor-pointer toggle-row hover:bg-gray-50 transition-colors">
                    <?php if($docType==='requested'): ?>
                        <td class="py-4 px-6 font-medium"><?= htmlspecialchars($doc['document_type']) ?></td>
                        <td class="py-4 px-6 text-gray-600 truncate max-w-[150px]"><?= htmlspecialchars($doc['purpose']) ?></td>
                        <td class="py-4 px-6 text-gray-600"><?= date('M j, Y g:i:s A', strtotime($doc['date_requested'])) ?></td>
                        <td class="py-4 px-6 text-gray-600">
                            <?= ($doc['date_claimed'] && $doc['status']==='Claimed') ? date('M j, Y g:i:s A', strtotime($doc['date_claimed'])) : '---' ?>
                        </td>
                        <td class="py-4 px-6">
                            <select class="border border-gray-300 px-3 py-2 rounded-lg bg-white text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent"
                                    onchange="updateStatus(<?= $doc['id'] ?>, this)">
                                <option value="Pending"        <?= $doc['status']==='Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved"       <?= $doc['status']==='Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Ready to Claim" <?= $doc['status']==='Ready to Claim' ? 'selected' : '' ?>>Ready to Claim</option>
                                <option value="Claimed"        <?= $doc['status']==='Claimed' ? 'selected' : '' ?>>Claimed</option>
                                <option value="Declined"       <?= $doc['status']==='Declined' ? 'selected' : '' ?>>Declined</option>
                            </select>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="arrow text-gray-400 hover:text-gray-600 transition-colors">&#9662;</span>
                        </td>
                    <?php else: ?>
                        <td class="py-4 px-6 font-medium"><?= htmlspecialchars($doc['document_name']) ?></td>
                        <td class="py-4 px-6 text-gray-600"><?= date('M j, Y g:i:s A', strtotime($doc['date_submitted'])) ?></td>
                        <td class="py-4 px-6 text-gray-600"><?= htmlspecialchars($doc['remarks']) ?></td>
                        <td class="py-4 px-6 text-center">
                            <span class="arrow text-gray-400 hover:text-gray-600 transition-colors">&#9662;</span>
                        </td>
                    <?php endif; ?>
                </tr>
                <!-- Hidden expandable row -->
                <tr class="hidden detail-row bg-blue-50">
                    <td colspan="<?= $docType==='requested'?6:4 ?>" class="p-6 text-gray-700 border-t border-blue-100">
                        <?php if($docType==='requested'): ?>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p><strong>Document Type:</strong> <?= htmlspecialchars($doc['document_type']) ?></p>
                                    <p><strong>Purpose:</strong> <?= htmlspecialchars($doc['purpose']) ?></p>
                                    <p><strong>Date Requested:</strong> <?= date('M j, Y g:i:s A', strtotime($doc['date_requested'])) ?></p>
                                    <p><strong>Status:</strong> <?= htmlspecialchars($doc['status']) ?></p>
                                    <p><strong>Claimed At:</strong> <?= $doc['date_claimed'] ? date('M j, Y g:i:s A', strtotime($doc['date_claimed'])) : '---' ?></p>
                                </div>
                                <button type="button" onclick="showDeleteRequestedModal(<?= (int)$doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['document_type'])) ?>')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p><strong>Document Name:</strong> <?= htmlspecialchars($doc['document_name']) ?></p>
                                    <p><strong>Date Submitted:</strong> <?= date('M j, Y g:i:s A', strtotime($doc['date_submitted'])) ?></p>
                                    <p><strong>Remarks:</strong> <?= htmlspecialchars($doc['remarks']) ?></p>
                                </div>
                                <button type="button" onclick="showDeleteSubmittedModal(<?= (int)$doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['document_name'])) ?>')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= $docType==='requested'?6:4 ?>" 
                    class="text-center py-12 text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    No documents found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
document.querySelectorAll(".toggle-row").forEach((row, index) => {
    row.addEventListener("click", (e) => {
        // Prevent row expansion when clicking on select dropdown
        if (e.target.tagName === 'SELECT' || e.target.closest('select')) {
            return;
        }
        
        const detailRow = row.nextElementSibling;
        const arrow = row.querySelector(".arrow");
        detailRow.classList.toggle("hidden");
        arrow.innerHTML = detailRow.classList.contains("hidden") ? "&#9662;" : "&#9652;";
    });
});

// ===== RFID Scanner Logic =====
let rfidBuffer = "";
let lastKeyTime = Date.now();
const rfidInput = document.getElementById("rfid_input");
const rfidForm = document.getElementById("rfidForm");

document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeStudentModal();
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;

    if (e.key === 'Enter') {
        // Submit only if buffer has reasonable length
        if (rfidBuffer.length >= 5) {
            rfidInput.value = rfidBuffer.trim();
            rfidForm.submit();
        }
        rfidBuffer = "";
        e.preventDefault();
    } else if (e.key.length === 1) {
        rfidBuffer += e.key;
    }
});
</script>

</div>
</div>
</body>
</html>
