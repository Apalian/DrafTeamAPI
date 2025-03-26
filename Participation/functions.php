<?php

function readParticipation($linkpdo, $numLicense = null, $dateMatch = null, $heure = null) {
    try {
        if (is_null($numLicense) && is_null($dateMatch) && is_null($heure)) {
            $sql = "SELECT * FROM `PARTICIPATION`";
            $req = $linkpdo->prepare($sql);
        } elseif (!is_null($numLicense) && is_null($dateMatch) && is_null($heure)) {
            $sql = "SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense";
            $req = $linkpdo->prepare($sql);
            $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
        } elseif (is_null($numLicense) && !is_null($dateMatch) && !is_null($heure)) {
            $sql = "SELECT * FROM `PARTICIPATION` WHERE dateMatch = :dateMatch AND heure = :heure";
            $req = $linkpdo->prepare($sql);
            $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
            $req->bindParam(':heure', $heure, PDO::PARAM_STR);
        } elseif (!is_null($numLicense) && !is_null($dateMatch) && is_null($heure)) {
            $sql = "SELECT * FROM `PARTICIPATION` WHERE numLicense = :numLicense AND dateMatch = :dateMatch";
            $req = $linkpdo->prepare($sql);
            $req->bindParam(':numLicense', $numLicense, PDO::PARAM_STR);
            $req->bindParam(':dateMatch', $dateMatch, PDO::PARAM_STR);
        }

        $req->execute();
        $res = $req->fetchAll(PDO::FETCH_ASSOC);

        if ($res) {
            deliver_response(200, "success", "Données récupérées avec succès.", $res);
        } else {
            deliver_response(404, "error", "Aucune participation trouvée.");
        }
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la récupération : " . $e->getMessage());
    }
}

