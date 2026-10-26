@extends('tenant.layouts.app')

@section('title', 'Import Resellers - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-4 max-w-5xl mx-auto" x-data="resellerImportManager()">

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-file-excel"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Import Resellers (Excel / CSV)</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span>Current Partners: {{ $totalResellers }}</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Download Sample Template Button -->
            <a href="{{ route('tenant.resellers.import-template') }}" 
               class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-download text-[10px]"></i>
                <span>Download Sample Template</span>
            </a>

            <!-- Back to Reseller Directory -->
            <a href="{{ route('tenant.resellers.index') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Back to Directory</span>
            </a>
        </div>
    </div>

    <!-- Alert / Flash Messages -->
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2.5 text-xs text-emerald-800 font-medium">
            <i class="fas fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2.5 text-xs text-rose-800 font-medium">
            <i class="fas fa-circle-exclamation text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 2. Main Upload Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <form action="{{ route('tenant.resellers.import.process') }}" method="POST" enctype="multipart/form-data" @submit="isUploading = true" class="p-5 space-y-5">
            @csrf

            <!-- Step A: Drag & Drop Upload Zone -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Select or Drop File (.csv, .xlsx, .txt)
                </label>
                
                <div class="border-2 border-dashed rounded-xl p-6 text-center transition cursor-pointer relative"
                     :class="dragOver ? 'border-cyan-500 bg-cyan-50/40' : (fileName ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-300 hover:border-slate-400 bg-slate-50/50')"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     @drop.prevent="handleDrop($event)">
                    
                    <input type="file" 
                           id="fileInput" 
                           name="file" 
                           accept=".csv,.txt"
                           required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                           @change="handleFileSelect($event)">

                    <template x-if="!fileName">
                        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl shadow-xs">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">
                                    Click to browse or drag and drop your Excel / CSV file here
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Supports standard UTF-8 CSV, Comma-Separated values up to 10MB
                                </p>
                            </div>
                        </div>
                    </template>

                    <template x-if="fileName">
                        <div class="flex items-center justify-center gap-3 pointer-events-none">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-slate-800" x-text="fileName"></p>
                                <p class="text-[10.5px] text-emerald-700 font-mono font-semibold" x-text="fileSize"></p>
                            </div>
                            <button type="button" 
                                    @click.stop="clearFile()" 
                                    class="pointer-events-auto ml-2 w-6 h-6 rounded-full bg-slate-200 hover:bg-rose-100 hover:text-rose-600 text-slate-500 flex items-center justify-center text-[10px] transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step B: Import Options Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
                <!-- 1. Duplicate Action -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Duplicate Action
                    </label>
                    <select name="duplicate_action" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="skip" selected>Skip Duplicates</option>
                        <option value="update">Update Existing by Code/Mobile</option>
                    </select>
                </div>

                <!-- 2. Default Billing Type -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Default Billing Type
                    </label>
                    <select name="default_billing_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="prepaid" selected>Prepaid (Wallet Deduct)</option>
                        <option value="postpaid">Postpaid (Monthly Invoiced)</option>
                    </select>
                </div>

                <!-- 3. Default Commission Rate -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Default Commission Rate (%)
                    </label>
                    <input type="number" 
                           name="default_commission_rate" 
                           value="20" 
                           min="0" 
                           max="100" 
                           step="0.5" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>

                <!-- 4. Default Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Initial Status
                    </label>
                    <select name="default_status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('tenant.resellers.import-template') }}" class="text-xs text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1.5">
                    <i class="fas fa-file-excel"></i>
                    <span>Need template? Download sample CSV</span>
                </a>

                <button type="submit" 
                        class="px-5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-2 cursor-pointer"
                        :disabled="!fileName || isUploading">
                    <template x-if="!isUploading">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-upload text-[11px]"></i>
                            <span>Start Bulk Import</span>
                        </div>
                    </template>
                    <template x-if="isUploading">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-spinner fa-spin text-[11px]"></i>
                            <span>Importing Data...</span>
                        </div>
                    </template>
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Reference Guide: Expected Column Format -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs">
                    <i class="fas fa-table-list"></i>
                </div>
                <h3 class="text-xs font-bold text-slate-800">Supported Columns & Excel Header Mapping</h3>
            </div>
            <span class="text-[10.5px] text-slate-400 font-mono">Case-insensitive headers supported</span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-32">Column Header</th>
                        <th class="w-20 text-center">Required</th>
                        <th class="w-36">Example Value</th>
                        <th>Description / Accepted Values</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold text-slate-800">Reseller Name</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Required</span></td>
                        <td class="font-mono text-cyan-800">SpeedNet Online Ltd</td>
                        <td class="text-slate-600 text-xs">Company or Sub-ISP franchise trade name</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Code</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">RES-001</td>
                        <td class="text-slate-600 text-xs">Unique Partner Code (auto-generated if empty: RES-001, RES-002)</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Prefix</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">SN</td>
                        <td class="text-slate-600 text-xs">Customer/PPPoE username prefix (e.g. SN for SN1001)</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Contact Person</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="text-slate-700">Md. Rahim Ahmed</td>
                        <td class="text-slate-600 text-xs">Full name of the franchise owner or manager</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Mobile Phone</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">01711223344</td>
                        <td class="text-slate-600 text-xs">11-digit Bangladesh mobile number for SMS notifications</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Email</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">rahim@speednet.com</td>
                        <td class="text-slate-600 text-xs">Contact email address</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Billing Type</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-purple-700 font-semibold">prepaid</td>
                        <td class="text-slate-600 text-xs">Either <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">prepaid</code> (wallet deduction) or <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">postpaid</code></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Commission Rate (%)</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-emerald-700 font-bold">25.00</td>
                        <td class="text-slate-600 text-xs">Commission discount percentage for package retail price</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Credit Limit</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">5000.00</td>
                        <td class="text-slate-600 text-xs">Allowed overdraft or credit balance limit</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Monthly Panel Charge</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-slate-700">500.00</td>
                        <td class="text-slate-600 text-xs">Recurring monthly panel or software maintenance fee</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800">Status</td>
                        <td class="text-center"><span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600">Optional</span></td>
                        <td class="font-mono text-emerald-700 font-semibold">active</td>
                        <td class="text-slate-600 text-xs">Either <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">active</code> or <code class="bg-slate-100 px-1 py-0.5 rounded text-[10px]">inactive</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerImportManager() {
    return {
        dragOver: false,
        fileName: '',
        fileSize: '',
        isUploading: false,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.setFile(file);
            }
        },

        handleDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                document.getElementById('fileInput').files = event.dataTransfer.files;
                this.setFile(file);
            }
        },

        setFile(file) {
            this.fileName = file.name;
            const sizeInKb = (file.size / 1024).toFixed(1);
            if (sizeInKb > 1024) {
                this.fileSize = (sizeInKb / 1024).toFixed(2) + ' MB';
            } else {
                this.fileSize = sizeInKb + ' KB';
            }
        },

        clearFile() {
            this.fileName = '';
            this.fileSize = '';
            document.getElementById('fileInput').value = '';
        }
    };
}
</script>
@endpush
