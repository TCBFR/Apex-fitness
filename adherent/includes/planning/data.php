<?php
/**
 * ATHLETIX — données statiques (en dur) : catégories, coachs, planning type, performances.
 */
date_default_timezone_set('Europe/Paris');

const ATX_USER = ['firstname' => 'Samuel', 'lastname' => 'Petit', 'initial' => 'S.'];

const CATEGORIES = [
    'musculation' => ['label' => 'Musculation',      'color' => '#4F7DFF', 'icon' => 'dumbbell'],
    'cardio'      => ['label' => 'Cardio',           'color' => '#FF4D5E', 'icon' => 'heart'],
    'yoga'        => ['label' => 'Yoga & Bien-être', 'color' => '#A266FF', 'icon' => 'lotus'],
    'cross'       => ['label' => 'Cross Training',   'color' => '#FF9F1C', 'icon' => 'flame'],
    'pilates'     => ['label' => 'Pilates',          'color' => '#D45BFF', 'icon' => 'activity'],
    'abdos'       => ['label' => 'Abdos & Gainage',  'color' => '#4ADE80', 'icon' => 'target'],
];

/** Catégories affichées dans les filtres "pills" principaux (comme sur la maquette). */
const MAIN_FILTERS = ['musculation', 'cardio', 'yoga', 'cross'];

const COACHES = [
    'Lucas'  => 'Lucas Moreau',
    'Sophie' => 'Sophie Lambert',
    'Emma'   => 'Emma Girard',
    'Thomas' => 'Thomas Martin',
];

const LEVELS = ['Débutant', 'Intermédiaire', 'Avancé'];

const CAT_IMAGES = [
    'musculation' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=600&q=70',
    'cardio'      => 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?auto=format&fit=crop&w=600&q=70',
    'yoga'        => 'https://images.unsplash.com/photo-1616279969096-54b228f5f103?auto=format&fit=crop&w=600&q=70',
    'cross'       => 'https://images.unsplash.com/photo-1648542036561-e1d66a5ae2b1?auto=format&fit=crop&w=600&q=70',
    'pilates'     => 'https://images.unsplash.com/photo-1616279969096-54b228f5f103?auto=format&fit=crop&w=600&q=70',
    'abdos'       => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=600&q=70',
];

const HERO_IMAGE = 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1400&q=70';

/**
 * Planning type hebdomadaire.
 * [jour (1=lun..7=dim), début, fin, titre, catégorie, coach, inscrits, capacité, niveau, salle]
 */
const WEEKLY_TEMPLATE = [
    [1, '09:00', '10:00', 'Body Sculpt',       'abdos',       'Lucas',  6, 15, 'Tous niveaux',  'Studio A'],
    [1, '12:30', '13:30', 'Musculation',       'musculation', 'Thomas', 3, 10, 'Intermédiaire', 'Plateau muscu'],
    [1, '17:00', '18:00', 'Cross Training',    'cross',       'Lucas',  4, 12, 'Avancé',        'Zone Cross'],
    [2, '09:00', '10:00', 'Cross Training',    'cross',       'Sophie', 8, 12, 'Intermédiaire', 'Zone Cross'],
    [2, '12:30', '13:30', 'Cycling',           'cardio',      'Lucas',  9, 15, 'Tous niveaux',  'Studio Bike'],
    [2, '17:00', '18:00', 'Cardio HIIT',       'cardio',      'Sophie', 8, 12, 'Avancé',        'Studio B'],
    [3, '08:30', '10:00', 'Musculation',       'musculation', 'Thomas', 4, 10, 'Tous niveaux',  'Plateau muscu'],
    [3, '12:30', '13:30', 'Yoga',              'yoga',        'Emma',   6, 15, 'Débutant',      'Studio Zen'],
    [3, '17:00', '18:30', 'Musculation',       'musculation', 'Thomas', 6, 10, 'Avancé',        'Plateau muscu'],
    [4, '09:00', '10:00', 'Yoga',              'yoga',        'Emma',  10, 15, 'Tous niveaux',  'Studio Zen'],
    [4, '12:30', '13:30', 'Cross Training',    'cross',       'Sophie',12, 12, 'Avancé',        'Zone Cross'],
    [4, '17:00', '18:00', 'Pilates',           'pilates',     'Emma',   9, 15, 'Débutant',      'Studio Zen'],
    [5, '09:00', '10:00', 'Cardio HIIT',       'cardio',      'Lucas',  7, 12, 'Intermédiaire', 'Studio B'],
    [5, '12:30', '13:30', 'Full Body',         'abdos',       'Lucas',  7, 12, 'Tous niveaux',  'Studio A'],
    [5, '17:00', '18:00', 'Full Body',         'abdos',       'Lucas',  7, 12, 'Intermédiaire', 'Studio A'],
    [6, '10:00', '11:00', 'Abdos & Gainage',   'abdos',       'Thomas', 5, 12, 'Tous niveaux',  'Studio A'],
    [6, '14:00', '15:00', 'Cardio',            'cardio',      'Sophie', 5, 12, 'Débutant',      'Studio B'],
    [6, '18:00', '19:00', 'Yoga',              'yoga',        'Emma',  11, 15, 'Tous niveaux',  'Studio Zen'],
    [7, '10:00', '11:00', 'Yoga',              'yoga',        'Emma',   8, 15, 'Débutant',      'Studio Zen'],
    [7, '16:00', '17:00', 'Mobilité & Stretching', 'yoga',    'Sophie', 9, 15, 'Tous niveaux',  'Studio Zen'],
];

