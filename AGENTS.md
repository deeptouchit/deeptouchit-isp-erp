# ISP MANAGEMENT SYSTEM - MASTER AGENT RULES & DESIGN STANDARDS

All pages, components, controllers, and features in this application must strictly adhere to this single unified master rulebook:

---

## 1. Blade Architecture & Layout Hierarchy
Each Blade view must strictly follow this 3-section layout:
```blade
@extends('tenant.layouts.app')

@section('title', 'Page Title - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="pageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    {{-- 1. Top Header Bar --}}
    {{-- 2. KPI Summary Strip (6 Cards) --}}
    {{-- 3. Search & Filter Bar --}}
    {{-- 4. Master Table (<table class="saas-table">) --}}
    {{-- 5. Floating Action Dropdown Menu & Modals --}}
</div>
@endsection

@push('scripts')
<script>
    // Alpine.js & JavaScript logic ONLY (Never put <script> inside @section('content'))
</script>
@endpush
```

---

## 2. Page Components & Design Standards

### A. Top Header Bar
- **আইকন + শিরোনাম (Title) + অ্যাকশন বাটন ONLY**।
- **কঠোর নিয়ম**: কোনো অবস্থাতেই হেডার বারে কোনো সাব-টাইটেল, ডেসক্রিপশন লাইন বা `<p>` ট্যাগ থাকবে না।

### B. KPI Summary Strip (৬টি ইনফো বক্স)
- গ্রিড: `grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2` (Strictly 6 Cards)।
- কার্ড প্যাডিং: `px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs`।
- লেবেল: `text-[9px] font-medium uppercase tracking-wider block truncate`।
- ভ্যালু: `text-[13px] font-bold font-mono leading-tight block`।
- আইকন ব্যাজ: `w-6 h-6 rounded-md text-[10px] border flex-shrink-0`।

### C. Search & Multi-Filter Toolbar & Action Buttons
- **কমপ্যাক্ট গ্রিড**: সার্চ বক্স (`pl-8 pr-3 py-1.5 text-xs`), ড্রপডাউন ফিল্টারসমূহ, `per_page` পেজিনেশন সিলেক্টর (10, 20, 50, 100), এবং সুনির্দিষ্ট ফিল্টার ও রিসেট বাটন সিকুয়েন্স।
- **কঠোর ফিল্টার ও রিসেট বাটন নীতি (Strict Filter & Reset Button Sequence & Styles)**:
  - বাটন দুটির অবস্থান সর্বদা ফিল্টার গ্রিডের শেষে একটি কমপ্যাক্ট ফ্লেক্স বক্সে (`flex items-center gap-1.5` অথবা `flex items-end gap-1.5`) থাকবে।
  - **সিকুয়েন্স**: সর্বদা প্রথমে **[Filter] বাটন** এবং পরে **[Reset] বাটন** বসবে (কখনো ভিন্ন সিকুয়েন্স বা এলোমেলো অর্ডার গ্রহণযোগ্য নয়)।
  - **Filter বাটন**: প্রাইমারি সায়ান থিম (`bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer`) সাথে আইকন `<i class="fas fa-filter text-[10px]"></i>` এবং লেবেল `<span>Filter</span>`।
  - **Reset বাটন**: নিউট্রাল স্লেট থিম (`bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer`) সাথে আইকন `<i class="fas fa-rotate-left text-[10px]"></i>` এবং লেবেল `<span>Reset</span>`।
  - **অলওয়েজ ভিজিবল রিসেট (Always Visible Reset)**: কোনো পিএইচপি কন্ডিশনাল হাইডিং ছাড়া রিসেট বাটন সর্বদা দৃশ্যমান থাকবে যাতে ইউজার যেকোনো সময় ১-ক্লিকে ফিল্টার ক্লিয়ার করে মূল তালিকায় ফিরে যেতে পারে।

