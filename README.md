# DeepTouch ISP-ERP

<p align="center">
  <img src="public/images/logo.png" alt="DeepTouch ISP-ERP Logo" width="160" onerror="this.style.display='none'"/>
</p>

<p align="center">
  <strong>Next-Generation Multi-Tenant ISP Operations, Billing & Network Automation Platform</strong>
</p>

<p align="center">
  <a href="#-key-features"><img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13"/></a>
  <a href="#-system-requirements"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php" alt="PHP 8.3"/></a>
  <a href="#-technology-stack"><img src="https://img.shields.io/badge/TailwindCSS-v4.0-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind CSS v4"/></a>
  <a href="#-technology-stack"><img src="https://img.shields.io/badge/MikroTik-RouterOS_v6%2Fv7-004E8A?style=flat-square&logo=mikrotik" alt="MikroTik RouterOS"/></a>
  <a href="#-technology-stack"><img src="https://img.shields.io/badge/FreeRADIUS-AAA_%26_CoA-00599C?style=flat-square" alt="FreeRADIUS"/></a>
  <a href="#-key-features"><img src="https://img.shields.io/badge/Multi--Tenant-SaaS_Architecture-0ea5e9?style=flat-square" alt="Multi-Tenant SaaS"/></a>
  <a href="#-license"><img src="https://img.shields.io/badge/License-Proprietary-red?style=flat-square" alt="License"/></a>
</p>

---

## 📖 Overview

**DeepTouch ISP-ERP** is an enterprise-grade, multi-tenant ISP Enterprise Resource Planning (ERP), automated billing, and network operations platform built for modern Internet Service Providers (ISPs), Sub-ISPs / Reseller Partners, and SaaS Operators.

Powered by **Laravel 13**, **PHP 8.3**, and **Tailwind CSS v4**, DeepTouch ISP-ERP integrates directly with **MikroTik RouterOS (v6 & v7 API)**, **FreeRADIUS / NAS**, **GPON / EPON OLTs**, and leading payment gateways (**bKash**, **Nagad**, **SSLCommerz**, **Stripe**). It automates the entire subscriber lifecycle—from provisioning and dynamic bandwidth queues to automated monthly invoicing, billing reminders, auto-cut/disconnection enforcement, and BTRC regulatory compliance reporting.

---

## 🌟 Key Features

### 🏢 1. Multi-Tenant SaaS Architecture
- **Platform Owner (Super Admin) Portal:** Oversee all ISP tenants, manage tiered SaaS subscription plans (starter, pro, enterprise), monitor platform earnings, global SMS & mail gateways, and audit logs.
- **Tenant Portal (ISP Admin & Staff):** Dedicated environment for ISP operators to handle subscribers, network hardware, financial ledgers, billing cycles, field technicians, and cash collectors.
- **Reseller / Sub-ISP Portal:** Dedicated sub-partner interface with allocated wholesale bandwidth, sub-customer provisioning, prepaid wallet balance, and self-managed recharge lifecycles.
- **Customer Self-Service Portal:** Client access to view active subscriptions, download invoices, make instant online payments, and open support tickets.

### 🌐 2. Network Automation & Telemetry
- **MikroTik RouterOS Integration (v6 & v7):**
  - Instant PPP Secret creation, update, and profile assignment via API.
  - Automatic comment tagging using unique customer IDs (`[PREFIX] + [ID]`).
  - Real-time active session monitoring, one-click session disconnect/kick, and CoA socket integration.
  - Live interface traffic telemetry, IP pool allocation, and firewall address-list management.
- **FreeRADIUS & NAS Engine:** RADIUS authentication, accounting (`radacct`), and dynamic Change of Authorization (CoA / PoD on port 3799).
- **OLT & ONU Fleet Management:**
  - Support for GPON & EPON OLTs via CLI/API.
  - Automatic ONU discovery, optical power level telemetry (RX/TX dBm), and PON port status tracking.
- **IP Pool & VLAN Orchestration:** Static and dynamic IPv4 pools, subnet planning, and VLAN tag assignments.
- **Network Map & Topology:** Visual coverage zone mapping and distribution pop management.

### 💳 3. Automated Billing & Financial Management
- **Intelligent Billing Engine:**
  - Configurable recurring billing cycles (calendar month, anniversary date, prepaid, or postpaid).
  - Dynamic invoice generation with tax, discounts, and itemized bandwidth charges.
  - Automatic grace period tracking and dynamic service suspension on MikroTik routers upon non-payment.
  - Dynamic multi-currency formatting (`@currency` blade directive supporting BDT ৳, USD $, EUR €, GBP £, etc.).
- **Online Payment Gateways:**
  - **bKash:** Tokenized Checkout & direct PGW webhooks.
  - **Nagad:** Integrated checkout API and instant verification.
  - **SSLCommerz:** Multi-card, internet banking, and mobile banking aggregator.
  - **Stripe:** International credit/debit card processing.
