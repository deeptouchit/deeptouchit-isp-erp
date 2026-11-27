@props([
    'title' => '',
    'value' => '—',
    'icon' => 'fa-chart-simple',
    'theme' => 'slate',
    'meta' => null,
    'href' => null,
    'valueClass' => '',
])

@php
    $allowedThemes = ['blue', 'emerald', 'amber', 'rose', 'purple', 'cyan', 'slate'];
    $theme = in_array($theme, $allowedThemes, true) ? $theme : 'slate';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" aria-label="{{ $title }}: {{ $value }}" @endif class="owner-kpi-card{{ $href ? ' hover:border-slate-300 hover:shadow-sm transition focus:outline-none focus:ring-2 focus:ring-cyan-500/40' : '' }}">
    <div class="min-w-0 flex-1">
        <span class="owner-kpi-label">{{ $title }}</span>
        <span class="owner-kpi-value {{ $valueClass }}">{{ $value }}</span>
        @if($meta !== null)
            <span class="owner-kpi-meta">{{ $meta }}</span>
        @endif
    </div>
    <span class="owner-kpi-icon owner-kpi-icon-{{ $theme }}" aria-hidden="true">
        <i class="fas {{ $icon }}"></i>
    </span>
</{{ $tag }}>
