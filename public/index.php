<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/function.php';

const MODULES_AUTORISES = [
    'accueil', 'abonnement', 'abonnement_plus', 'activite', 'adherent', 'admin',
    'avis', 'employe', 'equipe', 'faq', 'fournisseur', 'freemium',
    'interaction_adherent', 'message_contact', 'offre', 'paiement', 'planning',
    'qr_code', 'reservation', 'salle', 'souscrire', 'statut',
    'statut_maintenance', 'type_equipement', 'utilisateur',
];
const MODULES_PUBLICS = [
    'accueil', 'abonnement', 'abonnement_plus', 'avis', 'faq',
    'message_contact', 'offre', 'freemium', 'souscrire',
];
// Rôle exigé pour accéder à un module (vérifié ici, avant d'appeler le contrôleur).
const ROLES_REQUIS = [
    'admin' => 'ADMIN',
    'employe' => 'EMPLOYE',
];
// Actions par défaut, pour les contrôleurs qui ne déclarent pas de constante ACTIONS.
const ACTIONS_AUTORISEES = ['index', 'espace', 'connexion', 'inscription', 'deconnexion'];

/** Affiche un message d'erreur simple et arrête le script. */
function afficherErreur(int $code, string $titre, string $message): never
{
    http_response_code($code);
    echo '<h1>' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</h1>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

/** Retourne [module, action] déduits de l'URL. */
function lireRoute(): array
{
    $chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($chemin)) {
        afficherErreur(400, 'Adresse invalide', 'L\'adresse demandée est invalide.');
    }

    $base = baseUrl();
    if ($base !== '' && str_starts_with($chemin, $base . '/')) {
        $chemin = substr($chemin, strlen($base));
    } elseif ($base !== '' && $chemin === $base) {
        $chemin = '/';
    }
    $chemin = rawurldecode($chemin);

    $route = $_GET['route'] ?? '';
    if (is_string($route) && $route !== '') {
        $chemin = '/' . ltrim($route, '/');
    }
    $chemin = preg_replace('#^/index\.php(?=/|$)#', '', $chemin) ?? '';

    $segments = explode('/', trim($chemin, '/'));
    $module = $segments[0] !== '' ? $segments[0] : 'accueil';
    $action = basename($segments[1] ?? 'index', '.php');

    return [$module, $action];
}

/** Charge et instancie le contrôleur d'un module. */
function chargerControleur(string $module, string $dossier): object
{
    $camel = str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
    $classes = array_unique([$camel . 'Controller', ucfirst($module) . 'Controller']);

    // Noms de fichiers possibles : AbonnementController.php, ou <module>.php (ex. planning/controller/planning.php)
    foreach (array_merge($classes, [$module]) as $nomFichier) {
        $fichier = $dossier . '/controller/' . $nomFichier . '.php';
        if (!is_file($fichier)) {
            continue;
        }
        require_once $fichier;
        foreach ($classes as $classe) {
            if (class_exists($classe)) {
                return new $classe();
            }
        }
    }
    afficherErreur(500, 'Erreur de contrôleur', 'Aucun contrôleur valide pour le module « ' . $module . ' ».');
}

/** Actions accessibles par URL : ACTIONS_URL ou ACTIONS (constante publique du contrôleur), sinon la liste par défaut. */
function actionsAutorisees(object $controleur): array
{
    foreach (['ACTIONS_URL', 'ACTIONS'] as $nom) {
        $constante = $controleur::class . '::' . $nom;
        if (defined($constante)) {
            return constant($constante);
        }
    }

    return ACTIONS_AUTORISEES;
}

/** Retourne le fichier de vue correspondant à l'action. */
function trouverVue(string $dossier, string $action): string
{
    $noms = $action === 'index' ? ['index', 'catalogue', 'public', 'contact'] : [$action];
    foreach ($noms as $nom) {
        $fichier = $dossier . '/view/' . $nom . '.php';
        if (is_file($fichier)) {
            return $fichier;
        }
    }
    afficherErreur(500, 'Erreur de vue', 'Aucune vue pour l\'action « ' . $action . ' ».');
}

/**
 * Affiche la vue (avec ou sans layout) dans une portée isolée :
 * les données du contrôleur deviennent des variables, sans écraser celles du routeur.
 */
function afficherPage(string $__vue, array $__donnees, string $__layout): void
{
    extract($__donnees, EXTR_SKIP);

    if ($__layout === 'application') {
        require_once __DIR__ . '/../layout/topbar.php';
        require $__vue;
        require_once __DIR__ . '/../layout/sidebar.php';
        require_once __DIR__ . '/../layout/footer.php';
        return;
    }

    require $__vue;
}

// 1. Route : module + action déduits de l'URL
[$module, $action] = lireRoute();

if (!in_array($module, MODULES_AUTORISES, true)) {
    afficherErreur(404, 'Module introuvable', 'Le module demandé n\'existe pas.');
}

// 2. Contrôle d'accès du module (rôle exigé)
if (isset(ROLES_REQUIS[$module])) {
    exige_role(ROLES_REQUIS[$module]);
}

// 3. Contrôleur : il prépare les données
$dossier = __DIR__ . '/../' . $module;
$controleur = chargerControleur($module, $dossier);

if (!in_array($action, actionsAutorisees($controleur), true) || !is_callable([$controleur, $action])) {
    afficherErreur(404, 'Page introuvable', 'L\'action « ' . $action . ' » n\'existe pas pour ce module.');
}

$donnees = $controleur->$action();
$donnees = is_array($donnees) ? $donnees : [];

// 4. Vue : elle affiche uniquement les données reçues
$fichierVue = trouverVue($dossier, $action);

$donnees['titrePage'] = $donnees['titrePage'] ?? $module;
$donnees['pageActive'] = $donnees['pageActive'] ?? $module;
$donnees += ['controller' => $controleur]; // compatibilité avec les vues qui utilisent $controller
$layout = $donnees['layout'] ?? (in_array($module, MODULES_PUBLICS, true) ? 'public' : 'application');

afficherPage($fichierVue, $donnees, $layout);