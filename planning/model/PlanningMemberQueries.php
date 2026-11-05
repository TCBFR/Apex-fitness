<?php

declare(strict_types=1);

trait PlanningMemberQueries
{
    public function ficheAdherent(int $numUsers): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT ad.NumUsers, ad.matriculeadh, ad.Date_inscriptionadh, ad.Date_finadh,
                    CASE WHEN ad.Date_finadh IS NULL OR ad.Date_finadh >= CURDATE()
                         THEN 'Actif' ELSE 'Expire' END AS libelle_statut,
                    ab.Numabo, ab.libelle_abo, o.type_offre AS type_abo,
                    su.date_debut, su.date_fin, su.statut_souscription,
                    fr.nb_reservations_max_mois
             FROM adherent ad
             LEFT JOIN souscrire su ON su.NumUsers = ad.NumUsers
                                   AND su.statut_souscription = 'En cours'
                                   AND su.date_debut <= CURRENT_DATE
                                   AND (su.date_fin IS NULL OR su.date_fin >= CURRENT_DATE)
                                   AND su.date_debut = (SELECT MAX(s2.date_debut) FROM souscrire s2
                                                         WHERE s2.NumUsers = ad.NumUsers
                                                           AND s2.statut_souscription = 'En cours'
                                                           AND s2.date_debut <= CURRENT_DATE
                                                           AND (s2.date_fin IS NULL OR s2.date_fin >= CURRENT_DATE))
             LEFT JOIN abonnement ab ON ab.Numabo   = su.Numabo
             LEFT JOIN offre o       ON o.Numoffre  = ab.Numoffre
             LEFT JOIN freemium fr   ON fr.Numoffre = o.Numoffre
             WHERE ad.NumUsers = ?
             LIMIT 1"
        );
        $st->execute([$numUsers]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
