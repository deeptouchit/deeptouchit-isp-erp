@extends('tenant.layouts.app')

@section('title', 'Dashboard - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Management'))

@section('content')
<div class="space-y-4">
    {{-- Dashboard Canvas (Empty) --}}
</div>
@endsection
