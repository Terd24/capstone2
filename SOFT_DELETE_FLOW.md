# Soft Delete System - Visual Flow

## 📊 System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    SOFT DELETE SYSTEM FLOW                       │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│   REGISTRAR      │
│   Deletes        │
│   Student        │
└────────┬─────────┘
         │
         ▼
┌────────────────────────────────────────┐
│  Student Record Marked as Deleted      │
│  • deleted_at = NOW()                  │
│  • deleted_by = "Registrar Admin"      │
│  • deleted_reason = "Administrative"   │
└────────┬───────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────┐
│  Record Moves to "Deleted Items"       │
│  • Disappears from Registrar view      │
│  • Appears in Super Admin dashboard    │
│  • Data preserved in database          │
└────────┬───────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────┐
│     SUPER ADMIN ACTIONS                │
│                                        │
│  Option 1: RESTORE                     │
│  ├─ Clears deletion flags              │
│  ├─ Record returns to Registrar        │
│  └─ Logs restoration action            │
│                                        │
│  Option 2: EXPORT                      │
│  ├─ Creates JSON backup file           │
│  ├─ Includes all related data          │
│  └─ Saves to exports folder            │
│                                        │
│  Option 3: KEEP IN DELETED ITEMS       │
│  └─ Record stays for future review     │
└────────────────────────────────────────┘


┌──────────────────┐
│   HR ADMIN       │
│   Deletes        │
│   Employee       │
└────────┬─────────┘
         │
         ▼
┌────────────────────────────────────────┐
│  Employee Record Marked as Deleted     │
│  • deleted_at = NOW()                  │
│  • deleted_by = "HR Administrator"     │
│  • deleted_reason = "Administrative"   │
└────────┬───────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────┐
│  Record Moves to "Deleted Items"       │
│  • Disappears from HR view             │
│  • Appears in Super Admin dashboard    │
│  • Data preserved in database          │
└────────┬───────────────────────────────┘
         │
         ▼
┌────────────────────────────────────────┐
│     SUPER ADMIN ACTIONS                │
│  (Same options as above)               │
└────────────────────────────────────────┘
```

## 🔄 Restoration Flow

```
DELETED RECORD
      │
      ▼
┌─────────────────────┐
│  Super Admin        │
│  Clicks "Restore"   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────┐
│  Confirmation Dialog            │
│  "Restore this record?"         │
└──────────┬──────────────────────┘
           │
           ▼ (Yes)
┌─────────────────────────────────┐
│  Database Update:               │
│  • deleted_at = NULL            │
│  • deleted_by = NULL            │
│  • deleted_reason = NULL        │
└──────────┬──────────────────────┘
           │
           ▼
┌─────────────────────────────────┐
│  System Logs:                   │
│  • Action: RESTORE_STUDENT      │
│  • Performed by: Super Admin    │
│  • Timestamp: NOW()             │
└──────────┬──────────────────────┘
           │
           ▼
┌─────────────────────────────────┐
│  RECORD RESTORED                │
│  • Reappears in original system │
│  • Fully functional             │
│  • All data intact              │
└─────────────────────────────────┘
```

## 📤 Export Flow

```
DELETED RECORD
      │
      ▼
┌─────────────────────┐
│  Super Admin        │
│  Clicks "Export"    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────┐
│  System Gathers Data:           │
│  • Personal information         │
│  • Academic/Work records        │
│  • Financial data               │
│  • Attendance history           │
│  • Deletion metadata            │
└──────────┬──────────────────────┘
           │
           ▼
┌─────────────────────────────────┐
│  Creates JSON File:             │
│  • Formatted data structure     │
│  • Timestamped filename         │
│  • Complete record backup       │
└──────────┬──────────────────────┘
           │
           ▼
┌─────────────────────────────────┐
│  Saves to Server:               │
│  Path: exports/deleted_accounts/│
│  File: DELETED_[TYPE]_[ID].json │
└──────────┬──────────────────────┘
           │
           ▼
