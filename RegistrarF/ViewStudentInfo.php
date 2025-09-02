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
    <title>Student Information</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-100 font-sans">

<!-- RFID Form -->
<form id="rfidForm" method="get" action="ViewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header -->
<div class="bg-white p-4 flex items-center shadow-md">
    <button onclick="window.location.href='registrardashboard.php'" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Documents</h1>
</div>

<div class="max-w-5xl mx-auto mt-10">
    <!-- Student Info Card -->
    <div class="bg-white p-6 rounded-lg shadow flex flex-col items-center gap-6">
        <div class="flex gap-4 items-start">
            <div class="text-4xl">👤</div>
            <div>
                <p class="font-semibold text-lg"><?= htmlspecialchars($student['full_name']) ?></p>
                <p class="text-sm text-gray-500">ID: <?= htmlspecialchars($student['id_number']) ?></p>
                <p class="text-sm text-gray-500">Program: <?= htmlspecialchars($student['program']) ?></p>
                <p class="text-sm text-gray-500">Year & Section: <?= htmlspecialchars($student['year_section']) ?></p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-col md:flex-row gap-3 w-full justify-center mt-4">
            <a href="javascript:void(0);" onclick="switchTab('requested')" 
            class="py-3 rounded-lg flex-1 text-center font-semibold <?= $docType==='requested' ? 'bg-black text-white' : 'bg-gray-200 text-black' ?>">
            📝 Requested Documents
            </a>
            <a href="javascript:void(0);" onclick="switchTab('submitted')" 
            class="py-3 rounded-lg flex-1 text-center font-semibold <?= $docType==='submitted' ? 'bg-black text-white' : 'bg-gray-200 text-black' ?>">
            📄 Submitted Documents
            </a>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="bg-white mt-6 rounded-lg shadow p-6 overflow-x-auto">
        <h3 class="font-semibold text-lg mb-4">
            <?= $docType === 'submitted' ? 'Submitted Documents' : 'Requested Documents' ?>
        </h3>

        <?php if($docType==='submitted'): ?>
            <!-- Add Submitted Document -->
            <div class="mb-6">
                <button onclick="document.getElementById('addSubmittedForm').classList.toggle('hidden')" 
                        class="bg-black text-white px-4 py-2 rounded hover:bg-white hover:text-black border border-black transition-all duration-200">
                    Add Submitted Document
                </button>
                <div id="addSubmittedForm" class="hidden mt-4 bg-gray-50 p-4 rounded border">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
                        <input type="hidden" name="add_submitted" value="1">
                        <input type="hidden" name="remarks" value="Submitted">

                        <div>
                            <label class="block text-sm font-medium mb-1">Document Name</label>
                            <select name="document_name" class="w-full border border-gray-300 p-2 rounded" required>
                                <option value="">Select Document</option>
                                <?php
                                $allDocs = ["Form 137", "Transcript of Records", "Certificate of Enrollment", "Good Moral Certificate"];
                                foreach ($allDocs as $docOption) {
                                    if (!in_array($docOption, $submittedDocs)) { 
                                        echo "<option value='" . htmlspecialchars($docOption) . "'>$docOption</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-black text-white py-2 rounded hover:bg-white hover:text-black border border-black transition-all duration-200">
                            Submit Document
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

<!-- Table -->
<table class="w-full text-sm border border-gray-300">
    <thead class="bg-black text-white">
        <tr>
            <?php if($docType==='requested'): ?>
                <th class="py-2 px-4 border text-left">Document Type</th>
                <th class="py-2 px-4 border text-left">Purpose</th>
                <th class="py-2 px-4 border text-left">Date Requested</th>
                <th class="py-2 px-4 border text-left">Claimed At</th>
                <th class="py-2 px-4 border text-left">Status</th>
                <th class="py-2 px-4 border"></th>
            <?php else: ?>
                <th class="py-2 px-4 border text-left">Document Name</th>
                <th class="py-2 px-4 border text-left">Date Submitted</th>
                <th class="py-2 px-4 border text-left">Remarks</th>
                <th class="py-2 px-4 border"></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($docs->num_rows > 0): ?>
            <?php while($doc=$docs->fetch_assoc()): ?>
                <tr class="cursor-pointer toggle-row">
                    <?php if($docType==='requested'): ?>
                        <td class="py-2 px-4 border"><?= htmlspecialchars($doc['document_type']) ?></td>
                        <td class="py-2 px-4 border truncate max-w-[150px]"><?= htmlspecialchars($doc['purpose']) ?></td>
                        <td class="py-2 px-4 border"><?= $doc['date_requested'] ?></td>
                        <td class="py-2 px-4 border">
                            <?= ($doc['date_claimed'] && $doc['status']==='Claimed') ? $doc['date_claimed'] : '---' ?>
                        </td>
                        <td class="py-2 px-4 border">
                            <select class="border px-2 py-1 rounded bg-white"
                                    onchange="updateStatus(<?= $doc['id'] ?>, this)">
                                <option value="Pending"        <?= $doc['status']==='Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Ready to Claim" <?= $doc['status']==='Ready to Claim' ? 'selected' : '' ?>>Ready to Claim</option>
                                <option value="Claimed"        <?= $doc['status']==='Claimed' ? 'selected' : '' ?>>Claimed</option>
                            </select>
                        </td>
                        <td class="py-2 px-4 border text-center">
                            <span class="arrow">&#9662;</span>
                        </td>
                    <?php else: ?>
                        <td class="py-2 px-4 border"><?= htmlspecialchars($doc['document_name']) ?></td>
                        <td class="py-2 px-4 border"><?= date('Y-m-d H:i:s', strtotime($doc['date_submitted'])) ?></td>
                        <td class="py-2 px-4 border"><?= htmlspecialchars($doc['remarks']) ?></td>
                        <td class="py-2 px-4 border text-center">
                            <span class="arrow">&#9662;</span>
                        </td>
                    <?php endif; ?>
                </tr>
                <!-- Hidden expandable row -->
                <tr class="hidden detail-row bg-gray-50">
                    <td colspan="<?= $docType==='requested'?6:4 ?>" class="p-4 text-gray-700">
                        <?php if($docType==='requested'): ?>
                            <div>
                                <p><strong>Document Type:</strong> <?= htmlspecialchars($doc['document_type']) ?></p>
                                <p><strong>Purpose:</strong> <?= htmlspecialchars($doc['purpose']) ?></p>
                                <p><strong>Date Requested:</strong> <?= $doc['date_requested'] ?></p>
                                <p><strong>Status:</strong> <?= htmlspecialchars($doc['status']) ?></p>
                                <p><strong>Claimed At:</strong> <?= $doc['date_claimed'] ?: '---' ?></p>
                            </div>
                        <?php else: ?>
                            <div>
                                <p><strong>Document Name:</strong> <?= htmlspecialchars($doc['document_name']) ?></p>
                                <p><strong>Date Submitted:</strong> <?= date('Y-m-d H:i:s', strtotime($doc['date_submitted'])) ?></p>
                                <p><strong>Remarks:</strong> <?= htmlspecialchars($doc['remarks']) ?></p>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= $docType==='requested'?6:4 ?>" 
                    class="text-center py-3 text-gray-500">
                    No documents found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
document.querySelectorAll(".toggle-row").forEach((row, index) => {
    row.addEventListener("click", () => {
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

document.addEventListener('keydown', (e) => {
    const currentTime = Date.now();
    
    // Reset buffer if too much time passed between keys
    if (currentTime - lastKeyTime > 100) rfidBuffer = "";
    lastKeyTime = currentTime;

    // Ignore typing in inputs or textareas
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
