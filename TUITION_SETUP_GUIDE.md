# Tuition Fee System - Setup Guide

## 🎯 Overview
The system now automatically creates tuition fee records when a registrar adds a student account. The Owner controls the prices, and the Cashier can immediately see and collect payments.

---

## 📋 Step-by-Step Setup

### Step 1: Owner Sets Tuition Prices

1. **Login as Owner**
2. **Navigate to Dashboard** → Click **"Tuition Fees"** in the sidebar
3. **View existing fee structures** organized by school year
4. **Add new fee structure** (if needed):
   - Click "+ Add New Fee Structure"
   - Enter:
     - Grade Level (e.g., "Grade 11")
     - Academic Track (e.g., "STEM")
     - Tuition Fee (e.g., 45000.00)
     - Other Fees (e.g., 9000.00)
     - School Year (e.g., "2024-2025")
   - Click "Add"
5. **Edit existing fees**:
   - Click the edit icon (pencil) on any fee card
   - Update Tuition Fee or Other Fees
   - Click "Update"

**Example Fee Structures:**
```
Grade 11 - STEM
School Year: 2024-2025
Tuition Fee: ₱45,000
Other Fees: ₱9,000
Total: ₱54,000

Grade 12 - ABM
School Year: 2024-2025
Tuition Fee: ₱40,000
Other Fees: ₱8,000
Total: ₱48,000
```

---

### Step 2: Registrar Adds Student

1. **Login as Registrar**
2. **Navigate to Accounts** → Click **"Add Account"**
3. **Fill in student information**:
   - Personal details (name, DOB, etc.)
   - **Grade Level** (e.g., "Grade 11")
   - **Academic Track** (e.g., "STEM")
   - **School Year** (e.g., "2024-2025")
   - **Semester** (e.g., "1st Semester")
   - Parent information
4. **Click "Add Student"**

**What happens automatically:**
- System finds matching tuition fee structure
- Creates fee record: "Tuition Fee" = ₱54,000 (for Grade 11 STEM)
- Links to school year term: "2024-2025 - 1st Semester"
- Sets paid amount to ₱0.00

---

### Step 3: Cashier Views Balance

1. **Login as Cashier**
2. **Search for student** by:
   - Name or ID in search bar, OR
   - Scan RFID card, OR
   - Scan QR code
3. **View Student Balance tab**:
   - Shows: Tuition Fee
   - Amount Due: ₱54,000
   - Paid: ₱0
   - Balance: ₱54,000
4. **Record payment**:
   - Click "Edit" on the fee
   - Enter paid amount (e.g., ₱20,000)
   - Select payment method
   - Click "Update Payment"
5. **Balance updates automatically**:
   - Paid: ₱20,000
   - Balance: ₱34,000

---

## 🔍 Verification Checklist

### ✅ Owner Verification
- [ ] Can access Tuition Fees section
- [ ] Can view all fee structures
- [ ] Can add new fee structure
- [ ] Can edit existing fees
- [ ] Total fee = Tuition Fee + Other Fees

### ✅ Registrar Verification
- [ ] Can add student account
- [ ] Student's grade level and track match a fee structure
- [ ] No errors during student creation
- [ ] Success message appears

### ✅ Cashier Verification
- [ ] Can search for newly added student
- [ ] Student Balance tab shows "Tuition Fee"
- [ ] Amount matches Owner's fee structure
- [ ] Can record payment
- [ ] Balance updates correctly

---

## 🚨 Troubleshooting

### Problem: Tuition fee not appearing for student

**Possible causes:**
1. **No matching fee structure** - Owner needs to add fee structure for that grade/track/year
2. **Mismatch in data** - Check:
   - Grade level spelling (e.g., "Grade 11" vs "11th Grade")
   - Academic track spelling (e.g., "STEM" vs "stem")
   - School year format (e.g., "2024-2025" vs "2024/2025")

**Solution:**
- Owner: Add the missing fee structure with exact matching values
- Registrar: Re-check student's grade level and track when adding

### Problem: Can't edit tuition fee price

**Cause:** Only Owner can edit tuition fee prices

**Solution:** Login as Owner to modify prices

### Problem: Balance not updating after payment

**Cause:** Database connection issue or incorrect fee ID

**Solution:** 
- Refresh the page
- Check browser console for errors
- Verify database connection

---

## 📊 Database Structure

### Tables Used:

**tuition_fee_structure** (Owner manages)
```
id | grade_level | academic_track | tuition_fee | other_fees | total_fee | school_year
1  | Grade 11    | STEM          | 45000.00    | 9000.00    | 54000.00  | 2024-2025
```

**student_fee_items** (Auto-created by Registrar)
```
id | id_number   | school_year_term        | fee_type     | amount   | paid
1  | 02200000001 | 2024-2025 - 1st Semester| Tuition Fee  | 54000.00 | 0.00
```

**student_payments** (Created by Cashier)
```
id | id_number   | school_year_term        | fee_type     | amount   | payment_method | date
1  | 02200000001 | 2024-2025 - 1st Semester| Tuition Fee  | 20000.00 | Cash          | 2025-10-19
```

---

## 💡 Tips

1. **Set up all fee structures first** - Before adding students, ensure Owner has created fee structures for all grade levels and tracks
2. **Use consistent naming** - Keep grade levels and track names consistent (e.g., always "Grade 11", never "11th Grade")
3. **Update fees annually** - Owner should create new fee structures for each school year
4. **Monitor balances** - Cashier can generate reports to track outstanding balances
5. **Partial payments** - System supports installment payments - just record each payment separately

---

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Verify database tables exist
3. Check browser console for JavaScript errors
4. Review PHP error logs
5. Contact system administrator

---

**Last Updated:** October 19, 2025
**Version:** 1.0
