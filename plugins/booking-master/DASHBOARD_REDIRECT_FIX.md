# Fix for Dashboard Redirect Issue

## Problem
When mentors and mentees click "Visit full dashboard" links, they are redirected to the theme's front-end dashboard instead of the plugin's admin dashboard.

## Root Cause
The mentor and mentee user roles lack the `edit_dashboard` capability, which is required to access the WordPress admin area. Without this capability, WordPress redirects users to the front-end.

## Solution Applied

### 1. Updated User Roles Creation
Modified `includes/class-activator.php` to add `edit_dashboard` capability to both mentor and mentee roles.

### 2. Created Roles Updater Class
Created `includes/class-roles-updater.php` to update existing user roles for sites where the plugin is already installed.

### 3. Created Admin Notices Handler
Created `admin/class-admin-notices.php` to show admin notices when roles need updating and provide a one-click fix.

### 4. Updated Main Plugin Class
Modified `includes/class-booking-master.php` to include the new classes and automatically check for role updates.

## How to Apply the Fix

### Option 1: Automatic Update (Recommended)
1. The plugin will automatically detect if roles need updating
2. Administrators will see a notice in the admin area
3. Click "Update Roles Now" to fix the issue instantly

### Option 2: Manual Method
If you want to apply the fix manually:

1. **Deactivate and Reactivate Plugin** (This will recreate roles with correct capabilities)
   - Go to WordPress admin → Plugins
   - Deactivate "Booking Master"
   - Reactivate "Booking Master"

2. **Or run this code** (Add to functions.php temporarily):
   ```php
   function bm_fix_user_roles() {
       // Update mentor role
       $mentor_role = get_role('mentor');
       if ($mentor_role) {
           $mentor_role->add_cap('edit_dashboard');
       }
       
       // Update mentee role
       $mentee_role = get_role('mentee');
       if ($mentee_role) {
           $mentee_role->add_cap('edit_dashboard');
       }
   }
   add_action('init', 'bm_fix_user_roles');
   ```
   
   Then visit any page on your site and remove the code.

## What This Fix Does

1. **Adds `edit_dashboard` capability** to mentor and mentee roles
2. **Preserves existing capabilities** (bm_manage_services, bm_book_services, etc.)
3. **Provides automatic detection** of when roles need updating
4. **Shows admin notices** to administrators for easy fixing
5. **Includes one-click fix** from the admin area

## Testing the Fix

After applying the fix:

1. **Test as Administrator**: 
   - Go to Users → All Users
   - Find a mentor/mentee user
   - Verify they have the "edit_dashboard" capability

2. **Test as Mentor**:
   - Login as a mentor user
   - Visit a page with `[booking_master_user_dashboard]` shortcode
   - Click "Visit full dashboard"
   - Should now go to: `/wp-admin/admin.php?page=booking-master-mentor`

3. **Test as Mentee**:
   - Login as a mentee user
   - Visit a page with `[booking_master_user_dashboard]` shortcode
   - Click "Visit full dashboard"
   - Should now go to: `/wp-admin/admin.php?page=booking-master-mentee`

## Important Notes

- **Backup your site** before making changes
- **Test with a mentor/mentee account** after applying the fix
- **The fix is backwards compatible** - existing functionality won't be affected
- **New user registrations** will automatically get the correct capabilities

## If the Fix Doesn't Work

1. **Check user roles**:
   - Use a plugin like "User Role Editor" to verify capabilities
   - Ensure mentor/mentee roles have `edit_dashboard` capability

2. **Check for conflicts**:
   - Temporarily deactivate other plugins
   - Switch to a default WordPress theme
   - Test if the redirect still occurs

3. **Check theme settings**:
   - Some themes force users to custom dashboards
   - Check theme/plugin settings for "redirect after login" options

4. **Check .htaccess**:
   - Ensure no redirect rules are interfering
   - Temporarily rename .htaccess to test

## Alternative Solution (If Admin Access is Restricted)

If you want to keep mentors/mentees out of the admin area but still provide dashboards:

1. Create front-end dashboard pages using the shortcodes
2. Remove the "Visit full dashboard" links
3. Use only the shortcode-based dashboards:
   - `[booking_master_user_dashboard]`
   - `[booking_master_services]`
   - `[booking_master_mentor_application]`

This provides full functionality without requiring admin access.