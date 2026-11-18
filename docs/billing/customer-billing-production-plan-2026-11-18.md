# Customer Billing Production Remediation Plan

তারিখ: ১৮ নভেম্বর ২০২৬  
পরিধি: `/admin/customers`, customer invoice/payment, expiry/reactivation, bulk collection এবং online/offline session state

## লক্ষ্য

গ্রাহকের বিল গ্রহণ থেকে receipt, invoice ledger, due balance, মেয়াদ, network reactivation এবং live status—সবগুলোকে একই নির্ভরযোগ্য business rule, atomic database operation এবং পরীক্ষাযোগ্য workflow-এর মধ্যে আনা।

## কাজের নীতি

- Financial data পরিবর্তন একই database transaction-এর মধ্যে হবে।
- Concurrent request-এ customer, invoice এবং wallet row lock করা হবে।
- Payment amount, discount, due reduction এবং validity credit আলাদা হিসাব হবে।
- Network sync ব্যর্থ হলেও financial transaction হারাবে না; failure audit/retry করার মতোভাবে প্রকাশিত হবে।
- প্রতিটি phase test, focused diff এবং regression check পাস করার পরেই পরবর্তী phase শুরু হবে।
- Live customer দিয়ে destructive test করা হবে না।

## Phase 0 — Baseline ও Safety Net

অবস্থা: সম্পন্ন (baseline audit)

কাজ:

1. বিদ্যমান payment, invoice, expiry, bulk payment এবং session sync flow map করা।
2. বর্তমান failing tests আলাদা করে নথিভুক্ত করা।
3. নতুন production behavior-এর জন্য focused feature tests প্রস্তুত করা।
4. প্রয়োজনীয় database unique/index constraint যাচাই করা।

Acceptance criteria:

- পুরোনো failure এবং নতুন regression failure আলাদা বোঝা যাবে।
- financial changes যাচাই করার repeatable test suite থাকবে।

Baseline findings:

- Customer payment route বর্তমানে broad customer permission group-এর মধ্যে; mutation route-এ আলাদা `billing.collect` permission নেই।
- Single payment transaction ব্যবহার করে, কিন্তু customer/invoice row lock করে না।
- Receipt number `last + 1` পদ্ধতিতে তৈরি হয়; concurrent request collision protection application-level নয়।
- Fresh migration schema এবং controller/model-এর ব্যবহৃত payment/invoice field names-এর মধ্যে compatibility drift আছে।
- Focused billing regression run: ২১টি test-এর মধ্যে ৬টি pass; ১টি assertion failure এবং ১৪টি পুরোনো `HostingPlan` dependency error।
- বর্তমান customer tests legacy route/model expectation ব্যবহার করে; production customer billing flow-এর dedicated tests নেই।

## Phase 1 — Single Payment ও Invoice Ledger

অবস্থা: সম্পন্ন

কাজ:

1. Customer row এবং outstanding invoice rows `lockForUpdate()` দিয়ে lock করা।
2. Payment সবচেয়ে পুরোনো outstanding invoice থেকে ধারাবাহিকভাবে allocate করা।
3. Customer due এবং invoice due একই transaction-এ update করা।
4. Partial payment-এ স্বয়ংক্রিয় full-month validity extension বন্ধ করা।
5. Discount দিয়ে validity বৃদ্ধি বন্ধ করা; validity কেবল বাস্তবে প্রাপ্ত টাকার policy অনুযায়ী হবে।
6. Full settlement বা configured eligibility ছাড়া line reactivation বন্ধ করা।
7. Duplicate submission ঠেকাতে idempotency key/reference validation যোগ করা।
8. Receipt number generation concurrent-safe করা এবং payment-এর সঙ্গে invoice linkage রাখা।

Acceptance criteria:

- একই payment পুনরায় submit করলে দ্বিতীয়বার balance পরিবর্তন হবে না।
- partial payment শুধু due কমাবে; অননুমোদিত expiry/reactivation হবে না।
- payment শেষ হলে customer due এবং সব open invoice due-এর যোগফল মিলবে।

বাস্তবায়িত পরিবর্তন:

- Tenant ও customer row lock করে একই tenant-এর receipt sequence এবং customer balance mutation serialize করা হয়েছে।
- UI-generated `request_key` বাধ্যতামূলক করা হয়েছে; exact replay দ্বিতীয়বার payment তৈরি করে না এবং পরিবর্তিত payload replay HTTP 409 দেয়।
- Partial payment শুধু due কমায়; full cash coverage ছাড়া due-mode validity বাড়ে না।
- Discount debt settle করতে পারে, কিন্তু service validity কিনতে পারে না।
- হার্ডকোডেড fallback monthly rate বাদ দিয়ে customer/package rate ব্যবহার করা হয়েছে।
- Due শূন্য, expiry ভবিষ্যতে এবং reactivation request—তিন শর্ত পূরণ হলেই line active হয়।
- Cash ও discount সবচেয়ে পুরোনো outstanding invoice থেকে ধারাবাহিকভাবে allocate হয় এবং allocation ledger-এ সংরক্ষিত হয়।
- Financial invoice allocation ব্যর্থ হলে transaction rollback হয়; mismatch নীরবে ignore করা হয় না।
- Fresh-install invoice/payment schema বর্তমান canonical model/controller fields-এর সঙ্গে align করা হয়েছে।

