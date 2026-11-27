# Owner Filter & Table Standardization Plan

## Current Implementation Status

- Shared filter toolbar and table shell components are implemented.
- Tenant, User, Plan, Wallet, Subscription, Billing, Payment, Transaction, Revenue, Activity Log, SMS Log, Ticket, Network Engine, Payment Gateway, Automation, Security Backup, Security Audit, Mail SMTP History and SMS Gateway table migrations are committed.
- Remaining focused work: normalize compressed SMS Gateway inner markup, reconcile any remaining filter-toolbar deviations, and complete full-suite validation.
- Full `php artisan test --no-coverage` was started but produced no output and was stopped; targeted Owner regression validation remains successful.
- Coverage audit exception: `owner/invoices/print.blade.php`, `owner/invoices/show.blade.php` and `owner/tenants/show.blade.php` contain detail/print tables, not master listing tables, so they remain outside the shared listing shell scope.
- Owner route validation passed with `php artisan route:list --path=owner`: 111 routes resolved successfully.

### Validation Handoff

- `php artisan view:cache` passes after each scoped migration.
- `tests/Feature/OwnerPortalTest.php --filter=test_owner_can_access_all_dedicated_menus` passes with 10 assertions.
- `git diff --check` passes for each committed change.
- Full PHPUnit suite remains environment-blocked because it runs without output; Vite build is blocked by the workspace path containing `&` and the Windows npm wrapper resolving the wrong path.
- No deployment, database reset, credential change or unrelated file cleanup was performed.

## লক্ষ্য

Owner panel-এর সব master listing page-এ একই filter ও table design system প্রয়োগ করা। Reference হিসেবে project-এর বিদ্যমান customer listing pattern (`resources/views/reseller/customers/index.blade.php` এবং `resources/views/tenant/customers/index.blade.php`) অনুসরণ করা হবে, কারণ `resources/views/admin/customers` নামে এই repository-তে আলাদা view পাওয়া যায়নি।

## Reference Design Rules

### Filter system

- প্রতিটি master listing page-এ দুই-স্তরীয় filter layout থাকবে।
- প্রথম স্তরে থাকবে:
  - live search input
  - ২–৩টি primary filter
  - `per_page` selector
  - `More Filters` toggle
  - সর্বশেষে `Filter`, তারপর `Reset` button
- দ্বিতীয় স্তর `x-show`, `x-cloak`, `x-transition` drawer হিসেবে থাকবে।
- Active secondary filter থাকলে drawer page load-এ open থাকবে।
- Input, select এবং button-এর height বাধ্যতামূলকভাবে `h-[34px]` হবে।
- Search input-এ dynamic search icon state এবং এক-click clear action থাকবে।
- `More Filters` button-এ active filter counter, `aria-expanded` এবং `aria-controls` থাকবে।
- Filter state server-side request থেকে hydrate হবে; reset action সবসময় visible থাকবে।
- Form submit-এ অপ্রয়োজনীয় sort query parameter যোগ করা যাবে না।

### Table system

- সব master table-এ `<table class="saas-table">` ব্যবহার করতে হবে।
- Table container-এ forced `min-height` ব্যবহার করা যাবে না।
- Empty state compact, meaningful এবং relevant action সহ হবে।
- Header label center aligned হবে।
- Text data left, amount right এবং status/date/action center aligned হবে।
- Cell data single-line `nowrap` থাকবে; অপ্রয়োজনীয় concatenation নিষিদ্ধ।
- সর্বোচ্চ ৫–৭টি essential column থাকবে। বিস্তারিত data `View Details` modal/drawer-এ থাকবে।
- Zebra row, hover border এবং compact padding global `.saas-table` CSS থেকে আসবে।
- Action column-এ শুধু একটি global three-dot menu থাকবে; duplicate inline action button রাখা যাবে না।
- Sortable columns client-side global sorter ব্যবহার করবে; action column `no-sort` হবে।
- Pagination Laravel server-side paginator দিয়ে হবে; table-এর ভিতরে fake client-side pagination রাখা যাবে না।

## Scope

### Mandatory sidebar coverage rule

`resources/views/owner/layouts/partials/sidebar.blade.php`-এ থাকা কোনো Owner menu route এই কাজের scope-এর বাইরে রাখা যাবে না। Sidebar-এর প্রতিটি linked view inventory করে তার filter/table status plan-এ নথিভুক্ত করতে হবে। যেসব view-তে table আছে সেগুলো অবশ্যই reference filter ও `.saas-table` rules-এ migrate হবে; যেসব view-তে table নেই সেগুলোকে `No table / not applicable` হিসেবে audit checklist-এ চিহ্নিত করতে হবে। কোনো table থাকা menu view বাদ রাখা যাবে না।

বর্তমান sidebar coverage inventory:

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

### In-scope Owner master listing pages

