<?php
require_once '../connexionDB.php';
require_once 'functions.php';
require_once '../Authentication/jwt_utils.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$secret = 'your-256-bit-secret';

$jwt = get_bearer_token();
if (!$jwt || !is_jwt_valid($jwt, $secret)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "status_code" => 401, "status_message" => "Token JWT invalide ou manquant."]);
    exit();
}

$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

$numLicense = isset($_GET['numLicense']) ? intval($_GET['numLicense']) : null;
$dateMatch = isset($_GET['dateMatch']) ? $_GET['dateMatch'] : null;
$heure = isset($_GET['heure']) ? $_GET['heure'] : null;

switch ($_SERVER['REQUEST_METHOD']){
    case 'GET' :
        echo readParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;
    case 'POST' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour ajouter une participation."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        $numLicense = $input['numLicense'];
        $dateMatch = isset($input['dateMatch']) ? $input['dateMatch'] : null;
        $heure = isset($input['heure']) ? $input['heure'] : null;
        $estTitulaire = isset($input['estTitulaire']) ? $input['estTitulaire'] : null;
        $endurance = isset($input['endurance']) ? $input['endurance'] : null;
        $vitesse = isset($input['vitesse']) ? $input['vitesse'] : null;
        $defense = isset($input['defense']) ? $input['defense'] : null;
        $tirs = isset($input['tirs']) ? $input['tirs'] : null;
        $passes = isset($input['passes']) ? $input['passes'] : null;
        $poste = isset($input['poste']) ? $input['poste'] : null;

        echo writeParticipation($linkpdo, $numLicense, $dateMatch, $heure, $estTitulaire, $endurance, $vitesse, $defense, $tirs, $passes, $poste);
        break;
    case 'PATCH' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour modifier une participation."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo patchParticipation($linkpdo, $numLicense, isset($input['dateMatch']) ? $input['dateMatch'] : null, isset($input['heure']) ? $input['heure'] : null, isset($input['estTitulaire']) ? $input['estTitulaire'] : null, isset($input['endurance']) ? $input['endurance'] : null, isset($input['vitesse']) ? $input['vitesse'] : null, isset($input['defense']) ? $input['defense'] : null, isset($input['tirs']) ? $input['tirs'] : null, isset($input['passes']) ? $input['passes'] : null, isset($input['poste']) ? $input['poste'] : null);
        break;

    case 'PUT':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour remplacer une participation."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo putParticipation($linkpdo, $numLicense, $input['dateMatch'], $input['heure'], $input['estTitulaire'], $input['endurance'], $input['vitesse'], $input['defense'], $input['tirs'], $input['passes'], $input['poste']);
        break;

    case 'DELETE':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour supprimer une participation."]);
            exit();
        }
        echo deleteParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;

    case 'OPTIONS':
        http_response_code(204);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "status_code" => 405, "status_message" => "Méthode non autorisée"], JSON_PRETTY_PRINT);
        break;
}
