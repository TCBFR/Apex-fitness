-- Complète le planning avec cinq créneaux espacés par jour pendant quatre semaines.
-- Les cours existants et réservés sont conservés; aucun nouveau créneau ne les chevauche.
INSERT IGNORE INTO activite
    (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
     capacite_max, Numsalle, NumUsers)
SELECT cours.titre, cours.type_activite, cours.description, 'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), creneaux.debut),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), creneaux.fin),
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
CROSS JOIN (
    SELECT 1 AS numero, '07:00:00' AS debut, '08:00:00' AS fin
    UNION ALL SELECT 2, '10:00:00', '11:00:00'
    UNION ALL SELECT 3, '13:00:00', '14:00:00'
    UNION ALL SELECT 4, '16:00:00', '17:00:00'
    UNION ALL SELECT 5, '19:00:00', '20:00:00'
) AS creneaux
JOIN (
    SELECT 1 AS position, 'Musculation guidée' AS titre, 'musculation' AS type_activite,
           'Renforcement musculaire encadré en petit groupe.' AS description,
           'Plateau muscu' AS salle, 20 AS capacite
    UNION ALL SELECT 2, 'Cardio HIIT', 'cardio',
           'Séance cardio progressive adaptée à tous les niveaux.', 'Studio cardio', 16
    UNION ALL SELECT 3, 'Yoga Flow', 'yoga',
           'Mobilité, respiration et renforcement doux.', 'Studio zen', 14
    UNION ALL SELECT 4, 'Cross Training', 'cross',
           'Entraînement fonctionnel en petit groupe.', 'Zone cross', 12
    UNION ALL SELECT 5, 'Pilates', 'pilates',
           'Renforcement profond et travail de posture.', 'Studio zen', 14
    UNION ALL SELECT 6, 'Abdos et gainage', 'abdos',
           'Renforcement du centre du corps, tous niveaux.', 'Plateau muscu', 20
    UNION ALL SELECT 7, 'Boxe fitness', 'boxe',
           'Travail technique et cardio sur sac.', 'Salle boxe', 12
    UNION ALL SELECT 8, 'Danse fitness', 'danse',
           'Chorégraphies dynamiques pour bouger en musique.', 'Studio danse', 16
) AS cours ON cours.position = MOD((jours.jour - 1) * 5 + creneaux.numero - 1, 8) + 1
JOIN salle ON salle.nomsalle = cours.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe ON employe.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
    SELECT 1
    FROM activite existante
    WHERE existante.date_heure_debut < TIMESTAMP(
              DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), creneaux.fin
          )
      AND existante.date_heure_fin > TIMESTAMP(
              DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), creneaux.debut
          )
);
