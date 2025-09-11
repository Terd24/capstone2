<!-- Attendance Account Form -->
<div id="attendanceForm" class="form-section" style="display: none;">
    <form method="POST" class="px-6 py-6 space-y-6">
        <input type="hidden" name="account_type" value="attendance">
        
        <!-- Form Title -->
        <div class="mb-6">
            <h3 class="text-xl font-bold text-[#0B2C62]">Create Attendance Account</h3>
        </div>

        <!-- Username and Password Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                <input type="text" name="username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" 
                       class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]" 
                       placeholder="Enter username" required>  
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input type="password" name="password" 
                       class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]" 
                       placeholder="Enter password" required>
            </div>
        </div>


        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="submit" class="px-6 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">
                Create Attendance Account
            </button>
        </div>
    </form>
</div>
