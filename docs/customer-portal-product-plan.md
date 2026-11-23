# ISP Customer & Reseller Portal পরিকল্পনা

## ১. বর্তমান প্রকল্পের অবস্থা

প্রকল্পে বর্তমানে তিনটি প্রধান user layer আছে:

- Platform Owner portal: `/owner/*`
- ISP/Tenant administration portal: `/admin/*`
- Reseller portal: `/reseller/*`

Customer-related data ও business logic ইতিমধ্যে আছে:

- `TenantCustomer`
- `TenantCustomerInvoice`
- `TenantCustomerPayment`
- `TenantGatewayTransaction`
- package, session, usage ও network data
- ticket ও notification architecture

তবে customer-এর জন্য আলাদা login identity ও mobile-first portal এখনো নেই। `TenantCustomer.password` network/PPPoE credential হিসেবে ব্যবহৃত হয়; এটি customer portal password হিসেবে reuse করা যাবে না।

## ২. Product লক্ষ্য

প্রতিটি ISP-এর গ্রাহকের জন্য একটি mobile app-এর মতো responsive Customer Portal এবং পরবর্তীতে installable PWA তৈরি করা। একই platform-এ ISP staff ও reseller staff-এর জন্য mobile-friendly operational experience রাখা হবে, তবে customer security boundary আলাদা থাকবে।

প্রস্তাবিত customer routes:

- `/portal/login`
- `/portal/dashboard`
- `/portal/package`
- `/portal/bills`
- `/portal/payments`
- `/portal/usage`
- `/portal/support`
- `/portal/profile`

## ৩. Account ও authentication architecture

Customer-এর জন্য আলাদা `tenant_customer_accounts` table তৈরি করা হবে। এতে থাকবে:

- `tenant_id`
- `customer_id`
- login phone/email
- hashed portal password
- active/inactive status
- email/phone verification state
- last login timestamp
- remember token
- optional 2FA state

প্রথম release-এ phone/email + password login থাকবে। Password reset OTP বা verified email-এর মাধ্যমে হবে। Login rate limit, session regeneration, logout এবং inactive account blocking বাধ্যতামূলক।

গুরুত্বপূর্ণ নিয়ম: PPPoE username/password এবং portal login credential কখনো একই credential হিসেবে ব্যবহার করা যাবে না।

## ৪. Authorization ও tenant isolation

প্রতিটি customer request-এ নিম্নলিখিত scope বাধ্যতামূলক:

```text
authenticated_customer
  -> customer_account.customer_id
  -> tenant_customer.id
  -> tenant_id
```

Customer কেবল নিজের invoice, payment, package, usage ও ticket দেখতে পারবে। URL-এ customer ID বা invoice ID বদলালেও policy এবং ownership query ছাড়া কোনো record ফেরত দেওয়া যাবে না।

Reseller staff কেবল নিজের reseller scope-এর assigned customer দেখবে। ISP staff-এর permission-based access থাকবে। Platform Owner ছাড়া কোনো user অন্য tenant-এর data দেখতে পারবে না।

## ৫. Mobile app-এর মতো UI

Customer portal-এর জন্য আলাদা mobile layout তৈরি হবে:

- fixed bottom navigation
- Home, Bills, Payments, Support, Profile—প্রধান পাঁচটি menu
- touch-friendly button ও control
- compact summary cards
- bottom sheet ও mobile modal
- desktop-এ responsive centered layout
- wide desktop table এড়িয়ে card/list view
- dynamic ISP logo, company name ও brand color
- loading, empty, offline ও error state
- PWA manifest, service worker ও install prompt

Customer dashboard-এ থাকবে:

- current package
- internet/service status
- due amount
- next billing date
- current session/usage summary
- Quick Pay action
- recent payment
- open support ticket
- service suspension বা payment warning

## ৬. Billing ও payment

বিদ্যমান billing model reuse করা হবে:

- `TenantCustomerInvoice`
- `TenantCustomerPayment`
- `TenantGatewayTransaction`
- existing gateway/payment services

তবে customer portal payment flow-এ অবশ্যই থাকতে হবে:

- invoice ownership verification
- server-side payable amount calculation
- transaction এবং row lock
- durable idempotency key
- callback signature/status verification
- duplicate payment prevention
- immutable payment ledger
- success/failure/pending state
- safe retry flow

Customer কখনো সরাসরি invoice status `paid` করতে পারবে না। Gateway callback বা authorized ledger settlement ছাড়া status পরিবর্তন হবে না।

## ৭. Customer services

### Bills

- current due
- invoice list
- invoice details
- downloadable receipt
- due-date ও status badge

### Payments

- gateway payment
- payment history
- pending payment tracking
- receipt view/download

### Package ও usage

