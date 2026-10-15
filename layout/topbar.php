<?php
/** Barre supérieure commune aux espaces connectés. */
$topbarUser = $topbarUser ?? (function_exists('utilisateur') ? utilisateur() : []);
$topbarTitle = $topbarTitle ?? '';
$topbarContext = $topbarContext ?? '';
$topbarLinks = $topbarLinks ?? [];
$topbarNotifications = $topbarNotifications ?? false;
$topbarName = trim((string) ($topbarUser['prenomUsers'] ?? '') . ' ' . (string) ($topbarUser['nomUsers'] ?? ''));
$topbarInitial = $topbarName !== '' ? mb_strtoupper(mb_substr($topbarName, 0, 1)) : '?';
?>
<header id="topbar" class="<?= e($topbarClass ?? '') ?>" data-testid="topbar">
  <button type="button" class="tb-burger" id="burger" data-testid="mobile-menu-btn" aria-label="Ouvrir le menu" aria-expanded="false">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <?php if ($topbarTitle !== ''): ?>
    <div class="tb-heading">
      <?php if ($topbarContext !== ''): ?><p><?= e($topbarContext) ?></p><?php endif; ?>
      <h1><?= e($topbarTitle) ?></h1>
    </div>
  <?php endif; ?>
  <div class="tb-right">
    <?php if ($topbarNotifications): ?>
      <div class="tb-notifications" data-notification-menu
           data-endpoint="<?= e($topbarNotificationApi ?? '') ?>"
           data-csrf="<?= e($topbarNotificationCsrf ?? '') ?>">
        <button type="button" class="tb-bell" id="tbBell" data-notification-toggle
                data-testid="notifications-btn" aria-label="Notifications" aria-expanded="false" aria-controls="tbNotifications">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.268 21a2 2 0 0 0 3.464 0M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
          <span class="tb-badge" data-notification-count<?= (int)($topbarNotificationCount ?? 0) > 0 ? '' : ' hidden' ?>><?= (int)($topbarNotificationCount ?? 0) > 99 ? '99+' : (int)($topbarNotificationCount ?? 0) ?></span>
        </button>
        <section class="tb-notification-popover" id="tbNotifications" data-notification-popover hidden aria-label="Vos notifications">
          <header class="tb-notification-heading"><strong>Notifications</strong><span data-notification-heading-count></span></header>
          <div class="tb-notification-list" data-notification-list aria-live="polite"><p class="tb-notification-empty">Chargement…</p></div>
        </section>
      </div>
    <?php endif; ?>
    <div class="tb-user">
      <button type="button" class="tb-avatar-btn" id="tbAvatarBtn" data-testid="user-menu-btn" aria-haspopup="true" aria-expanded="false">
        <span class="tb-user-name"><?= e($topbarName !== '' ? $topbarName : 'Mon compte') ?></span>
        <span class="tb-avatar"><?= e($topbarInitial) ?></span>
        <svg class="tb-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <nav class="tb-dd" id="tbDropdown" data-testid="user-dropdown" aria-label="Menu du compte">
        <?php foreach ($topbarLinks as $link): ?>
          <a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</header>

<?php if ($topbarNotifications): ?>
<script src="<?= e(url('assets/js/pages/adherent-notifications.js')) ?>" defer></script>
<?php endif; ?>

<script>
(function () {
  var button = document.getElementById('tbAvatarBtn');
  var menu = document.getElementById('tbDropdown');
  if (button && menu) {
    function setOpen(open) {
      menu.classList.toggle('open', open);
      button.setAttribute('aria-expanded', String(open));
    }
    button.addEventListener('click', function (event) {
      event.stopPropagation();
      setOpen(!menu.classList.contains('open'));
    });
    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setOpen(false);
    });
  }

})();
</script>

