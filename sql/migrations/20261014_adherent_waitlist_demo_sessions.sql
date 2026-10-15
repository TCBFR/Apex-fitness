-- Trois cours visibles dans le planning et volontairement complets pour tester la file.
INSERT IGNORE INTO activite
    (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
     capacite_max, Numsalle, NumUsers)
SELECT demo.titre, demo.type_activite,
       'Séance de démonstration complète pour essayer la liste d’attente.',
       'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL demo.jour DAY), '14:30:00'),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL demo.jour DAY), '15:30:00'),
       1, salle.Numsalle, coach.NumUsers
FROM (
    SELECT 1 AS jour, 'Cardio HIIT — démo liste d’attente' AS titre,
           'cardio' AS type_activite, 'Studio cardio' AS salle
    UNION ALL SELECT 2, 'Pilates — démo liste d’attente', 'pilates', 'Studio zen'
    UNION ALL SELECT 3, 'Boxe fitness — démo liste d’attente', 'boxe', 'Salle boxe'
) AS demo
JOIN salle ON salle.nomsalle = demo.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe ON employe.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
    SELECT 1 FROM activite existante
    WHERE existante.titre = demo.titre
      AND existante.date_heure_debut = TIMESTAMP(
          DATE_ADD(CURRENT_DATE, INTERVAL demo.jour DAY), '14:30:00'
      )
);

-- Le compte Nabil est le membre de démonstration qui occupe l’unique place.
-- Awa peut ainsi rejoindre la liste d’attente avec son compte habituel.
INSERT IGNORE INTO reservation (NumUsers, Numactivite)
SELECT membre.NumUsers, activite.Numactivite
FROM utilisateur membre
JOIN adherent ON adherent.NumUsers = membre.NumUsers
JOIN activite ON activite.titre IN (
    'Cardio HIIT — démo liste d’attente',
    'Pilates — démo liste d’attente',
    'Boxe fitness — démo liste d’attente'
)
WHERE membre.mailUsers = 's.nabil@mail.fr'
  AND activite.date_heure_debut > NOW()
  AND activite.description = 'Séance de démonstration complète pour essayer la liste d’attente.'
  AND NOT EXISTS (
      SELECT 1 FROM reservation existante
      WHERE existante.NumUsers = membre.NumUsers
        AND existante.Numactivite = activite.Numactivite
  );
