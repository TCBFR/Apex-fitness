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

function atx_all_sessions(): array
{
    static $cache;
    if ($cache !== null) return $cache;
    $userId=(int)utilisateur()['NumUsers'];
    $q=db()->prepare("SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,
        s.nomsalle,u.prenomUsers AS coach,
        (SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>'Annulee') AS places,
        (SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.NumUsers=? AND r.statut_reservation<>'Annulee') AS mine,
        (SELECT COUNT(*) FROM liste_attente la WHERE la.Numactivite=a.Numactivite AND la.NumUsers=?) AS waitlisted
        FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers
        WHERE a.date_heure_debut>=DATE_SUB(NOW(),INTERVAL 14 DAY) AND a.date_heure_debut<DATE_ADD(NOW(),INTERVAL 60 DAY)
        ORDER BY a.date_heure_debut");
    $q->execute([$userId, $userId]); $out=[];
    foreach($q->fetchAll() as $row){
        $start=str_replace(' ','T',$row['date_heure_debut']); $end=str_replace(' ','T',$row['date_heure_fin']);
        $cat=(string)$row['type_activite'];
        $out[(string)$row['Numactivite']]=['id'=>(string)$row['Numactivite'],'title'=>$row['titre'],'category'=>$cat,
            'coach'=>$row['coach'],'coach_full'=>$row['coach'],'level'=>(string)($row['niveau'] ?? ''),'room'=>$row['nomsalle'],
            'date'=>substr($start,0,10),'start'=>$start,'end'=>$end,'capacity'=>(int)$row['capacite_max'],
            'base_booked'=>(int)$row['places'],'is_booked'=>(bool)$row['mine'],'is_waitlisted'=>(bool)$row['waitlisted']];
    }
    uasort($out,static fn($a,$b)=>strcmp($a['start'],$b['start']));
    return $cache=$out;
}
