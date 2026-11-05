<?php

if ($maintenance && $action === 'commenter_ticket') {
    $nummaint = filter_var($_POST['maintenance'] ?? null, FILTER_VALIDATE_INT);
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    try {
      if (!$nummaint || $nummaint < 1 || $commentaire === '' || mb_strlen($commentaire) > 2000) {
        throw new RuntimeException('Le commentaire est obligatoire et ne doit pas dépasser 2 000 caractères.');
      }
      $pdo->beginTransaction();
      $ticketQuery = $pdo->prepare(
        'SELECT NumUsers_technicien, Numstatut FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
      );
      $ticketQuery->execute([$nummaint]);
      $ticket = $ticketQuery->fetch(PDO::FETCH_ASSOC);
      if (!$ticket || (int) $ticket['NumUsers_technicien'] !== (int) $moi) {
        throw new RuntimeException('Seul le technicien chargé du ticket peut ajouter un commentaire.');
      }
      $statutQuery = $pdo->prepare(
        "SELECT libelle_statut FROM statut
         WHERE Numstatut = ? AND type_statut = 'MAINTENANCE'"
      );
      $statutQuery->execute([(int) $ticket['Numstatut']]);
      $statutActuel = $statutQuery->fetchColumn();
      if (!$statutActuel || in_array($statutActuel, ['Terminee', 'À valider'], true)) {
        throw new RuntimeException('Ce ticket ne peut plus recevoir de commentaire.');
      }
      $pdo->prepare(
        'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)'
      )->execute([$commentaire, $nummaint, (int) $moi, (int) $ticket['Numstatut']]);
      $pdo->commit();
      journalise('COMMENTAIRE_MAINTENANCE', 'Nummaint=' . $nummaint);
      flash('succes', 'Commentaire ajouté au suivi du ticket.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('Ajout de commentaire au ticket impossible : ' . $exception->getMessage());
      flash('erreur', 'Le commentaire n’a pas pu être ajouté.');
    }
    redirige('employe/espace.php?onglet=maintenance&ticket=' . (int) $nummaint);
  }
