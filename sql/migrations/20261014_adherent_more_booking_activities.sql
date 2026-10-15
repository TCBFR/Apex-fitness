INSERT INTO salle (nomsalle, capacitesalle, localisationsalle, superficiesalle, etatsalle)
SELECT salles.nomsalle, salles.capacitesalle, salles.localisationsalle,
       salles.superficiesalle, 'Ouverte'
FROM (
    SELECT 'Studio cardio' AS nomsalle, 16 AS capacitesalle,
           'Rez-de-chaussée' AS localisationsalle, 85.00 AS superficiesalle
    UNION ALL SELECT 'Studio zen', 14, 'Étage 1', 70.00
    UNION ALL SELECT 'Zone cross', 12, 'Rez-de-chaussée', 95.00
    UNION ALL SELECT 'Plateau muscu', 20, 'Rez-de-chaussée', 140.00
    UNION ALL SELECT 'Studio danse', 16, 'Étage 1', 75.00
    UNION ALL SELECT 'Salle boxe', 12, 'Rez-de-chaussée', 90.00
) AS salles
WHERE NOT EXISTS (
    SELECT 1 FROM salle existante WHERE existante.nomsalle = salles.nomsalle
);

INSERT IGNORE INTO activite
    (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
     capacite_max, Numsalle, NumUsers)
SELECT cours.titre, cours.type_activite, cours.description, 'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), horaires.debut),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), horaires.fin),
       cours.capacite, salle.Numsalle, coach.NumUsers
FROM (
    SELECT 1 AS jour UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
    UNION ALL SELECT 13 UNION ALL SELECT 14
) AS jours
CROSS JOIN (
    SELECT 1 AS position, 'Musculation guidée' AS titre, 'musculation' AS type_activite,
           'Renforcement musculaire encadré en petit groupe.' AS description,
           'Plateau muscu' AS salle, 20 AS capacite
    UNION ALL SELECT 2, 'Cardio training', 'cardio',
           'Séance cardio progressive adaptée à tous les niveaux.', 'Studio cardio', 16
    UNION ALL SELECT 3, 'Yoga Flow', 'yoga',
           'Mobilité, respiration et renforcement doux.', 'Studio zen', 14
    UNION ALL SELECT 4, 'Cross Training', 'cross',
           'Entraînement fonctionnel en petit groupe.', 'Zone cross', 12
    UNION ALL SELECT 5, 'Boxe fitness', 'boxe',
           'Travail technique et cardio sur sac.', 'Salle boxe', 12
    UNION ALL SELECT 6, 'Danse fitness', 'danse',
           'Chorégraphies dynamiques pour bouger en musique.', 'Studio danse', 16
    UNION ALL SELECT 7, 'Pilates', 'pilates',
           'Renforcement profond et travail de posture.', 'Studio zen', 14
) AS cours
CROSS JOIN (
    SELECT '08:00:00' AS debut, '09:00:00' AS fin
    UNION ALL SELECT '18:30:00', '19:30:00'
) AS horaires
JOIN salle ON salle.nomsalle = cours.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe ON employe.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
    SELECT 1 FROM activite existante
    WHERE existante.Numsalle = salle.Numsalle
      AND existante.date_heure_debut = TIMESTAMP(
          DATE_ADD(CURRENT_DATE, INTERVAL jours.jour DAY), horaires.debut
      )
);
