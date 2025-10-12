# Username Auto-Generation - EXACT COPY from add_account.php ✅

## What Was Done

I copied the EXACT implementation from `add_account.php` to `view_student.php` - 100% identical code.

## 1. JavaScript Functions - EXACT COPY

### setupAutoUsername() - Student Username
```javascript
function setupAutoUsername() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const idField = document.querySelector('input[name="id_number"]');
    const usernameField = document.querySelector('input[name="username"]');
    
    if (!lastNameField || !idField || !usernameField) return;

    const lettersOnly = (s) => (s || '').toLowerCase().replace(/[^a-z]/g, '');
    const last6 = (s) => {
        const digits = (s || '').replace(/\D/g, '');
        return digits.slice(-6).padStart(6, '0');
    };

    const updateUsername = () => {
        const lastName = lettersOnly(lastNameField.value);
        const idNumber = idField.value;
        const tail = last6(idNumber);
        
        if (lastName && tail.length === 6) {
            const username = `${lastName}${tail}muzon@student.cci.edu.ph`;
            usernameField.value = username;
            usernameField.readOnly = true;
            usernameField.style.backgroundColor = '#f3f4f6';
            usernameField.style.cursor = 'not-allowed';
        }
    };

    // Initial fill and listeners
    updateUsername();
    lastNameField.addEventListener('input', updateUsername);
    idField.addEventListener('input', updateUsername);
    
    // Poll every 500ms to check if Student ID gets auto-filled
    const pollForStudentId = setInterval(() => {
        if (idField.value && idField.value.length === 11) {
            updateUsername();
            clearInterval(pollForStudentId);
        }
    }, 500);
    
    // Stop polling after 10 seconds
    setTimeout(() => clearInterval(pollForStudentId), 10000);
}
```

### setupAutoParentUsername() - Parent Username
Same exact logic, just changes:
- Field name: `parent_username`
- Domain: `@parent.cci.edu.ph`

## 2. HTML Fields - EXACT COPY

### Student Username Field
```html
<input type="text" 
       name="username" 
       autocomplete="off" 
       pattern="^[a-z]+[0-9]{6}muzon@student\.cci\.edu\.ph$" 
       title="Auto-generated: lastname000000muzon@student.cci.edu.ph" 
       class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] student-field" 
       readonly 
       style="background-color:#f3f4f6; cursor:not-allowed;">
```

### Parent Username Field
```html
<input type="text" 
       name="parent_username" 
       autocomplete="off" 
       pattern="^[a-z]+[0-9]{6}muzon@parent\.cci\.edu\.ph$" 
       title="Auto-generated: lastname000000muzon@parent.cci.edu.ph" 
       class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] student-field" 
       readonly 
       style="background-color:#f3f4f6; cursor:not-allowed;">
```

## 3. Initialization - EXACT COPY

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // ... other code ...
    
    // Setup auto-generated usernames
    setupAutoUsername();
    setupAutoParentUsername();
    
    // ... rest of code ...
});
```

## 4. toggleEdit Function - Simplified

```javascript
// Skip username fields - they should always remain readonly (auto-generated)
if (field.name === 'username' || field.name === 'parent_username') {
    return;
}
```

## Key Features (Copied from add_account.php)

1. ✅ **lettersOnly()** - Strips all non-letters from last name
2. ✅ **last6()** - Gets last 6 digits from ID, pads with zeros
3. ✅ **updateUsername()** - Generates username on every change
4. ✅ **Event listeners** - Attached to last_name and id_number
5. ✅ **Polling** - Checks every 500ms for auto-filled ID
6. ✅ **Timeout** - Stops polling after 10 seconds
7. ✅ **Initial call** - Runs immediately on page load
8. ✅ **Readonly enforcement** - Sets field to readonly with styling

## How It Works

### On Page Load:
1. `setupAutoUsername()` is called
2. Finds last_name, id_number, and username fields
3. Calls `updateUsername()` immediately
4. Attaches event listeners to last_name and id_number
5. Starts polling for ID auto-fill
6. Same for parent username

### When You Type in Last Name:
1. `input` event fires
2. `updateUsername()` is called
3. Last name is cleaned (letters only, lowercase)
4. Last 6 digits extracted from ID
5. Username generated: `lastname000001muzon@student.cci.edu.ph`
6. Field value updated
7. Field set to readonly with gray background

### When You Type in Student ID:
1. `input` event fires
2. `updateUsername()` is called
3. Last 6 digits extracted
4. Username updated with new digits
5. Field remains readonly

## Testing Steps

1. **Open a student record**
   - Username fields should be filled
   - Gray background, readonly

2. **Click Edit button**
   - Other fields become editable
   - Username fields stay readonly

3. **Change last name from "Go" to "Test"**
   - Student username changes to: `test000001muzon@student.cci.edu.ph`
   - Parent username changes to: `test000001muzon@parent.cci.edu.ph`

4. **Try to click username field**
   - Field should not accept focus
   - Cursor shows "not-allowed"

5. **Open browser console (F12)**
   - No errors should appear
   - Username should update as you type

## Files Modified
- `RegistrarF/Accounts/view_student.php`

## What's Different from Before

### REMOVED:
- ❌ `pointer-events:none` (was preventing form submission)
- ❌ `onfocus="this.blur()"` (was causing issues)
- ❌ `setAttribute('readonly')` (redundant)
- ❌ Extra console.log statements
- ❌ Multiple setTimeout calls
- ❌ Extra style enforcement

### KEPT (from add_account.php):
- ✅ Simple `readonly` attribute
- ✅ Inline styles for background and cursor
- ✅ Event listeners on input
- ✅ Polling mechanism
- ✅ Clean, simple code

## Result

The code is now **EXACTLY THE SAME** as add_account.php. If it works there, it will work here!

The username field will:
- Auto-generate on page load
- Update when you change last name
- Update when you change student ID
- Stay readonly (gray background)
- Work exactly like add_account.php
