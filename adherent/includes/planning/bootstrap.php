<?php
/** Fonctions partagées par le planning et la liste des réservations. */
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/../../../config/function.php';
exige_role('ADHERENT');

/** Séance enrichie avec l'état utilisateur (réservée, places, passée). */
function atx_view(array $s): array
{
    $isBooked = (bool)($s['is_booked'] ?? false);
    $count = (int)$s['base_booked'];
    $cat = CATEGORIES[$s['category']];
    return $s + [
        'booked'         => $count,
        'remaining'      => $s['capacity'] - $count,
        'is_booked'      => $isBooked,
        'is_past'        => $s['start'] < date('Y-m-d\TH:i:s'),
        'color'          => $cat['color'],
        'category_label' => $cat['label'],
        'image'          => CAT_IMAGES[$s['category']],
    ];
}

function atx_sessions_view(?callable $filter = null): array
{
    $out = [];
    foreach (atx_all_sessions() as $s) {
        if ($filter === null || $filter($s)) $out[] = atx_view($s);
    }
    return $out;
}

function atx_find(string $id): ?array
{
    $all = atx_all_sessions();
    return isset($all[$id]) ? atx_view($all[$id]) : null;
}

/** Prochaine séance réservée (ou, à défaut, prochaine séance disponible). */
function atx_next_session(): ?array
{
    $upcoming = atx_sessions_view(fn($s) => $s['start'] > date('Y-m-d\TH:i:s'));
    foreach ($upcoming as $s) if ($s['is_booked']) return $s;
    foreach ($upcoming as $s) if ($s['remaining'] > 0) return $s;
    return null;
}

// e() est déjà défini par config/function.php : ne le redéclarer que s'il n'existe pas
if (!function_exists('e')) {
    function e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/** Formatage de dates en français. */
function fr_date(string $iso, string $mode = 'long'): string
{
    $d = new DateTimeImmutable($iso);
    $days = ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'];
    $daysLong = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $monthsLong = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dow = (int)$d->format('N') - 1;
    $m = (int)$d->format('n') - 1;
    return match ($mode) {
        'short' => ucfirst($days[$dow]) . ' ' . $d->format('j') . ' ' . $months[$m],
        'day'   => $d->format('j') . ' ' . $months[$m] . ' ' . $d->format('Y'),
        default => ucfirst($days[$dow]) . ' ' . $d->format('j') . ' ' . $monthsLong[$m] . ' ' . $d->format('Y'),
    };
}

function hm(string $iso): string
{
    return substr($iso, 11, 5);
}
