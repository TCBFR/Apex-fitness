    <!-- Grille Principale : Graphique Multiligne (CA + 3 Formules) -->
    <div class="pro-grid-main">
        
        <!-- Graphique Temporel à 4 Courbes -->
        <div class="pro-card span-2">
            <div class="pro-card-header">
                <h3>Analyse Comparative (CA & Formules d'Abonnement)</h3>
                <div class="chart-legend">
                    <span class="legend-item"><i class="dot-amber"></i> Chiffre d'Affaires (€)</span>
                    <span class="legend-item"><i class="dot-blue"></i> Formule Premium</span>
                    <span class="legend-item"><i class="dot-green"></i> Formule Standard</span>
                    <span class="legend-item"><i class="dot-gray"></i> Formule Étudiant</span>
                </div>
            </div>

            <?php
            // Configuration SVG du graphique multiligne
            $largeurTotal = 850;
            $hauteurTotal = 280;
            $margeR = 40;
            $margeL = 60;
            $margeT = 20;
            $margeB = 40;

            $largeurDessin = $largeurTotal - $margeL - $margeR;
            $hauteurDessin = $hauteurTotal - $margeT - $margeB;

            $maxCA = max(100, ...$caData);
            $maxVolume = max(10, ...$formule1Data, ...$formule2Data, ...$formule3Data);

            $ptsCA = [];
            $ptsF1 = [];
            $ptsF2 = [];
            $ptsF3 = [];
            $nbPoints = count($moisLabels);

            foreach ($moisLabels as $index => $mois) {
                $x = $margeL + ($nbPoints > 1 ? ($index / ($nbPoints - 1)) * $largeurDessin : $largeurDessin / 2);
                
                // Normalisation proportionnelle pour un rendu visuel harmonieux sur le même axe Y
                $yCA = $margeT + $hauteurDessin - (($caData[$index] / $maxCA) * $hauteurDessin);
                $yF1 = $margeT + $hauteurDessin - (($formule1Data[$index] / $maxVolume) * $hauteurDessin);
                $yF2 = $margeT + $hauteurDessin - (($formule2Data[$index] / $maxVolume) * $hauteurDessin);
                $yF3 = $margeT + $hauteurDessin - (($formule3Data[$index] / $maxVolume) * $hauteurDessin);

                $ptsCA[] = round($x, 1) . ',' . round($yCA, 1);
                $ptsF1[] = round($x, 1) . ',' . round($yF1, 1);
                $ptsF2[] = round($x, 1) . ',' . round($yF2, 1);
                $ptsF3[] = round($x, 1) . ',' . round($yF3, 1);
            }
            ?>

            <div class="chart-container-svg">
                <svg viewBox="0 0 <?= $largeurTotal ?> <?= $hauteurTotal ?>" class="pro-line-svg">
                    <!-- Grille horizontale -->
                    <?php for ($i = 0; $i <= 4; $i++): 
                        $val = ($maxCA / 4) * (4 - $i);
                        $y = $margeT + ($hauteurDessin / 4) * $i;
                    ?>
                        <line x1="<?= $margeL ?>" y1="<?= $y ?>" x2="<?= $largeurTotal - $margeR ?>" y2="<?= $y ?>" stroke="#f1f5f9" stroke-width="1"/>
                        <text x="<?= $margeL - 8 ?>" y="<?= $y + 4 ?>" font-size="10" fill="#94a3b8" text-anchor="end"><?= number_format($val, 0, '', ' ') ?> €</text>
                    <?php endfor; ?>

                    <!-- Axe X de base -->
                    <line x1="<?= $margeL ?>" y1="<?= $margeT + $hauteurDessin ?>" x2="<?= $largeurTotal - $margeR ?>" y2="<?= $margeT + $hauteurDessin ?>" stroke="#cbd5e1" stroke-width="1.5"/>

                    <!-- Courbes -->
                    <polyline fill="none" stroke="#F59E0B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $ptsCA) ?>" />
                    <polyline fill="none" stroke="#0284C7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $ptsF1) ?>" />
                    <polyline fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $ptsF2) ?>" />
                    <polyline fill="none" stroke="#475569" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="<?= implode(' ', $ptsF3) ?>" />

                    <!-- Labels de l'axe X (Mois) -->
                    <?php foreach ($moisLabels as $index => $mois): 
                        $x = $margeL + ($nbPoints > 1 ? ($index / ($nbPoints - 1)) * $largeurDessin : $largeurDessin / 2);
                    ?>
                        <text x="<?= $x ?>" y="<?= $margeT + $hauteurDessin + 22 ?>" font-size="10" fill="#64748b" text-anchor="middle"><?= e($mois) ?></text>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>

