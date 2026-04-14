# Pharmacy Internship Management System

A full-stack prototype for Pharmacy Internship Management using HTML, CSS, JavaScript, PHP, and MySQL.

## Project Overview

This system supports:
- Email/password registration and login
- Google OAuth 2.0 login flow
- Session-based authentication with PHP
- Role-based dashboards for Intern, HR Personnel, and Pharmacist
- Requirement and policy management for HR
- Intern document submission, review, and approval workflow
- Completion tracking and reports for Pharmacists

## Features

- Register as Intern, HR Personnel, or Pharmacist
- Login with email/password or Google
- Role-based redirection after login
- Intern upload checklist with status tracking
- HR management of required documents and pharmacy policies
- HR approval/rejection workflow with remarks
- Pharmacist intern monitoring and completion reports
- JSON-based backend API endpoints

## Installation

1. Install XAMPP or another PHP/MySQL server.
2. Place the project folder in `htdocs` (for XAMPP):
   - `C:\xampp\htdocs\Pharmacy intership system`
3. Start Apache and MySQL from the XAMPP control panel.
4. Create the database:
   - Open `http://localhost/phpmyadmin`
   - Run the SQL statements from `schema.sql`
5. Update database credentials in `config.php` if needed.

## Google OAuth Setup

1. Create a Google Cloud project at https://console.cloud.google.com.
2. Configure OAuth consent screen and add a Web application credential.
3. Add `http://localhost/Pharmacy intership system/google_callback.php` as an authorized redirect URI.
4. Copy the Client ID and Client Secret into `config.php`:
   - `GOOGLE_CLIENT_ID`
   - `GOOGLE_CLIENT_SECRET`
5. Save the file and restart Apache if required.

## Usage

### Intern

- Login with email/password or Google.
- Upload required documents for each checklist item.
- Review status indicators for Uploaded, Pending, Approved, or Rejected.
- View pharmacy policies and guidelines.

### HR Personnel

- Manage internship requirement templates.
- Create and edit pharmacy policies by category.
- Review intern submissions, approve or reject documents, and leave remarks.
- Monitor total interns, pending submissions, and completed interns.

### Pharmacist

- View intern progress and completion status.
- See reports for interns with approved document counts and completion percentage.

## Notes

- This project is a functional prototype intended for demo or hackathon use.
- File uploads are stored in `uploads/`.
- Use modern browsers for best dashboard experience.
- For quick testing, create accounts using the registration page and upload sample PDFs or images.

## File Structure

- `index.php` — Login page
- `register.php` — Registration page
- `google_login.php` / `google_callback.php` — Google sign-in flow
- `dashboard_intern.php` — Intern dashboard
- `dashboard_hr.php` — HR dashboard
- `dashboard_pharmacist.php` — Pharmacist dashboard
- `api/` — Backend REST-style endpoints
- `assets/css/style.css` — UI styles
- `assets/js/app.js` — Dashboard behavior
- `schema.sql` — Database schema and seed data
