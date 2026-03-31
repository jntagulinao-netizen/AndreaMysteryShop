# Quick Start Guide - New Recipient & Default Selection

## For Users

### How to Add a New Recipient
1. Go to checkout
2. Click "**+ Add New Recipient**" button
3. Fill in your details:
   - ❗ Full Name
   - ❗ Phone Number
   - ❗ Street Name/Number
   - City
   - Region/Province
   - Unit/Floor (optional)
   - District (optional)
4. **NEW**: Check "Set as default recipient" if you want this as your primary address
5. Click "**Save Recipient**" button
6. Your recipient is saved and appears in the list!

### Default Recipient
- Shows with a "★ DEFAULT" label
- Highlighted in orange
- Automatically selected when you open checkout
- To change default: Add a new recipient and mark it as default

---

## For Developers

### Key Changes Summary
| File | What Changed |
|------|--------------|
| `api/add-recipient.php` | Now saves `is_default` flag, auto-clears old defaults |
| `api/get-recipients.php` | Returns `is_default` for each recipient + `default_recipient` object |
| `user_dashboard.php` | Added save button, checkbox, saveNewRecipient() function |
| `recipients` table | Added `is_default` column (already exists) |

### JavaScript Functions
- `saveNewRecipient()` - Saves new recipient with validation
- `loadRecipients()` - Loads and displays recipients with default highlighting
- `toggleNewRecipientForm()` - Shows/hides form, clears fields

### API Endpoints
```
POST /api/add-recipient.php
- recipient_name, phone_no, street_name, city, region
- is_default (new) ← NEW PARAMETER

GET /api/get-recipients.php
- Returns: { recipients: [...], default_recipient: {...} } ← NEW FORMAT
```

---

## Testing the Feature

### Quick Test Script
```js
// In browser console (F12)
// Test 1: Check if saveNewRecipient function exists
console.log(typeof saveNewRecipient); // Should show "function"

// Test 2: Check if default recipient is loaded
console.log(recipients); // Should show array with is_default property

// Test 3: Open and close checkout
openCheckout(); // Opens modal
// Should see default recipient pre-selected
closeCheckout(); // Closes modal
```

### Manual Testing
1. ✓ Add recipient without default - should work
2. ✓ Add recipient WITH default - should replace any existing default
3. ✓ Open checkout - default should be pre-selected
4. ✓ Close and reopen checkout - default still pre-selected
5. ✓ Try saving without required fields - should see validation error

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Save button not working | Check browser console (F12) for errors, verify form fields are filled |
| Default not showing | Verify `is_default` column exists in DB, refresh page |
| Database error | Run `apply_migration.php` to ensure schema is updated |
| Form not clearing | Check if setAsDefault checkbox has correct ID |

---

**Created**: 2026-03-23 | **Status**: Ready for Testing ✓
