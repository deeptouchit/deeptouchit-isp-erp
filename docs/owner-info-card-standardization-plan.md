# Owner Panel Global Info Card Standardization Plan

## লক্ষ্য

`/owner` প্যানেলের সকল পেজে একই production-grade, responsive এবং reusable Info/KPI Card system চালু করা।

কার্ডের size, padding, icon, label, value, spacing, typography, color এবং alignment সব পেজে consistent থাকবে।

## Phase 1: Audit ও Inventory

### Task 1.1 — Owner page inventory `[x]`

- Dashboard
- SaaS Plans
- ISP Tenants
- Tenant Wallets
- User Management
- Fleet Overview
- Network Audit Trail
- Invoices & Billing
- Subscriptions
- Settled Payments
- Gateway Transactions
- Support Tickets
- Payment Gateways
- SMS Gateways
- Mail & SMTP
- Revenue & Income
- Activity & Audit Logs
- SMS Logs & Ledger
- Automation & Cron
- Security & Backup
- Platform Settings

**Verification:** ২১টি নির্ধারিত Owner view file পাওয়া গেছে এবং inventory সম্পূর্ণ।

### Task 1.2 — বর্তমান card markup audit `[x]`

প্রতিটি পেজে বর্তমান card সংখ্যা, CSS class, grid, icon size, typography, padding, responsive behavior এবং duplicate style নথিভুক্ত করা হয়েছে।

**Audit findings:** বর্তমানে `grid-cols-2`, `sm:grid-cols-4`, `sm:grid-cols-5`, `md:grid-cols-6`, `md:grid-cols-8` এবং পৃথক card padding/font-size ব্যবহৃত হচ্ছে। `.owner-kpi-*` shared class কোনো view-তে ব্যবহার হচ্ছে না।

### Task 1.3 — KPI data mapping `[x]`

প্রতিটি কার্ডের title, metric value, unit/currency, icon, theme color, helper text এবং empty state-এর source নির্ধারণ করা হয়েছে।

**Mapping summary:** Dashboard, Billing, Tenants, Wallets, Users, Payments, Subscriptions, Transactions, Gateways, Mail, SMS, Tickets, Network, Reports এবং Settings-এর data controller/view variables থেকে আসছে। Financial metrics-এ dynamic currency এবং count/status metrics-এ numeric/status formatting ব্যবহার করা হবে।

## Phase 2: Global Design System

### Task 2.1 — Shared CSS classes `[x]`

`resources/css/app.css`-এ reusable class যোগ করা হয়েছে:

- `.owner-kpi-grid`
- `.owner-kpi-card`
- `.owner-kpi-icon`
- `.owner-kpi-label`
- `.owner-kpi-value`
- `.owner-kpi-meta`
- `.owner-kpi-trend`

**Verification:** Vite production build, Blade cache এবং `git diff --check` সফল।

### Task 2.2 — Standard specification `[x]`

- Grid: `grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6`
- Card: `px-2.5 py-2 rounded-xl border bg-white shadow-xs`
- Icon: `w-7 h-7 rounded-lg`
- Label: `text-[9px] uppercase tracking-wider font-semibold`
- Value: `text-[15px] font-bold font-mono leading-tight`
- Card height: natural consistent minimum height
- Mobile layout: horizontal overflow ছাড়া responsive layout

**Verification:** Global KPI classes-এ grid breakpoint, card height, padding, icon size, label/value typography এবং overflow protection সব standard অনুযায়ী আছে।

### Task 2.3 — Theme variants `[x]`

Blue, Emerald, Amber, Rose, Purple, Cyan এবং Slate theme variant যুক্ত করা হয়েছে।

**Verification:** সাতটি reusable icon theme class যুক্ত হয়েছে এবং Vite production build ও Blade cache সফল।

## Phase 3: Shared Blade Component

### Task 3.1 — Component তৈরি `[x]`

ফাইল:

`resources/views/components/owner/kpi-card.blade.php` তৈরি করা হয়েছে।

সম্ভাব্য props:

- `title`
- `value`
- `icon`
- `theme`
- `meta`
- `href`
- `align`

**Verification:** Component theme validation, optional link/meta support এবং Blade cache সফল।

