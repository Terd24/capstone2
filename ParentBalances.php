<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['parent_id']) || !isset($_SESSION['child_id'])) {
    header("Location: ParentLogin.html");
    exit();
}

$id_number = $_SESSION['child_id']; // Get student ID from session

// Get all available terms dynamically
$term_result = $conn->query("SELECT DISTINCT school_year_term FROM student_balances WHERE id_number = '$id_number' ORDER BY school_year_term DESC");

// Dropdown selected term
$selected_term = $_GET['term'] ?? '';
if (!$selected_term && $term_result->num_rows > 0) {
    $row = $term_result->fetch_assoc();
    $selected_term = $row['school_year_term'];
    $term_result->data_seek(0); // reset pointer
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Balances</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex justify-center min-h-screen p-4">
  <div class="bg-white shadow-lg rounded-lg max-w-3xl w-full p-6 space-y-6">
    
    <!-- Back -->
    <button onclick="history.back()" class="flex items-center space-x-2 text-gray-600 hover:text-gray-900">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      <span>Back</span>
    </button>

    <!-- Dropdown Form (Auto-generated from DB) -->
    <form method="get">
      <label for="termSelect" class="block mb-1 font-semibold text-gray-700">School Year & Term:</label>
      <select name="term" id="termSelect" class="border border-gray-300 rounded px-3 py-2 w-full max-w-xs" onchange="this.form.submit()">
        <?php while ($row = $term_result->fetch_assoc()): 
          $term_value = $row['school_year_term'];
          $is_selected = ($term_value == $selected_term) ? 'selected' : '';
        ?>
          <option value="<?= htmlspecialchars($term_value) ?>" <?= $is_selected ?>>
            <?= htmlspecialchars($term_value) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </form>

    <!-- Gross Assessment -->
    <section class="bg-gray-100 rounded p-4 space-y-2">
      <h2 class="font-semibold text-lg border-b border-gray-300 pb-1">Gross Assessment</h2>
      <div class="flex justify-between">
        <span>Tuition Fee</span>
        <span>₱<?= number_format($tuition_fee, 2) ?></span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm text-gray-600">(Registration Fee, Other School Fees)</span>
        <span>₱<?= number_format($other_fees, 2) ?></span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm text-gray-600">(Student Related Activities, ...)</span>
        <span>₱<?= number_format($student_fees, 2) ?></span>
      </div>
      <div class="flex justify-between border-t border-gray-400 font-semibold pt-1 text-red-700">
        <span>Gross Assessment</span>
        <span>₱<?= number_format($gross_total, 2) ?></span>
      </div>
    </section>

    <!-- Payment Schedule -->
<section>
  <h2 class="font-semibold text-lg mb-2 border-b border-gray-300 pb-1">Payment Schedule</h2>
  <?php if ($sched_result->num_rows > 0): ?>
  <ul class="bg-gray-100 rounded p-4 space-y-2 w-full overflow-x-auto">
    <?php while ($sched = $sched_result->fetch_assoc()):
      $sched_total += $sched['amount'];
    ?>
      <li class="flex flex-wrap md:flex-nowrap justify-between gap-2">
        <span class="min-w-[120px]"><?= date('d M, Y', strtotime($sched['due_date'])) ?></span>
        <?php if (!empty($sched['description'])): ?>
          <span class="text-sm text-gray-600 flex-1 overflow-x-auto whitespace-nowrap">
            (<?= htmlspecialchars($sched['description']) ?>)
          </span>
        <?php else: ?>
          <span class="flex-1"></span>
        <?php endif; ?>
        <span class="min-w-[100px] text-right">₱<?= number_format($sched['amount'], 2) ?></span>
      </li>
    <?php endwhile; ?>
    <li class="flex justify-between font-semibold text-gray-700 border-t border-gray-400 pt-2">
      <span>Total</span>
      <span>₱<?= number_format($sched_total, 2) ?></span>
    </li>
  </ul>
  <?php else: ?>
    <p class="text-gray-500 italic">No payment schedule available for this term.</p>
  <?php endif; ?>
</section>


    <!-- Payment Section -->
    <section>
      <h2 class="font-semibold text-lg mb-4 border-b border-gray-300 pb-1">
        Payments and Adjustments for <?= htmlspecialchars($selected_term) ?>
      </h2>

      <?php if ($pay_result->num_rows > 0): ?>
        <?php while ($row = $pay_result->fetch_assoc()):
          $total = $row['misc_fee'] + $row['other_school_fee'] + $row['tuition_fee'];
        ?>
          <div class="mb-6 p-4 bg-gray-50 rounded shadow-inner border border-gray-200">
            <div class="mb-2 font-semibold">
              <?= date('d M, Y', strtotime($row['date'])) ?> | OR #<?= htmlspecialchars($row['or_number']) ?>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200">
              <span>Miscellaneous Fee</span>
              <span>₱<?= number_format($row['misc_fee'], 2) ?></span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200">
              <span>Other School Fees</span>
              <span>₱<?= number_format($row['other_school_fee'], 2) ?></span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200">
              <span>Tuition Fees</span>
              <span>₱<?= number_format($row['tuition_fee'], 2) ?></span>
            </div>
            <div class="flex justify-between pt-2 font-semibold text-red-700 text-right">
              <span>Total</span>
              <span>₱<?= number_format($total, 2) ?></span>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-gray-500 italic">No payment records found for this term.</p>
      <?php endif; ?>
    </section>

  </div>
</body>
</html>
