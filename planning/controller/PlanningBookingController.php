<?php

declare(strict_types=1);

trait PlanningBookingController
{
    public function reserver(int $numUsers, int $numActivite): string
    {
        $fiche = $this->model->ficheAdherent($numUsers);
        if ($fiche === null || $fiche['libelle_statut'] !== 'Actif') {
            throw new RuntimeException(
                'Votre compte est ' . mb_strtolower($fiche['libelle_statut'] ?? 'inconnu')
                . '. Régularisez votre abonnement à l\'accueil avant de réserver.'
            );
        }
        if (
            $fiche['Numabo'] === null
            || $fiche['statut_souscription'] !== 'En cours'
            || $fiche['type_abo'] === null
        ) {
            throw new RuntimeException(
                'Un abonnement actif est nécessaire pour réserver une séance. Choisissez ou renouvelez votre formule.'
            );
        }

        try {
            $this->model->begin();

            // Verrou sur le creneau : deux reservations simultanees ne depassent pas la capacite.
            $creneau = $this->model->verrouillerCreneau($numActivite);
            if ($creneau === null) {
                throw new RuntimeException('Ce créneau n\'existe plus.');
            }
            if (strtotime($creneau['date_heure_debut']) < time()) {
                throw new RuntimeException('Ce créneau est déjà passé.');
            }

            if ($this->model->placesPrises($numActivite) >= (int) $creneau['capacite_max']) {
                throw new RuntimeException('Le créneau est complet (' . (int) $creneau['capacite_max'] . ' places).');
            }

            $existante = $this->model->reservationExistante($numUsers, $numActivite);
            if ($existante && $existante['statut_reservation'] !== 'Annulee') {
                throw new RuntimeException('Vous avez déjà une réservation sur ce créneau.');
            }

            if ($fiche['type_abo'] === 'FREEMIUM') {
                $quota = (int) $fiche['nb_reservations_max_mois'];
                if ($this->model->reservationsDuMois($numUsers, $creneau['date_heure_debut']) >= $quota) {
                    throw new RuntimeException("L'offre Freemium est limitée à $quota réservations par mois.");
                }
            }

            if ($existante) {   // une annulation precedente est reactivee
                $this->model->reactiverReservation((int) $existante['Numres']);
            } else {
                $this->model->creerReservation($numUsers, $numActivite);
            }

            $this->model->commit();
            return (string) $creneau['titre'];
        } catch (RuntimeException $e) {
            $this->model->rollback();
            throw $e;
        } catch (Throwable $e) {
            $this->model->rollback();
            throw new RuntimeException('La réservation a échoué. Réessayez dans un instant.');
        }
    }
}
