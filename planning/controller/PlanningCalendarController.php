<?php

declare(strict_types=1);

trait PlanningCalendarController
{
    public function evenements(string $debut, string $fin, int $numUsers = 0): array
    {
        $out = [];
        foreach ($this->model->evenements($debut, $fin, $numUsers) as $r) {
            $type      = isset(self::TYPES[$r['type_activite']]) ? $r['type_activite'] : 'cardio';
            $capacite  = (int) $r['capacite_max'];
            $prises    = (int) $r['places_prises'];
            $restantes = max(0, $capacite - $prises);
            $complet   = $restantes === 0;
            $tsDebut   = strtotime($r['date_heure_debut']);

            $out[] = [
                'id'            => (int) $r['Numactivite'],
                'title'         => $r['titre'],
                'start'         => date('Y-m-d\TH:i:s', $tsDebut),
                'end'           => date('Y-m-d\TH:i:s', strtotime($r['date_heure_fin'])),
                'classNames'    => ['act-' . $type, $complet ? 'act-complet' : 'act-ouvert'],
                'extendedProps' => [
                    'type'        => $type,
                    'typeLibelle' => self::TYPES[$type],
                    'salle'       => $r['nomsalle'],
                    'coach'       => trim($r['prenomUsers'] . ' ' . $r['nomUsers']),
                    'coachFirstName' => $r['prenomUsers'],
                    'coachId'     => (int) $r['NumUsers'],
                    'roomId'      => (int) $r['Numsalle'],
                    'capacite'    => $capacite,
                    'prises'      => $prises,
                    'restantes'   => $restantes,
                    'complet'     => $complet,
                    'reserve'     => (int) $r['reserve'] > 0,
                    'enAttente'   => (int) ($r['en_attente'] ?? 0) > 0,
                    'serie'       => $r['id_serie'] !== null,
                    'passe'       => $tsDebut < time(),
                ],
            ];
        }
        return $out;
    }

    public function donneesEmploye(int $moi): array
    {
        $creneaux = [];
        foreach ($this->model->derniersCreneaux() as $c) {
            $capacite = (int) $c['capacite_max'];
            $c['pct'] = $capacite > 0 ? (int) round((int) $c['places_prises'] / $capacite * 100) : 0;
            $creneaux[] = $c;
        }
        return [
            'moi'      => $moi,
            'creneaux' => $creneaux,
            'titres'   => $this->model->titresExistants(),
            'salles'   => $this->model->salles(),
            'coachs'   => $this->model->coachs(),
        ];
    }

    public function donneesAdmin(string $debutSemaine): array
    {
        $dateDebut = DateTimeImmutable::createFromFormat('!Y-m-d', $debutSemaine);
        if (!$dateDebut || $dateDebut->format('Y-m-d') !== $debutSemaine) {
            $dateDebut = new DateTimeImmutable('monday this week');
        }
        $dateFin = $dateDebut->modify('+7 days');
        $donnees = $this->donneesEmploye(0);
        $donnees['salles'] = $this->model->toutesLesSalles();
        $donnees['debut_semaine'] = $dateDebut->format('Y-m-d');
        $donnees['fin_semaine'] = $dateFin->format('Y-m-d');
        $donnees['creneaux_semaine'] = $this->model->creneauxSurPeriode(
            $dateDebut->format('Y-m-d 00:00:00'),
            $dateFin->format('Y-m-d 00:00:00')
        );
        return $donnees;
    }
}
