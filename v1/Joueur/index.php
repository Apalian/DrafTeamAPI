<?php


require_once 'functions.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode(getJoueur($_GET['id']));
        } else {
            echo json_encode(getAllJoueurs());
        }
        break;
    case 'POST':
        echo json_encode(createJoueur($input));
        break;
    case 'PUT':
        if (isset($_GET['id'])) {
            echo json_encode(updateJoueur($_GET['id'], $input));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    case 'PATCH':
        if (isset($_GET['id'])) {
            echo json_encode(patchJoueur($_GET['id'], $input));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            echo json_encode(deleteJoueur($_GET['id']));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    default:
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