- **Field Collection & Daily Cash Handover:**
  - Mobile-responsive billing interface for field collection agents.
  - Instant collection receipts and customer SMS notifications.
  - Daily Cash Handover reconciliation workflow with manager approval.
- **Upstream Bandwidth & Wholesale Accounting:** Track upstream providers, link capacity, monthly bandwidth invoices, and payment disbursements.
- **General Ledger & Expense Tracking:** Multi-category expense tracking, cash accounts, and profit/loss statements.

### 🤝 4. Reseller / Sub-ISP Partner System
- **Prepaid Reseller Wallet:** Resellers operate on wallet balance deducted dynamically based on customer renewals.
- **Bandwidth Plan Allocation:** Wholesale bandwidth provisioning (Mbps/Gbps allocation or per-customer commission tiers).
- **Recharge Workflows:** Resellers submit wallet top-up requests with manual/digital payment verification.
- **Escalation Support:** Resellers can escalate customer complaints directly to upstream ISP engineers.

### 🎫 5. Helpdesk, Field Ops & SLA Tracking
- **Support Ticket Desk:** Departmental ticket routing, priority levels, attachments, and customer communication history.
- **Field Technicians & Installation Tasks:** Assign installation tasks and optical line maintenance jobs with technician status logs.
- **SLA Analytics:** Mean-time-to-repair (MTTR), resolution performance metrics, and ticket escalation management.

### 📊 6. Reports & Regulatory Compliance
- **BTRC Compliance Reporting:** Standardized logs formatted for the Bangladesh Telecommunication Regulatory Commission (BTRC), including subscriber identification, IP assignment history, and bandwidth tier logs.
- **MRTG & Traffic Reports:** Live and historical bandwidth utilization telemetry.
- **Revenue & Collection Ledgers:** Daily, monthly, and yearly collection statements with filterable breakdown by zone, collector, and payment method.

### 🎨 7. Ultra-Fast High-Density UI/UX
- **`.saas-table` Pure CSS Architecture:** Clean, high-density, zero-wrap data tables with zebra striping and hover protection.
- **6-Card KPI Summary Strips:** Standardized top-level metrics on all primary module indexes.
- **In-Memory Client-Side Table Sorting:** Zero-page-reload fast sorting for currency, numbers, text, and status states.
- **Floating 3-Dot Action Menus:** Collision-aware popup menus positioned accurately using viewport boundary detection.
- **Soft Natural Modals:** Clean, modern modal components without cluttered interfaces.
- **Bilingual Multi-Language Support:** Fully localized in **Bengali (বাংলা)** and **English** with runtime switching.

---

## 🏛️ Technology Stack

| Component | Technology | Description |
|---|---|---|
| **Backend Framework** | Laravel 13.x | Latest stable enterprise PHP framework |
| **Runtime** | PHP 8.3+ | Fast, typed, modern PHP execution |
| **Database** | MySQL 8.0+ / MariaDB 10.11+ | Relational data persistence with strict tenant scopes |
| **Frontend Styling** | Tailwind CSS v4.0 | Utility-first, compile-free high performance styling |
| **Frontend Interactivity**| Blade + Alpine.js | Reactive, lightweight, server-driven UI |
| **Icons & Assets** | FontAwesome 6 | Enterprise vector icon set |
| **Network Protocols** | RouterOS API, RADIUS, Telnet/SSH | Hardware orchestration with MikroTik, Cisco, OLTs |
| **Build Tools** | Vite 8.0 | Next-generation asset compilation |
| **Web Server** | Nginx / Apache + PHP-FPM | Production reverse proxy & execution engine |

---

## 💻 System Requirements

- **Operating System:** Ubuntu 22.04 LTS / 24.04 LTS, Debian 12, or AlmaLinux 9
- **PHP:** `8.3` (or `8.2+`)
  - Required Extensions: `bcmath`, `curl`, `fpm`, `gd`, `intl`, `mbstring`, `mysql`, `openssl`, `pdo_mysql`, `redis`, `sockets`, `xml`, `zip`
- **Database:** MySQL 8.0+ or MariaDB 10.11+
- **Node.js:** v20.x or v22.x LTS with NPM
- **Composer:** v2.7+
- **Network Access:** TCP port `8728` / `8729` (MikroTik API), UDP port `1812` / `1813` (RADIUS), UDP `3799` (CoA)

---

## ⚡ Installation & Quick Start

### 1. Clone Repository & Install Dependencies

```bash
# Clone the repository
git clone https://github.com/deeptouchit/deeptouchit-isp-erp.git /var/www/isp-erp
cd /var/www/isp-erp

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install NPM dependencies
npm install
```

