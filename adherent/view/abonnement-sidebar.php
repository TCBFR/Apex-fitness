<!-- Colonne latérale -->
            <aside class="col-side">

                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-crown"></i></div>
                        <h3>Votre abonnement</h3>
                    </header>

                    <div class="status-row">
                        <span class="status-dot"><i class="fa-solid fa-circle-check"></i></span>
                        <div>
                            <strong class="green-text"><?= htmlspecialchars($abonnement['statut']); ?></strong>
                            <p class="muted small"><?= $subscription ? 'Votre formule est en cours.' : 'Aucune formule active. Consultez les offres disponibles.' ?></p>
                        </div>
                    </div>

                    <a class="btn btn-primary btn-block" href="<?= e(url('adherent/offres')) ?>">
                        <i class="fa-regular fa-calendar"></i> Gérer mon abonnement
                        <i class="fa-solid fa-chevron-right right"></i>
                    </a>
                </section>

            </aside>
