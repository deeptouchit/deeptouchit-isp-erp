# Owner Panel Global Header Standardization Plan

## লক্ষ্য

Owner panel-এর সব পেজে Dashboard-এর মতো একই global header ব্যবহার করা। Header-এর height, spacing, icon, title, status badge এবং action layout এক জায়গা থেকে নিয়ন্ত্রিত হবে। কোনো পেজে আলাদা বা duplicate top header থাকবে না।

## Design Standard

- Shared header height ও responsive spacing একই থাকবে।
- Header/card/button কোনোটি অতিরিক্ত pill বা ডিম্বাকৃতি হবে না; restrained rounded corner (প্রয়োজনে `rounded-lg`) ব্যবহার করতে হবে।
- `rounded-full` শুধু status dot, ছোট status badge বা avatar-এর মতো উপযুক্ত উপাদানে ব্যবহার করা যাবে; প্রধান header container ও action button-এ নয়।
- বামে page icon, dynamic page title, context subtitle এবং `Live` status badge থাকবে।
- ডানে SMS Balance, Run Billing, New Tenant, notification এবং profile actions থাকবে।
- Mobile-first layout হবে; ছোট স্ক্রিনে title truncate হবে, actions wrap/stack হবে এবং কোনো horizontal overflow থাকবে না।
- Desktop, tablet এবং mobile breakpoint-এ header-এর alignment, spacing ও action visibility আলাদা করে যাচাই করতে হবে।
- Touch target কমপক্ষে 36px উচ্চতার হবে এবং mobile-এ action button চাপতে সুবিধাজনক spacing থাকবে।
- Dashboard এবং অন্যান্য Owner page একই component/layout ব্যবহার করবে।
- Page-specific action থাকলে সেটি নির্দিষ্ট slot/section-এর মাধ্যমে যুক্ত হবে; global header markup copy করা যাবে না।
- Header title `page-title` section বা নির্ধারিত page metadata থেকে আসবে।
- কোনো hardcoded credential, API key বা sensitive data header-এ থাকবে না।

## কাজের ধাপ ও Task

### Task 1 — বর্তমান Owner layout audit `[x]`

- Owner layout, topbar, Dashboard এবং সব Owner page-এর প্রথম header block তালিকাভুক্ত করা।
- Duplicate header, inconsistent height, title এবং action pattern শনাক্ত করা।
- Existing notification, profile menu এবং mobile navigation-এর dependency নথিভুক্ত করা।

#### Audit Findings

- Owner layout-এর shared entry point: `resources/views/owner/layouts/app.blade.php`।
- Global topbar ও sidebar বর্তমানে layout থেকে include হচ্ছে; notification, profile dropdown, logout এবং mobile sidebar state topbar/layout-এর সঙ্গে যুক্ত।
- মোট ৩৪টি Owner Blade view audit করা হয়েছে।
- Dashboard-এর পুরোনো duplicate content header সরানো হয়েছে, কিন্তু অনেক page body-তে এখনও আলাদা header block আছে।
- আলাদা header পাওয়া গেছে Billing, Tenants, Plans, Wallets, Subscriptions, Payments, Transactions, Network Engines, Network Settings, Network Audit Logs, Payment Gateways, SMS Gateways, Mail Settings, Revenue, Activity/SMS Logs, Automation, Security, Settings, Users এবং Tickets-এর মতো page-এ।
- প্রধান অসঙ্গতি: header height, title wording, icon size, action placement, rounded style এবং mobile wrapping এক নয়।
- Migration-এর সময় `owner/auth/login`, invoice print/show এবং tenant detail-এর মতো document/detail-specific header আলাদা scope হিসেবে বিবেচনা করতে হবে; এগুলো global navigation header-এর সঙ্গে সরাসরি merge করা হবে না।

### Task 2 — Shared header architecture `[x]`

- Reusable Owner header component/partial-এর final structure নির্ধারণ করা।
- Dynamic title, subtitle, icon, status এবং optional page-action slot-এর contract নির্ধারণ করা।
- Global action route ও authorization behavior যাচাই করা।

#### Architecture Decision

- Existing `resources/views/owner/layouts/partials/topbar.blade.php`-কেই shared Owner header-এর canonical implementation হিসেবে রাখা হবে; একই markup আর কোনো page-এ copy করা যাবে না।
- `resources/views/owner/layouts/app.blade.php` একমাত্র global injection point থাকবে।
- Page title `@section('page-title')` থেকে আসবে; Dashboard-এর জন্য compatibility label `Overview Dashboard` থাকবে।
- Global actions topbar-এ থাকবে; page-specific actions body header থেকে migration-এর সময় আলাদা action slot/toolbar-এ রাখা হবে।
- Notification, profile, logout এবং mobile sidebar state বর্তমান topbar/layout Alpine scope-এর মধ্যেই থাকবে।
- নতুন component শুধু তখনই তৈরি করা হবে যখন topbar-এর shared markup ভেঙে reusable subcomponent করা বাস্তবিকভাবে প্রয়োজন হবে; অপ্রয়োজনীয় abstraction যোগ করা হবে না।

### Task 3 — Global header implementation `[x]`

