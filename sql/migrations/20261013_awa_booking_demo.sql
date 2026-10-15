-- Active le compte de démonstration Awa et ajoute des cours futurs réservables.
UPDATE adherent ad
JOIN utilisateur u ON u.NumUsers = ad.NumUsers
SET ad.Date_inscriptionadh = CURRENT_DATE,
    ad.Date_finadh = DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)
WHERE u.mailUsers = 'awa.diallo@mail.fr';

INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription)
SELECT u.NumUsers, a.Numabo, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH), 'En cours'
FROM utilisateur u
JOIN abonnement a ON a.libelle_abo = 'Premium' AND a.actif_abo = 1
WHERE u.mailUsers = 'awa.diallo@mail.fr'
  AND NOT EXISTS (
    SELECT 1 FROM souscrire s
    WHERE s.NumUsers = u.NumUsers AND s.Numabo = a.Numabo
      AND s.statut_souscription = 'En cours'
  );

INSERT INTO salle (nomsalle, capacitesalle, localisationsalle, superficiesalle, etatsalle)
SELECT salles.nomsalle, salles.capacitesalle, salles.localisationsalle, salles.superficiesalle, 'Ouverte'
FROM (
  SELECT 'Studio cardio' AS nomsalle, 16 AS capacitesalle, 'Rez-de-chaussée' AS localisationsalle, 85.00 AS superficiesalle
  UNION ALL SELECT 'Studio zen', 14, 'Étage 1', 70.00
  UNION ALL SELECT 'Zone cross', 12, 'Rez-de-chaussée', 95.00
  UNION ALL SELECT 'Plateau muscu', 20, 'Rez-de-chaussée', 140.00
) AS salles
WHERE NOT EXISTS (SELECT 1 FROM salle s WHERE s.nomsalle = salles.nomsalle);

INSERT INTO activite
  (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
   capacite_max, Numsalle, NumUsers)
SELECT cours.titre, cours.type_activite, cours.description, 'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.debut),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.fin),
       cours.capacite, s.Numsalle, coach.NumUsers
FROM (
  SELECT 'Cardio HIIT' AS titre, 'cardio' AS type_activite,
         'Circuit cardio encadré, adapté à tous les niveaux.' AS description,
         1 AS jour, '09:00:00' AS debut, '10:00:00' AS fin, 16 AS capacite, 'Studio cardio' AS salle
  UNION ALL SELECT 'Yoga Flow', 'yoga', 'Mobilité, respiration et renforcement doux.',
         2, '12:00:00', '13:00:00', 14, 'Studio zen'
  UNION ALL SELECT 'Cross Training', 'cross', 'Séance fonctionnelle en petit groupe.',
         3, '17:30:00', '18:30:00', 12, 'Zone cross'
  UNION ALL SELECT 'Renforcement musculaire', 'musculation', 'Travail complet guidé par le coach.',
         4, '18:00:00', '19:00:00', 20, 'Plateau muscu'
) AS cours
JOIN salle s ON s.nomsalle = cours.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe e ON e.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
  SELECT 1 FROM activite existante
  WHERE existante.Numsalle = s.Numsalle
    AND existante.date_heure_debut = TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.debut)
);
