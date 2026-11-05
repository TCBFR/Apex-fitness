<?php

$moi    = utilisateur()['NumUsers'];
$onglet = $_GET['onglet'] ?? 'tableau';
$pdo    = db();
$faq = new FaqController();
$maintenance = est_technicien_maintenance();
$accueil = fonction_employe((int) $moi) === 'Accueil';
$messageController = new MessageController();
if ($maintenance && $onglet === 'materiel') SchemaInventaireEquipement::ensure($pdo);
$transitionsMaintenance = [
  'En attente' => ['En cours', 'En attente de piece', 'À valider'],
  'En cours' => ['En attente de piece', 'À valider'],
  'En attente de piece' => ['En cours', 'À valider'],
];
$planning = new PlanningController();

$pdo->exec(
  'CREATE TABLE IF NOT EXISTS intervention (
    Numintervention INT AUTO_INCREMENT PRIMARY KEY,
    date_intervention DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT NULL,
    Nummaint INT NOT NULL,
    NumUsers INT NOT NULL,
    Numstatut INT NOT NULL,
    FOREIGN KEY (Nummaint) REFERENCES demande_maintenance(Nummaint) ON DELETE CASCADE,
    FOREIGN KEY (NumUsers) REFERENCES employe(NumUsers),
    FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
  ) ENGINE=InnoDB'
);
$colonnesDemande = $pdo->query("SHOW COLUMNS FROM demande_maintenance LIKE 'NumUsers_technicien'")->fetch();
if (!$colonnesDemande) {
  $pdo->exec('ALTER TABLE demande_maintenance ADD COLUMN NumUsers_technicien INT NULL AFTER NumUsers');
}
$cleTechnicien = $pdo->query(
  "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'demande_maintenance'
     AND COLUMN_NAME = 'NumUsers_technicien'
     AND REFERENCED_TABLE_NAME = 'employe'
   LIMIT 1"
)->fetchColumn();
if (!$cleTechnicien) {
  $pdo->exec(
    'ALTER TABLE demande_maintenance
     ADD CONSTRAINT fk_demande_maintenance_technicien
     FOREIGN KEY (NumUsers_technicien) REFERENCES employe(NumUsers) ON DELETE SET NULL'
  );
}
if ($maintenance && !in_array($onglet, ['tableau', 'maintenance', 'materiel', 'profil'], true)) {
  redirige('employe/espace.php?onglet=tableau');
}
if ($maintenance && $onglet === 'profil') {
  redirige('employe/profil.php');
}
if (!$maintenance && $onglet === 'maintenance') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'suivi-tickets') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'messages') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'reservations') {
  redirige('employe/espace.php?onglet=tableau');
}

/* =====================================================================
   ACTIONS
   ===================================================================== */