### D. Master Compact Table (`.saas-table` Pure CSS System)
- টেবিল ট্যাগে অবশ্যই `<table class="saas-table">` ব্যবহার করতে হবে।
- **সেল বর্ডার ও হোভার সুরক্ষা**:
  - গ্লোবাল CSS `tbody tr:hover > td` দিয়ে ব্যাকগ্রাউন্ড হ্যান্ডেল করা আছে, তাই মাউস হোভার করলে বর্ডার অক্ষত থাকে।
  - প্রতিটি রো-তে জেব্রা স্ট্রাইপ (`tbody tr:nth-child(even)`), কমপ্যাক্ট SM প্যাডিং (`px-2.5 py-1.5`), এবং `white-space: nowrap` কার্যকর থাকে।
- **মিনিমাল ও এসেনশিয়াল কলাম নীতি (Strict Minimal Core Columns Policy - Max 5-7 Columns)**:
  - কোনো টেবিলে অপ্রয়োজনীয় বা সেকেন্ডারি কলাম দিয়ে টেবিলকে ভারী, ঘিঞ্জি বা স্ক্রল-নির্ভর করা সম্পূর্ণ নিষিদ্ধ।
  - প্রতিটি টেবিলে **সর্বোচ্চ ৫ থেকে ৭টি মূল আবশ্যক কলাম** থাকবে (যেমন: `#`, `Main Title/Identifier`, `Type/Category`, `Amount/Size/Primary Metric`, `Status`, `Action`)।
  - সেকেন্ডারি বা বিস্তারিত সকল তথ্য (যেমন: Checksum, Detailed Description, Sub-counts, Full payload, Log notes, Extra timestamps) টেবিলে দেখানো যাবে না; এগুলো সরাসরি **'View Details' মডাল** বা **প্রিন্ট ভিউ**-তে থাকবে।
  - টেবিলে অপ্রয়োজনীয় হরাইজন্টাল স্ক্রলিং পরিহার করে ক্লিন, কমপ্যাক্ট ও এক নজরে পড়ার মতো ইন্টারফেস নিশ্চিত করতে হবে।
- **সিঙ্গেল রো - সিঙ্গেল লাইন নীতি (Single-Line Rows)**:
  - কোনো সেলের ভেতর একাধিক লাইন বা স্ট্যাকড টেক্সট থাকবে না।
  - **সিঙ্গেল ডেটা / নো কনক্যাট বা ব্যাজ নীতি (Strict Single Data per Cell)**: নামের পাশে কোড (যেমন `[RES-001]`), ফোন নাম্বার (যেমন `(01819-xxxx)`), বা এক্সট্রা ব্যাজ কনক্যাট বা যুক্ত করা সম্পূর্ণ নিষিদ্ধ। সেলে শুধুমাত্র মূল ফিল্ড থাকবে; বিস্তারিত তথ্য 'View Details' মডালে দেখা যাবে।
  - অপ্রয়োজনীয় কলাম বাদ দিয়ে শুধুমাত্র মূল ফিল্ড টেবিলে থাকবে। বিস্তারিত তথ্য 'View Details' মডাল বা ড্রয়ারে দেখা যাবে।
- **নো রিপিটিটিভ ইউটিলিটি ক্লাস (Clean Markup)**:
  - `<th>` বা `<td>` তে বারবার `border border-slate-200`, `font-normal`, `text-[11px]` লেখার কোনো প্রয়োজন নেই; `.saas-table` স্বয়ংক্রিয়ভাবে সবকিছু হ্যান্ডেল করে।
  - সেলে শুধুমাত্র কলাম-স্পেসিফিক ক্লাস (যেমন `w-10`, `text-center`, `font-mono text-slate-800`, `text-cyan-800`) বসবে।
