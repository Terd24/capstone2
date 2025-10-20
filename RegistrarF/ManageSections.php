<?php
session_start();
include("../StudentLogin/db_conn.php");

if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Create sections table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_section_name (section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'create') {
        $section_name = trim($_POST['section_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($section_name)) {
            echo json_encode(['success' => false, 'message' => 'Section name is required']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO sections (section_name, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $section_name, $description, $_SESSION['registrar_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section created successfully']);
        } else {
            if ($conn->errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'Section name already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating section']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $section_name = trim($_POST['section_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($section_name)) {
            echo json_encode(['success' => false, 'message' => 'Section name is required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE sections SET section_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('ssi', $section_name, $description, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
        } else {
            if ($conn->errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'Section name already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating section']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Check if section is used in class_schedules
        $check = $conn->prepare("SELECT COUNT(*) as count FROM class_schedules WHERE section_name = (SELECT section_name FROM sections WHERE id = ?)");
        $check->bind_param('i', $id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete section that is used in schedules']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting section']);
        }
        exit;
    }
}

// Fetch all sections
$sections_query = "SELECT * FROM sections ORDER BY section_name ASC";
$sections_result = $conn->query($sections_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - Registrar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="window.history.back()" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h1 class="text-xl font-bold">Manage Sections</h1>
            </div>
            <div class="flex items-center gap-4">
                <img src="../images/LogoCCI.png" alt="CCI" class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Registrar Portal</p>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container mx-auto px-6 py-8 max-w-6xl">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Section Management</h2>
                <p class="text-sm text-gray-600 mt-1">Create and manage section names for class schedules</p>
            </div>
            <button onclick="showAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Section
            </button>
        </div>

        <div class="mb-4">
            <input type="text" id="searchInput" placeholder="Search sections..." 
                   class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   onkeyup="filterSections()">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-[#0B2C62] text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Section Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Created</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody id="sectionsTable" class="divide-y divide-gray-200">
                    <?php if ($sections_result && $sections_result->num_rows > 0): ?>
                        <?php while ($section = $sections_result->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50 section-row">
                                <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($section['section_name']) ?></td>
                                <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($section['description'] ?: '-') ?></td>
                                <td class="px-6 py-4 text-gray-600 text-sm"><?= date('M j, Y', strtotime($section['created_at'])) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick='editSection(<?= json_encode($section) ?>)' class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-3">Edit</button>
                                    <button onclick="deleteSection(<?= $section['id'] ?>)" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">No sections found. Click "Add Section" to create one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Section Modal -->
<div id="sectionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="bg-gradient-to-r from-[#0B2C62] to-[#1E3A8A] text-white px-6 py-4 rounded-t-xl">
            <h3 id="modalTitle" class="text-lg font-bold">Add Section</h3>
        </div>
        <form id="sectionForm" class="p-6 space-y-4">
            <input type="hidden" id="sectionId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Section Name <span class="text-red-500">*</span></label>
                <input type="text" id="sectionName" required placeholder="e.g., BSIT 603, ABM - 12A"
                       class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description <span class="text-gray-400 text-xs">(Optional)</span></label>
                <input type="text" id="sectionDescription" placeholder="e.g., Bachelor of Science in IT - 3rd Year"
                       class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium">Cancel</button>
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Delete Section</h3>
            <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete this section? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="hideDeleteModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium">Cancel</button>
                <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg font-medium">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSectionId = null;

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Section';
    document.getElementById('sectionForm').reset();
    document.getElementById('sectionId').value = '';
    document.getElementById('sectionModal').classList.remove('hidden');
}

function editSection(section) {
    document.getElementById('modalTitle').textContent = 'Edit Section';
    document.getElementById('sectionId').value = section.id;
    document.getElementById('sectionName').value = section.section_name;
    document.getElementById('sectionDescription').value = section.description || '';
    document.getElementById('sectionModal').classList.remove('hidden');
}

function hideModal() {
    document.getElementById('sectionModal').classList.add('hidden');
}

function deleteSection(id) {
    currentSectionId = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    currentSectionId = null;
}

function confirmDelete() {
    if (!currentSectionId) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentSectionId);
    
    fetch('ManageSections.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Error deleting section'))
        .finally(() => hideDeleteModal());
}

document.getElementById('sectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const id = document.getElementById('sectionId').value;
    formData.append('action', id ? 'update' : 'create');
    if (id) formData.append('id', id);
    formData.append('section_name', document.getElementById('sectionName').value);
    formData.append('description', document.getElementById('sectionDescription').value);
    
    fetch('ManageSections.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Error saving section'));
});

function filterSections() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.section-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}
</script>

</body>
</html>
