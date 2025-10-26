<?php
session_start();
include("db_conn.php");

$id_number = $_SESSION['id_number'];

$sql = "SELECT document_type, date_requested, date_claimed, student_id, status 
        FROM document_requests 
        WHERE student_id = ? 
        ORDER BY date_requested DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Requested Documents - CCI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }

    /* Mobile PWA optimizations */
    @media (max-width: 1023px) {
      /* Ensure stable layout */
      html, body {
        overflow-x: hidden;
      }

      /* Modal styles - only apply when modal is actually open */
      body.modal-open {
        overflow: hidden;
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
      }

      /* Modal positioning - more stable */
      #documentModal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.5);
      }

      #documentModal.hidden {
        display: none;
      }

      #documentModal > div {
        width: 100%;
        max-width: 400px;
        max-height: 80vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
      }
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3 sm:space-x-4">
          <button onclick="window.location.href='studentDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 sm:p-3 rounded-lg transition-all duration-200">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-lg sm:text-xl font-bold">Requested Documents</h1>
          </div>
        </div>
        <div class="flex items-center space-x-3 sm:space-x-4">
          <img src="../images/LogoCCI.png" alt="CCI Logo" class="h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-white p-1">
          <div class="hidden sm:block text-right">
            <h1 class="text-lg sm:text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-xs sm:text-sm">Student Portal</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-6 sm:mb-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Document Requests</h2>
        <p class="text-gray-600 text-sm sm:text-base">Track the status of your document requests</p>
      </div>

      <!-- Documents Table Card -->
      <div class="bg-white rounded-2xl card-shadow overflow-hidden">
        <!-- Desktop Table View (hidden on mobile) -->
        <div class="hidden lg:block overflow-x-auto">
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
                          <p class="font-medium text-gray-900 text-sm sm:text-base">
                            <?= htmlspecialchars($row['document_type']) ?>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 text-sm sm:text-base">
                      <?= $row['date_requested'] ? date('M d, Y', strtotime($row['date_requested'])) : '---' ?>
                    </td>
                    <td class="px-6 py-4 text-gray-600 text-sm sm:text-base">
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
                      <button onclick="window.location.href='request-document.php'" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg transition-colors">
                        Request Document
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View (hidden on desktop) -->
        <div class="lg:hidden">
          <?php if ($result->num_rows > 0): ?>
            <?php
            $result->data_seek(0); // Reset pointer
            while ($row = $result->fetch_assoc()): ?>
              <div class="p-2 border-b border-gray-100 last:border-b-0">
                <div class="space-y-2">
                  <!-- Document Name and Icon -->
                  <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center flex-shrink-0">
                      <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-semibold text-gray-900 text-sm leading-tight">
                        <?= htmlspecialchars($row['document_type']) ?>
                      </h3>
                    </div>
                  </div>

                  <!-- Document Details in Compact Grid -->
                  <div class="grid grid-cols-2 gap-1">
                    <div class="bg-gray-50 rounded p-1.5 border border-gray-200">
                      <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Req:</span>
                        <span class="font-semibold text-gray-800">
                          <?= $row['date_requested'] ? date('M d', strtotime($row['date_requested'])) : '---' ?>
                        </span>
                      </div>
                    </div>

                    <div class="bg-gray-50 rounded p-1.5 border border-gray-200">
                      <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500">Claim:</span>
                        <span class="font-semibold text-gray-800">
                          <?= ($row['date_claimed'] && $row['status']==='Claimed') ? date('M d', strtotime($row['date_claimed'])) : '---' ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Status Badge and Action Button -->
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <?php if ($row['status'] === 'Pending'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                          </svg>
                          Pending
                        </span>
                      <?php elseif ($row['status'] === 'Ready to Claim' || $row['status'] === 'Ready for Claiming'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Ready
                        </span>
                      <?php elseif (strcasecmp(trim($row['status']), 'Approved') === 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Approved
                        </span>
                      <?php elseif ($row['status'] === 'Claimed'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.51.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                          Claimed
                        </span>
                      <?php elseif (strcasecmp(trim($row['status']), 'Declined') === 0): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                          <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd" />
                          </svg>
                          Declined
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          <?= htmlspecialchars($row['status']) ?>
                        </span>
                      <?php endif; ?>
                    </div>

                    <!-- View Details Button -->
                    <button onclick="viewDocument('<?= htmlspecialchars($row['document_type']) ?>', '<?= $row['date_requested'] ?>', '<?= $row['status'] ?>')"
                            class="bg-[#0B2C62] text-white px-2 py-1 rounded text-xs font-medium min-h-[32px] flex items-center justify-center">
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                      View
                    </button>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Mobile Empty State -->
            <div class="p-4 text-center">
              <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <h3 class="text-sm font-semibold text-gray-900 mb-1">No requests found</h3>
              <p class="text-gray-500 text-xs mb-3">You haven't requested any documents yet.</p>
              <button onclick="window.location.href='request-document.php'"
                      class="bg-[#0B2C62] text-white px-3 py-1.5 rounded text-xs font-medium min-h-[32px] w-full max-w-xs">
                <svg class="w-3 h-3 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Request Document
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Document Details Modal -->
  <div id="documentModal" class="hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Document Details</h3>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div id="modalContent" class="space-y-4">
        <!-- Content will be populated by JavaScript -->
      </div>
      <div class="mt-8 flex justify-end">
        <button onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-all duration-200 font-medium min-h-[48px]">
          Close
        </button>
      </div>
    </div>
  </div>

  <script>
    function viewDocument(documentType, dateRequested, status) {
      const modal = document.getElementById('documentModal');
      const content = document.getElementById('modalContent');

      // Prevent body scroll when modal is open on mobile
      if (window.innerWidth <= 1023) {
        document.body.classList.add('modal-open');
      }

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
    }

    function closeModal() {
      const modal = document.getElementById('documentModal');

      // Restore body scroll when modal closes on mobile
      document.body.classList.remove('modal-open');

      modal.classList.add('hidden');
    }
  </script>

</body>
</html>
<?php $stmt->close(); ?>
