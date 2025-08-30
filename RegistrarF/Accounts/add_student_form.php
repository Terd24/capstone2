<!-- Success notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="bg-green-400 text-white px-3 py-2 rounded shadow mt-4 w-fit ml-auto mr-auto text-center">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Add Account Modal -->
<div id="addAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 <?= $show_modal ? '' : 'hidden' ?>">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-95" id="modalContent">
        
        <!-- Header -->
<div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
    <h2 class="text-lg font-semibold">Add New Account</h2>
    <button onclick="closeModal()" class="text-2xl font-bold hover:text-gray-300">&times;</button>
</div>

        <!-- Account Type Selection -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <label class="block text-sm font-semibold mb-2">Select Account Type *</label>
            <select id="accountType" onchange="showAccountForm()" class="w-full max-w-md border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]" required>
                <option value="">-- Choose Account Type to Create --</option>
                <option value="student" <?= ($form_data['account_type'] ?? '') === 'student' ? 'selected' : '' ?>>Student Account</option>
                <option value="registrar" <?= ($form_data['account_type'] ?? '') === 'registrar' ? 'selected' : '' ?>>Registrar Account</option>
            </select>
            <p class="text-sm text-gray-600 mt-1">Please select the type of account you want to create</p>
        </div>

        <!-- No Selection Message -->
        <div id="noSelectionMessage" class="px-6 py-12 text-center" style="display: block;">
            <div class="max-w-md mx-auto">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Select Account Type</h3>
                <p class="text-gray-500">Choose the type of account you want to create from the dropdown above to get started.</p>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($error_msg)): ?>
            <div class="mx-6 mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <!-- Student Form -->
        <div id="studentForm" class="account-form hidden">
            <form method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
                <input type="hidden" name="account_type" value="student">

            <!-- Row: LRN and Student ID -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">LRN</label>
                    <input type="number" name="lrn" required value="<?= htmlspecialchars($form_data['lrn'] ?? '') ?>" pattern="[0-9]+" title="Please enter numbers only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
     <div>
  <label class="block text-sm font-semibold mb-1">Academic Track / Course</label>
  <select name="academic_track" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
      <option value="">-- Select Academic Track / Course --</option>
      
        <optgroup label="Elementary">
          <option value="Elementary" <?= ($form_data['academic_track'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
      </optgroup>
      <!-- Junior High -->
      <optgroup label="Junior High School">
          <option value="Junior High School" <?= ($form_data['academic_track'] ?? '') === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
      </optgroup>

      <!-- Senior High -->
      <optgroup label="Senior High School Strands">
          <option value="STEM" <?= ($form_data['academic_track'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering & Mathematics)</option>
          <option value="ABM" <?= ($form_data['academic_track'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business & Management)</option>
          <option value="HUMSS" <?= ($form_data['academic_track'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities & Social Sciences)</option>
          <option value="GAS" <?= ($form_data['academic_track'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
          <option value="TVL" <?= ($form_data['academic_track'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL (Technical-Vocational-Livelihood)</option>
          <option value="Arts and Design" <?= ($form_data['academic_track'] ?? '') === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
      </optgroup>

      <!-- College -->
      <optgroup label="College Courses">
          <option value="BS Information Technology" <?= ($form_data['academic_track'] ?? '') === 'BS Information Technology' ? 'selected' : '' ?>>BS Information Technology</option>
          <option value="BS Computer Science" <?= ($form_data['academic_track'] ?? '') === 'BS Computer Science' ? 'selected' : '' ?>>BS Computer Science</option>
          <option value="BS Business Administration" <?= ($form_data['academic_track'] ?? '') === 'BS Business Administration' ? 'selected' : '' ?>>BS Business Administration</option>
          <option value="BS Accountancy" <?= ($form_data['academic_track'] ?? '') === 'BS Accountancy' ? 'selected' : '' ?>>BS Accountancy</option>
          <option value="BS Hospitality Management" <?= ($form_data['academic_track'] ?? '') === 'BS Hospitality Management' ? 'selected' : '' ?>>BS Hospitality Management</option>
          <option value="BS Education" <?= ($form_data['academic_track'] ?? '') === 'BS Education' ? 'selected' : '' ?>>BS Education</option>
      </optgroup>
  </select>
</div>

<div>
  <label class="block text-sm font-semibold mb-1">Enrollment Status</label>
  <div class="flex items-center gap-6 mt-1">
    <label class="flex items-center gap-2">
      <input type="radio" name="enrollment_status" value="OLD" <?= ($form_data['enrollment_status'] ?? '') === 'OLD' ? 'checked' : '' ?> onchange="toggleNewOptions()"> OLD
    </label>
    <label class="flex items-center gap-2">
      <input type="radio" name="enrollment_status" value="NEW" <?= ($form_data['enrollment_status'] ?? '') === 'NEW' ? 'checked' : '' ?> onchange="toggleNewOptions()"> NEW
    </label>
  </div>

  <!-- Hidden extra options (only for NEW) -->
  <div id="newOptions" class="flex items-center gap-6 mt-3 <?= ($form_data['enrollment_status'] ?? '') === 'NEW' ? '' : 'hidden' ?> ml-4">
    <label class="flex items-center gap-2">
      <input type="radio" name="school_type" value="PUBLIC" <?= ($form_data['school_type'] ?? '') === 'PUBLIC' ? 'checked' : '' ?>> Public
    </label>
    <label class="flex items-center gap-2">
      <input type="radio" name="school_type" value="PRIVATE" <?= ($form_data['school_type'] ?? '') === 'PRIVATE' ? 'checked' : '' ?>> Private
    </label>
  </div>
</div>


            <!-- Row: Full Name -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">Last Name</label>
                    <input type="text" name="last_name" required value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">First Name</label>
                    <input type="text" name="first_name" required value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
            </div>

            <!-- Other Student Info -->           
             <div>
                <label class="block text-sm font-semibold mb-1">School Year</label>
                <input type="text" name="school_year" required value="<?= htmlspecialchars($form_data['school_year'] ?? '') ?>" pattern="[0-9\-]+" title="Please enter numbers and dash only (e.g. 2024-2025)" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Grade Level</label>
                <select id="gradeLevel" name="grade_level" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <option value="">-- Select Grade Level --</option>
                    <?php if (!empty($form_data['grade_level'])): ?>
                        <option value="<?= htmlspecialchars($form_data['grade_level']) ?>" selected><?= htmlspecialchars($form_data['grade_level']) ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Semester</label>
                <select name="semester" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <option value="">Select Term</option>
                    <option value="1st" <?= ($form_data['semester'] ?? '') === '1st' ? 'selected' : '' ?>>1st Term</option>
                    <option value="2nd" <?= ($form_data['semester'] ?? '') === '2nd' ? 'selected' : '' ?>>2nd Term</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                <input type="date" name="dob" required value="<?= htmlspecialchars($form_data['dob'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Birthplace</label>
                <input type="text" name="birthplace" required value="<?= htmlspecialchars($form_data['birthplace'] ?? '') ?>" pattern="[A-Za-z\s,.-]+" title="Please enter a valid location" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
                      <div>
                <label class="block text-sm font-semibold mb-1">Gender</label>
                <div class="flex items-center gap-6 mt-1">
                    <label class="flex items-center gap-2"><input type="radio" name="gender" value="M" <?= ($form_data['gender'] ?? '') === 'M' ? 'checked' : '' ?> required> Male</label>
                    <label class="flex items-center gap-2"><input type="radio" name="gender" value="F" <?= ($form_data['gender'] ?? '') === 'F' ? 'checked' : '' ?> required> Female</label>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Religion</label>
                <input type="text" name="religion" required value="<?= htmlspecialchars($form_data['religion'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>

                            <!-- Credentials -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Credentials Submitted</label>
                    <div class="grid grid-cols-2 gap-y-2 text-sm ml-2">
                        <?php $saved_credentials = $form_data['credentials'] ?? []; ?>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="F-138" <?= in_array('F-138', $saved_credentials) ? 'checked' : '' ?>> <span>F-138</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="Good Moral" <?= in_array('Good Moral', $saved_credentials) ? 'checked' : '' ?>> <span>Good Moral</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="PSA Birth" <?= in_array('PSA Birth', $saved_credentials) ? 'checked' : '' ?>> <span>PSA Birth</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="ESC Certification" <?= in_array('ESC Certification', $saved_credentials) ? 'checked' : '' ?>> <span>ESC Certification</span></label>
                    </div>
                </div>

                    <!-- Mode of Payment -->
    <div>
        <label class="block text-sm font-semibold mb-1">Mode of Payment</label>
        <div class="flex items-center gap-6 mt-1">
            <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Cash" <?= ($form_data['payment_mode'] ?? '') === 'Cash' ? 'checked' : '' ?> required> Cash</label>
            <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Installment" <?= ($form_data['payment_mode'] ?? '') === 'Installment' ? 'checked' : '' ?> required> Installment</label>
        </div>
    </div>
</div>

            <!-- Credentials and Address -->
            <div class="col-span-3 grid grid-cols-2 gap-6">
                <!-- Complete Address -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Complete Address</label>
                    <textarea name="address" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]"><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Parents / Guardian Info -->
            <div class="col-span-3 space-y-6">
                <h3 class="font-semibold mt-4">Father's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="father_name" placeholder="Name" required value="<?= htmlspecialchars($form_data['father_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="father_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['father_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="tel" name="father_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['father_contact'] ?? '') ?>" pattern="[0-9+\-\s()]+" title="Please enter numbers only" oninput="this.value = this.value.replace(/[^0-9+\-\s()]/g, '')" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>

                <h3 class="font-semibold mt-4">Mother's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="mother_name" placeholder="Name" required value="<?= htmlspecialchars($form_data['mother_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="mother_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['mother_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="tel" name="mother_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['mother_contact'] ?? '') ?>" pattern="[0-9+\-\s()]+" title="Please enter numbers only" oninput="this.value = this.value.replace(/[^0-9+\-\s()]/g, '')" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>

                <h3 class="font-semibold mt-4">Guardian's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="guardian_name" placeholder="Name" value="<?= htmlspecialchars($form_data['guardian_name'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="guardian_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['guardian_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="tel" name="guardian_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['guardian_contact'] ?? '') ?>" pattern="[0-9+\-\s()]*" title="Please enter numbers only" oninput="this.value = this.value.replace(/[^0-9+\-\s()]/g, '')" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
            </div>

            <!-- Last School Attended -->
            <div class="col-span-3 grid grid-cols-2 gap-6 mt-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Last School Attended</label>
                    <input type="text" name="last_school" placeholder="School Name" value="<?= htmlspecialchars($form_data['last_school'] ?? '') ?>" pattern="[A-Za-z\s.-]*" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">School Year</label>
                    <input type="text" name="last_school_year" placeholder="School Year (e.g. 2023-2024)" value="<?= htmlspecialchars($form_data['last_school_year'] ?? '') ?>" pattern="[0-9\-]*" title="Please enter numbers and dash only (e.g. 2023-2024)" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
            </div>
            
<!-- Personal Account Section -->
<div class="w-full flex justify-center mt-6">
  <div class="w-full max-w-3xl flex justify-center items-center border-b border-gray-200 px-6 py-2 bg-[#1E4D92] text-white">
    <h2 class="text-lg font-semibold">PERSONAL ACCOUNT</h2>
  </div>
</div>

<div class="col-span-3 grid grid-cols-3 gap-6 mt-6">
    <!-- Student ID -->
    <div>
        <label class="block text-sm font-semibold mb-1">Student ID</label>
        <input type="number" name="id_number" required
               value="<?= htmlspecialchars($old_id ?? '') ?>"
               pattern="[0-9]+" title="Please enter numbers only" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
               class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= !empty($error_id) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
        <?php if (!empty($error_id)): ?>
            <p class="text-red-500 text-sm mt-1 font-medium"><?= htmlspecialchars($error_id) ?></p>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div>
        <label class="block text-sm font-semibold mb-1">Password</label>
        <div class="relative">
            <input type="text" name="password" required value="<?= htmlspecialchars($form_data['password'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
        </div>
    </div>

    <!-- RFID Number -->
    <div>
        <label class="block text-sm font-semibold mb-1">RFID Number</label>
        <input type="number" name="rfid_uid" required
               value="<?= htmlspecialchars($old_rfid ?? '') ?>"
               pattern="[0-9]+" title="Please enter numbers only" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
               class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= !empty($error_rfid) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
        <?php if (!empty($error_rfid)): ?>
            <p class="text-red-500 text-sm mt-1 font-medium"><?= htmlspecialchars($error_rfid) ?></p>
        <?php endif; ?>
    </div>
</div>


            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">Cancel</button>
                <button type="submit" id="submitBtn" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition">
                    <span id="submitText">Create Student Account</span>
                    <span id="submitLoader" class="hidden">Processing...</span>
                </button>
            </div>
        </form>
        </div>

        <!-- Include Registrar Form -->
        <?php include("add_registrar_form.php"); ?>

    </div>
</div>

<!-- Hide Scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
// Remove duplicate JavaScript code that's already in AccountList.php

// Show/hide account forms based on selection
function showAccountForm() {
    const accountType = document.getElementById('accountType').value;
    const studentForm = document.getElementById('studentForm');
    const registrarForm = document.getElementById('registrarForm');
    const noSelectionMessage = document.getElementById('noSelectionMessage');
    
    // Hide all forms and message first
    studentForm.style.display = 'none';
    registrarForm.style.display = 'none';
    noSelectionMessage.style.display = 'none';
    
    // Show selected form or default message
    if (accountType === 'student') {
        studentForm.style.display = 'block';
    } else if (accountType === 'registrar') {
        registrarForm.style.display = 'block';
    } else {
        // No selection - show the message
        noSelectionMessage.style.display = 'block';
    }
}

function openModal(){ 
    document.getElementById('addAccountModal').classList.remove('hidden'); 
    document.getElementById('modalContent').classList.remove('scale-95');
    document.getElementById('modalContent').classList.add('scale-100');
    
    // Reset to show selection message when modal opens
    document.getElementById('accountType').value = '';
    showAccountForm();
}
function closeModal(){ 
    document.getElementById('addAccountModal').classList.add('hidden'); 
}
function toggleNewOptions() {
  const newOptions = document.getElementById("newOptions");
  const isNew = document.querySelector('input[name="enrollment_status"]:checked')?.value === "NEW";
  newOptions.classList.toggle("hidden", !isNew);
}
const academicTrack = document.querySelector('select[name="academic_track"]');
const gradeLevel = document.getElementById('gradeLevel');

// Options mapping
const gradeOptions = {
    "Elementary": ["Grade 1", "Grade 2", "Grade 3", "Grade 4", "Grade 5", "Grade 6"],
    "Junior High School": ["Grade 7", "Grade 8", "Grade 9", "Grade 10"],
    "Senior High School Strands": ["Grade 11", "Grade 12"],
    "College Courses": ["1st Year", "2nd Year", "3rd Year", "4th Year"]
};

// Function to populate grade levels
function populateGradeLevels(selectedTrack, selectedGrade = '') {
    const selected = selectedTrack ? academicTrack.options[academicTrack.selectedIndex].parentNode.label : '';
    const course = selectedTrack;

    gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';

    let levels = [];
    if (gradeOptions[selected]) {
        levels = gradeOptions[selected];
    } else if (gradeOptions[course]) {
        levels = gradeOptions[course];
    }

    levels.forEach(level => {
        let option = document.createElement("option");
        option.value = level;
        option.textContent = level;
        if (level === selectedGrade) option.selected = true;
        gradeLevel.appendChild(option);
    });
}

// Detect selection & update grade level
academicTrack.addEventListener('change', function() {
    populateGradeLevels(academicTrack.value);
});

// Initialize grade levels on page load if academic track is selected
window.addEventListener('load', function() {
    const savedTrack = '<?= $form_data["academic_track"] ?? "" ?>';
    const savedGrade = '<?= $form_data["grade_level"] ?? "" ?>';
    if (savedTrack) {
        populateGradeLevels(savedTrack, savedGrade);
    }
    
    // Initialize form display based on saved account type or show selection message
    const savedAccountType = '<?= $form_data["account_type"] ?? "" ?>';
    if (savedAccountType) {
        document.getElementById('accountType').value = savedAccountType;
        showAccountForm();
    } else {
        // Default to showing no selection message
        showAccountForm(); // This will show the "Select Account Type" message
    }
});

// Add client-side validation to prevent page flicker
document.addEventListener('DOMContentLoaded', function() {
    const studentFormElement = document.getElementById('studentForm');
    if (studentFormElement) {
        studentFormElement.addEventListener('submit', function(e) {
            const studentId = document.querySelector('input[name="id_number"]').value.trim();
            const rfidUid = document.querySelector('input[name="rfid_uid"]').value.trim();
            
            if (!studentId || !rfidUid) {
                return; // Let normal validation handle empty fields
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitLoader = document.getElementById('submitLoader');
            
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitLoader.classList.remove('hidden');
            
            // Check for duplicates via AJAX
            const formData = new FormData();
            formData.append('check_duplicates', '1');
            formData.append('id_number', studentId);
            formData.append('rfid_uid', rfidUid);
            
            fetch('Accounts/check_duplicates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error_id || data.error_rfid) {
                    e.preventDefault();
                    
                    // Show errors without page reload
                    if (data.error_id) {
                        const idField = document.querySelector('input[name="id_number"]');
                        idField.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                        idField.classList.remove('border-gray-300', 'focus:ring-[#2F8D46]');
                        
                        let errorMsg = idField.parentNode.querySelector('.text-red-500');
                        if (!errorMsg) {
                            errorMsg = document.createElement('p');
                            errorMsg.className = 'text-red-500 text-sm mt-1 font-medium';
                            idField.parentNode.appendChild(errorMsg);
                        }
                        errorMsg.textContent = data.error_id;
                    }
                    
                    if (data.error_rfid) {
                        const rfidField = document.querySelector('input[name="rfid_uid"]');
                        rfidField.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                        rfidField.classList.remove('border-gray-300', 'focus:ring-[#2F8D46]');
                        
                        let errorMsg = rfidField.parentNode.querySelector('.text-red-500');
                        if (!errorMsg) {
                            errorMsg = document.createElement('p');
                            errorMsg.className = 'text-red-500 text-sm mt-1 font-medium';
                            rfidField.parentNode.appendChild(errorMsg);
                        }
                        errorMsg.textContent = data.error_rfid;
                    }
                    
                    // Scroll to first error
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
                
                // Reset button state
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoader.classList.add('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state and allow normal form submission
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoader.classList.add('hidden');
            });
        });
    }
});

// Clear error styling only when user enters valid data
document.querySelector('input[name="id_number"]').addEventListener('input', function() {
    const value = this.value.trim();
    // Only clear error if field has valid numbers
    if (value && value.match(/^[0-9]+$/)) {
        this.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
        this.classList.add('border-gray-300', 'focus:ring-[#2F8D46]');
        const errorMsg = this.parentNode.querySelector('.text-red-500');
        if (errorMsg) errorMsg.remove();
    }
});

document.querySelector('input[name="rfid_uid"]').addEventListener('input', function() {
    const value = this.value.trim();
    // Only clear error if field has valid numbers
    if (value && value.match(/^[0-9]+$/)) {
        this.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
        this.classList.add('border-gray-300', 'focus:ring-[#2F8D46]');
        const errorMsg = this.parentNode.querySelector('.text-red-500');
        if (errorMsg) errorMsg.remove();
    }
});
</script>
<script>
const notif = document.getElementById("notif");
if (notif) {
    // Show notification with slide-in effect
    setTimeout(() => {
        notif.style.transform = 'translateX(0)';
        notif.style.opacity = '1';
    }, 100);
    
    // Hide after 4 seconds with fade out
    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transform = 'translateX(100px)';
        setTimeout(() => notif.classList.add("hidden"), 300);
    }, 4000);
}

// Handle validation errors
<?php if (!empty($error_id) || !empty($error_rfid) || !empty($error_msg) || $show_modal): ?>
// Show modal if there are errors
document.getElementById('addAccountModal').classList.remove('hidden');
document.getElementById('modalContent').classList.remove('scale-95');
document.getElementById('modalContent').classList.add('scale-100');

// Show the appropriate form based on account type
const accountType = '<?= $form_data['account_type'] ?? '' ?>';
if (accountType) {
    document.getElementById('accountType').value = accountType;
    showAccountForm();
}

// Scroll to first error field
setTimeout(() => {
    const firstError = document.querySelector('.border-red-500');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstError.focus();
    }
}, 300);
<?php endif; ?>

// Retrieve session errors for display
<?php 
$session_error_id = $_SESSION['error_id'] ?? "";
$session_error_rfid = $_SESSION['error_rfid'] ?? "";
if (!empty($session_error_id) || !empty($session_error_rfid)): 
?>
// Show modal and errors from session
document.getElementById('addStudentModal').classList.remove('hidden');
document.getElementById('modalContent').classList.remove('scale-95');
document.getElementById('modalContent').classList.add('scale-100');

<?php if (!empty($session_error_id)): ?>
// Show Student ID error
const idField = document.querySelector('input[name="id_number"]');
if (idField) {
    idField.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
    idField.classList.remove('border-gray-300', 'focus:ring-[#2F8D46]');
    
    let errorMsg = idField.parentNode.querySelector('.text-red-500');
    if (!errorMsg) {
        errorMsg = document.createElement('p');
        errorMsg.className = 'text-red-500 text-sm mt-1 font-medium';
        idField.parentNode.appendChild(errorMsg);
    }
    errorMsg.textContent = '<?= htmlspecialchars($session_error_id) ?>';
    
    // Auto-scroll and focus on error field
    setTimeout(() => {
        idField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        idField.focus();
    }, 500);
}
<?php endif; ?>

<?php if (!empty($session_error_rfid)): ?>
// Show RFID error
const rfidField = document.querySelector('input[name="rfid_uid"]');
if (rfidField) {
    rfidField.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
    rfidField.classList.remove('border-gray-300', 'focus:ring-[#2F8D46]');
    
    let errorMsg = rfidField.parentNode.querySelector('.text-red-500');
    if (!errorMsg) {
        errorMsg = document.createElement('p');
        errorMsg.className = 'text-red-500 text-sm mt-1 font-medium';
        rfidField.parentNode.appendChild(errorMsg);
    }
    errorMsg.textContent = '<?= htmlspecialchars($session_error_rfid) ?>';
    
    // Auto-scroll and focus on error field if no ID error
    <?php if (empty($session_error_id)): ?>
    setTimeout(() => {
        rfidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        rfidField.focus();
    }, 500);
    <?php endif; ?>
}
<?php endif; ?>

// Clear session errors after displaying
<?php 
unset($_SESSION['error_id'], $_SESSION['error_rfid']);
?>
<?php endif; ?>
</script>
