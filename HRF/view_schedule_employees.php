<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login
if (!((isset($_SESSION['role']) && $_SESSION['role'] === 'hr') || isset($_SESSION['hr_name']))) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$schedule_name = isset($_GET['schedule_name']) ? $_GET['schedule_name'] : 'Unknown Schedule';

if ($schedule_id <= 0) {
    header("Location: ManageEmployeeSchedule.php");
    exit;
}

// Get schedule details
$schedule_stmt = $conn->prepare("SELECT * FROM employee_work_schedules WHERE id = ?");
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

// Handle removal
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_employee') {
    $employee_id = $_POST['employee_id'] ?? '';
    if ($employee_id !== '') {
        $rm = $conn->prepare("DELETE FROM employee_schedules WHERE employee_id = ? AND schedule_id = ?");
        $rm->bind_param("si", $employee_id, $schedule_id);
        if ($rm->execute()) {
            $notice = 'success';
            $msg = 'Removed employee from schedule successfully!';
        } else {
            $notice = 'error';
            $msg = 'Failed to remove employee from schedule.';
        }
    }
    header("Location: view_schedule_employees.php?schedule_id=".$schedule_id."&schedule_name=".urlencode($schedule_name)."&notice=".$notice."&msg=".urlencode($msg));
    exit;
}

