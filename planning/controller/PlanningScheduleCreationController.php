<?php

declare(strict_types=1);

trait PlanningScheduleCreationController
{
    public function creer(array $d): array
    {
        $creation = $this->preparerOccurrences($d);
        $titre = $creation['titre'];
        $type = $creation['type'];
        $salle = $creation['salle'];
        $cap = $creation['capacite'];
        $coach = $creation['coach'];
        $occurrences = $creation['occurrences'];
        $idSerie = $creation['serie'];
        $repeter = $creation['repeter'];
        $fmt = 'Y-m-d H:i:s';

        try {
            $this->model->begin();

            $capSalle = $this->model->capaciteSalleVerrouillee($salle);
            if ($capSalle === null) {
                throw new RuntimeException('Salle introuvable.');
            }
            if ($cap > $capSalle) {
                throw new RuntimeException("La salle accueille au maximum $capSalle personnes.");
            }
            if (!$this->model->coachValide($coach)) {
                throw new RuntimeException('Coach introuvable.');
            }

            $crees = 0;
            $ignores = [];
            foreach ($occurrences as [$deb, $fin]) {
                if ($this->model->conflit($salle, $coach, $deb->format($fmt), $fin->format($fmt))) {
                    if (!$repeter) {
                        throw new RuntimeException('La salle ou le coach est déjà pris sur ce créneau.');
                    }
                    $ignores[] = $deb->format('d/m/Y H\hi');
                    continue;
                }
                $this->model->insererCreneau($titre, $type, $deb->format($fmt), $fin->format($fmt),
                                             $cap, $salle, $coach, $idSerie);
                $crees++;
            }
            if ($crees === 0) {
                throw new RuntimeException('Aucun créneau créé : salle ou coach déjà pris sur toutes les dates.');
            }

            $this->model->commit();
            return ['crees' => $crees, 'ignores' => $ignores];
        } catch (Throwable $e) {
            $this->model->rollback();
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Création impossible : ' . ($e instanceof PDOException ? 'conflit en base.' : 'erreur interne.'));
        }
    }
}
