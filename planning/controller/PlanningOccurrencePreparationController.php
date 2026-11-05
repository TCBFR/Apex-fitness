<?php

declare(strict_types=1);

trait PlanningOccurrencePreparationController
{
    private function preparerOccurrences(array $data): array
    {
        $titre = trim((string) ($data['titre'] ?? ''));
        $type = (string) ($data['type'] ?? '');
        $salle = (int) ($data['salle'] ?? 0);
        $duree = min(480, max(15, (int) ($data['duree'] ?? 60)));
        $capacite = max(1, (int) ($data['capacite'] ?? 1));
        $coach = (int) ($data['coach'] ?? 0);
        if ($titre === '' || mb_strlen($titre) > 100) {
            throw new RuntimeException('Le titre est obligatoire (100 caractères maximum).');
        }
        if (!isset(self::TYPES[$type])) throw new RuntimeException('Type d\'activité invalide.');
        if ($salle <= 0) throw new RuntimeException('Choisissez une salle.');
        if ($coach <= 0) throw new RuntimeException('Choisissez un coach.');

        $startText = trim((string) ($data['debut'] ?? ''));
        if ($startText === '') $startText = trim((string) ($data['date'] ?? '')) . 'T' . trim((string) ($data['heure'] ?? ''));
        try {
            if ($startText === '') throw new Exception('vide');
            $first = new DateTimeImmutable($startText);
        } catch (Exception $exception) {
            throw new RuntimeException('Date de début invalide.');
        }
        if ($first->getTimestamp() < time()) throw new RuntimeException('La date de début doit être dans le futur.');

        $repeat = !empty($data['repeter']);
        $limit = $first;
        if ($repeat) {
            try {
                $endText = trim((string) ($data['jusqu_au'] ?? ''));
                if ($endText === '') throw new Exception('vide');
                $limit = new DateTimeImmutable($endText . ' 23:59:59');
            } catch (Exception $exception) {
                throw new RuntimeException('Indiquez la date de fin de la répétition.');
            }
            if ($limit < $first) throw new RuntimeException('La date de fin de répétition précède le début.');
        }

        $occurrences = [];
        for ($index = 0; ; $index++) {
            $start = $index === 0 ? $first : $first->modify("+$index weeks");
            if ($start > $limit) break;
            if ($index >= self::MAX_OCCURRENCES) {
                throw new RuntimeException('Répétition limitée à ' . self::MAX_OCCURRENCES . ' semaines.');
            }
            $occurrences[] = [$start, $start->modify("+$duree minutes")];
            if (!$repeat) break;
        }

        return [
            'titre' => $titre,
            'type' => $type,
            'salle' => $salle,
            'capacite' => $capacite,
            'coach' => $coach,
            'occurrences' => $occurrences,
            'serie' => $repeat && count($occurrences) > 1 ? bin2hex(random_bytes(16)) : null,
            'repeter' => $repeat,
        ];
    }
}
