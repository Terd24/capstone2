<!-- Parent Form -->
<div id="parentForm" class="account-form" style="display: none;">
    <form method="POST" action="AccountList.php" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[70vh] no-scrollbar">
        <input type="hidden" name="account_type" value="parent">

        <!-- Personal Information -->
        <div class="col-span-3">
            <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Personal Information</h3>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">First Name *</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Last Name *</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>" 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <!-- Child Information -->
        <div class="col-span-3">
            <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Child Information</h3>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Child's Student ID *</label>
            <input type="text" name="child_id" value="<?= htmlspecialchars($form_data['child_id'] ?? '') ?>" required 
                   onblur="fetchChildName(this.value)"
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            <p class="text-xs text-gray-500 mt-1">Enter the student ID of your child</p>
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-semibold mb-1">Child's Full Name</label>
            <input type="text" id="child_name_display" readonly 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-100 text-gray-600"
                   placeholder="Child's name will appear here automatically">
            <input type="hidden" name="child_name" id="child_name_hidden">
        </div>

        <!-- Personal Account -->
        <div class="col-span-3">
            <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Personal Account</h3>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Username *</label>
            <input type="text" name="username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" required 
                   pattern="[A-Za-z0-9_]+" title="Username can only contain letters, numbers, and underscores"
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Password *</label>
            <input type="text" name="password" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>
        
        <div>
            <label class="block text-sm font-semibold mb-1">ID Number *</label>
            <input type="text" name="id_number" value="<?= htmlspecialchars($form_data['id_number'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <!-- Submit Button -->
        <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
            <button type="button" onclick="closeModal()" 
                    class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">
                Cancel
            </button>
            <button type="submit" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition">
                Create Parent Account
            </button>
        </div>
    </form>
</div>

<script>
// Function to fetch child name based on student ID
function fetchChildName(childId) {
    if (!childId) {
        document.getElementById('child_name_display').value = '';
        document.getElementById('child_name_hidden').value = '';
        return;
    }
    
    // Make AJAX request to get child name
    fetch('get_child_name.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'child_id=' + encodeURIComponent(childId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('child_name_display').value = data.child_name;
            document.getElementById('child_name_hidden').value = data.child_name;
        } else {
            document.getElementById('child_name_display').value = 'Student not found';
            document.getElementById('child_name_hidden').value = '';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('child_name_display').value = 'Error fetching student';
        document.getElementById('child_name_hidden').value = '';
    });
}
</script>
