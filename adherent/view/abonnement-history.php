<!-- Historique -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-clock-rotate-left"></i></div>
                        <h3>Historique de facturation</h3>
                    </header>

                    <label class="invoice-filter">Filtrer par année
                        <select id="invoice-year"><option value="all">Toutes les années</option>
                            <?php foreach (array_unique(array_map(static fn($item) => substr($item['raw_date'], 0, 4), $abonnement['historique'])) as $year): ?>
                                <option value="<?= e($year) ?>"><?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <table class="history">
                        <thead>
                            <tr><th>Date</th><th>Prestation</th><th>Montant</th><th>Statut</th><th>Facture</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abonnement['historique'] as $h): ?>
                                <tr data-payment-year="<?= e(substr($h['raw_date'], 0, 4)) ?>">
                                    <td><?= htmlspecialchars($h['date']); ?></td>
                                    <td><?= htmlspecialchars($h['prestation']); ?></td>
                                    <td><?= htmlspecialchars($h['montant']); ?></td>
                                    <td><span class="badge badge-green small"><?= htmlspecialchars($h['statut']); ?></span></td>
                                    <td><a class="green-link" href="<?= e(url('adherent/facture.php?id=' . $h['id'])) ?>" data-testid="invoice-download-<?= (int)$h['id'] ?>">Télécharger PDF</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!$abonnement['historique']): ?><p class="muted">Aucun paiement enregistré.</p><?php endif; ?>

                </section>
