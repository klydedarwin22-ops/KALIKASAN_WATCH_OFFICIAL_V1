# 🌿 KALIKASAN WATCH

**A Community-Driven Environmental Reporting Platform for the Municipality of Aparri, Cagayan**

KALIKASAN WATCH empowers citizens to report environmental issues in their barangay — from illegal dumping to water pollution — and enables local officers and administrators to investigate, track, and resolve them. All reports are geolocated on an interactive Google Map for full community transparency.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [Running the App](#-running-the-app)
- [Default Accounts](#-default-accounts)
- [Project Structure](#-project-structure)
- [User Roles](#-user-roles)
- [Report Categories](#-report-categories)
- [Screenshots](#-screenshots)
- [License](#-license)

---

## ✨ Features

- **Role-Based Access Control** — Three user roles: **Citizen**, **Officer**, and **Admin**, each with distinct permissions.
- **Environmental Report CRUD** — Citizens can create, view, edit, and delete their own reports with title, description, category, severity, geolocation, and photo upload.
- **Interactive Google Maps** — Full satellite/hybrid map view showing all reports as color-coded markers. Draggable pin for precise location selection when creating/editing reports.
- **Report Lifecycle Management** — Officers and admins can update report status (`Pending` → `Investigating` → `Resolved` / `Rejected`) and assign reports to officers.
- **Completion Proof Tracking** — Officers can attach a single proof photo when marking a report complete, replace it with a new image, or remove an incorrect one before saving.
- **Comment System** — Citizens and admins can leave comments on reports for discussion and follow-up updates; officers are restricted from posting comments.
- **Barangay Verification System** — Citizens can upload a barangay ID or certificate of residency from their dashboard after registration. Officers/admins review the privately stored document and approve or reject verification requests; citizens can resubmit after rejection.
- **Role-Aware Dashboards** — Citizens see their own report stats; officers see assigned workload; admins see platform-wide analytics.
- **Officer Presence** — Officers are shown as online after logging in and offline after logging out.
- **Filtering & Pagination** — Reports list supports filtering by category, status, severity, and barangay.
- **Authorization Policies** — Fine-grained policy layer ensuring users can only access what their role permits.
- **Responsive Green/Eco UI** — Nature-inspired design with TailwindCSS, fully responsive across devices.

---

## 🛠 Tech Stack

| Layer        | Technology                          |
| ------------ | ----------------------------------- |
| **Backend**  | PHP 8.2+, Laravel 12                |
| **Auth**     | Laravel Breeze (Blade stack)        |
| **Frontend** | Blade Templates, TailwindCSS v4, Alpine.js |
| **Maps**     | Google Maps JavaScript API          |
| **Build**    | Vite 7                              |
| **Database** | MySQL                               |
| **Server**   | XAMPP (Apache + MySQL)              |

---

## 📦 Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18 & npm
- MySQL 5.7+ or MariaDB 10.3+
- XAMPP (or any Apache/MySQL environment)
- Google Maps API Key (with Maps JavaScript API enabled)

---

## 🚀 Installation

### 1. Clone the repository

```bash
cd C:\xampp\htdocs
git clone <repository-url> KALIKASAN_WATCH
cd KALIKASAN_WATCH
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node.js dependencies

```bash
npm install
```

### 4. Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

---

## ⚙ Configuration

Edit the `.env` file with your local settings:

```env
APP_NAME="KALIKASAN WATCH"
APP_URL=http://localhost/KALIKASAN_WATCH/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kalikasan_watch
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_MAPS_API_KEY=your-google-maps-api-key-here
FACE_RECOGNITION_NODE_BINARY=node
```

> **Note:** You need a valid [Google Maps API Key](https://developers.google.com/maps/documentation/javascript/get-api-key) with the **Maps JavaScript API** enabled for the map features to work.

### Citizen face login

Citizens must have an approved National ID before enrolling in face login. After approval, they enroll from the prompted camera flow; subsequent password logins require the randomized camera challenge. Face templates are encrypted using Laravel's `APP_KEY`, and uploaded challenge frames are processed locally by the server and are not retained. Camera access requires HTTPS or localhost. If Node.js is not on the PHP process `PATH`, set `FACE_RECOGNITION_NODE_BINARY` to the Node executable path.

Face matching is a biometric check and may produce false matches or rejections; its current similarity and liveness thresholds have not been validated for production identity assurance. Keep the staff-assisted reset available and evaluate with representative devices and users before relying on it for high-impact decisions.

---

## 🗄 Database Setup

### 1. Create the database

Open phpMyAdmin (http://localhost/phpmyadmin) and create a database called `kalikasan_watch`.

### 2. Run migrations

```bash
php artisan migrate
```

### 3. Seed default data

```bash
php artisan db:seed
```

### 4. Create storage symlink

```bash
php artisan storage:link
```

---

## ▶ Running the App

Start the Vite development server for frontend assets:

```bash
npm run dev
```

Then visit: **http://localhost/KALIKASAN_WATCH/public**

For production, build assets instead:

```bash
npm run build
```

---

## 👤 Default Accounts

After running `php artisan db:seed`, the following accounts are available:

| Role      | Email                        | Password   | Barangay        |
| --------- | ---------------------------- | ---------- | --------------- |
| **Admin** | admin@kalikasanwatch.ph      | `password` | Centro 1 (Pob.) |
| **Officer** | ronnie@kalikasanwatch.ph   | `password` | Linao           |
| **Officer** | kris@kalikasanwatch.ph     | `password` | Sanja           |
| **Citizen** | roberto@kalikasanwatch.ph  | `password` | Bangag          |
| **Citizen** | garcia@kalikasanwatch.ph   | `password` | Maura           |
| **Citizen** | citizen3@kalikasanwatch.ph | `password` | Gaddang         |

---

## 🗂 Project Structure

```
KALIKASAN_WATCH/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php    # Role-aware dashboard
│   │   │   ├── MapController.php          # Public interactive map
│   │   │   ├── ReportController.php       # Full report CRUD + actions
│   │   │   └── VerificationController.php # National ID verification
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php         # Role-based route guard
│   │   └── Requests/
│   │       ├── StoreReportRequest.php
│   │       ├── UpdateReportRequest.php
│   │       └── StoreCommentRequest.php
│   ├── Models/
│   │   ├── User.php                       # Roles, verification, relationships
│   │   ├── Report.php                     # Categories, statuses, severities
│   │   └── Comment.php
│   ├── Policies/
│   │   └── ReportPolicy.php              # Authorization rules
│   └── Services/
│       ├── DashboardService.php          # Stats aggregation
│       └── ReportService.php             # Business logic layer
├── database/
│   ├── migrations/                       # Users, reports, comments, verification
│   └── seeders/
│       └── DatabaseSeeder.php            # Demo data with all roles
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php                 # Main layout (Google Maps, nav)
│   │   ├── guest.blade.php               # Auth pages layout
│   │   └── navigation.blade.php          # Role-aware navbar
│   ├── auth/
│   │   └── register.blade.php            # Registration + barangay ID upload
│   ├── dashboard.blade.php               # Role-aware stats dashboard
│   ├── map/
│   │   └── index.blade.php               # Public Google Maps view
│   ├── reports/
│   │   ├── index.blade.php               # Filterable reports list
│   │   ├── create.blade.php              # Report form + map picker
│   │   ├── show.blade.php                # Report detail + comments
│   │   └── edit.blade.php                # Edit form + map picker
│   ├── verification/
│   │   ├── index.blade.php               # Pending verifications list
│   │   └── show.blade.php                # Review & approve/reject
│   └── welcome.blade.php                 # Public landing page
├── routes/
│   └── web.php                           # All application routes
└── config/
    └── services.php                      # Google Maps API key config
```

---

## 🔐 User Roles

### 🧑 Citizen
- Register with barangay selection and optional ID upload
- Create, view, edit, and delete their own environmental reports
- Pin report location on Google Maps
- Upload photo evidence
- Comment on any report
- View personal report statistics on the dashboard

### 👮 Officer
- View all reports across the platform
- Update report status (Pending → Investigating → Resolved / Rejected)
- Get assigned to reports by admins
- Attach a single completion proof photo when a task is finished
- Replace or remove a wrong completion proof photo before finalizing the update
- Review and approve/reject barangay verification requests
- View assigned workload stats on the dashboard

### 🛡 Admin
- Full access to all reports and users
- Assign reports to specific officers
- Update any report's status
- Comment on reports for follow-up and coordination
- Approve/reject barangay verifications
- View platform-wide analytics on the dashboard
- Delete any report

---

## 📂 Report Categories

| Key                | Label            |
| -----------------  | ---------------- |
| `illegal_dumping`  | Illegal Dumping  |
| `water_pollution`  | Water Pollution  |
| `air_pollution`    | Air Pollution    |
| `deforestation`    | Deforestation    |
| `illegal_fishing`  | Illegal Fishing  |
| `flooding`         | Flooding         |
| `soil_erosion`     | Soil Erosion     |
| `wildlife_threat`  | Wildlife Threat  |
| `noise_pollution`  | Noise Pollution  |
| `other`            | Other            |

**Severity Levels:** `Low` · `Medium` · `High`

**Report Statuses:** `Pending` → `Investigating` → `Resolved` / `Rejected`

---

## 📸 Screenshots

> _Screenshots coming soon._

---

## 📄 License

This project is built with the [Laravel](https://laravel.com) framework and is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
