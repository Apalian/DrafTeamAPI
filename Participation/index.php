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
    echo json_encode([
        "status" => "error",
        "status_code" => 400,
        "status_message" => "[Drafteam API] : BAD REQUEST"
    ]);
    exit();
}
if (!checkTokenValidity($jwt)) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "status_code" => 401,
        "status_message" => "Token JWT invalide"
    ]);
    exit();
}

// Décoder le payload pour obtenir le rôle
$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

// Récupération des paramètres GET
$numLicense = $_GET['numLicense'] ?? null;
$dateMatch  = $_GET['dateMatch'] ?? null;
$heure      = $_GET['heure'] ?? null;

// Validation des paramètres selon la méthode
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Cas : suppression d'une participation spécifique => tous les paramètres requis
    if ($numLicense && (!$dateMatch || !$heure)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "status_code" => 400,
            "status_message" => "[Drafteam API] : Pour supprimer une participation précise, la date et l'heure sont aussi requises."
        ]);
        exit;
    }

    // Cas : suppression de toutes les participations d’un match => date et heure requis
    if (!$numLicense && (!$dateMatch || !$heure)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "status_code" => 400,
            "status_message" => "[Drafteam API] : La date et l'heure du match sont requis pour supprimer les participations d’un match."
        ]);
        exit;
    }
} elseif (in_array($_SERVER['REQUEST_METHOD'], ['PATCH', 'PUT']) && (!$numLicense || !$dateMatch || !$heure)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "status_code" => 400,
        "status_message" => "[Drafteam API] : Le numéro de licence, la date et l'heure du match sont requis en paramètre URL"
    ]);
    exit;
}

// Récupération des données JSON pour les méthodes POST, PUT, PATCH
$input = json_decode(file_get_contents("php://input"), true);
$estTitulaire = $input['estTitulaire'] ?? null;
$endurance    = $input['endurance']    ?? null;
$vitesse      = $input['vitesse']      ?? null;
$defense      = $input['defense']      ?? null;
$tirs         = $input['tirs']         ?? null;
$passes       = $input['passes']       ?? null;
$poste        = $input['poste']        ?? null;

// Traitement de la requête
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
