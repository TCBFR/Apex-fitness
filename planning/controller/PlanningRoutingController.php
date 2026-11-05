<?php

declare(strict_types=1);

trait PlanningRoutingController
{
    public static function estAction(string $mode, string $action): bool
    {
        return in_array($action, self::ACTIONS[$mode] ?? [], true);
    }

    public function traiter(string $mode, int $moi, string $retour, ?string $retourAnnulation = null): void
    {
        $action = (string) ($_POST['action'] ?? '');
        if (!self::estAction($mode, $action)) {
            return;
        }

        try {
            switch ($action) {
                case 'reserver':
                    $num   = (int) ($_POST['creneau'] ?? 0);
                    $titre = $this->reserver($moi, $num);
                    journalise('RESERVATION', 'Numactivite=' . $num);
                    flash('succes', 'Réservation enregistrée pour ' . $titre . '.');
                    break;

                case 'annuler':
                    $numRes = (int) ($_POST['reservation'] ?? 0);
                    $this->annuler($moi, $numRes);
                    journalise('ANNULATION', 'Numres=' . $numRes);
                    flash('succes', 'Réservation annulée, la place est de nouveau disponible.');
                    $retour = $retourAnnulation ?? $retour;
                    break;

                case 'creer_creneau':
                    $res = $this->creer($_POST);
                    journalise('CREATION_CRENEAU', $res['crees'] . ' creneau(x)');
                    flash('succes', $res['crees'] > 1
                        ? $res['crees'] . ' créneaux ajoutés au planning.'
                        : 'Créneau ajouté au planning.');
                    if ($res['ignores']) {
                        flash('info', 'Ignorés (salle ou coach déjà pris) : ' . implode(', ', $res['ignores']) . '.');
                    }
                    break;

                case 'supprimer_creneau':
                    $num = (int) ($_POST['creneau'] ?? 0);
                    $n   = $this->supprimer($num, ($_POST['portee'] ?? 'un') === 'serie');
                    journalise('SUPPRESSION_CRENEAU', 'Numactivite=' . $num . ' (' . $n . ')');
                    flash('succes', $n > 1 ? "$n créneaux supprimés." : 'Créneau supprimé.');
                    break;

                case 'modifier_creneau':
                    $this->modifier($_POST);
                    journalise('MODIFICATION_CRENEAU', 'Numactivite=' . (int) ($_POST['creneau'] ?? 0));
                    flash('succes', 'Le créneau a été modifié.');
                    break;
            }
        } catch (RuntimeException $ex) {
            flash('erreur', $ex->getMessage());
        }

        redirige($retour);
        exit;
    }
}