### Task 3.2 — Standard usage `[x]`

```blade
<x-owner.kpi-card
    title="Total Tenants"
    :value="$totalTenants"
    icon="fa-building"
    theme="blue"
/>
```

**Verification:** Core, billing, gateway, network, report, log, automation, security এবং communication owner views-এর KPI cards shared `<x-owner.kpi-card>` component ব্যবহার করছে। Blade cache, Vite production build এবং `git diff --check` সফল।

### Task 3.3 — Accessibility `[x]`

- অর্থপূর্ণ label
- যথাযথ color contrast
- icon-এ `aria-hidden`
- clickable card-এ keyboard support
- প্রয়োজনীয় tooltip বা accessible text

**Verification:** Component icon-এ `aria-hidden`, clickable card-এ `aria-label`, keyboard focus ring এবং native anchor keyboard support যুক্ত আছে। Blade cache ও production build সফল।

## Phase 4: Owner Page Migration

### Priority 1 — Core pages

- Dashboard
- Billing
- Tenants
- Wallets
- Users
- Payments
- Subscriptions

### Task 4.1 — Core page final consistency review `[x]`

Core Owner pages-এর shared KPI component usage, global grid এবং build/cache consistency যাচাই করা হয়েছে।

**Verification:** সব core page-এ অন্তত একটি `<x-owner.kpi-card>` আছে; Blade cache, Vite production build এবং `git diff --check` সফল।

### Priority 2 — Gateway ও communication pages

- Payment Gateways
- SMS Gateways
- Mail Settings
- Transactions
- SMS Logs

### Priority 3 — Network ও reports

- Network Engines
- Network Audit Logs
- Revenue Report
- Activity Logs
- Tickets

### Priority 4 — Settings

- Plans
- Automation
- Security & Backup
- Platform Settings

প্রতিটি পেজে পুরোনো card markup সরিয়ে shared component ব্যবহার করা হবে, duplicate metric বাদ দেওয়া হবে এবং card order standardize করা হবে।

## Phase 5: Data ও Financial Formatting

### Task 5.1 — Dynamic financial display audit `[x]`

- Owner financial amount displays use `@currency(...)` or the configured currency symbol.
- Controller messages and audit entries use the configured currency symbol instead of a hardcoded symbol.
- Revenue and dashboard chart axes/tooltips use the configured currency symbol.
- Verified with Blade cache, production Vite build and `git diff --check`.

### Task 5.2 — Remaining financial/configuration format review `[x]`

- Billing and tenant plan-price selectors now use `@currency(...)` for displayed amounts.
- Currency symbols retained only where they are configuration/default-setting inputs or labels.
- No remaining Owner pattern combines a hardcoded currency symbol with `number_format()` for amount display.
- Re-verified Blade cache, production Vite build and `git diff --check`.

- আর্থিক value-তে বাধ্যতামূলকভাবে `@currency($amount)` ব্যবহার
- count, percentage, latency এবং size-এর consistent formatting
- empty state-এ `0`, `—`, `No data` অথবা `Not configured` standard ব্যবহার
- hardcoded currency symbol নিষিদ্ধ

## Phase 6: Responsive ও Visual QA

### Task 6.1 — Desktop KPI layout review `[x]`

- Network Engines overview now uses the shared `owner-kpi-grid` wrapper.
- Six KPI cards retain consistent spacing and sizing at desktop widths.
- Verified Blade cache, production Vite build and `git diff --check`.

### Desktop

- 1280px বা তার বেশি viewport
- সমান alignment ও spacing
- unnecessary whitespace ছাড়া layout

### Tablet

### Task 6.2 — Tablet responsive review `[x]`

- Shared KPI grid verified as `2 → 3 → 4` columns through tablet breakpoints.
- Owner filters stack at `md` widths without fixed-width controls forcing layout overflow.
- Master tables and long technical content retain horizontal scrolling inside their own containers.
- No new tablet clipping or forced-height issue was found in the owner views scan.

- 768px থেকে 1279px viewport
- 3 বা 4-column layout
- label/value clipping পরীক্ষা

### Mobile

### Task 6.3 — Mobile responsive review `[x]`

