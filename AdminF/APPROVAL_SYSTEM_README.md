# Approval System for SuperAdmin Actions

## Overview
This system requires Owner approval for SuperAdmin actions on deleted records (restore/permanent delete).

## Setup Instructions

### 1. Create Database Table
Run this file to create the approval_requests table:
```
http://localhost/onecci/AdminF/setup_approval_system.php
```

### 2. Files Created
- `AdminF/setup_approval_system.php` - Database setup
- `AdminF/create_approval_request.php` - API to create approval requests
- `OwnerF/get_approval_requests.php` - API to fetch pending requests
- `OwnerF/process_approval.php` - API to approve/reject requests

### 3. SuperAdmin Dashboard Updates Needed

In `AdminF/SuperAdminDashboard.php`, there's a duplicate `restoreEmployee` function at line 3543 that needs to be removed or commented out. The new approval-based function is at line 3419.

**To fix:**
1. Find line 3543: `function restoreEmployee(employeeId) {`
2. Comment it out or rename it to `function oldRestoreEmployee_DISABLED(employeeId) {`

### 4. Owner Dashboard Integration

Add this section to `OwnerF/Dashboard.php`:

```html
<!-- Pending Approval Requests -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">📋 Pending Approval Requests</h3>
    <div id="approval-requests-container">
        <p class="text-gray-500">Loading...</p>
    </div>
</div>

<script>
// Load pending approval requests
async function loadApprovalRequests() {
    try {
        const response = await fetch('get_approval_requests.php?status=pending');
        const data = await response.json();
        
        const container = document.getElementById('approval-requests-container');
        
        if (!data.success || data.requests.length === 0) {
            container.innerHTML = '<p class="text-gray-500">No pending requests</p>';
            return;
        }
        
        let html = '<div class="space-y-4">';
        data.requests.forEach(request => {
            const requestTypeLabels = {
                'restore_student': 'Restore Student',
                'restore_employee': 'Restore Employee',
                'permanent_delete_student': 'Permanently Delete Student',
                'permanent_delete_employee': 'Permanently Delete Employee'
            };
            
            const typeColors = {
                'restore_student': 'bg-green-100 text-green-800',
                'restore_employee': 'bg-blue-100 text-blue-800',
                'permanent_delete_student': 'bg-red-100 text-red-800',
                'permanent_delete_employee': 'bg-orange-100 text-orange-800'
            };
            
            html += `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${typeColors[request.request_type]}">
                                    ${requestTypeLabels[request.request_type]}
                                </span>
                                <span class="text-sm text-gray-500">${new Date(request.requested_at).toLocaleString()}</span>
                            </div>
                            <p class="font-medium text-gray-900">${request.record_name || request.record_id}</p>
                            <p class="text-sm text-gray-600">ID: ${request.record_id}</p>
                            <p class="text-sm text-gray-500">Requested by: ${request.requested_by}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="processApproval(${request.id}, 'approve')" 
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                Approve
                            </button>
                            <button onclick="processApproval(${request.id}, 'reject')" 
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading approval requests:', error);
    }
}

async function processApproval(requestId, action) {
    const notes = prompt(`Enter notes for this ${action} (optional):`);
    
    if (action === 'reject' && !notes) {
        alert('Please provide a reason for rejection');
        return;
    }
    
    try {
        const response = await fetch('process_approval.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                request_id: requestId,
                action: action,
                notes: notes || ''
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            loadApprovalRequests(); // Reload the list
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('An error occurred while processing the request');
    }
}

// Load requests on page load
document.addEventListener('DOMContentLoaded', loadApprovalRequests);

// Refresh every 30 seconds
setInterval(loadApprovalRequests, 30000);
</script>
```

## How It Works

1. **SuperAdmin** clicks "Restore" or "Permanent Delete" on a deleted record
2. Instead of immediate action, a request is created in `approval_requests` table
3. **Owner** sees pending requests in their dashboard
4. **Owner** can approve or reject with optional notes
5. If approved, the action is executed automatically
6. SuperAdmin is notified of the decision

## Request Types
- `restore_student` - Restore a deleted student record
- `restore_employee` - Restore a deleted employee record
- `permanent_delete_student` - Permanently delete a student (already implemented)
- `permanent_delete_employee` - Permanently delete an employee (already implemented)

## Database Schema
```sql
approval_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_type ENUM(...),
    record_id VARCHAR(50),
    record_name VARCHAR(255),
    record_data JSON,
    requested_by VARCHAR(100),
    requested_at TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected'),
    reviewed_by VARCHAR(100),
    reviewed_at TIMESTAMP,
    review_notes TEXT
)
```

## Testing
1. Login as SuperAdmin
2. Go to Deleted Items
3. Click "Restore" on any deleted record
4. Check that approval request is created
5. Login as Owner
6. See the pending request
7. Approve or reject it
8. Verify the action is executed (if approved)
