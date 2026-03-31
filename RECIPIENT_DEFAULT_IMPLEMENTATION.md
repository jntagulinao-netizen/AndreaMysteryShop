# Recipient Management & Default Selection - Implementation Summary

## Overview
This update adds the ability for users to save new recipients with the option to mark them as default, and automatically select the default recipient when checking out.

## Changes Made

### 1. **Database Schema Update**
- **New Column**: `is_default` (BOOLEAN DEFAULT FALSE) added to `recipients` table
- **New Index**: `idx_default_recipient` on (user_id, is_default) for faster queries
- **Status**: ✓ Column already exists in your database (verified)

### 2. **API Endpoints Updated**

#### `api/add-recipient.php` - Enhanced
- Now accepts `is_default` parameter from form
- When saving a recipient as default, automatically removes default flag from other recipients
- Returns `is_default` status in response
- **Endpoint**: POST `/api/add-recipient.php`
- **New Parameters**:
  - `is_default` (optional): Set to 'true' to mark as default recipient

**Example Request**:
```php
POST /api/add-recipient.php
- recipient_name: "John Doe"
- phone_no: "+63 9123456789"
- street_name: "123 Main Street"
- city: "Manila"
- region: "Metro Manila"
- is_default: "true"  // NEW: Mark as default
```

#### `api/get-recipients.php` - Enhanced
- Now returns `is_default` flag for each recipient
- Returns `default_recipient` object (the currently set default)
- Orders recipients by default status first, then by ID
- **Response includes**:
  ```json
  {
    "recipients": [
      {
        "recipient_id": 1,
        "recipient_name": "John Doe",
        "phone_no": "+63 9123456789",
        "street_name": "123 Main Street",
        "city": "Manila",
        "region": "Metro Manila",
        "is_default": true
      },
      // ... more recipients
    ],
    "default_recipient": {
      // The recipient marked as default (null if none)
    }
  }
  ```

### 3. **Frontend Changes - user_dashboard.php**

#### New UI Elements:
1. **"Set as default recipient" Checkbox** (Line 452)
   - In the new recipient form
   - Allows users to mark a new recipient as their default

2. **"Save Recipient" Button** (Line 457)
   - Orange button with hover effect
   - Saves the recipient with the selected options
   - Auto-refreshes recipient list after save

3. **Default Recipient Indicator**
   - Shows "★ DEFAULT" label next to default recipient name
   - Default recipient is highlighted with orange border and light background
   - Default recipient is automatically selected when checkout opens

#### New JavaScript Functions:

**`saveNewRecipient()`** (Lines 1095-1146)
- Validates all required fields
- Sends form data to API with is_default flag
- Handles errors with user-friendly messages
- Reloads recipient list after successful save
- Auto-closes form and shows recipient list

**`toggleNewRecipientForm()`** (Lines 1077-1095)
- Clears all form fields when opening
- Toggles visibility of form vs. recipient list
- Updated to handle "Save" and "Cancel" buttons properly

**`loadRecipients()`** (Lines 1006-1043) - Enhanced
- Now displays default recipient with "★ DEFAULT" label
- Highlights default recipient with orange styling
- Auto-selects default recipient if one exists
- Falls back to first recipient if no default set

#### CSS Updates (Lines 129-131):
```css
.save-new-recipient-btn { 
  background: #FF6F00; 
  color: white; 
  border: none; 
  padding: 10px 16px; 
  border-radius: 6px; 
  cursor: pointer; 
  font-size: 14px; 
  font-weight: 600; 
  transition: all 0.2s; 
  flex: 1; 
}
.save-new-recipient-btn:hover { background: #FF8C00; }
.cancel-new-btn { 
  background: #f5f5f5; 
  color: #666; 
  border: 1px solid #ddd; 
  padding: 10px 16px; 
  border-radius: 6px; 
  cursor: pointer; 
  font-size: 14px; 
  font-weight: 600; 
  transition: all 0.2s; 
  flex: 1; 
}
.cancel-new-btn:hover { background: #ebebeb; }
```

## How it Works - User Flow

