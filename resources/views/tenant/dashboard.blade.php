@extends('tenant.layouts.app')

@section('title', 'Admin Dashboard - ' . ($tenant->company_name ?? $tenant->name))

{{-- 1. Page Specific Stylesheets --}}
@push('styles')

@endpush

{{-- 2. Main Dashboard Content Canvas --}}
@section('content')
<div class="space-y-4" x-data="tenantDashboard()">



</div>
@endsection

{{-- 3. Page Specific Scripts --}}
@push('scripts')

@endpush
