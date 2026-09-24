# DeepTouchHost Control Panel

A modern, production-ready web hosting control panel built with **Laravel 11**, **Vue.js 3**, **Inertia.js**, and **Tailwind CSS** on **Ubuntu 24.04 LTS**.

---

## 🏛️ Architecture Overview

- **Backend:** Laravel 11 (PHP 8.3 / 8.2), MySQL 8.0, Redis 7.0
- **Frontend:** Vue.js 3, Inertia.js, Tailwind CSS, Heroicons, Chart.js
- **Queues & Real-time:** Redis Queue with Laravel Horizon, Laravel Reverb (WebSocket)
- **Web & Engine:** Nginx + PHP-FPM (multi-version pool), Certbot (Let's Encrypt SSL)
- **Security:** UFW Firewall, Sudoers wrapper with NOPASSWD isolation, Rate Limiting, 2FA

---

## 💻 System Requirements

- **OS:** Ubuntu 24.04 LTS (Minimal)
- **PHP:** 8.2 or 8.3 with extensions (`fpm`, `cli`, `mysql`, `redis`, `zip`, `gd`, `mbstring`, `curl`, `xml`, `bcmath`)
- **Database:** MySQL 8.0 / MariaDB 10.11+
- **Cache / Queue:** Redis 7.0+
- **Node.js:** v20.x or v22.x with NPM
- **Web Server:** Nginx 1.24+

---

## ⚡ Installation & Setup

1. **Clone Repository & Install Dependencies:**
   ```bash
   git clone git@github.com:your-org/deeptouchhost-panel.git /var/www/deeptouchhost
   cd /var/www/deeptouchhost
   composer install --no-interaction
   npm install
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
   *Default Admin Credentials:*
   - **Email:** `admin@deeptouchhost.com`
   - **Password:** `password`

4. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

5. **Start Background Queue (Horizon):**
   ```bash
   php artisan horizon
   ```

---

## 🛠️ CLI & Artisan Commands

```bash
# Create a new hosting account
php artisan hosting:create client@example.com starter mydomain.com --php=8.2 --period=monthly

# Routine Maintenance (SSL renewal, Suspensions, Invoicing)
php artisan hosting:maintenance --renew-ssl --suspend-expired --generate-invoices

# Monitor Server Health Metrics
php artisan server:monitor

# Run Spatie Backup & Check List
php artisan backup:run
php artisan backup:list
```

---

## 🚀 Deployment (Zero-Downtime)

Use the built-in deployment script for instant releases:
```bash
./deploy.sh
```

---

## 🔒 Security Features

- **RBAC & 2FA:** Spatie Permission (Admin, Reseller, Client) & Two-Factor Authentication.
- **Rate Limiting:** Login (`5 req/min`), Payment (`3 req/5min`), API (`60 req/min`), Admin (`100 req/min`).
- **Isolation:** Dedicated Linux user per domain under `/var/www/vhosts/{username}/{domain}/public_html`.
- **Hardening:** Strict Nginx security headers (`SAMEORIGIN`, `nosniff`, Modern TLS ciphers) and PHP-FPM function restrictions.

---

## 📦 Backup & Retention

- Automated daily backups at **01:00 AM** using Spatie Backup.
- Local destination: `/var/www/backups`
- Cloud destination: Amazon AWS S3
- 30-day retention cleanup strategy with Gzip compression.

---

## 📈 Monitoring & Health Checks

- Live server CPU, RAM, Disk, and Load average metrics gathered via `/usr/local/panel-scripts/server-monitor.sh`.
- Real-time Dashboard in Vue.js with instant status feeds.
