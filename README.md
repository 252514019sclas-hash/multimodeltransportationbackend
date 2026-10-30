# Multi-Modal Transportation Recommendation Backend

Backend REST API service for the Multi-Modal Transportation Recommendation Android application. Built using PHP, MySQL (PDO), and Apache (XAMPP).

## Features & APIs

### 1. Authentication (`/auth/`)
- **Sign Up** (`POST auth/signup.php`): Register new users with name, email, password (hashed with `PASSWORD_DEFAULT`), and optional phone number.
- **Log In** (`POST auth/login.php`): Authenticate users with email and password, returning user session details.
- **Change Password** (`POST auth/change_password.php`): Validates the existing password and securely updates to a new password.

### 2. User Profiles (`/profile/`)
- **Fetch Profile** (`GET profile/profile_fetch.php?user_id={id}`): Fetches user personal details, contact information, bio, address, gender, and profile photo path.
- **Edit / Upsert Profile** (`POST profile/profile_edit.php`): Updates personal details including full name, phone, gender, home city/address, and bio.
- **Upload Profile Image** (`POST profile/profile_upload.php`): Handles multipart profile photo uploads (`image/*`), stores files under `upload/profile/`, and updates the profile image URL.
- **Add Profile** (`POST profile/profile_add.php`): Initialize profile record for a user.

### 3. Database
- **Schema & Tables**: `multimodel_transportation (1).sql`
  - `users`: Core account details (`user_id`, `full_name`, `email`, `password`, `phone`, `created_at`)
  - `profiles`: Extended profile info (`profile_id`, `user_id`, `gender`, `address`, `bio`, `profile_image`, `created_at`, `updated_at`)
  - `api_tokens`: Token store for API sessions
  - `user_profiles`: Preferences and transit preferences

---

## Getting Started

1. Place this project folder under your XAMPP web root:
   ```
   C:/xampp/htdocs/multimodeltrans/
   ```
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` and import `multimodel_transportation (1).sql`.
4. Verify database connection credentials in `config/config.php`:
   ```php
   $host = "localhost";
   $dbname = "multimodel transportation";
   $username = "root";
   $password = "";
   ```
5. Test the APIs locally or expose using ngrok / local IP for mobile app access.
