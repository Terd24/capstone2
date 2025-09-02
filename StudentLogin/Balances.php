<?php
session_start();
include 'db_conn.php';

$id_number = $_SESSION['id_number'];

$term_result = $conn->query("SELECT DISTINCT school_year_term FROM student_balances WHERE id_number = '$id_number' ORDER BY school_year_term DESC");

// Dropdown selected term
$selected_term = $_GET['term'] ?? '';
if (!$selected_term && $term_result->num_rows > 0) {
    $row = $term_result->fetch_assoc();
    $selected_term = $row['school_year_term'];
    $term_result->data_seek(0);
}

$school_year_term = $selected_term;

// Get balance
$bal_query = "SELECT * FROM student_balances 
              WHERE id_number = ? AND school_year_term = ? LIMIT 1";
$bal_stmt = $conn->prepare($bal_query);
$bal_stmt->bind_param("ss", $id_number, $school_year_term);
$bal_stmt->execute();
$bal_result = $bal_stmt->get_result();
$bal = $bal_result->fetch_assoc();

$tuition_fee = $bal['tuition_fee'] ?? 0;
$other_fees = $bal['other_fees'] ?? 0;
$student_fees = $bal['student_fees'] ?? 0;
$gross_total = $tuition_fee + $other_fees + $student_fees;

// Get payments
$pay_query = "SELECT * FROM student_payments 
              WHERE id_number = ? AND school_year_term = ? ORDER BY date ASC";
$pay_stmt = $conn->prepare($pay_query);
$pay_stmt->bind_param("ss", $id_number, $school_year_term);
$pay_stmt->execute();
$pay_result = $pay_stmt->get_result();

// Get payment schedule
$sched_query = "SELECT * FROM payment_schedule 
                WHERE id_number = ? AND school_year_term = ? ORDER BY due_date ASC";
$sched_stmt = $conn->prepare($sched_query);
$sched_stmt->bind_param("ss", $id_number, $school_year_term);
$sched_stmt->execute();
$sched_result = $sched_stmt->get_result();
$sched_total = 0;
?>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

<!-- Header -->
<header class="bg-blue-600 text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div class="text-left">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= $_SESSION['student_name'] ?></p>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div class="text-right">
          <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
          <p class="text-blue-200 text-sm">Account Balance</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
  <!-- Term Selection -->
  <div class="bg-white rounded-2xl card-shadow p-6 mb-8">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800">Academic Term</h2>
      <form method="GET" class="flex items-center space-x-3">
        <select name="term" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <?php while ($term_row = $term_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($term_row['school_year_term']) ?>" 
                    <?= $term_row['school_year_term'] === $selected_term ? 'selected' : '' ?>>
              <?= htmlspecialchars($term_row['school_year_term']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
    </div>
  </div>

  <!-- Balance Summary -->
  <div class="bg-white rounded-2xl card-shadow p-6 mb-8">
    <div class="flex items-center mb-6">
      <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800">Balance Summary</h2>
    </div>
    
    <div class="space-y-4">
      <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
        <span class="font-medium text-gray-700">Tuition Fee</span>
        <span class="font-semibold text-gray-900">₱<?= number_format($tuition_fee, 2) ?></span>
      </div>
      <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
        <span class="font-medium text-gray-700">Other School Fees</span>
        <span class="font-semibold text-gray-900">₱<?= number_format($other_fees, 2) ?></span>
      </div>
      <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
        <span class="font-medium text-gray-700">Student Activity Fees</span>
        <span class="font-semibold text-gray-900">₱<?= number_format($student_fees, 2) ?></span>
      </div>
      <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg border-2 border-red-200">
        <span class="font-bold text-red-700">Total Outstanding Balance</span>
        <span class="font-bold text-red-700 text-lg">₱<?= number_format($gross_total, 2) ?></span>
      </div>
    </div>
  </div>

  <!-- Payment Schedule -->
  <div class="bg-white rounded-2xl card-shadow p-6 mb-8">
    <div class="flex items-center mb-6">
      <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800">Payment Schedule</h2>
    </div>
    
    <?php if ($sched_result->num_rows > 0): ?>
      <div class="space-y-3">
        <?php while ($sched = $sched_result->fetch_assoc()):
          $sched_total += $sched['amount'];
        ?>
          <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900"><?= date('F j, Y', strtotime($sched['due_date'])) ?></p>
              <?php if (!empty($sched['description'])): ?>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($sched['description']) ?></p>
              <?php endif; ?>
            </div>
            <span class="font-semibold text-gray-900">₱<?= number_format($sched['amount'], 2) ?></span>
          </div>
        <?php endwhile; ?>
        <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
          <span class="font-bold text-blue-700">Total Scheduled</span>
          <span class="font-bold text-blue-700 text-lg">₱<?= number_format($sched_total, 2) ?></span>
        </div>
      </div>
    <?php else: ?>
      <div class="text-center py-8">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <p class="text-gray-500">No payment schedule available for this term</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payment History -->
  <div class="bg-white rounded-2xl card-shadow p-6">
    <div class="flex items-center mb-6">
      <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800">Payment History</h2>
    </div>

    <?php if ($pay_result->num_rows > 0): ?>
      <div class="space-y-4">
        <?php while ($row = $pay_result->fetch_assoc()):
          $total = $row['misc_fee'] + $row['other_school_fee'] + $row['tuition_fee'];
        ?>
          <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-4">
              <div>
                <p class="font-semibold text-gray-900"><?= date('F j, Y', strtotime($row['date'])) ?></p>
                <p class="text-sm text-gray-600">OR #<?= htmlspecialchars($row['or_number']) ?></p>
              </div>
              <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Paid</span>
            </div>
            
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Miscellaneous Fee</span>
                <span class="text-gray-900">₱<?= number_format($row['misc_fee'], 2) ?></span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Other School Fees</span>
                <span class="text-gray-900">₱<?= number_format($row['other_school_fee'], 2) ?></span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tuition Fees</span>
                <span class="text-gray-900">₱<?= number_format($row['tuition_fee'], 2) ?></span>
              </div>
              <div class="flex justify-between pt-2 border-t border-gray-200">
                <span class="font-semibold text-gray-900">Total Payment</span>
                <span class="font-semibold text-green-600">₱<?= number_format($total, 2) ?></span>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-8">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <p class="text-gray-500">No payment records found for this term</p>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>