- **নো ডুপ্লিকেট অ্যাকশন বাটন টেবিলে (Strict Anti-Duplication Rule)**:
  - যেসব অ্যাকশন ৩-ডট ড্রপডাউন মেনুর মধ্যে অন্তর্ভুক্ত রয়েছে (যেমন `Test Connection`, `Test CoA`, `Copy Key`, `Edit`, `Delete`), সেগুলোর জন্য টেবিল রো-তে আলাদা কোনো বাটন বা কলাম রাখা যাবে না। সব অ্যাকশন সেন্ট্রালাইজড ড্রপডাউনে থাকবে।
- **নো jQuery DataTables কনফ্লিক্ট ও গ্লোবাল ক্লায়েন্ট-সাইড সর্টিং**:
  - সাধারণ ব্লেড টেবিলে jQuery DataTables ইনিশিয়ালাইজেশন পরিহার করে দ্রুতগতির নেটিভ লারাভেল পেজিনেশন ও `.saas-table` ব্যবহার করতে হবে।
  - **গ্লোবাল টেবিল সর্টিং নীতি**: সর্টিং সম্পূর্ণ ক্লায়েন্ট-সাইডে (ইন-মেমোরি) রিলোড ছাড়া গ্লোবাল ইঞ্জিনের মাধ্যমে পরিচালিত হবে। URL-এ কোনো `?sort=...` বা `&direction=...` কোয়েরি প্যারামিটার আসবে না। হেডার `<th>` এ ক্লিক করলেই স্বয়ংক্রিয়ভাবে কারেন্সি (৳, $), নাম্বার, টেক্সট এবং স্টেট অনুযায়ী দ্রুতগতিতে টেবিল সর্ট হবে। অ্যাকশন বা নন-সর্ট কলামের জন্য `class="no-sort"` ব্যবহার করতে হবে।
- **প্যাকেজ কলামে সর্বদা প্যাকেজ/মাইক্রোটিক প্রোফাইল নাম প্রদর্শন নীতি (Strict Package & MikroTik Profile Display Policy)**:
  - প্যাকেজ কলাম এবং ফিল্টার ড্রপডাউনে নেটওয়ার্ক প্যাকেজ টেবিলের (`https://somitysoft.com/admin/network/packages`) মাইক্রোটিক প্রোফাইল নামের সাথে মিল রেখে প্যাকেজ প্রোফাইল নাম (যেমন: `10Mbps`, `25Mbps`) প্রদর্শন করতে হবে (বাধ্যতামূলকভাবে `$customer->package_display_name` ব্যবহার করতে হবে)।

### E. Global Floating 3-Dot Action Menu (নিখুঁত ফ্লোটিং ড্রপডাউন)
- **বাটন আইকন**: ভার্টিক্যাল ৩-ডট (`fas fa-ellipsis-v text-[10px]`)।
- **পজিশনিং অ্যালগরিদম**:
  - বাটন বাউন্ডারি (`getBoundingClientRect()`) ডিটেক্ট করে রাইট-এলাইনমেন্ট (`right = Math.max(10, window.innerWidth - rect.right)`) এবং মাত্র ২ পিক্সেল টপ গ্যাপ (`top = Math.round(rect.bottom) + 2`) সেট করতে হবে:
  ```javascript
  toggleMenu(item, event) {
      if (this.activeMenu?.id === item.id) {
          this.activeMenu = null;
          return;
      }
      this.activeMenu = item;
      const rect = event.currentTarget.getBoundingClientRect();
      const dropdownHeight = 200;
      const right = Math.max(10, window.innerWidth - rect.right);
      let top = Math.round(rect.bottom) + 2;
      let bottom = 'auto';

      if (top + dropdownHeight > window.innerHeight) {
          top = 'auto';
          bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
      } else {
          top = `${top}px`;
      }

      this.menuPos = {
          top: top,
          bottom: bottom,
          right: `${right}px`,
          left: 'auto'
      };
  }
  ```
