# Script Execution Fix - Using Indirect Eval

## Problem

When trying to execute scripts from loaded HTML, we got:
```
Uncaught SyntaxError: Identifier 'gradeOptions' has already been declared
```

**Cause:** Variables were being declared multiple times when scripts were executed.

## Solution

Changed from creating new script elements to using **indirect eval** which executes code in the global scope.

### Before (Caused Errors):
```javascript
const newScript = document.createElement('script');
newScript.textContent = script.textContent;
document.body.appendChild(newScript);
```

### After (Works):
```javascript
// Use indirect eval to execute in global scope
(0, eval)(script.textContent);
```

## How Indirect Eval Works

### Regular eval:
```javascript
eval(code); // Executes in local scope
```

### Indirect eval:
```javascript
(0, eval)(code); // Executes in GLOBAL scope
```

The `(0, eval)` syntax is a JavaScript trick that:
1. Evaluates to the `eval` function
2. But loses its "direct eval" status
3. Causes it to execute in global scope instead of local scope

## Why This Fixes the Issue

### Problem with appendChild:
- Creates a new execution context
- Variables declared with `let`/`const` can conflict
- Multiple executions cause "already declared" errors

### Solution with indirect eval:
- Executes in global scope
- Functions become globally available
- `setupAutoUsername` and `setupAutoParentUsername` are accessible
- No variable conflicts

## Updated Code

```javascript
// Extract and execute only the script content (using eval in global scope)
const scripts = inner.querySelectorAll('script');
scripts.forEach((script) => {
    if (!script.src && script.textContent.trim()) {
        try {
            // Use indirect eval to execute in global scope
            (0, eval)(script.textContent);
        } catch (e) {
            console.warn('Error executing script:', e);
        }
    }
});

// Trigger username setup with multiple attempts
const triggerSetup = () => {
    if (typeof setupAutoUsername === 'function') {
        console.log('Calling setupAutoUsername from AccountList...');
        setupAutoUsername();
    } else {
        console.warn('setupAutoUsername not found');
    }
    if (typeof setupAutoParentUsername === 'function') {
        console.log('Calling setupAutoParentUsername from AccountList...');
        setupAutoParentUsername();
    } else {
        console.warn('setupAutoParentUsername not found');
    }
};

// Try multiple times with delays
setTimeout(triggerSetup, 100);
setTimeout(triggerSetup, 300);
setTimeout(triggerSetup, 500);
```

## What You'll See in Console

### ✅ Success:
```
Student data loaded, length: 67715
Calling setupAutoUsername from AccountList...
setupAutoUsername called { lastNameField: "found", ... }
✅ Username generated: go000001muzon@student.cci.edu.ph
Calling setupAutoParentUsername from AccountList...
✅ Username generated: go000001muzon@parent.cci.edu.ph
```

### ❌ If Functions Not Found:
```
setupAutoUsername not found
setupAutoParentUsername not found
```

This means the eval didn't work or functions weren't defined.

## Testing

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Refresh page** (Ctrl+F5)
3. **Open console** (F12)
4. **Click on a student**
5. **Check console messages**
6. **Click Edit**
7. **Change last name**
8. **Watch username update!**

## Why Multiple Attempts?

We call `triggerSetup()` three times:
- At 100ms
- At 300ms
- At 500ms

This ensures it works even if:
- DOM isn't ready immediately
- Scripts take time to execute
- Browser is slow

## Files Modified

- `RegistrarF/AccountList.php`

## Result

✅ No more "already declared" errors
✅ Scripts execute in global scope
✅ Functions are accessible
✅ Username auto-generation works
✅ Multiple timing attempts ensure reliability
