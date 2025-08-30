<!-- Success notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="bg-green-400 text-white px-3 py-2 rounded shadow mt-4 w-fit ml-auto mr-auto text-center">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Add Student Modal -->
<div id="addStudentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-95" id="modalContent">
        
        <!-- Header -->
<div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
    <h2 class="text-lg font-semibold">Add New Student</h2>
    <button onclick="closeModal()" class="text-2xl font-bold hover:text-gray-300">&times;</button>
</div>
        <!-- Form -->
        <form action="AccountList.php" method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">

            <!-- Row: LRN and Student ID -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">LRN</label>
                    <input type="number" name="lrn" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
     <div>
  <label class="block text-sm font-semibold mb-1">Academic Track / Course</label>
  <select name="academic_track" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
      <option value="">-- Select Academic Track / Course --</option>
      
        <optgroup label="Elementary">
          <option value="Elementary">Elementary</option>
      </optgroup>
      <!-- Junior High -->
      <optgroup label="Junior High School">
          <option value="Junior High School">Junior High School</option>
      </optgroup>

      <!-- Senior High -->
      <optgroup label="Senior High School Strands">
          <option value="STEM">STEM (Science, Technology, Engineering & Mathematics)</option>
          <option value="ABM">ABM (Accountancy, Business & Management)</option>
          <option value="HUMSS">HUMSS (Humanities & Social Sciences)</option>
          <option value="GAS">GAS (General Academic Strand)</option>
          <option value="TVL">TVL (Technical-Vocational-Livelihood)</option>
          <option value="Arts and Design">Arts and Design</option>
      </optgroup>

      <!-- College -->
      <optgroup label="College Courses">
          <option value="BS Information Technology">BS Information Technology</option>
          <option value="BS Computer Science">BS Computer Science</option>
          <option value="BS Business Administration">BS Business Administration</option>
          <option value="BS Accountancy">BS Accountancy</option>
          <option value="BS Hospitality Management">BS Hospitality Management</option>
          <option value="BS Education">BS Education</option>
      </optgroup>
  </select>
</div>

<div>
  <label class="block text-sm font-semibold mb-1">Enrollment Status</label>
  <div class="flex items-center gap-6 mt-1">
    <label class="flex items-center gap-2">
      <input type="radio" name="enrollment_status" value="OLD" onchange="toggleNewOptions()"> OLD
    </label>
    <label class="flex items-center gap-2">
      <input type="radio" name="enrollment_status" value="NEW" onchange="toggleNewOptions()"> NEW
    </label>
  </div>

  <!-- Hidden extra options (only for NEW) -->
  <div id="newOptions" class="flex items-center gap-6 mt-3 hidden ml-4">
    <label class="flex items-center gap-2">
      <input type="radio" name="school_type" value="PUBLIC"> Public
    </label>
    <label class="flex items-center gap-2">
      <input type="radio" name="school_type" value="PRIVATE"> Private
    </label>
  </div>
</div>


            <!-- Row: Full Name -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">Last Name</label>
                    <input type="text" name="last_name" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">First Name</label>
                    <input type="text" name="first_name" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Middle Name</label>
                    <input type="text" name="middle_name" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
            </div>

            <!-- Other Student Info -->           
             <div>
                <label class="block text-sm font-semibold mb-1">School Year</label>
                <input type="text" name="school_year" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Grade Level</label>
                <select id="gradeLevel" name="grade_level" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <option value="">-- Select Grade Level --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Semester</label>
                <select name="semester" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <option value="">Select Term</option>
                    <option value="1st">1st Term</option>
                    <option value="2nd">2nd Term</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                <input type="date" name="dob" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Birthplace</label>
                <input type="text" name="birthplace" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>
                      <div>
                <label class="block text-sm font-semibold mb-1">Gender</label>
                <div class="flex items-center gap-6 mt-1">
                    <label class="flex items-center gap-2"><input type="radio" name="gender" value="M" required> Male</label>
                    <label class="flex items-center gap-2"><input type="radio" name="gender" value="F" required> Female</label>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Religion</label>
                <input type="text" name="religion" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
            </div>

                            <!-- Credentials -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Credentials Submitted</label>
                    <div class="grid grid-cols-2 gap-y-2 text-sm ml-2">
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="F-138"> <span>F-138</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="Good Moral"> <span>Good Moral</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="PSA Birth"> <span>PSA Birth</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="ESC Certification"> <span>ESC Certification</span></label>
                    </div>
                </div>

                    <!-- Mode of Payment -->
    <div>
        <label class="block text-sm font-semibold mb-1">Mode of Payment</label>
        <div class="flex items-center gap-6 mt-1">
            <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Cash" required> Cash</label>
            <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Installment" required> Installment</label>
        </div>
    </div>