┌─────────────────────────────────┐
│  Downloads to User:             │
│  • Browser download dialog      │
│  • User chooses save location   │
│  • File ready for archival      │
└─────────────────────────────────┘
```

## 🗄️ Database Structure

```
┌─────────────────────────────────────────┐
│         student_account TABLE           │
├─────────────────────────────────────────┤
│  id_number (PK)                         │
│  first_name                             │
│  last_name                              │
│  ... (other student fields)             │
│  ┌───────────────────────────────────┐  │
│  │  SOFT DELETE COLUMNS              │  │
│  ├───────────────────────────────────┤  │
│  │  deleted_at      DATETIME NULL    │  │
│  │  deleted_by      VARCHAR(255)     │  │
│  │  deleted_reason  TEXT             │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│           employees TABLE               │
├─────────────────────────────────────────┤
│  id_number (PK)                         │
│  first_name                             │
│  last_name                              │
│  ... (other employee fields)            │
│  ┌───────────────────────────────────┐  │
│  │  SOFT DELETE COLUMNS              │  │
│  ├───────────────────────────────────┤  │
│  │  deleted_at      DATETIME NULL    │  │
│  │  deleted_by      VARCHAR(255)     │  │
│  │  deleted_reason  TEXT             │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## 🎯 Query Logic

### Show Active Records (Registrar/HR View)
```sql
SELECT * FROM student_account 
WHERE deleted_at IS NULL
```

### Show Deleted Records (Super Admin View)
```sql
SELECT * FROM student_account 
WHERE deleted_at IS NOT NULL
ORDER BY deleted_at DESC
```

### Soft Delete a Record
```sql
UPDATE student_account 
SET deleted_at = NOW(),
    deleted_by = 'Registrar Admin',
    deleted_reason = 'Administrative purposes'
WHERE id_number = 'S2025006'
```

### Restore a Record
```sql
UPDATE student_account 
SET deleted_at = NULL,
    deleted_by = NULL,
    deleted_reason = NULL
WHERE id_number = 'S2025006'
```

## 📱 User Interface Flow

```
REGISTRAR DASHBOARD
    │
    ├─ Account List
    │   └─ Shows only active students (deleted_at IS NULL)
    │
    ├─ Click Student → View Details
    │   └─ Click "Delete" Button
    │       └─ Confirmation Dialog
    │           └─ Record soft-deleted
    │               └─ Disappears from list
    │
    └─ Student no longer visible to Registrar


SUPER ADMIN DASHBOARD
    │
    ├─ Deleted Items Section
    │   │
    │   ├─ 🔴 Deleted Students (Count)
    │   │   └─ Table showing all deleted students
    │   │       ├─ Student Info
    │   │       ├─ Deletion Info (who, when, why)
    │   │       └─ Actions: [Restore] [Export]
    │   │
    │   └─ 🟠 Deleted Employees (Count)
    │       └─ Table showing all deleted employees
    │           ├─ Employee Info
    │           ├─ Deletion Info (who, when, why)
    │           └─ Actions: [Restore] [Export]
    │
    └─ Click "Restore" → Record returns to original system
```

## 🔐 Security & Permissions

```
┌─────────────────────────────────────────┐
│         PERMISSION MATRIX               │
├─────────────────────────────────────────┤
│                                         │
│  REGISTRAR:                             │
│  ✅ View active students                │
│  ✅ Delete students (soft delete)       │
│  ❌ View deleted students               │
│  ❌ Restore students                    │
│                                         │
│  HR ADMIN:                              │
│  ✅ View active employees               │
│  ✅ Delete employees (soft delete)      │
│  ❌ View deleted employees              │
│  ❌ Restore employees                   │
│                                         │
│  SUPER ADMIN:                           │
│  ✅ View all records (active + deleted) │
│  ✅ View deleted items section          │
│  ✅ Restore any deleted record          │
│  ✅ Export deleted records              │
│  ✅ View deletion audit trail           │
│                                         │
└─────────────────────────────────────────┘
```

## 📈 Benefits Visualization

```
BEFORE (Hard Delete):
Student Deleted → ❌ PERMANENTLY GONE
                  ❌ No recovery possible
                  ❌ Data lost forever
                  ❌ No audit trail

AFTER (Soft Delete):
Student Deleted → ✅ Marked as deleted
                  ✅ Data preserved
                  ✅ Can be restored
                  ✅ Full audit trail
                  ✅ Export capability
                  ✅ Super Admin oversight
```

## 🎉 Summary

The soft delete system provides:
- ✅ **Safety**: No accidental permanent deletions
- ✅ **Recovery**: Easy restoration of records
- ✅ **Audit**: Complete deletion history
- ✅ **Compliance**: Data retention for review
- ✅ **Control**: Super Admin oversight
- ✅ **Export**: Backup before permanent deletion
