<?php

declare(strict_types=1);

final class ContratAbonnement
{
    public const VERSION = '2026-11-01';

    public static function reglementSalle(): array
    {
        return [
            'L’accès est réservé à la personne titulaire de l’abonnement.',
            'La carte de membre est personnelle et ne peut pas être prêtée.',
            'Les consignes de sécurité du personnel doivent être respectées.',
            'Une tenue et des chaussures adaptées à la pratique sportive sont requises.',
            'Le matériel utilisé doit être rangé après chaque séance.',
            'Chaque adhérent veille à respecter les autres usagers et les espaces communs.',
        ];
    }

    public static function contenu(array $offre, bool $changement): string
    {
        $type = (string) ($offre['type_abo'] ?? '');
        $prix = match ($type) {
            'FREEMIUM' => self::prix($offre['prix_mensuel_reduit'] ?? null) . ' par mois',
            'BASIC' => self::prix($offre['prix_annuel'] ?? null) . ' par an',
            'PLUS' => self::prix($offre['prix_mensuel_plus'] ?? null) . ' par mois',
            default => throw new RuntimeException('Tarif de formule introuvable.'),
        };
        $engagement = match ($type) {
            'BASIC' => 'Engagement de ' . (int) ($offre['duree_engagement_mois'] ?? 12) . ' mois.',
            'FREEMIUM' => 'Formule mensuelle avec justificatif requis : '
                . (string) ($offre['justificatif_requis'] ?? 'voir les conditions de l’offre') . '.',
            'PLUS' => 'Formule mensuelle avec '
                . (int) ($offre['seances_coach_incluses'] ?? 0) . ' séances de coach incluses.',
            default => throw new RuntimeException('Type de formule introuvable.'),
        };

        $lines = [
            'CONTRAT D’ADHÉSION ATHLETIX — version ' . self::VERSION,
            '',
            'Formule : ' . (string) $offre['libelle_abo'],
            'Tarif : ' . $prix,
            $engagement,
            $changement
                ? 'En cas de changement, la nouvelle formule commence le premier jour du mois prochain.'
                : 'La formule commence après confirmation du paiement.',
            '',
            'RÈGLEMENT INTÉRIEUR',
        ];

        foreach (self::reglementSalle() as $index => $rule) {
            $lines[] = ($index + 1) . '. ' . $rule;
        }

        $lines[] = '';
        $lines[] = 'En validant cette étape, je confirme avoir lu et accepté le règlement intérieur et les conditions de la formule indiquée ci-dessus.';

        return implode("\n", $lines);
    }

    private static function prix(mixed $value): string
    {
        if (!is_numeric($value)) {
            throw new RuntimeException('Tarif de formule introuvable.');
        }
        return number_format((float) $value, 2, ',', ' ') . ' €';
    }
}
