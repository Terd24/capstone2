<?php
session_start();

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header("Location: ../StudentLogin/login.html");
    exit;
}

require_once '../StudentLogin/db_conn.php';

// Get archive type from URL
$archive_type = $_GET['type'] ?? 'login';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 50;
$offset = ($page - 1) * $records_per_page;

// Search filters
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if ($archive_type === 'login') {
    // Login Logs Archive
    $table = 'login_logs_archive';
    $date_column = 'login_time';
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($search) {
        $where_conditions[] = "(username LIKE ? OR ip_address LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    
    if ($date_from) {
        $where_conditions[] = "DATE($date_column) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if ($date_to) {
        $where_conditions[] = "DATE($date_column) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
    if ($params) {
        $count_stmt = $conn->prepare($count_query);
        if ($types) $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    }
    
    // Get records
    $query = "SELECT * FROM $table $where_clause ORDER BY archived_at DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
} else {
    // Attendance Archive
    $table = 'attendance_archive';
    $date_column = 'date';
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($search) {
        $where_conditions[] = "(id_number LIKE ? OR name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    
    if ($date_from) {
        $where_conditions[] = "DATE($date_column) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if ($date_to) {
        $where_conditions[] = "DATE($date_column) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM $table $where_clause";
    if ($params) {
        $count_stmt = $conn->prepare($count_query);
        if ($types) $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    }
    
    // Get records
    $query = "SELECT * FROM $table $where_clause ORDER BY archived_at DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}

$total_pages = ceil($total_records / $records_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Archives - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../images/Logo.png">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-[#0B2C62] text-white shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="SuperAdminDashboard.php" class="text-white hover:text-blue-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold">View Archives</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm">Super Admin</span>
                    <a href="SuperAdminDashboard.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        
        <!-- Archive Type Tabs -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="flex border-b">
                <a href="?type=login" class="px-6 py-4 font-medium <?= $archive_type === 'login' ? 'text-[#0B2C62] border-b-2 border-[#0B2C62]' : 'text-gray-600 hover:text-[#0B2C62]' ?>">
                    Login Logs Archive
                </a>
                <a href="?type=attendance" class="px-6 py-4 font-medium <?= $archive_type === 'attendance' ? 'text-[#0B2C62] border-b-2 border-[#0B2C62]' : 'text-gray-600 hover:text-[#0B2C62]' ?>">
                    Attendance Archive
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="type" value="<?= htmlspecialchars($archive_type) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="<?= $archive_type === 'login' ? 'Username or IP' : 'ID or Name' ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62]">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62]">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62]">
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg font-medium">
                        Search
                    </button>
                    <a href="?type=<?= $archive_type ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?= $archive_type === 'login' ? 'Login Logs' : 'Attendance Records' ?> 
                    <span class="text-sm font-normal text-gray-600">(<?= number_format($total_records) ?> total)</span>
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <?php if ($archive_type === 'login'): ?>
                    <!-- Login Logs Table -->
                    <table class="w-full">
                        <thead class="bg-[#0B2C62] text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">Username</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Login Time</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">IP Address</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">User Type</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Archived At</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Archived By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['username']) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= date('M d, Y g:i A', strtotime($row['login_time'])) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['ip_address']) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['user_type']) ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full <?= $row['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= date('M d, Y g:i A', strtotime($row['archived_at'])) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['archived_by']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No archived records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- Attendance Table -->
                    <table class="w-full">
                        <thead class="bg-[#0B2C62] text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">ID Number</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Time In</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Time Out</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">User Type</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Archived At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['id_number']) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['name']) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= $row['time_in'] ? date('g:i A', strtotime($row['time_in'])) : '---' ?></td>
                                        <td class="px-4 py-3 text-sm"><?= $row['time_out'] ? date('g:i A', strtotime($row['time_out'])) : '---' ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['user_type']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= date('M d, Y g:i A', strtotime($row['archived_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">No archived records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $records_per_page, $total_records)) ?> of <?= number_format($total_records) ?> records
                    </div>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?type=<?= $archive_type ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        
                        <span class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg">Page <?= $page ?> of <?= $total_pages ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?type=<?= $archive_type ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
