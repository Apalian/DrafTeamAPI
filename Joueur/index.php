<?php
require_once '../connexionDB.php';
require_once 'functions.php';
require_once '../check_token.php';

$secret = 'your-256-bit-secret';

header("Access-Control-Allow-Origin: https://drafteam.lespi.fr");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Récupération des paramètres
$numLicense = isset($_GET['numLicense']) ? $_GET['numLicense'] : null;

// Validation du numLicense pour les méthodes autres que GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$numLicense) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "status_code" => 400,
        "status_message" => "[Drafteam API] : Le numéro de licence est requis"
    ]);
    exit;
}

// Vérification du token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

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

$payload = json_decode(base64_decode(explode('.', $jwt)[1]), true);
$userRole = $payload['role'] ?? null;

$dateMatch = isset($_GET['dateMatch']) ? $_GET['dateMatch'] : null;
$heure = isset($_GET['heure']) ? $_GET['heure'] : null;

// Récupération des données du body pour les méthodes POST, PUT, PATCH
$input = json_decode(file_get_contents("php://input"), true);
$nom = isset($input['nom']) ? $input['nom'] : null;
$prenom = isset($input['prenom']) ? $input['prenom'] : null;
$dateNaissance = isset($input['dateNaissance']) ? $input['dateNaissance'] : null;
$commentaire = isset($input['commentaire']) ? $input['commentaire'] : null;
$statut = isset($input['statut']) ? $input['statut'] : null;
$taille = isset($input['taille']) ? $input['taille'] : null;
$poids = isset($input['poids']) ? $input['poids'] : null;

switch ($_SERVER['REQUEST_METHOD']){
    case 'GET' :
        echo readJoueur($linkpdo, $numLicense);
        break;
    case 'POST' :
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour ajouter un joueur."]);
            exit();
        }
        echo writeJoueur($linkpdo, $numLicense, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids);
        break;
    case 'PATCH' :
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour modifier un joueur."]);
            exit();
        }
        echo patchJoueur($linkpdo, $numLicense, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids);
        break;
    case 'PUT':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour remplacer un joueur."]);
            exit();
        }
        echo putJoueur($linkpdo, $numLicense, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids);
        break;
    case 'DELETE':
        if ($userRole !== 'administrateur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "status_code" => 403, "status_message" => "Accès refusé. Vous devez être administrateur pour supprimer un joueur."]);
            exit();
        }
        echo deleteJoueur($linkpdo, $numLicense);
        break;
    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "status_code" => 405, "status_message" => "Méthode non autorisée"], JSON_PRETTY_PRINT);
        break;
}
