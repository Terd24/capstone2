<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['id_number'])) {
    $_SESSION['id_number'] = '24'; 
}

$id_number = $_SESSION['id_number'];

$sql = "SELECT document_name, date_submitted, remarks FROM submitted_documents WHERE id_number = ? ORDER BY date_submitted DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Requested Documents - Cornerstone College Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">
  
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold">Requested Documents</h1>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Student Portal</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="max-w-5xl mx-auto px-4 py-8">
    <!-- Page Title -->
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Submitted Documents</h2>
        <p class="text-gray-600">View your submitted documents history</p>
      </div>

    <!-- Documents Container -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
      <?php if ($result->num_rows > 0): ?>
        <!-- Table Header -->
        <div class="bg-[#0B2C62] text-white px-4 py-3">
          <div class="grid grid-cols-3 gap-2 text-sm font-semibold">
            <div>Document Name</div>
            <div class="text-center">Date Submitted</div>
            <div class="text-right">Remarks</div>
          </div>
        </div>

        <!-- Table Body -->
        <div class="divide-y divide-gray-100">
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
              <div class="grid grid-cols-3 gap-2 items-center">
                <!-- Document Name -->
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-blue-600 text-sm"></i>
                  </div>
                  <span class="font-medium text-gray-800"><?= htmlspecialchars($row['document_name']) ?></span>
                </div>
                
                <!-- Date Submitted -->
                <div class="text-gray-600 text-sm text-center">
                  <?php if ($row['date_submitted']): ?>
                    <?= date('M d, Y', strtotime($row['date_submitted'])) ?>
                  <?php else: ?>
                    <span class="text-gray-400">---</span>
                  <?php endif; ?>
                </div>
                
                <!-- Remarks -->
                <div class="text-gray-600 text-sm text-right">
                  <?php if (!empty($row['remarks'])): ?>
                    <?= htmlspecialchars($row['remarks']) ?>
                  <?php else: ?>
                    <span class="text-gray-400">No remarks</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16">
          <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-file-alt text-3xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-semibold text-gray-700 mb-3">No Documents Found</h3>
          <p class="text-gray-500 mb-8 max-w-md mx-auto">You haven't submitted any documents yet.</p>
        </div>
      <?php endif; ?>
    </div>

  </main>

</body>
</html>
