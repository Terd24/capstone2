<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$schedule_id = $_GET['schedule_id'] ?? null;
$section_name = $_GET['section_name'] ?? 'Unknown Section';

if (!$schedule_id) {
    header("Location: ManageSchedule.php");
    exit;
}

// Get schedule details
$schedule_stmt = $conn->prepare("SELECT * FROM class_schedules WHERE id = ?");
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

// Get assigned students
$students_stmt = $conn->prepare("
    SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, 
           sa.academic_track as program, sa.grade_level as year_section,
           ss.assigned_at
    FROM student_schedules ss
    JOIN student_account sa ON ss.student_id = sa.id_number
    WHERE ss.schedule_id = ?
    ORDER BY sa.first_name, sa.last_name
");
$students_stmt->bind_param("i", $schedule_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Handle student removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove_student') {
    $student_id = $_POST['student_id'];
    
    // Remove from student_schedules
    $remove_stmt = $conn->prepare("DELETE FROM student_schedules WHERE student_id = ? AND schedule_id = ?");
    $remove_stmt->bind_param("si", $student_id, $schedule_id);
    
    if ($remove_stmt->execute()) {
        // Clear schedule from student_account and attendance_record
        $clear_student = $conn->prepare("UPDATE student_account SET class_schedule = NULL WHERE id_number = ?");
        $clear_student->bind_param("s", $student_id);
        $clear_student->execute();
        $clear_attendance = $conn->prepare("UPDATE attendance_record SET schedule = NULL WHERE id_number = ?");
        $clear_attendance->bind_param("s", $student_id);
        $clear_attendance->execute();
        $notice = 'success';
        $msg = 'Removed student from schedule successfully!';
    } else {
        $notice = 'error';
        $msg = 'Failed to remove student from schedule.';
    }
    // Refresh the page with notice
    header("Location: view_schedule_students.php?schedule_id=$schedule_id&section_name=" . urlencode($section_name) . "&notice=".$notice."&msg=".urlencode($msg));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Students - <?= htmlspecialchars($section_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button onclick="window.location.href='ManageSchedule.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div>
                    <h2 class="text-lg font-semibold"><?= htmlspecialchars($section_name) ?> Students</h2>
                    <p class="text-blue-200 text-sm">
                        <?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                        • <?= htmlspecialchars($schedule['days']) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Schedule Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Success/Error Messages -->
    <?php if (isset($success_msg)): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Students List -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Assigned Students</h2>
            <div class="flex items-center gap-3">
                <div class="text-sm text-gray-600 mr-2">Total: <span id="totalCount"><?= $students_result->num_rows ?></span> student(s)</div>
                <button type="button" onclick="showAssignStudentsModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Add Students</button>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="searchInput" placeholder="Search students by name, ID number, or program..." 
                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
            </div>
        </div>
        
        <?php if ($students_result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year/GRADE LEVEL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr class="bg-blue-50 hover:bg-blue-100 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['full_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['id_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['program'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['year_section'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($student['assigned_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="removeStudent('<?= htmlspecialchars($student['id_number']) ?>', '<?= htmlspecialchars($student['full_name']) ?>')" 
                                            class="text-red-600 hover:text-red-900">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-500 text-lg">No students assigned to this schedule</p>
                <p class="text-gray-400 text-sm">Use "Assign Schedule to Students" to add students</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Students Modal (centered) -->
<div id="assignStudentsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-bold text-gray-800">Assign Schedule to Students</h3>
      <button onclick="hideAssignStudentsModal()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div class="mb-2 text-sm text-gray-600">Schedule: <span class="font-medium"><?= htmlspecialchars($section_name) ?></span></div>
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Search Students</label>
      <input type="text" id="assignStudentSearch" placeholder="Search students by name, ID, or program..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" />
    </div>
    <div id="assignStudentsList" class="border rounded-lg h-72 overflow-y-auto">
      <p class="text-gray-500 text-center py-4">Loading students...</p>
    </div>
    <div class="mb-4 text-sm text-gray-600">Selected: <span id="assignStudentsSelected">0</span> student(s)</div>
    <div class="flex gap-3">
      <button type="button" onclick="hideAssignStudentsModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
      <button type="button" onclick="assignStudentsProceed()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Assign Schedule</button>
    </div>
    <p id="assignStudentsError" class="hidden mt-2 text-sm text-red-600"></p>
  </div>
  
</div>

<!-- Confirm Reassignment Modal (students) -->
<div id="confirmStudentsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-lg font-bold text-gray-800">Confirm Reassignment</h3>
      <button onclick="hideConfirmStudents()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div id="confirmStudentsBody" class="text-sm text-gray-700 space-y-2"></div>
    <div class="flex gap-3 pt-4">
      <button onclick="hideConfirmStudents()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
      <button onclick="confirmStudentsProceed()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Confirm</button>
    </div>
  </div>
</div>

<!-- Remove Student Modal -->
<div id="removeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Remove Student</h3>
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span id="studentName" class="font-medium"></span> from this schedule?</p>
            <form id="removeForm" method="POST">
                <input type="hidden" name="action" value="remove_student">
                <input type="hidden" name="student_id" id="removeStudentId">
                <div class="flex gap-3">
                    <button type="button" onclick="hideRemoveModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">
                        Remove
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- Assign Students Modal Logic ---
const STUD_SCHEDULE_ID = <?= (int)$schedule_id ?>;
let sSelected = [];
let sQuery = '';
let sLimit = 15;
let sOffset = 0;
let sHasMore = false;
let sLoading = false;
const sIndex = {}; // id -> { name, hasCurrent }

function showAssignStudentsModal(){
  const modal = document.getElementById('assignStudentsModal');
  sSelected = []; sQuery=''; sOffset=0; updateAssignStudentsCount();
  const list = document.getElementById('assignStudentsList'); if(list) list.innerHTML = '<p class="text-gray-500 text-center py-4">Loading students...</p>';
  modal.classList.remove('hidden');
  loadStudentsPickPage(false);
  document.getElementById('assignStudentSearch').addEventListener('input', onStudentSearchChange);
  list.addEventListener('scroll', onStudentsPickScroll);
}
function hideAssignStudentsModal(){
  const modal = document.getElementById('assignStudentsModal');
  modal.classList.add('hidden');
  const list = document.getElementById('assignStudentsList');
  document.getElementById('assignStudentSearch').removeEventListener('input', onStudentSearchChange);
  if(list) list.removeEventListener('scroll', onStudentsPickScroll);
}

function onStudentSearchChange(){ sQuery = this.value.trim(); sOffset=0; loadStudentsPickPage(false); }

function loadStudentsPickPage(append){
  if(sLoading) return; sLoading=true;
  const params = new URLSearchParams();
  if(sQuery==='') params.set('all','1'); else params.set('query', sQuery);
  params.set('limit', sLimit); params.set('offset', sOffset);
  // Exclude those already in this schedule
  params.set('exclude_schedule_id', STUD_SCHEDULE_ID);
  fetch('SearchStudent.php?' + params.toString())
    .then(r=>r.json())
    .then(d=>{
      const list = d.students || [];
      renderStudentsPick(list, !!append);
      sHasMore = !!d.has_more;
    })
    .catch(()=>{ document.getElementById('assignStudentsList').innerHTML = '<p class="text-red-600 text-center py-4">Failed to load students</p>'; })
    .finally(()=>{ sLoading=false; });
}

function renderStudentsPick(items, append){
  const c = document.getElementById('assignStudentsList');
  if(!append){ c.innerHTML = ''; }
  if(items.length===0 && !append){ c.innerHTML = '<p class="text-gray-500 text-center py-4">No students found</p>'; return; }
  let html='';
  items.forEach(st=>{
    const id = st.id_number; const name = st.full_name || (st.first_name + ' ' + st.last_name);
    const currentName = st.current_section || st.class_schedule;
    const hasCurrent = !!currentName; sIndex[id] = { name, hasCurrent };
    const checked = sSelected.includes(id) ? 'checked' : '';
    const info = hasCurrent ? `<span class=\"text-orange-600 font-medium\">Current: ${currentName}</span>` : '<span class=\"text-green-600\">Available</span>';
    html += `
      <div class=\"flex items-center p-3 border-b hover:bg-gray-50 cursor-pointer select-none\" onclick=\"toggleStudentPick('${id}', ${hasCurrent})\">
        <input id=\"stud_${id}\" type=\"checkbox\" ${checked} onchange=\"toggleStudentPick('${id}', ${hasCurrent})\" class=\"mr-3 pointer-events-none\">
        <div class=\"flex-1\">
          <div class=\"font-medium\">${name}</div>
          <div class=\"text-sm text-gray-600\">ID: ${id} • ${st.grade_level || st.year_section || 'N/A'}</div>
          <div class=\"text-sm\">${info}</div>
        </div>
      </div>`;
  });
  c.insertAdjacentHTML('beforeend', html);
}

function onStudentsPickScroll(){
  const el = document.getElementById('assignStudentsList');
  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 20;
  if(nearBottom && sHasMore && !sLoading){ sOffset += sLimit; loadStudentsPickPage(true); }
}

function toggleStudentPick(id, hasCurrent){
  const idx = sSelected.indexOf(id);
  if(idx>-1){ sSelected.splice(idx,1); }
  else { sSelected.push(id); }
  updateAssignStudentsCount();
  const cb = document.getElementById('stud_'+id); if (cb) cb.checked = sSelected.includes(id);
}

function updateAssignStudentsCount(){ document.getElementById('assignStudentsSelected').textContent = sSelected.length; }

let pendingStud = null;
function assignStudentsProceed(){
  const err = document.getElementById('assignStudentsError'); err.classList.add('hidden'); err.textContent='';
  if(sSelected.length===0){ err.textContent='Please select at least one student'; err.classList.remove('hidden'); return; }
  // Build confirmation view
  try{
    const items = sSelected.map(id=>{ const m = sIndex[id]||{name:id,hasCurrent:false}; return {id, name:m.name, hasCurrent:!!m.hasCurrent}; });
    const movingCount = items.filter(i=>i.hasCurrent).length;
    pendingStud = { ids: sSelected.slice() };
    showConfirmStudents(items, items.length, movingCount);
    return;
  }catch(e){}
  doAssignStudents(sSelected);
}

function showConfirmStudents(items, total, moving){
  const body = document.getElementById('confirmStudentsBody');
  const listNames = items.map(i=>`<li>${i.name}</li>`).join('');
  const currentNames = items.filter(i=>i.hasCurrent).map(i=>i.name);
  const curList = currentNames.length ? `<ul class=\"list-disc ml-5 space-y-0.5\">${currentNames.map(n=>`<li>${n}</li>`).join('')}</ul>` : 'None';
  body.innerHTML = `
    <p>You are about to move <strong>${total}</strong> student(s) to the selected schedule.</p>
    <div class=\"mt-2 space-y-3\">
      <div>
        <div class=\"font-medium mb-1\">Selected students (${items.length}):</div>
        <ul class=\"list-disc ml-5 space-y-0.5\">${listNames}</ul>
      </div>
      <div>
        <div class=\"font-medium mb-1\">Have current schedule (${currentNames.length}):</div>
        ${curList}
      </div>
    </div>`;
  document.getElementById('confirmStudentsModal').classList.remove('hidden');
}
function hideConfirmStudents(){ document.getElementById('confirmStudentsModal').classList.add('hidden'); }
function confirmStudentsProceed(){ const p=pendingStud; hideConfirmStudents(); if(!p) return; doAssignStudents(p.ids); }

function doAssignStudents(ids){
  const fd = new FormData();
  fd.append('action','assign_schedule');
  fd.append('schedule_id', STUD_SCHEDULE_ID);
  ids.forEach(id=>fd.append('student_ids[]', id));
  fetch('ManageSchedule.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(d=>{ if(d.success){ try{ sessionStorage.setItem('schedule_notice', JSON.stringify({ type: 'success', message: d.message })); }catch(e){} hideAssignStudentsModal(); window.location.reload(); } else { showErrorBanner(d.message||'Assign failed.'); const err=document.getElementById('assignStudentsError'); err.textContent=d.message||'Assign failed.'; err.classList.remove('hidden'); } })
    .catch(()=>{ showErrorBanner('Failed to assign students. Please try again.'); const err=document.getElementById('assignStudentsError'); err.textContent='Failed to assign students. Please try again.'; err.classList.remove('hidden'); });
}

function removeStudent(studentId, studentName) {
    document.getElementById('removeStudentId').value = studentId;
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('removeModal').classList.remove('hidden');
}

function hideRemoveModal() {
    document.getElementById('removeModal').classList.add('hidden');
}

// Toast/Banner Notifications
function showBanner(message, type='success', timeout=3000){
  let box = document.createElement('div');
  box.className = `fixed top-4 right-4 z-[70] px-4 py-2 rounded-lg shadow text-white ${type==='success' ? 'bg-green-600' : 'bg-red-600'}`;
  box.textContent = message;
  document.body.appendChild(box);
  setTimeout(()=>{ box.classList.add('opacity-0'); box.style.transition='opacity .3s'; setTimeout(()=>box.remove(),300); }, timeout);
}
function showSuccessBanner(msg){ showBanner(msg, 'success'); }
function showErrorBanner(msg){ showBanner(msg, 'error'); }

document.addEventListener('DOMContentLoaded', ()=>{
  try{
    const raw = sessionStorage.getItem('schedule_notice');
    if(raw){ const {type, message} = JSON.parse(raw); showBanner(message || 'Action completed', type || 'success'); sessionStorage.removeItem('schedule_notice'); }
  }catch(e){}
  // Read URL notice from remove redirect
  try{
    const url = new URL(window.location.href);
    const notice = url.searchParams.get('notice');
    const msg = url.searchParams.get('msg');
    if(notice && msg){ showBanner(decodeURIComponent(msg), notice==='success' ? 'success' : 'error'); url.searchParams.delete('notice'); url.searchParams.delete('msg'); history.replaceState(null,'',url.toString()); }
  }catch(e){}
});

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const studentName = row.querySelector('td:nth-child(1) .text-sm').textContent.toLowerCase();
        const idNumber = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const program = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const yearSection = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
        
        const isVisible = studentName.includes(searchTerm) || 
                         idNumber.includes(searchTerm) || 
                         program.includes(searchTerm) ||
                         yearSection.includes(searchTerm);
        
        if (isVisible) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update count
    document.getElementById('totalCount').textContent = visibleCount;
});
</script>

</body>
</html>
