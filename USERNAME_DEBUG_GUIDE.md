# Username Auto-Generation - Debug Guide

## Changes Made

### 1. Added Console Logging
The code now logs detailed information to help debug why username isn't generating.

### 2. Added Multiple Timing Attempts
Calls the setup functions at 100ms, 500ms, and 1000ms to ensure fields are ready.

### 3. Added Error Messages
Shows clear error messages if fields aren't found.

## How to Debug

### Step 1: Open Browser Console
1. Open the student record page
2. Press **F12** to open Developer Tools
3. Click on the **Console** tab

### Step 2: Check Console Messages

You should see messages like:

#### ✅ **If Working:**
```
Setting up auto username...
setupAutoUsername called {
  lastNameField: "found",
  idField: "found", 
  usernameField: "found",
  lastNameValue: "Go",
  idValue: "02200000001"
}
updateUsername called {
  lastName: "go",
  idNumber: "02200000001",
  tail: "000001",
  tailLength: 6
}
✅ Username generated: go000001muzon@student.cci.edu.ph
```

#### ❌ **If NOT Working - Fields Not Found:**
```
Setting up auto username...
setupAutoUsername called {
  lastNameField: "NOT FOUND",
  idField: "NOT FOUND",
  usernameField: "NOT FOUND"
}
Username fields not found!
```

**Solution:** The field names might be wrong. Check the HTML.

#### ❌ **If NOT Working - Missing Data:**
```
setupAutoUsername called {
  lastNameField: "found",
  idField: "found",
  usernameField: "found",
  lastNameValue: "",
  idValue: ""
}
updateUsername called {
  lastName: "",
  idNumber: "",
  tail: "000000",
  tailLength: 6
}
Cannot generate username - missing lastName or tail not 6 digits
```

**Solution:** The last name or ID fields are empty in the database.

#### ❌ **If NOT Working - ID Too Short:**
```
updateUsername called {
  lastName: "go",
  idNumber: "123",
  tail: "000123",
  tailLength: 6
}
Cannot generate username - missing lastName or tail not 6 digits
```

**Solution:** The ID needs to be at least 6 digits.

### Step 3: Test Manually

1. **Click Edit button**
2. **Type in the Last Name field**
3. **Watch the console** - You should see:
   ```
   updateUsername called { lastName: "test", ... }
   ✅ Username generated: test000001muzon@student.cci.edu.ph
   ```

### Step 4: Check the Username Field

After typing in last name:
- Username field should update automatically
- Should show: `lastname000001muzon@student.cci.edu.ph`
- Field should have gray background
- Cursor should show "not-allowed"

## Common Issues & Solutions

### Issue 1: "Username fields not found!"

**Cause:** JavaScript can't find the input fields

**Check:**
1. View page source (Ctrl+U)
2. Search for `name="username"`
3. Make sure it exists
4. Make sure it's not inside a hidden div

**Fix:** The field might be inside a modal or hidden section that loads later.

### Issue 2: "Cannot generate username - missing lastName"

**Cause:** Last name field is empty

**Check:**
1. Look at the Last Name input field
2. Is it empty?
3. Check database - does this student have a last name?

**Fix:** Enter a last name in the field.

### Issue 3: "tail not 6 digits"

**Cause:** Student ID is too short or empty

**Check:**
1. Look at the Student ID field
2. Should be 11 digits (e.g., 02200000001)
3. Check database - does this student have an ID?

**Fix:** Make sure Student ID is at least 6 digits.

### Issue 4: Username field is editable

**Cause:** The readonly attribute isn't being applied

**Check Console:**
```
✅ Username generated: go000001muzon@student.cci.edu.ph
```

If you see this but field is still editable, the styling isn't being applied.

**Fix:** Check if toggleEdit() is removing the readonly attribute.

### Issue 5: Username doesn't update when typing

**Cause:** Event listeners not attached

**Check Console:** Should see multiple calls to `updateUsername` as you type

**Fix:** Make sure `addEventListener` is being called:
```javascript
lastNameField.addEventListener('input', updateUsername);
```

## Testing Checklist

- [ ] Open browser console (F12)
- [ ] Refresh the page
- [ ] Check for console messages
- [ ] See "Setting up auto username..."
- [ ] See "setupAutoUsername called"
- [ ] See "✅ Username generated"
- [ ] Click Edit button
- [ ] Type in Last Name field
- [ ] See "updateUsername called" in console
- [ ] See username field update
- [ ] Username field has gray background
- [ ] Username field shows "not-allowed" cursor

## Next Steps

Based on what you see in the console:

1. **If you see "Username fields not found!"**
   → The HTML structure might be different
   → Check the field names in the HTML

2. **If you see "Cannot generate username"**
   → The data is missing
   → Check the database values

3. **If you see "✅ Username generated" but field is blank**
   → The value is being set but not displaying
   → Check if there's CSS hiding it

4. **If nothing appears in console**
   → JavaScript isn't running
   → Check for JavaScript errors above in console

## Report Back

When reporting the issue, please include:
1. Screenshot of browser console
2. What messages you see
3. What happens when you type in Last Name field
4. Whether username field updates or stays blank