- Shared Owner layout-এ standard header যুক্ত করা।
- Dashboard-এর duplicate header সরিয়ে shared header ব্যবহার করা।
- Existing notification, profile dropdown, CSRF এবং logout behavior অক্ষত রাখা।

#### Completed Implementation

- Shared topbar-এ `owner-global-header` contract class যুক্ত করা হয়েছে।
- Header title, Live badge এবং global actions responsive overflow-safe করা হয়েছে।
- প্রধান header/action container-এ pill বা অতিরিক্ত rounded style যোগ করা হয়নি।
- Mobile breakpoint-এ title truncation, compact spacing এবং action visibility hardening করা হয়েছে।
- Compiled CSS source ও production asset build সফল হয়েছে।

### Task 4 — Owner page migration `[ ]`

- Dashboard, Tenants & Plans, Network Engines, Billing & Finance, Support, Gateways, Reports এবং Settings পেজ যাচাই করা।
- প্রতিটি পেজের duplicate top header standard layout-এ রূপান্তর করা।
- প্রয়োজনীয় page-specific action শুধু অনুমোদিত slot/section-এ রাখা।

#### Task 4.1 — Finance listing batch `[x]`

- Payments, Wallets, Subscriptions এবং Transactions page-এর duplicate body header সরানো হয়েছে।
- Existing KPI, filters, tables, actions এবং modal behavior অক্ষত রাখা হয়েছে।
- Legacy test-visible wording page-title-এর মাধ্যমে compatibility-safe রাখা হয়েছে।
- Blade cache, Owner regression tests এবং diff check সফল হয়েছে।

#### Task 4.2 — Billing, plans ও tenants batch `[x]`

- Billing, Plans এবং Tenants listing page-এর duplicate title header সরানো হয়েছে।
- Generate Invoice, Sync Plans, Add Plan এবং Create Tenant-এর মতো page-specific actions compact action row-তে রাখা হয়েছে।
- Legacy page labels accessible compatibility text/page-title-এর মাধ্যমে বজায় রাখা হয়েছে।
- Blade cache এবং dedicated financial Owner test সফল হয়েছে।

### Task 5 — Responsive ও accessibility hardening `[ ]`

- Desktop, tablet এবং mobile viewport-এ overflow, truncation ও alignment যাচাই করা।
- অতিরিক্ত গোলাকার/pill UI বাদ দিয়ে consistent `rounded-lg` visual language প্রয়োগ করা।
- 320px থেকে বড় mobile viewport-এ header ভাঙে না বা horizontal scroll তৈরি করে না—এটি যাচাই করা।
- Button-এর accessible label, focus state এবং keyboard interaction নিশ্চিত করা।
- Notification/profile dropdown header-এর বাইরে clip না হওয়ার নিশ্চয়তা দেওয়া।

### Task 6 — Visual consistency validation `[ ]`

- সব Owner route-এ header height, padding, typography, icon এবং action alignment তুলনা করা।
- Duplicate title/header, oversized spacing এবং inconsistent action placement সরানো।
- Existing global KPI card standard-এর সঙ্গে vertical rhythm মিলিয়ে নেওয়া।

### Task 7 — Runtime ও build validation `[ ]`

- `php artisan view:cache`
- `node .\\node_modules\\vite\\bin\\vite.js build`
- Owner feature test suite
- Owner route smoke test
- `git diff --check`
- গুরুত্বপূর্ণ Owner page-এ HTTP 500 বা missing route আছে কি না যাচাই

### Task 8 — Documentation ও deployment `[ ]`

- এই plan-এর প্রতিটি সম্পন্ন task `[x]` করা।
- পরিবর্তিত file scope review করা।
- Unrelated user changes stage না করা।
- Scoped commit তৈরি করা এবং অনুমোদিত workflow অনুযায়ী remote push করা।
- Commit hash ও validation result নথিভুক্ত করা।

## Acceptance Criteria

- সব Owner page একই shared global header ব্যবহার করবে।
- কোনো Owner page-এ duplicate বা manually copied global header থাকবে না।
- Header-এর title page অনুযায়ী dynamic হবে, কিন্তু layout এক থাকবে।
- Dashboard-এর existing action ও label compatibility বজায় থাকবে।
- Notification, profile, logout এবং mobile navigation কাজ করবে।
- Desktop, tablet ও mobile layout-এ clipping/overflow থাকবে না।
- Blade cache, production build এবং relevant tests সফল হবে।
- কোনো HTTP 500, missing route বা console-breaking template error থাকবে না।

## সম্ভাব্য পরিবর্তিত ফাইল

- `resources/views/owner/layouts/app.blade.php`
- `resources/views/owner/layouts/partials/topbar.blade.php`
- `resources/views/components/owner/page-header.blade.php` অথবা shared Owner partial
- `resources/views/owner/**/*.blade.php` — কেবল duplicate header migration-এর প্রয়োজন হলে
- `resources/css/app.css` — কেবল shared header CSS প্রয়োজন হলে
- এই plan file

## নিরাপত্তা ও সীমা

- Database reset/drop/truncate করা যাবে না।
- Credentials বা private key source code-এ যোগ করা যাবে না।
- Unrelated deleted/untracked files পরিবর্তন বা commit করা যাবে না।
- প্রতিটি edit-এর আগে target file VS Code-এ খুলতে হবে এবং পরে focused diff যাচাই করতে হবে।