### 2. Configure Environment

```bash
# Copy example configuration
cp .env.example .env

# Generate application key
php artisan key:generate
```

Configure your `.env` file with your database, cache, and mail settings:

```env
APP_NAME="DeepTouch ISP-ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-isp-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=isp_erp_db
DB_USERNAME=isp_user
DB_PASSWORD=your_secure_password

CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

### 3. Database Migration & Initial Seeding

```bash
# Run database migrations
php artisan migrate --force

# Seed master platform owner, plans, and demo ISP tenant
php artisan db:seed --force
```

#### 🔑 Default Seeded Accounts

| Role | Email | Password | Access Portal |
|---|---|---|---|
| **Platform Owner (Super Admin)** | `owner@somitysoft.com` | `password` | `/owner/login` |
| **Demo ISP Admin (SpeedNet Online)** | `admin@speednet.com` | `password` | `/admin/login` |

> [!IMPORTANT]
> Change the default passwords immediately upon initial login in production!

### 4. Build Frontend Assets

```bash
npm run build
```

### 5. Storage Linking & Directory Permissions

```bash
php artisan storage:link

# Set file permissions for web server
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## ⏰ Background Scheduling & Automation

DeepTouch ISP-ERP relies on Laravel's master scheduler for automated billing, MikroTik synchronization, and reminder broadcasts.

### Crontab Setup

Add the following single entry to your server's crontab (`sudo crontab -e -u www-data`):

```bash
* * * * * cd /var/www/isp-erp && php artisan schedule:run >> /dev/null 2>&1
```

### Automated Schedule Breakdown

| Interval | Command | Purpose |
|---|---|---|
| **Every Minute** | `php artisan system:heartbeat` | Live scheduler telemetry and heartbeat monitoring |
| **Every Minute** | `php artisan isp:process-subscribers --mode=auto` | Dynamic UI-driven subscriber scheduler (billing, auto-cut, reminders) |
| **Every 5 Minutes** | `php artisan network:sync-olts` | Core OLT fleet & ONU optical diagnostic telemetry sync |
| **Daily at 00:05** | `php artisan billing:process-subscriptions --triggered-by=cron` | Evaluates SaaS renewals, invoices, wallet settlement, and grace periods |
| **Daily at 09:00** | `php artisan billing:process-subscriptions --triggered-by=cron_reminder` | Multi-channel SMS/Email reminder broadcasts for pending bills |
| **Weekly** | `php artisan queue:prune-failed --hours=168` | Housekeeping of failed jobs and temporary logs |

---

## 🛠️ Key CLI & Artisan Commands

```bash
# Process ISP subscriber automation manually (modes: auto, midnight, reminder, autocut, billing, all)
php artisan isp:process-subscribers --mode=all --tenant=1

# Trigger SaaS tenant subscription renewal and invoicing
php artisan billing:process-subscriptions

# Synchronize all or specific OLT devices
php artisan network:sync-olts --olt=1

# Run diagnostic backup restore test
php artisan backup:test-restore

# Clear and optimize application caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚀 Deployment & Webhooks

### Automated Script Deployment

The repository includes a ready-to-use zero-downtime deployment script:

```bash
./deploy.sh
```

### GitHub Auto-Deployment Webhook

Deployments can be triggered automatically upon pushing to the `main` branch:

- **Webhook URL:** `https://your-domain.com/webhook/github-deploy` or `/github/webhook`
- **Method:** `POST`
- **Content type:** `application/json`

---

## 🔒 Security & Data Integrity

- **Tenant Isolation:** Enforced via `BelongsToTenant` scope on all tenant-specific Eloquent models.
- **Hardware Vault Encryption:** RouterOS credentials, API keys, and FreeRADIUS shared secrets are encrypted using Laravel's AES-256 encryption.
- **Audit Trails:** Comprehensive logging of network operations (`NetworkAuditLog`), security events (`SecurityAuditLog`), and administrative actions.
- **Rate Limiting:** Protects API endpoints, authentication routes, and payment webhook receivers against brute-force attacks.

---

## 🌐 Localization & Language Support

DeepTouch ISP-ERP provides full bilingual support:
- **English** (`en`)
- **Bengali (বাংলা)** (`bn`)

Switch language anytime via `/locale/{lang}` or the navbar switcher. All translation dictionaries are maintained in:
- `lang/en.json`
- `lang/bn.json`

---

## 📄 License & Intellectual Property

This software is proprietary software developed by **DeepTouch IT**. All rights reserved. Unauthorized copying, modification, distribution, or deployment without explicit written permission is strictly prohibited.

For commercial licensing, custom OLT driver development, or integration inquiries, please contact:
- **Website:** [https://deeptouchit.com](https://deeptouchit.com)
- **Support Email:** `support@deeptouchit.com`
