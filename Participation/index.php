<?php
require_once '../connexionDB.php';
require_once 'functions.php';
require_once '../check_token.php';

header("Access-Control-Allow-Origin: https://drafteam.lespi.fr");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gestion de la requête preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$secret = 'your-256-bit-secret';

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

$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

// Paramètres GET pour identifier la ressource
$numLicense = isset($_GET['numLicense']) ? intval($_GET['numLicense']) : null;
$dateMatch  = isset($_GET['dateMatch'])  ? $_GET['dateMatch']            : null;
$heure      = isset($_GET['heure'])      ? $_GET['heure']                : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // On appelle directement la fonction, 
        // sans echo, car deliver_response() est déjà dedans.
        readParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;

    case 'POST':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            deliver_response(403, "error", 
                "Accès refusé. Vous devez être administrateur pour ajouter une participation."
            );
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        $numLicense    = $input['numLicense']       ?? null;
        $dateMatch     = $input['dateMatch']        ?? null;
        $heure         = $input['heure']            ?? null;
        $estTitulaire  = $input['estTitulaire']     ?? null;
        $endurance     = $input['endurance']        ?? null;
        $vitesse       = $input['vitesse']          ?? null;
        $defense       = $input['defense']          ?? null;
        $tirs          = $input['tirs']             ?? null;
        $passes        = $input['passes']           ?? null;
        $poste         = $input['poste']            ?? null;

        // Appel direct
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
            deliver_response(403, "error", 
                "Accès refusé. Vous devez être administrateur pour modifier une participation."
            );
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        patchParticipation(
            $linkpdo,
            $numLicense,
            isset($input['dateMatch']) ? $input['dateMatch'] : null,
            isset($input['heure'])     ? $input['heure']     : null,
            isset($input['estTitulaire']) ? $input['estTitulaire'] : null,
            isset($input['endurance'])   ? $input['endurance']   : null,
            isset($input['vitesse'])     ? $input['vitesse']     : null,
            isset($input['defense'])     ? $input['defense']     : null,
            isset($input['tirs'])        ? $input['tirs']        : null,
            isset($input['passes'])      ? $input['passes']      : null,
            isset($input['poste'])       ? $input['poste']       : null
        );
        break;

    case 'PUT':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            deliver_response(403, "error", 
                "Accès refusé. Vous devez être administrateur pour remplacer une participation."
            );
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);

        putParticipation(
            $linkpdo, 
            $numLicense, 
            $input['dateMatch'], 
            $input['heure'], 
            $input['estTitulaire'], 
            $input['endurance'], 
            $input['vitesse'], 
            $input['defense'], 
            $input['tirs'], 
            $input['passes'], 
            $input['poste']
        );
        break;

    case 'DELETE':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            deliver_response(403, "error", 
                "Accès refusé. Vous devez être administrateur pour supprimer une participation."
            );
            exit();
        }
        deleteParticipation($linkpdo, $numLicense, $dateMatch, $heure);
        break;

    case 'OPTIONS':
        // Réponse OK pour la requête preflight
        http_response_code(204);
        break;

    default:
        http_response_code(405);
        deliver_response(
            405,
            "error",
            "Méthode non autorisée"
        );
        break;
}