/** Génère les séances de 2 semaines passées à 5 semaines à venir, à partir du planning type. */
function atx_all_sessions(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $now = new DateTimeImmutable('now');
    $monday = $now->setISODate((int)$now->format('o'), (int)$now->format('W'), 1)->setTime(0, 0);
    $out = [];
    for ($w = -2; $w <= 5; $w++) {
        foreach (WEEKLY_TEMPLATE as $i => $t) {
            [$dow, $start, $end, $title, $cat, $coach, $booked, $cap, $level, $room] = $t;
            $date = $monday->modify(sprintf('%+d days', $w * 7 + $dow - 1))->format('Y-m-d');
            $id = $date . '-' . str_replace(':', '', $start) . '-' . $i;
            if ($booked < $cap) {
                // légère variation déterministe d'une semaine à l'autre
                $booked = max(0, min($cap - 1, $booked + (crc32($id) % 3) - 1));
            }
            $out[$id] = [
                'id'          => $id,
                'title'       => $title,
                'category'    => $cat,
                'coach'       => $coach,
                'coach_full'  => COACHES[$coach],
                'level'       => $level,
                'room'        => $room,
                'date'        => $date,
                'start'       => "{$date}T{$start}:00",
                'end'         => "{$date}T{$end}:00",
                'capacity'    => $cap,
                'base_booked' => $booked,
            ];
        }
    }
    uasort($out, fn($a, $b) => strcmp($a['start'], $b['start']));
    return $cache = $out;
}

/** Données statiques de la page "Mes performances". */
function atx_performance_data(): array
{
    $series = [];
    foreach ([7, 30, 90] as $days) {
        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = new DateTimeImmutable("-{$i} days");
            $p = ($days - 1 - $i) / max(1, $days - 1);
            $labels[] = $d->format('Y-m-d');
            $values[] = round(22 + 70 * $p + 6 * sin($i * 0.9) + 3 * cos($i * 2.3), 1);
        }
        $series[$days] = ['labels' => $labels, 'values' => $values];
    }

    return [
        'kpis' => [
            ['icon' => 'dumbbell', 'label' => 'Séances réalisées', 'value' => '12',    'unit' => '',    'trend' => '+33%'],
            ['icon' => 'flame',    'label' => 'Calories brûlées',  'value' => '4 320', 'unit' => '',    'trend' => '+27%'],
            ['icon' => 'timer',    'label' => 'Durée moyenne',     'value' => '62',    'unit' => 'min', 'trend' => '+18%'],
        ],
        'goal'   => ['done' => 3, 'target' => 4],
        'series' => $series,
        'body'   => [
            ['icon' => 'scale',    'label' => 'Poids',                       'value' => '78', 'unit' => 'kg',  'delta' => '-2 kg'],
            ['icon' => 'droplet',  'label' => 'Masse grasse',                'value' => '12', 'unit' => '%',   'delta' => '-1,5 %'],
            ['icon' => 'dumbbell', 'label' => 'Muscle',                      'value' => '36', 'unit' => 'kg',  'delta' => '+1,8 kg'],
            ['icon' => 'heart',    'label' => 'Fréquence cardiaque (repos)', 'value' => '58', 'unit' => 'bpm', 'delta' => '-6 bpm'],
        ],
        'objectives' => [
            ['icon' => 'calendar', 'label' => 'Séances',    'text' => '3 / 4',      'pct' => 75, 'color' => '#CCFF00'],
            ['icon' => 'droplet',  'label' => 'Hydratation', 'text' => '2,5 L / 3 L', 'pct' => 83, 'color' => '#22D3EE'],
            ['icon' => 'moon',     'label' => 'Sommeil',    'text' => '7 h / 8 h',  'pct' => 87, 'color' => '#7C5CFF'],
        ],
        'history' => [
            ['title' => 'Dos / Biceps', 'meta' => '-2 days',  'duration' => '1h05',   'cat' => 'musculation'],
            ['title' => 'HIIT',         'meta' => '-4 days',  'duration' => '45 min', 'cat' => 'cardio'],
            ['title' => 'Jambes',       'meta' => '-6 days',  'duration' => '1h10',   'cat' => 'cross'],
            ['title' => 'Full Body',    'meta' => '-8 days',  'duration' => '1h00',   'cat' => 'abdos'],
        ],
    ];
}
