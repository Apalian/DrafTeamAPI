<?php

require_once '../connexionDB.php';
require_once 'functions.php';
require_once '../check_token.php';

$secret = 'your-256-bit-secret';

// Configuration CORS
header("Access-Control-Allow-Origin: https://drafteam.lespi.fr");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 3600");

// Gestion de la requête preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Renvoyer uniquement les en-têtes CORS pour les requêtes OPTIONS
    http_response_code(204);
    exit();
}

// Vérifier le token JWT (sauf pour OPTIONS)
$jwt = get_bearer_token();
if (!$jwt || !checkTokenValidity($jwt)) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "status_code" => 401,
        "status_message" => "Token JWT invalide ou manquant."
    ]);
    exit();
}

// Décoder le payload pour obtenir le rôle
$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

// Récupérer le chemin de la requête
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$endpoint = end($path_parts);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['numLicense'])) {
            // Si un numéro de licence est fourni, retourner les stats du joueur
            getPlayerStats($linkpdo, $_GET['numLicense']);
        } else {
            // Sinon, retourner les stats générales des matchs
            getMatchStats($linkpdo);
        }
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