// Get assigned employees
$employees_stmt = $conn->prepare("SELECT e.id_number,
                                         CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                                         ea.role,
                                         es.assigned_at
                                  FROM employee_schedules es
                                  JOIN employees e ON e.id_number = es.employee_id
                                  LEFT JOIN employee_accounts ea ON ea.employee_id = e.id_number
                                  WHERE es.schedule_id = ?
                                  ORDER BY e.first_name, e.last_name");
$employees_stmt->bind_param("i", $schedule_id);
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedule Employees - <?= htmlspecialchars($schedule_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<header class="bg-[#0B2C62] text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <button onclick="window.location.href='ManageEmployeeSchedule.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <div>
          <h2 class="text-lg font-semibold"><?= htmlspecialchars($schedule_name) ?> Employees</h2>
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
          <p class="text-blue-200 text-sm">Employee Schedule Management</p>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="container mx-auto px-6 py-8">
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

  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-800">Assigned Employees</h2>
      <div class="flex items-center gap-3">
        <div class="text-sm text-gray-600 mr-2">Total: <span id="totalCount"><?= $employees_result->num_rows ?></span> employee(s)</div>
        <button type="button" onclick="showAssignModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Add Employees</button>
      </div>
    </div>

    <div class="mb-6">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <input type="text" id="searchInput" placeholder="Search employees by name, ID number, or role..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
      </div>
    </div>

    <?php if ($employees_result->num_rows > 0): ?>
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php while ($emp = $employees_result->fetch_assoc()): ?>
              <tr class="bg-blue-50 hover:bg-blue-100 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center text-white">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($emp['full_name']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($emp['id_number']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($emp['role'] ?? 'N/A') ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <?php if (!empty($emp['assigned_at'])): ?>
                    <?= date('M j, Y g:i A', strtotime($emp['assigned_at'])) ?>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button onclick="removeEmployee('<?= htmlspecialchars($emp['id_number']) ?>', '<?= htmlspecialchars($emp['full_name']) ?>')" class="text-red-600 hover:text-red-900">Remove</button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-8">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        <p class="text-gray-500 text-lg">No employees assigned to this schedule</p>
        <p class="text-gray-400 text-sm">Use "Assign Schedule to Employees" to add employees</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Assign Employees Modal (centered) -->
<div id="assignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-bold text-gray-800">Assign Schedule to Employees</h3>
      <button onclick="hideAssignModal()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div class="mb-2 text-sm text-gray-600">Schedule: <span class="font-medium"><?= htmlspecialchars($schedule_name) ?></span></div>
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
      <input type="text" id="assignSearch" placeholder="Search employee by name or ID..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" />
    </div>
    <div id="assignList" class="border rounded-lg h-72 overflow-y-auto">
      <p class="text-gray-500 text-center py-4">Loading employees...</p>
    </div>
    <div class="mb-4 text-sm text-gray-600">Selected: <span id="assignSelected">0</span> employee(s)</div>
    <div class="flex gap-3">
      <button type="button" onclick="hideAssignModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
      <button type="button" onclick="assignFromModal()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Assign Schedule</button>
    </div>
    <p id="assignInlineError" class="hidden mt-2 text-sm text-red-600"></p>
  </div>
</div>

<!-- Confirm Reassignment Modal -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-lg font-bold text-gray-800">Confirm Reassignment</h3>
      <button onclick="hideConfirm()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div id="confirmBody" class="text-sm text-gray-700 space-y-2"></div>
    <div class="flex gap-3 pt-4">
      <button onclick="hideConfirm()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
      <button onclick="confirmAssignProceed()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Confirm</button>
    </div>
  </div>
</div>

<!-- Remove Employee Modal -->
<div id="removeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
    <div class="text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Remove Employee</h3>
      <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span id="employeeName" class="font-medium"></span> from this schedule?</p>
      <form id="removeForm" method="POST">
        <input type="hidden" name="action" value="remove_employee">
        <input type="hidden" name="employee_id" id="removeEmployeeId">
        <div class="flex gap-3">
          <button type="button" onclick="hideRemoveModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">Cancel</button>
          <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">Remove</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// --- Center Assign Modal Logic ---
const SCHEDULE_ID = <?= (int)$schedule_id ?>;
let aSelected = [];
let aQuery = '';
let aLimit = 15;
let aOffset = 0;
let aHasMore = false;
let aLoading = false;
const aIndex = {}; // id -> {name, hasCurrent}

function showAssignModal(){
  document.getElementById('assignModal').classList.remove('hidden');
  aSelected = []; aQuery=''; aOffset=0; updateAssignSelected();
  loadAssignPage(false);
  const listEl = document.getElementById('assignList');
  listEl.addEventListener('scroll', onAssignScroll);
}
function hideAssignModal(){
  document.getElementById('assignModal').classList.add('hidden');
  const listEl = document.getElementById('assignList');
  listEl.removeEventListener('scroll', onAssignScroll);
}

document.getElementById('assignSearch').addEventListener('input', ()=>{ aQuery=document.getElementById('assignSearch').value.trim(); aOffset=0; loadAssignPage(false); });

function loadAssignPage(append){
  if(aLoading) return; aLoading = true;
  const params = new URLSearchParams();
  if(aQuery==='') params.set('all','1'); else params.set('query', aQuery);
  params.set('limit', aLimit); params.set('offset', aOffset);
  // Exclude those already in this schedule
  params.set('exclude_schedule_id', SCHEDULE_ID);
  fetch('SearchEmployee.php?' + params.toString())
    .then(r=>r.json())
    .then(d=>{
      const list = d.employees || [];
      renderAssignList(list, !!append);
      aHasMore = !!d.has_more;
    })
    .catch(()=>{ document.getElementById('assignList').innerHTML = '<p class="text-red-600 text-center py-4">Failed to load employees</p>'; })
    .finally(()=>{ aLoading=false; });
}

function renderAssignList(items, append){
  const c = document.getElementById('assignList');
  if(!append){ c.innerHTML = ''; }
  if(items.length===0 && !append){ c.innerHTML = '<p class="text-gray-500 text-center py-4">No employees found</p>'; return; }
  let html='';
  items.forEach(emp=>{
    const id = emp.id_number; const name = emp.full_name || (emp.first_name + ' ' + emp.last_name);
    const currentName = emp.current_schedule || emp.schedule_text || emp.current_section;
    const hasCurrent = !!currentName;
    aIndex[id] = { name, hasCurrent };
    const checked = aSelected.includes(id) ? 'checked' : '';
    const info = hasCurrent ? `<span class=\"text-orange-600 font-medium\">Current: ${currentName}</span>` : '<span class=\"text-green-600\">Available</span>';
    html += `
      <div class=\"flex items-center p-3 border-b hover:bg-gray-50 cursor-pointer select-none\" onclick=\"toggleAssign('${id}', ${hasCurrent})\">
        <input id=\"emp_${id}\" type=\"checkbox\" ${checked} onchange=\"toggleAssign('${id}', ${hasCurrent})\" class=\"mr-3 pointer-events-none\">
        <div class=\"flex-1\">
          <div class=\"font-medium\">${name}</div>
          <div class=\"text-sm text-gray-600\">ID: ${id} • ${emp.position || emp.department || 'N/A'}</div>
          <div class=\"text-sm\">${info}</div>
        </div>
      </div>`;
  });
  c.insertAdjacentHTML('beforeend', html);
}

function onAssignScroll(){
  const el = document.getElementById('assignList');
  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 20;
  if(nearBottom && aHasMore && !aLoading){ aOffset += aLimit; loadAssignPage(true); }
}

function toggleAssign(id, hasCurrent){
  const idx = aSelected.indexOf(id);
  if(idx>-1){ aSelected.splice(idx,1); }
  else {
    aSelected.push(id);
  }
  updateAssignSelected();
  const cb = document.getElementById('emp_'+id); if(cb){ cb.checked = aSelected.includes(id); }
}

function updateAssignSelected(){ document.getElementById('assignSelected').textContent = aSelected.length; }

let pending = null;
function assignFromModal(){
  const err = document.getElementById('assignInlineError'); err.classList.add('hidden'); err.textContent='';
  if(aSelected.length===0){ err.textContent='Please select at least one employee'; err.classList.remove('hidden'); return; }
  // Build confirmation body
  try{
    const items = aSelected.map(id=>{ const m = aIndex[id]||{name:id,hasCurrent:false}; return {id, name:m.name, hasCurrent:!!m.hasCurrent}; });
    const movingCount = items.filter(i=>i.hasCurrent).length;
    pending = { ids: aSelected.slice() };
    showConfirm(items, items.length, movingCount);
    return;
  }catch(e){}
  doAssign(aSelected);
}

function showConfirm(items, total, moving){
  const body = document.getElementById('confirmBody');
  const listNames = items.map(i=>`<li>${i.name}</li>`).join('');
  const currentNames = items.filter(i=>i.hasCurrent).map(i=>i.name);
  const curList = currentNames.length ? `<ul class=\"list-disc ml-5 space-y-0.5\">${currentNames.map(n=>`<li>${n}</li>`).join('')}</ul>` : 'None';
  body.innerHTML = `
    <p>You are about to move <strong>${total}</strong> employee(s) to the selected schedule.</p>
    <div class=\"mt-2 space-y-3\">
      <div>
        <div class=\"font-medium mb-1\">Selected employees (${items.length}):</div>
        <ul class=\"list-disc ml-5 space-y-0.5\">${listNames}</ul>
      </div>
      <div>
        <div class=\"font-medium mb-1\">Have current schedule (${currentNames.length}):</div>
        ${curList}
      </div>
    </div>`;
  document.getElementById('confirmModal').classList.remove('hidden');
}
function hideConfirm(){ document.getElementById('confirmModal').classList.add('hidden'); }
function confirmAssignProceed(){ const p=pending; hideConfirm(); if(!p) return; doAssign(p.ids); }

function doAssign(ids){
  const fd = new FormData();
  fd.append('action','assign_schedule');
  fd.append('schedule_id', SCHEDULE_ID);
  ids.forEach(id=>fd.append('employee_ids[]', id));
  fetch('ManageEmployeeSchedule.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(d=>{ if(d.success){ try{ sessionStorage.setItem('schedule_notice', JSON.stringify({ type: 'success', message: d.message })); }catch(e){} hideAssignModal(); window.location.reload(); } else { showErrorBanner(d.message||'Assign failed.'); const err=document.getElementById('assignInlineError'); err.textContent=d.message||'Assign failed.'; err.classList.remove('hidden'); } })
    .catch(()=>{ showErrorBanner('Failed to assign employees. Please try again.'); const err=document.getElementById('assignInlineError'); err.textContent='Failed to assign employees. Please try again.'; err.classList.remove('hidden'); });
}
function removeEmployee(empId, empName){
  document.getElementById('removeEmployeeId').value = empId;
  document.getElementById('employeeName').textContent = empName;
  document.getElementById('removeModal').classList.remove('hidden');
}
function hideRemoveModal(){ document.getElementById('removeModal').classList.add('hidden'); }

// Client-side search
const searchInput = document.getElementById('searchInput');
if(searchInput){
  searchInput.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    let visible = 0;
    rows.forEach(row=>{
      const name = row.querySelector('td:nth-child(1) .text-sm').textContent.toLowerCase();
      const id = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
      const role = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
      const show = name.includes(term) || id.includes(term) || role.includes(term);
      row.style.display = show ? '' : 'none';
      if(show) visible++;
    });
    const total = document.getElementById('totalCount'); if(total) total.textContent = visible;
  });
}

// Toast/Banner Notifications (top-right)
function showBanner(message, type='success', timeout=3000){
  const box = document.createElement('div');
  box.className = 'fixed top-4 right-4 z-[70] px-4 py-2 rounded-lg shadow text-white ' + (type==='success' ? 'bg-green-600' : 'bg-red-600');
  box.textContent = message;
  document.body.appendChild(box);
  setTimeout(()=>{ box.style.transition='opacity .3s'; box.style.opacity='0'; setTimeout(()=>box.remove(), 300); }, timeout);
}
function showSuccessBanner(msg){ showBanner(msg, 'success'); }
function showErrorBanner(msg){ showBanner(msg, 'error'); }

document.addEventListener('DOMContentLoaded', ()=>{
  try{
    const raw = sessionStorage.getItem('schedule_notice');
    if(raw){ const data = JSON.parse(raw); showBanner(data.message || 'Action completed', data.type || 'success'); sessionStorage.removeItem('schedule_notice'); }
  }catch(e){}
  // Read URL notice (from remove redirects)
  try{
    const url = new URL(window.location.href);
    const notice = url.searchParams.get('notice');
    const msg = url.searchParams.get('msg');
    if(notice && msg){ showBanner(decodeURIComponent(msg), notice==='success' ? 'success' : 'error'); url.searchParams.delete('notice'); url.searchParams.delete('msg'); history.replaceState(null,'',url.toString()); }
  }catch(e){}
});
</script>

</body>
</html>