- active package/profile
- expiry date
- speed/plan information
- current online state
- session duration
- usage summary, যদি network telemetry available থাকে

### Support

- ticket create
- ticket list/detail
- reply
- attachment
- status timeline
- SLA/status notification

Existing tenant/reseller ticket architecture reuse করা যাবে, তবে customer-specific policy ও ownership যোগ করতে হবে।

## ৮. API-first design

Web portal এবং ভবিষ্যৎ native mobile app একই API ব্যবহার করবে:

- `POST /api/portal/login`
- `POST /api/portal/logout`
- `GET /api/portal/me`
- `GET /api/portal/dashboard`
- `GET /api/portal/invoices`
- `GET /api/portal/invoices/{invoice}`
- `POST /api/portal/payments`
- `GET /api/portal/payments`
- `GET /api/portal/package`
- `GET /api/portal/usage`
- `GET /api/portal/tickets`
- `POST /api/portal/tickets`
- `POST /api/portal/tickets/{ticket}/replies`
- `PUT /api/portal/profile`

API-তে token/session protection, rate limit, validation, ownership policy এবং sanitized response থাকবে।

## ৯. Notification ও communication

প্রথম release-এ:

- invoice generated notification
- payment success/failure notification
- due-date reminder
- service suspension warning
- ticket reply notification

পরবর্তী release-এ:

- push notification
- OTP login
- WhatsApp/SMS delivery preference
- notification center

## ১০. Security baseline

- separate customer authentication boundary
- password hash only; plaintext নয়
- OTP expiry ও attempt limit
- login throttling
- session regeneration
- CSRF protection
- API token rotation/revocation
- tenant/customer ownership policy
- payment idempotency
- sensitive credential redaction
- audit log for login, password change, payment ও profile change
- file upload type/size scanning
- no PPPoE password exposure in portal
- no gateway secret exposure in customer response

বর্তমান reseller account provisioning-এ default password `12345678` ধরনের pattern পাওয়া গেছে; নতুন portal-এর জন্য এটি সম্পূর্ণ নিষিদ্ধ করতে হবে এবং পুরোনো account migration/reset plan করতে হবে।

## ১১. বাস্তবায়ন ধাপ

### Phase 1 — Foundation

- customer account migration
- customer auth guard/provider
- login/logout/reset
- customer policies
- tenant isolation tests

### Phase 2 — Mobile shell

- customer layout
- bottom navigation
- responsive cards
- loading/empty/error state
- branding configuration

### Phase 3 — Dashboard ও billing

- dashboard API/controller
- invoice list/details
- due summary
- payment initiation
- payment callback verification

### Phase 4 — Customer services

- package
- session/usage
- payment history
- receipt
- support tickets
- notifications

### Phase 5 — PWA ও production hardening

- manifest
- service worker
- install prompt
- offline shell
- device/session management
- security headers
- monitoring ও audit

## ১২. Test পরিকল্পনা

অবশ্যই feature/security test লিখতে হবে:

- customer login success/failure
- inactive customer blocked
- tenant isolation
- customer A cannot read customer B invoice
- reseller scope isolation
- invoice ownership validation
- duplicate payment/idempotency
- callback replay protection
- ticket ownership
- password reset expiry
- OTP attempt limit
- API rate limit
- mobile view render এবং PWA asset availability

## ১৩. Production rollout

প্রথমে staging environment-এ:

1. migration backup
2. sample customer account creation
3. login ও password reset verification
4. payment sandbox verification
5. reseller/tenant isolation test
6. mobile browser verification
7. PWA install verification
8. security regression suite

এরপর limited tenant pilot, তারপর সব ISP-এর জন্য rollout করা হবে।

## ১৪. Recommended first implementation batch

প্রথম batch-এ একসঙ্গে পুরো portal না বানিয়ে নিচের অংশ তৈরি করা সবচেয়ে নিরাপদ:

1. `tenant_customer_accounts` schema
2. customer authentication
3. mobile layout ও bottom navigation
4. dashboard summary
5. invoice list/details
6. security policy ও isolation tests

এরপর payment এবং support ধাপে ধাপে যোগ করা হবে।

## ১৫. অনুমোদিত security decisions

### Tenant identification

Customer login-এর সময় শুধু phone number দিয়ে ISP শনাক্ত করা যাবে না, কারণ একই mobile number একাধিক ISP-তে থাকতে পারে। Login flow-এ নির্ভরযোগ্য tenant context বাধ্যতামূলক হবে:

- tenant-specific URL বা branded domain
- অথবা tenant code/slug + phone/email + password
- অথবা verified OTP flow-তে tenant selection
- resolved `tenant_id` ছাড়া authentication সম্পন্ন হবে না

