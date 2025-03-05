<?php
function readMatch($linkpdo, $dateMatch = null, $heure = null) {
    try {
        if (is_null($dateMatch) && is_null($heure)) {   
            $requete = "SELECT * FROM `MATCHS`";
            $req = $linkpdo->prepare($requete);
        } else {
            $requete = "SELECT * FROM `MATCHS` WHERE dateMatch = :dateMatch AND heure = :heure";
            $req = $linkpdo->prepare($requete);
            $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
            $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        }

        $req->execute();
        $resquery = $req->fetchAll(PDO::FETCH_ASSOC);

        if ($resquery) {
            deliver_response(200, "success", "Données récupérées avec succès.", $resquery);
        } else {
            deliver_response(404, "error", "Aucune donnée trouvée.");
        }
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la récupération : " . $e->getMessage());
    }
}


function writeMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne) {
    if (empty($dateMatch) || empty($heure) || empty($nomEquipeAdverse) || empty($LieuRencontre)) {
        deliver_response(400, "error", "Paramètre dateMatch, heure, nomEquipeAdverse, LieuRencontre manquant");
        return;
    }

    try {
        $requete = "INSERT INTO `MATCHS` (dateMatch, heure, nomEquipeAdverse, LieuRencontre, scoreEquipeDomicile, scoreEquipeExterne) VALUES (:dateMatch, :heure, :nomEquipeAdverse, :LieuRencontre, :scoreEquipeDomicile, :scoreEquipeExterne)";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);  
        $req->bindParam(':nomEquipeAdverse', $nomEquipeAdverse, PDO::PARAM_STR);
        $req->bindParam(':LieuRencontre', $LieuRencontre, PDO::PARAM_STR);
        $req->bindParam(':scoreEquipeDomicile', $scoreEquipeDomicile, PDO::PARAM_INT);
        $req->bindParam(':scoreEquipeExterne', $scoreEquipeExterne, PDO::PARAM_INT);
        $req->execute();

        deliver_response(201, "success", "Donnée créée avec succès", [
            "dateMatch" => $dateMatch,
            "heure" => $heure,
            "nomEquipeAdverse" => $nomEquipeAdverse,
            "LieuRencontre" => $LieuRencontre,
            "scoreEquipeDomicile" => $scoreEquipeDomicile,
            "scoreEquipeExterne" => $scoreEquipeExterne
        ]);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de l'insertion : " . $e->getMessage());
    }
}


function patchMatch($linkpdo, $dateMatch, $heure = null, $nomEquipeAdverse = null, $LieuRencontre = null, $scoreEquipeDomicile = null, $scoreEquipeExterne = null) {
    if (empty($dateMatch)  || empty($heure)) {
        deliver_response(400, "error", "Paramètre dateMatch, heure manquant");
        return;
    }

    $fields = [];
    if (!is_null($nomEquipeAdverse)) $fields[] = "nomEquipeAdverse = :nomEquipeAdverse";
    if (!is_null($LieuRencontre)) $fields[] = "LieuRencontre = :LieuRencontre";
    if (!is_null($scoreEquipeDomicile)) $fields[] = "scoreEquipeDomicile = :scoreEquipeDomicile";
    if (!is_null($scoreEquipeExterne)) $fields[] = "scoreEquipeExterne = :scoreEquipeExterne";

    if (empty($fields)) {
        deliver_response(400, "error", "Aucune mise à jour spécifiée");
        return;
    }

    $requete = "UPDATE `MATCHS` SET " . implode(", ", $fields) . " WHERE dateMatch = :dateMatch AND heure = :heure";

    try {
        $req = $linkpdo->prepare($requete);
        if (!is_null($nomEquipeAdverse)) $req->bindParam(':nomEquipeAdverse', $nomEquipeAdverse, PDO::PARAM_STR);
        if (!is_null($LieuRencontre)) $req->bindParam(':LieuRencontre', $LieuRencontre, PDO::PARAM_STR);
        if (!is_null($scoreEquipeDomicile)) $req->bindParam(':scoreEquipeDomicile', $scoreEquipeDomicile, PDO::PARAM_INT);
        if (!is_null($scoreEquipeExterne)) $req->bindParam(':scoreEquipeExterne', $scoreEquipeExterne, PDO::PARAM_INT);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `MATCHS` WHERE dateMatch = :dateMatch AND heure = :heure");
        $query->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $query->bindParam(':heure', $heure, PDO::PARAM_STR);
        $query->execute();
        $updatedMatch = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedMatch);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}


function putMatch($linkpdo, $dateMatch, $heure, $nomEquipeAdverse, $LieuRencontre, $scoreEquipeDomicile, $scoreEquipeExterne) {
    if (empty($dateMatch) || empty($heure) || empty($nomEquipeAdverse) || empty($LieuRencontre) || empty($scoreEquipeDomicile) || empty($scoreEquipeExterne)) {
        deliver_response(400, "error", "Paramètre dateMatch, heure, nomEquipeAdverse, LieuRencontre, scoreEquipeDomicile, scoreEquipeExterne manquant");
        return;
    }

    try {
        $requete = "UPDATE `MATCHS` SET dateMatch = :dateMatch, heure = :heure, nomEquipeAdverse = :nomEquipeAdverse, LieuRencontre = :LieuRencontre, scoreEquipeDomicile = :scoreEquipeDomicile, scoreEquipeExterne = :scoreEquipeExterne WHERE dateMatch = :dateMatch AND heure = :heure";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->bindParam(':nomEquipeAdverse', $nomEquipeAdverse, PDO::PARAM_STR);
        $req->bindParam(':LieuRencontre', $LieuRencontre, PDO::PARAM_STR);
        $req->bindParam(':scoreEquipeDomicile', $scoreEquipeDomicile, PDO::PARAM_INT);
        $req->bindParam(':scoreEquipeExterne', $scoreEquipeExterne, PDO::PARAM_INT);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `MATCHS` WHERE dateMatch = :dateMatch AND heure = :heure");
        $query->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $query->bindParam(':heure', $heure, PDO::PARAM_STR);
        $query->execute();
        $updatedMatch = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedMatch);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}

function deleteMatch($linkpdo, $dateMatch, $heure){
    if (empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "Paramètre dateMatch, heure manquant");
        return;
    }
    try{
        $requete = "DELETE FROM `MATCHS` WHERE dateMatch = :dateMatch AND heure = :heure";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->execute();
        if ($req->rowCount() > 0) {
            deliver_response(200, "success", "Données dateMatch:$dateMatch et heure:$heure supprimée avec succès.");
        } else {
            deliver_response(404, "error", "Aucune donnée trouvée");

        }
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur : " . $e->getMessage());
    }
}

function deliver_response($status_code, $status, $status_message, $data = null) {
    http_response_code($status_code);
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    $response = [
        "status" => $status,
        "status_code" => $status_code,
        "status_message" => $status_message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
