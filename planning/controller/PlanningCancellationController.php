<?php

declare(strict_types=1);

trait PlanningCancellationController
{
    public function annuler(int $numUsers, int $numRes): void
    {
        $res = $this->model->reservationDeAdherent($numRes, $numUsers);
        if ($res === null) {
            throw new RuntimeException('Réservation introuvable.');
        }
        if ($res['statut_reservation'] === 'Annulee') {
            throw new RuntimeException('Cette réservation est déjà annulée.');
        }
        if (strtotime($res['date_heure_debut']) - time() < 2 * 3600) {
            throw new RuntimeException('L\'annulation n\'est plus possible moins de 2 heures avant le cours.');
        }
        $this->model->annulerReservation($numRes);
    }
}
