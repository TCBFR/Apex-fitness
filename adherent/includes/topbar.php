<?php
require_once dirname(__DIR__) . '/controller/NotificationController.php';

$topbarUser = function_exists('utilisateur') ? (utilisateur() ?? []) : [];
$topbarNotifications = true;
$topbarNotificationCount = (new NotificationController())->nombreNonLues((int)$topbarUser['NumUsers']);
$topbarNotificationApi = url('adherent/notification_api.php');
$topbarNotificationCsrf = csrf();
$topbarLinks = [
    ['label' => 'Mon profil', 'href' => url('adherent/view/profil.php')],
    ['label' => 'Mon abonnement', 'href' => url('adherent/view/abonnement.php')],
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
</script>
