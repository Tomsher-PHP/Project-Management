# Project-Management

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Installation & Setup

Follow these steps to get your local development environment up and running.

---

### 1. Install Dependencies

Clone the repository and install required packages:

```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Install Activity Log package
composer require spatie/laravel-activitylog
```

### 2. Database Setup

```bash

CREATE DATABASE your_database_name;

# Run fresh migrations
php artisan migrate
php artisan db:seed

# Seed dummy/test data (optional)
php artisan db:seed DummyDataSeeder
```

### 3. Storage Setup

```bash

# Create storage system link for uploaded files
php artisan storage:link
```

### 4. Run Development Servers

```bash
# Laravel server
php artisan serve

# Vite frontend
npm run dev
```

===========================================
===========================================

### 5. Broadcast Channel Setup

```bash
php artisan install:broadcasting

# When prompted, select:
✔ Laravel Reverb
✔ Yes (install Node dependencies)

#Install Reverb dependencies (if not auto-installed)
npm install
npm run dev

#Environment Configuration
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=310655
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
VITE_REVERB_HOST=${REVERB_HOST}
VITE_REVERB_PORT=${REVERB_PORT}
VITE_REVERB_SCHEME=${REVERB_SCHEME}

#Start Reverb Server
php artisan reverb:start

#Run Queue Worker (IMPORTANT)
php artisan queue:work

npm run dev


composer require maatwebsite/excel
php artisan make:export ProjectReportExport --model=Project