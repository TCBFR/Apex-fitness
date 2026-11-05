    <!-- Grille Secondaire : Activités & Matériel -->
    <div class="pro-grid-secondary">
        
        <!-- Fréquentation des Activités -->
        <div class="pro-card">
            <div class="pro-card-header">
                <h3>Activités Populaires</h3>
                <span class="pro-card-tag">Planning</span>
            </div>
            <?php if (empty($topActivites)): ?>
                <p class="empty-state">Aucune activité enregistrée.</p>
            <?php else: ?>
                <div class="progress-container">
                    <?php foreach ($topActivites as $act): 
                        $val = (int)$act['n'];
                        $pct = round(($val / $maxActiviteCount) * 100);
                    ?>
                        <div class="progress-row">
                            <div class="progress-info">
                                <span><?= e($act['libelleact']) ?></span>
                                <strong><?= $val ?> résa.</strong>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- État du Parc Matériel -->
        <div class="pro-card">
            <div class="pro-card-header">
                <h3>Parc Matériel</h3>
                <span class="pro-card-tag">Technique</span>
            </div>
            <?php if (empty($parEtat)): ?>
                <p class="empty-state">Aucun équipement suivi.</p>
            <?php else: ?>
                <table class="pro-table">
                    <thead>
                        <tr>
                            <th>Statut du matériel</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parEtat as $etat): ?>
                            <tr>
                                <td><span class="status-dot"></span> <?= e($etat['libelle_statut']) ?></td>
                                <td class="text-right"><strong><?= (int)$etat['n'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</div>