</div>

            <!-- Credentials and Address -->
            <div class="col-span-3 grid grid-cols-2 gap-6">
                <!-- Complete Address -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Complete Address</label>
                    <textarea name="address" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]"></textarea>
                </div>
            </div>

            <!-- Parents / Guardian Info -->
            <div class="col-span-3 space-y-6">
                <h3 class="font-semibold mt-4">Father's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="father_name" placeholder="Name" required class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="father_occupation" placeholder="Occupation" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="father_contact" placeholder="Contact No." class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>

                <h3 class="font-semibold mt-4">Mother's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="mother_name" placeholder="Name" required class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="mother_occupation" placeholder="Occupation" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="mother_contact" placeholder="Contact No." class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>

                <h3 class="font-semibold mt-4">Guardian's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="guardian_name" placeholder="Name" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="guardian_occupation" placeholder="Occupation" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                    <input type="text" name="guardian_contact" placeholder="Contact No." class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
            </div>

            <!-- Last School Attended -->
            <div class="col-span-3 grid grid-cols-2 gap-6 mt-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Last School Attended</label>
                    <input type="text" name="last_school" placeholder="School Name" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">School Year</label>
                    <input type="text" name="school_year" placeholder="School Year" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
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
        <input type="text" name="id_number" required
               value="<?= htmlspecialchars($old_id ?? '') ?>"
               class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= $error_id ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
        <?php if ($error_id): ?>
            <p class="text-red-500 text-sm mt-1"><?= $error_id ?></p>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div>
        <label class="block text-sm font-semibold mb-1">Password</label>
        <input type="password" name="password" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
    </div>

    <!-- RFID Number -->
    <div>
        <label class="block text-sm font-semibold mb-1">RFID Number</label>
        <input type="text" name="rfid_uid" required
               value="<?= htmlspecialchars($old_rfid ?? '') ?>"
               class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= $error_rfid ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
        <?php if ($error_rfid): ?>
            <p class="text-red-500 text-sm mt-1"><?= $error_rfid ?></p>
        <?php endif; ?>
    </div>
</div>


            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition">Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Hide Scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
function changeType(type){ window.location.href = `AccountList.php?type=${type}`; }

const searchInput = document.getElementById('searchInput');
const showEntriesInput = document.getElementById('showEntries');
let tableRows = Array.from(document.querySelectorAll('#accountTable tr'));

function updateEntries() {
    const value = parseInt(showEntriesInput.value) || tableRows.length;
    let shown = 0;
    tableRows.forEach(row => row.style.display = '');
    const query = searchInput.value.toLowerCase().trim();
    tableRows.forEach(row => { if (!row.textContent.toLowerCase().includes(query)) row.style.display='none'; });
    shown = 0;
    tableRows.forEach(row => {
        if(row.style.display !== 'none'){ if(shown<value) row.style.display=''; else row.style.display='none'; shown++; }
    });
}
showEntriesInput.addEventListener('input', updateEntries);
searchInput.addEventListener('input', updateEntries);
updateEntries();

function openModal(){ 
    document.getElementById('addStudentModal').classList.remove('hidden'); 
    document.getElementById('modalContent').classList.remove('scale-95');
    document.getElementById('modalContent').classList.add('scale-100');
}
function closeModal(){ 
    document.getElementById('addStudentModal').classList.add('hidden'); 
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

// Detect selection & update grade level
academicTrack.addEventListener('change', function() {
    const selected = academicTrack.options[academicTrack.selectedIndex].parentNode.label;
    const course = academicTrack.value;

    gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';

    if (gradeOptions[selected]) {
        gradeOptions[selected].forEach(level => {
            let option = document.createElement("option");
            option.value = level;
            option.textContent = level;
            gradeLevel.appendChild(option);
        });
    } 
    // In case Elementary/Junior High/College is chosen directly without optgroup
    else if (gradeOptions[course]) {
        gradeOptions[course].forEach(level => {
            let option = document.createElement("option");
            option.value = level;
            option.textContent = level;
            gradeLevel.appendChild(option);
        });
    }
});
</script>
<script>
const notif = document.getElementById("notif");
if (notif) {
    setTimeout(() => notif.classList.add("hidden"), 3000); // hide after 3 seconds
}
</script>

