<?php
session_start();
if (!isset($_SESSION['id_number'])) {
    header("Location: index.php");
    exit();
}

include 'db_conn.php';

$id_number = $_SESSION['id_number'];

// Handle selected term from dropdown (GET method)
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

// If no term selected, use the first available one (if exists)
if (!$selected_term && count($terms) > 0) {
    $selected_term = $terms[0];
}

// Fetch grades for the selected term including teacher_name
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
<body class="bg-gray-100 font-sans">

  <div class="bg-white p-4 flex items-center shadow-md">
    <button onclick="window.history.back()" class="text-2xl mr-4">←</button>
    <h1 class="text-xl font-semibold">Grades</h1>
  </div>

  <div class="bg-gray-100 px-6 py-4">
    <form method="get" class="flex flex-col md:flex-row md:items-center gap-3">
      <label class="font-semibold text-lg" for="term">School Year & Term:</label>
      <select name="term" id="term" class="mt-1 block w-full md:w-80 p-2 border rounded shadow" onchange="this.form.submit()">
        <?php foreach ($terms as $term): ?>
          <option value="<?= htmlspecialchars($term) ?>" <?= $term == $selected_term ? 'selected' : '' ?>>
            <?= htmlspecialchars($term) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (count($grades) > 0): ?>
      <?php foreach ($grades as $grade): ?>
      <div class="bg-white p-4 rounded shadow">
        <div class="mb-2">
          <p class="font-semibold"><?= htmlspecialchars($grade['teacher_name']) ?></p>
          <p class="text-sm text-gray-500"><?= htmlspecialchars($grade['subject']) ?></p>
        </div>
        <div class="border-t mt-2 pt-2">
          <div class="grid grid-cols-4 text-sm font-semibold bg-gray-100 p-2 rounded">
            <div>PRELIM</div>
            <div>MIDTERM</div>
            <div>PRE FINALS</div>
            <div>FINALS</div>
          </div>
          <div class="grid grid-cols-4 text-center mt-2 text-sm">
            <div><?= htmlspecialchars($grade['prelim']) ?></div>
            <div><?= htmlspecialchars($grade['midterm']) ?></div>
            <div><?= htmlspecialchars($grade['pre_finals']) ?></div>
            <div><?= htmlspecialchars($grade['finals']) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center text-gray-500 col-span-2">No grades found for the selected term.</p>
    <?php endif; ?>
  </div>

</body>
</html>