একবার tenant context resolve হলে login query অবশ্যই `tenant_id + login identifier` দিয়ে হবে। Authentication-এর পরে session ও token claims-এ tenant context bind থাকবে এবং প্রতিটি request-এ তা verify হবে।

### Customer account security

`tenant_customer_accounts`-এ আলাদা password hash থাকবে। `TenantCustomer.password`, PPPoE password বা network credential কোনোভাবেই portal authentication-এ ব্যবহার করা যাবে না।

- portal password আলাদাভাবে hash করতে হবে
- password কখনো plaintext হিসেবে সংরক্ষণ করা যাবে না
- masked credential field-এ password ফেরত দেওয়া যাবে না
- password reset হলে পুরোনো session/token revoke করতে হবে
- customer account disable হলে সব active session/token বন্ধ করতে হবে

### API authentication

একই domain model দুই ধরনের authentication সমর্থন করবে:

- Blade portal: secure Laravel session, CSRF protection, session regeneration
- ভবিষ্যৎ mobile app: token-based authentication, token expiry, rotation ও revocation

Web session ও mobile token-এর authorization policy একই থাকবে। API token কখনো Blade session বা password-এর বিকল্প হিসেবে plaintext response-এ প্রকাশ করা যাবে না।

### Payment security gate

Payment implementation-এর সময় নিচের বিষয়গুলো optional নয়, বাধ্যতামূলক:

- durable idempotency key
- database transaction
- invoice/customer row lock
- server-side amount ও ownership verification
- signed callback verification
- callback replay protection
- immutable payment ledger
- duplicate settlement prevention
- pending/failed/success state transition rules
- gateway response ও secret redaction

Customer request থেকে সরাসরি invoice `paid` করা যাবে না। Verified gateway callback বা authorized ledger settlement ছাড়া financial state পরিবর্তন হবে না।

### PWA data security

Offline cache-এ customer invoice, payment information, gateway response, personal profile বা অন্য sensitive account data রাখা যাবে না।

- service worker কেবল public static shell cache করবে
- API response cache করা যাবে না
- sensitive route-এ `Cache-Control: no-store` ব্যবহার করতে হবে
- logout বা account switch হলে local storage/cache পরিষ্কার করতে হবে
- service worker update ও cache invalidation versioned হতে হবে
- offline mode-এ শুধু generic offline screen দেখানো হবে

## ১৬. Implementation phase approval

| Phase | Scope | Evaluation |
|---|---|---|
| Phase 1 — Foundation | Tenant-aware customer account, authentication, policies, isolation tests | সঠিক |
| Phase 2 — Mobile Shell | Mobile-first layout, bottom navigation, responsive states, branding | সঠিক |
| Phase 3 — Dashboard & Billing | Dashboard, invoice, payment initiation ও secure callback flow | সঠিক |
| Phase 4 — Customer Services | Package, usage, payment history, receipt, support ও notifications | সঠিক |
| Phase 5 — PWA & Hardening | PWA shell, install flow, offline-safe behavior, monitoring ও production hardening | সঠিক; তবে security testing Phase 1 থেকেই চলবে |

প্রতিটি phase শেষ হওয়ার আগে unit/feature/security test, tenant isolation test এবং relevant browser/mobile verification সম্পন্ন করতে হবে। Security testing Phase 5 পর্যন্ত অপেক্ষা করবে না।

## ১৭. Final implementation decision

এই পরিকল্পনা implementation-এর ভিত্তি হিসেবে গ্রহণযোগ্য।

- নতুন frontend framework প্রয়োজন নেই। বর্তমান Laravel + Blade + Alpine.js stack যথেষ্ট।
- বিদ্যমান Owner, ISP Admin এবং Reseller portal সরাসরি পুনর্লিখন করতে হবে না; প্রয়োজনীয় integration, navigation ও policy যোগ হবে।
- বিদ্যমান billing, payment, customer ও ticket model reuse করা যাবে, তবে source code audit করে ownership ও lifecycle compatibility নিশ্চিত করতে হবে।
- প্রথম implementation batch হিসেবে account foundation, authentication, mobile shell, dashboard summary, invoice list/details এবং security isolation test নেওয়া সঠিক সিদ্ধান্ত।
- Production rollout হবে staging verification, limited tenant pilot এবং তারপর controlled rollout-এর মাধ্যমে।

পরিকল্পনা সঠিক হওয়া এবং প্রতিটি অংশ বিদ্যমান codebase-এর সঙ্গে সামঞ্জস্যপূর্ণ হওয়া আলাদা বিষয়। Implementation শুরু করার আগে সংশ্লিষ্ট migration, model, policy, middleware, route, payment service ও existing test source code দিয়ে compatibility যাচাই করতে হবে।