### Adding a New Recipient:
1. User clicks "**+ Add New Recipient**" button
2. Form appears with input fields:
   - Full Name *
   - Phone Number *
   - Street Name/Number *
   - Unit/Floor/Building (optional)
   - District (optional)
   - City *
   - Region/Province *
   - ☐ Set as default recipient (new checkbox)
3. User enters all required information
4. User optionally checks "Set as default recipient" checkbox
5. User clicks "**Save Recipient**" button
6. Recipient is saved to database
7. If marked as default, previous default is cleared
8. Recipient list reloads showing the new recipient
9. If marked as default, it appears first with "★ DEFAULT" label

### During Checkout:
1. Checkout modal opens
2. Default recipient is automatically selected and pre-filled
3. User can select payment method
4. User can click Place Order to proceed
5. If no default exists, first recipient is selected

### Subsequent Visits:
1. Each time checkout opens, default recipient is automatically selected
2. User can change recipient at checkout if needed
3. To change the default, they can:
   - Add a new recipient and mark as default (new default replaces old)
   - Currently, users cannot change default from existing recipients (can be added later)

## Files Modified

1. **user_dashboard.php** (Main application file)
   - Added CSS for save button and cancel button
   - Added "Set as default" checkbox to form
   - Added "Save Recipient" button
   - Updated loadRecipients() function
   - Updated toggleNewRecipientForm() function
   - Added saveNewRecipient() function

2. **api/add-recipient.php**
   - Added is_default parameter handling
   - Added logic to clear other defaults when setting new default
   - Updated response to include is_default flag

3. **api/get-recipients.php**
   - Added is_default field to SELECT query
   - Added default_recipient object to response
   - Changed ORDER BY to prioritize default recipient

## Files Created (for reference)

- **add_default_recipient.sql** - SQL migration script (for reference)
- **apply_migration.php** - Automation script for migration (already applied)
- **check_schema.php** - Schema verification script

## Testing Checklist

- [ ] Add a new recipient with "Set as default" checked
- [ ] Verify recipient appears in list with "★ DEFAULT" label
- [ ] Verify default recipient is highlighted with orange styling
- [ ] Open checkout and verify default recipient is pre-selected
- [ ] Add another recipient with default checked
- [ ] Verify previous default is replaced
- [ ] Test form validation (try saving without required fields)
- [ ] Test Cancel button (should clear form and show recipient list)
- [ ] Add recipient without checking default
- [ ] Verify it doesn't affect existing default

## Technical Details

### Database Query Changes:
```sql
-- Old query:
SELECT recipient_id, recipient_name... FROM recipients WHERE user_id = ? ORDER BY recipient_id DESC

-- New query:
SELECT recipient_id, recipient_name..., is_default FROM recipients WHERE user_id = ? ORDER BY is_default DESC, recipient_id DESC
```

### API Response Enhancement:
```json
// Old:
{ "recipients": [...] }

// New:
{
  "recipients": [...],
  "default_recipient": {...}
}
```

## Error Handling

- ✓ Validates all required fields before saving
- ✓ Shows user-friendly error messages
- ✓ Console logging for debugging
- ✓ Graceful fallback if no recipients exist
- ✓ Transaction support in API for data consistency

## Performance Considerations

- Index on (user_id, is_default) speeds up queries for finding default recipient
- Default recipient queries are O(1) with index
- Minimal database overhead for new column

## Security

- ✓ All inputs validated server-side
- ✓ User ID verified from session (prevents cross-user access)
- ✓ Prepared statements used throughout
- ✓ Input sanitization on form fields

## Future Enhancements (Optional)

1. Allow changing default from existing recipients list
2. Option to delete recipients
3. Edit existing recipients
4. Set multiple recipients as favorites
5. Order history showing which recipient was used
6. Recipient validation (address verification API)
7. Recipient suggestions based on order history

## Support

If you encounter any issues:
1. Check browser console (F12) for JavaScript errors
2. Check PHP error logs in XAMPP
3. Verify database migration was applied (is_default column exists)
4. Test API endpoints individually using tools like Postman

---

**Implementation Date**: 2026-03-23
**Status**: ✓ Complete and Ready for Testing
