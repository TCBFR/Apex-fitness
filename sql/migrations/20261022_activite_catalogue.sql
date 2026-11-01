CREATE TABLE IF NOT EXISTS activite_catalogue (
  Numcatalogue   INT AUTO_INCREMENT PRIMARY KEY,
  titre          VARCHAR(100) NOT NULL,
  type_activite  VARCHAR(20) NOT NULL,
  description    TEXT NOT NULL,
  image_path     VARCHAR(255) NULL,
  date_creation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_catalogue_type (type_activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO activite_catalogue (titre, type_activite, description)
SELECT a.titre, a.type_activite, COALESCE(a.description, '')
FROM activite a
JOIN (
  SELECT type_activite, MIN(Numactivite) AS Numactivite
  FROM activite
  WHERE type_activite IN ('cross', 'pilates', 'abdos', 'boxe', 'danse', 'yoga')
  GROUP BY type_activite
) premiere ON premiere.Numactivite = a.Numactivite
WHERE NOT EXISTS (SELECT 1 FROM activite_catalogue);

INSERT INTO activite_catalogue (titre, type_activite, description)
SELECT defaults_catalogue.titre, defaults_catalogue.type_activite, defaults_catalogue.description
FROM (
  SELECT 'Cross Training' AS titre, 'cross' AS type_activite,
    'Le Cross Training enchaîne des exercices qui mobilisent les bras, les épaules, le dos, les abdominaux et les jambes. Ce travail complet développe la force, l’endurance et la coordination.' AS description
  UNION ALL SELECT 'Pilates', 'pilates', 'Le Pilates cible les abdominaux profonds, le dos et les muscles stabilisateurs. Les mouvements contrôlés améliorent la posture, la souplesse et la conscience du corps.'
  UNION ALL SELECT 'Abdos et gainage', 'abdos', 'Renforcement progressif du centre du corps pour améliorer la stabilité, la posture et le maintien, quel que soit votre niveau.'
  UNION ALL SELECT 'Boxe fitness', 'boxe', 'La boxe renforce les bras, les épaules, le dos et les jambes. Les frappes développent le cardio et aident à évacuer le stress et les tensions.'
  UNION ALL SELECT 'Danse fitness', 'danse', 'Des chorégraphies dynamiques pour travailler les jambes, les fessiers et le cardio tout en améliorant la coordination et l’endurance.'
  UNION ALL SELECT 'Yoga', 'yoga', 'Le yoga associe respiration, mobilité et postures pour renforcer le corps en douceur, améliorer la souplesse et relâcher les tensions.'
) defaults_catalogue
WHERE NOT EXISTS (
  SELECT 1 FROM activite_catalogue existante
  WHERE existante.type_activite = defaults_catalogue.type_activite
);

UPDATE activite_catalogue
SET description = CASE type_activite
  WHEN 'cross' THEN 'Le Cross Training enchaîne des exercices qui mobilisent les bras, les épaules, le dos, les abdominaux et les jambes. Ce travail complet développe la force, l’endurance et la coordination, tout en donnant de l’énergie.'
  WHEN 'pilates' THEN 'Le Pilates cible les abdominaux profonds, le dos et les muscles stabilisateurs. Les mouvements contrôlés améliorent la posture, la souplesse et la conscience du corps, tout en aidant à relâcher les tensions.'
  WHEN 'abdos' THEN 'Renforcement du centre du corps, tous niveaux.'
  WHEN 'boxe' THEN 'La boxe renforce les bras, les épaules et le dos tout en développant le cardio. Les frappes aident à évacuer le stress et les tensions.'
  WHEN 'danse' THEN 'La danse fitness fait travailler les jambes, les fessiers et les abdominaux tout en stimulant le cardio et la coordination.'
  WHEN 'yoga' THEN 'Le yoga associe respiration et postures pour améliorer la souplesse, renforcer le corps en douceur et relâcher les tensions.'
  ELSE description
END
WHERE type_activite IN ('cross', 'pilates', 'abdos', 'boxe', 'danse', 'yoga');
