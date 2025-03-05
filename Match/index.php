<?php
require_once '../connexionDB.php';
require_once 'functions.php';
require_once '../Authentication/jwt_utils.php'; // Inclure le fichier jwt_utils.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$secret = 'your-256-bit-secret'; // Assurez-vous que cela correspond à votre secret

// Vérifier le token JWT
$jwt = get_bearer_token();
if (!$jwt || !is_jwt_valid($jwt, $secret)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "status_code" => 401, "status_message" => "Token JWT invalide ou manquant."]);
    exit();
}

// Décoder le payload pour obtenir le rôle
$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null; // Assurez-vous que le rôle est inclus dans le payload

$dateMatch = isset($_GET['dateMatch']) ? $_GET['dateMatch'] : null;
$heure = isset($_GET['heure']) ? $_GET['heure'] : null;

// Vérifie la méthode de requête
switch ($_SERVER['REQUEST_METHOD']){
    case 'GET' :
        echo readMatch($linkpdo, $dateMatch, $heure);
        break;
    case 'POST' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour ajouter un match."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        $dateMatch = $input['dateMatch'];
        $heure = isset($input['heure']) ? $input['heure'] : null;
        $nomEquipeAdverse = isset($input['nomEquipeAdverse']) ? $input['nomEquipeAdverse'] : null;
        $LieuRencontre = isset($input['LieuRencontre']) ? $input['LieuRencontre'] : null;
        $scoreEquipeDomicile = isset($input['scoreEquipeDomicile']) ? $input['scoreEquipeDomicile'] : null;
        $scoreEquipeExterne = isset($input['scoreEquipeExterne']) ? $input['scoreEquipeExterne'] : null;

        echo writeMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne);
        break;
    case 'PATCH' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour modifier un match."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo patchMatch($linkpdo, $dateMatch, isset($input['heure']) ? $input['heure'] : null, isset($input['nomEquipeAdverse']) ? $input['nomEquipeAdverse'] : null, isset($input['LieuRencontre']) ? $input['LieuRencontre'] : null, isset($input['scoreEquipeDomicile']) ? $input['scoreEquipeDomicile'] : null, isset($input['scoreEquipeExterne']) ? $input['scoreEquipeExterne'] : null);
        break;

    case 'PUT':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour remplacer un match."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo putMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne);
        break;

    case 'DELETE':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour supprimer un match."]);
            exit();
        }
        echo deleteMatch($linkpdo, $dateMatch, $heure);
        break;

    case 'OPTIONS':
        http_response_code(204);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "status_code" => 405, "status_message" => "Méthode non autorisée"], JSON_PRETTY_PRINT);
        break;
}