function writeParticipation($linkpdo, $numLicense, $dateMatch, $heure, $estTitulaire, $endurance, $vitesse, $defense, $tirs, $passes, $poste)
{
    // Nettoyage des espaces éventuels
    $numLicense = trim($numLicense);
    $dateMatch = trim($dateMatch);
    $heure = trim($heure);

    if (empty($numLicense) || empty($dateMatch) || empty($heure) 
        || ($estTitulaire === null)) {
        deliver_response(400, "error", "Paramètres obligatoires manquants (numLicense, dateMatch, heure, estTitulaire).");
        return;
    }

    try {
        $sql = "INSERT INTO `PARTICIPATION`
                (numLicense, dateMatch, heure, estTitulaire, endurance, vitesse, defense, tirs, passes, poste)
                VALUES (:numLicense, :dateMatch, :heure, :estTitulaire, :endurance, :vitesse, :defense, :tirs, :passes, :poste)";
        $req = $linkpdo->prepare($sql);

        $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindValue(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindValue(':heure', $heure, PDO::PARAM_STR);
        $req->bindValue(':estTitulaire', (int)$estTitulaire, PDO::PARAM_INT);
        $req->bindValue(':endurance', (int)($endurance ?? 0), PDO::PARAM_INT);
        $req->bindValue(':vitesse', (int)($vitesse ?? 0), PDO::PARAM_INT);
        $req->bindValue(':defense', (int)($defense ?? 0), PDO::PARAM_INT);
        $req->bindValue(':tirs', (int)($tirs ?? 0), PDO::PARAM_INT);
        $req->bindValue(':passes', (int)($passes ?? 0), PDO::PARAM_INT);
        $req->bindValue(':poste', $poste ?? null, PDO::PARAM_STR);

        $req->execute();

        deliver_response(201, "success", "Participation créée avec succès", [
            "numLicense"    => $numLicense,
            "dateMatch"     => $dateMatch,
            "heure"         => $heure,
            "estTitulaire"  => $estTitulaire,
            "endurance"     => $endurance,
            "vitesse"       => $vitesse,
            "defense"       => $defense,
            "tirs"          => $tirs,
            "passes"        => $passes,
            "poste"         => $poste
        ]);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de l'insertion : " . $e->getMessage());
    }
}


/**
 * PATCH = mise à jour partielle
 */
function patchParticipation(
    $linkpdo,
    $numLicense,
    $dateMatch,
    $heure,
    $estTitulaire = null,
    $endurance = null,
    $vitesse = null,
    $defense = null,
    $tirs = null,
    $passes = null,
    $poste = null
) {
    if (empty($numLicense) || empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "numLicense, dateMatch, heure requis pour PATCH");
        return;
    }

    $fields = [];
    if (!is_null($estTitulaire)) $fields[] = "estTitulaire = :estTitulaire";
    if (!is_null($endurance))    $fields[] = "endurance = :endurance";
    if (!is_null($vitesse))      $fields[] = "vitesse = :vitesse";
    if (!is_null($defense))      $fields[] = "defense = :defense";
    if (!is_null($tirs))         $fields[] = "tirs = :tirs";
    if (!is_null($passes))       $fields[] = "passes = :passes";
    if (!is_null($poste))        $fields[] = "poste = :poste";

    if (empty($fields)) {
        deliver_response(400, "error", "Aucun champ à mettre à jour.");
        return;
    }

    $sql = "UPDATE `PARTICIPATION` SET " . implode(", ", $fields) . 
           " WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure";

    try {
        $req = $linkpdo->prepare($sql);

        if (!is_null($estTitulaire)) $req->bindValue(':estTitulaire', $estTitulaire, PDO::PARAM_INT);
        if (!is_null($endurance))    $req->bindValue(':endurance', $endurance, PDO::PARAM_INT);
        if (!is_null($vitesse))      $req->bindValue(':vitesse', $vitesse, PDO::PARAM_INT);
        if (!is_null($defense))      $req->bindValue(':defense', $defense, PDO::PARAM_INT);
        if (!is_null($tirs))         $req->bindValue(':tirs', $tirs, PDO::PARAM_INT);
        if (!is_null($passes))       $req->bindValue(':passes', $passes, PDO::PARAM_INT);
        if (!is_null($poste))        $req->bindValue(':poste', $poste, PDO::PARAM_STR);

        $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $req->bindValue(':heure',      $heure,      PDO::PARAM_STR);

        $req->execute();

        if ($req->rowCount() === 0) {
            deliver_response(404, "error", "Participation non trouvée ou aucune modification.");
            return;
        }

        // Récupérer la participation mise à jour
        $check = $linkpdo->prepare("
            SELECT * FROM `PARTICIPATION`
            WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure
        ");
        $check->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $check->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $check->bindValue(':heure',      $heure,      PDO::PARAM_STR);
        $check->execute();

        $updated = $check->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Participation mise à jour (PATCH) avec succès", $updated);

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur PATCH : " . $e->getMessage());
    }
}

/**
 * PUT = remplacement complet
 */
function putParticipation(
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
) {
    if (empty($numLicense) || empty($dateMatch) || empty($heure)
        || ($estTitulaire === null) || ($endurance === null) 
        || ($vitesse === null) || ($defense === null)
        || ($tirs === null) || ($passes === null) || ($poste === null)
    ) {
        deliver_response(400, "error", "Tous les champs sont obligatoires pour un PUT complet.");
        return;
    }

    try {
        $sql = "UPDATE `PARTICIPATION`
                SET estTitulaire = :estTitulaire,
                    endurance    = :endurance,
                    vitesse      = :vitesse,
                    defense      = :defense,
                    tirs         = :tirs,
                    passes       = :passes,
                    poste        = :poste
                WHERE numLicense = :numLicense
                  AND dateMatch  = :dateMatch
                  AND heure      = :heure";

        $req = $linkpdo->prepare($sql);

        $req->bindValue(':estTitulaire', $estTitulaire, PDO::PARAM_INT);
        $req->bindValue(':endurance',    $endurance,    PDO::PARAM_INT);
        $req->bindValue(':vitesse',      $vitesse,      PDO::PARAM_INT);
        $req->bindValue(':defense',      $defense,      PDO::PARAM_INT);
        $req->bindValue(':tirs',         $tirs,         PDO::PARAM_INT);
        $req->bindValue(':passes',       $passes,       PDO::PARAM_INT);
        $req->bindValue(':poste',        $poste,        PDO::PARAM_STR);

        $req->bindValue(':numLicense',   $numLicense,   PDO::PARAM_STR);
        $req->bindValue(':dateMatch',    $dateMatch,    PDO::PARAM_STR);
        $req->bindValue(':heure',        $heure,        PDO::PARAM_STR);

        $req->execute();

        if ($req->rowCount() === 0) {
            // Vérif si la participation existe
            $check = $linkpdo->prepare("
                SELECT COUNT(*) FROM `PARTICIPATION`
                WHERE numLicense = :numLicense
                  AND dateMatch  = :dateMatch
                  AND heure      = :heure
            ");
            $check->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
            $check->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
            $check->bindValue(':heure',      $heure,      PDO::PARAM_STR);
            $check->execute();

            $count = $check->fetchColumn();
            if ($count == 0) {
                deliver_response(404, "error", "Participation inexistante (PUT).");
                return;
            }
        }

        // Récupérer la participation mise à jour
        $check2 = $linkpdo->prepare("
            SELECT * FROM `PARTICIPATION`
            WHERE numLicense = :numLicense
              AND dateMatch  = :dateMatch
              AND heure      = :heure
        ");
        $check2->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $check2->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $check2->bindValue(':heure',      $heure,      PDO::PARAM_STR);
        $check2->execute();

        $updated = $check2->fetch(PDO::FETCH_ASSOC);
        deliver_response(200, "success", "Participation mise à jour (PUT) avec succès", $updated);

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur PUT : " . $e->getMessage());
    }
}

function deleteParticipation($linkpdo, $numLicense = null, $dateMatch = null, $heure = null)
{
    try {
        // CAS 1 : Supprimer toutes les participations d’un match
        if (!empty($dateMatch) && !empty($heure) && empty($numLicense)) {
            $sql = "DELETE FROM `PARTICIPATION` WHERE dateMatch = :dateMatch AND heure = :heure";
            $req = $linkpdo->prepare($sql);
            $req->bindValue(':dateMatch', $dateMatch, PDO::PARAM_STR);
            $req->bindValue(':heure', $heure, PDO::PARAM_STR);
            $req->execute();

            if ($req->rowCount() > 0) {
                deliver_response(200, "success",
                    "Toutes les participations pour le match date=$dateMatch heure=$heure ont été supprimées."
                );
            } else {
                deliver_response(404, "error", "Aucune participation trouvée pour ce match.");
            }
            return;
        }

        // CAS 2 : Supprimer UNE participation (numLicense, dateMatch, heure)
        if (!empty($numLicense) && !empty($dateMatch) && !empty($heure)) {
            $sql = "DELETE FROM `PARTICIPATION`
                    WHERE numLicense = :numLicense
                      AND dateMatch = :dateMatch
                      AND heure = :heure";
            $req = $linkpdo->prepare($sql);
            $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
            $req->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
            $req->bindValue(':heure',      $heure,      PDO::PARAM_STR);
            $req->execute();

            if ($req->rowCount() > 0) {
                deliver_response(
                    200,
                    "success",
                    "Participation (numLicense=$numLicense, date=$dateMatch, heure=$heure) supprimée avec succès."
                );
            } else {
                deliver_response(404, "error",
                    "Aucune participation trouvée pour numLicense=$numLicense, date=$dateMatch, heure=$heure."
                );
            }
            return;
        }

        // Paramètres insuffisants
        deliver_response(400, "error",
            "Paramètres insuffisants : (dateMatch, heure) ou (numLicense, dateMatch, heure)."
        );

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la suppression : " . $e->getMessage());
    }
}

/**
 * deliver_response : renvoie la réponse JSON au client
 */
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