Verification:

- `CustomerBillingProductionTest`: ৬টি test pass, ৪২টি assertion pass।
- Covered: schema contract, partial payment, discount-only settlement, full cash renewal, exact replay/conflicting replay এবং oldest-invoice-first allocation।

## Phase 2 — Finance Invoice Payment

অবস্থা: অপেক্ষমাণ

কাজ:

1. Invoice payment-কে atomic transaction-এ নেওয়া।
2. Invoice ও customer row lock করা।
3. Overpayment এবং cancelled/paid invoice payment reject করা।
4. Receipt-এ `invoice_id` সংরক্ষণ করা।
5. Customer reactivation একই shared policy দিয়ে পরিচালনা করা।

Acceptance criteria:

- মাঝপথে failure হলে invoice, receipt বা customer balance আংশিকভাবে save হবে না।
- invoice due-এর বেশি payment গ্রহণ হবে না।

## Phase 3 — Bulk Payment ও Reseller Wallet

অবস্থা: অপেক্ষমাণ

কাজ:

1. Mixed-reseller customer selection reject বা reseller অনুযায়ী আলাদা batch করা।
2. Selected reseller-এর ownership tenant scope-সহ যাচাই করা।
3. Reseller wallet row lock করে balance atomically deduct করা।
4. Zero-amount payment/receipt এবং বিনা payment-এ expiry extension বন্ধ করা।
5. প্রতিটি customer-এর payment invoice ledger-এ সঠিকভাবে allocate করা।
6. Batch reference/idempotency key যোগ করা।

Acceptance criteria:

- wallet concurrent request-এ overdraft হবে না।
- zero due/zero bill customer বিনা কারণে renewed হবে না।
- batch total, receipt total এবং wallet transaction total মিলবে।

## Phase 4 — Expiry, Billing Cycle ও Automation

অবস্থা: অপেক্ষমাণ

কাজ:

1. হার্ডকোডেড fallback rate বাদ দিয়ে package/customer billing rate ব্যবহার করা।
2. Due, advance ও custom payment-এর validity policy একটি shared service-এ নেওয়া।
3. Invoice generation-এর database-level uniqueness নিশ্চিত করা।
4. Automation retry, audit এবং partial network failure reporting উন্নত করা।
5. Expiry boundary এবং tenant timezone test করা।

Acceptance criteria:

- একই billing cycle-এর duplicate invoice তৈরি হবে না।
- configured package ও tenant policy অনুযায়ী expiry নির্ধারিত হবে।

## Phase 5 — Online/Offline ও Network State

অবস্থা: অপেক্ষমাণ

কাজ:

1. MikroTik/RADIUS উভয় source ব্যর্থ হলে আগের online state অক্ষত রাখা।
2. Successful source count এবং degraded sync response যোগ করা।
3. Kick-session result যাচাই করে তবেই offline state update করা।
4. Stale RADIUS session handling ও last-successful-sync timestamp যোগ করা।
5. Financial success এবং network provisioning status আলাদাভাবে UI-তে দেখানো।

Acceptance criteria:

- source outage-এর কারণে সব customer ভুলভাবে offline হবে না।
- session termination ব্যর্থ হলে UI success দেখাবে না।

## Phase 6 — Permissions, Audit ও Observability

অবস্থা: অপেক্ষমাণ

কাজ:

1. Collect, discount, bulk payment, void এবং manual billing action-এ নির্দিষ্ট permission enforcement।
2. Financial audit log-এ before/after balances, actor, request reference এবং network result সংরক্ষণ।
3. নীরবে ignore করা exception-এর পরিবর্তে structured warning/error log ও retry indicator।
4. Sensitive data audit log বা response-এ না রাখা।

Acceptance criteria:

- অনুমতিহীন user financial mutation করতে পারবে না।
- প্রতিটি balance change audit trail দিয়ে পুনর্গঠন করা যাবে।

## Phase 7 — Regression, Deployment ও Verification

অবস্থা: অপেক্ষমাণ

কাজ:

1. Single, partial, advance, discount, duplicate ও concurrent payment tests।
2. Invoice allocation, overpayment, bulk wallet এবং expiry tests।
3. MikroTik/RADIUS success, partial failure ও total failure tests।
4. Blade compile, route/permission এবং focused billing suite চালানো।
5. GitHub push-এর পর webhook deployment ও live commit/source verification।
6. Production smoke test শুধু non-destructive read-only flow দিয়ে করা।

Acceptance criteria:

- নতুন billing tests সম্পূর্ণ pass করবে।
- পুরোনো known failures নতুন পরিবর্তনের কারণে বাড়বে না।
- live server expected commit চালাবে এবং error log-এ নতুন regression থাকবে না।

## বাস্তবায়নের ক্রম

1. Phase 0 — baseline tests ও constraints
2. Phase 1 — single payment ও ledger
3. Phase 2 — finance invoice payment
4. Phase 3 — bulk payment ও reseller wallet
5. Phase 4 — expiry ও automation
6. Phase 5 — online/offline reliability
7. Phase 6 — permissions ও observability
8. Phase 7 — final regression ও deployment

প্রতিটি phase শেষ হলে এই নথিতে অবস্থা, পরিবর্তিত files, test result, commit এবং অবশিষ্ট ঝুঁকি আপডেট করা হবে।
