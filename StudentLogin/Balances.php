<?php
session_start();
include 'db_conn.php';

$id_number = $_SESSION['id_number'];

$term_result = $conn->query("SELECT DISTINCT school_year_term FROM student_fee_items WHERE id_number = '$id_number' ORDER BY school_year_term DESC");

// Dropdown selected term
$selected_term = $_GET['term'] ?? '';
if (!$selected_term && $term_result->num_rows > 0) {
    $row = $term_result->fetch_assoc();
    $selected_term = $row['school_year_term'];
    $term_result->data_seek(0);
}

$school_year_term = $selected_term;

// Get fee items and calculate totals
$fee_query = "SELECT fee_type, amount, paid FROM student_fee_items 
              WHERE id_number = ? AND school_year_term = ?";
$fee_stmt = $conn->prepare($fee_query);
$fee_stmt->bind_param("ss", $id_number, $school_year_term);
$fee_stmt->execute();
$fee_result = $fee_stmt->get_result();

$fee_items = [];
$gross_total = 0;
$total_paid = 0;
while ($fee = $fee_result->fetch_assoc()) {
    $fee_items[] = $fee;
    $gross_total += $fee['amount'];
    $total_paid += $fee['paid'];
}
$remaining_balance = $gross_total - $total_paid;

// Get payments
$pay_query = "SELECT fee_type, amount, or_number, date FROM student_payments 
              WHERE id_number = ? AND school_year_term = ? ORDER BY date DESC";
$pay_stmt = $conn->prepare($pay_query);
$pay_stmt->bind_param("ss", $id_number, $school_year_term);
$pay_stmt->execute();
$pay_result = $pay_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Balance - Cornerstone College Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