- Tenants
- Users
- Plans
- Wallets
- Billing / Invoices
- Subscriptions
- Payments
- Transactions
- Payment Gateways
- SMS Gateways
- Activity Logs
- SMS Logs
- Network Engines / Audit Logs
- Tickets
- Revenue report যেখানে master table আছে

Sidebar coverage-এর জন্য অতিরিক্ত audit requirement:

- Sidebar route inventory এবং actual route/controller/view mapping মিলিয়ে দেখা।
- প্রতিটি linked view-এ table আছে কি না এবং থাকলে কোন table/filter batch-এ migrate হয়েছে তা checklist-এ mark করা।
- Hidden tab, modal-এর ভিতরের table এবং secondary ledger table-ও বাদ না দেওয়া।
- কোনো view table না থাকলে শুধু সেই view-এর filter/table status `not applicable` হিসেবে চিহ্নিত করা; view-টি audit থেকে বাদ দেওয়া যাবে না।

Document print view, create/edit form এবং detail-only page এই scope-এর master table migration-এর বাইরে থাকবে; তবে তাদের shared filter/table CSS conflict করা যাবে না।

## Task Plan

### Task 1 — Reference ও current Owner audit `[x]`

- Customer reference filter/table structure line-by-line audit করা।
- `resources/views/owner/layouts/partials/sidebar.blade.php` থেকে সব menu route inventory করা।
- সব Owner listing page-এর filter control, query parameter, paginator, table class, column count এবং action pattern inventory করা।
- Missing filter, inconsistent height, duplicate action, excessive column এবং non-standard empty state তালিকাভুক্ত করা।

#### Audit Findings

- `resources/views/owner/layouts/partials/sidebar.blade.php` থেকে Dashboard ও logout বাদে ২১টি Owner menu route inventory করা হয়েছে।
- Reference customer filter-এ live search, primary status/zone/package filters, `per_page`, `More Filters`, active counter, secondary drawer এবং `[Filter]` তারপর `[Reset]` sequence রয়েছে।
- Reference customer table `.saas-table`, centered headers, single-line cell data, server pagination এবং compact empty state ব্যবহার করে।
- Owner listing pages-এ existing filter/query parameter আলাদা; migration-এর সময় backend query contract অক্ষত রেখে markup standardize করতে হবে।
- Payment, SMS, mail, network, billing, logs ও settings-এর কিছু view-এ table নেই বা table secondary section-এ আছে; এগুলোও sidebar coverage checklist-এ explicitly mark করতে হবে।
- `resources/views/admin/customers` repository-তে অনুপস্থিত; তাই reseller ও tenant customer views reference হিসেবে নথিভুক্ত করা হয়েছে।

### Task 2 — Shared filter contract `[x]`

- Shared filter markup/component বা reusable partial-এর contract নির্ধারণ করা।
- Search, primary filters, secondary drawer, active count, per-page এবং Filter/Reset sequence এক করা।
- Existing controller query parameter না ভেঙে page-specific filter field mapping নির্ধারণ করা।

#### Contract Implemented

- Reusable component তৈরি হয়েছে: `resources/views/components/owner/filter-toolbar.blade.php`।
- Component props: `action`, `method`, `showMoreOpen`, `activeMoreCount`, `resetUrl` এবং optional `formId`।
- Named slots:
  - `primary` — search, core filters, `per_page` এবং page-specific primary controls
  - `secondary` — advanced/secondary filter drawer controls
- Component বাধ্যতামূলকভাবে `h-[34px]` More Filters, Filter ও Reset controls, active counter, `aria-expanded`, `aria-controls`, `x-cloak` এবং responsive drawer structure প্রদান করে।
- Page-specific filter fields slots-এ থাকবে; shared component কোনো existing query parameter বা backend contract hardcode করবে না।
- Blade view cache সফল হয়েছে।

### Task 3 — Shared table contract `[x]`

- `.saas-table` header/body/empty/pagination structure standardize করা।
- Column alignment, nowrap, currency formatting, date/status badge এবং `no-sort` rule প্রয়োগ করা।
- Global three-dot action menu positioning ও clipping protection যাচাই করা।

#### Contract Implemented

- Reusable component তৈরি হয়েছে: `resources/views/components/owner/table-shell.blade.php`।
- Component বাধ্যতামূলকভাবে `.saas-table`, natural-height overflow container, compact empty state এবং optional pagination footer প্রদান করে।
- Named slots:
  - `head` — centered table header cells
  - `body` বা default slot — page-specific rows
  - `emptyAction` — empty state-এর relevant action
  - `pagination` — server-side paginator output
- Shared CSS contract `owner-table-shell`, `owner-table-empty` এবং `owner-table-pagination` যুক্ত হয়েছে।
- Component কোনো column, query parameter বা backend model hardcode করে না।
- Blade cache ও production Vite build সফল হয়েছে।

### Task 4 — Owner page migration batch 1 `[ ]`

