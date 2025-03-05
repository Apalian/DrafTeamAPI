<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
        echo readJoueur($linkpdo, $numLicense);
        break;
    case 'POST' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour ajouter un joueur."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        $nom = $input['nom'];
        $prenom = isset($input['prenom']) ? $input['prenom'] : null;
        $dateNaissance = isset($input['dateNaissance']) ? $input['dateNaissance'] : null;
        $commentaire = isset($input['commentaire']) ? $input['commentaire'] : null;
        $statut = isset($input['statut']) ? $input['statut'] : null;
        $taille = isset($input['taille']) ? $input['taille'] : null;
        $poids = isset($input['poids']) ? $input['poids'] : null;

        echo writeJoueur($linkpdo, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids);
        break;
    case 'PATCH' :
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour modifier un joueur."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo patchJoueur($linkpdo, $numLicense, isset($input['nom']) ? $input['nom'] : null, isset($input['prenom']) ? $input['prenom'] : null, isset($input['dateNaissance']) ? $input['dateNaissance'] : null, isset($input['commentaire']) ? $input['commentaire'] : null, isset($input['statut']) ? $input['statut'] : null, isset($input['taille']) ? $input['taille'] : null, isset($input['poids']) ? $input['poids'] : null);
        break;

    case 'PUT':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour remplacer un joueur."]);
            exit();
        }
        $input = json_decode(file_get_contents("php://input"), true);
        echo putJoueur($linkpdo, $numLicense, $input['nom'], $input['prenom'], $input['dateNaissance'], $input['commentaire'], $input['statut'], $input['taille'], $input['poids']);
        break;

    case 'DELETE':
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour supprimer un joueur."]);
            exit();
        }
        echo deleteJoueur($linkpdo, $numLicense);
        break;

    case 'OPTIONS':
        http_response_code(204);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "status_code" => 405, "status_message" => "Méthode non autorisée"], JSON_PRETTY_PRINT);
        break;
}
