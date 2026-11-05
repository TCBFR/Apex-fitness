<?php

declare(strict_types=1);

trait PlanningScheduleController
{
    public function supprimer(int $numActivite, bool $serie): int
    {
        $creneau = $this->model->creneau($numActivite);
        if ($creneau === null) {
            throw new RuntimeException('Créneau introuvable.');
        }

        if ($serie && $creneau['id_serie'] !== null) {
            return $this->model->supprimerSerieAVenir((string) $creneau['id_serie']);
        }
        return $this->model->supprimerCreneau($numActivite);
    }

    public function modifier(array $d): void
    {
        $id = (int) ($d['creneau'] ?? 0);
        $actuel = $this->model->detailsCreneau($id);
        if (!$actuel) throw new RuntimeException('Créneau introuvable.');
        $titre = trim((string) ($d['titre'] ?? ''));
        $type = (string) ($d['type'] ?? '');
        $salle = (int) ($d['salle'] ?? 0);
        $coach = (int) ($d['coach'] ?? 0);
        $capacite = (int) ($d['capacite'] ?? 0);
        $debutTexte = trim((string) ($d['debut'] ?? ''));
        if ($debutTexte === '') {
            $debutTexte = trim((string) ($d['date'] ?? '')) . 'T' . trim((string) ($d['heure'] ?? ''));
        }
        $duree = min(480, max(15, (int) ($d['duree'] ?? 60)));
        if ($titre === '' || mb_strlen($titre) > 100) throw new RuntimeException('Le titre est obligatoire (100 caractères maximum).');
        if (!isset(self::TYPES[$type])) throw new RuntimeException('Type d’activité invalide.');
        if ($salle <= 0 || $coach <= 0 || $capacite <= 0) throw new RuntimeException('Vérifiez la salle, le coach et le nombre de places.');
        try { $debut = new DateTimeImmutable($debutTexte); }
        catch (Throwable $e) { throw new RuntimeException('Date de début invalide.'); }
        if ($debut->getTimestamp() < time()) throw new RuntimeException('La date de début doit être dans le futur.');
        $fin = $debut->modify("+$duree minutes");
        try {
            $this->model->begin();
            $capSalle = $this->model->capaciteSalleVerrouillee($salle);
            if ($capSalle === null || $capacite > $capSalle) throw new RuntimeException('La capacité indiquée dépasse celle de la salle.');
            if (!$this->model->coachValide($coach)) throw new RuntimeException('Coach introuvable.');
            if ($this->model->conflit($salle, $coach, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s'), $id)) {
                throw new RuntimeException('La salle ou le coach est déjà pris sur ce créneau.');
            }
            $this->model->modifierCreneau($id, $titre, $type, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s'), $capacite, $salle, $coach);
            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollback();
            if ($e instanceof RuntimeException) throw $e;
            throw new RuntimeException('Modification impossible : créneau en conflit ou erreur interne.');
        }
    }
}