- **প্রফেশনাল অপশন নাম (Professional Action Titles)**:
  - গ্রুপ ১ (ডায়াগনস্টিক ও টেলিমেট্রি): `View Details`, `Test API Connection` / `Test CoA Socket`, `Copy Shared Secret` / `Copy Host IP`।
  - গ্রুপ ২ (কনফিগারেশন ও ম্যানেজমেন্ট): `Enable Gateway` / `Disable Gateway`, `Edit Configuration` / `Edit Gateway`, `Delete Gateway`।
- **ক্লিপিং সুরক্ষা**: `fixed z-50` এবং `@scroll.window` ও `@resize.window` এ বন্ধ হওয়া নিশ্চিত করতে হবে।

---

## 3. Production-Grade Natural Modals (ন্যাচারাল ও স্মার্ট মডাল ডিজাইন)
- **ব্যাকড্রপ**: `bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50`।
- **মডাল কন্টেইনার**: `bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden`।
- **সফট ন্যাচারাল হেডার (কঠোর নিয়ম: কোনো কুৎসিত ডার্ক হেডার নয়)**:
  - হেডার ব্যাকগ্রাউন্ড: `bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between`।
  - মিনি আইকন ব্যাজ: `w-7 h-7 rounded-lg bg-[theme]-50 text-[theme]-600 border border-[theme]-100 flex items-center justify-center text-xs flex-shrink-0`।
  - টাইটেল: `text-xs font-semibold text-slate-800` এবং সাবটাইটেল `text-[10.5px] text-slate-500 font-normal`।
  - ক্লোজ বাটন: `w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition`।
- **মডাল বডি**:
  - কমপ্যাক্ট ইনপুট ফিল্ডস: `bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500`।
  - কোড ও স্ক্রিপ্ট স্নsnippet: `bg-slate-900 text-cyan-300 font-mono text-[11px] p-3.5 rounded-lg border border-slate-800` সাথে 1-ক্লিক কপি সুবিধা।
  - ডিটেইলস গ্রিড: ২/৩ কলাম মেট্রিক কার্ডস (`p-2.5 bg-slate-50 rounded-lg border border-slate-200/80`)।
- **মডাল ফুটার**:
  - `px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between` (অথবা `justify-end gap-2`)।
  - ক্যানসেল/ক্লোজ বাটন: `border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg`।
  - প্রাইমারি বাটন: `bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs`।

---

## 4. Create / Edit Form Pages
- **লেআউট**: `max-w-4xl mx-auto space-y-4`।
- **টপ হেডার**: শুধুমাত্র ব্যাক অ্যারো বাটন (`fa-arrow-left`) এবং পরিষ্কার টাইটেল (কোনো সাবটাইটেল বা ডেসক্রিপশন কার্ড নয়)।
- **কার্ড সেকশন**: `bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4`।
- **ভ্যালিডেশন এরর হাইলাইট**:
  ```blade
  @error('field') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror
  ```
- **সাবমিট এরিয়া**: সেন্ট্রালাইজড সাবমিট বাটন (`bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-5 py-2 rounded-lg shadow-xs`)।

---

## 5. Network, MikroTik, FreeRADIUS & Controller Standards
- **MikroTik PPP Secret**:
  - গ্রাহক যখন তৈরি বা আপডেট করা হবে, তখন মাইক্রোটিকের `/ppp/secret` এ `remote-address` এবং `caller-id` সম্পূর্ণ ফাঁকা (Blank / Unset) থাকতে হবে।
  - মাইক্রোটিকের `/ppp/secret` এর `comment` ফিল্ডে গ্রাহকের ইউনিক আইডি (যেমন: `SO1001`, `SO1002`) বসবে।
  - গ্রাহক আইডি ফরম্যাট হবে: `[Company Prefix] + [Number]` (যেমন: SpeedNet Online এর জন্য `SO1001`, `SO1002`, ইত্যাদি)।
