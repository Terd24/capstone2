# Modal Loading Fix - Username Auto-Generation

## Problem Identified ✅

The student view is loaded in a **modal via AJAX/fetch**, not as a standalone page. When HTML is inserted using `innerHTML`, the `<script>` tags inside are **NOT executed automatically**.

### Why It Wasn't Working:

1. `AccountList.php` calls `fetch('view_student.php?embed=1&id=...')`
2. Gets HTML response
3. Sets `inner.innerHTML = html`
4. **Scripts inside the HTML don't run!**
5. `setupAutoUsername()` never gets called
6. Username field stays blank

## Solution Applied ✅

Updated `AccountList.php` to:

### 1. Execute Scripts from Loaded HTML
```javascript
// Execute scripts in the loaded HTML
const scripts = inner.querySelectorAll('script');
scripts.forEach(script => {
    const newScript = document.createElement('script');
    if (script.src) {
        newScript.src = script.src;
    } else {
        newScript.textContent = script.textContent;
    }
    document.body.appendChild(newScript);
    document.body.removeChild(newScript);
});
```

### 2. Manually Trigger Setup Functions
```javascript
// Trigger username setup after a short delay to ensure DOM is ready
setTimeout(() => {
    if (typeof setupAutoUsername === 'function') {
        console.log('Calling setupAutoUsername from AccountList...');
        setupAutoUsername();
    }
    if (typeof setupAutoParentUsername === 'function') {
        console.log('Calling setupAutoParentUsername from AccountList...');
        setupAutoParentUsername();
    }
}, 200);
```

## How It Works Now

### Step 1: Modal Opens
```
User clicks student row
→ viewStudent(studentId) called
→ Fetches view_student.php?embed=1&id=...
```

### Step 2: HTML Loaded
```
HTML response received
→ inner.innerHTML = html
→ Content displayed in modal
```

### Step 3: Scripts Executed
```
Find all <script> tags in loaded HTML
→ Create new script elements
→ Copy content
→ Append to document body (executes them)
→ Remove from body (cleanup)
```

### Step 4: Setup Functions Called
```
Wait 200ms for DOM to be ready
→ Call setupAutoUsername()
→ Call setupAutoParentUsername()
→ Username fields populated
→ Event listeners attached
```

### Step 5: User Edits
```
User clicks "Edit" button
→ Changes last name
→ Event listener fires
→ updateUsername() called
→ Username field updates automatically!
```

## Testing

### What You Should See in Console:

```
viewStudent called with ID: 02200000001
Modal elements found, opening...
Modal should be visible now
Fetching student data...
Student data loaded, length: 67715
Calling setupAutoUsername from AccountList...
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
Calling setupAutoParentUsername from AccountList...
✅ Username generated: go000001muzon@parent.cci.edu.ph
```

### Test Steps:

1. **Open AccountList.php**
2. **Click on a student row**
3. **Open browser console (F12)**
4. **Check for the messages above**
5. **Click "Edit" button**
6. **Change the last name**
7. **Watch username field update!**

## Why This Fix Works

### Problem with innerHTML:
- `innerHTML` parses HTML but doesn't execute scripts
- Security feature to prevent XSS attacks
- Scripts need to be manually executed

### Our Solution:
1. Extract all `<script>` tags from loaded HTML
2. Create new script elements
3. Copy the content
4. Append to document (this executes them)
5. Also manually call setup functions as backup

### Timing:
- 200ms delay ensures DOM is fully ready
- Multiple attempts in view_student.php (100ms, 500ms, 1000ms)
- Ensures it works even if timing varies

## Files Modified

1. **RegistrarF/AccountList.php**
   - Added script execution after innerHTML
   - Added manual setup function calls
   - Added console logging

2. **RegistrarF/Accounts/view_student.php**
   - Already has console logging
   - Already has multiple timing attempts
   - Functions are defined and ready

## Result

✅ Scripts now execute when modal loads
✅ Setup functions are called
✅ Username fields populate on load
✅ Username updates when you type
✅ Works exactly like add_account.php

## Troubleshooting

### If still not working:

1. **Check console for errors**
   - Red error messages?
   - JavaScript syntax errors?

2. **Check if functions are defined**
   ```javascript
   console.log(typeof setupAutoUsername); // should be "function"
   ```

3. **Check if fields are found**
   - Should see "found" for all three fields
   - If "NOT FOUND", field names might be wrong

4. **Check timing**
   - Try increasing the 200ms delay to 500ms
   - Some browsers might need more time

5. **Check for conflicts**
   - Other JavaScript might be interfering
   - Check for errors in console