- Tenants, Users, Plans, Wallets এবং Subscriptions migrate করা।
- Filter query preservation, pagination এবং existing CRUD/modal actions যাচাই করা।

#### Task 4.1 — Tenants table-shell migration `[x]`

- Tenants master table `x-owner.table-shell` component-এ migrate করা হয়েছে।
- Existing columns, three-dot actions, tenant pagination এবং modal behavior অক্ষত রাখা হয়েছে।
- Empty state component-এর natural empty-state contract-এ নেওয়া হয়েছে।
- Blade cache ও tenant management regression test সফল হয়েছে।
- Tenants filter migration আলাদা Task 4.2 হিসেবে সম্পন্ন করা হয়েছে।

#### Task 4.2 — Tenants filter migration `[x]`

- Tenants legacy filter `x-owner.filter-toolbar` component-এ migrate করা হয়েছে।
- Search, plan, status, per-page, active filter counter, secondary drawer এবং Filter/Reset sequence standard contract অনুসরণ করছে।
- Existing tenant query parameters ও CRUD/action flow অক্ষত রাখা হয়েছে।
- Blade cache, tenant regression test এবং diff check সফল হয়েছে।

### Task 5 — Owner page migration batch 2 `[ ]`

- Billing, Payments, Transactions এবং Revenue migrate করা।
- Financial amount right alignment ও dynamic `@currency` formatting যাচাই করা।

### Task 6 — Owner page migration batch 3 `[ ]`

- Payment Gateways, SMS Gateways, Mail-related logs, Activity Logs এবং SMS Logs migrate করা।
- Credential/sensitive data table-এ প্রকাশ না করার নিশ্চয়তা দেওয়া।

### Task 7 — Owner page migration batch 4 `[ ]`

- Network Engines, Network Audit Logs, Tickets এবং remaining listing pages migrate করা।
- Long device/action data modal বা details view-এ রাখা।

### Task 8 — Responsive, accessibility ও state validation `[ ]`

- Desktop, tablet এবং 320px+ mobile viewport যাচাই করা।
- Filter drawer keyboard accessible কি না পরীক্ষা করা।
- Empty, loading, validation error এবং no-result state যাচাই করা।
- Table horizontal overflow শুধু প্রয়োজনীয় ক্ষেত্রে থাকবে; forced blank space থাকবে না।

### Task 9 — Runtime, build ও deployment validation `[ ]`

- `php artisan view:cache`
- `node .\\node_modules\\vite\\bin\\vite.js build`
- Relevant Owner feature tests
- Owner route smoke test
- Filter query/pagination HTTP tests
- `git diff --check`
- Duplicate/non-standard table audit
- Sidebar coverage audit: কোনো table থাকা linked view বাদ আছে কি না যাচাই
- Scoped commit ও remote push; unrelated changes stage করা যাবে না

## Acceptance Criteria

- Owner-এর সব master listing page customer reference-এর মতো দুই-স্তরীয় filter ব্যবহার করবে।
- সব filter control একই `h-[34px]` height ও consistent spacing অনুসরণ করবে।
- Filter button-এর পরে Reset button থাকবে এবং Reset সবসময় visible থাকবে।
- সব master table `.saas-table` ব্যবহার করবে।
- অপ্রয়োজনীয় column, duplicate action এবং multi-line cell থাকবে না।
- Empty state compact ও actionable হবে।
- Search/filter/pagination query behavior বিদ্যমান backend logic না ভেঙে কাজ করবে।
- Desktop/mobile-এ layout ভাঙবে না।
- No HTTP 500, Blade error, missing route বা broken modal থাকবে না।
- Build, cache, tests এবং diff validation সফল হবে।

## সম্ভাব্য পরিবর্তিত ফাইল

- `resources/views/components/owner/*`
- `resources/css/app.css`
- `resources/views/owner/**/*.blade.php`
- সংশ্লিষ্ট Owner controllers/services/query builders
- সংশ্লিষ্ট Feature tests
- এই plan file

## সীমা ও নিরাপত্তা

- Database reset/drop/truncate করা যাবে না।
- Existing filter query parameter ও tenant isolation নষ্ট করা যাবে না।
- Sensitive credential/API key table বা diff-এ প্রকাশ করা যাবে না।
- Unrelated deleted/untracked file stage বা commit করা যাবে না।
#### Task 4.3 — Users filter/table migration `[x]`

- Users primary ও secondary filter `x-owner.filter-toolbar` component-এ migrate করা হয়েছে।
- Tenant, role, status, search এবং per-page filter-এর existing query contract রাখা হয়েছে।
- Users table `x-owner.table-shell` component-এ migrate করা হয়েছে; shared empty state ও pagination slot ব্যবহার করা হয়েছে।
- Existing action menu, user payload এবং modal flow অপরিবর্তিত রাখা হয়েছে।
- Blade cache, `test_owner_can_access_all_dedicated_menus` এবং `git diff --check` সফল হয়েছে।
