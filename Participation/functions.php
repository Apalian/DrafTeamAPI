<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log'); // Change le nom du fichier si besoin

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
        deliver_response(500, "fatal error", "Erreur lors du chargement des participations : " . $e->getMessage());
    }
}


function writeParticipation($linkpdo, $numLicense, $dateMatch, $heure, $estTitulaire, $endurance, $vitesse, $defense, $tirs, $passes, $poste)
{
    // Vérification des paramètres obligatoires
    if (empty($numLicense) || empty($dateMatch) || empty($heure) || (empty($estTitulaire) && $estTitulaire != 0)) {
        deliver_response(400, "error", "Paramètre numLicense, dateMatch, heure, estTitulaire manquant");
        return;
    }

    try {
        $requete = "INSERT INTO `PARTICIPATION`
                    (numLicense, dateMatch, heure, estTitulaire, endurance, vitesse, defense, tirs, passes, poste)
                    VALUES (:numLicense, :dateMatch, :heure, :estTitulaire, :endurance, :vitesse, :defense, :tirs, :passes, :poste)";

        $req = $linkpdo->prepare($requete);

        // Utilisation de bindValue() au lieu de bindParam()
        $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindValue(':dateMatch', $dateMatch, PDO::PARAM_STR);
        $req->bindValue(':heure', $heure, PDO::PARAM_STR);
        $req->bindValue(':estTitulaire', $estTitulaire, PDO::PARAM_INT);
        $req->bindValue(':endurance', $endurance ?? 0, PDO::PARAM_INT);
        $req->bindValue(':vitesse', $vitesse ?? 0, PDO::PARAM_INT);
        $req->bindValue(':defense', $defense ?? 0, PDO::PARAM_INT);
        $req->bindValue(':tirs', $tirs ?? 0, PDO::PARAM_INT);
        $req->bindValue(':passes', $passes ?? 0, PDO::PARAM_INT);
        $req->bindValue(':poste', $poste ?? null, PDO::PARAM_STR);

        $req->execute();

        // Réponse de succès
        deliver_response(201, "success", "Donnée créée avec succès", [
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
    } catch (Exception $e) {
        error_log("Erreur lors de l'insertion : " . $e->getMessage());
        deliver_response(500, "fatal error", "Erreur lors de l'insertion : " . $e->getMessage());
    }
}


/**
 * PATCH /Participation
 * Paramètres obligatoires dans l’URL : numLicense, dateMatch, heure (identifiant de la ressource)
 * Paramètres optionnels dans le body (JSON) : estTitulaire, endurance, vitesse, defense, tirs, passes, poste
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
    // On vérifie que l'identifiant de la ressource est présent
    if (empty($numLicense) || empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "Paramètres numLicense, dateMatch, heure manquants dans l'URL");
        return;
    }

    // On construit dynamiquement la liste des champs à mettre à jour
    $fields = [];
    if (!is_null($estTitulaire)) $fields[] = "estTitulaire = :estTitulaire";
    if (!is_null($endurance))   $fields[] = "endurance = :endurance";
    if (!is_null($vitesse))     $fields[] = "vitesse = :vitesse";
    if (!is_null($defense))     $fields[] = "defense = :defense";
    if (!is_null($tirs))        $fields[] = "tirs = :tirs";
    if (!is_null($passes))      $fields[] = "passes = :passes";
    if (!is_null($poste))       $fields[] = "poste = :poste";

    // Si aucun champ à mettre à jour => erreur 400
    if (empty($fields)) {
        deliver_response(400, "error", "Aucun champ à mettre à jour : requête PATCH vide.");
        return;
    }

    $sql = "UPDATE `PARTICIPATION` SET " . implode(", ", $fields) .
           " WHERE numLicense = :numLicense AND dateMatch = :dateMatch AND heure = :heure";

    try {
        $req = $linkpdo->prepare($sql);

        // On bind uniquement les paramètres qui ne sont pas nuls
        if (!is_null($estTitulaire)) $req->bindValue(':estTitulaire', $estTitulaire, PDO::PARAM_INT);
        if (!is_null($endurance))   $req->bindValue(':endurance',   $endurance,   PDO::PARAM_INT);
        if (!is_null($vitesse))     $req->bindValue(':vitesse',     $vitesse,     PDO::PARAM_INT);
        if (!is_null($defense))     $req->bindValue(':defense',     $defense,     PDO::PARAM_INT);
        if (!is_null($tirs))        $req->bindValue(':tirs',        $tirs,        PDO::PARAM_INT);
        if (!is_null($passes))      $req->bindValue(':passes',      $passes,      PDO::PARAM_INT);
        if (!is_null($poste))       $req->bindValue(':poste',       $poste,       PDO::PARAM_STR);

        // Bind de la clé primaire
        $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $req->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $req->bindValue(':heure',      $heure,      PDO::PARAM_STR);

        $req->execute();

        // Vérifier si la ligne existe (ou si la requête n'a mis à jour aucune ligne)
        if ($req->rowCount() === 0) {
            deliver_response(404, "error", "La participation spécifiée n'existe pas ou aucune modification.");
            return;
        }

        // Récupérer la participation mise à jour pour la renvoyer
        $query = $linkpdo->prepare("
            SELECT * FROM `PARTICIPATION`
            WHERE numLicense = :numLicense
              AND dateMatch = :dateMatch
              AND heure = :heure
        ");
        $query->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $query->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $query->bindValue(':heure',      $heure,      PDO::PARAM_STR);
        $query->execute();

        $updatedParticipation = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Participation mise à jour (PATCH) avec succès", $updatedParticipation);

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour (PATCH) : " . $e->getMessage());
    }
}


/**
 * PUT /Participation
 * Paramètres obligatoires dans l’URL : numLicense, dateMatch, heure
 * Paramètres obligatoires dans le body : tous les champs (estTitulaire, endurance, vitesse, defense, tirs, passes, poste)
 */
function putParticipation(
    $linkpdo,
    $numLicense,       // Clés primaires
    $dateMatch,
    $heure,
    $estTitulaire,     // Champs obligatoires pour un PUT complet
    $endurance,
    $vitesse,
    $defense,
    $tirs,
    $passes,
    $poste
) {
    // Vérification des paramètres clés (identifiant de la ressource)
    if (empty($numLicense) || empty($dateMatch) || empty($heure)) {
        deliver_response(400, "error", "Clés primaires manquantes (numLicense, dateMatch, heure)");
        return;
    }

    // Vérification de tous les champs obligatoires pour un PUT complet
    // (Ici, on choisit d’exiger *tous* les champs. Adaptable selon votre logique métier.)
    if (
        !isset($estTitulaire) || 
        !isset($endurance)   ||
        !isset($vitesse)     ||
        !isset($defense)     ||
        !isset($tirs)        ||
        !isset($passes)      ||
        !isset($poste)
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

        // Bind des champs
        $req->bindValue(':estTitulaire', $estTitulaire, PDO::PARAM_INT);
        $req->bindValue(':endurance',    $endurance,    PDO::PARAM_INT);
        $req->bindValue(':vitesse',      $vitesse,      PDO::PARAM_INT);
        $req->bindValue(':defense',      $defense,      PDO::PARAM_INT);
        $req->bindValue(':tirs',         $tirs,         PDO::PARAM_INT);
        $req->bindValue(':passes',       $passes,       PDO::PARAM_INT);
        $req->bindValue(':poste',        $poste,        PDO::PARAM_STR);

        // Bind de la clé primaire
        $req->bindValue(':numLicense',   $numLicense,   PDO::PARAM_STR);
        $req->bindValue(':dateMatch',    $dateMatch,    PDO::PARAM_STR);
        $req->bindValue(':heure',        $heure,        PDO::PARAM_STR);

        $req->execute();

        // Vérifier si la ressource existe (rowCount)
        if ($req->rowCount() === 0) {
            // Soit la participation n'existe pas, soit aucune modification n'a été apportée
            // Selon votre logique, vous pouvez :
            // - retourner 404 si la participation n'existe pas
            // - ou vérifier si la participation existe tout de même
            $check = $linkpdo->prepare("
                SELECT COUNT(*) FROM `PARTICIPATION`
                WHERE numLicense = :numLicense
                  AND dateMatch = :dateMatch
                  AND heure = :heure
            ");
            $check->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
            $check->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
            $check->bindValue(':heure',      $heure,      PDO::PARAM_STR);
            $check->execute();

            $count = $check->fetchColumn();
            if ($count == 0) {
                deliver_response(404, "error", "La participation n'existe pas (PUT).");
                return;
            } else {
                // Sinon, aucune modification
                // On peut quand même retourner la participation courante
            }
        }

        // Récupérer la participation mise à jour
        $query = $linkpdo->prepare("
            SELECT * FROM `PARTICIPATION`
            WHERE numLicense = :numLicense
              AND dateMatch  = :dateMatch
              AND heure      = :heure
        ");
        $query->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
        $query->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
        $query->bindValue(':heure',      $heure,      PDO::PARAM_STR);
        $query->execute();

        $updated = $query->fetch(PDO::FETCH_ASSOC);

        deliver_response(200, "success", "Participation mise à jour (PUT) avec succès", $updated);

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la mise à jour (PUT) : " . $e->getMessage());
    }
}
function deleteParticipation($linkpdo, $numLicense = null, $dateMatch = null, $heure = null)
{
    try {
        // CAS 1 : Suppression de toutes les participations d’un match
        // => /Participation?dateMatch=xxx&heure=xxx (PAS de numLicense)
        if (!empty($dateMatch) && !empty($heure) && empty($numLicense)) {
            $sql = "DELETE FROM `PARTICIPATION` WHERE dateMatch = :dateMatch AND heure = :heure";
            $req = $linkpdo->prepare($sql);
            $req->bindValue(':dateMatch', $dateMatch, PDO::PARAM_STR);
            $req->bindValue(':heure',     $heure,     PDO::PARAM_STR);
            $req->execute();

            if ($req->rowCount() > 0) {
                deliver_response(
                    200,
                    "success",
                    "Toutes les participations pour le match $dateMatch à $heure ont été supprimées."
                );
            } else {
                deliver_response(404, "error", "Aucune participation trouvée pour ce match.");
            }
            return;
        }

        // CAS 2 : Suppression d’UNE participation => /Participation?numLicense=xxx&dateMatch=xxx&heure=xxx
        if (!empty($numLicense) && !empty($dateMatch) && !empty($heure)) {
            $sql = "DELETE FROM `PARTICIPATION`
                    WHERE numLicense = :numLicense
                      AND dateMatch  = :dateMatch
                      AND heure      = :heure";
            $req = $linkpdo->prepare($sql);
            $req->bindValue(':numLicense', $numLicense, PDO::PARAM_STR);
            $req->bindValue(':dateMatch',  $dateMatch,  PDO::PARAM_STR);
            $req->bindValue(':heure',      $heure,      PDO::PARAM_STR);
            $req->execute();

            if ($req->rowCount() > 0) {
                deliver_response(
                    200,
                    "success",
                    "Participation numLicense:$numLicense, dateMatch:$dateMatch, heure:$heure supprimée avec succès."
                );
            } else {
                deliver_response(
                    404,
                    "error",
                    "Aucune participation trouvée pour numLicense:$numLicense, dateMatch:$dateMatch, heure:$heure."
                );
            }
            return;
        }

        // Si on n’entre dans aucun cas, c’est que les paramètres sont insuffisants
        deliver_response(
            400,
            "error",
            "Paramètres insuffisants : besoin de (dateMatch, heure) pour tout supprimer OU (numLicense, dateMatch, heure) pour une seule participation."
        );

    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la suppression : " . $e->getMessage());
    }
}

