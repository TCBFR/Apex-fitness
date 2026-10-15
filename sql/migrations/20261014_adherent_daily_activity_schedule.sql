-- Supprime uniquement les cours de démonstration ajoutés en double et sans inscription.
DELETE FROM activite
WHERE titre IN (
    'Musculation guidée', 'Cardio training', 'Yoga Flow', 'Cross Training',
    'Boxe fitness', 'Danse fitness', 'Pilates'
)
  AND TIME(date_heure_debut) IN ('08:00:00', '18:30:00')
  AND NOT EXISTS (
      SELECT 1 FROM reservation r WHERE r.Numactivite = activite.Numactivite
  )
  AND NOT EXISTS (
      SELECT 1 FROM liste_attente la WHERE la.Numactivite = activite.Numactivite
  );

-- Ajoute au plus un cours par jour libre pendant les quatre prochaines semaines.
-- Les catégories correspondent exactement aux filtres du planning.
INSERT IGNORE INTO activite
    (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
     capacite_max, Numsalle, NumUsers)
SELECT cours.titre, cours.type_activite, cours.description, 'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), cours.debut),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), cours.fin),
       cours.capacite, salle.Numsalle, coach.NumUsers
FROM (
    SELECT 1 AS jour UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
    UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16
    UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
    UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28
) AS jours
JOIN (
    SELECT 1 AS position, 'Musculation guidée' AS titre, 'musculation' AS type_activite,
           'Renforcement musculaire encadré en petit groupe.' AS description,
           'Plateau muscu' AS salle, 20 AS capacite, '08:00:00' AS debut, '09:00:00' AS fin
    UNION ALL SELECT 2, 'Cardio HIIT', 'cardio',
           'Séance cardio progressive adaptée à tous les niveaux.',
           'Studio cardio', 16, '12:00:00', '13:00:00'
    UNION ALL SELECT 3, 'Yoga Flow', 'yoga',
           'Mobilité, respiration et renforcement doux.',
           'Studio zen', 14, '17:30:00', '18:30:00'
    UNION ALL SELECT 4, 'Cross Training', 'cross',
           'Entraînement fonctionnel en petit groupe.',
           'Zone cross', 12, '19:00:00', '20:00:00'
    UNION ALL SELECT 5, 'Pilates', 'pilates',
           'Renforcement profond et travail de posture.',
           'Studio zen', 14, '09:00:00', '10:00:00'
    UNION ALL SELECT 6, 'Abdos et gainage', 'abdos',
           'Renforcement du centre du corps, tous niveaux.',
           'Plateau muscu', 20, '12:30:00', '13:30:00'
    UNION ALL SELECT 7, 'Boxe fitness', 'boxe',
           'Travail technique et cardio sur sac.',
           'Salle boxe', 12, '18:00:00', '19:00:00'
    UNION ALL SELECT 8, 'Danse fitness', 'danse',
           'Chorégraphies dynamiques pour bouger en musique.',
           'Studio danse', 16, '17:00:00', '18:00:00'
) AS cours ON cours.position = MOD(jours.jour - 1, 8) + 1
JOIN salle ON salle.nomsalle = cours.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe ON employe.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
    SELECT 1 FROM activite existante
    WHERE DATE(existante.date_heure_debut) = DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY)
);
