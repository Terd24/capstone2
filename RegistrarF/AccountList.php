<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("Accounts/add_account.php");
}

// Handle success message from add_student.php
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) {
    unset($_SESSION['success_msg']);
}


// Build student-only list
$rows = [];

$student_q = $conn->query("SELECT id_number, first_name, last_name, academic_track, grade_level FROM student_account");
if ($student_q) {
    while ($s = $student_q->fetch_assoc()) {
        $rows[] = [
            'id_number' => $s['id_number'],
            'full_name' => $s['first_name'] . ' ' . $s['last_name'],
            'academic_track' => $s['academic_track'],
            'grade_level' => $s['grade_level'],
        ];
    }
}

usort($rows, function($a, $b){ return strcasecmp($a['full_name'], $b['full_name']); });

$columns = ['ID Number', 'Full Name', 'Academic Track', 'Grade Level'];
$total_accounts = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account List - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="window.location.href='registrardashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
                <h1 class="text-xl font-bold">Account Management</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Grade Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto mt-8 p-6">

    <!-- Header Section with Title and Account Count -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-bold text-[#0B2C62]">Student Accounts</h2>
            <div class="flex items-center gap-2 bg-[#0B2C62] text-white px-4 py-2 rounded-lg shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM21 10a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-sm font-semibold">Total: <?= $total_accounts ?></span>
            </div>
        </div>
    </div>

    <!-- Top Controls -->
    <div class="flex flex-col sm:flex-row gap-4 sm:items-center justify-between mb-6 bg-white p-4 rounded-xl shadow-sm border border-[#0B2C62]/10">
        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62] text-sm">Show entries:</label>
            <input type="number" id="showEntries" min="1" value="10" class="w-20 border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
        </div>

        <div class="flex items-center gap-3">
            <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-64 border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
            <button onclick="openModal()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg shadow hover:bg-[#256f37] transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Account
            </button>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="overflow-x-auto bg-white shadow-lg rounded-2xl p-4 border border-[#0B2C62]/20">
        <table class="min-w-full text-sm border-collapse">
            <thead class="bg-[#0B2C62] text-white">
                <tr>
                    <?php foreach($columns as $col): ?>
                        <th class="px-4 py-3 border text-left font-semibold"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="accountTable" class="divide-y divide-gray-200">
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-[#FBB917]/20 transition cursor-pointer" onclick="viewStudent('<?= htmlspecialchars($r['id_number']) ?>');" >
                            <td class="px-4 py-3"><?= htmlspecialchars($r['id_number']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['academic_track']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['grade_level']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-4 py-6 text-center text-gray-500 italic">No accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Success Notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="fixed top-4 right-4 bg-green-400 text-white px-4 py-2 rounded shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Include Modal -->
<?php include("Accounts/add_account.php"); ?>

<!-- Student View Modal (embedded content) -->
<div id="studentViewOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="relative w-full max-w-6xl max-h-[90vh] mx-auto overflow-hidden">
    <div class="absolute -top-10 right-0 flex gap-2">
      <button aria-label="Close" onclick="closeStudentModal()" class="bg-white text-gray-700 px-3 py-1 rounded-lg shadow hover:bg-gray-100">Close</button>
    </div>
    <div id="studentViewInner" class="w-full h-full overflow-auto bg-white rounded-2xl shadow-2xl border-2 border-[#0B2C62]"></div>
  </div>
</div>

<script>
// Define view functions at global scope immediately
window.viewStudent = async function(studentId) {
    const overlay = document.getElementById('studentViewOverlay');
    const inner = document.getElementById('studentViewInner');
    inner.innerHTML = '<div class="p-8 text-center text-gray-600">Loading student information...</div>';
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';
    try {
        const res = await fetch(`Accounts/view_student.php?embed=1&id=${encodeURIComponent(studentId)}`, { credentials: 'same-origin' });
        const html = await res.text();
        inner.innerHTML = html;

        // Wire up handlers to the embedded student view (no inline script execution)
        setupStudentModalHandlers(studentId);
    } catch (e) {
        inner.innerHTML = '<div class="p-6 text-red-600">Failed to load student information.</div>';
    }
};

// Wire up the embedded Student View (no inline script execution needed)
function setupStudentModalHandlers(studentId) {
    const overlay = document.getElementById('studentViewOverlay');
    const container = document.getElementById('studentViewInner');
    if (!container) return;

    const root = container; // embedded content root
    const editBtn = root.querySelector('#editBtn');
    const deleteBtn = root.querySelector('#deleteBtn');
    const saveBtn = root.querySelector('#saveBtn');
    const xBtn = root.querySelector('button[onclick*="closeModal"], button.text-2xl');
    const backLink = root.querySelector('a[href*="AccountList.php"]');
    const form = root.querySelector('#studentForm');
    const gradeLevel = root.querySelector('#gradeLevel');
    const academicTrack = root.querySelector('select[name="academic_track"]');

    // Ensure form posts to the server endpoint that handles updates/deletes
    if (form) {
        form.action = `Accounts/view_student.php?id=${encodeURIComponent(studentId)}`;
    }

    // Helper to update grade levels based on academic track
    function updateGradeLevelsLocal() {
        if (!academicTrack || !gradeLevel) return;
        const gradeOptions = {
            "Elementary": ["Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6"],
            "Junior High School": ["Grade 7","Grade 8","Grade 9","Grade 10"],
            "Senior High School Strands": ["Grade 11","Grade 12"],
            "STEM": ["Grade 11","Grade 12"],
            "ABM": ["Grade 11","Grade 12"],
            "HUMSS": ["Grade 11","Grade 12"],
            "GAS": ["Grade 11","Grade 12"],
            "TVL": ["Grade 11","Grade 12"],
            "Arts and Design": ["Grade 11","Grade 12"],
            "BS Information Technology": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BS Computer Science": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BS Business Administration": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BS Accountancy": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BS Hospitality Management": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BS Education": ["1st Year","2nd Year","3rd Year","4th Year"]
        };
        const selectedTrack = academicTrack.value;
        const selectedGrade = gradeLevel.value;
        const optgroupLabel = selectedTrack ? academicTrack.options[academicTrack.selectedIndex].parentNode?.label : '';
        let levels = [];
        if (gradeOptions[optgroupLabel]) levels = gradeOptions[optgroupLabel];
        else if (gradeOptions[selectedTrack]) levels = gradeOptions[selectedTrack];
        gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';
        levels.forEach(level => {
            const opt = document.createElement('option');
            opt.value = level; opt.textContent = level;
            if (level === selectedGrade) opt.selected = true;
            gradeLevel.appendChild(opt);
        });
    }

    // Implement toggleEdit locally
    function toggleEditLocal() {
        if (!editBtn || !saveBtn) return;
        const fields = root.querySelectorAll('.student-field');
        const isCancel = editBtn.textContent.trim() === 'Cancel';
        if (isCancel) {
            editBtn.textContent = 'Edit';
            editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
            saveBtn.classList.add('hidden');
            fields.forEach(field => {
                if (['TEXTAREA'].includes(field.tagName) || ['text','number','date'].includes(field.type)) {
                    field.readOnly = true; field.classList.add('bg-gray-50'); field.classList.remove('bg-white');
                } else if (['radio','checkbox'].includes(field.type) || field.tagName === 'SELECT') {
                    field.disabled = true;
                }
            });
        } else {
            editBtn.textContent = 'Cancel';
            editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
            saveBtn.classList.remove('hidden');
            fields.forEach(field => {
                if (['TEXTAREA'].includes(field.tagName) || ['text','number','date'].includes(field.type)) {
                    field.readOnly = false; field.classList.remove('bg-gray-50'); field.classList.add('bg-white');
                } else if (['radio','checkbox'].includes(field.type) || field.tagName === 'SELECT') {
                    field.disabled = false;
                }
            });
            updateGradeLevelsLocal();
        }
    }

    if (editBtn) {
        editBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); toggleEditLocal(); };
    }

    if (deleteBtn) {
        deleteBtn.onclick = (e) => {
            e.preventDefault(); e.stopPropagation();
            // Build simple confirm overlay
            const c = document.createElement('div');
            c.className = 'fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-[2147483647]';
            c.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold mb-2">Delete Student Account</h3>
                    <p class="text-gray-600 mb-6">Are you sure you want to delete this student account? This action cannot be undone.</p>
                    <div class="flex justify-end gap-2">
                        <button id="cCancel" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded">Cancel</button>
                        <button id="cDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                    </div>
                </div>`;
            document.body.appendChild(c);
            c.querySelector('#cCancel').onclick = () => c.remove();
            c.querySelector('#cDelete').onclick = () => {
                const idInput = root.querySelector('input[name="id_number"]');
                const idVal = idInput ? idInput.value : '';
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = `Accounts/view_student.php?id=${encodeURIComponent(studentId)}`;
                f.innerHTML = `<input type="hidden" name="delete_student" value="1"><input type="hidden" name="id_number" value="${idVal}">`;
                document.body.appendChild(f);
                f.submit();
            };
        };
    }

    if (xBtn) {
        xBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); closeStudentModal(); };
    }
    if (backLink) {
        backLink.onclick = (e) => { e.preventDefault(); e.stopPropagation(); closeStudentModal(); };
    }

    if (academicTrack) {
        academicTrack.addEventListener('change', updateGradeLevelsLocal);
    }
}

function closeStudentModal() {
    const overlay = document.getElementById('studentViewOverlay');
    const inner = document.getElementById('studentViewInner');
    inner.innerHTML = '';
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    document.body.style.overflow = '';
}

// Disable outside-click to close for the student modal (close only via X/Back)
document.addEventListener('click', function(e){
  const overlay = document.getElementById('studentViewOverlay');
  if (!overlay || overlay.classList.contains('hidden')) return;
  // Intentionally do nothing on outside click to prevent accidental closing
});

// Close on ESC
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeStudentModal();
});

window.viewParent = function(parentId) {
    console.log('Clicked parent ID:', parentId);
    console.log('Navigating to:', `Accounts/view_parent.php?id=${parentId}`);
    window.location.href = `Accounts/view_parent.php?id=${parentId}`;
};

window.viewAttendance = function(attendanceId) {
    console.log('Clicked attendance ID:', attendanceId);
    console.log('Navigating to:', `Accounts/view_attendance.php?id=${attendanceId}`);
    window.location.href = `Accounts/view_attendance.php?id=${attendanceId}`;
};

// Show success notification with animation
const notificationElement = document.getElementById("notif");
if (notificationElement) {
    // Show notification with slide-in effect
    setTimeout(() => {
        notificationElement.style.transform = 'translateX(0)';
        notificationElement.style.opacity = '1';
    }, 100);
    
    // Hide after 4 seconds with fade out
    setTimeout(() => {
        notificationElement.style.opacity = '0';
        notificationElement.style.transform = 'translateX(100px)';
        setTimeout(() => notificationElement.remove(), 300);
    }, 4000);
}



const searchInput = document.getElementById('searchInput');
const showEntriesInput = document.getElementById('showEntries');
let tableRows = Array.from(document.querySelectorAll('#accountTable tr'));

function updateEntries() {
    const value = parseInt(showEntriesInput.value) || tableRows.length;
    let shown = 0;
    tableRows.forEach(row => row.style.display = '');
    const query = searchInput.value.toLowerCase().trim();
    tableRows.forEach(row => { if (!row.textContent.toLowerCase().includes(query)) row.style.display='none'; });
    shown = 0;
    tableRows.forEach(row => {
        if(row.style.display !== 'none'){ if(shown<value) row.style.display=''; else row.style.display='none'; shown++; }
    });
}
showEntriesInput.addEventListener('input', updateEntries);
searchInput.addEventListener('input', updateEntries);
updateEntries();

// Remove duplicate function definition since it's now at the top
</script>
</body>
</html>