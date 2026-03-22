# Cabo Multibus - Documentation

This directory contains documentation, examples, and reference materials for the Cabo Multibus project.

## Contents

### Implementation & Architecture
- **[ROUTER_GUIDE.md](./ROUTER_GUIDE.md)** - Complete guide to the Router-based architecture
- **[IMPLEMENTATION_CHECKLIST.md](./IMPLEMENTATION_CHECKLIST.md)** - Feature checklist and implementation status

### Examples
- **[example-protected-page.php](./example-protected-page.php)** - Example of JWT-protected page implementation
  - Shows how to use `requireAdminAuth()` middleware
  - Useful reference for creating additional protected pages

## Project Structure Overview

```
/
├── admin.php                 # Admin dashboard & AJAX router
├── api.php                   # REST API endpoints
├── login.php                 # User authentication
├── logout.php                # Session termination
├── Router.php                # Core routing engine
│
├── /admin/                   # Admin module
│   ├── init.php             # Admin initialization
│   ├── ajax.php             # AJAX action dispatcher
│   └── /ajax/               # AJAX handlers (20+ operations)
│
├── /config/                  # Configuration files
│   ├── db.php               # Database connection
│   ├── auth_config.php      # JWT & auth settings
│   └── pdo_compat.php       # PDO compatibility
│
├── /middleware/              # Middleware
│   └── auth.php             # Authentication middleware
│
├── /includes/                # UI Components (13 files)
│   ├── bookings.php
│   ├── customers.php
│   ├── schedules.php
│   └── ... (more components)
│
├── /helpers/                 # Helper functions
│   └── api-response.php      # API response utilities
│
├── /assets/                  # Static assets
│   ├── css/                 # Stylesheets
│   └── ...
│
└── /docs/                    # Documentation (this folder)
    ├── README.md
    ├── ROUTER_GUIDE.md
    ├── IMPLEMENTATION_CHECKLIST.md
    └── example-protected-page.php
```

## Key Features

### Router-Based Architecture
- Centralized routing for both REST API and Admin AJAX
- Clean action-based URL structure
- Supports GET, POST, and ANY method routes

### Active Modules
- **API Module** (44+ endpoints) - Public REST API for bookings
- **Admin Module** (30+ AJAX actions) - Dashboard & management
- **Auth Module** - JWT-based authentication
- **Database** - PostgreSQL with PDO

## Active Files Count
- PHP files: 44+
- Config files: 3
- AJAX handlers: 20+
- UI Components: 13
- Stylesheets: 5

## Cleanup Notes

The following files have been removed (see git history):
- Backup files: `admin.php.backup`, `api.php.backup`
- Test artifacts: `admin.php.new`, `admin_setup.txt`, `debug_actions.log`
- Duplicate config: `.env copy.example`
- Reference examples: `example-api-with-router.php`, `api-refactored.php`

**Result:** Cleaner project structure, reduced clutter, improved maintainability.
