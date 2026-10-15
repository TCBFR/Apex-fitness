INSERT INTO notification_adherent (NumUsers, Numres, type_notification, titre, message)
SELECT u.NumUsers, NULL, 'DEMO', 'Bienvenue dans vos notifications',
       'Ceci est une notification de démonstration. Les rappels de séance apparaîtront ici dans les 24 heures précédant un cours réservé.'
FROM utilisateur u
JOIN adherent ad ON ad.NumUsers = u.NumUsers
WHERE u.mailUsers = 'awa.diallo@mail.fr'
  AND NOT EXISTS (
      SELECT 1 FROM notification_adherent n
      WHERE n.NumUsers = u.NumUsers AND n.type_notification = 'DEMO'
  );
