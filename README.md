# PawPoint Veterinary Care System

PawPoint is a web-based application for managing veterinary care services, including doctor and patient management, appointment scheduling, and administrative controls.

## Recent Updates

### 1. Doctor Approval System

A doctor approval system has been implemented with the following features:

- When doctors register, their accounts are set to 'pending' status by default
- Doctors cannot log in until an administrator approves their account
- Administrators can view, approve, or reject doctor applications from the admin dashboard and doctor management page
- Doctors receive appropriate status messages when attempting to log in

### 2. Patient Email Verification System

A patient email verification system has been added with these features:

- When patients register, they must verify their email address before they can log in
- Verification emails are sent automatically after registration with a secure token link
- Token links expire after 24 hours for security
- Patients can request new verification emails if needed
- Clear status messages guide patients through the verification process
- All existing patient accounts in the database are automatically marked as verified

## Database Setup

1. Make sure your MySQL server is running
2. Import the `schema.sql` file to create/update your database:
   ```
   mysql -u root -p < schema.sql
   ```
   
   Or you can manually run the SQL commands in the file using phpMyAdmin or any other MySQL client.

3. The schema includes:
   - Tables for doctors, patients, appointments, and admins
   - Status column for doctor approval system
   - Email verification fields for patient verification system
   - Default admin account creation (username: admin, password: admin123)

## Admin Dashboard

The admin dashboard has been updated to show:
- Total doctor count
- Pending doctor applications count with direct approval links
- A list of recent pending applications for quick approval

## Doctor Management Page

The doctor management page now includes:
- Status indicators for doctor accounts (pending, approved, rejected)
- Action buttons to approve or reject doctor accounts
- Filtering to show pending applications first

## Patient Registration and Verification

The patient registration process now includes:
- Required pet information (name and type)
- Automatic verification email dispatch on registration
- User-friendly verification page for patients to confirm their email
- Ability to request new verification emails if needed
- Clear status indicators showing verification progress

## Access Instructions

1. Admin access: `http://localhost/pawpoint/admin/login.php` (username: admin, password: admin123)
2. Doctor access: `http://localhost/pawpoint/doctor/login.php` (requires approval before login)
3. Patient access: `http://localhost/pawpoint/patient/login.php` (requires email verification)

## Email Configuration

For the email verification system to work properly, ensure your PHP mail function is properly configured:

1. For local development using XAMPP:
   - Open php.ini file (usually in xampp/php/php.ini)
   - Configure the [mail function] section with appropriate SMTP settings
   - Restart Apache server

2. For production environments:
   - Configure your server's mail settings according to your hosting provider's instructions
   - Update the send_verification_email() function in includes/functions.php if needed

## Technical Note

If you have existing doctor accounts in your database, they will be automatically set to 'approved' status when the schema update is applied. 