- **Controller Standard (`toggleStatus`)**:
  - অবশ্যই `$tenant = $this->getTenant();` নিশ্চিত করতে হবে।
  - মেথড সিগনেচারে `Request $request` গ্রহণ করে AJAX/JSON রেসপন্স হ্যান্ডেল করতে হবে:
  ```php
  public function toggleStatus(Request $request, $id)
  {
      $tenant = $this->getTenant();
      $model = $this->findModel($id);
      $model->is_active = !$model->is_active;
      $model->save();

      $stateText = $model->is_active ? 'enabled' : 'disabled';

      if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
          return response()->json([
              'success' => true,
              'is_active' => $model->is_active,
              'message' => "Item '{$model->name}' is now {$stateText}.",
          ]);
      }

      return back()->with('success', "Item '{$model->name}' has been {$stateText} successfully.");
  }
  ```

---

## 6. Dynamic Currency & Financial Formatting Standards (কঠোর ডাইনামিক কারেন্সি নীতি)
- **হার্ডকোডেড কারেন্সি সম্পূর্ণ নিষিদ্ধ (Strict Anti-Hardcoding Rule)**:
  - কোনো ব্লেড ভিউ, টেবিল কলাম, মেট্রিক কার্ড, মডাল, রিসিপ্ট বা ফর্মে কারেন্সি সিম্বল (যেমন `৳`, `$`, `€`, `BDT`, `Tk`) সরাসরি হার্ডকোড করা সম্পূর্ণ নিষিদ্ধ।
- **সার্ভার-সাইড ব্লেড ফরম্যাটিং (`@currency` Directive)**:
  - সকল আর্থিক ভ্যালু (যেমন: Paid Amount, Due, Monthly Bill, Discount, Total MRR, Wallet Balance, Expense, Revenue) রেন্ডার করার জন্য বাধ্যতামূলকভাবে গ্লোবাল ব্লেড ডিরেক্টিভ `@currency($amount)` ব্যবহার করতে হবে।
  ```blade
  {{-- সঠিক নিয়ম (Correct) --}}
  @currency($payment->amount)
  @currency($customer->due_amount)
  @currency($package->price)

  {{-- ভুল ও সম্পূর্ণ নিষিদ্ধ (Forbidden) --}}
  ৳ {{ number_format($payment->amount, 2) }}
  $ {{ $payment->amount }}
  ```
- **ক্লায়েন্ট-সাইড ও ফর্ম ইনপুট সিম্বল (`$currencySymbol`)**:
  - ফর্ম লেবেল, ইনপুট প্রিপেন্ড/অ্যাপেন্ড ব্যাজ, বা Alpine.js স্টেট এক্সপ্রেশনে ডাইনামিক ভেরিয়েবল `{{ $currencySymbol ?? '৳' }}` ব্যবহার করতে হবে:
  ```blade
  <label>Monthly Fee ({{ $currencySymbol ?? '৳' }})</label>
  <div class="input-prefix">{{ $currencySymbol ?? '৳' }}</div>
  ```
- **ম্যানেজমেন্ট সেটিংস ইন্টিগ্রেশন**:
  - সিস্টেম স্বয়ংক্রিয়ভাবে টেন্যান্ট বা গ্লোবাল সেটিংসের কারেন্সি কোড, সিম্বল, সিম্বল পজিশন (Left/Right) এবং দশমিক প্রিসিশন অনুসরণ করবে।

---

