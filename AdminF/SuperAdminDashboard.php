<?php
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once '../StudentLogin/db_conn.php';

// Function to generate next Employee ID (same as HR Dashboard)
function generateNextEmployeeId($conn) {
    $currentYear = date('Y');
    $prefix = 'CCI' . $currentYear . '-';
    
    // Get the highest existing employee ID for current year
    $query = "SELECT id_number FROM employees WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastId = $row['id_number'];
        // Extract the numeric part after the dash
        $parts = explode('-', $lastId);
        if (count($parts) == 2) {
            $numericPart = intval($parts[1]);
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }
    } else {
        $nextNumber = 1;
    }
    
    // Format as CCI2025-001, CCI2025-002, etc. (3 digits)
    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

// Clear any session messages (we use toast notifications now)
unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);

// Check maintenance mode status
$maintenance_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
$is_maintenance = false;
if ($maintenance_result && $maintenance_result->num_rows > 0) {
    $maintenance_row = $maintenance_result->fetch_assoc();
    $is_maintenance = ($maintenance_row['config_value'] == '1');
}
$system_status = $is_maintenance ? '🔴 Maintenance' : '🟢 Online';
$system_status_color = $is_maintenance ? 'text-red-600' : 'text-green-600';

// Include dashboard data processing
require_once 'includes/dashboard_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-[#0B2C62] to-[#153e86] text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
        <div class="flex items-center justify-between h-16 px-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <img src="../images/LogoCCI.png" class="h-8 w-8 rounded-full bg-white p-1" alt="Logo">
                <div class="leading-tight">
                    <div class="font-bold text-sm">Cornerstone College</div>
                    <div class="text-xs text-blue-200">Super Admin</div>
                </div>
            </div>
        </div>
        
        <nav class="mt-8 px-4">
            <div class="space-y-2">
                <!-- Dashboard -->
                <a href="#dashboard" onclick="showSection('dashboard', event)" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <!-- Management Tools -->
                <div class="pt-4">
                    <div class="text-xs font-semibold text-blue-200 uppercase tracking-wider px-4 mb-2">Management</div>
                    <a href="#hr-accounts" onclick="showSection('hr-accounts', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>HR Accounts</span>
                    </a>
                    <a href="#system-maintenance" onclick="showSection('system-maintenance', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>System Maintenance</span>
                    </a>
                    <a href="#deleted-items" onclick="showSection('deleted-items', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Deleted Items</span>
                    </a>
                    
                    <!-- User Info & Logout -->
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <div class="flex items-center gap-3 mb-3 px-4">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold"><?= substr($_SESSION['superadmin_name'] ?? 'IT', 0, 2) ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['superadmin_name'] ?? 'IT Personnel') ?></div>
                                <div class="text-xs text-blue-200">Super Administrator</div>
                            </div>
                        </div>
                        <a href="../StudentLogin/logout.php" class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-white/10 rounded-lg transition mx-4">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-0">
        <!-- Top Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 id="page-title" class="text-2xl font-bold text-gray-900">Dashboard</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="location.reload()" class="p-2 rounded-md hover:bg-gray-100 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <main class="p-6">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section active">
                <!-- System Status Overview -->
                <div class="mb-6 bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-xl p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold">System Status Overview</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-gray-300 text-xs font-medium">System Status</div>
                            </div>
                            <div class="text-lg font-bold system-status-display"><?= $system_status ?></div>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                                <div class="text-gray-300 text-xs font-medium">Data Usage</div>
                            </div>
                            <div class="text-lg font-bold">Monitoring</div>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <div class="text-gray-300 text-xs font-medium">Performance</div>
                            </div>
                            <div class="text-lg font-bold">Optimal</div>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <div class="text-gray-300 text-xs font-medium">Security</div>
                            </div>
                            <div class="text-lg font-bold">Secured</div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Enrollment -->
                    <div class="group bg-white rounded-xl shadow-md hover:shadow-xl border border-gray-100 p-6 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Total Enrollees</p>
                                <p class="text-4xl font-bold text-gray-900"><?= number_format($total_students ?? 35) ?></p>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Active students</span>
                        </div>
                    </div>

                    <!-- Present Today -->
                    <div class="group bg-white rounded-xl shadow-md hover:shadow-xl border border-gray-100 p-6 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">Present Today</p>
                                <p class="text-4xl font-bold text-gray-900"><?= number_format(($student_present_today ?? 0) + ($employee_present_today ?? 0)) ?></p>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <span class="text-gray-600">Students: <span class="font-semibold text-gray-900"><?= $student_present_today ?? 0 ?></span></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                <span class="text-gray-600">Teachers: <span class="font-semibold text-gray-900"><?= $employee_present_today ?? 0 ?></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="group bg-white rounded-xl shadow-md hover:shadow-xl border border-gray-100 p-6 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-1">System Health</p>
                                <p class="text-4xl font-bold text-gray-900">Optimal</p>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-600">
                            <span>DB: <span class="font-semibold text-gray-900"><?= $db_size ?? 1.44 ?> MB</span></span>
                            <span class="text-gray-300">•</span>
                            <span>Records: <span class="font-semibold text-gray-900"><?= number_format($total_records ?? 44) ?></span></span>
                        </div>
                    </div>
                </div>

                <!-- Additional Dashboard Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Enrollment by Grade Level -->
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg border border-gray-100 p-6 transition-shadow">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Enrollment by Grade Level</h3>
                                <p class="text-xs text-gray-500 mt-1">Student distribution across grades</p>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="text-sm font-semibold text-gray-900"><?= array_sum($grade_levels ?? []) ?></span>
                            </div>
                        </div>
                        <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                            <?php
                            // Get enrollment by grade level
                            $grade_levels = [
                                'Not Set' => 1,
                                '1-A' => 2,
                                '1-B' => 1,
                                '11-A' => 2,
                                '11-B' => 3,
                                '12-A' => 3,
                                '12-B' => 1
                            ];
                            
                            // Try to get real data from database
                            if (!empty($enrollment_by_grade)) {
                                $grade_levels = [];
                                foreach ($enrollment_by_grade as $grade) {
                                    $grade_levels[$grade['grade_level'] ?: 'Not Set'] = (int)$grade['count'];
                                }
                            }
                            
                            // Color palette for different grades
                            $colors = [
                                'bg-blue-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500',
                                'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500',
                                'bg-teal-500', 'bg-cyan-500', 'bg-sky-500', 'bg-violet-500'
                            ];
                            
                            $maxCount = max(array_values($grade_levels));
                            $colorIndex = 0;
                            
                            foreach ($grade_levels as $grade => $count):
                                $percentage = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                $color = $colors[$colorIndex % count($colors)];
                                $colorIndex++;
                            ?>
                            <div class="group">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full <?= $color ?>"></div>
                                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($grade) ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500"><?= number_format(($count / max(1, array_sum($grade_levels))) * 100, 1) ?>%</span>
                                        <span class="text-sm font-bold text-gray-900 min-w-[2rem] text-right"><?= $count ?></span>
                                    </div>
                                </div>
                                <div class="relative h-3 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="absolute inset-0 <?= $color ?> rounded-full transition-all duration-500 group-hover:opacity-90" 
                                         style="width: <?= $percentage ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Summary Stats -->
                        <div class="mt-5 pt-5 border-t border-gray-100">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">Total Grades</div>
                                    <div class="text-lg font-bold text-gray-900"><?= count($grade_levels) ?></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">Avg per Grade</div>
                                    <div class="text-lg font-bold text-gray-900"><?= count($grade_levels) > 0 ? round(array_sum($grade_levels) / count($grade_levels), 1) : 0 ?></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">Largest Class</div>
                                    <div class="text-lg font-bold text-gray-900"><?= max(array_values($grade_levels)) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- System Performance -->
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg border border-gray-100 p-6 transition-shadow">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">System Performance</h3>
                                <p class="text-xs text-gray-500 mt-1">Real-time system metrics</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Healthy
                            </span>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Database Size -->
                            <div class="group p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">Database Size</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?= number_format($db_size ?? 1.44, 2) ?> MB</span>
                                </div>
                                <div class="ml-10">
                                    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" style="width: <?= min(100, (($db_size ?? 1.44) / 10) * 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Tables -->
                            <div class="group p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">Total Tables</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?= number_format($table_count ?? 37) ?></span>
                                </div>
                            </div>

                            <!-- Total Records -->
                            <div class="group p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">Total Records</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900"><?= number_format($total_records ?? 44) ?></span>
                                </div>
                            </div>

                            <!-- Server Uptime -->
                            <div class="group p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">Server Uptime</span>
                                    </div>
                                    <span class="text-lg font-bold text-green-600"><?= $formatted_uptime ?? '02:39:04' ?></span>
                                </div>
                            </div>

                            <!-- Active Connections -->
                            <div class="group p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">Active Connections</span>
                                    </div>
                                    <span class="text-lg font-bold text-cyan-600"><?= number_format($connections ?? 1) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login Activity Section -->
                <div class="mb-6">
                    <!-- Today's Logins -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-blue-600 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="bg-white/20 p-2 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white">Today's Logins</h3>
                                        <p class="text-blue-100 text-sm">Recent system access activity</p>
                                    </div>
                                </div>
                                <button onclick="location.reload()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        
                        <!-- Login Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="logins-table">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">User Type</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Login Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" id="logins-tbody">
                                    <?php if (!empty($today_logins)): ?>
                                        <?php foreach ($today_logins as $login): 
                                            $userTypeColor = $login['user_type'] === 'employee' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                                            $roleColors = [
                                                'superadmin' => 'bg-red-100 text-red-700',
                                                'hr' => 'bg-orange-100 text-orange-700',
                                                'teacher' => 'bg-green-100 text-green-700',
                                                'registrar' => 'bg-indigo-100 text-indigo-700',
                                                'cashier' => 'bg-yellow-100 text-yellow-700',
                                                'guidance' => 'bg-pink-100 text-pink-700',
                                                'attendance' => 'bg-teal-100 text-teal-700',
                                                'student' => 'bg-blue-100 text-blue-700'
                                            ];
                                            $roleColor = $roleColors[$login['role']] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                        <tr class="hover:bg-blue-50 transition-colors login-row">
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $userTypeColor ?>">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <circle cx="10" cy="10" r="3"/>
                                                    </svg>
                                                    <?= ucfirst(htmlspecialchars($login['user_type'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 font-mono text-gray-600"><?= htmlspecialchars($login['id_number']) ?></td>
                                            <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($login['full_name'] ?: $login['username']) ?></td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $roleColor ?>">
                                                    <?= ucfirst(htmlspecialchars($login['role'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?= date('M j, Y g:i A', strtotime($login['login_time'])) ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="login-row">
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                No logins recorded today
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination for Today's Logins -->
                        <?php if (!empty($today_logins) && count($today_logins) > 10): ?>
                        <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="text-sm text-gray-600">
                                Showing <span id="logins-start" class="font-semibold text-gray-900">1</span> to <span id="logins-end" class="font-semibold text-gray-900">10</span> of <span id="logins-total" class="font-semibold text-gray-900"><?= count($today_logins) ?></span> logins
                            </div>
                            <div class="flex gap-2">
                                <button id="logins-prev" onclick="changeLoginsPage(-1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Previous
                                </button>
                                <button id="logins-next" onclick="changeLoginsPage(1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    Next
                                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Not Logged In Today Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Not Logged In Today (Teachers) -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-orange-500 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                                    <p class="text-orange-100 text-sm">Teachers</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="min-h-[280px]">
                                <ul class="space-y-2" id="employees-list">
                                    <!-- Items will be loaded here -->
                                </ul>
                                <div id="employees-loading" class="text-center py-8">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-200 border-t-orange-600"></div>
                                    <p class="text-gray-500 text-sm mt-2">Loading teachers...</p>
                                </div>
                            </div>
                            <!-- Pagination for Teachers -->
                            <div id="employees-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                                <div class="text-sm text-gray-600">
                                    Showing <span id="employees-start" class="font-semibold text-gray-900">1</span> to <span id="employees-end" class="font-semibold text-gray-900">10</span> of <span id="employees-total" class="font-semibold text-gray-900">0</span> teachers
                                </div>
                                <div class="flex gap-2">
                                    <button id="employees-prev" onclick="changeEmployeesPage(-1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        Prev
                                    </button>
                                    <button id="employees-next" onclick="changeEmployeesPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        Next
                                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Not Logged In Today (Students) -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-blue-500 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                                    <p class="text-blue-100 text-sm">Students</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="min-h-[280px]">
                                <ul class="space-y-2" id="students-list">
                                    <!-- Items will be loaded here -->
                                </ul>
                                <div id="students-loading" class="text-center py-8">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600"></div>
                                    <p class="text-gray-500 text-sm mt-2">Loading students...</p>
                                </div>
                            </div>
                            <!-- Pagination for Students -->
                            <div id="students-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                                <div class="text-sm text-gray-600">
                                    Showing <span id="students-start" class="font-semibold text-gray-900">1</span> to <span id="students-end" class="font-semibold text-gray-900">10</span> of <span id="students-total" class="font-semibold text-gray-900">0</span> students
                                </div>
                                <div class="flex gap-2">
                                    <button id="students-prev" onclick="changeStudentsPage(-1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        Prev
                                    </button>
                                    <button id="students-next" onclick="changeStudentsPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        Next
                                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HR Accounts Section -->
            <div id="hr-accounts-section" class="section hidden">
                <!-- Header Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <h2 class="text-xl font-semibold text-gray-900">HR Accounts Management</h2>
                                <?php
                                // Get HR employee count (all HR employees, not just those with accounts)
                                $hr_count_query = "SELECT COUNT(*) as count FROM employees WHERE department = 'Human Resources'";
                                $hr_count_result = $conn->query($hr_count_query);
                                $hr_count = $hr_count_result ? $hr_count_result->fetch_assoc()['count'] : 0;
                                ?>
                                <span class="bg-[#0B2C62] text-white px-3 py-1 rounded-full text-sm font-medium">Total: <?= $hr_count ?></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Show entries:</label>
                                    <select class="border border-gray-300 rounded px-2 py-1 text-sm">
                                        <option>10</option>
                                        <option>25</option>
                                        <option>50</option>
                                    </select>
                                </div>
                                <input type="text" placeholder="Search by name or ID..." class="border border-gray-300 rounded px-3 py-2 text-sm w-64">
                                <button onclick="addHRAccount()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                                    + Add HR Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-[#0B2C62] text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium">ID Number</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Full Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Position</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Hire Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Account Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                // Get ALL HR employees (with and without system accounts)
                                $hr_query = "
                                    SELECT 
                                        e.id_number,
                                        e.first_name,
                                        e.last_name,
                                        e.middle_name,
                                        e.position,
                                        e.department,
                                        e.hire_date,
                                        ea.username,
                                        ea.role,
                                        CASE 
                                            WHEN ea.employee_id IS NOT NULL THEN 'Has Account'
                                            ELSE 'No Account'
                                        END as account_status
                                    FROM employees e
                                    LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id AND ea.role = 'hr'
                                    WHERE e.department = 'Human Resources'
                                    ORDER BY e.last_name, e.first_name
                                ";
                                
                                $hr_result = $conn->query($hr_query);
                                
                                if ($hr_result && $hr_result->num_rows > 0):
                                    while ($hr = $hr_result->fetch_assoc()):
                                        $full_name = trim($hr['first_name'] . ' ' . ($hr['middle_name'] ? $hr['middle_name'] . ' ' : '') . $hr['last_name']);
                                ?>
                                <tr class="hover:bg-[#0B2C62]/5 cursor-pointer transition-colors" onclick="viewHRAccount('<?= htmlspecialchars($hr['id_number']) ?>')">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($hr['id_number']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($full_name) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($hr['position'] ?: 'HR Staff') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($hr['department'] ?: 'Human Resources') ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= $hr['hire_date'] ? date('M d, Y', strtotime($hr['hire_date'])) : 'N/A' ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($hr['account_status'] === 'Has Account'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Has Account
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                No Account
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No HR employees found. Create HR employees to manage HR staff.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- System Maintenance Section -->
            <div id="system-maintenance-section" class="section hidden">
                <!-- System Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-[#1e3a8a]/20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#1e3a8a]/10 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-[#1e3a8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-[#1e3a8a] text-sm font-medium">Database Size</div>
                                <div class="text-3xl font-bold text-gray-900"><?= number_format($db_size ?? 1.44, 2) ?> MB</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-[#1e3a8a]/20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#1e3a8a]/10 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-[#1e3a8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-[#1e3a8a] text-sm font-medium">System Status</div>
                                <div class="text-xl font-bold system-status-display <?= $system_status_color ?>"><?= $system_status ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-[#1e3a8a]/20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#1e3a8a]/10 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-[#1e3a8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-[#1e3a8a] text-sm font-medium">Total Users</div>
                                <div class="text-3xl font-bold text-gray-900"><?= number_format(($total_students ?? 35) + ($total_employees ?? 0)) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-[#1e3a8a]/20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#1e3a8a]/10 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-[#1e3a8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-[#1e3a8a] text-sm font-medium">Total Tables</div>
                                <div class="text-3xl font-bold text-gray-900"><?= number_format($table_count ?? 37) ?></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- System Configuration -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-[#0B2C62]/10 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">System Configuration</h3>
                            <p class="text-sm text-gray-600">Control system-wide settings and database maintenance</p>
                        </div>
                    </div>


                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Maintenance Mode -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Maintenance Mode</h4>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <div class="font-medium text-gray-900">System Maintenance</div>
                                        <div class="text-sm text-gray-600">Restrict system access for maintenance</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="maintenanceToggle" class="sr-only peer" onchange="toggleMaintenance()">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0B2C62]"></div>
                                    </label>
                                </div>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Prevents user logins during maintenance</li>
                                    <li>• Displays maintenance message to users</li>
                                </ul>
                            </div>
                            <button onclick="updateConfiguration()" class="w-full bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                Update Configuration
                            </button>
                        </div>

                        <!-- Database Backup -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Database Backup</h4>
                            <div class="bg-[#0B2C62]/5 rounded-lg p-4 mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-[#0B2C62] font-medium">Backup Information</span>
                                </div>
                                <ul class="text-sm text-[#0B2C62] space-y-1">
                                    <li>• Includes all tables and data</li>
                                    <li>• Regular backups recommended before updates</li>
                                    <li>• Creates timestamped backup files</li>
                                </ul>
                            </div>
                            <button onclick="createDatabaseBackup()" class="w-full bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Create Database Backup
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Management -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-[#0B2C62]/10 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Data Management</h3>
                            <p class="text-sm text-gray-600">Clear old records and manage system data</p>
                        </div>
                    </div>


                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Clear Login Logs -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Clear Login Logs</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                                    <input type="date" id="loginStartDate" value="31/08/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
                                    <input type="date" id="loginEndDate" value="30/09/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <p class="text-sm text-gray-600">All login records between these dates will be deleted and saved to CSV</p>
                            </div>
                            <button onclick="clearLoginLogs()" class="w-full mt-4 bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Clear Login Logs
                            </button>
                        </div>

                        <!-- Clear Attendance Records -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Clear Attendance Records</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                                    <input type="date" id="attendanceStartDate" value="31/08/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
                                    <input type="date" id="attendanceEndDate" value="30/09/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <p class="text-sm text-gray-600">All attendance records between these dates will be deleted and saved to CSV</p>
                            </div>
                            <button onclick="clearAttendanceRecords()" class="w-full mt-4 bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Clear Attendance Records
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deleted Items Section -->
            <div id="deleted-items-section" class="section hidden">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Deleted Students Card -->
                    <div class="bg-red-500 text-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white/80 text-sm font-medium">Deleted Students</div>
                                <div class="text-4xl font-bold text-white"><?= count($deleted_students) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Deleted Employees Card -->
                    <div class="bg-orange-500 text-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-white/80 text-sm font-medium">Deleted Employees</div>
                                <div class="text-4xl font-bold text-white"><?= count($deleted_employees) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deleted Students Table -->
                <div class="bg-white rounded-2xl shadow-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <h3 class="text-lg font-bold text-gray-900">Deleted Students (<?= count($deleted_students) ?>)</h3>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($deleted_students) > 0): ?>
                                    <?php foreach ($deleted_students as $student): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                    <span class="text-red-600 font-medium text-sm">
                                                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($student['id_number']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($student['academic_track'] ?: 'N/A') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($student['grade_level'] ?: 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <div class="font-medium"><?= date('M j, Y g:i A', strtotime($student['deleted_at'])) ?></div>
                                                <div class="text-gray-500">By: <?= htmlspecialchars($student['deleted_by'] ?: 'Unknown') ?></div>
                                                <?php if ($student['deleted_reason']): ?>
                                                    <div class="text-gray-500 text-xs mt-1">Reason: <?= htmlspecialchars($student['deleted_reason']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button onclick="restoreStudent('<?= htmlspecialchars($student['id_number']) ?>')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    Restore
                                                </button>
                                                <button onclick="exportToFile('<?= htmlspecialchars($student['id_number']) ?>', 'student')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    Export to File
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                            </svg>
                                            No deleted students found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Deleted Employees Table -->
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <h3 class="text-lg font-bold text-gray-900">Deleted Employees (<?= count($deleted_employees) ?>)</h3>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($deleted_employees) > 0): ?>
                                    <?php foreach ($deleted_employees as $employee): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                                    <span class="text-orange-600 font-medium text-sm">
                                                        <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($employee['id_number']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($employee['position'] ?: 'N/A') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($employee['department'] ?: 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <div class="font-medium"><?= date('M j, Y g:i A', strtotime($employee['deleted_at'])) ?></div>
                                                <div class="text-gray-500">By: <?= htmlspecialchars($employee['deleted_by'] ?: 'Unknown') ?></div>
                                                <?php if ($employee['deleted_reason']): ?>
                                                    <div class="text-gray-500 text-xs mt-1">Reason: <?= htmlspecialchars($employee['deleted_reason']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button onclick="restoreEmployee('<?= htmlspecialchars($employee['id_number']) ?>')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    Restore
                                                </button>
                                                <button onclick="exportToFile('<?= htmlspecialchars($employee['id_number']) ?>', 'employee')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">
                                                    Export to File
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            No deleted employees found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .section.hidden {
            display: none;
        }
        .section.active {
            display: block;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        function showSection(sectionName, clickEvent = null) {
            // Prevent default link behavior to avoid hash in URL
            if (clickEvent) {
                clickEvent.preventDefault();
            }
            
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
                section.classList.add('hidden');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }
            
            // Update nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked nav item
            if (clickEvent && clickEvent.target) {
                const navItem = clickEvent.target.closest('.nav-item');
                if (navItem) {
                    navItem.classList.add('active');
                }
            }
            
            // Remove hash from URL without page reload
            if (window.location.hash) {
                history.replaceState(null, null, window.location.pathname);
            }
            
            // Update page title
            const titles = {
                'dashboard': 'Dashboard',
                'hr-accounts': 'HR Accounts Management',
                'system-maintenance': 'System Maintenance',
                'deleted-items': 'Deleted Items Management'
            };
            
            document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
        }

        // HR Accounts Functions - Based on HRF Dashboard
        let currentHREmployeeId = null;
        
        function viewHRAccount(employeeId) {
            // Fetch HR employee details and show in modal
            fetch(`view_hr_employee.php?id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showHREmployeeDetailsModal(data.employee);
                    } else {
                        alert('Error loading HR employee details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading HR employee details');
                });
        }

        // Show HR employee details modal
        function showHREmployeeDetailsModal(employee) {
            currentHREmployeeId = employee.id_number;
            
            // Create modal for viewing HR employee details
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    <!-- Header -->
                    <div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center justify-between rounded-t-lg">
                        <h3 class="text-xl font-semibold">Employee Information</h3>
                        <div class="flex items-center gap-3">
                            <button id="saveHRChangesBtn" onclick="showSaveConfirmation()" class="hidden px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Save Changes</button>
                            <button id="editHREmployeeBtn" onclick="toggleHREditMode()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                            <button id="deleteHREmployeeBtn" onclick="showDeleteHREmployeeConfirmation('${employee.id_number}')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete Employee</button>
                            <button onclick="closeHRModal()" class="text-white hover:text-gray-200 p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)] no-scrollbar" id="hrEmployeeDetailsContent">
                        <!-- Personal Information Section -->
                        <div class="mb-6">
                            <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    PERSONAL INFORMATION
                                </h3>
                                <div class="grid grid-cols-3 gap-6">
                                    <!-- Row: ID Number, First Name, Middle Name -->
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">First Name</label>
                                        <input type="text" id="first_name_${employee.id_number}" value="${employee.first_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                                                        <div>
                                        <label class="block text-sm font-semibold mb-1">Last Name</label>
                                        <input type="text" id="last_name_${employee.id_number}" value="${employee.last_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Middle Name</label>
                                        <input type="text" id="middle_name_${employee.id_number}" value="${employee.middle_name || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    
                                    <!-- Row: Last Name, Position, Department -->
                                                                        <div>
                                        <label class="block text-sm font-semibold mb-1">Employee ID</label>
                                        <input type="text" value="${employee.id_number}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field employee-id-readonly cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Position</label>
                                        <input type="text" id="position_${employee.id_number}" value="${employee.position || 'HR Staff'}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Department</label>
                                        <input type="text" id="department_${employee.id_number}" value="${employee.department || 'Human Resources'}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    
                                    <!-- Row: Email, Phone, Hire Date -->
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Email</label>
                                        <input type="email" id="email_${employee.id_number}" value="${employee.email || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Phone</label>
                                        <input type="text" id="phone_${employee.id_number}" value="${employee.phone || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1">Hire Date</label>
                                        <input type="date" id="hire_date_${employee.id_number}" value="${employee.hire_date || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field hire-date-readonly cursor-not-allowed">
                                    </div>
                                </div>
                                
                                <!-- Complete Address -->
                                <div class="mt-6">
                                    <label class="block text-sm font-semibold mb-1">Complete Address</label>
                                    <textarea id="address_${employee.id_number}" rows="3" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 hr-employee-field">${employee.address || ''}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Account Section -->
                        <div class="mb-6">
                            <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    PERSONAL ACCOUNT
                                </h3>
                                ${employee.username ? `
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold mb-1">Username</label>
                                            <input type="text" value="${employee.username}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 username-field-readonly cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold mb-1">Password</label>
                                            <input type="password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50">
                                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                                        </div>
                                    </div>
                                ` : `
                                    <div class="text-center py-8">
                                        <p class="text-gray-600 text-lg mb-6">This employee doesn't have a system account.</p>
                                        <button onclick="createAccountForEmployee('${employee.id_number}')" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2 mx-auto">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Create Account
                                        </button>
                                    </div>
                                `}
                            </div>
                        </div>
                        

                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentHRModal = modal;
        }

        function closeHRModal() {
            if (window.currentHRModal) {
                document.body.removeChild(window.currentHRModal);
                window.currentHRModal = null;
            }
        }

        function getCurrentHREmployeeId() {
            return currentHREmployeeId;
        }

        function toggleHREditMode() {
            const editBtn = document.getElementById('editHREmployeeBtn');
            const fields = document.querySelectorAll('.hr-employee-field');
            const isEditing = editBtn.textContent === 'Cancel';
            
            if (!isEditing) {
                // Enter edit mode
                editBtn.textContent = 'Cancel';
                editBtn.className = 'px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition';
                
                // Show save button and hide delete button
                const saveBtn = document.getElementById('saveHRChangesBtn');
                const deleteBtn = document.getElementById('deleteHREmployeeBtn');
                if (saveBtn) saveBtn.classList.remove('hidden');
                if (deleteBtn) deleteBtn.classList.add('hidden');
                
                // Enable fields for editing (except Employee ID, Username, and Hire Date which should always be readonly)
                fields.forEach(field => {
                    // Skip Employee ID field - it should NEVER be editable
                    const isEmployeeIdField = field.classList.contains('employee-id-readonly');
                    // Skip Username field - it should NEVER be editable
                    const isUsernameField = field.classList.contains('username-field-readonly');
                    // Skip Hire Date field - it should NEVER be editable
                    const isHireDateField = field.classList.contains('hire-date-readonly');
                    
                    if (!isEmployeeIdField && !isUsernameField && !isHireDateField && (['TEXTAREA'].includes(field.tagName) || ['text', 'email', 'date', 'tel'].includes(field.type))) {
                        field.readOnly = false;
                        field.classList.remove('bg-gray-50');
                        field.classList.remove('cursor-not-allowed');
                        field.classList.add('bg-white');
                        field.classList.add('focus:ring-2', 'focus:ring-[#0B2C62]', 'focus:border-[#0B2C62]');
                        // Clear any previous error styling
                        field.classList.remove('border-red-500');
                    }
                });
            } else {
                // Cancel editing - reset to read-only mode
                editBtn.textContent = 'Edit';
                editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
                
                // Hide save button and show delete button
                const saveBtn = document.getElementById('saveHRChangesBtn');
                const deleteBtn = document.getElementById('deleteHREmployeeBtn');
                if (saveBtn) saveBtn.classList.add('hidden');
                if (deleteBtn) deleteBtn.classList.remove('hidden');
                
                // Disable fields and restore read-only appearance
                fields.forEach(field => {
                    if (['TEXTAREA'].includes(field.tagName) || ['text', 'email', 'date', 'tel'].includes(field.type)) {
                        field.readOnly = true;
                        field.classList.add('bg-gray-50');
                        field.classList.remove('bg-white');
                        field.classList.remove('focus:ring-2', 'focus:ring-blue-600', 'focus:border-blue-600');
                    }
                });
                
                // Reset form values to original values without reloading modal
                // The original values are already stored in the form, so no need to reload
            }
        }

        function showSaveConfirmation() {
            const c = document.createElement('div');
            c.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
            c.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold mb-2">Confirm Changes</h3>
                    <p class="text-gray-600 mb-6">Are you sure you want to save these changes to the HR employee information?</p>
                    <div class="flex justify-end gap-2">
                        <button id="cCancel" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded">Cancel</button>
                        <button id="cSave" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirm & Save</button>
                    </div>
                </div>
            `;
            document.body.appendChild(c);
            c.querySelector('#cCancel').onclick = () => c.remove();
            c.querySelector('#cSave').onclick = () => {
                c.remove();
                saveHREmployeeChanges();
            };
        }

        function saveHREmployeeChanges() {
            if (!currentHREmployeeId) {
                showToast('No HR employee selected', 'error');
                return;
            }
            
            // Collect employee data from the form fields
            const firstName = document.getElementById(`first_name_${currentHREmployeeId}`)?.value;
            const middleName = document.getElementById(`middle_name_${currentHREmployeeId}`)?.value;
            const lastName = document.getElementById(`last_name_${currentHREmployeeId}`)?.value;
            const position = document.getElementById(`position_${currentHREmployeeId}`)?.value;
            const department = document.getElementById(`department_${currentHREmployeeId}`)?.value;
            const email = document.getElementById(`email_${currentHREmployeeId}`)?.value;
            const phone = document.getElementById(`phone_${currentHREmployeeId}`)?.value;
            const hireDate = document.getElementById(`hire_date_${currentHREmployeeId}`)?.value;
            const address = document.getElementById(`address_${currentHREmployeeId}`)?.value;
            
            // Validate required fields
            if (!firstName || !lastName || !position || !department || !hireDate) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('employee_id', currentHREmployeeId);
            formData.append('first_name', firstName);
            formData.append('middle_name', middleName || '');
            formData.append('last_name', lastName);
            formData.append('position', position);
            formData.append('department', department);
            formData.append('email', email || '');
            formData.append('phone', phone || '');
            formData.append('hire_date', hireDate);
            formData.append('address', address || '');
            
            fetch('edit_hr_employee.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('HR Employee updated successfully!', 'success');
                    toggleHREditMode(); // Exit edit mode
                    
                    // Refresh the modal data without closing it
                    fetch(`view_hr_employee.php?id=${currentHREmployeeId}`)
                        .then(response => response.json())
                        .then(refreshData => {
                            if (refreshData.success) {
                                // Update all field values with fresh data
                                const emp = refreshData.employee;
                                document.getElementById(`first_name_${currentHREmployeeId}`).value = emp.first_name || '';
                                document.getElementById(`middle_name_${currentHREmployeeId}`).value = emp.middle_name || '';
                                document.getElementById(`last_name_${currentHREmployeeId}`).value = emp.last_name || '';
                                document.getElementById(`position_${currentHREmployeeId}`).value = emp.position || '';
                                document.getElementById(`department_${currentHREmployeeId}`).value = emp.department || '';
                                document.getElementById(`email_${currentHREmployeeId}`).value = emp.email || '';
                                document.getElementById(`phone_${currentHREmployeeId}`).value = emp.phone || '';
                                document.getElementById(`hire_date_${currentHREmployeeId}`).value = emp.hire_date || '';
                                document.getElementById(`address_${currentHREmployeeId}`).value = emp.address || '';
                            }
                        });
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating the HR employee', 'error');
            });
        }

        // Delete HR Employee (entire record) handlers
        function showDeleteHREmployeeConfirmation(employeeId) {
            if (confirm('⚠️ DELETE HR EMPLOYEE WARNING ⚠️\n\nAre you sure you want to delete this HR employee record?\n\nEmployee ID: ' + employeeId + '\n\nThis action:\n• Cannot be undone\n• Will remove all associated data\n• Requires School Owner approval\n• Will be logged for audit purposes\n\nThis will permanently remove the HR employee from the system.')) {
                const confirmation = prompt('To confirm deletion, type "DELETE" (in capital letters):');
                
                if (confirmation === "DELETE") {
                    alert('HR Employee deletion request submitted.\n\nThe School Owner will be notified for final approval.\n\nThis action has been logged for audit purposes.');
                    // Here you would make an AJAX call to request permanent deletion
                    closeHRModal();
                } else {
                    alert('HR Employee deletion cancelled.\n\nThe record has not been deleted.');
                }
            }
        }

        function deleteHRAccount(employeeId) {
            if (confirm('Are you sure you want to delete this HR employee?\n\nThis action requires School Owner approval and cannot be undone.')) {
                alert('Delete HR Employee request submitted.\n\nThe School Owner will be notified for approval.');
            }
        }

        function addHRAccount() {
            // Create modal for adding new HR employee
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    <!-- Header -->
                    <div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center justify-between rounded-t-lg">
                        <h3 class="text-xl font-semibold">Add New Employee</h3>
                        <button onclick="closeAddHRModal()" class="text-white hover:text-gray-200 p-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-80px)] no-scrollbar">
                        <form method="POST" action="add_hr_employee.php" autocomplete="off">
                            <!-- Personal Information Section -->
                            <div class="border border-gray-300 rounded-lg p-6 mb-6">
                                <div class="flex items-center gap-2 mb-6">
                                    <svg class="w-5 h-5 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">PERSONAL INFORMATION</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                        <input type="text" name="first_name" autocomplete="off" pattern="[A-Za-z\\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm name-input" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)">
                                    </div>
                                                                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                        <input type="text" name="last_name" autocomplete="off" pattern="[A-Za-z\\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm name-input" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name <span class="text-gray-500">(Optional)</span></label>
                                        <input type="text" name="middle_name" autocomplete="off" pattern="[A-Za-z\\s]*" maxlength="20" title="Letters only, maximum 20 characters" placeholder="Optional" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm name-input" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)">
                                    </div>
                                                                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                                        <input type="text" name="id_number" id="auto_employee_id" autocomplete="off" required readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-600" value="Loading...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                                        <input type="text" name="position" autocomplete="off" pattern="[A-Za-z\\s]+" maxlength="50" title="Letters only, maximum 50 characters" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm name-input" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 50)">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                        <input type="text" name="department" value="Human Resources" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date *</label>
                                        <input type="date" name="hire_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                        <input type="email" name="email" autocomplete="off" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                        <input type="text" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" title="Please enter exactly 11 digits (e.g., 09123456789)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Complete Address *</label>
                                    <textarea name="address" rows="3" autocomplete="off" required minlength="20" maxlength="500" placeholder="Enter complete address (e.g., Block 8, Lot 15, Subdivision Name, Barangay, City, Province)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm" title="Please enter a complete address with at least 20 characters including street, barangay, city/municipality, and province."></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Minimum 20 characters. Include street, barangay, city/municipality, and province.</p>
                                </div>
                            </div>
                            
                            <!-- System Account Section -->
                            <div class="border border-gray-300 rounded-lg p-6">
                                <div class="flex items-center gap-2 mb-6">
                                    <svg class="w-5 h-5 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">SYSTEM ACCOUNT</h4>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="createAccount" name="create_account" class="mr-2 rounded border-gray-300 text-[#0B2C62] focus:ring-[#0B2C62]">
                                        <span class="text-sm text-gray-700">Create system account for this employee</span>
                                    </label>
                                </div>
                                
                                <div id="accountFields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                                        <input type="text" id="usernameField" name="username" autocomplete="off" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-600">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                        <input type="text" id="passwordField" name="password" autocomplete="new-password" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-600">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex justify-end gap-3 pt-6">
                                <button type="button" onclick="closeAddHRModal()" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="button" onclick="confirmAddEmployee()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                                    Add Employee
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentAddHRModal = modal;
            
            // Fetch next employee ID
            fetch('get_next_employee_id.php')
                .then(response => response.json())
                .then(data => {
                    const employeeIdField = document.getElementById('auto_employee_id');
                    if (employeeIdField && data.next_id) {
                        employeeIdField.value = data.next_id;
                        // Trigger username/password generation after employee ID is loaded
                        generateUsernameAndPassword();
                    }
                })
                .catch(error => {
                    console.error('Error fetching employee ID:', error);
                    const employeeIdField = document.getElementById('auto_employee_id');
                    if (employeeIdField) {
                        employeeIdField.value = 'Error loading ID';
                    }
                });
            
            // Auto-generate username and password based on last name and employee ID
            function generateUsernameAndPassword() {
                const lastNameField = modal.querySelector('input[name="last_name"]');
                const employeeIdField = document.getElementById('auto_employee_id');
                const usernameField = document.getElementById('usernameField');
                const passwordField = document.getElementById('passwordField');
                
                if (!lastNameField || !employeeIdField || !usernameField || !passwordField) return;
                
                const lastName = lastNameField.value.trim().toLowerCase();
                const employeeId = employeeIdField.value.trim();
                
                if (lastName && employeeId) {
                    // Extract the 3-digit number from employee ID (e.g., CCI2025-006 -> 006)
                    const parts = employeeId.split('-');
                    const idNumber = parts.length === 2 ? parts[1] : '000';
                    
                    // Get current year
                    const currentYear = new Date().getFullYear();
                    
                    // Format username: lastname006muzon@employee.cci.edu.ph
                    const username = lastName + idNumber + 'muzon@employee.cci.edu.ph';
                    usernameField.value = username;
                    
                    // Format password: lastname0062025
                    const password = lastName + idNumber + currentYear;
                    passwordField.value = password;
                } else {
                    usernameField.value = '';
                    passwordField.value = '';
                }
            }
            
            // Setup auto-generation on last name change
            const lastNameField = modal.querySelector('input[name="last_name"]');
            if (lastNameField) {
                lastNameField.addEventListener('input', generateUsernameAndPassword);
                lastNameField.addEventListener('blur', generateUsernameAndPassword);
            }
            
            // Handle checkbox toggle
            document.getElementById('createAccount').addEventListener('change', function() {
                const accountFields = document.getElementById('accountFields');
                const usernameField = document.querySelector('input[name="username"]');
                const passwordField = document.querySelector('input[name="password"]');
                
                if (this.checked) {
                    accountFields.style.display = 'grid';
                    usernameField.required = true;
                    passwordField.required = true;
                } else {
                    accountFields.style.display = 'none';
                    usernameField.required = false;
                    passwordField.required = false;
                }
            });
            
            // Initially hide account fields since checkbox is unchecked by default
            const accountFields = document.getElementById('accountFields');
            const usernameField = document.querySelector('input[name="username"]');
            const passwordField = document.querySelector('input[name="password"]');
            accountFields.style.display = 'none';
            usernameField.required = false;
            passwordField.required = false;
        }

        function confirmAddEmployee() {
            const form = window.currentAddHRModal.querySelector('form');
            
            // Clear previous error states
            clearFieldErrors(form);
            
            // Get form values
            const idNumber = form.querySelector('input[name="id_number"]').value.trim();
            const firstName = form.querySelector('input[name="first_name"]').value.trim();
            const middleName = form.querySelector('input[name="middle_name"]').value.trim();
            const lastName = form.querySelector('input[name="last_name"]').value.trim();
            const position = form.querySelector('input[name="position"]').value.trim();
            const department = form.querySelector('input[name="department"]').value.trim();
            const email = form.querySelector('input[name="email"]').value.trim();
            const phone = form.querySelector('input[name="phone"]').value.trim();
            const address = form.querySelector('textarea[name="address"]').value.trim();
            const hireDate = form.querySelector('input[name="hire_date"]').value;
            const createAccount = form.querySelector('input[name="create_account"]').checked;
            const username = form.querySelector('input[name="username"]').value.trim();
            const password = form.querySelector('input[name="password"]').value;
            
            let hasErrors = false;
            
            // Basic validation with field highlighting
            if (!idNumber) {
                highlightFieldError(form.querySelector('input[name="id_number"]'), 'Employee ID is required');
                hasErrors = true;
            }
            if (!firstName) {
                highlightFieldError(form.querySelector('input[name="first_name"]'), 'First name is required');
                hasErrors = true;
            }
            if (!lastName) {
                highlightFieldError(form.querySelector('input[name="last_name"]'), 'Last name is required');
                hasErrors = true;
            }
            if (!position) {
                highlightFieldError(form.querySelector('input[name="position"]'), 'Position is required');
                hasErrors = true;
            }
            if (!email) {
                highlightFieldError(form.querySelector('input[name="email"]'), 'Email is required');
                hasErrors = true;
            }
            if (!phone) {
                highlightFieldError(form.querySelector('input[name="phone"]'), 'Phone is required');
                hasErrors = true;
            }
            if (!address) {
                highlightFieldError(form.querySelector('textarea[name="address"]'), 'Address is required');
                hasErrors = true;
            } else if (address.length < 20) {
                highlightFieldError(form.querySelector('textarea[name="address"]'), 'Complete address must be at least 20 characters long');
                hasErrors = true;
            } else if (address.length > 500) {
                highlightFieldError(form.querySelector('textarea[name="address"]'), 'Complete address must not exceed 500 characters');
                hasErrors = true;
            } else if (!/.*[,\s].*/i.test(address)) {
                highlightFieldError(form.querySelector('textarea[name="address"]'), 'Complete address must include multiple components (street, barangay, city, etc.) separated by commas or spaces.');
                hasErrors = true;
            }
            if (!hireDate) {
                highlightFieldError(form.querySelector('input[name="hire_date"]'), 'Hire date is required');
                hasErrors = true;
            }
            
            // Email validation
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                highlightFieldError(form.querySelector('input[name="email"]'), 'Please enter a valid email address');
                hasErrors = true;
            }
            
            // Phone validation
            if (phone && !/^[0-9]{11}$/.test(phone)) {
                highlightFieldError(form.querySelector('input[name="phone"]'), 'Phone must be exactly 11 digits');
                hasErrors = true;
            }
            
            // Account validation if creating account
            if (createAccount) {
                if (!username) {
                    highlightFieldError(form.querySelector('input[name="username"]'), 'Username is required when creating system account');
                    hasErrors = true;
                }
                if (!password) {
                    highlightFieldError(form.querySelector('input[name="password"]'), 'Password is required when creating system account');
                    hasErrors = true;
                }
            }
            
            if (hasErrors) {
                return;
            }
            
            // Build full name
            const fullName = firstName + (middleName ? ' ' + middleName : '') + ' ' + lastName;
            
            // Show custom confirmation modal (use unique name to avoid collision with generic modal)
            showEmployeeConfirmationModal({
                idNumber,
                fullName,
                position,
                department,
                email,
                phone,
                address,
                hireDate,
                createAccount,
                username,
                password,
                form
            });
        }

        function showEmployeeConfirmationModal(data) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-70 z-[60] flex items-center justify-center p-4';
            
            const formattedDate = new Date(data.hireDate).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden border-2 border-gray-200">
                    <!-- Header -->
                    <div class="bg-[#0B2C62] text-white px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">Confirm Employee Creation</h3>
                        </div>
                        <button onclick="closeConfirmationModal()" class="text-white hover:text-gray-200 p-2 rounded-lg hover:bg-white/10 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 max-h-[calc(90vh-160px)] overflow-y-auto bg-gray-50">
                        <!-- Personal Information -->
                        <div class="mb-6">
                            <div class="flex items-center gap-3 mb-4 p-3 bg-[#0B2C62]/5 rounded-lg">
                                <div class="w-8 h-8 bg-[#0B2C62] rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-[#0B2C62]">Personal Information</h4>
                            </div>
                            <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Employee ID:</span> 
                                        <span class="text-[#0B2C62] font-medium text-lg">${data.idNumber}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Full Name:</span> 
                                        <span class="text-[#0B2C62] font-medium text-lg">${data.fullName}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Position:</span> 
                                        <span class="text-gray-900 font-medium">${data.position}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Department:</span> 
                                        <span class="text-gray-900 font-medium">${data.department}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Email:</span> 
                                        <span class="text-gray-900 font-medium">${data.email}</span>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <span class="font-semibold text-gray-800 block">Phone:</span> 
                                        <span class="text-gray-900 font-medium">${data.phone}</span>
                                    </div>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-semibold text-gray-800 block">Address:</span> 
                                    <span class="text-gray-900 font-medium">${data.address}</span>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-semibold text-gray-800 block">Hire Date:</span> 
                                    <span class="text-gray-900 font-medium">${formattedDate}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Account -->
                        <div class="mb-6">
                            <div class="flex items-center gap-3 mb-4 p-3 bg-[#0B2C62]/5 rounded-lg">
                                <div class="w-8 h-8 bg-[#0B2C62] rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-[#0B2C62]">System Account</h4>
                            </div>
                            <div class="bg-white rounded-lg p-5 shadow-sm border-2 border-gray-300 space-y-4">
                                ${data.createAccount ? `
                                    <div class="flex items-center gap-3 mb-4">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">System account will be created</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <span class="font-semibold text-gray-700 block">Username:</span> 
                                            <span class="text-gray-900 font-medium">${data.username}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-gray-700 block">Password:</span> 
                                            <span class="text-gray-900 font-medium">${'•'.repeat(data.password.length)} (${data.password.length} chars)</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-gray-700 block">Role:</span> 
                                            <span class="text-gray-900 font-medium">HR</span>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 text-sm pt-2 border-t border-gray-200">This employee will have login access to the HR system.</p>
                                ` : `
                                    <div class="flex items-center gap-3 mb-4">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900">Employee record only</span>
                                    </div>
                                    <p class="text-gray-700 text-sm">No system account will be created. Employee will NOT have login access.</p>
                                `}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="bg-white border-t-2 border-gray-200 px-6 py-5 pb-8 flex justify-end gap-4">
                        <button onclick="closeConfirmationModal()" class="px-8 py-3 border-2 border-gray-400 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors font-medium text-lg">
                            Cancel
                        </button>
                        <button onclick="proceedWithCreation()" class="px-8 py-3 bg-[#0B2C62] hover:bg-[#153e86] text-white rounded-lg transition-all duration-200 flex items-center gap-3 font-bold text-lg shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Confirm & Create Employee</span>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentConfirmationModal = modal;
            window.confirmationData = data;
        }

        function closeConfirmationModal() {
            if (window.currentConfirmationModal) {
                document.body.removeChild(window.currentConfirmationModal);
                window.currentConfirmationModal = null;
                window.confirmationData = null;
            }
        }

        function proceedWithCreation() {
            if (window.confirmationData) {
                // Show loading state
                const submitBtn = document.querySelector('button[onclick="proceedWithCreation()"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Creating Employee...
                `;
                
                // Submit the form (this will create the employee and redirect back to HR accounts)
                window.confirmationData.form.submit();
                
                // Close both modals
                closeConfirmationModal();
                closeAddHRModal();
            }
        }

        function highlightFieldError(field, message) {
            if (!field) return;
            
            // Add error styling
            field.classList.add('border-red-500', 'bg-red-50');
            field.classList.remove('border-gray-300');
            
            // Remove existing error message
            const existingError = field.parentElement.querySelector('.field-error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error message below field
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error-message text-red-600 text-xs mt-1 flex items-center gap-1';
            errorDiv.innerHTML = `
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                ${message}
            `;
            field.parentElement.appendChild(errorDiv);
            
            // Add event listener to clear error on input
            field.addEventListener('input', function clearError() {
                field.classList.remove('border-red-500', 'bg-red-50');
                field.classList.add('border-gray-300');
                const errorMsg = field.parentElement.querySelector('.field-error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
                field.removeEventListener('input', clearError);
            });
        }

        function clearFieldErrors(form) {
            // Remove all error styling and messages
            const errorFields = form.querySelectorAll('.border-red-500');
            errorFields.forEach(field => {
                field.classList.remove('border-red-500', 'bg-red-50');
                field.classList.add('border-gray-300');
            });
            
            const errorMessages = form.querySelectorAll('.field-error-message');
            errorMessages.forEach(msg => msg.remove());
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-[70] max-w-md w-full transform translate-x-full opacity-0 transition-all duration-300`;
            
            const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';
            const icon = type === 'error' ? 
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' :
                type === 'success' ?
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            
            notification.innerHTML = `
                <div class="${bgColor} text-white px-6 py-4 rounded-lg shadow-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            ${icon}
                        </svg>
                        <span class="font-medium">${message}</span>
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="ml-auto text-white hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }

        function closeAddHRModal() {
            if (window.currentAddHRModal) {
                document.body.removeChild(window.currentAddHRModal);
                window.currentAddHRModal = null;
            }
        }



        // Modal Functions for Better Notifications
        function showConfirmationModal({title, message, details = [], confirmText = 'Confirm', cancelText = 'Cancel', type = 'info', onConfirm = null}) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const typeColors = {
                info: 'border-blue-500 text-blue-600',
                warning: 'border-yellow-500 text-yellow-600',
                danger: 'border-red-500 text-red-600',
                success: 'border-green-500 text-green-600'
            };
            
            const buttonColors = {
                info: 'bg-blue-600 hover:bg-blue-700',
                warning: 'bg-yellow-600 hover:bg-yellow-700',
                danger: 'bg-red-600 hover:bg-red-700',
                success: 'bg-green-600 hover:bg-green-700'
            };
            
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full border-2 ${typeColors[type]} flex items-center justify-center mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    ${type === 'danger' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>' : 
                                      type === 'warning' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>' :
                                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                        </div>
                        <p class="text-gray-600 mb-4">${message}</p>
                        ${details.length > 0 ? `
                            <ul class="text-sm text-gray-500 space-y-1 mb-6">
                                ${details.map(detail => `<li>• ${detail}</li>`).join('')}
                            </ul>
                        ` : ''}
                        <div class="flex justify-end gap-3">
                            <button onclick="closeConfirmationModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                ${cancelText}
                            </button>
                            <button onclick="confirmAction()" class="px-4 py-2 ${buttonColors[type]} text-white rounded-lg transition-colors">
                                ${confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentConfirmationModal = modal;
            window.currentConfirmAction = onConfirm;
        }
        
        function showNotificationModal({title, message, details = [], type = 'info'}) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const typeColors = {
                info: 'border-blue-500 text-blue-600',
                warning: 'border-yellow-500 text-yellow-600',
                error: 'border-red-500 text-red-600',
                success: 'border-green-500 text-green-600'
            };
            
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full border-2 ${typeColors[type]} flex items-center justify-center mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    ${type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' : 
                                      type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                        </div>
                        <p class="text-gray-600 mb-4">${message}</p>
                        ${details.length > 0 ? `
                            <ul class="text-sm text-gray-500 space-y-1 mb-6">
                                ${details.map(detail => `<li>• ${detail}</li>`).join('')}
                            </ul>
                        ` : ''}
                        <div class="flex justify-end">
                            <button onclick="closeNotificationModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentNotificationModal = modal;
        }
        
        function confirmAction() {
            if (window.currentConfirmAction) {
                window.currentConfirmAction();
            }
            closeConfirmationModal();
        }
        
        function closeConfirmationModal() {
            if (window.currentConfirmationModal) {
                document.body.removeChild(window.currentConfirmationModal);
                window.currentConfirmationModal = null;
                window.currentConfirmAction = null;
            }
        }
        
        function closeNotificationModal() {
            if (window.currentNotificationModal) {
                document.body.removeChild(window.currentNotificationModal);
                window.currentNotificationModal = null;
            }
        }

        // System Maintenance Functions
        function toggleMaintenance() {
            // Toggle works silently - no notifications
            // Confirmation/warning only appears when clicking "Update Configuration"
        }

        function updateConfiguration() {
            const toggle = document.getElementById('maintenanceToggle');
            const maintenanceStatus = toggle.checked ? 'enabled' : 'disabled';
            
            showConfirmationModal({
                title: 'Update System Configuration',
                message: `Are you sure you want to update the system configuration?`,
                details: [
                    `Maintenance mode will be: <strong>${maintenanceStatus}</strong>`,
                    'All changes will be logged for audit purposes'
                ],
                confirmText: 'Update Configuration',
                cancelText: 'Cancel',
                type: 'warning',
                onConfirm: () => {
                    fetch('update_configuration.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ maintenance_mode: maintenanceStatus })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update system status displays dynamically
                            const statusText = maintenanceStatus === 'enabled' ? '🔴 Maintenance' : '🟢 Online';
                            const statusColor = maintenanceStatus === 'enabled' ? 'text-red-600' : 'text-green-600';
                            
                            // Update all system status displays on the page
                            document.querySelectorAll('.system-status-display').forEach(el => {
                                el.textContent = statusText;
                                // Preserve existing classes and update color
                                el.classList.remove('text-red-600', 'text-green-600');
                                el.classList.add(statusColor.replace('text-', '').split('-')[0] === 'red' ? 'text-red-600' : 'text-green-600');
                            });
                            
                            showNotificationModal({
                                title: 'Configuration Updated',
                                message: data.message,
                                details: [
                                    `Maintenance mode: ${data.maintenance_mode}`,
                                    `System status updated to: ${statusText}`,
                                    'Changes applied successfully'
                                ],
                                type: 'success'
                            });
                        } else {
                            showNotificationModal({
                                title: 'Update Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while updating configuration',
                            type: 'error'
                        });
                    });
                }
            });
        }

        function createDatabaseBackup() {
            showConfirmationModal({
                title: 'Create Database Backup',
                message: 'Are you sure you want to create a complete database backup?',
                details: [
                    'This will include all tables and data',
                    'The process may take a few minutes',
                    'Backup file will be created with timestamp'
                ],
                confirmText: 'Create Backup',
                cancelText: 'Cancel',
                type: 'info',
                onConfirm: () => {
                    fetch('create_backup.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotificationModal({
                                title: 'Backup Created Successfully',
                                message: data.message,
                                details: [
                                    `Filename: ${data.filename}`,
                                    `Size: ${data.size}`,
                                    'Backup stored in /backups directory'
                                ],
                                type: 'success'
                            });
                        } else {
                            showNotificationModal({
                                title: 'Backup Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while creating backup',
                            type: 'error'
                        });
                    });
                }
            });
        }

        function clearLoginLogs() {
            const startDate = document.getElementById('loginStartDate').value;
            const endDate = document.getElementById('loginEndDate').value;
            
            if (!startDate || !endDate) {
                showNotificationModal({
                    title: 'Missing Information',
                    message: 'Please select both start and end dates before proceeding.',
                    details: [
                        'Start date is required',
                        'End date is required',
                        'Date range cannot be empty'
                    ],
                    type: 'error'
                });
                return;
            }
            
            showConfirmationModal({
                title: 'Clear Login Logs',
                message: `Are you sure you want to clear all login logs between ${startDate} and ${endDate}?`,
                details: [
                    'Records will be exported to CSV before deletion',
                    'CSV file will download automatically',
                    'Records will be permanently deleted from database'
                ],
                confirmText: 'Clear Logs',
                cancelText: 'Cancel',
                type: 'danger',
                onConfirm: () => {
                    fetch('clear_login_logs.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            start_date: startDate, 
                            end_date: endDate 
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Trigger CSV download if data exists
                            if (data.csv_data && data.filename) {
                                const csvContent = atob(data.csv_data);
                                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                                const link = document.createElement('a');
                                link.href = URL.createObjectURL(blob);
                                link.download = data.filename;
                                link.click();
                            }
                            
                            const details = [
                                `Records deleted: ${data.records_deleted}`,
                                `Date range: ${startDate} to ${endDate}`
                            ];
                            if (data.csv_data) {
                                details.push(`✅ CSV file downloaded: ${data.filename}`);
                                details.push('📥 Check your Downloads folder');
                            }
                            showNotificationModal({
                                title: 'Login Logs Cleared',
                                message: data.message,
                                details: details,
                                type: 'success'
                            });
                            // Clear the date inputs
                            document.getElementById('loginStartDate').value = '';
                            document.getElementById('loginEndDate').value = '';
                        } else{
                            showNotificationModal({
                                title: 'Clear Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while clearing login logs',
                            type: 'error'
                        });
                    });
                }
            });
        }

        function clearAttendanceRecords() {
            const startDate = document.getElementById('attendanceStartDate').value;
            const endDate = document.getElementById('attendanceEndDate').value;
            
            if (!startDate || !endDate) {
                showNotificationModal({
                    title: 'Missing Information',
                    message: 'Please select both start and end dates before proceeding.',
                    details: [
                        'Start date is required',
                        'End date is required',
                        'Date range cannot be empty'
                    ],
                    type: 'error'
                });
                return;
            }
            
            showConfirmationModal({
                title: 'Clear Attendance Records',
                message: `Are you sure you want to clear all attendance records between ${startDate} and ${endDate}?`,
                details: [
                    'Records will be exported to CSV before deletion',
                    'CSV file will download automatically',
                    'Records will be permanently deleted from database'
                ],
                confirmText: 'Clear Records',
                cancelText: 'Cancel',
                type: 'danger',
                onConfirm: () => {
                    fetch('clear_attendance_records.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            start_date: startDate, 
                            end_date: endDate 
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Trigger CSV download if data exists
                            if (data.csv_data && data.filename) {
                                const csvContent = atob(data.csv_data);
                                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                                const link = document.createElement('a');
                                link.href = URL.createObjectURL(blob);
                                link.download = data.filename;
                                link.click();
                            }
                            
                            const details = [
                                `Records deleted: ${data.records_deleted}`,
                                `Date range: ${startDate} to ${endDate}`
                            ];
                            if (data.csv_data) {
                                details.push(`✅ CSV file downloaded: ${data.filename}`);
                                details.push('📥 Check your Downloads folder');
                            }
                            showNotificationModal({
                                title: 'Attendance Records Cleared',
                                message: data.message,
                                details: details,
                                type: 'success'
                            });
                            // Clear the date inputs
                            document.getElementById('attendanceStartDate').value = '';
                            document.getElementById('attendanceEndDate').value = '';
                        } else {
                            showNotificationModal({
                                title: 'Clear Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while clearing attendance records',
                            type: 'error'
                        });
                    });
                }
            });
        }

        // Deleted Items Functions - Working with existing backend
function restoreStudent(studentId) {
    showConfirmationModal({
        title: 'Restore Student Record',
        message: 'Are you sure you want to restore this student record?',
        details: [
            'The student will be reactivated',
            'Student will appear in Registrar system',
            'All data will be restored'
        ],
        confirmText: 'Restore',
        cancelText: 'Cancel',
        type: 'info',
        onConfirm: () => {
            fetch('restore_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'student', 
                    record_id: studentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal({
                        title: 'Student Restored',
                        message: data.message,
                        details: [
                            `Student ID: ${studentId}`,
                            'Record is now active',
                            'Visible in Registrar system'
                        ],
                        type: 'success'
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotificationModal({
                        title: 'Restore Failed',
                        message: data.message,
                        type: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal({
                    title: 'Error',
                    message: 'An error occurred while restoring the record',
                    type: 'error'
                });
            });
        }
    });
}

function restoreEmployee(employeeId) {
    showConfirmationModal({
        title: 'Restore Employee Record',
        message: 'Are you sure you want to restore this employee record?',
        details: [
            'The employee will be reactivated',
            'Employee will appear in HR system',
            'All data will be restored'
        ],
        confirmText: 'Restore',
        cancelText: 'Cancel',
        type: 'info',
        onConfirm: () => {
            fetch('restore_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'employee',
                    record_id: employeeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal({
                        title: 'Employee Restored',
                        message: data.message,
                        details: [
                            `Employee ID: ${employeeId}`,
                            'Record is now active',
                            'Visible in HR system'
                        ],
                        type: 'success'
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotificationModal({
                        title: 'Restore Failed',
                        message: data.message,
                        type: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal({
                    title: 'Error',
                    message: 'An error occurred while restoring the record',
                    type: 'error'
                });
            });
        }
    });
}

function deletePermanently(recordId, recordType) {
    const reason = prompt('Please provide a reason for permanent deletion:');
    if (reason && reason.trim()) {
        if (confirm('This will send a request to the School Owner for approval. Continue?')) {
            fetch('request_permanent_delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'request_permanent_delete',
                    record_type: recordType,
                    record_id: recordId,
                    reason: reason.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Permanent deletion request sent to School Owner for approval.');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the request.');
            });
        }
    }
}

        // Prevent back button after logout
        window.addEventListener("pageshow", function(event) {
            if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
        });

        // Handle hash fragment on page load to show correct section
        document.addEventListener('DOMContentLoaded', function() {
            // Set maintenance toggle based on current status
            const isMaintenanceMode = <?= $is_maintenance ? 'true' : 'false' ?>;
            const toggle = document.getElementById('maintenanceToggle');
            if (toggle) {
                toggle.checked = isMaintenanceMode;
            }
            
            const hash = window.location.hash;
            if (hash === '#hr-accounts') {
                showSection('hr-accounts');
            }
        });

        function createAccountForEmployee(employeeId) {
            // Show a modal to create account for existing employee
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-70 z-[70] flex items-center justify-center p-4';
            
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border-2 border-gray-200">
                    <!-- Header -->
                    <div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
                        <h3 class="text-lg font-bold">Create System Account</h3>
                        <button onclick="closeCreateAccountModal()" class="text-white hover:text-gray-200 p-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6">
                        <p class="text-gray-600 mb-6">Create a system account for Employee ID: <strong>${employeeId}</strong></p>
                        
                        <form id="createAccountForm" action="create_employee_account.php" method="POST">
                            <input type="hidden" name="employee_id" value="${employeeId}">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Username</label>
                                    <input type="text" name="username" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0B2C62]">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Password</label>
                                    <input type="password" name="password" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0B2C62]">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Role</label>
                                    <select name="role" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0B2C62]">
                                        <option value="hr">HR</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" onclick="closeCreateAccountModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            window.currentCreateAccountModal = modal;
        }

        function closeCreateAccountModal() {
            if (window.currentCreateAccountModal) {
                document.body.removeChild(window.currentCreateAccountModal);
                window.currentCreateAccountModal = null;
            }
        }



        // Export deleted account to file
        function exportToFile(accountId, accountType) {
            if (!accountId || !accountType) {
                alert('Invalid account information');
                return;
            }

            // Show confirmation dialog
            const accountTypeText = accountType === 'student' ? 'Student' : 'Employee';
            const confirmMessage = `Export ${accountTypeText} Account to File\n\nAccount ID: ${accountId}\n\nThis will:\n• Create a comprehensive backup file with all account data\n• Include related records (grades, payments, attendance, etc.)\n• Save the file to your computer\n• Keep the record in the deleted items list\n\nDo you want to proceed with the export?`;
            
            if (confirm(confirmMessage)) {
                // Show loading state
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Exporting...';
                button.disabled = true;
                button.classList.add('opacity-50');

                // Create download link
                const downloadUrl = `export_deleted_account.php?id=${encodeURIComponent(accountId)}&type=${encodeURIComponent(accountType)}`;
                
                // Create temporary link element for download
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.style.display = 'none';
                document.body.appendChild(link);
                
                // Trigger download
                link.click();
                
                // Clean up
                document.body.removeChild(link);
                
                // Reset button state after a delay
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                    button.classList.remove('opacity-50');
                    
                    // Show success message
                    alert(`${accountTypeText} account data has been exported successfully!\n\nThe file has been downloaded to your computer and contains:\n• Complete account information\n• All related records and history\n• Export timestamp and metadata\n\nThe account remains in the deleted items list for potential restoration.`);
                }, 1000);
            }
        }
    </script>



    <!-- Error Notification -->
    <?php if (!empty($error_msg)): ?>
    <div id="error-notif" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <?= htmlspecialchars($error_msg) ?>
        </div>
    </div>
    <script>
        // Show error notification
        const errorNotif = document.getElementById('error-notif');
        if (errorNotif) {
            setTimeout(() => {
                errorNotif.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            setTimeout(() => {
                errorNotif.classList.add('translate-x-full', 'opacity-0');
            }, 5000);
        }
    </script>
    <script src="assets/js/deleted-items-fix.js"></script>
    <?php endif; ?>
    
    <!-- Toast Notification -->
    <div id="toast" class="fixed top-5 right-5 z-[9999] hidden">
        <div id="toastInner" class="px-4 py-3 rounded shadow-lg text-white"></div>
    </div>
    <style>
        .toast-success { background-color: #10b981; }
        .toast-error { background-color: #ef4444; }
    </style>
    <script>
        function showToast(message, type='success'){
            const t = document.getElementById('toast');
            const ti = document.getElementById('toastInner');
            ti.className = 'px-4 py-3 rounded shadow-lg text-white ' + (type==='success'?'toast-success':'toast-error');
            ti.textContent = message;
            t.classList.remove('hidden');
            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(()=>{ t.classList.add('hidden'); }, 3000);
        }
        
        // Pagination for Not Logged In Today sections
        let employeesPage = 1;
        let studentsPage = 1;
        let employeesTotal = 0;
        let studentsTotal = 0;
        const itemsPerPage = 10;
        
        async function loadNotLoggedIn(type, page = 1) {
            const list = document.getElementById(`${type}-list`);
            const loading = document.getElementById(`${type}-loading`);
            const pagination = document.getElementById(`${type}-pagination`);
            
            if (!list || !loading) {
                console.error('Required elements not found:', {list, loading});
                return;
            }
            
            loading.classList.remove('hidden');
            
            try {
                const offset = (page - 1) * itemsPerPage;
                // Relative path - load_more_users.php is in the same directory
                const url = `load_more_users.php?type=${type}&offset=${offset}&limit=${itemsPerPage}`;
                console.log('Fetching:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Data received:', data);
                
                if (data.error) {
                    console.error('Error loading users:', data.error);
                    list.innerHTML = `<li class="text-center py-8"><p class="text-red-500 font-medium">Error: ${data.error}</p></li>`;
                    return;
                }
                
                // Clear list
                list.innerHTML = '';
                
                // Add items
                if (data.items.length === 0) {
                    const emptyIcon = type === 'employees' 
                        ? '<svg class="w-12 h-12 mx-auto mb-2 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'
                        : '<svg class="w-12 h-12 mx-auto mb-2 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>';
                    list.innerHTML = `<li class="text-center py-8">${emptyIcon}<p class="text-gray-500 font-medium">All ${type} have logged in today!</p><p class="text-gray-400 text-sm mt-1">Great attendance 🎉</p></li>`;
                    pagination.classList.add('hidden');
                } else {
                    data.items.forEach(item => {
                        const li = document.createElement('li');
                        const iconColor = type === 'employees' ? 'text-orange-500' : 'text-blue-500';
                        const bgColor = type === 'employees' ? 'hover:bg-orange-50' : 'hover:bg-blue-50';
                        const borderColor = type === 'employees' ? 'border-orange-100' : 'border-blue-100';
                        
                        li.className = `flex items-center gap-3 p-3 rounded-lg border ${borderColor} ${bgColor} transition-all`;
                        
                        // Extract name from item (format: "• Name (ID)" or "• First, Last (ID)")
                        // Remove bullet point and extract name before parentheses
                        const cleanItem = item.replace(/•\s*/, '').trim();
                        const nameMatch = cleanItem.match(/^([^(]+)/);
                        const name = nameMatch ? nameMatch[1].trim() : '';
                        
                        // Split by comma or space to get name parts
                        let nameParts;
                        if (name.includes(',')) {
                            // Format: "First, Last" - split by comma
                            nameParts = name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                        } else {
                            // Format: "First Last" - split by space
                            nameParts = name.split(' ').filter(p => p.length > 0);
                        }
                        
                        let initials = '?';
                        if (nameParts.length >= 2) {
                            // First letter of first name + first letter of last name
                            initials = (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
                        } else if (nameParts.length === 1 && nameParts[0].length >= 2) {
                            // If only one name, take first two letters
                            initials = nameParts[0].substring(0, 2).toUpperCase();
                        }
                        
                        li.innerHTML = `
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full ${type === 'employees' ? 'bg-orange-500' : 'bg-blue-500'} flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    ${initials}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${item.replace(/•\s*/, '')}</p>
                                <p class="text-xs text-gray-500">Not logged in today</p>
                            </div>
                        `;
                        list.appendChild(li);
                    });
                    
                    // Update pagination
                    const total = data.total || 0;
                    if (type === 'employees') {
                        employeesTotal = total;
                    } else {
                        studentsTotal = total;
                    }
                    
                    updatePagination(type, page, total);
                    
                    if (total > itemsPerPage) {
                        pagination.classList.remove('hidden');
                    } else {
                        pagination.classList.add('hidden');
                    }
                }
                
            } catch (error) {
                console.error('Error loading users:', error);
                list.innerHTML = `<li class="text-center py-8"><p class="text-red-500 font-medium">Error loading data</p><p class="text-gray-500 text-sm mt-1">${error.message}</p></li>`;
            } finally {
                if (loading) {
                    loading.classList.add('hidden');
                }
            }
        }
        
        function updatePagination(type, page, total) {
            const start = (page - 1) * itemsPerPage + 1;
            const end = Math.min(page * itemsPerPage, total);
            
            document.getElementById(`${type}-start`).textContent = start;
            document.getElementById(`${type}-end`).textContent = end;
            document.getElementById(`${type}-total`).textContent = total;
            
            const prevBtn = document.getElementById(`${type}-prev`);
            const nextBtn = document.getElementById(`${type}-next`);
            
            prevBtn.disabled = page === 1;
            nextBtn.disabled = end >= total;
        }
        
        function changeEmployeesPage(direction) {
            employeesPage += direction;
            if (employeesPage < 1) employeesPage = 1;
            loadNotLoggedIn('employees', employeesPage);
        }
        
        function changeStudentsPage(direction) {
            studentsPage += direction;
            if (studentsPage < 1) studentsPage = 1;
            loadNotLoggedIn('students', studentsPage);
        }
        
        // Pagination for Today's Logins
        let loginsPage = 1;
        const loginsPerPage = 10;
        
        function updateLoginsDisplay() {
            const rows = document.querySelectorAll('.login-row');
            const total = rows.length;
            
            if (total === 0) return;
            
            const start = (loginsPage - 1) * loginsPerPage;
            const end = start + loginsPerPage;
            
            rows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update pagination info only if elements exist
            const startEl = document.getElementById('logins-start');
            const endEl = document.getElementById('logins-end');
            const totalEl = document.getElementById('logins-total');
            const prevBtn = document.getElementById('logins-prev');
            const nextBtn = document.getElementById('logins-next');
            
            if (startEl) startEl.textContent = start + 1;
            if (endEl) endEl.textContent = Math.min(end, total);
            if (totalEl) totalEl.textContent = total;
            
            // Update button states
            if (prevBtn) prevBtn.disabled = loginsPage === 1;
            if (nextBtn) nextBtn.disabled = end >= total;
        }
        
        function changeLoginsPage(direction) {
            const rows = document.querySelectorAll('.login-row');
            const totalPages = Math.ceil(rows.length / loginsPerPage);
            
            loginsPage += direction;
            if (loginsPage < 1) loginsPage = 1;
            if (loginsPage > totalPages) loginsPage = totalPages;
            
            updateLoginsDisplay();
        }
        
        // Initialize pagination on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Today's Logins pagination only if pagination elements exist
            const loginsTable = document.getElementById('logins-table');
            const loginsPagination = document.getElementById('logins-start');
            if (loginsTable && loginsPagination) {
                updateLoginsDisplay();
            }
            
            // Initialize Not Logged In sections
            loadNotLoggedIn('employees', 1);
            loadNotLoggedIn('students', 1);
        });
    </script>
</body>
</html>
