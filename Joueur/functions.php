<?php
function readJoueur($linkpdo, $numLicense = null) {
    try {
        if (is_null($numLicense)) {
            $requete = "SELECT * FROM `JOUEURS`";
            $req = $linkpdo->prepare($requete);
        } else {
            $requete = "SELECT * FROM `JOUEURS` WHERE numLicense = :numLicense";
            $req = $linkpdo->prepare($requete);
            $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
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


function writeJoueur($linkpdo, $numLicense, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids) {
    if (empty($numLicense) || empty($nom) || empty($prenom) || empty($dateNaissance) || empty($statut) || empty($taille) || empty($poids)) {
        deliver_response(400, "error", "Paramètre numLicense, nom, prenom, dateNaissance, statut, taille, poids manquant");
        return;
    }

    try {
        $requete = "INSERT INTO `JOUEURS` (numLicense, nom, prenom, dateNaissance, commentaire, statut, taille, poids) VALUES (:numLicense, :nom, :prenom, :dateNaissance, :commentaire, :statut, :taille, :poids)";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindParam(':nom', $nom, PDO::PARAM_STR);  
        $req->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $req->bindParam(':dateNaissance', $dateNaissance, PDO::PARAM_STR);
        $req->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
        $req->bindParam(':statut', $statut, PDO::PARAM_STR);
        $req->bindParam(':taille', $taille, PDO::PARAM_INT);
        $req->bindParam(':poids', $poids, PDO::PARAM_INT);
        $req->execute();

        deliver_response(201, "success", "Donnée créée avec succès", [
            "numLicense" => $numLicense,
            "nom" => $nom,
            "prenom" => $prenom,
            "dateNaissance" => $dateNaissance,
            "commentaire" => $commentaire,
            "statut" => $statut,
            "taille" => $taille,
            "poids" => $poids
        ]);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de l'insertion : " . $e->getMessage());
    }
}


function patchJoueur($linkpdo, $numLicense, $nom = null, $prenom = null, $dateNaissance = null, $commentaire = null, $statut = null, $taille = null, $poids = null) {
    if (empty($numLicense)) {
        deliver_response(400, "error", "Paramètre numLicense manquant");
        return;
    }

    $fields = [];
    if (!is_null($nom)) $fields[] = "nom = :nom";
    if (!is_null($prenom)) $fields[] = "prenom = :prenom";
    if (!is_null($dateNaissance)) $fields[] = "dateNaissance = :dateNaissance";
    if (!is_null($commentaire)) $fields[] = "commentaire = :commentaire";
    if (!is_null($statut)) $fields[] = "statut = :statut";
    if (!is_null($taille)) $fields[] = "taille = :taille";
    if (!is_null($poids)) $fields[] = "poids = :poids";

    if (empty($fields)) {
        deliver_response(400, "error", "Aucune mise à jour spécifiée");
        return;
    }

    $requete = "UPDATE `JOUEURS` SET " . implode(", ", $fields) . " WHERE numLicense = :numLicense";

    try {
        $req = $linkpdo->prepare($requete);
        if (!is_null($nom)) $req->bindParam(':nom', $nom, PDO::PARAM_STR);
        if (!is_null($prenom)) $req->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        if (!is_null($dateNaissance)) $req->bindParam(':dateNaissance', $dateNaissance, PDO::PARAM_STR);
        if (!is_null($commentaire)) $req->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
        if (!is_null($statut)) $req->bindParam(':statut', $statut, PDO::PARAM_STR);
        if (!is_null($taille)) $req->bindParam(':taille', $taille, PDO::PARAM_INT);
        if (!is_null($poids)) $req->bindParam(':poids', $poids, PDO::PARAM_INT);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `JOUEURS` WHERE numLicense = :numLicense");
        $query->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $query->execute();
        $updatedJoueur = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedJoueur);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}


function putJoueur($linkpdo, $numLicense, $nom, $prenom, $dateNaissance, $commentaire, $statut, $taille, $poids) {
    if (empty($numLicense) || empty($nom) || empty($prenom) || empty($dateNaissance) || empty($commentaire) || empty($statut) || empty($taille) || empty($poids)) {
        deliver_response(400, "error", "Paramètre numLicense, nom, prenom, dateNaissance, commentaire, statut, taille, poids manquant");
        return;
    }

    try {
        $requete = "UPDATE `JOUEURS` SET nom = :nom, prenom = :prenom, dateNaissance = :dateNaissance, commentaire = :commentaire, statut = :statut, taille = :taille, poids = :poids WHERE numLicense = :numLicense";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':nom', $nom, PDO::PARAM_STR);
        $req->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $req->bindParam(':dateNaissance', $dateNaissance, PDO::PARAM_STR);
        $req->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
        $req->bindParam(':statut', $statut, PDO::PARAM_STR);
        $req->bindParam(':taille', $taille, PDO::PARAM_INT);
        $req->bindParam(':poids', $poids, PDO::PARAM_INT);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $req->execute();

        $query = $linkpdo->prepare("SELECT * FROM `JOUEURS` WHERE numLicense = :numLicense");
        $query->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $query->execute();
        $updatedJoueur = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Données modifiées avec succès", $updatedJoueur);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour : " . $e->getMessage());
    }
}

function deleteJoueur($linkpdo, $numLicense){
    if (empty($numLicense)) {
        deliver_response(400, "error", "Paramètre numLicense manquant");
        return;
    }
    try{
        $requete = "DELETE FROM `JOUEURS` WHERE numLicense = :numLicense";
        $req = $linkpdo->prepare($requete);
        $req->bindParam(':numLicense', $numLicense, PDO::PARAM_INT);
        $req->execute();
        if ($req->rowCount() > 0) {
            deliver_response(200, "success", "Données numLicense:$numLicense supprimée avec succès.");
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
