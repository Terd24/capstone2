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
    <style>
        /* Prevent flash of dashboard on page load */
        .section:not(.active) {
            display: none !important;
        }
    </style>
    <script>
        // Restore section state IMMEDIATELY before any rendering
        (function() {
            const savedSection = sessionStorage.getItem('currentSection');
            const activeSection = sessionStorage.getItem('activeSection');
            const sectionToShow = activeSection === 'deleted-items' ? 'deleted-items' : (savedSection || 'dashboard');
            
            // If not dashboard, inject CSS to hide dashboard and show target section
            if (sectionToShow !== 'dashboard') {
                const style = document.createElement('style');
                style.innerHTML = `
                    #dashboard-section { display: none !important; }
                    #${sectionToShow}-section { display: block !important; }
                `;
                document.head.appendChild(style);
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-[#0B2C62] to-[#153e86] text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 overflow-y-auto flex flex-col">
        <!-- Header with Logo -->
        <div class="flex items-center gap-3 h-16 px-6 border-b border-white/10 flex-shrink-0">
            <img src="../images/LogoCCI.png" class="h-8 w-8 rounded-full bg-white p-1" alt="Logo">
            <div class="leading-tight">
                <div class="font-bold text-sm">Cornerstone College</div>
                <div class="text-xs text-blue-200">Super Admin</div>
            </div>
        </div>
        
        <!-- User Profile Section -->
        <div class="border-b border-white/10 p-4 flex-shrink-0">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-semibold"><?= substr($_SESSION['superadmin_name'] ?? 'IT', 0, 2) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['superadmin_name'] ?? 'IT Personnel') ?></div>
                    <div class="text-xs text-blue-200">Super Administrator</div>
                </div>
            </div>
        </div>
        
        <!-- Navigation - Flex grow to fill space -->
        <nav class="flex-1 px-4 py-6">
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="#dashboard" onclick="showSection('dashboard', event)" class="nav-item active flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <!-- Management Section -->
                <div class="pt-6 pb-2">
                    <div class="text-xs font-semibold text-blue-200 uppercase tracking-wider px-4">Management</div>
                </div>
                
                <a href="#hr-accounts" onclick="showSection('hr-accounts', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>HR Accounts</span>
                </a>
                
                <a href="#system-maintenance" onclick="showSection('system-maintenance', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>System Maintenance</span>
                </a>
                
                <a href="#deleted-items" onclick="showSection('deleted-items', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Deleted Items</span>
                </a>
                
                <a href="#view-archives" onclick="showSection('view-archives', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>View Archives</span>
                </a>
            </div>
        </nav>
        
        <!-- Logout Button - Fixed at bottom -->
        <div class="border-t border-white/10 p-4 flex-shrink-0">
            <a href="../StudentLogin/logout.php" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-64">
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
                    <button onclick="refreshCurrentSection()" class="p-2 rounded-md hover:bg-gray-100 text-gray-600" title="Refresh">
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
                            <div class="flex items-center justify-between mb-4">
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
                                <div class="flex gap-2">
                                    <button onclick="openLoginHistory()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Login History
                                    </button>
                                    <button onclick="location.reload()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Refresh
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filters -->
                            <div class="flex flex-wrap gap-3 items-end">
                                <div class="flex items-center gap-2">
                                    <label class="text-white text-sm font-medium whitespace-nowrap">User Type:</label>
                                    <select id="filter-user-type" onchange="updateRoleOptions(); filterLogins();" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                        <option value="all">All</option>
                                        <option value="student">Student</option>
                                        <option value="employee">Employee</option>
                                        <option value="parent">Parent</option>
                                    </select>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <label class="text-white text-sm font-medium whitespace-nowrap">Role:</label>
                                    <select id="filter-role" onchange="filterLogins()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                        <option value="all">All</option>
                                        <!-- Options will be populated dynamically -->
                                    </select>
                                </div>
                                
                                <div class="flex items-center gap-2 flex-1 min-w-[250px] max-w-md">
                                    <label class="text-white text-sm font-medium whitespace-nowrap">Search:</label>
                                    <div class="relative flex-1">
                                        <input type="text" id="filter-search" oninput="filterLogins()" placeholder="Search by ID or Name..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                <button onclick="clearFilters()" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                                    Clear Filters
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
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Logout Time</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Duration</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" id="logins-tbody">
                                    <?php if (!empty($today_logins)): ?>
                                        <?php foreach ($today_logins as $login): 
                                            // Set user type color
                                            $userTypeColors = [
                                                'employee' => 'bg-purple-100 text-purple-700',
                                                'student' => 'bg-blue-100 text-blue-700',
                                                'parent' => 'bg-cyan-100 text-cyan-700'
                                            ];
                                            $userTypeColor = $userTypeColors[$login['user_type']] ?? 'bg-gray-100 text-gray-700';
                                            
                                            $roleColors = [
                                                'superadmin' => 'bg-red-100 text-red-700',
                                                'hr' => 'bg-orange-100 text-orange-700',
                                                'teacher' => 'bg-green-100 text-green-700',
                                                'registrar' => 'bg-indigo-100 text-indigo-700',
                                                'cashier' => 'bg-yellow-100 text-yellow-700',
                                                'guidance' => 'bg-pink-100 text-pink-700',
                                                'attendance' => 'bg-teal-100 text-teal-700',
                                                'student' => 'bg-blue-100 text-blue-700',
                                                'parent' => 'bg-cyan-100 text-cyan-700'
                                            ];
                                            $roleColor = $roleColors[$login['role']] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                        <tr class="hover:bg-blue-50 transition-colors login-row" data-user-type="<?= strtolower(htmlspecialchars($login['user_type'])) ?>" data-role="<?= strtolower(htmlspecialchars($login['role'])) ?>" data-id="<?= htmlspecialchars($login['id_number']) ?>" data-name="<?= htmlspecialchars($login['full_name'] ?: $login['username']) ?>">
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
                                            <td class="px-4 py-3 text-gray-600">
                                                <?php if (!empty($login['logout_time'])): ?>
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                        </svg>
                                                        <?= date('M j, Y g:i A', strtotime($login['logout_time'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-green-600 font-medium flex items-center gap-1">
                                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                                        Active
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                <?php if (!empty($login['session_duration'])): ?>
                                                    <?php 
                                                        $hours = floor($login['session_duration'] / 3600);
                                                        $minutes = floor(($login['session_duration'] % 3600) / 60);
                                                        if ($hours > 0) {
                                                            echo $hours . 'h ' . $minutes . 'm';
                                                        } else {
                                                            echo $minutes . ' min';
                                                        }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">---</span>
                                                <?php endif; ?>
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
                    <!-- Not Logged In Today (Employees) -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-orange-500 px-6 py-4">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="bg-white/20 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                                    <p class="text-orange-100 text-sm">Employees</p>
                                </div>
                            </div>
                            <!-- Employee Filters -->
                            <div class="flex flex-wrap gap-2">
                                <div class="relative flex-1 min-w-[200px]">
                                    <input type="text" id="employee-search" oninput="filterEmployees()" placeholder="Search by name or ID..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <select id="employee-role-filter" onchange="filterEmployees()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                    <option value="all">All Roles</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="hr">HR</option>
                                    <option value="attendance">Attendance</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="guidance">Guidance</option>
                                </select>
                                <button onclick="clearEmployeeFilters()" class="px-3 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="min-h-[280px]">
                                <ul class="space-y-2" id="employees-list">
                                    <!-- Items will be loaded here -->
                                </ul>
                                <div id="employees-loading" class="text-center py-8">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-200 border-t-orange-600"></div>
                                    <p class="text-gray-500 text-sm mt-2">Loading employees...</p>
                                </div>
                            </div>
                            <!-- Pagination for Employees -->
                            <div id="employees-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                                <div class="text-sm text-gray-600">
                                    Showing <span id="employees-start" class="font-semibold text-gray-900">1</span> to <span id="employees-end" class="font-semibold text-gray-900">10</span> of <span id="employees-total" class="font-semibold text-gray-900">0</span> employees
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
                            <div class="flex items-center gap-3 mb-4">
                                <div class="bg-white/20 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                                    <p class="text-blue-100 text-sm">Students & Parents</p>
                                </div>
                            </div>
                            <!-- Student Filters -->
                            <div class="flex flex-wrap gap-2">
                                <div class="relative flex-1 min-w-[200px]">
                                    <input type="text" id="student-search" oninput="filterStudents()" placeholder="Search by name or ID..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <select id="student-type-filter" onchange="filterStudents()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                                    <option value="all">All Types</option>
                                    <option value="student">Students</option>
                                    <option value="parent">Parents</option>
                                </select>
                                <button onclick="clearStudentFilters()" class="px-3 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="min-h-[280px]">
                                <ul class="space-y-2" id="students-list">
                                    <!-- Items will be loaded here -->
                                </ul>
                                <div id="students-loading" class="text-center py-8">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600"></div>
                                    <p class="text-gray-500 text-sm mt-2">Loading students & parents...</p>
                                </div>
                            </div>
                            <!-- Pagination for Students & Parents -->
                            <div id="students-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                                <div class="text-sm text-gray-600">
                                    Showing <span id="students-start" class="font-semibold text-gray-900">1</span> to <span id="students-end" class="font-semibold text-gray-900">10</span> of <span id="students-total" class="font-semibold text-gray-900">0</span> users
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
                        <table class="w-full table-fixed">
                            <thead class="bg-[#0B2C62] text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium w-[15%]">ID Number</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium w-[25%]">Full Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium w-[20%]">Position</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium w-[25%]">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium w-[15%]">Hire Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                // Get ALL HR employees
                                $hr_query = "
                                    SELECT 
                                        e.id_number,
                                        e.first_name,
                                        e.last_name,
                                        e.middle_name,
                                        e.position,
                                        e.department,
                                        e.hire_date
                                    FROM employees e
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
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
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
                        <div class="flex flex-col">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Maintenance Mode</h4>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4 flex-grow">
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
                            <button onclick="updateConfiguration()" class="w-full bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors mt-auto">
                                Update Configuration
                            </button>
                        </div>

                        <!-- Database Backup -->
                        <div class="flex flex-col">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Database Backup</h4>
                            <div class="bg-[#0B2C62]/5 rounded-lg p-4 mb-4 flex-grow">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-[#0B2C62] font-medium">Backup Information</span>
                                </div>
                                <ul class="text-sm text-[#0B2C62] space-y-1">
                                    <li>• Includes all tables and data</li>
                                    <li>• Regular backups recommended before updates</li>
                                    <li>• Choose where to save the backup file</li>
                                    <li>• Creates timestamped SQL backup files</li>
                                </ul>
                            </div>
                            <button onclick="createDatabaseBackup()" class="w-full bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 mt-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Download Database Backup
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
                                <p class="text-sm text-gray-600">All login records between these dates will be archived and can be viewed in "View Archives"</p>
                            </div>
                            <button onclick="clearLoginLogs()" class="w-full mt-4 bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Archive Login Logs
                            </button>
                        </div>

                        <!-- Archive Attendance Records -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Archive Attendance Records</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                                    <input type="date" id="attendanceStartDate" value="31/08/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date:</label>
                                    <input type="date" id="attendanceEndDate" value="30/09/2025" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                </div>
                                <p class="text-sm text-gray-600">All attendance records between these dates will be archived and can be viewed in "View Archives"</p>
                            </div>
                            <button onclick="clearAttendanceRecords()" class="w-full mt-4 bg-[#1e3a8a] hover:bg-[#1e40af] text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Archive Attendance Records
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
                                <div class="text-4xl font-bold text-white" id="deleted-students-count"><?= count($deleted_students) ?></div>
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
                                <div class="text-4xl font-bold text-white" id="deleted-employees-count"><?= count($deleted_employees) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deleted Students Table -->
                <div class="bg-white rounded-2xl shadow-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <h3 class="text-lg font-bold text-gray-900">Deleted Students (<span id="deleted-students-table-count"><?= count($deleted_students) ?></span>)</h3>
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
                                    <tr class="hover:bg-gray-50" data-student-id="<?= $student['id'] ?>" data-student-id-number="<?= htmlspecialchars($student['id_number']) ?>">
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
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button onclick="restoreStudent(<?= $student['id'] ?>)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Restore
                                                </button>
                                                <button onclick="archiveStudent(<?= $student['id'] ?>)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                    </svg>
                                                    Archive
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
                    
                    <!-- Pagination for Deleted Students -->
                    <div id="students-pagination" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span id="students-start">1</span> to <span id="students-end">5</span> of <span id="students-total"><?= count($deleted_students) ?></span> students
                        </div>
                        <div class="flex gap-2">
                            <button id="students-prev" onclick="changeStudentsPage(-1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <button id="students-next" onclick="changeStudentsPage(1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Deleted Employees Table -->
                <div class="bg-white rounded-2xl shadow-lg" id="deleted-employees-section">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <h3 class="text-lg font-bold text-gray-900">Deleted Employees (<span id="deleted-employees-table-count"><?= count($deleted_employees) ?></span>)</h3>
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
                                    <tr class="hover:bg-gray-50" data-employee-id="<?= $employee['id'] ?>" data-employee-id-number="<?= htmlspecialchars($employee['id_number']) ?>">
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
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button onclick="restoreEmployee(<?= $employee['id'] ?>)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Restore
                                                </button>
                                                <button onclick="archiveEmployee(<?= $employee['id'] ?>)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm transition-colors flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                    </svg>
                                                    Archive
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
                    
                    <!-- Pagination for Deleted Employees -->
                    <div id="employees-pagination" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span id="employees-start">1</span> to <span id="employees-end">5</span> of <span id="employees-total"><?= count($deleted_employees) ?></span> employees
                        </div>
                        <div class="flex gap-2">
                            <button id="employees-prev" onclick="changeEmployeesPage(-1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <button id="employees-next" onclick="changeEmployeesPage(1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Archives Section -->
            <div id="view-archives-section" class="section hidden">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">📦 Archived Records</h2>
                    <p class="text-gray-600">View permanently archived student and employee records</p>
                </div>

                <!-- Archive Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Archived Students</p>
                                <p class="text-4xl font-bold text-white" id="archived-students-count">-</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                            </div>
                        </div>
                        <button onclick="loadArchives('students')" class="w-full bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            View Students
                        </button>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-indigo-100 text-sm font-medium">Archived Employees</p>
                                <p class="text-4xl font-bold text-white" id="archived-employees-count">-</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                        <button onclick="loadArchives('employees')" class="w-full bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            View Employees
                        </button>
                    </div>

                    <div class="bg-gradient-to-br from-gray-700 to-gray-800 text-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-gray-300 text-sm font-medium">Total Archived</p>
                                <p class="text-4xl font-bold text-white" id="total-archived-count">-</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-gray-300 text-sm">
                            Permanent storage
                        </div>
                    </div>
                </div>

                <!-- Data Archives (Login Logs & Attendance) -->
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">📊 System Data Archives</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-blue-100 text-sm font-medium">Login Logs Archive</p>
                                    <p class="text-sm text-blue-100 mt-1">Archived login history</p>
                                </div>
                                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                            </div>
                            <button onclick="loadDataArchives('login')" class="w-full bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                View Login Archives
                            </button>
                        </div>

                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-green-100 text-sm font-medium">Attendance Archive</p>
                                    <p class="text-sm text-green-100 mt-1">Archived attendance records</p>
                                </div>
                                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <button onclick="loadDataArchives('attendance')" class="w-full bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                View Attendance Archives
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Archive Viewer -->
                <div id="archive-viewer" class="bg-white rounded-2xl shadow-lg hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" id="archive-title">Archived Records</h3>
                            <p class="text-sm text-gray-500" id="archive-subtitle">Viewing archived records</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="text" id="archive-search" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button onclick="searchArchives()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                                Search
                            </button>
                            <button onclick="closeArchiveViewer()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition">
                                Close
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr id="archive-table-header">
                                    <!-- Dynamic headers -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="archive-table-body">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span id="archive-start">0</span> to <span id="archive-end">0</span> of <span id="archive-total">0</span> records
                        </div>
                        <div class="flex gap-2">
                            <button onclick="changeArchivePage(-1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" id="archive-prev">
                                Previous
                            </button>
                            <button onclick="changeArchivePage(1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" id="archive-next">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Login History Modal -->
    <div id="login-history-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Login History</h2>
                            <p class="text-blue-100 text-sm">View and search past login records</p>
                        </div>
                    </div>
                    <button onclick="closeLoginHistory()" class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- User Type -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">User Type:</label>
                        <select id="history-user-type" onchange="updateHistoryRoleOptions(); autoSearchHistory();" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all">All</option>
                            <option value="student">Student</option>
                            <option value="employee">Employee</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    
                    <!-- Role -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Role:</label>
                        <select id="history-role" onchange="autoSearchHistory()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all">All</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Search:</label>
                        <div class="relative flex-1">
                            <input type="text" id="history-search" placeholder="Name or ID..." oninput="debouncedHistorySearch()" class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- From Date -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">From Date:</label>
                        <input type="date" id="history-date-from" onchange="if(validateDateRange(this)) { updateDateConstraints(); autoSearchHistory(); }" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- To Date -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">To Date:</label>
                        <input type="date" id="history-date-to" onchange="if(validateDateRange(this)) { updateDateConstraints(); autoSearchHistory(); }" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- Clear Button -->
                    <button onclick="clearHistoryFilters()" class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                        Clear
                    </button>
                </div>
            </div>
            
            <!-- Row 3: Date Range Indicator -->
            <div id="history-date-indicator" class="hidden px-6 py-3 bg-blue-50 border-b border-blue-100">
                <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-gray-600">Viewing records from:</span>
                    <span id="history-date-range" class="font-semibold text-blue-700"></span>
                </div>
            </div>

            <!-- Table -->
            <div class="flex-1 overflow-auto">
                <div id="history-loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
                        <p class="text-gray-500">Loading login history...</p>
                    </div>
                </div>
                
                <table id="history-table" class="w-full text-sm hidden">
                    <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">User Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Login Time</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Logout Time</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Duration</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody" class="divide-y divide-gray-100">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
                
                <div id="history-no-results" class="hidden flex items-center justify-center min-h-[400px]">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-500 font-medium text-lg">No login records found</p>
                        <p class="text-gray-400 text-sm mt-2">Try adjusting your filters or select a date range</p>
                    </div>
                </div>
                
                <div id="history-initial-message" class="flex items-center justify-center min-h-[400px]">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-600 font-medium text-lg mb-2">Search Login History</p>
                        <p class="text-gray-500 text-sm">Select filters and click Search to view login records</p>
                        <p class="text-gray-400 text-xs mt-2">💡 Tip: Leave dates empty to search all records</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div id="history-pagination" class="hidden px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <span id="history-start">1</span> to <span id="history-end">20</span> of <span id="history-total">0</span> records
                </div>
                <div class="flex gap-2">
                    <button id="history-prev" onclick="changeHistoryPage(-1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Previous
                    </button>
                    <button id="history-next" onclick="changeHistoryPage(1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Next
                    </button>
                </div>
            </div>
        </div>
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
        /* Reset Password Button styles */
        button[id^="resetPasswordBtn_"]:not([disabled]) {
            background-color: #eab308 !important;
            cursor: pointer !important;
        }
        button[id^="resetPasswordBtn_"]:not([disabled]):hover {
            background-color: #ca8a04 !important;
        }
        button[id^="resetPasswordBtn_"][disabled] {
            background-color: #9ca3af !important;
            cursor: not-allowed !important;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        let currentSection = 'dashboard'; // Track current section
        
        function showSection(sectionName, clickEvent = null) {
            // Prevent default link behavior to avoid hash in URL
            if (clickEvent) {
                clickEvent.preventDefault();
            }
            
            // Store current section
            currentSection = sectionName;
            sessionStorage.setItem('currentSection', sectionName);
            
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
            
            // Add active class to clicked nav item or find by href
            if (clickEvent && clickEvent.target) {
                const navItem = clickEvent.target.closest('.nav-item');
                if (navItem) {
                    navItem.classList.add('active');
                }
            } else {
                // If no click event, find the nav item by href
                const navItem = document.querySelector(`a[href="#${sectionName}"]`);
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
                'deleted-items': 'Deleted Items Management',
                'view-archives': 'View Archives'
            };
            
            document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
        }
        
        // Function to refresh current section without full page reload
        function refreshCurrentSection() {
            // Just reload the page - the DOMContentLoaded will restore the section
            location.reload();
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
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold mb-1">Username</label>
                                            <input type="text" id="username_${employee.id_number}" value="${employee.username}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 username-field-readonly cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold mb-1">Password</label>
                                            <div class="flex gap-2">
                                                <input type="text" id="password_${employee.id_number}" placeholder="Click Reset Password button to generate" readonly disabled class="flex-1 border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 cursor-not-allowed" style="pointer-events: none;">
                                                <button type="button" id="resetPasswordBtn_${employee.id_number}" onclick="resetHRPassword('${employee.id_number}', '${employee.last_name}')" disabled class="px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed whitespace-nowrap">
                                                    Reset Password
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password. Click "Reset Password" to generate a new temporary password.</p>
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
            const resetPasswordBtn = document.querySelector('[id^="resetPasswordBtn_"]');
            const passwordField = document.querySelector('[id^="password_"]');
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
                
                // Enable reset password button
                if (resetPasswordBtn) {
                    resetPasswordBtn.disabled = false;
                    resetPasswordBtn.removeAttribute('disabled');
                    resetPasswordBtn.className = 'px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors cursor-pointer whitespace-nowrap';
                    resetPasswordBtn.style.backgroundColor = '#eab308';
                    resetPasswordBtn.style.cursor = 'pointer';
                }
                
                // Keep password field ALWAYS disabled and readonly (only Reset button should fill it)
                if (passwordField) {
                    passwordField.readOnly = true;
                    passwordField.disabled = true;
                    passwordField.setAttribute('readonly', 'readonly');
                    passwordField.setAttribute('disabled', 'disabled');
                    passwordField.style.pointerEvents = 'none';
                    passwordField.classList.add('bg-gray-50');
                    passwordField.classList.remove('bg-white');
                }
                
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
                
                // Disable reset password button and clear password field
                if (resetPasswordBtn) {
                    resetPasswordBtn.disabled = true;
                    resetPasswordBtn.setAttribute('disabled', 'disabled');
                    resetPasswordBtn.className = 'px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed whitespace-nowrap';
                }
                
                // Clear password field when canceling
                if (passwordField) {
                    passwordField.value = '';
                    passwordField.disabled = true;
                    passwordField.setAttribute('disabled', 'disabled');
                }
                
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

        // Reset HR employee password function
        function resetHRPassword(employeeId, lastName, hireDate) {
            const passwordField = document.getElementById(`password_${employeeId}`);
            
            if (!passwordField || !lastName || !employeeId) {
                alert('Error: Required fields not found. Please ensure last name and employee ID are available.');
                return;
            }
            
            // Get values
            const lastNameClean = lastName.toLowerCase().replace(/[^a-z]/g, '');
            
            // Extract the 3-digit number from employee ID (e.g., CCI2025-006 -> 006)
            const parts = employeeId.split('-');
            const idNumber = parts.length === 2 ? parts[1] : '000';
            
            // Get current year
            const currentYear = new Date().getFullYear();
            
            // Format: lastname + idNumber + currentYear (e.g., smith0062025)
            const newPassword = lastNameClean + idNumber + currentYear;
            
            // Enable field and set value (keep it enabled so it submits with the form)
            passwordField.disabled = false;
            passwordField.removeAttribute('disabled');
            passwordField.value = newPassword;
            // Keep readonly and pointer-events:none for visual purposes, but NOT disabled
            passwordField.readOnly = true;
            passwordField.style.pointerEvents = 'none';
            
            // Show custom modal
            showPasswordResetModal(newPassword);
        }

        // Show password reset modal
        function showPasswordResetModal(password) {
            const modal = document.createElement('div');
            modal.id = 'passwordResetModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center';
            modal.style.zIndex = '10100';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Temporary Password Generated</h3>
                        <p class="text-gray-600 mb-4">The new temporary password has been generated:</p>
                        <div class="bg-gray-100 border border-gray-300 rounded-lg px-4 py-3 mb-4 w-full">
                            <p class="text-lg font-mono font-semibold text-gray-900 break-all">${password}</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-blue-800"><strong>⚠️ Important:</strong> Please save this password and click <strong>"Save Changes"</strong> button to apply the password reset.</p>
                        </div>
                        <p class="text-xs text-amber-600 mb-6">The employee will be required to change this password on first login.</p>
                        <button onclick="closePasswordResetModal()" class="w-full py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium">
                            OK, I've Saved It
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Close password reset modal
        function closePasswordResetModal() {
            const modal = document.getElementById('passwordResetModal');
            if (modal) {
                modal.remove();
            }
        }

        function showSaveConfirmation() {
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
            
            // Remove any existing error messages
            document.querySelectorAll('.field-error').forEach(el => el.remove());
            
            // Validate required fields and show red borders
            let hasErrors = false;
            const requiredFields = [
                { id: `first_name_${currentHREmployeeId}`, value: firstName, label: 'First Name' },
                { id: `last_name_${currentHREmployeeId}`, value: lastName, label: 'Last Name' },
                { id: `position_${currentHREmployeeId}`, value: position, label: 'Position' },
                { id: `department_${currentHREmployeeId}`, value: department, label: 'Department' },
                { id: `email_${currentHREmployeeId}`, value: email, label: 'Email' },
                { id: `phone_${currentHREmployeeId}`, value: phone, label: 'Phone' },
                { id: `hire_date_${currentHREmployeeId}`, value: hireDate, label: 'Hire Date' },
                { id: `address_${currentHREmployeeId}`, value: address, label: 'Complete Address' }
            ];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (!field.value || field.value.trim() === '') {
                    hasErrors = true;
                    element.classList.add('border-red-500', 'border-2');
                    element.classList.remove('border-gray-300');
                    
                    // Add error message below field
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'field-error text-red-500 text-sm mt-1';
                    errorMsg.textContent = `${field.label} is required`;
                    element.parentNode.appendChild(errorMsg);
                } else {
                    element.classList.remove('border-red-500', 'border-2');
                    element.classList.add('border-gray-300');
                }
            });
            
            if (hasErrors) {
                return;
            }
            
            // If validation passes, show confirmation dialog
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
            const password = document.getElementById(`password_${currentHREmployeeId}`)?.value;
            
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
            if (password) {
                formData.append('password', password);
            }
            
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
                                        <select name="position" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm">
                                            <option value="">Select HR Position</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="HR Assistant Manager">HR Assistant Manager</option>
                                            <option value="HR Supervisor">HR Supervisor</option>
                                            <option value="HR Officer">HR Officer</option>
                                            <option value="HR Staff">HR Staff</option>
                                            <option value="HR Assistant">HR Assistant</option>
                                            <option value="Recruitment Specialist">Recruitment Specialist</option>
                                            <option value="Training Coordinator">Training Coordinator</option>
                                            <option value="Payroll Officer">Payroll Officer</option>
                                            <option value="Benefits Administrator">Benefits Administrator</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                        <input type="text" name="department" value="Human Resources" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date *</label>
                                        <input type="date" name="hire_date" max="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                        <input type="email" name="email" autocomplete="off" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                        <input type="tel" name="phone" id="phoneField" required placeholder="+63 9XX-XXX-XXXX" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[#0B2C62] focus:border-[#0B2C62] text-sm" title="Please enter Philippine mobile number (e.g., +63 912-345-6789)" oninput="formatPhilippinePhone(this)">
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
                                
                                <input type="hidden" id="createAccount" name="create_account" value="1">
                                
                                <div id="accountFields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                                        <input type="text" id="usernameField" name="username" autocomplete="off" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-600">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                        <div class="relative">
                                            <input type="password" id="passwordField" name="password" autocomplete="new-password" readonly class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-600">
                                            <button type="button" onclick="togglePasswordVisibility('passwordField', 'eyeIcon')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                        </div>
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
            
            // Account fields are always visible and required (automatic account creation)
            const accountFields = document.getElementById('accountFields');
            const usernameField = document.querySelector('input[name="username"]');
            const passwordField = document.querySelector('input[name="password"]');
            accountFields.style.display = 'grid';
            usernameField.required = true;
            passwordField.required = true;
        }

        function formatPhilippinePhone(input) {
            // Remove all non-numeric characters except +
            let value = input.value.replace(/[^\d+]/g, '');
            
            // Remove + if it's not at the start
            if (value.indexOf('+') > 0) {
                value = value.replace(/\+/g, '');
            }
            
            // If starts with 0, convert to +63
            if (value.startsWith('0')) {
                value = '+63' + value.slice(1);
            }
            
            // If doesn't start with +63, add it
            if (!value.startsWith('+63') && !value.startsWith('+')) {
                value = '+63' + value;
            }
            
            // Ensure it starts with +63
            if (value.startsWith('+') && !value.startsWith('+63')) {
                value = '+63' + value.slice(1);
            }
            
            // Remove +63 prefix for processing
            let digits = value.replace('+63', '');
            
            // Limit to 10 digits (after +63)
            digits = digits.slice(0, 10);
            
            // Format as +63 9XX-XXX-XXXX
            let formatted = '+63';
            if (digits.length > 0) {
                formatted += ' ' + digits.slice(0, 3);
            }
            if (digits.length > 3) {
                formatted += '-' + digits.slice(3, 6);
            }
            if (digits.length > 6) {
                formatted += '-' + digits.slice(6, 10);
            }
            
            input.value = formatted;
        }

        function togglePasswordVisibility(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                // Change to eye-slash icon (simple diagonal line)
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                `;
            } else {
                passwordField.type = 'password';
                // Change back to eye icon
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
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
            const position = form.querySelector('select[name="position"]').value.trim();
            const department = form.querySelector('input[name="department"]').value.trim();
            const email = form.querySelector('input[name="email"]').value.trim();
            const phone = form.querySelector('input[name="phone"]').value.trim();
            const address = form.querySelector('textarea[name="address"]').value.trim();
            const hireDate = form.querySelector('input[name="hire_date"]').value;
            const createAccount = true; // Always create account (mandatory)
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
                highlightFieldError(form.querySelector('select[name="position"]'), 'Position is required');
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
            } else {
                // Count commas to ensure multiple address components
                const commaCount = (address.match(/,/g) || []).length;
                if (commaCount < 3) {
                    highlightFieldError(form.querySelector('textarea[name="address"]'), 'Complete address must include at least 4 components separated by commas (e.g., Street, Barangay, City, Province)');
                    hasErrors = true;
                }
                // Check if address has meaningful content (not just commas)
                const addressParts = address.split(',').map(part => part.trim()).filter(part => part.length > 0);
                if (addressParts.length < 4) {
                    highlightFieldError(form.querySelector('textarea[name="address"]'), 'Complete address must include street, barangay, city/municipality, and province');
                    hasErrors = true;
                }
            }
            if (!hireDate) {
                highlightFieldError(form.querySelector('input[name="hire_date"]'), 'Hire date is required');
                hasErrors = true;
            }
            // Validate hire date is not in the future
            if (hireDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(hireDate + 'T00:00:00');
                if (selectedDate > today) {
                    highlightFieldError(form.querySelector('input[name="hire_date"]'), 'Hire date cannot be in the future');
                    hasErrors = true;
                }
            }
            
            // Email validation - only allow trusted email providers
            if (email) {
                const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                const allowedDomains = [
                    'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 
                    'icloud.com', 'protonmail.com', 'aol.com', 'zoho.com',
                    'mail.com', 'yandex.com', 'gmx.com', 'tutanota.com'
                ];
                
                if (!emailPattern.test(email)) {
                    highlightFieldError(form.querySelector('input[name="email"]'), 'Please enter a valid email address');
                    hasErrors = true;
                } else {
                    const domain = email.split('@')[1].toLowerCase();
                    if (!allowedDomains.includes(domain)) {
                        highlightFieldError(form.querySelector('input[name="email"]'), 'Please use a valid email provider (Gmail, Yahoo, Outlook, etc.)');
                        hasErrors = true;
                    }
                }
            }
            
            // Phone validation (accept formatted: +63 9XX-XXX-XXXX)
            const phoneDigits = phone.replace(/\D/g, '');
            if (phone && !phone.startsWith('+63')) {
                highlightFieldError(form.querySelector('input[name="phone"]'), 'Phone must start with +63');
                hasErrors = true;
            }
            if (phoneDigits && phoneDigits.length !== 12) { // 63 + 10 digits
                highlightFieldError(form.querySelector('input[name="phone"]'), 'Phone must be in format +63 9XX-XXX-XXXX');
                hasErrors = true;
            }
            if (phoneDigits && !phoneDigits.startsWith('639')) {
                highlightFieldError(form.querySelector('input[name="phone"]'), 'Philippine mobile numbers start with 9 after +63');
                hasErrors = true;
            }
            
            // Account validation (now mandatory)
            if (!username) {
                highlightFieldError(form.querySelector('input[name="username"]'), 'Username is required for system account');
                hasErrors = true;
            }
            if (!password) {
                highlightFieldError(form.querySelector('input[name="password"]'), 'Password is required for system account');
                hasErrors = true;
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
                            <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200 space-y-3">
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-semibold text-gray-800 block">Username:</span>
                                    <span class="text-gray-900 font-medium">${data.username || 'Not generated'}</span>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-semibold text-gray-800 block">Password:</span>
                                    <span class="text-gray-900 font-medium">${data.password ? '•'.repeat(data.password.length) + ' (' + data.password.length + ' chars)' : 'Not generated'}</span>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <span class="font-semibold text-gray-800 block">Role:</span>
                                    <span class="text-gray-900 font-medium">HR</span>
                                </div>
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
        
        function showNotificationModal({title, message, details = [], type = 'info', onClose = null}) {
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
            window.currentNotificationOnClose = onClose;
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
                
                // Execute onClose callback if provided
                if (window.currentNotificationOnClose) {
                    window.currentNotificationOnClose();
                    window.currentNotificationOnClose = null;
                }
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
                title: 'Download Database Backup',
                message: 'Are you sure you want to download a complete database backup?',
                details: [
                    'This will include all tables and data',
                    'The process may take a few minutes',
                    'You will be prompted to choose where to save the file'
                ],
                confirmText: 'Download Backup',
                cancelText: 'Cancel',
                type: 'info',
                onConfirm: () => {
                    // Show loading notification
                    showNotificationModal({
                        title: 'Creating Backup...',
                        message: 'Please wait while the backup is being generated. This may take a few moments.',
                        details: [
                            'Do not close this window',
                            'Download will start automatically'
                        ],
                        type: 'info'
                    });
                    
                    // Trigger download by opening the backup URL
                    // This will prompt the browser's "Save As" dialog
                    window.location.href = 'create_backup.php';
                    
                    // Close the loading modal after a short delay
                    setTimeout(() => {
                        const modal = document.getElementById('notificationModal');
                        if (modal) {
                            modal.classList.add('hidden');
                        }
                    }, 2000);
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
                title: 'Archive Login Logs',
                message: `Are you sure you want to archive all login logs between ${startDate} and ${endDate}?`,
                details: [
                    'Records will be moved to archive',
                    'Archived records can be viewed in "View Archives"',
                    'Records will be removed from active logs'
                ],
                confirmText: 'Archive Logs',
                cancelText: 'Cancel',
                type: 'info',
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
                            showNotificationModal({
                                title: 'Login Logs Archived',
                                message: data.message,
                                details: [
                                    `Records archived: ${data.records_archived}`,
                                    `Date range: ${startDate} to ${endDate}`,
                                    'View archived records in "View Archives"'
                                ],
                                type: 'success'
                            });
                            // Clear the date inputs
                            document.getElementById('loginStartDate').value = '';
                            document.getElementById('loginEndDate').value = '';
                        } else {
                            showNotificationModal({
                                title: 'Archive Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while archiving login logs',
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
                title: 'Archive Attendance Records',
                message: `Are you sure you want to archive all attendance records between ${startDate} and ${endDate}?`,
                details: [
                    'Records will be moved to archive',
                    'Archived records can be viewed in "View Archives"',
                    'Records will be removed from active attendance'
                ],
                confirmText: 'Archive Records',
                cancelText: 'Cancel',
                type: 'info',
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
                            showNotificationModal({
                                title: 'Attendance Records Archived',
                                message: data.message,
                                details: [
                                    `Records archived: ${data.records_archived}`,
                                    `Date range: ${startDate} to ${endDate}`,
                                    'View archived records in "View Archives"'
                                ],
                                type: 'success'
                            });
                            // Clear the date inputs
                            document.getElementById('attendanceStartDate').value = '';
                            document.getElementById('attendanceEndDate').value = '';
                        } else {
                            showNotificationModal({
                                title: 'Archive Failed',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationModal({
                            title: 'Error',
                            message: 'An error occurred while archiving attendance records',
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
                            `Student ID: ${data.student_id || studentId}`,
                            'Record is now active',
                            'Visible in Registrar system'
                        ],
                        type: 'success',
                        onClose: () => {
                            // Remove the restored student from the list smoothly (no page reload)
                            const studentRow = document.querySelector(`tr[data-student-id="${studentId}"]`);
                            if (studentRow) {
                                // Fade out and slide the row
                                studentRow.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                                studentRow.style.opacity = '0';
                                studentRow.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    studentRow.remove();
                                    
                                    // Update the deleted students count in the card
                                    const deletedStudentsCard = document.querySelector('.bg-red-500.text-white.rounded-2xl');
                                    if (deletedStudentsCard) {
                                        const countElement = deletedStudentsCard.querySelector('.text-4xl.font-bold.text-white');
                                        if (countElement) {
                                            const currentCount = parseInt(countElement.textContent);
                                            const newCount = Math.max(0, currentCount - 1);
                                            countElement.textContent = newCount;
                                        }
                                    }
                                    
                                    // Update the table header count
                                    const tableHeaders = document.querySelectorAll('h3.text-lg.font-bold.text-gray-900');
                                    tableHeaders.forEach(header => {
                                        if (header.textContent.includes('Deleted Students')) {
                                            const match = header.textContent.match(/\((\d+)\)/);
                                            if (match) {
                                                const newCount = Math.max(0, parseInt(match[1]) - 1);
                                                header.textContent = header.textContent.replace(/\(\d+\)/, `(${newCount})`);
                                            }
                                        }
                                    });
                                    
                                    // Check if table is empty and show "no deleted students" message
                                    const tbody = document.querySelector('tbody');
                                    const remainingRows = tbody.querySelectorAll('tr[data-student-id]');
                                    if (remainingRows.length === 0) {
                                        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500"><svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg><p>No deleted students found</p></td></tr>';
                                    }
                                    
                                    // Update pagination after removing item
                                    updateStudentsPagination();
                                }, 300);
                            }
                        }
                    });
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
                            `Employee ID: ${data.employee_id || employeeId}`,
                            'Record is now active',
                            'Visible in HR system'
                        ],
                        type: 'success',
                        onClose: () => {
                            // Save current scroll position before making changes
                            const scrollPosition = window.scrollY || window.pageYOffset;
                            
                            // Remove the restored employee from the list smoothly (no page reload)
                            const employeeRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                            if (employeeRow) {
                                // Fade out and slide the row
                                employeeRow.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                                employeeRow.style.opacity = '0';
                                employeeRow.style.transform = 'translateX(-20px)';
                                
                                setTimeout(() => {
                                    employeeRow.remove();
                                    
                                    // Update the deleted employees count in the card
                                    const deletedEmployeesCard = document.querySelector('.bg-orange-500.text-white.rounded-2xl');
                                    if (deletedEmployeesCard) {
                                        const countElement = deletedEmployeesCard.querySelector('.text-4xl.font-bold.text-white');
                                        if (countElement) {
                                            const currentCount = parseInt(countElement.textContent);
                                            const newCount = Math.max(0, currentCount - 1);
                                            countElement.textContent = newCount;
                                        }
                                    }
                                    
                                    // Update the table header count
                                    const tableHeaders = document.querySelectorAll('h3.text-lg.font-bold.text-gray-900');
                                    tableHeaders.forEach(header => {
                                        if (header.textContent.includes('Deleted Employees')) {
                                            const match = header.textContent.match(/\((\d+)\)/);
                                            if (match) {
                                                const newCount = Math.max(0, parseInt(match[1]) - 1);
                                                header.textContent = header.textContent.replace(/\(\d+\)/, `(${newCount})`);
                                            }
                                        }
                                    });
                                    
                                    // Check if table is empty and show "no deleted employees" message
                                    const employeeTbody = employeeRow.closest('tbody');
                                    if (employeeTbody) {
                                        const remainingRows = employeeTbody.querySelectorAll('tr[data-employee-id]');
                                        if (remainingRows.length === 0) {
                                            employeeTbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500"><svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg><p>No deleted employees found</p></td></tr>';
                                        } else {
                                            // Update pagination after removing item
                                            updateEmployeesPagination();
                                        }
                                    }
                                    
                                    // Restore scroll position to prevent unwanted scrolling
                                    window.scrollTo(0, scrollPosition);
                                }, 300);
                            }
                        }
                    });
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

// Archive Functions
function archiveStudent(studentId) {
    showConfirmationModal({
        title: 'Archive Student Record',
        message: 'Are you sure you want to archive this student record?',
        details: [
            'Student will be moved to permanent archive',
            'Record will be removed from deleted items',
            'This action preserves the data permanently',
            'Cannot be restored once archived'
        ],
        confirmText: 'Archive',
        cancelText: 'Cancel',
        type: 'warning',
        onConfirm: () => {
            fetch('archive_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'archive',
                    record_type: 'student',
                    record_id: studentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal({
                        title: 'Student Archived',
                        message: data.message,
                        details: [
                            `Student ID: ${data.student_id}`,
                            'Moved to permanent archive',
                            'Data preserved for records'
                        ],
                        type: 'success',
                        onClose: () => {
                            // Remove the archived student from the list
                            const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
                            if (row) {
                                row.remove();
                            }
                            // Update the counts
                            updateDeletedCounts();
                            loadArchiveCounts();
                        }
                    });
                } else {
                    showNotificationModal({
                        title: 'Archive Failed',
                        message: data.message,
                        type: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal({
                    title: 'Error',
                    message: 'An error occurred while archiving the record',
                    type: 'error'
                });
            });
        }
    });
}

function archiveEmployee(employeeId) {
    showConfirmationModal({
        title: 'Archive Employee Record',
        message: 'Are you sure you want to archive this employee record?',
        details: [
            'Employee will be moved to permanent archive',
            'Record will be removed from deleted items',
            'This action preserves the data permanently',
            'Cannot be restored once archived'
        ],
        confirmText: 'Archive',
        cancelText: 'Cancel',
        type: 'warning',
        onConfirm: () => {
            fetch('archive_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'archive',
                    record_type: 'employee',
                    record_id: employeeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotificationModal({
                        title: 'Employee Archived',
                        message: data.message,
                        details: [
                            `Employee ID: ${data.employee_id}`,
                            'Moved to permanent archive',
                            'Data preserved for records'
                        ],
                        type: 'success',
                        onClose: () => {
                            // Remove the archived employee from the list
                            const row = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                            if (row) {
                                row.remove();
                            }
                            // Update the counts
                            updateDeletedCounts();
                            loadArchiveCounts();
                        }
                    });
                } else {
                    showNotificationModal({
                        title: 'Archive Failed',
                        message: data.message,
                        type: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotificationModal({
                    title: 'Error',
                    message: 'An error occurred while archiving the record',
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

        // Restore section immediately before page is visible (prevents flash)
        (function() {
            const savedSection = sessionStorage.getItem('currentSection');
            const activeSection = sessionStorage.getItem('activeSection');
            
            if (activeSection === 'deleted-items' || savedSection) {
                const sectionToShow = activeSection === 'deleted-items' ? 'deleted-items' : savedSection;
                
                // Hide dashboard section immediately
                const dashboardSection = document.getElementById('dashboard-section');
                if (dashboardSection) {
                    dashboardSection.classList.remove('active');
                    dashboardSection.classList.add('hidden');
                }
                
                // Show target section immediately
                const targetSection = document.getElementById(sectionToShow + '-section');
                if (targetSection) {
                    targetSection.classList.remove('hidden');
                    targetSection.classList.add('active');
                }
                
                // Update nav items immediately
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                const navItem = document.querySelector(`a[href="#${sectionToShow}"]`);
                if (navItem) {
                    navItem.classList.add('active');
                }
                
                // Update page title immediately
                const titles = {
                    'dashboard': 'Dashboard',
                    'hr-accounts': 'HR Accounts Management',
                    'system-maintenance': 'System Maintenance',
                    'deleted-items': 'Deleted Items Management',
                    'view-archives': 'View Archives'
                };
                const pageTitle = document.getElementById('page-title');
                if (pageTitle) {
                    pageTitle.textContent = titles[sectionToShow] || 'Dashboard';
                }
                
                currentSection = sectionToShow;
            }
        })();
        
        // Handle hash fragment on page load to show correct section
        document.addEventListener('DOMContentLoaded', function() {
            // Set maintenance toggle based on current status
            const isMaintenanceMode = <?= $is_maintenance ? 'true' : 'false' ?>;
            const toggle = document.getElementById('maintenanceToggle');
            if (toggle) {
                toggle.checked = isMaintenanceMode;
            }
            
            // Clean up activeSection flag if it was used
            const activeSection = sessionStorage.getItem('activeSection');
            if (activeSection === 'deleted-items') {
                sessionStorage.removeItem('activeSection');
            }
            
            // Handle hash in URL if no saved section
            const savedSection = sessionStorage.getItem('currentSection');
            if (!savedSection && !activeSection) {
                const hash = window.location.hash;
                if (hash === '#hr-accounts') {
                    showSection('hr-accounts');
                } else if (hash) {
                    const sectionName = hash.substring(1);
                    showSection(sectionName);
                }
            }
            
            // Initialize pagination for deleted items
            initDeletedItemsPagination();
        });

        // Pagination for Deleted Students and Employees
        let studentsCurrentPage = 1;
        let employeesCurrentPage = 1;
        const itemsPerPage = 5;

        function initDeletedItemsPagination() {
            updateStudentsPagination();
            updateEmployeesPagination();
        }

        function updateStudentsPagination() {
            const rows = document.querySelectorAll('tr[data-student-id]');
            const totalItems = rows.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Hide all rows first
            rows.forEach(row => row.style.display = 'none');
            
            // Show only current page rows
            const start = (studentsCurrentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            for (let i = start; i < end && i < totalItems; i++) {
                rows[i].style.display = '';
            }
            
            // Update pagination info
            document.getElementById('students-start').textContent = totalItems > 0 ? start + 1 : 0;
            document.getElementById('students-end').textContent = Math.min(end, totalItems);
            document.getElementById('students-total').textContent = totalItems;
            
            // Update button states
            document.getElementById('students-prev').disabled = studentsCurrentPage === 1;
            document.getElementById('students-next').disabled = studentsCurrentPage >= totalPages || totalItems === 0;
            
            // Hide pagination if no items
            const pagination = document.getElementById('students-pagination');
            if (pagination) {
                pagination.style.display = totalItems === 0 ? 'none' : 'flex';
            }
        }

        function updateEmployeesPagination() {
            const rows = document.querySelectorAll('tr[data-employee-id]');
            const totalItems = rows.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Hide all rows first
            rows.forEach(row => row.style.display = 'none');
            
            // Show only current page rows
            const start = (employeesCurrentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            for (let i = start; i < end && i < totalItems; i++) {
                rows[i].style.display = '';
            }
            
            // Update pagination info
            document.getElementById('employees-start').textContent = totalItems > 0 ? start + 1 : 0;
            document.getElementById('employees-end').textContent = Math.min(end, totalItems);
            document.getElementById('employees-total').textContent = totalItems;
            
            // Update button states
            document.getElementById('employees-prev').disabled = employeesCurrentPage === 1;
            document.getElementById('employees-next').disabled = employeesCurrentPage >= totalPages || totalItems === 0;
            
            // Hide pagination if no items
            const pagination = document.getElementById('employees-pagination');
            if (pagination) {
                pagination.style.display = totalItems === 0 ? 'none' : 'flex';
            }
        }

        function changeStudentsPage(direction) {
            const rows = document.querySelectorAll('tr[data-student-id]');
            const totalPages = Math.ceil(rows.length / itemsPerPage);
            
            studentsCurrentPage += direction;
            studentsCurrentPage = Math.max(1, Math.min(studentsCurrentPage, totalPages));
            
            updateStudentsPagination();
        }

        function changeEmployeesPage(direction) {
            const rows = document.querySelectorAll('tr[data-employee-id]');
            const totalPages = Math.ceil(rows.length / itemsPerPage);
            
            employeesCurrentPage += direction;
            employeesCurrentPage = Math.max(1, Math.min(employeesCurrentPage, totalPages));
            
            updateEmployeesPagination();
        }

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
        const notLoggedInItemsPerPage = 10;
        
        async function loadNotLoggedIn(type, page = 1) {
            console.log(`Loading ${type}, page ${page}`);
            const list = document.getElementById(`${type}-list`);
            const loading = document.getElementById(`${type}-loading`);
            const pagination = document.getElementById(`${type}-pagination`);
            
            console.log('Elements found:', {list: !!list, loading: !!loading, pagination: !!pagination});
            
            if (!list) {
                console.error(`List element not found: ${type}-list`);
                return;
            }
            
            if (!loading) {
                console.error(`Loading element not found: ${type}-loading`);
                return;
            }
            
            loading.classList.remove('hidden');
            
            try {
                const offset = (page - 1) * notLoggedInItemsPerPage;
                // Relative path - load_more_users.php is in the same directory
                const url = `load_more_users.php?type=${type}&offset=${offset}&limit=${notLoggedInItemsPerPage}`;
                console.log('Fetching:', url);
                
                // Add timeout to prevent infinite loading
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch(url, { signal: controller.signal });
                clearTimeout(timeoutId);
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Data received:', data);
                
                if (data.error) {
                    console.error('Error loading users:', data.error);
                    list.innerHTML = `<li class="text-center py-8"><p class="text-red-500 font-medium">Error: ${data.error}</p></li>`;
                    return;
                }
                
                // Clear list
                list.innerHTML = '';
                
                // Store all items in global arrays for filtering
                if (page === 1) {
                    if (type === 'employees') {
                        allEmployees = data.all_items || data.items;
                    } else {
                        allStudents = data.all_items || data.items;
                    }
                }
                
                // Add items
                if (data.items.length === 0) {
                    const emptyIcon = type === 'employees' 
                        ? '<svg class="w-12 h-12 mx-auto mb-2 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'
                        : '<svg class="w-12 h-12 mx-auto mb-2 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>';
                    const message = type === 'employees' ? 'All employees have logged in today!' : 'All students & parents have logged in today!';
                    list.innerHTML = `<li class="text-center py-8">${emptyIcon}<p class="text-gray-500 font-medium">${message}</p><p class="text-gray-400 text-sm mt-1">Great attendance 🎉</p></li>`;
                    pagination.classList.add('hidden');
                } else {
                    // Role color mapping
                    const roleColors = {
                        'teacher': { bg: 'bg-green-500', border: 'border-green-100', hover: 'hover:bg-green-50' },
                        'registrar': { bg: 'bg-indigo-500', border: 'border-indigo-100', hover: 'hover:bg-indigo-50' },
                        'hr': { bg: 'bg-orange-500', border: 'border-orange-100', hover: 'hover:bg-orange-50' },
                        'cashier': { bg: 'bg-yellow-500', border: 'border-yellow-100', hover: 'hover:bg-yellow-50' },
                        'guidance': { bg: 'bg-pink-500', border: 'border-pink-100', hover: 'hover:bg-pink-50' },
                        'attendance': { bg: 'bg-teal-500', border: 'border-teal-100', hover: 'hover:bg-teal-50' },
                        'student': { bg: 'bg-blue-500', border: 'border-blue-100', hover: 'hover:bg-blue-50' },
                        'parent': { bg: 'bg-cyan-500', border: 'border-cyan-100', hover: 'hover:bg-cyan-50' }
                    };
                    
                    data.items.forEach(item => {
                        const li = document.createElement('li');
                        
                        // Extract name from item (format: "• Name (ID)" or "• First, Last (ID)")
                        // Remove bullet point and extract name before parentheses
                        const cleanItem = item.replace(/•\s*/, '').trim();
                        const nameMatch = cleanItem.match(/^([^(]+)/);
                        const name = nameMatch ? nameMatch[1].trim() : '';
                        
                        // Extract role from the string (e.g., "Name (ID) - Role")
                        const roleMatch = cleanItem.match(/-\s*(\w+)\s*$/i);
                        const role = roleMatch ? roleMatch[1].toLowerCase() : (type === 'employees' ? 'teacher' : 'student');
                        
                        // Get colors based on role
                        const colors = roleColors[role] || (type === 'employees' ? roleColors['teacher'] : roleColors['student']);
                        
                        li.className = `flex items-center gap-3 p-3 rounded-lg border ${colors.border} ${colors.hover} transition-all`;
                        
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
                                <div class="w-10 h-10 rounded-full ${colors.bg} flex items-center justify-center text-white font-bold text-sm shadow-md">
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
                    
                    if (total > notLoggedInItemsPerPage) {
                        pagination.classList.remove('hidden');
                    } else {
                        pagination.classList.add('hidden');
                    }
                }
                
            } catch (error) {
                console.error('Error loading users:', error);
                const errorMessage = error.name === 'AbortError' ? 'Request timed out' : error.message;
                list.innerHTML = `<li class="text-center py-8">
                    <svg class="w-12 h-12 text-red-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-500 font-medium">Error loading data</p>
                    <p class="text-gray-500 text-sm mt-1">${errorMessage}</p>
                    <button onclick="loadNotLoggedIn('${type}', ${page})" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        Retry
                    </button>
                </li>`;
            } finally {
                console.log('Finally block - hiding loading spinner');
                if (loading) {
                    loading.classList.add('hidden');
                }
            }
        }
        
        function updatePagination(type, page, total) {
            const start = (page - 1) * notLoggedInItemsPerPage + 1;
            const end = Math.min(page * notLoggedInItemsPerPage, total);
            
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
        
        // Filter functions for Not Logged In sections
        let allEmployees = [];
        let allStudents = [];
        
        function filterEmployees() {
            const searchTerm = document.getElementById('employee-search').value.toLowerCase();
            const roleFilter = document.getElementById('employee-role-filter').value.toLowerCase();
            
            const filtered = allEmployees.filter(emp => {
                const matchesSearch = emp.toLowerCase().includes(searchTerm);
                const matchesRole = roleFilter === 'all' || emp.toLowerCase().includes(roleFilter);
                return matchesSearch && matchesRole;
            });
            
            displayFilteredEmployees(filtered);
        }
        
        function filterStudents() {
            const searchTerm = document.getElementById('student-search').value.toLowerCase();
            const typeFilter = document.getElementById('student-type-filter').value.toLowerCase();
            
            const filtered = allStudents.filter(student => {
                const matchesSearch = student.toLowerCase().includes(searchTerm);
                const matchesType = typeFilter === 'all' || 
                    (typeFilter === 'parent' && student.toLowerCase().includes('parent')) ||
                    (typeFilter === 'student' && !student.toLowerCase().includes('parent'));
                return matchesSearch && matchesType;
            });
            
            displayFilteredStudents(filtered);
        }
        
        function clearEmployeeFilters() {
            document.getElementById('employee-search').value = '';
            document.getElementById('employee-role-filter').value = 'all';
            displayFilteredEmployees(allEmployees);
        }
        
        function clearStudentFilters() {
            document.getElementById('student-search').value = '';
            document.getElementById('student-type-filter').value = 'all';
            displayFilteredStudents(allStudents);
        }
        
        function displayFilteredEmployees(employees) {
            const list = document.getElementById('employees-list');
            const pagination = document.getElementById('employees-pagination');
            
            if (employees.length === 0) {
                list.innerHTML = '<li class="text-center py-8 text-gray-500">No employees found</li>';
                pagination.classList.add('hidden');
                return;
            }
            
            // Role color mapping
            const roleColors = {
                'teacher': { bg: 'bg-green-500', border: 'border-green-100', hover: 'hover:bg-green-50' },
                'registrar': { bg: 'bg-indigo-500', border: 'border-indigo-100', hover: 'hover:bg-indigo-50' },
                'hr': { bg: 'bg-orange-500', border: 'border-orange-100', hover: 'hover:bg-orange-50' },
                'cashier': { bg: 'bg-yellow-500', border: 'border-yellow-100', hover: 'hover:bg-yellow-50' },
                'guidance': { bg: 'bg-pink-500', border: 'border-pink-100', hover: 'hover:bg-pink-50' },
                'attendance': { bg: 'bg-teal-500', border: 'border-teal-100', hover: 'hover:bg-teal-50' }
            };
            
            list.innerHTML = employees.slice(0, 10).map(emp => {
                const cleanItem = emp.replace(/•\s*/, '').trim();
                const nameMatch = cleanItem.match(/^([^(]+)/);
                const name = nameMatch ? nameMatch[1].trim() : '';
                
                // Extract role from the string (e.g., "Name (ID) - Role")
                const roleMatch = cleanItem.match(/-\s*(\w+)\s*$/i);
                const role = roleMatch ? roleMatch[1].toLowerCase() : 'teacher';
                
                const colors = roleColors[role] || roleColors['teacher'];
                
                let nameParts;
                if (name.includes(',')) {
                    nameParts = name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                } else {
                    nameParts = name.split(' ').filter(p => p.length > 0);
                }
                
                let initials = '?';
                if (nameParts.length >= 2) {
                    initials = (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
                } else if (nameParts.length === 1 && nameParts[0].length >= 2) {
                    initials = nameParts[0].substring(0, 2).toUpperCase();
                }
                
                return `
                    <li class="flex items-center gap-3 p-3 rounded-lg border ${colors.border} ${colors.hover} transition-all">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full ${colors.bg} flex items-center justify-center text-white font-bold text-sm shadow-md">
                                ${initials}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${emp.replace(/•\s*/, '')}</p>
                            <p class="text-xs text-gray-500">Not logged in today</p>
                        </div>
                    </li>
                `;
            }).join('');
            
            if (employees.length > 10) {
                pagination.classList.remove('hidden');
                document.getElementById('employees-total').textContent = employees.length;
            } else {
                pagination.classList.add('hidden');
            }
        }
        
        function displayFilteredStudents(students) {
            const list = document.getElementById('students-list');
            const pagination = document.getElementById('students-pagination');
            
            if (students.length === 0) {
                list.innerHTML = '<li class="text-center py-8 text-gray-500">No students found</li>';
                pagination.classList.add('hidden');
                return;
            }
            
            list.innerHTML = students.slice(0, 10).map(student => {
                const cleanItem = student.replace(/•\s*/, '').trim();
                const nameMatch = cleanItem.match(/^([^(]+)/);
                const name = nameMatch ? nameMatch[1].trim() : '';
                
                let nameParts;
                if (name.includes(',')) {
                    nameParts = name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                } else {
                    nameParts = name.split(' ').filter(p => p.length > 0);
                }
                
                let initials = '?';
                if (nameParts.length >= 2) {
                    initials = (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
                } else if (nameParts.length === 1 && nameParts[0].length >= 2) {
                    initials = nameParts[0].substring(0, 2).toUpperCase();
                }
                
                const isParent = student.toLowerCase().includes('parent');
                const bgColor = isParent ? 'bg-cyan-500' : 'bg-blue-500';
                const borderColor = isParent ? 'border-cyan-100' : 'border-blue-100';
                const hoverColor = isParent ? 'hover:bg-cyan-50' : 'hover:bg-blue-50';
                
                return `
                    <li class="flex items-center gap-3 p-3 rounded-lg border ${borderColor} ${hoverColor} transition-all">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full ${bgColor} flex items-center justify-center text-white font-bold text-sm shadow-md">
                                ${initials}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${student.replace(/•\s*/, '')}</p>
                            <p class="text-xs text-gray-500">Not logged in today</p>
                        </div>
                    </li>
                `;
            }).join('');
            
            if (students.length > 10) {
                pagination.classList.remove('hidden');
                document.getElementById('students-total').textContent = students.length;
            } else {
                pagination.classList.add('hidden');
            }
        }
        
        // Pagination for Today's Logins
        let loginsPage = 1;
        const loginsPerPage = 10;
        
        function updateLoginsDisplay() {
            const rows = document.querySelectorAll('.login-row');
            // Only count visible rows (not filtered out)
            const visibleRows = Array.from(rows).filter(row => !row.classList.contains('hidden'));
            const total = visibleRows.length;
            
            if (total === 0) {
                // Hide all rows if no visible rows
                rows.forEach(row => row.style.display = 'none');
                return;
            }
            
            const start = (loginsPage - 1) * loginsPerPage;
            const end = start + loginsPerPage;
            
            // Hide all rows first
            rows.forEach(row => row.style.display = 'none');
            
            // Show only visible rows for current page
            visibleRows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = '';
                }
            });
            
            // Update pagination info only if elements exist
            const startEl = document.getElementById('logins-start');
            const endEl = document.getElementById('logins-end');
            const totalEl = document.getElementById('logins-total');
            const prevBtn = document.getElementById('logins-prev');
            const nextBtn = document.getElementById('logins-next');
            
            if (startEl) startEl.textContent = total > 0 ? start + 1 : 0;
            if (endEl) endEl.textContent = Math.min(end, total);
            if (totalEl) totalEl.textContent = total;
            
            // Update button states
            if (prevBtn) prevBtn.disabled = loginsPage === 1;
            if (nextBtn) nextBtn.disabled = end >= total;
        }
        
        function changeLoginsPage(direction) {
            const rows = document.querySelectorAll('.login-row:not(.hidden)');
            const totalPages = Math.ceil(rows.length / loginsPerPage);
            
            loginsPage += direction;
            if (loginsPage < 1) loginsPage = 1;
            if (loginsPage > totalPages) loginsPage = totalPages;
            
            updateLoginsDisplay();
        }
        
        // Filter functions for Today's Logins
        function updateRoleOptions() {
            const userTypeFilter = document.getElementById('filter-user-type').value.toLowerCase();
            const roleSelect = document.getElementById('filter-role');
            
            // Define role options for each user type
            const roleOptions = {
                'all': [
                    { value: 'all', label: 'All' },
                    { value: 'student', label: 'Student' },
                    { value: 'parent', label: 'Parent' },
                    { value: 'teacher', label: 'Teacher' },
                    { value: 'registrar', label: 'Registrar' },
                    { value: 'cashier', label: 'Cashier' },
                    { value: 'guidance', label: 'Guidance' },
                    { value: 'hr', label: 'HR' },
                    { value: 'attendance', label: 'Attendance' }
                ],
                'student': [
                    { value: 'all', label: 'All' },
                    { value: 'student', label: 'Student' }
                ],
                'parent': [
                    { value: 'all', label: 'All' },
                    { value: 'parent', label: 'Parent' }
                ],
                'employee': [
                    { value: 'all', label: 'All' },
                    { value: 'teacher', label: 'Teacher' },
                    { value: 'registrar', label: 'Registrar' },
                    { value: 'cashier', label: 'Cashier' },
                    { value: 'guidance', label: 'Guidance' },
                    { value: 'hr', label: 'HR' },
                    { value: 'attendance', label: 'Attendance' }
                ]
            };
            
            // Get current selected role
            const currentRole = roleSelect.value;
            
            // Clear existing options
            roleSelect.innerHTML = '';
            
            // Get options for selected user type
            const options = roleOptions[userTypeFilter] || roleOptions['all'];
            
            // Add options
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.label;
                optionElement.className = 'text-gray-900';
                roleSelect.appendChild(optionElement);
            });
            
            // Try to restore previous selection if it exists in new options
            const optionExists = options.some(opt => opt.value === currentRole);
            if (optionExists) {
                roleSelect.value = currentRole;
            } else {
                roleSelect.value = 'all';
            }
        }
        
        function filterLogins() {
            const userTypeFilter = document.getElementById('filter-user-type').value.toLowerCase();
            const roleFilter = document.getElementById('filter-role').value.toLowerCase();
            const searchFilter = document.getElementById('filter-search').value.toLowerCase();
            const rows = document.querySelectorAll('.login-row');
            
            let visibleCount = 0;
            rows.forEach(row => {
                const userType = row.getAttribute('data-user-type');
                const role = row.getAttribute('data-role');
                const idNumber = row.getAttribute('data-id') || '';
                const name = row.getAttribute('data-name') || '';
                
                const userTypeMatch = userTypeFilter === 'all' || userType === userTypeFilter;
                const roleMatch = roleFilter === 'all' || role === roleFilter;
                const searchMatch = searchFilter === '' || 
                    idNumber.toLowerCase().includes(searchFilter) || 
                    name.toLowerCase().includes(searchFilter);
                
                if (userTypeMatch && roleMatch && searchMatch) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });
            
            // Reset to page 1 and update display
            loginsPage = 1;
            updateLoginsDisplay();
            
            // Show message if no results
            const tbody = document.getElementById('logins-tbody');
            const existingMessage = tbody.querySelector('.no-results-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            if (visibleCount === 0) {
                const messageRow = document.createElement('tr');
                messageRow.className = 'no-results-message';
                messageRow.innerHTML = `
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        No logins match the selected filters
                    </td>
                `;
                tbody.appendChild(messageRow);
            }
        }
        
        function clearFilters() {
            document.getElementById('filter-user-type').value = 'all';
            document.getElementById('filter-role').value = 'all';
            document.getElementById('filter-search').value = '';
            filterLogins();
        }
        
        // Validate date range - prevent dates before 2025 and future dates
        function validateDateRange(input) {
            const selectedDate = new Date(input.value);
            const minDate = new Date('2025-01-01');
            const maxDate = new Date();
            maxDate.setHours(23, 59, 59, 999); // End of today
            
            if (selectedDate < minDate) {
                alert('Please select a date from 2025 onwards.');
                input.value = '';
                return false;
            }
            
            if (selectedDate > maxDate) {
                alert('Future dates are not allowed. Please select today or an earlier date.');
                input.value = '';
                return false;
            }
            
            // Validate From Date vs To Date
            const fromDateInput = document.getElementById('history-date-from');
            const toDateInput = document.getElementById('history-date-to');
            
            if (fromDateInput.value && toDateInput.value) {
                const fromDate = new Date(fromDateInput.value);
                const toDate = new Date(toDateInput.value);
                
                if (fromDate > toDate) {
                    alert('From Date cannot be later than To Date.');
                    input.value = '';
                    return false;
                }
            }
            
            return true;
        }

        // Update date range constraints dynamically
        function updateDateConstraints() {
            const dateFromInput = document.getElementById('history-date-from');
            const dateToInput = document.getElementById('history-date-to');
            const today = new Date().toISOString().split('T')[0];
            
            if (dateFromInput && dateToInput) {
                // If From Date is selected, set To Date minimum to From Date
                if (dateFromInput.value) {
                    dateToInput.setAttribute('min', dateFromInput.value);
                } else {
                    dateToInput.setAttribute('min', '2025-01-01');
                }
                
                // If To Date is selected, set From Date maximum to To Date
                if (dateToInput.value) {
                    dateFromInput.setAttribute('max', dateToInput.value);
                } else {
                    dateFromInput.setAttribute('max', today);
                }
            }
        }

        // Initialize pagination on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set min and max dates for date inputs to prevent selecting dates before 2025 and future dates
            const dateFromInput = document.getElementById('history-date-from');
            const dateToInput = document.getElementById('history-date-to');
            
            if (dateFromInput && dateToInput) {
                const today = new Date().toISOString().split('T')[0];
                dateFromInput.setAttribute('min', '2025-01-01');
                dateFromInput.setAttribute('max', today);
                dateToInput.setAttribute('min', '2025-01-01');
                dateToInput.setAttribute('max', today);
            }
            
            // Initialize role options
            updateRoleOptions();
            
            // Initialize Today's Logins pagination only if pagination elements exist
            const loginsTable = document.getElementById('logins-table');
            const loginsPagination = document.getElementById('logins-start');
            if (loginsTable && loginsPagination) {
                updateLoginsDisplay();
            }
            
            // Initialize Not Logged In sections
            loadNotLoggedIn('employees', 1);
            loadNotLoggedIn('students', 1);
            
            // Load archive counts on page load
            loadArchiveCounts();
        });

        // Archive Management Functions
        let currentArchiveType = '';
        let currentArchivePage = 1;
        let currentArchiveSearch = '';
        const archiveItemsPerPage = 20;

        async function loadArchiveCounts() {
            try {
                // Get students count
                const studentsResponse = await fetch('get_archived_records.php?type=students&limit=1&offset=0');
                const studentsData = await studentsResponse.json();
                document.getElementById('archived-students-count').textContent = studentsData.total || 0;

                // Get employees count
                const employeesResponse = await fetch('get_archived_records.php?type=employees&limit=1&offset=0');
                const employeesData = await employeesResponse.json();
                document.getElementById('archived-employees-count').textContent = employeesData.total || 0;

                // Calculate total
                const total = (studentsData.total || 0) + (employeesData.total || 0);
                document.getElementById('total-archived-count').textContent = total;
            } catch (error) {
                console.error('Error loading archive counts:', error);
            }
        }

        async function updateDeletedCounts() {
            try {
                // Count deleted students in the table
                const studentRows = document.querySelectorAll('tr[data-student-id]');
                const studentCount = studentRows.length;
                
                // Update deleted students count in card
                const deletedStudentsCount = document.getElementById('deleted-students-count');
                if (deletedStudentsCount) {
                    deletedStudentsCount.textContent = studentCount;
                }
                
                // Update deleted students count in table header
                const deletedStudentsTableCount = document.getElementById('deleted-students-table-count');
                if (deletedStudentsTableCount) {
                    deletedStudentsTableCount.textContent = studentCount;
                }
                
                // Count deleted employees in the table
                const employeeRows = document.querySelectorAll('tr[data-employee-id]');
                const employeeCount = employeeRows.length;
                
                // Update deleted employees count in card
                const deletedEmployeesCount = document.getElementById('deleted-employees-count');
                if (deletedEmployeesCount) {
                    deletedEmployeesCount.textContent = employeeCount;
                }
                
                // Update deleted employees count in table header
                const deletedEmployeesTableCount = document.getElementById('deleted-employees-table-count');
                if (deletedEmployeesTableCount) {
                    deletedEmployeesTableCount.textContent = employeeCount;
                }
            } catch (error) {
                console.error('Error updating deleted counts:', error);
            }
        }

        async function loadArchives(type) {
            currentArchiveType = type;
            currentArchivePage = 1;
            currentArchiveSearch = '';
            document.getElementById('archive-search').value = '';
            
            const viewer = document.getElementById('archive-viewer');
            viewer.classList.remove('hidden');
            
            // Update title
            const title = type === 'students' ? 'Archived Students' : 'Archived Employees';
            document.getElementById('archive-title').textContent = title;
            document.getElementById('archive-subtitle').textContent = `Viewing ${type} in permanent archive`;
            
            // Set up table headers
            const headerRow = document.getElementById('archive-table-header');
            if (type === 'students') {
                headerRow.innerHTML = `
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade/Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archived Info</th>
                `;
            } else {
                headerRow.innerHTML = `
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archived Info</th>
                `;
            }
            
            await fetchArchiveData();
        }

        async function fetchArchiveData() {
            const tbody = document.getElementById('archive-table-body');
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>';
            
            try {
                const offset = (currentArchivePage - 1) * archiveItemsPerPage;
                const url = `get_archived_records.php?type=${currentArchiveType}&offset=${offset}&limit=${archiveItemsPerPage}&search=${encodeURIComponent(currentArchiveSearch)}`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">${data.message || 'Error loading data'}</td></tr>`;
                    return;
                }
                
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No archived records found</td></tr>';
                    return;
                }
                
                // Render data
                tbody.innerHTML = '';
                data.data.forEach(record => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    
                    if (currentArchiveType === 'students') {
                        const initials = (record.first_name[0] + record.last_name[0]).toUpperCase();
                        row.innerHTML = `
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span class="text-purple-600 font-medium text-sm">${initials}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">${record.first_name} ${record.last_name}</div>
                                        <div class="text-sm text-gray-500">ID: ${record.id_number || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">${record.grade_level || 'N/A'}</div>
                                <div class="text-sm text-gray-500">${record.section || 'N/A'}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">${new Date(record.archived_at).toLocaleString()}</div>
                                <div class="text-sm text-gray-500">By: ${record.archived_by}</div>
                                ${record.deletion_reason ? `<div class="text-xs text-gray-500 mt-1">${record.deletion_reason}</div>` : ''}
                            </td>
                        `;
                    } else {
                        const initials = (record.first_name[0] + record.last_name[0]).toUpperCase();
                        row.innerHTML = `
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-medium text-sm">${initials}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">${record.first_name} ${record.last_name}</div>
                                        <div class="text-sm text-gray-500">ID: ${record.id_number || 'N/A'}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">${record.position || 'N/A'}</div>
                                <div class="text-sm text-gray-500">${record.department || 'N/A'}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">${new Date(record.archived_at).toLocaleString()}</div>
                                <div class="text-sm text-gray-500">By: ${record.archived_by}</div>
                                ${record.deletion_reason ? `<div class="text-xs text-gray-500 mt-1">${record.deletion_reason}</div>` : ''}
                            </td>
                        `;
                    }
                    
                    tbody.appendChild(row);
                });
                
                // Update pagination
                const start = offset + 1;
                const end = Math.min(offset + archiveItemsPerPage, data.total);
                document.getElementById('archive-start').textContent = start;
                document.getElementById('archive-end').textContent = end;
                document.getElementById('archive-total').textContent = data.total;
                
                document.getElementById('archive-prev').disabled = currentArchivePage === 1;
                document.getElementById('archive-next').disabled = end >= data.total;
                
            } catch (error) {
                console.error('Error fetching archive data:', error);
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500">Error loading data: ${error.message}</td></tr>`;
            }
        }

        function searchArchives() {
            const searchTerm = document.getElementById('archive-search').value.toLowerCase();
            
            if (currentDataArchiveType) {
                // Searching data archives (login/attendance)
                if (!searchTerm) {
                    // If search is empty, reload all data
                    loadDataArchives(currentDataArchiveType);
                    return;
                }
                
                // Filter records based on search term
                const allRecords = allDataArchiveRecords;
                allDataArchiveRecords = allRecords.filter(record => {
                    if (currentDataArchiveType === 'login') {
                        return (record.username && record.username.toLowerCase().includes(searchTerm)) ||
                               (record.full_name && record.full_name.toLowerCase().includes(searchTerm)) ||
                               (record.id_number && record.id_number.toLowerCase().includes(searchTerm)) ||
                               (record.role && record.role.toLowerCase().includes(searchTerm));
                    } else {
                        return (record.id_number && record.id_number.toLowerCase().includes(searchTerm)) ||
                               (record.name && record.name.toLowerCase().includes(searchTerm));
                    }
                });
                
                currentDataArchivePage = 1;
                displayDataArchivePage();
            } else {
                // Searching regular archives (students/employees)
                currentArchiveSearch = searchTerm;
                currentArchivePage = 1;
                fetchArchiveData();
            }
        }

        function changeArchivePage(direction) {
            if (currentDataArchiveType) {
                // For data archives
                changeDataArchivePage(direction);
            } else {
                // For regular archives
                currentArchivePage += direction;
                if (currentArchivePage < 1) currentArchivePage = 1;
                fetchArchiveData();
            }
        }
        
        function changeDataArchivePage(direction) {
            currentDataArchivePage += direction;
            if (currentDataArchivePage < 1) currentDataArchivePage = 1;
            displayDataArchivePage();
        }

        function closeArchiveViewer() {
            document.getElementById('archive-viewer').classList.add('hidden');
            currentDataArchiveType = '';
            allDataArchiveRecords = [];
        }

        // Load Data Archives (Login Logs & Attendance)
        let currentDataArchivePage = 1;
        let currentDataArchiveType = '';
        let allDataArchiveRecords = [];
        const dataArchivePerPage = 10;

        async function loadDataArchives(type) {
            currentDataArchiveType = type;
            currentDataArchivePage = 1;
            
            const viewer = document.getElementById('archive-viewer');
            viewer.classList.remove('hidden');
            
            // Update title
            const title = type === 'login' ? 'Login Logs Archive' : 'Attendance Records Archive';
            document.getElementById('archive-title').textContent = title;
            document.getElementById('archive-subtitle').textContent = `Viewing archived ${type} records`;
            
            // Set up table headers
            const headerRow = document.getElementById('archive-table-header');
            if (type === 'login') {
                headerRow.innerHTML = `
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">User Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Login Time</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Logout Time</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Duration</th>
                `;
            } else {
                headerRow.innerHTML = `
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time In/Out</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                `;
            }
            
            // Fetch and display data
            const tbody = document.getElementById('archive-table-body');
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">Loading...</td></tr>';
            
            try {
                const response = await fetch(`get_data_archives.php?type=${type}`);
                const data = await response.json();
                
                console.log('Archive data received:', data); // Debug log
                
                if (!data.success) {
                    const errorMsg = data.message || 'Error loading data';
                    tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-8 text-center text-red-500">${errorMsg}</td></tr>`;
                    console.error('API Error:', errorMsg);
                    return;
                }
                
                if (!data.records || data.records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No archived records found</td></tr>';
                    return;
                }
                
                // Store all records for pagination
                allDataArchiveRecords = data.records;
                displayDataArchivePage();
                
            } catch (error) {
                console.error('Error:', error);
                tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-8 text-center text-red-500">Error: ${error.message}</td></tr>`;
            }
        }

        function displayDataArchivePage() {
            const tbody = document.getElementById('archive-table-body');
            tbody.innerHTML = '';
            
            const start = (currentDataArchivePage - 1) * dataArchivePerPage;
            const end = start + dataArchivePerPage;
            const pageRecords = allDataArchiveRecords.slice(start, end);
            
            if (pageRecords.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No records found</td></tr>';
                return;
            }
            
            const userTypeColors = {
                'employee': 'bg-purple-100 text-purple-700',
                'student': 'bg-blue-100 text-blue-700',
                'parent': 'bg-cyan-100 text-cyan-700'
            };
            
            const roleColors = {
                'superadmin': 'bg-red-100 text-red-700',
                'hr': 'bg-orange-100 text-orange-700',
                'teacher': 'bg-green-100 text-green-700',
                'registrar': 'bg-indigo-100 text-indigo-700',
                'cashier': 'bg-yellow-100 text-yellow-700',
                'guidance': 'bg-pink-100 text-pink-700',
                'attendance': 'bg-teal-100 text-teal-700',
                'student': 'bg-blue-100 text-blue-700',
                'parent': 'bg-cyan-100 text-cyan-700'
            };
            
            pageRecords.forEach(record => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors';
                
                if (currentDataArchiveType === 'login') {
                    // Safely get values with defaults
                    const userType = record.user_type || 'unknown';
                    const role = record.role || 'user';
                    const userTypeColor = userTypeColors[userType.toLowerCase()] || 'bg-gray-100 text-gray-700';
                    const roleColor = roleColors[role.toLowerCase()] || 'bg-gray-100 text-gray-700';
                    
                    const loginDate = new Date(record.login_time);
                    const logoutTime = record.logout_time ? new Date(record.logout_time) : null;
                    
                    let duration = '---';
                    if (record.session_duration) {
                        const hours = Math.floor(record.session_duration / 3600);
                        const minutes = Math.floor((record.session_duration % 3600) / 60);
                        duration = hours > 0 ? `${hours}h ${minutes}m` : `${minutes} min`;
                    }
                    
                    // Format user type and role for display
                    const userTypeDisplay = userType.charAt(0).toUpperCase() + userType.slice(1);
                    const roleDisplay = role.charAt(0).toUpperCase() + role.slice(1);
                    
                    row.innerHTML = `
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${userTypeColor}">
                                ${userTypeDisplay}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-600 text-xs">${record.id_number || record.username || '---'}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">${record.full_name || record.username || '---'}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${roleColor}">
                                ${roleDisplay}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">${loginDate.toLocaleDateString()}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">${loginDate.toLocaleTimeString()}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            ${logoutTime ? logoutTime.toLocaleTimeString() : '<span class="text-green-600 font-medium">Active</span>'}
                        </td>
                        <td class="px-4 py-3 text-gray-600">${duration}</td>
                    `;
                } else {
                    row.innerHTML = `
                        <td class="px-6 py-4 text-sm text-gray-900">${record.id_number}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">${record.name}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">${new Date(record.date).toLocaleDateString()}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">${record.time_in || '---'} / ${record.time_out || '---'}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">${record.status}</span>
                        </td>
                    `;
                }
                
                tbody.appendChild(row);
            });
            
            // Update pagination
            const total = allDataArchiveRecords.length;
            const startNum = total > 0 ? start + 1 : 0;
            const endNum = Math.min(end, total);
            
            document.getElementById('archive-start').textContent = startNum;
            document.getElementById('archive-end').textContent = endNum;
            document.getElementById('archive-total').textContent = total;
            
            document.getElementById('archive-prev').disabled = currentDataArchivePage === 1;
            document.getElementById('archive-next').disabled = endNum >= total;
        }

        // Login History Modal Functions
        let historyPage = 1;
        const historyPerPage = 10;
        let historyData = [];

        function updateHistoryRoleOptions() {
            const userTypeFilter = document.getElementById('history-user-type').value.toLowerCase();
            const roleSelect = document.getElementById('history-role');
            
            // Define role options for each user type
            const roleOptions = {
                'all': [
                    { value: 'all', label: 'All' },
                    { value: 'student', label: 'Student' },
                    { value: 'parent', label: 'Parent' },
                    { value: 'teacher', label: 'Teacher' },
                    { value: 'registrar', label: 'Registrar' },
                    { value: 'cashier', label: 'Cashier' },
                    { value: 'guidance', label: 'Guidance' },
                    { value: 'hr', label: 'HR' },
                    { value: 'attendance', label: 'Attendance' }
                ],
                'student': [
                    { value: 'all', label: 'All' },
                    { value: 'student', label: 'Student' }
                ],
                'parent': [
                    { value: 'all', label: 'All' },
                    { value: 'parent', label: 'Parent' }
                ],
                'employee': [
                    { value: 'all', label: 'All' },
                    { value: 'teacher', label: 'Teacher' },
                    { value: 'registrar', label: 'Registrar' },
                    { value: 'cashier', label: 'Cashier' },
                    { value: 'guidance', label: 'Guidance' },
                    { value: 'hr', label: 'HR' },
                    { value: 'attendance', label: 'Attendance' }
                ]
            };
            
            // Get current selected role
            const currentRole = roleSelect.value;
            
            // Clear existing options
            roleSelect.innerHTML = '';
            
            // Get options for selected user type
            const options = roleOptions[userTypeFilter] || roleOptions['all'];
            
            // Add options
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.label;
                roleSelect.appendChild(optionElement);
            });
            
            // Try to restore previous selection if it exists in new options
            const optionExists = options.some(opt => opt.value === currentRole);
            if (optionExists) {
                roleSelect.value = currentRole;
            } else {
                roleSelect.value = 'all';
            }
        }

        function openLoginHistory() {
            document.getElementById('login-history-modal').classList.remove('hidden');
            // Prevent background scrolling
            document.body.style.overflow = 'hidden';
            
            // Clear date fields (leave empty to show all records)
            document.getElementById('history-date-to').value = '';
            document.getElementById('history-date-from').value = '';
            
            // Reset filters
            document.getElementById('history-user-type').value = 'all';
            document.getElementById('history-search').value = '';
            historyPage = 1;
            
            // Initialize role options
            updateHistoryRoleOptions();
            
            // Auto-load all records on open
            searchLoginHistory();
        }

        function closeLoginHistory() {
            document.getElementById('login-history-modal').classList.add('hidden');
            // Restore background scrolling
            document.body.style.overflow = '';
        }

        function autoSearchHistory() {
            // Debounce to prevent rapid-fire searches when changing filters
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                historyPage = 1;
                searchLoginHistory();
            }, 300); // Wait 300ms before searching
        }

        // Debounce search input to avoid too many requests while typing
        function debouncedHistorySearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                historyPage = 1;
                searchLoginHistory();
            }, 500); // Wait 500ms after user stops typing
        }

        let searchTimeout;
        let isSearching = false;
        
        async function searchLoginHistory() {
            // Prevent multiple simultaneous searches
            if (isSearching) {
                return;
            }
            
            const loading = document.getElementById('history-loading');
            const table = document.getElementById('history-table');
            const noResults = document.getElementById('history-no-results');
            const initialMessage = document.getElementById('history-initial-message');
            const pagination = document.getElementById('history-pagination');
            
            // Only show loading spinner if request takes longer than 200ms
            isSearching = true;
            let showLoadingTimeout = setTimeout(() => {
                loading.classList.remove('hidden');
                table.classList.add('hidden');
                noResults.classList.add('hidden');
                initialMessage.classList.add('hidden');
                pagination.classList.add('hidden');
            }, 200);
            
            try {
                const dateFrom = document.getElementById('history-date-from').value;
                const dateTo = document.getElementById('history-date-to').value;
                const userType = document.getElementById('history-user-type').value;
                const role = document.getElementById('history-role').value;
                const search = document.getElementById('history-search').value;
                
                const params = new URLSearchParams({
                    date_from: dateFrom,
                    date_to: dateTo,
                    user_type: userType,
                    role: role,
                    search: search,
                    page: historyPage,
                    limit: 10
                });
                
                const response = await fetch(`get_login_history.php?${params}`);
                const data = await response.json();
                
                console.log('Login history response:', data); // Debug log
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                historyData = data.records || [];
                displayHistoryResults(data);
                
            } catch (error) {
                console.error('Error loading history:', error);
                alert('Error loading login history: ' + error.message);
            } finally {
                clearTimeout(showLoadingTimeout);
                loading.classList.add('hidden');
                isSearching = false;
            }
        }

        function displayHistoryResults(data) {
            const table = document.getElementById('history-table');
            const tbody = document.getElementById('history-tbody');
            const noResults = document.getElementById('history-no-results');
            const initialMessage = document.getElementById('history-initial-message');
            const pagination = document.getElementById('history-pagination');
            const dateIndicator = document.getElementById('history-date-indicator');
            const dateRange = document.getElementById('history-date-range');
            
            tbody.innerHTML = '';
            
            if (!data.records || data.records.length === 0) {
                table.classList.add('hidden');
                noResults.classList.remove('hidden');
                initialMessage.classList.add('hidden');
                dateIndicator.classList.add('hidden');
                pagination.classList.add('hidden');
                return;
            }
            
            // Hide messages and show table when we have records
            table.classList.remove('hidden');
            noResults.classList.add('hidden');
            initialMessage.classList.add('hidden');
            
            // Calculate date range from records
            const dates = data.records.map(r => new Date(r.login_time).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            }));
            const uniqueDates = [...new Set(dates)];
            
            if (uniqueDates.length === 1) {
                dateRange.textContent = uniqueDates[0];
            } else if (uniqueDates.length > 1) {
                // Sort dates and show range
                const sortedDates = uniqueDates.sort((a, b) => new Date(a) - new Date(b));
                dateRange.textContent = `${sortedDates[0]} - ${sortedDates[sortedDates.length - 1]}`;
            }
            
            dateIndicator.classList.remove('hidden');
            
            data.records.forEach(record => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors';
                
                const userTypeColors = {
                    'employee': 'bg-purple-100 text-purple-700',
                    'student': 'bg-blue-100 text-blue-700',
                    'parent': 'bg-cyan-100 text-cyan-700'
                };
                
                const roleColors = {
                    'superadmin': 'bg-red-100 text-red-700',
                    'hr': 'bg-orange-100 text-orange-700',
                    'teacher': 'bg-green-100 text-green-700',
                    'registrar': 'bg-indigo-100 text-indigo-700',
                    'cashier': 'bg-yellow-100 text-yellow-700',
                    'guidance': 'bg-pink-100 text-pink-700',
                    'attendance': 'bg-teal-100 text-teal-700',
                    'student': 'bg-blue-100 text-blue-700',
                    'parent': 'bg-cyan-100 text-cyan-700'
                };
                
                const userTypeColor = userTypeColors[record.user_type] || 'bg-gray-100 text-gray-700';
                const roleColor = roleColors[record.role] || 'bg-gray-100 text-gray-700';
                
                const loginDate = new Date(record.login_time);
                const logoutTime = record.logout_time ? new Date(record.logout_time) : null;
                
                let duration = '---';
                if (record.session_duration) {
                    const hours = Math.floor(record.session_duration / 3600);
                    const minutes = Math.floor((record.session_duration % 3600) / 60);
                    duration = hours > 0 ? `${hours}h ${minutes}m` : `${minutes} min`;
                }
                
                row.innerHTML = `
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${userTypeColor}">
                            ${record.user_type.charAt(0).toUpperCase() + record.user_type.slice(1)}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-gray-600 text-xs">${record.id_number}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">${record.full_name || record.username}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${roleColor}">
                            ${record.role.charAt(0).toUpperCase() + record.role.slice(1)}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">${loginDate.toLocaleDateString()}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">${loginDate.toLocaleTimeString()}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        ${logoutTime ? logoutTime.toLocaleTimeString() : '<span class="text-green-600 font-medium">Active</span>'}
                    </td>
                    <td class="px-4 py-3 text-gray-600">${duration}</td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Update pagination
            const total = data.total || 0;
            const start = total > 0 ? ((historyPage - 1) * 10) + 1 : 0;
            const end = Math.min(historyPage * 10, total);
            
            document.getElementById('history-start').textContent = start;
            document.getElementById('history-end').textContent = end;
            document.getElementById('history-total').textContent = total;
            
            document.getElementById('history-prev').disabled = historyPage === 1;
            document.getElementById('history-next').disabled = end >= total;
            
            pagination.classList.remove('hidden');
        }

        function changeHistoryPage(direction) {
            historyPage += direction;
            if (historyPage < 1) historyPage = 1;
            searchLoginHistory();
        }

        function clearHistoryFilters() {
            document.getElementById('history-date-from').value = '';
            document.getElementById('history-date-to').value = '';
            document.getElementById('history-user-type').value = 'all';
            document.getElementById('history-role').value = 'all';
            document.getElementById('history-search').value = '';
            historyPage = 1;
            updateHistoryRoleOptions();
            searchLoginHistory();
        }

        function exportLoginHistory() {
            const dateFrom = document.getElementById('history-date-from').value;
            const dateTo = document.getElementById('history-date-to').value;
            const userType = document.getElementById('history-user-type').value;
            const role = document.getElementById('history-role').value;
            const search = document.getElementById('history-search').value;
            
            const params = new URLSearchParams({
                date_from: dateFrom,
                date_to: dateTo,
                user_type: userType,
                role: role,
                search: search,
                export: 'csv'
            });
            
            window.location.href = `get_login_history.php?${params}`;
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('login-history-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeLoginHistory();
                }
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('login-history-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginHistory();
            }
        });

    </script>
</body>
</html>
