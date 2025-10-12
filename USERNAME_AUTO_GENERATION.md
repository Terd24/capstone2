# Username Auto-Generation in Edit Student

## Summary
Added automatic username generation in the edit/view student page. When the last name or student ID is changed, the username field automatically updates to match the format: `lastname + last6digits(id) + muzon@student.cci.edu.ph`

## Changes Made

### 1. Added generateUsername() Function
```javascript
function generateUsername() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const idField = document.querySelector('input[name="id_number"]');
    const usernameField = document.querySelector('input[name="username"]');
    
    if (!lastNameField || !idField || !usernameField) return;
    
    const lastName = lastNameField.value.trim().toLowerCase().replace(/\s+/g, '');
    const idNumber = idField.value.trim();
    
    if (lastName && idNumber && idNumber.length >= 6) {
        const last6Digits = idNumber.slice(-6);
        const generatedUsername = lastName + last6Digits + 'muzon@student.cci.edu.ph';
        usernameField.value = generatedUsername;
    }
}
```

### 2. Added Event Listeners
- Attached `input` and `blur` event listeners to the `last_name` field
- Attached `input` and `blur` event listeners to the `id_number` field
- Both trigger the `generateUsername()` function automatically

### 3. Made Username Field Editable
- Removed `disabled` attribute from username field
- Kept `readonly` initially, but becomes editable in edit mode
- Added helper text: "Auto-generated from last name and ID"

### 4. Updated toggleEdit() Function
- Removed username from the skip list
- Now username field becomes editable when Edit button is clicked
- Username auto-generates as user types in last name or ID fields

## How It Works

1. **View Mode**: Username field is readonly and displays current value
2. **Edit Mode**: 
   - Click "Edit" button
   - Username field becomes editable
   - When you change the last name, username auto-updates
   - When you change the ID, username auto-updates
3. **Format**: `lastname000000muzon@student.cci.edu.ph`
   - Example: If last name is "Go" and ID is "02200000001"
   - Username becomes: `go000001muzon@student.cci.edu.ph`

## Benefits
- ✅ Prevents username from being deleted accidentally
- ✅ Automatically regenerates when last name changes
- ✅ Maintains consistent format across all student accounts
- ✅ Works in real-time as user types
- ✅ Matches the behavior from add_account.php

## Testing
1. Open an existing student record
2. Click "Edit"
3. Change the last name (e.g., from "Go" to "Gesterd")
4. Watch the username automatically update
5. Save changes
6. Verify username is correctly saved in database