- Payment Gateway credential modal now uses one column on mobile and two columns from `sm` upward.
- KPI cards retain the shared two-column mobile layout.
- Long tables and technical content remain contained in horizontal-scroll wrappers.
- Verified Blade cache and `git diff --check` after the mobile layout patch.

- 320px থেকে 767px viewport
- 2-column layout
- horizontal overflow নিষিদ্ধ
- long value truncate policy

## Phase 7: Technical Validation

### Task 7.1 — Build, Blade and route validation `[x]`

- PHP lint passed for the modified Owner controllers.
- `php artisan route:list --path=owner` loaded 111 Owner routes successfully.
- Blade view cache completed successfully.
- Production Vite build completed successfully.
- `git diff --check` completed without whitespace errors.
- Note: this Laravel version does not support the optional `route:list --compact` flag; the supported route command was used.

### Task 7.2 — Owner route smoke test `[x]`

- Owner portal, network engine and SMS gateway tests were executed.
- Fixed runtime 500 from the missing global `currency()` helper.
- Added a schema guard for legacy `sms_logs` tables without `gateway_name`.
- Invoice toggle behavior and wallet transaction schema/currency issues were fixed; `OwnerPortalTest` now passes 9/9.
- Network audit schema drift was corrected with a compatibility migration; global audit entries now support nullable tenants and canonical device/command fields.
- OLT SNMP community values are encrypted at rest with a decrypted accessor.
- Selected Owner smoke suite passed: 21 tests, 169 assertions.

### Task 7.3 — Final Owner view/runtime validation `[x]`

- No Tailwind CDN references remain in `resources/views/owner`.
- Owner invoice print view now uses compiled Vite CSS.
- Owner route listing and selected runtime suite passed.
- Blade cache, production Vite build and `git diff --check` passed.

```bash
php artisan view:cache
node .\\node_modules\\vite\\bin\\vite.js build
```

এছাড়া পরিবর্তিত PHP ফাইল lint, owner route smoke test, browser console error check এবং empty/loading state পরীক্ষা করা হবে।

## Phase 8: Deployment

### Task 8.1 — Deployment preflight `[x]`

- Owner runtime tests, Blade cache and production build are ready.
- The intended change set is isolated in Git staging.
- Unrelated deleted documentation files, deleted `test_vsol_mac.php`, and untracked `debug.log`/`Untitled-1.txt` remain unstaged and untouched.
- No commit, push, migration execution or live-server mutation was performed.

### Task 8.2 — Scoped commit preparation `[x]`

- Staged file scope reviewed and unrelated changes remain unstaged.
- Sensitive-value scan found no hardcoded credentials or private key values.
- Migration, runtime test and build readiness were verified before commit.
- Only a non-functional EOF blank-line warning remains in this plan document.

### Task 8.3 — Scoped local commit `[x]`

- Commit created: `1c5362d` (`Standardize owner KPI cards and harden runtime validation`).
- Commit contains only the staged implementation scope; unrelated worktree changes remain outside the commit.

1. শুধু সংশ্লিষ্ট ফাইল commit করা
2. `main` branch-এ push করা
3. live commit যাচাই করা
4. `public/build/manifest.json` যাচাই করা
5. cache clear করা
6. application down mode আছে কি না যাচাই করা
7. গুরুত্বপূর্ণ owner pages HTTP test করা

## Acceptance Criteria

- সব owner page একই KPI card system ব্যবহার করবে
- card size, padding, icon, label এবং value consistent হবে
- desktop, tablet এবং mobile layout ভাঙবে না
- কোনো oversized বা duplicate card থাকবে না
- dynamic currency formatting বজায় থাকবে
- compiled CSS build সফল হবে
- Blade cache সফল হবে
- কোনো owner page HTTP 500 দেবে না
- existing functionality নষ্ট হবে না

## সম্ভাব্য পরিবর্তিত ফাইল

- `resources/css/app.css`
- `resources/views/components/owner/kpi-card.blade.php`
- সংশ্লিষ্ট `resources/views/owner/**/*.blade.php`
- প্রয়োজন হলে সংশ্লিষ্ট controller বা view-model

