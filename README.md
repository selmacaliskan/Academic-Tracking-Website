# Academic Tracker — Project & Task Management System

Academic Tracker is a robust PHP-based web application designed for students and researchers to organize academic projects, track tasks with dynamic status updates, and manage project-related files in a secure, centralized environment.

## 🚀 Key Features

* **Secure Authentication**: Comprehensive registration and login system featuring password hashing (Bcrypt) for data security.
* **Persistent Sessions**: "Remember Me" functionality utilizing secure cookie tokens for seamless user access.
* **Project Management**: Full CRUD (Create, Read, Update, Delete) operations for managing academic initiatives.
* **Task Tracking System**: Assign tasks to projects with real-time status management (Pending, In Progress, Completed).
* **File Management**: Integrated module for securely uploading and downloading project documents and assets.
* **UI/UX Customization**: Built-in support for **Light** and **Dark** themes, allowing users to switch based on preference.
* **User Profiles**: Self-service profile management, including information updates and secure password changes.

## 🛠️ Technical Stack

* **Language**: PHP 7.4+
* **Database**: MySQL 5.7+ / MariaDB
* **Environment**: Optimized for Apache/Nginx (XAMPP, WAMP, or LAMP stacks)
* **Security**: PDO for database interactions to prevent SQL injection; password hashing for credential safety.

## 📂 Project Architecture

```text
AcademicTracking/
├── akademik_takip.sql        # Database schema and constraints
├── index.php                 # Root entry point with auto-redirect
├── includes/                 # Core backend logic
│   ├── db.php                # Database connection settings
│   ├── auth.php              # Session and authentication management
│   └── navbar.php            # Shared navigation component
├── system/                     # Frontend application pages
│   ├── style.css             # Global application styling
│   ├── login.php             # User authentication (Login)
│   ├── register.php          # Account creation
│   ├── projects.php          # Project management dashboard
│   ├── tasks.php             # Task tracking and status updates
│   ├── files.php             # File upload and management
│   └── profile.php           # User settings and theme toggling
└── uploads/                  # Directory for user-uploaded documents

## 📦 Installation & Setup

### 1. Database Initialization
Create a database and import the provided SQL schema using your terminal or a GUI tool like phpMyAdmin:

```bash
mysql -u root -p < akademik_takip.sql
