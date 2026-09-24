@extends('layouts.app')

@section('title', 'SomitySoft ISP Automation Platform')

@section('content')
<div class="min-h-screen flex flex-col justify-between bg-slate-50 text-slate-800 selection:bg-blue-600 selection:text-white"
     x-data="{ 
        mobileMenu: false,
        demoCounter: 1
     }">
    
    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center font-black text-xl shadow-md text-white">
                    <i class="fas fa-network-wired text-lg"></i>
                </div>
                <div>
                    <span class="font-extrabold text-base tracking-tight text-slate-900 block">SomitySoft ISP</span>
                    <span class="text-[10px] font-semibold text-blue-600 tracking-wider uppercase block -mt-1">Next-Gen Automation</span>
                </div>
            </div>

            <!-- Desktop Nav Items -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
                <a href="#features" class="hover:text-slate-900 transition">বৈশিষ্ট্যসমূহ</a>
                <a href="#architecture" class="hover:text-slate-900 transition">পোর্টাল আর্কিটেকচার</a>
                <a href="#mikrotik" class="hover:text-slate-900 transition">মাইক্রোটিক অটোমেশন</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('owner.login') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm flex items-center gap-2">
                    <i class="fas fa-crown text-amber-300"></i>
                    <span>ওনার লগইন</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 py-16 flex flex-col justify-center items-center text-center">
        
        <!-- Live Stack Pill Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-semibold mb-6 shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Tailwind CSS + Alpine.js + Blade Powered</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 max-w-3xl leading-tight">
            আধুনিক ও সম্পূর্ণ অটোমেটেড <span class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 bg-clip-text text-transparent">ISP ম্যানেজমেন্ট ও বিলিং</span> প্ল্যাটফর্ম
        </h1>

        <p class="mt-4 text-sm sm:text-base text-slate-600 max-w-2xl leading-relaxed">
            MikroTik Realtime Sync, OLT/FTTH Monitoring, Reseller/Collector Mobile POS, এবং Customer Self-Care সহ ক্লাউড-নেটিভ মাল্টি-টেন্যান্ট সলিউশন।
        </p>

        <!-- Interactive Grid (Natural White Cards) -->
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 w-full max-w-5xl text-left">
            
            <!-- Card 1 -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs hover:shadow-app-lg hover:border-blue-500/40 transition group">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-crown text-amber-500"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-sm mb-1.5">সুপার এডমিন (Owner)</h3>
                <p class="text-xs text-slate-500 leading-relaxed">ক্লাউড ল্যান্ডলর্ড ড্যাশবোর্ড, টেন্যান্ট ম্যানেজমেন্ট, সাবস্ক্রিপশন প্ল্যান ও ইনভয়েস।</p>
            </div>

            <!-- Card 2 -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs hover:shadow-app-lg hover:border-emerald-500/40 transition group">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-sm mb-1.5">সেন্ট্রাল ISP এডমিন</h3>
                <p class="text-xs text-slate-500 leading-relaxed">গ্রাহক, বিলিং, অটো-লক/আনলক, ব্রাঞ্চ ও একাউন্টিং সম্পূর্ণ নিয়ন্ত্রণ।</p>
            </div>

            <!-- Card 3 -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs hover:shadow-app-lg hover:border-amber-500/40 transition group">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-sm mb-1.5">রিসেলার ও কালেক্টর POS</h3>
                <p class="text-xs text-slate-500 leading-relaxed">মোবাইলে তাৎক্ষণিক বিল কালেকশন, ব্লুটুথ প্রিন্ট ও ব্যালেন্স রিচার্জ।</p>
            </div>

            <!-- Card 4 -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-xs hover:shadow-app-lg hover:border-purple-500/40 transition group">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-sm mb-1.5">কাস্টমার সেলফ-কেয়ার</h3>
                <p class="text-xs text-slate-500 leading-relaxed">গ্রাহকের নিজস্ব মোবাইল পোর্টাল, ১-ক্লিকে বিকাশ/নগদে বিল পরিশোধ ও টিকিট।</p>
            </div>

        </div>

        <!-- Alpine.js Live Interactive Test Widget (Clean White) -->
        <div class="mt-8 p-4 rounded-2xl bg-white border border-slate-200/80 max-w-md w-full flex items-center justify-between text-xs shadow-xs">
            <span class="text-slate-600 font-medium">Alpine.js ইন্টারঅ্যাক্টিভ কাউন্টার:</span>
            <div class="flex items-center gap-2">
                <button @click="demoCounter--" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition">-</button>
                <span class="font-bold px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg text-blue-600" x-text="'কাউন্টার: ' + demoCounter"></span>
                <button @click="demoCounter++" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition">+</button>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200/80 bg-white py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} SomitySoft ISP Automation Platform. All Rights Reserved.
    </footer>

</div>
@endsection
