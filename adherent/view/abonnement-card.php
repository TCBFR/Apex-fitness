<!-- Carte principale abonnement -->
                <section class="card">
                    <span class="badge badge-green badge-lg"><i class="fa-solid fa-lock-open"></i> <?= htmlspecialchars($abonnement['statut']); ?></span>

                    <div class="sub-main-grid">
                        <!-- Carte membre -->
                        <div class="member-card compact">
                            <div class="member-card-inner">
                                <div>
                                    <div class="brand">
                                        <span class="logo-mark"><i class="fa-solid fa-angles-up"></i></span>
                                        <span class="logo-text">ATHLETIX</span>
                                    </div>
                                    <p class="card-sub">CARTE MEMBRE</p>
                                    <div class="member-infos tight">
                                        <strong><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></strong>
                                        <span>N° Membre : <?= htmlspecialchars($user['num_membre']); ?></span>
                                    </div>
                                </div>
                                <img class="qr" src="<?= e($user['qr_image']) ?>" alt="QR code de votre carte adhérent">
                            </div>
                        </div>

                        <!-- Détails -->
                        <div class="sub-details">
                            <span class="badge badge-green"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($abonnement['statut']); ?></span>
                            <h2>Abonnement <?= htmlspecialchars($abonnement['formule']); ?></h2>
                            <p class="muted">Accès illimité à toutes les installations et activités.</p>

                            <div class="pills">
                                <?php foreach ($abonnement['acces'] as $a): ?>
                                    <div class="pill">
                                        <i class="fa-solid fa-<?= $a['icon']; ?>"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($a['label']); ?></strong>
                                            <span><?= htmlspecialchars($a['detail']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <ul class="dates-list">
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Durée de l'abonnement</span><strong><?= htmlspecialchars($abonnement['duree']); ?></strong></div></li>
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Date de début</span><strong><?= htmlspecialchars($abonnement['date_debut']); ?></strong></div></li>
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Prochaine échéance</span><strong><?= htmlspecialchars($abonnement['prochaine_echeance']); ?></strong></div></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Progression -->
                    <div class="progress-wrap">
                        <div class="progress-head">
                            <span>Temps restant</span>
                            <span class="green-text"><?= $abonnement['mois_total'] > 0 ? (int)$abonnement['mois_restants'] . ' mois restants' : ($subscription ? 'En cours' : 'Aucune période active') ?></span>
                        </div>
                        <?php $pct = $abonnement['mois_total'] > 0 ? max(0, min(100, (int)round(($abonnement['mois_restants'] / $abonnement['mois_total']) * 100))) : 0; ?>
                        <div class="progress"><div class="progress-fill" style="width: <?= $pct; ?>%"></div></div>
                    </div>

                </section>
