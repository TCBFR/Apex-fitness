        <!-- Top Formules d'Abonnement -->
        <div class="pro-card">
            <div class="pro-card-header">
                <h3>Top Formules</h3>
                <span class="pro-card-tag">Commercial</span>
            </div>
            <?php if (empty($topAbonnements)): ?>
                <p class="empty-state">Aucune formule active.</p>
            <?php else: ?>
                <div class="pro-list">
                    <?php foreach ($topAbonnements as $abo): ?>
                        <div class="pro-list-item">
                            <div>
                                <strong><?= e($abo['libelle_abo']) ?></strong>
                                <span class="sub-text"><?= e($abo['type_abo']) ?></span>
                            </div>
                            <div class="badge-count"><?= (int)$abo['n'] ?> abonnés</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

