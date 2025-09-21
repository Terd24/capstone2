<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
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
            $success_msg = "Employee removed from schedule successfully!";
        } else {
            $error_msg = "Failed to remove employee from schedule.";
        }
    }
    header("Location: view_schedule_employees.php?schedule_id=".$schedule_id."&schedule_name=".urlencode($schedule_name));
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
      <div class="text-sm text-gray-600">Total: <span id="totalCount"><?= $employees_result->num_rows ?></span> employee(s)</div>
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
</script>

</body>
</html>
