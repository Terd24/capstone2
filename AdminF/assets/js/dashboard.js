// Super Admin Dashboard JavaScript Functions

// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
}

// Section navigation functionality
function showSection(sectionName, event) {
    if (event) {
        event.preventDefault();
    }
    
    console.log('Switching to section:', sectionName);
    
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
        section.classList.add('hidden');
        section.style.display = 'none';
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.classList.remove('hidden');
        targetSection.classList.add('active');
        targetSection.style.display = 'block';
        console.log('Section activated:', sectionName);
    } else {
        console.error('Section not found:', sectionName + '-section');
    }
    
    // Update navigation items
    updateNavigation(event, sectionName);
    
    // Update page title
    updatePageTitle(sectionName);
    
    // Close sidebar on mobile after selection
    if (window.innerWidth < 1024) {
        toggleSidebar();
    }
}

// Update navigation active states
function updateNavigation(event, sectionName) {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    if (event && event.target) {
        const navItem = event.target.closest('.nav-item');
        if (navItem) {
            navItem.classList.add('active');
        }
    } else {
        // Find nav item by section name
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href === '#' + sectionName) {
                item.classList.add('active');
            }
        });
    }
}

// Update page title based on section
function updatePageTitle(sectionName) {
    const titles = {
        'dashboard': 'Dashboard',
        'hr-accounts': 'HR Accounts Management',
        'system-maintenance': 'System Maintenance',
        'deleted-items': 'Deleted Items Management'
    };
    
    const titleElement = document.getElementById('page-title');
    if (titleElement) {
        titleElement.textContent = titles[sectionName] || 'Dashboard';
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard initialized');
    
    // Check URL hash on page load
    const hash = window.location.hash.substring(1);
    console.log('URL hash:', hash);
    
    if (hash && ['dashboard', 'hr-accounts', 'system-maintenance', 'deleted-items'].includes(hash)) {
        showSection(hash);
    } else {
        // Default to dashboard
        showSection('dashboard');
    }
});

// Listen for hash changes
window.addEventListener('hashchange', function() {
    const hash = window.location.hash.substring(1);
    console.log('Hash changed to:', hash);
    
    if (hash && ['dashboard', 'hr-accounts', 'system-maintenance', 'deleted-items'].includes(hash)) {
        showSection(hash);
    }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = event.target.closest('[onclick*="toggleSidebar"]');
    
    if (sidebar && !sidebar.contains(event.target) && !sidebarToggle && window.innerWidth < 1024) {
        if (!sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
});

// Back button prevention for security
window.addEventListener("pageshow", function(event) {
    if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
        window.location.reload();
    }
});

// Refresh page functionality
function refreshPage() {
    window.location.reload();
}

// System maintenance functions
function enableMaintenanceMode() {
    if (confirm('Are you sure you want to enable maintenance mode? This will make the system unavailable to users.')) {
        console.log('Maintenance mode enabled');
        alert('Maintenance mode would be enabled here');
    }
}

function createSystemBackup() {
    if (confirm('Create a system backup? This may take a few minutes.')) {
        console.log('Creating system backup');
        alert('System backup would be created here');
    }
}

function clearSystemCache() {
    if (confirm('Clear system cache? This will improve performance but may temporarily slow down the system.')) {
        console.log('Clearing system cache');
        alert('System cache would be cleared here');
    }
}

function viewSystemLogs() {
    console.log('Opening system logs');
    alert('System logs would be displayed here');
}

// HR Account functions
function createHRAccount() {
    console.log('Creating new HR account');
    alert('HR account creation form would open here');
}

function managePermissions() {
    console.log('Managing permissions');
    alert('Permissions management would open here');
}

// Restore deleted student
function restoreStudent(studentId) {
    if (confirm('Are you sure you want to restore this student record?')) {
        fetch('restore_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'restore',
                record_type: 'student',
                record_id: studentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student record restored successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while restoring the record.');
        });
    }
}

// Request permanent deletion
function requestPermanentDelete(recordId, recordType) {
    const reason = prompt('Please provide a reason for permanent deletion:');
    if (reason && reason.trim()) {
        if (confirm('This will send a request to the School Owner for approval. Continue?')) {
            fetch('request_permanent_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
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

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}
