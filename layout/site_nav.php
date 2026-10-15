<?php
$navActive = $pageActive === 'accueil' ? 'accueil' : $pageActive;
?>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?= e(url('accueil/index.php')) ?>" aria-label="ATHLETIX, accueil">
      <svg class="logo-mark" viewBox="0 0 40 36" aria-hidden="true"><path d="M20 0 40 36H29.5L20 18.5 10.5 36H0Z" fill="currentColor"/></svg>
      <span class="logo-text">ATHLETIX</span>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="Navigation principale">
      <?php foreach ([
          'accueil' => ['Accueil', url('accueil/index.php')],
          'activites' => ['Activités', url('accueil/index.php') . '#activites'],
          'abonnements' => ['Abonnements', url('abonnement/catalogue.php')],
          'avis' => ['Avis', url('avis/public.php')],
          'faq' => ['FAQ', url('faq/public.php')],
          'contact' => ['Contact', url('message_contact/index.php')],
      ] as $key => [$label, $href]): ?>
        <a class="nav-link <?= $navActive === $key ? 'is-active' : '' ?>" href="<?= e($href) ?>" <?= $navActive === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if (a_role('ADHERENT')): ?><a class="nav-link" href="<?= e(url('adherent/espace.php')) ?>">Mon espace</a><?php endif; ?>
      <?php if (a_role('EMPLOYE')): ?><a class="nav-link" href="<?= e(url('employe/espace.php')) ?>">Gestion</a><?php endif; ?>
      <?php if (a_role('ADMIN')): ?><a class="nav-link" href="<?= e(url('admin/espace.php')) ?>">Administration</a><?php endif; ?>
      <?php if (connecte()): ?>
        <a class="nav-link" href="<?= e(url('utilisateur/deconnexion.php')) ?>">Se déconnecter</a>
      <?php else: ?>
        <a class="nav-link <?= $pageActive === 'login' ? 'is-active' : '' ?>" href="<?= e(url('utilisateur/connexion.php')) ?>">Connexion</a>
      <?php endif; ?>
    </nav>
    <button class="menu-toggle" id="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="main-nav">
      <svg class="icon menu-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      <svg class="icon menu-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
    </button>
  </div>
</header>