<style>
#topbar,#topbar div,#topbar button,#topbar a,#topbar span,#topbar nav,#topbar p,#topbar h1{box-sizing:border-box;margin:0;padding:0}
#topbar{display:flex;align-items:center;justify-content:flex-end;gap:20px;height:66px;min-height:66px;padding:0 28px;position:sticky;top:0;z-index:60;color:#f2f4f1;background:#0b0d0c;border-bottom:1px solid #1f2422;font:400 14px/1.4 Archivo,system-ui,sans-serif}
#topbar button{font:inherit;color:inherit;background:none;border:0;cursor:pointer}
#topbar a{color:inherit;text-decoration:none}
#topbar svg{display:block;width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
#topbar .tb-burger{display:none;margin-right:auto;padding:8px;color:#e5e8e5}
#topbar .tb-heading{margin-right:auto}
#topbar .tb-heading p{margin-bottom:3px;color:#a1aaa4;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
#topbar .tb-heading h1{color:#f2f4f1;font-size:20px;font-weight:700}
#topbar .tb-right{display:flex;align-items:center;gap:18px;position:relative;margin-left:auto}
#topbar .tb-bell{position:relative;display:grid;place-items:center;width:38px;height:38px;padding:7px;color:#d5d9d6;border:1px solid transparent;border-radius:10px}
#topbar .tb-bell:hover,#topbar .tb-bell[aria-expanded="true"]{background:#171c18;border-color:#29312b}
#topbar .tb-badge{position:absolute;top:-3px;right:-4px;min-width:18px;height:18px;padding:0 5px;display:grid;place-items:center;border:2px solid #0b0d0c;border-radius:999px;background:#c6f432;color:#10140b;font-size:10px;font-weight:800;line-height:1}
#topbar .tb-badge[hidden]{display:none}
#topbar .tb-notification-popover{position:fixed;top:84px;left:16px;width:min(420px,calc(100vw - 32px));max-height:min(520px,calc(100vh - 100px));overflow:hidden;border:1px solid #2a332d;border-radius:14px;background:#111512;box-shadow:0 20px 60px rgba(0,0,0,.55);z-index:100}
#topbar .tb-notification-popover[hidden]{display:none}
#topbar .tb-notification-heading{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid #252d27;color:#f2f4f1;font-size:15px}
#topbar .tb-notification-heading span{color:#9aa69d;font-size:12px}
#topbar .tb-notification-list{max-height:min(440px,calc(100vh - 170px));overflow:auto}
#topbar .tb-notification-item{display:flex;gap:12px;align-items:flex-start;padding:15px 18px;border-bottom:1px solid #202721}
#topbar .tb-notification-item:last-child{border-bottom:0}
#topbar .tb-notification-item.is-unread{background:rgba(198,244,50,.045)}
#topbar .tb-notification-copy{min-width:0;flex:1}
#topbar .tb-notification-copy strong{display:block;margin-bottom:5px;color:#f2f4f1;font-size:13px}
#topbar .tb-notification-copy p{color:#a1aaa4;font-size:12px;line-height:1.5;overflow-wrap:anywhere}
#topbar .tb-notification-copy time{display:block;margin-top:6px;color:#717b74;font-size:11px}
#topbar .tb-notification-read{flex:none;padding:5px;color:#c6f432;font-size:11px;font-weight:700}
#topbar .tb-notification-read:hover{text-decoration:underline}
#topbar .tb-notification-read-label{flex:none;color:#737e76;font-size:11px}
#topbar .tb-notification-empty{padding:24px 18px;color:#9aa69d;text-align:center;font-size:13px}
#topbar .tb-user{position:relative}
#topbar .tb-avatar-btn{display:flex;align-items:center;gap:9px;color:#e5e8e5}
#topbar .tb-user-name{max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#topbar .tb-avatar{display:grid;place-items:center;width:34px;height:34px;border:1px solid #39413b;border-radius:50%;background:#1b211c;color:#c6f432;font-size:13px;font-weight:700}
#topbar .tb-chevron{width:14px;height:14px;color:#9aa19c}
#topbar .tb-dd{position:absolute;top:calc(100% + 12px);right:0;min-width:190px;padding:6px;border:1px solid #252c27;border-radius:9px;background:#121613;opacity:0;pointer-events:none;transform:translateY(-5px);transition:opacity .18s,transform .18s}
#topbar .tb-dd.open{opacity:1;pointer-events:auto;transform:none}
#topbar .tb-dd a{display:block;padding:10px;border-radius:6px;color:#e5e8e5;font-size:13px}
#topbar .tb-dd a:hover{background:#202820;color:#c6f432}
@media(max-width:860px){#topbar .tb-burger{display:block;margin-right:auto}#topbar{padding-inline:14px}}
</style>
