<?php
session_start();
include('../StudentLogin/db_conn.php');

if (!isset($_SESSION['parent_id'])) {
    header("Location: ParentLogin.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

// Get child information
$child_query = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM student_account WHERE id_number = ?");
$child_query->bind_param("s", $child_id);
$child_query->execute();
$child_result = $child_query->get_result();
$child_name = ($child_result->num_rows === 1) ? $child_result->fetch_assoc()['full_name'] : 'Unknown';

// Fetch document requests for the child
$sql = "SELECT document_type, date_requested, date_claimed, student_id, status 
        FROM document_requests 
        WHERE student_id = ? 
        ORDER BY date_requested DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $child_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Requested Documents - CCI Parent Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <button onclick="window.location.href='ParentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold">Requested Documents</h1>
            <p class="text-sm text-blue-200">For: <?= htmlspecialchars($child_name) ?></p>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Parent Portal</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Document Requests</h2>
        <p class="text-gray-600">Track the status of your child's document requests</p>
      </div>

      <!-- Documents Table Card -->
      <div class="bg-white rounded-2xl card-shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-[#0B2C62] text-white">
              <tr>
                <th class="px-6 py-4 text-left font-semibold">Document Name</th>
                <th class="px-6 py-4 text-left font-semibold">Date Requested</th>
                <th class="px-6 py-4 text-left font-semibold">Date Claimed</th>
                <th class="px-6 py-4 text-left font-semibold">Status</th>
                <th class="px-6 py-4 text-center font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr class="hover:bg-blue-50 transition-colors duration-150">
                    <td class="px-6 py-4">
                      <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                          </svg>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900"><?= htmlspecialchars($row['document_type']) ?></p>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                      <?= $row['date_requested'] ? date('M d, Y', strtotime($row['date_requested'])) : '---' ?>
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                      <?= ($row['date_claimed'] && $row['status']==='Claimed') ? date('M d, Y', strtotime($row['date_claimed'])) : '---' ?>
                    </td>
                    <td class="px-6 py-4">
                      <?php if ($row['status'] === 'Pending'): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                          </svg>
                          Pending
                        </span>
                      <?php elseif ($row['status'] === 'Ready to Claim' || $row['status'] === 'Ready for Claiming'): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Ready to Claim
                        </span>
                      <?php elseif (strcasecmp(trim($row['status']), 'Approved') === 0): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Approved
                        </span>
                      <?php elseif ($row['status'] === 'Claimed'): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.51.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Claimed
                        </span>
                      <?php elseif (strcasecmp(trim($row['status']), 'Declined') === 0): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd" />
                          </svg>
                          Declined
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          <?= htmlspecialchars($row['status']) ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <button onclick="viewDocument('<?= htmlspecialchars($row['document_type']) ?>', '<?= $row['date_requested'] ?>', '<?= $row['status'] ?>')" class="text-[#0B2C62] hover:text-blue-900 font-medium text-sm transition-colors">
                        View Details
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                      <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                      <h3 class="text-lg font-medium text-gray-900 mb-1">No document requests found</h3>
                      <p class="text-gray-500 mb-4">You haven't requested any documents yet.</p>
                      <button onclick="window.location.href='ParentDocumentRequest.php'" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg transition-colors">
                        Request Document
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Document Details Modal -->
  <div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div id="modalContent" class="space-y-3">
        <!-- Content will be populated by JavaScript -->
      </div>
      <div class="mt-6 flex justify-end">
        <button onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
          Close
        </button>
      </div>
    </div>
  </div>

  <script>
    function viewDocument(documentType, dateRequested, status) {
      const modal = document.getElementById('documentModal');
      const content = document.getElementById('modalContent');
      
      const formattedDate = new Date(dateRequested).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      
      content.innerHTML = `
        <div class="border-l-4 border-blue-500 pl-4">
          <p class="text-sm text-gray-600">Document Type</p>
          <p class="font-medium text-gray-900">${documentType}</p>
        </div>
        <div class="border-l-4 border-blue-500 pl-4">
          <p class="text-sm text-gray-600">Date Requested</p>
          <p class="font-medium text-gray-900">${formattedDate}</p>
        </div>
        <div class="border-l-4 border-blue-500 pl-4">
          <p class="text-sm text-gray-600">Current Status</p>
          <p class="font-medium text-gray-900">${status}</p>
        </div>
      `;
      
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
    
    function closeModal() {
      const modal = document.getElementById('documentModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }
  </script>

</body>
</html>
<?php $stmt->close(); ?>
