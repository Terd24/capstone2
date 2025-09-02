<?php
session_start();
if (!isset($_SESSION['id_number'])) {
    header("Location: index.php");
    exit();
}

include 'db_conn.php';

$id_number = $_SESSION['id_number'];

$selected_term = isset($_GET['term']) ? $_GET['term'] : null;

// Fetch all available terms for the dropdown
$term_query = $conn->prepare("SELECT DISTINCT school_year_term FROM grades_record WHERE id_number = ? ORDER BY school_year_term DESC");
$term_query->bind_param("s", $id_number);
$term_query->execute();
$term_result = $term_query->get_result();

$terms = [];
while ($row = $term_result->fetch_assoc()) {
    $terms[] = $row['school_year_term'];
}

if (!$selected_term && count($terms) > 0) {
    $selected_term = $terms[0];
}

$stmt = $conn->prepare("SELECT subject, teacher_name, prelim, midterm, pre_finals, finals FROM grades_record WHERE id_number = ? AND school_year_term = ?");
$stmt->bind_param("ss", $id_number, $selected_term);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Grades</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header -->
  <header class="bg-blue-600 text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex items-center space-x-4">
        <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
          <h1 class="text-xl font-bold">Student Grades</h1>
          <p class="text-blue-200 text-sm">Academic Performance Overview</p>
        </div>
      </div>
    </div>
  </header>

  <!-- Term Selection -->
  <div class="container mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <form method="get" class="flex flex-col md:flex-row md:items-center gap-4">
        <label class="font-semibold text-gray-700" for="term">School Year & Term:</label>
        <select name="term" id="term" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
          <?php if (empty($terms)): ?>
            <option value="">No grades available</option>
          <?php else: ?>
            <?php foreach ($terms as $term): ?>
              <option value="<?= htmlspecialchars($term) ?>" <?= $term == $selected_term ? 'selected' : '' ?>>
                <?= htmlspecialchars($term) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </form>
    </div>

    <!-- Grades Display -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php if (count($grades) > 0): ?>
        <?php foreach ($grades as $grade): ?>
        <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($grade['subject']) ?></h3>
            <p class="text-sm text-gray-600">Teacher: <?= htmlspecialchars($grade['teacher_name']) ?></p>
          </div>
          
          <div class="border-t pt-4">
            <div class="grid grid-cols-4 gap-2 mb-3">
              <div class="text-center">
                <div class="text-xs font-medium text-gray-500 mb-1">PRELIM</div>
                <div class="bg-gray-50 rounded-lg py-2 px-1">
                  <span class="text-lg font-bold text-black"><?= $grade['prelim'] ?? '-' ?></span>
                </div>
              </div>
              <div class="text-center">
                <div class="text-xs font-medium text-gray-500 mb-1">MIDTERM</div>
                <div class="bg-gray-50 rounded-lg py-2 px-1">
                  <span class="text-lg font-bold text-black"><?= $grade['midterm'] ?? '-' ?></span>
                </div>
              </div>
              <div class="text-center">
                <div class="text-xs font-medium text-gray-500 mb-1">PRE-FINALS</div>
                <div class="bg-gray-50 rounded-lg py-2 px-1">
                  <span class="text-lg font-bold text-black"><?= $grade['pre_finals'] ?? '-' ?></span>
                </div>
              </div>
              <div class="text-center">
                <div class="text-xs font-medium text-gray-500 mb-1">FINALS</div>
                <div class="bg-gray-50 rounded-lg py-2 px-1">
                  <span class="text-lg font-bold text-black"><?= $grade['finals'] ?? '-' ?></span>
                </div>
              </div>
            </div>
            
            <?php 
            $total_grades = 0;
            $grade_count = 0;
            foreach (['prelim', 'midterm', 'pre_finals', 'finals'] as $period) {
              if (!empty($grade[$period]) && is_numeric($grade[$period])) {
                $total_grades += $grade[$period];
                $grade_count++;
              }
            }
            $average = $grade_count > 0 ? round($total_grades / $grade_count, 2) : 0;
            ?>
            
            <?php if ($grade_count > 0): ?>
            <div class="mt-4 pt-3 border-t">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Current Average:</span>
                <span class="text-xl font-bold <?= $average >= 75 ? 'text-green-600' : 'text-red-600' ?>">
                  <?= $average ?>%
                </span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                <?= $average >= 75 ? 'Passing' : 'Needs Improvement' ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-span-2 bg-white rounded-2xl shadow-lg p-8 text-center">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-600 mb-2">No Grades Available</h3>
          <p class="text-gray-500">No grades found for the selected term. Please check back later or contact your registrar.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