<!-- Header -->
<header class="bg-[#0B2C62] text-white shadow-lg">
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
<div class="max-w-4xl mx-auto px-6 py-8">
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
  <div class="bg-white rounded-lg card-shadow mb-8">
    <div class="bg-[#0B2C62] text-white px-4 py-3 rounded-t-lg flex items-center">
      <div class="w-6 h-6 bg-white rounded mr-2 flex items-center justify-center">
        <span class="text-[#0B2C62] font-bold text-sm">₱</span>
      </div>
      <h2 class="text-lg font-semibold">Balance Summary</h2>
    </div>

    <?php if (!empty($fee_items)): ?>
      <div class="p-4 space-y-4">
        <?php 
        $has_unpaid_fees = false;
        foreach ($fee_items as $fee): 
          $balance = $fee['amount'] - $fee['paid'];
          if ($balance > 0) $has_unpaid_fees = true;
        endforeach;
        
        if ($has_unpaid_fees): ?>
          <div class="border border-gray-200 rounded p-3">
            <div class="flex justify-between items-start mb-3">
              <div>
                <div class="font-medium text-gray-800">Outstanding Fees</div>
                <div class="text-sm text-gray-600">Fees requiring payment</div>
              </div>
            </div>
            
            <?php foreach ($fee_items as $fee): 
              $balance = $fee['amount'] - $fee['paid'];
              if ($balance > 0): ?>
                <div class="flex justify-between text-sm py-1">
                  <span class="text-gray-600"><?= htmlspecialchars($fee['fee_type']) ?></span>
                  <span class="text-gray-900">₱<?= number_format($balance, 2) ?></span>
                </div>
              <?php endif;
            endforeach; ?>
            
            <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
              <span class="font-semibold text-gray-800">Total Amount to Pay</span>
              <span class="font-semibold text-gray-900">₱<?= number_format($remaining_balance, 2) ?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="border border-gray-200 rounded p-3">
            <div class="text-center">
              <div class="font-medium text-gray-800">All Fees Paid</div>
              <div class="text-sm text-gray-600 mt-1">No outstanding balance for this term</div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="p-6 text-center">
        <p class="text-gray-500">No balance records found for this term</p>
      </div>
    <?php endif; ?>
  </div>


  <!-- Payment History -->
  <div class="bg-white rounded-lg card-shadow">
    <div class="bg-[#0B2C62] text-white px-4 py-3 rounded-t-lg flex items-center">
      <div class="w-6 h-6 bg-white rounded mr-2 flex items-center justify-center">
        <span class="text-[#0B2C62] font-bold text-sm">₱</span>
      </div>
      <h2 class="text-lg font-semibold">Payment History</h2>
    </div>

    <?php if ($pay_result->num_rows > 0): ?>
      <div class="p-4 space-y-4">
        <?php 
        $payment_records = [];
        while ($row = $pay_result->fetch_assoc()) {
          $payment_records[] = $row;
        }
        $total_records = count($payment_records);
        $initial_display = 3; // Show only 3 records initially
        
        for ($i = 0; $i < min($initial_display, $total_records); $i++): 
          $row = $payment_records[$i];
        ?>
          <div class="payment-record border border-gray-200 rounded p-3">
            <div class="flex justify-between items-start mb-3">
              <div>
                <div class="font-medium text-gray-800"><?= date('F j, Y', strtotime($row['date'])) ?></div>
                <div class="text-sm text-gray-600">OR #<?= htmlspecialchars($row['or_number']) ?></div>
              </div>
              <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium">Paid</span>
            </div>
            
            <div class="flex justify-between text-sm py-1">
              <span class="text-gray-600"><?= htmlspecialchars($row['fee_type']) ?></span>
              <span class="text-gray-900">₱<?= number_format($row['amount'], 2) ?></span>
            </div>
            
            <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
              <span class="font-semibold text-gray-800">Total Payment</span>
              <span class="font-semibold text-green-600">₱<?= number_format($row['amount'], 2) ?></span>
            </div>
          </div>
        <?php endfor; ?>
        
        <?php if ($total_records > $initial_display): ?>
          <!-- Hidden records -->
          <div id="hiddenRecords" style="display: none;">
            <?php for ($i = $initial_display; $i < $total_records; $i++): 
              $row = $payment_records[$i];
            ?>
              <div class="payment-record border border-gray-200 rounded p-3">
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <div class="font-medium text-gray-800"><?= date('F j, Y', strtotime($row['date'])) ?></div>
                    <div class="text-sm text-gray-600">OR #<?= htmlspecialchars($row['or_number']) ?></div>
                  </div>
                  <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium">Paid</span>
                </div>
                
                <div class="flex justify-between text-sm py-1">
                  <span class="text-gray-600"><?= htmlspecialchars($row['fee_type']) ?></span>
                  <span class="text-gray-900">₱<?= number_format($row['amount'], 2) ?></span>
                </div>
                
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-200">
                  <span class="font-semibold text-gray-800">Total Payment</span>
                  <span class="font-semibold text-green-600">₱<?= number_format($row['amount'], 2) ?></span>
                </div>
              </div>
            <?php endfor; ?>
          </div>
          
          <!-- View More/View Less buttons -->
          <div class="text-center pt-4 border-t border-gray-200">
            <button id="viewMoreBtn" onclick="togglePaymentHistory()" 
                    class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
              View More (<?= $total_records - $initial_display ?> more)
            </button>
            <button id="viewLessBtn" onclick="togglePaymentHistory()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors" 
                    style="display: none;">
              View Less
            </button>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="p-6 text-center">
        <p class="text-gray-500">No payment records found for this term</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function togglePaymentHistory() {
  const hiddenRecords = document.getElementById('hiddenRecords');
  const viewMoreBtn = document.getElementById('viewMoreBtn');
  const viewLessBtn = document.getElementById('viewLessBtn');
  
  if (hiddenRecords.style.display === 'none') {
    // Show hidden records
    hiddenRecords.style.display = 'block';
    viewMoreBtn.style.display = 'none';
    viewLessBtn.style.display = 'inline-block';
  } else {
    // Hide records
    hiddenRecords.style.display = 'none';
    viewMoreBtn.style.display = 'inline-block';
    viewLessBtn.style.display = 'none';
  }
}
</script>

</body>
</html>