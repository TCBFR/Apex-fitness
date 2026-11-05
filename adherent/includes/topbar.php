<?php
require_once dirname(__DIR__) . '/controller/NotificationController.php';
require_once dirname(__DIR__) . '/controller/AdherentController.php';

$topbarUser = function_exists('utilisateur') ? (utilisateur() ?? []) : [];
$topbarNotifications = true;
$topbarMessageHref = url('adherent/messagerie');
$topbarMessageCount = (new AdherentController())->messagesNonLus((int) $topbarUser['NumUsers']);
$topbarMessageCountEndpoint = url('adherent/api.php?action=unread_messages');
$topbarNotificationCount = (new NotificationController())->nombreNonLues((int)$topbarUser['NumUsers']);
$topbarNotificationApi = url('adherent/notification_api.php');
$topbarNotificationCsrf = csrf();
$topbarLinks = [
    ['label' => 'Mon profil', 'href' => url('adherent/profil')],
    ['label' => 'Mon abonnement', 'href' => url('adherent/abonnement')],
    ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
];
require dirname(__DIR__, 2) . '/layout/topbar.php';
?>
<script>
window.ADH_API = async function (action, data = {}) {
    var endpoint = <?= json_encode(url('adherent/api.php'), JSON_UNESCAPED_SLASHES) ?>;
    var token = <?= json_encode(csrf(), JSON_UNESCAPED_SLASHES) ?>;
    var requestUrl = new URL(endpoint, window.location.href);
    requestUrl.searchParams.set('action', action);
    var response = await fetch(requestUrl.toString(), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.assign({}, data, {csrf: token}))
    });
    var result = await response.json();
    if (!response.ok) throw new Error(result.error || 'La demande a échoué.');
    return result;
};
(() => {
    const link = document.querySelector('[data-message-unread-link]');
    const badge = document.querySelector('[data-message-unread-count]');
    if (!link || !badge) return;

    const refreshUnreadMessages = async () => {
        try {
            const response = await fetch(link.dataset.countEndpoint, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const result = await response.json();
            const count = Math.max(0, Number(result.unread_count) || 0);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count === 0;
            link.setAttribute('aria-label', count ? `Messages, ${count} non lu${count > 1 ? 's' : ''}` : 'Messages');
        } catch (_) {}
    };

    refreshUnreadMessages();
    window.setInterval(refreshUnreadMessages, 30000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshUnreadMessages();
    });
})();
</script>