## 7. সহজ ও প্রাঞ্জল ভাষা নীতি (Simple & Plain Language Standard - No Complex Jargon)
- **সহজ ও ব্যবহারকারী-বান্ধব শব্দচয়ন**:
  - সমস্ত পেজের টাইটেল, কার্ড লেবেল, ফর্ম ফিল্ড, ড্রপডাউন অপশন এবং বাটন টেক্সট অত্যন্ত সহজ ও প্রাঞ্জল ভাষায় হতে হবে।
  - কোনো জটিল, অপ্রচলিত বা ভারী টেকনিক্যাল ইংরেজি শব্দ পরিহার করতে হবে।
  - উদাহরণ:
    - *জটিল (Avoid)*: Franchise Commercial & Upstream Billing Terms $\rightarrow$ *সহজ (Correct)*: Billing & Rates
    - *জটিল (Avoid)*: Available Usable Purchasing Telemetry $\rightarrow$ *সহজ (Correct)*: Available Balance
    - *জটিল (Avoid)*: Subscriber Provisioning Gateway $\rightarrow$ *সহজ (Correct)*: Customers / Subscribers
    - *জটিল (Avoid)*: Margin Rate Tier $\rightarrow$ *সহজ (Correct)*: Commission Rate
- **ক্লিন ইন্টারফেস ও অপ্রয়োজনীয় প্যারাগ্রাফ বর্জন**:
  - ফর্ম বা কার্ডের নিচে বড় বড় অপ্রয়োজনীয় ব্যাখ্যামূলক টেক্সট বা প্যারাগ্রাফ পরিহার করতে হবে। ইন্টারফেস যেন এক নজরে পরিষ্কার ও সরাসরি বোঝা যায়।

---

## 8. Multi-Language & Localization Standards (কঠোর বহুভাষিক ও ট্রান্সলেশন নীতি)
- **সকল UI টেক্সট ও লেবেল `__()` দিয়ে র‍্যাপ করা বাধ্যতামূলক (Mandatory Localization Wrapper)**:
  - সাইডবার মেনু, হেডার টাইটেল, টেবিল কলাম, ফিল্টার অপশন, বাটন, কার্ড লেবেল, মডাল এবং ফর্ম ইনপুটের প্রতিটি দৃশ্যমান স্ট্রিং অবশ্যই `{{ __('Text') }}` অথবা `@lang('Text')` এর মাধ্যমে রেন্ডার করতে হবে। কোনো টেক্সট বা লেবেল সরাসরি স্ট্যাটিক/হার্ডকোড করা নিষিদ্ধ।
- **বাধ্যতামূলক ট্রান্সলেশন ডিকশনারি সিঙ্ক (`lang/bn.json` ও `lang/en.json`)**:
  - প্রতিটি নতুন মেনু আইটেম, পেজ হেডার, স্ট্যাটাস ব্যাজ, নোটিফিকেশন বা বাটন যুক্ত করার সময় বাধ্যতামূলকভাবে `lang/bn.json` এবং `lang/en.json` উভয় ফাইলে সংশ্লিষ্ট কি (key) এবং স্ট্যান্ডার্ড বাংলা/ইংরেজি অনুবাদ যোগ করতে হবে।
  - কোনো অবস্থাতেই বাংলা লোকেলে কোনো মেনু বা লেবেল আনট্রান্সলেটেড (Raw English) অবস্থায় প্রদর্শন করা যাবে না।
- **আইএসপি ডোমেন-স্পেসিফিক মানসম্মত বাংলা পরিভাষা (Standard ISP Terminology)**:
  - Dashboard $\rightarrow$ ড্যাশবোর্ড
  - Customers / All Customers $\rightarrow$ গ্রাহক তালিকা / সকল গ্রাহক
  - Collections / Customer Payments $\rightarrow$ সংগৃহীত বিল / রসিদ
  - Due Customers $\rightarrow$ বকেয়া গ্রাহক
  - Billing & Invoices $\rightarrow$ বিল ও ইনভয়েস
  - Customer Invoices $\rightarrow$ গ্রাহকের বিল
  - Packages & Rates $\rightarrow$ প্যাকেজ ও রেট
  - Wallet & Balance $\rightarrow$ ওয়ালেট ও ব্যালেন্স
  - Staff & Team $\rightarrow$ কর্মকর্তা ও কর্মী
  - Support & Tickets $\rightarrow$ সাপোর্ট ও অভিযোগ
  - My Profile $\rightarrow$ আমার প্রোফাইল


