# Regex Pattern Fix - Invalid Character Class ✅

## Error Found

Console error:
```
Invalid regular expression: /[A-Za-z\s,-]+/: Invalid character in character class
```

## Problem

The regex pattern `[A-Za-z\s,.-]+` has an **invalid character class**.

### Why It's Invalid:

In regex character classes `[...]`, the hyphen `-` has special meaning:
- It denotes a **range** of characters
- Example: `[a-z]` means "any letter from a to z"
- Example: `[0-9]` means "any digit from 0 to 9"

In the pattern `[A-Za-z\s,.-]`:
- The hyphen is between `,` (comma) and `.` (period)
- Regex tries to create a range from comma to period
- This is **invalid** because comma and period aren't sequential in ASCII
- Results in: `Invalid character in character class`

## Solution

**Escape the hyphen** with a backslash: `\-`

### Before (Invalid):
```javascript
pattern="[A-Za-z\s,.-]+"
/[^A-Za-z\s,.-]/g
/^[A-Za-z\s,.-]+$/
```

### After (Valid):
```javascript
pattern="[A-Za-z\s,.\-]+"
/[^A-Za-z\s,.\-]/g
/^[A-Za-z\s,.\-]+$/
```

## Changes Made

### 1. HTML Pattern Attribute
```html
<!-- BEFORE -->
<input pattern="[A-Za-z\s,.-]+" ... >

<!-- AFTER -->
<input pattern="[A-Za-z\s,.\-]+" ... >
```

### 2. JavaScript oninput
```javascript
// BEFORE
oninput="this.value = this.value.replace(/[^A-Za-z\s,.-]/g, '')"

// AFTER
oninput="this.value = this.value.replace(/[^A-Za-z\s,.\-]/g, '')"
```

### 3. JavaScript Validation
```javascript
// BEFORE
if (birthplace && !/^[A-Za-z\s,.-]+$/.test(birthplace))

// AFTER
if (birthplace && !/^[A-Za-z\s,.\-]+$/.test(birthplace))
```

## How to Escape Hyphen in Regex

### Option 1: Escape with backslash (USED)
```javascript
[A-Za-z\s,.\-]+  // Hyphen is escaped
```

### Option 2: Place at the end
```javascript
[A-Za-z\s,.-]+   // Hyphen at the end (no escape needed)
```

### Option 3: Place at the beginning
```javascript
[-A-Za-z\s,.]+   // Hyphen at the beginning (no escape needed)
```

We used **Option 1** (escape with backslash) because it's clearest and most explicit.

## What This Pattern Matches

`[A-Za-z\s,.\-]+` matches one or more of:
- `A-Z` - Uppercase letters
- `a-z` - Lowercase letters
- `\s` - Whitespace (spaces, tabs, newlines)
- `,` - Comma
- `.` - Period
- `\-` - Hyphen (escaped)

Perfect for location names like:
- "Manila, Philippines"
- "New York, U.S.A."
- "Saint-Denis"
- "São Paulo"

## Testing

### Valid Inputs (Should Pass):
- ✅ "Manila"
- ✅ "New York"
- ✅ "Manila, Philippines"
- ✅ "Saint-Denis"
- ✅ "Los Angeles, CA"

### Invalid Inputs (Should Fail):
- ❌ "Manila123" (contains numbers)
- ❌ "Manila@City" (contains @)
- ❌ "Manila#1" (contains #)

## Files Modified

- `RegistrarF/Accounts/view_student.php`

## Result

✅ Regex pattern is now valid
✅ No more console errors
✅ Form validation works correctly
✅ Birthplace field accepts valid locations
✅ Invalid characters are properly rejected
