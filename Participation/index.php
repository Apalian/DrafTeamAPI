<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connexionDB.php';
require_once 'functions.php';      // => contient readParticipation, etc.
require_once '../check_token.php';

$secret = 'your-256-bit-secret';

header("Access-Control-Allow-Origin: https://drafteam.lespi.fr");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gestion de la requête preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier le token JWT
$jwt = get_bearer_token();
if (!$jwt) {
    http_response_code(400);
    echo json_encode(["status" => "error", "status_code" => 400, "status_message" => "[Drafteam API] : BAD REQUEST"]);
    exit();
}
if (!checkTokenValidity($jwt)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "status_code" => 401, "status_message" => "Token JWT invalide"]);
    exit();
}

// Décoder le payload pour obtenir le rôle
$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

// Récupération des paramètres GET
$numLicense = isset($_GET['numLicense']) ? $_GET['numLicense'] : null;
$dateMatch  = isset($_GET['dateMatch'])  ? $_GET['dateMatch']  : null;
$heure      = isset($_GET['heure'])      ? $_GET['heure']      : null;

// Validation des paramètres obligatoires pour les méthodes autres que GET
// Validation des paramètres obligatoires selon la méthode
if (in_array($_SERVER['REQUEST_METHOD'], ['PATCH', 'PUT', 'DELETE']) && (!$numLicense || !$dateMatch || !$heure)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "status_code" => 400,
        "status_message" => "[Drafteam API] : Le numéro de licence, la date et l'heure du match sont requis en paramètre URL"
    ]);
    exit;
}

// Pour POST, validation des paramètres du body JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['numLicense']) || empty($input['dateMatch']) || empty($input['heure'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "status_code" => 400,
            "status_message" => "[Drafteam API] : Le numéro de licence, la date et l'heure du match sont requis dans le corps JSON"
        ]);
        exit;
    }
}


// Récupération des données du body pour les méthodes POST, PUT, PATCH
$input = json_decode(file_get_contents("php://input"), true);
$estTitulaire = isset($input['estTitulaire']) ? $input['estTitulaire'] : null;
$endurance = isset($input['endurance']) ? $input['endurance'] : null;
$vitesse = isset($input['vitesse']) ? $input['vitesse'] : null;
$defense = isset($input['defense']) ? $input['defense'] : null;
$tirs = isset($input['tirs']) ? $input['tirs'] : null;
$passes = isset($input['passes']) ? $input['passes'] : null;
$poste = isset($input['poste']) ? $input['poste'] : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        readParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;

    case 'POST':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Administrateur requis pour ajouter une participation."
            ]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        writeParticipation(
            $linkpdo,
            $numLicense,
            $dateMatch,
            $heure,
            $estTitulaire,
            $endurance,
            $vitesse,
            $defense,
            $tirs,
            $passes,
            $poste
        );
        break;

    case 'PATCH':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Administrateur requis pour modifier une participation."
            ]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        patchParticipation(
            $linkpdo,
            $numLicense,
            $dateMatch,
            $heure,
            $estTitulaire,
            $endurance,
            $vitesse,
            $defense,
            $tirs,
            $passes,
            $poste
        );
        break;

    case 'PUT':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Administrateur requis pour remplacer une participation."
            ]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        putParticipation(
            $linkpdo,
            $numLicense,
            $dateMatch,
            $heure,
            $estTitulaire,
            $endurance,
            $vitesse,
            $defense,
            $tirs,
            $passes,
            $poste
        );
        break;

    case 'DELETE':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Administrateur requis pour supprimer une participation."
            ]);
            exit();
        }

        // On peut gérer la suppression d'une ou toutes les participations d'un match
        deleteParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "status" => "error",
            "status_code" => 405,
            "status_message" => "Méthode non autorisée"
        ]);
        break;
}
