<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../connexionDB.php';
require_once 'functions.php';
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

$dateMatch = isset($_GET['dateMatch']) ? $_GET['dateMatch'] : null;
$heure = isset($_GET['heure']) ? $_GET['heure'] : null;

// Validation de dateMatch et heure pour les méthodes autres que GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && (!$dateMatch || !$heure)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "status_code" => 400,
        "status_message" => "[Drafteam API] : La date et l'heure du match sont requises"
    ]);
    exit;
}

// Récupération des données du body pour les méthodes POST, PUT, PATCH
$input = json_decode(file_get_contents("php://input"), true);
$nomEquipeAdverse = isset($input['nomEquipeAdverse']) ? $input['nomEquipeAdverse'] : null;
$LieuRencontre = isset($input['LieuRencontre']) ? $input['LieuRencontre'] : null;
$scoreEquipeDomicile = isset($input['scoreEquipeDomicile']) ? $input['scoreEquipeDomicile'] : null;
$scoreEquipeExterne = isset($input['scoreEquipeExterne']) ? $input['scoreEquipeExterne'] : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo readMatch($linkpdo, $dateMatch, $heure);
        break;
    case 'POST':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Vous devez être administrateur pour ajouter un match."
            ]);
            exit();
        }
        echo writeMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne);
        break;
    case 'PATCH':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Vous devez être administrateur pour modifier un match."
            ]);
            exit();
        }
        echo patchMatch(
            $linkpdo, 
            $dateMatch, 
            $heure,
            $nomEquipeAdverse, 
            $LieuRencontre, 
            $scoreEquipeDomicile, 
            $scoreEquipeExterne
        );
        break;
    case 'PUT':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Vous devez être administrateur pour remplacer un match."
            ]);
            exit();
        }
        echo putMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne);
        break;
    case 'DELETE':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "status_code" => 403,
                "status_message" => "Accès refusé. Vous devez être administrateur pour supprimer un match."
            ]);
            exit();
        }
        echo deleteMatch($linkpdo, $dateMatch, $heure);
        break;
    default:
        http_response_code(405);
        echo json_encode([
            "status" => "error",
            "status_code" => 405,
            "status_message" => "Méthode non autorisée"
        ], JSON_PRETTY_PRINT);
        break;
}
