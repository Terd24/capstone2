<?php
session_start();
include("../StudentLogin/db_conn.php");

// Handle Add Submitted Document (when registrar submits form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_submitted'])) {
    $id_number = $_POST['id_number'];
    $document_name = $_POST['document_name'];
    $remarks = $_POST['remarks'] ?? '';

    $stmt = $conn->prepare("INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks) 
                            VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("sss", $id_number, $document_name, $remarks);
    $stmt->execute();

    // Redirect para mag-refresh at makita agad yung bagong document
    header("Location: ViewStudentInfo.php?student_id=" . urlencode($id_number) . "&type=submitted");
    exit;
}

// Get student_id from GET
$student_id = $_GET['student_id'] ?? '';
if (!$student_id) {
    echo "No student selected.";
    exit;
}

// Fetch student info
$stmt = $conn->prepare("
    SELECT id_number, full_name, program, year_section, rfid_uid
    FROM student_account
    WHERE id_number = ? OR rfid_uid = ?
");
$stmt->bind_param("ss", $student_id, $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Determine which document to show
$docType = $_GET['type'] ?? 'requested'; // 'submitted' or 'requested'
if ($docType === 'submitted') {
    $stmt2 = $conn->prepare("SELECT id, document_name, date_submitted, remarks FROM submitted_documents WHERE id_number = ?");
    $stmt2->bind_param("s", $student['id_number']);
    $stmt2->execute();
    $docs = $stmt2->get_result();
} else {
    $stmt3 = $conn->prepare("SELECT id, document_type, purpose, date_requested, status FROM document_requests WHERE student_id = ? ORDER BY date_requested DESC");
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
        })
        .then(res => res.text())
        .then(data => {
            console.log(data);
            if (status === "Claimed") {
                alert("Document has been claimed and date recorded.");
            }
        })
        .catch(err => console.error(err));
    }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="bg-white p-4 flex items-center shadow-md">
        <button onclick="window.history.back()" class="text-2xl mr-4">←</button>
        <h1 class="text-xl font-semibold">Documents</h1>
    </div>

    <div class="max-w-5xl mx-auto mt-10">
        <!-- Student Profile -->
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

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row gap-3 w-full justify-center mt-4">
                <a href="?student_id=<?= $student['id_number'] ?>&type=requested"
                   class="py-3 rounded-lg flex-1 text-center font-semibold hover:bg-white-800 <?= $docType==='requested' ? 'bg-black text-white' : 'bg-gray-200 text-black' ?>">
                    📝 Requested Documents
                </a>
                <a href="?student_id=<?= $student['id_number'] ?>&type=submitted"
                   class="py-3 rounded-lg flex-1 text-center font-semibold hover:bg-white-800 <?= $docType==='submitted' ? 'bg-black text-white' : 'bg-gray-200 text-black' ?>">
                    📄 Submitted Documents
                </a>
            </div>
        </div>

        <!-- Document Table -->
        <div class="bg-white mt-6 rounded-lg shadow p-6 overflow-x-auto">
            <h3 class="font-semibold text-lg mb-4">
                <?= $docType === 'submitted' ? 'Submitted Documents' : 'Requested Documents' ?>
            </h3>

                      <?php if ($docType === 'submitted'): ?>
            <!-- Add Submitted Document Button & Form -->
            <div class="mb-6">
                <button onclick="document.getElementById('addSubmittedForm').classList.toggle('hidden')" 
                        class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
                    Add Submitted Document
                </button>

                <div id="addSubmittedForm" class="hidden mt-4 bg-gray-50 p-4 rounded border">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
                        <input type="hidden" name="add_submitted" value="1">

                        <div>
                            <label class="block text-sm font-medium mb-1">Document Name</label>
                            <select name="document_name" class="w-full border border-gray-300 p-2 rounded" required>
                                <option value="">Select Document</option>
                                <option value="Form 137">Form 137</option>
                                <option value="Transcript of Records">Transcript of Records</option>
                                <option value="Certificate of Enrollment">Certificate of Enrollment</option>
                                <option value="Good Moral Certificate">Good Moral Certificate</option>
                            </select>
                        </div>

                        <!-- Hidden remarks (always Submitted) -->
                        <input type="hidden" name="remarks" value="Submitted">

                        <button type="submit" class="w-full bg-black text-white py-2 rounded hover:bg-gray-800">
                            Submit Document
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <table class="w-full text-sm border border-gray-300">
                <thead class="bg-black text-white">
                    <tr>
                        <th class="py-2 px-4 border"><?= $docType === 'submitted' ? 'Document Name' : 'Document Type' ?></th>
                        <?php if($docType==='requested'): ?>
                        <th class="py-2 px-4 border">Purpose</th>
                        <?php endif; ?>
                        <th class="py-2 px-4 border"><?= $docType === 'submitted' ? 'Date Submitted' : 'Date Requested' ?></th>
                        <th class="py-2 px-4 border"><?= $docType === 'submitted' ? 'Remarks' : 'Status' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($docs->num_rows > 0): ?>
                        <?php while ($doc = $docs->fetch_assoc()): ?>
                            <tr>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($docType === 'submitted' ? $doc['document_name'] : $doc['document_type']) ?></td>
                                <?php if($docType==='requested'): ?>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($doc['purpose']) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4 border"><?= htmlspecialchars($docType === 'submitted' ? $doc['date_submitted'] : $doc['date_requested']) ?></td>
                                <td class="py-2 px-4 border">
                                    <?php if ($docType === 'submitted'): ?>
                                        <?= htmlspecialchars($doc['remarks']) ?>
                                    <?php else: ?>
                                        <select class="border px-2 py-1 rounded bg-white" onchange="updateStatus(<?= $doc['id'] ?>, this)">
                                            <option value="Pending" <?= $doc['status']==='Pending'?'selected':'' ?>>Pending</option>
                                            <option value="Ready to Claim" <?= $doc['status']==='Ready to Claim'?'selected':'' ?>>Ready to Claim</option>
                                            <option value="Claimed" <?= $doc['status']==='Claimed'?'selected':'' ?>>Claimed</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= $docType==='requested'?4:3 ?>" class="text-center py-3 text-gray-500">No documents found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
