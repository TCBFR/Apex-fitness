<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Paris');
const CATEGORIES = [
    'musculation'=>['label'=>'Musculation','color'=>'#4F7DFF','icon'=>'dumbbell'],
    'cardio'=>['label'=>'Cardio','color'=>'#FF4D5E','icon'=>'heart'],
    'yoga'=>['label'=>'Yoga & Bien-être','color'=>'#A266FF','icon'=>'lotus'],
    'cross'=>['label'=>'Cross Training','color'=>'#FF9F1C','icon'=>'flame'],
    'pilates'=>['label'=>'Pilates','color'=>'#D45BFF','icon'=>'activity'],
    'abdos'=>['label'=>'Abdos & Gainage','color'=>'#4ADE80','icon'=>'target'],
    'boxe'=>['label'=>'Boxe','color'=>'#FF9F1C','icon'=>'flame'],
    'danse'=>['label'=>'Danse','color'=>'#D45BFF','icon'=>'activity'],
];
const MAIN_FILTERS = ['musculation','cardio','yoga','cross'];
const LEVELS = ['Débutant','Intermédiaire','Avancé'];
const CAT_IMAGES = [
    'musculation'=>'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=600&q=70',
    'cardio'=>'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?auto=format&fit=crop&w=600&q=70',
    'yoga'=>'https://images.unsplash.com/photo-1616279969096-54b228f5f103?auto=format&fit=crop&w=600&q=70',
    'cross'=>'https://images.unsplash.com/photo-1648542036561-e1d66a5ae2b1?auto=format&fit=crop&w=600&q=70',
    'pilates'=>'https://images.unsplash.com/photo-1616279969096-54b228f5f103?auto=format&fit=crop&w=600&q=70',
    'abdos'=>'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=600&q=70',
    'boxe'=>'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=70',
    'danse'=>'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=600&q=70',
];
const HERO_IMAGE = 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1400&q=70';

function atx_coaches(): array
{
    $coaches = db()->query("SELECT DISTINCT u.prenomUsers, u.nomUsers FROM activite a JOIN utilisateur u ON u.NumUsers=a.NumUsers ORDER BY u.prenomUsers,u.nomUsers")
        ->fetchAll(PDO::FETCH_KEY_PAIR);
    return $coaches ?: ['Lucas'=>'Lucas Moreau','Sophie'=>'Sophie Lambert','Emma'=>'Emma Girard','Thomas'=>'Thomas Martin'];
}

