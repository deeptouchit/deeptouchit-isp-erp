@extends('tenant.layouts.app')

@section('title', 'Open New Support Ticket')

@section('content')
        
        <!-- Error Alerts -->
        @if($errors->any())
            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-xs space-y-1">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-rose-600"></i>
                    <span>Please fix the following issues:</span>
                </div>
                <ul class="list-disc list-inside text-[11px] font-normal pl-4">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 sm:p-6">
            <form action="{{ route('tenant.tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <!-- Subject -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Subject / Problem Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="subject" 
                           value="{{ old('subject') }}" 
                           required 
                           placeholder="Brief summary of your inquiry (e.g., SMS Gateway API balance not updating)" 
                           class="w-full rounded-lg border border-slate-200 p-2.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-slate-50 focus:bg-white transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Department -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Department <span class="text-rose-500">*</span>
                        </label>
                        <select name="department" required class="w-full rounded-lg border border-slate-200 p-2.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-slate-50 focus:bg-white transition">
                            <option value="technical" {{ old('department') == 'technical' ? 'selected' : '' }}>Technical & Server Support</option>
                            <option value="billing" {{ old('department') == 'billing' ? 'selected' : '' }}>Billing & Subscription</option>
                            <option value="sms_gateway" {{ old('department') == 'sms_gateway' ? 'selected' : '' }}>SMS Gateway & Delivery</option>
                            <option value="payment_gateway" {{ old('department') == 'payment_gateway' ? 'selected' : '' }}>Payment Gateway Integration</option>
                            <option value="general" {{ old('department') == 'general' ? 'selected' : '' }}>General Query</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Priority <span class="text-rose-500">*</span>
                        </label>
                        <select name="priority" required class="w-full rounded-lg border border-slate-200 p-2.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-slate-50 focus:bg-white transition">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low (Minor question)</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium (Standard request)</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High (Operations affected)</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent (Critical system downtime)</option>
                        </select>
                    </div>
                </div>

                <!-- Detailed Message -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Detailed Message / Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="message" 
                              rows="6" 
                              required 
                              placeholder="Please describe your issue in detail, including steps to reproduce, error messages, or transaction IDs..."
                              class="w-full rounded-lg border border-slate-200 p-3 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-slate-50 focus:bg-white transition leading-relaxed">{{ old('message') }}</textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Upload Attachments (Screenshots, Error Logs, PDF)
                    </label>
                    <input type="file" 
                           name="attachments[]" 
                           multiple 
                           class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400 mt-1">Supported formats: JPG, PNG, PDF, ZIP, TXT (Max 10MB per file)</p>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="{{ route('tenant.tickets.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-2">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Submit Ticket</span>
                    </button>
                </div>

            </form>
        </div>
@endsection
