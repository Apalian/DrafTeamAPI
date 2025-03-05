<?php
function readParticipation($linkpdo, $numLicense = null, $dateMatch = null, $heure = null) {
    try {
        if (is_null($numLicense) && is_null($dateMatch) && is_null($heure)) {
            $requete = "SELECT * FROM `PARTICIPATION`";
            $req = $linkpdo->prepare($requete);
        } elseif (!is_null($numLicense) && is_null($dateMatch) && is_null($heure)) {
            $requete = "SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense";
            $req = $linkpdo->prepare($requete);
            $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
        } elseif (is_null($numLicense) && !is_null($dateMatch) && !is_null($heure)) {  
            $requete = "SELECT * FROM `PARTICIPATION` WHERE dateMatch = :dateMatch AND heure = :heure";
            $req = $linkpdo->prepare($requete);
            $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
            $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        } elseif (!is_null($numLicense) && !is_null($dateMatch) && is_null($heure)) {
            $requete = "SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense AND dateMatch = :dateMatch";
            $req = $linkpdo->prepare($requete);
            $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
            $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
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


function writeParticipation($linkpdo, $numLicense, $dateMatch, $heure, $estTitulaire, $endurance, $vitesse, $defense, $tirs, $passes, $poste) {
    if (empty($numLicense) || empty($dateMatch) || empty($heure) || empty($estTitulaire) || empty($poste)) {
        deliver_response(400, "error", "Paramètre numLicense, dateMatch, heure, estTitulaire, poste manquant");
        return;
    }

    try {
        $requete = "INSERT INTO `PARTICIPATION` (numLicense, dateMatch, heure, estTitulaire, endurance, vitesse, defense, tirs, passes, poste) VALUES (:numLicense, :dateMatch, :heure, :estTitulaire, :endurance, :vitesse, :defense, :tirs, :passes, :poste)";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);  
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->bindParam(':estTitulaire', $estTitulaire, PDO::PARAM_STR);
        $req->bindParam(':endurance', $endurance ?? 0, PDO::PARAM_INT);
        $req->bindParam(':vitesse', $vitesse ?? 0, PDO::PARAM_INT);
        $req->bindParam(':defense', $defense ?? 0, PDO::PARAM_INT);
        $req->bindParam(':tirs', $tirs ?? 0, PDO::PARAM_INT);
        $req->bindParam(':passes', $passes ?? 0, PDO::PARAM_INT);
        $req->bindParam(':poste', $poste, PDO::PARAM_STR);
        $req->execute();

        deliver_response(201, "success", "Donnée créée avec succès", [
            "numLicense" => $numLicense,
            "dateMatch" => $dateMatch,
            "heure" => $heure,
            "estTitulaire" => $estTitulaire,
            "endurance" => $endurance,
            "vitesse" => $vitesse,
            "defense" => $defense,
            "tirs" => $tirs,
            "passes" => $passes,
            "poste" => $poste
        ]);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de l'insertion : " . $e->getMessage());
    }
}


function patchParticipation($linkpdo, $numLicense, $dateMatch = null, $heure = null, $estTitulaire = null, $endurance = null, $vitesse = null, $defense = null, $tirs = null, $passes = null, $poste = null) {
    if (empty($numLicense) || empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "Paramètre numLicense, dateMatch, heure manquant");
        return;
    }

    $fields = [];
    if (!is_null($estTitulaire)) $fields[] = "estTitulaire = :estTitulaire";
    if (!is_null($endurance)) $fields[] = "endurance = :endurance";
    if (!is_null($vitesse)) $fields[] = "vitesse = :vitesse";
    if (!is_null($defense)) $fields[] = "defense = :defense";
    if (!is_null($tirs)) $fields[] = "tirs = :tirs";
    if (!is_null($passes)) $fields[] = "passes = :passes";
    if (!is_null($poste)) $fields[] = "poste = :poste";

    if (empty($fields)) {
        deliver_response(400, "error", "Aucune mise à jour spécifiée");
        return;
    }

    $requete = "UPDATE `PARTICIPATION` SET " . implode(", ", $fields) . " WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure";

    try {
        $req = $linkpdo->prepare($requete);
        if (!is_null($estTitulaire)) $req->bindParam(':estTitulaire', $estTitulaire, PDO::PARAM_STR);
        if (!is_null($endurance)) $req->bindParam(':endurance', $endurance, PDO::PARAM_INT);
        if (!is_null($vitesse)) $req->bindParam(':vitesse', $vitesse, PDO::PARAM_INT);
        if (!is_null($defense)) $req->bindParam(':defense', $defense, PDO::PARAM_INT);
        if (!is_null($tirs)) $req->bindParam(':tirs', $tirs, PDO::PARAM_INT);
        if (!is_null($passes)) $req->bindParam(':passes', $passes, PDO::PARAM_INT);
        if (!is_null($poste)) $req->bindParam(':poste', $poste, PDO::PARAM_STR);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure");
        $query->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $query->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $query->bindParam(':heure', $heure, PDO::PARAM_STR);
        $query->execute();
        $updatedParticipation = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedParticipation);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}


function putParticipation($linkpdo, $numLicense, $dateMatch, $heure, $estTitulaire, $endurance, $vitesse, $defense, $tirs, $passes, $poste) {
    if (empty($numLicense) || empty($dateMatch) || empty($heure) || empty($estTitulaire) || empty($endurance) || empty($vitesse) || empty($defense) || empty($tirs) || empty($passes) || empty($poste)) {
        deliver_response(400, "error", "Paramètre numLicense, dateMatch, heure, estTitulaire, endurance, vitesse, defense, tirs, passes, poste manquant");
        return;
    }

    try {
        $requete = "UPDATE `PARTICIPATION` SET numLicense = :numLicense, dateMatch = :dateMatch, heure = :heure, estTitulaire = :estTitulaire, endurance = :endurance, vitesse = :vitesse, defense = :defense, tirs = :tirs, passes = :passes, poste = :poste WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->bindParam(':estTitulaire', $estTitulaire, PDO::PARAM_STR);
        $req->bindParam(':endurance', $endurance, PDO::PARAM_INT);
        $req->bindParam(':vitesse', $vitesse, PDO::PARAM_INT);
        $req->bindParam(':defense', $defense, PDO::PARAM_INT);
        $req->bindParam(':tirs', $tirs, PDO::PARAM_INT);
        $req->bindParam(':passes', $passes, PDO::PARAM_INT);
        $req->bindParam(':poste', $poste, PDO::PARAM_STR);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure");
        $query->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $query->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $query->bindParam(':heure', $heure, PDO::PARAM_STR);
        $query->execute();
        $updatedParticipation = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedParticipation);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}

function deleteParticipation($linkpdo, $numLicense, $dateMatch, $heure){
    if (empty($numLicense) || empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "Paramètre numLicense, dateMatch, heure manquant");
        return;
    }
    try{
        $requete = "DELETE FROM `PARTICIPATION` WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        $req->execute();
        if ($req->rowCount() > 0) {
            deliver_response(200, "success", "Données numLicense:$numLicense, dateMatch:$dateMatch, heure:$heure supprimée avec succès.");
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