function atx_all_sessions(): array
{
    static $cache;
    if ($cache !== null) return $cache;
    $userId=(int)utilisateur()['NumUsers'];
    $q=db()->prepare("SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,
        s.nomsalle,u.prenomUsers AS coach,
        (SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>'Annulee') AS places,
        (SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.NumUsers=? AND r.statut_reservation<>'Annulee') AS mine
        FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers
        WHERE a.date_heure_debut>=DATE_SUB(NOW(),INTERVAL 14 DAY) AND a.date_heure_debut<DATE_ADD(NOW(),INTERVAL 60 DAY)
        ORDER BY a.date_heure_debut");
    $q->execute([$userId]); $out=[];
    foreach($q->fetchAll() as $row){
        $start=str_replace(' ','T',$row['date_heure_debut']); $end=str_replace(' ','T',$row['date_heure_fin']);
        $cat=isset(CATEGORIES[$row['type_activite']])?$row['type_activite']:'cardio';
        $out[(string)$row['Numactivite']]=['id'=>(string)$row['Numactivite'],'title'=>$row['titre'],'category'=>$cat,
            'coach'=>$row['coach'],'coach_full'=>$row['coach'],'level'=>$row['niveau']?:'Tous niveaux','room'=>$row['nomsalle'],
            'date'=>substr($start,0,10),'start'=>$start,'end'=>$end,'capacity'=>(int)$row['capacite_max'],
            'base_booked'=>(int)$row['places'],'is_booked'=>(bool)$row['mine']];
    }
    uasort($out,static fn($a,$b)=>strcmp($a['start'],$b['start']));
    return $cache=$out;
}

/** Séances d'exemple affichées uniquement quand la période n'a aucune séance en base. */
function atx_demo_sessions(string $start, string $end): array
{
    $template = [
        ['09:00', '10:00', 'Musculation', 'musculation', 'Lucas', 'Lucas Moreau', 'Plateau muscu'],
        ['12:30', '13:30', 'Cardio HIIT', 'cardio', 'Sophie', 'Sophie Lambert', 'Studio cardio'],
        ['17:30', '18:30', 'Yoga', 'yoga', 'Emma', 'Emma Girard', 'Studio zen'],
        ['18:00', '19:00', 'Cross Training', 'cross', 'Thomas', 'Thomas Martin', 'Zone cross'],
    ];
    $first = (new DateTimeImmutable($start))->setTime(0, 0);
    $last = (new DateTimeImmutable($end))->setTime(0, 0);
    $items = [];
    for ($day = $first; $day < $last; $day = $day->modify('+1 day')) {
        $weekday = (int)$day->format('N');
        if (!in_array($weekday, [1, 2, 3, 4, 5, 6], true)) continue;
        foreach ($template as $index => [$from, $to, $title, $category, $coach, $coachFull, $room]) {
            if (($weekday + $index) % 3 === 0) continue;
            $begin = $day->format('Y-m-d') . 'T' . $from . ':00';
            $finish = $day->format('Y-m-d') . 'T' . $to . ':00';
            $id = 'demo-' . $day->format('Ymd') . '-' . $index;
            $items[] = [
                'id' => $id, 'title' => $title, 'start' => $begin, 'end' => $finish,
                'extendedProps' => [
                    'id' => $id, 'category' => $category, 'coach' => $coach, 'coach_full' => $coachFull,
                    'level' => 'Tous niveaux', 'room' => $room, 'date' => $day->format('Y-m-d'),
                    'start' => $begin, 'end' => $finish, 'capacity' => 12, 'booked' => 5 + ($index % 4),
                    'remaining' => 7 - ($index % 4), 'is_booked' => false, 'is_past' => strtotime($begin) < time(),
                    'color' => CATEGORIES[$category]['color'], 'image' => CAT_IMAGES[$category], 'is_demo' => true,
                ],
            ];
        }
    }
    return $items;
}

function atx_performance_data(): array
{
    $series=[];
    foreach ([7,30,90] as $days) {
        $labels=[];
        $values=[];
        for ($i=$days-1; $i>=0; $i--) {
            $labels[]=(new DateTimeImmutable("-$i days"))->format('Y-m-d');
            $progress=($days-1-$i)/max(1,$days-1);
            $values[]=round(24+68*$progress+5*sin($i*.8)+2*cos($i*1.7),1);
        }
        $series[$days]=['labels'=>$labels,'values'=>$values];
    }

    return [
        'demo'=>true,
        'kpis'=>[
            ['icon'=>'dumbbell','label'=>'Séances réalisées','value'=>'12','unit'=>'','trend'=>'+33%'],
            ['icon'=>'flame','label'=>'Calories brûlées','value'=>'4 320','unit'=>'','trend'=>'+27%'],
            ['icon'=>'timer','label'=>'Durée moyenne','value'=>'62','unit'=>'min','trend'=>'+18%'],
        ],
        'goal'=>['done'=>3,'target'=>4],
        'series'=>$series,
        'body'=>[
            ['icon'=>'scale','label'=>'Poids','value'=>'78','unit'=>'kg','delta'=>'-2 kg'],
            ['icon'=>'droplet','label'=>'Masse grasse','value'=>'12','unit'=>'%','delta'=>'-1,5 %'],
            ['icon'=>'dumbbell','label'=>'Muscle','value'=>'36','unit'=>'kg','delta'=>'+1,8 kg'],
            ['icon'=>'heart','label'=>'Fréquence cardiaque (repos)','value'=>'58','unit'=>'bpm','delta'=>'-6 bpm'],
        ],
        'objectives'=>[
            ['icon'=>'calendar','label'=>'Séances','text'=>'3 / 4','pct'=>75,'color'=>'#CCFF00'],
            ['icon'=>'droplet','label'=>'Hydratation','text'=>'2,5 L / 3 L','pct'=>83,'color'=>'#22D3EE'],
            ['icon'=>'moon','label'=>'Sommeil','text'=>'7 h / 8 h','pct'=>87,'color'=>'#7C5CFF'],
        ],
        'history'=>[
            ['title'=>'Dos / Biceps','meta'=>'-2 days','duration'=>'1h05','cat'=>'musculation'],
            ['title'=>'HIIT','meta'=>'-4 days','duration'=>'45 min','cat'=>'cardio'],
            ['title'=>'Jambes','meta'=>'-6 days','duration'=>'1h10','cat'=>'cross'],
            ['title'=>'Full Body','meta'=>'-8 days','duration'=>'1h00','cat'=>'abdos'],
        ],
    ];
}
