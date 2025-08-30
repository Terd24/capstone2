<!-- Registrar Form -->
<div id="registrarForm" class="account-form hidden">
    <form method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto max-h-[70vh] no-scrollbar">
        <input type="hidden" name="account_type" value="registrar">

        <!-- Personal Information -->
        <div class="col-span-2">
            <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">Personal Information</h3>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Last Name *</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">First Name *</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>" 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Date of Birth *</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($form_data['dob'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Birthplace *</label>
            <input type="text" name="birthplace" value="<?= htmlspecialchars($form_data['birthplace'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Gender *</label>
            <div class="flex items-center gap-6 mt-1">
                <label class="flex items-center gap-2">
                    <input type="radio" name="gender" value="M" <?= ($form_data['gender'] ?? '') === 'M' ? 'checked' : '' ?> required> Male
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="gender" value="F" <?= ($form_data['gender'] ?? '') === 'F' ? 'checked' : '' ?> required> Female
                </label>
            </div>
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-semibold mb-1">Complete Address *</label>
            <textarea name="address" required rows="3" 
                      class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]"><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
        </div>

        <!-- Personal Account Section -->
        <div class="col-span-2 mt-4">
            <h3 class="text-lg font-semibold mb-4 text-[#1E4D92]">PERSONAL ACCOUNT</h3>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">ID Number *</label>
            <input type="number" name="id_number" value="<?= htmlspecialchars($form_data['id_number'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Password *</label>
            <input type="password" name="password" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">RFID Number *</label>
            <input type="number" name="rfid_uid" value="<?= htmlspecialchars($form_data['rfid_uid'] ?? '') ?>" required 
                   class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>

        <!-- Submit Buttons -->
        <div class="col-span-2 flex justify-end gap-4 pt-6 border-t border-gray-200">
            <button type="button" onclick="closeModal()" 
                    class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">
                Cancel
            </button>
            <button type="submit" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition">
                Create Registrar Account
            </button>
        </div>
    </form>
</div>
