<?php
function icon(string $name, int $size = 20): string
{
    $p = [
        'home' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M9 13h6"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'message' => '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 10h8"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/>',
        'chevron' => '<path d="m9 6 6 6-6 6"/>',
        'down' => '<path d="m6 9 6 6 6-6"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'idcard' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.5-1.5 1.7-2 3-2s2.5.5 3 2M14 10h4M14 14h3"/>',
        'dumbbell' => '<path d="M6 7v10M18 7v10M3 9v6M21 9v6M6 12h12"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="m12 12 7-7M16 5h3v3"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
        'heart' => '<path d="M12 20s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.5-8 11-8 11z"/><path d="M7 12h3l1-2 2 4 1-2h3"/>',
        'lotus' => '<path d="M12 20c-4 0-8-2-9-6 3 0 6 1 9 6zM12 20c4 0 8-2 9-6-3 0-6 1-9 6z"/><path d="M12 20c-2-3-2-9 0-13 2 4 2 10 0 13z"/>',
        'chart' => '<path d="M3 21h18"/><path d="M6 21v-7M11 21V6M16 21v-10M21 21v-4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    ];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}