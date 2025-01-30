<?php

require_once 'functions.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode(getParticipation($_GET['id']));
        } else {
            echo json_encode(getAllParticipations());
        }
        break;
    case 'POST':
        echo json_encode(createParticipation($input));
        break;
    case 'PUT':
        if (isset($_GET['id'])) {
            echo json_encode(updateParticipation($_GET['id'], $input));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    case 'PATCH':
        if (isset($_GET['id'])) {
            echo json_encode(patchParticipation($_GET['id'], $input));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            echo json_encode(deleteParticipation($_GET['id']));
        } else {
            echo json_encode(["error" => "Missing ID"]);
        }
        break;
    default:
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
