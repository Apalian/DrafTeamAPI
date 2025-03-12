<?php

function getMatchStats($linkpdo) {
    try {
        $sql = "
            SELECT
                COUNT(*) AS totalMatchs,
                SUM(CASE WHEN scoreEquipe > scoreAdversaire THEN 1 ELSE 0 END) AS matchsGagnes,
                SUM(CASE WHEN scoreEquipe < scoreAdversaire THEN 1 ELSE 0 END) AS matchsPerdus,
                SUM(CASE WHEN scoreEquipe = scoreAdversaire THEN 1 ELSE 0 END) AS matchsNuls,
                ROUND(SUM(CASE WHEN scoreEquipe > scoreAdversaire THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS pourcentageGagnes,
                ROUND(SUM(CASE WHEN scoreEquipe < scoreAdversaire THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS pourcentagePerdus,
                ROUND(SUM(CASE WHEN scoreEquipe = scoreAdversaire THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS pourcentageNuls
            FROM (
                SELECT
                    CASE WHEN lieuRencontre = 'Domicile' THEN scoreEquipeDomicile ELSE scoreEquipeExterne END AS scoreEquipe,
                    CASE WHEN lieuRencontre = 'Domicile' THEN scoreEquipeExterne ELSE scoreEquipeDomicile END AS scoreAdversaire
                FROM MATCHS
                WHERE scoreEquipeDomicile IS NOT NULL AND scoreEquipeExterne IS NOT NULL
            ) AS matches
        ";

        $req = $linkpdo->prepare($sql);
        $req->execute();
        $result = $req->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            deliver_response(200, "success", "Statistiques récupérées avec succès.", $result);
        } else {
            deliver_response(404, "error", "Aucune statistique trouvée.");
        }
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la récupération des statistiques : " . $e->getMessage());
    }
}

function getPlayerStats($linkpdo, $numLicense) {
    try {
        // Poste préféré
        $sqlPoste = "SELECT poste, COUNT(*) AS occurrences 
            FROM PARTICIPATION 
            WHERE numLicense = :numLicense 
            GROUP BY poste 
            ORDER BY occurrences DESC 
            LIMIT 1";
        $stmtPoste = $linkpdo->prepare($sqlPoste);
        $stmtPoste->execute(['numLicense' => $numLicense]);
        $postePrefere = $stmtPoste->fetch(PDO::FETCH_ASSOC)['poste'] ?? null;

        // Total titulaire
        $sqlTitulaire = "SELECT COUNT(*) AS total_titulaire 
            FROM PARTICIPATION 
            WHERE numLicense = :numLicense AND estTitulaire = TRUE";
        $stmtTitulaire = $linkpdo->prepare($sqlTitulaire);
        $stmtTitulaire->execute(['numLicense' => $numLicense]);
        $totalTitulaire = $stmtTitulaire->fetch(PDO::FETCH_ASSOC)['total_titulaire'] ?? 0;

        // Total remplaçant
        $sqlRemplacant = "SELECT COUNT(*) AS total_remplacant 
            FROM PARTICIPATION 
            WHERE numLicense = :numLicense AND estTitulaire = FALSE";
        $stmtRemplacant = $linkpdo->prepare($sqlRemplacant);
        $stmtRemplacant->execute(['numLicense' => $numLicense]);
        $totalRemplacant = $stmtRemplacant->fetch(PDO::FETCH_ASSOC)['total_remplacant'] ?? 0;

        // Moyennes des statistiques
        $stats = ['endurance', 'vitesse', 'defense', 'tirs', 'passes'];
        $moyennes = [];
        foreach ($stats as $stat) {
            $sql = "SELECT AVG($stat) AS moyenne_$stat 
                FROM PARTICIPATION 
                WHERE numLicense = :numLicense";
            $stmt = $linkpdo->prepare($sql);
            $stmt->execute(['numLicense' => $numLicense]);
            $moyennes[$stat] = $stmt->fetch(PDO::FETCH_ASSOC)["moyenne_$stat"] ?? null;
        }

        // Pourcentage de matchs gagnés
        $sqlVictoires = "SELECT COUNT(*) AS total_victoires 
            FROM PARTICIPATION, MATCHS 
            WHERE PARTICIPATION.numLicense = :numLicense 
            AND PARTICIPATION.dateMatch = MATCHS.dateMatch 
            AND PARTICIPATION.heure = MATCHS.heure 
            AND (
                (MATCHS.lieuRencontre = 'Domicile' AND MATCHS.scoreEquipeDomicile > MATCHS.scoreEquipeExterne) 
                OR 
                (MATCHS.lieuRencontre = 'Externe' AND MATCHS.scoreEquipeDomicile < MATCHS.scoreEquipeExterne)
            )";
        $stmtVictoires = $linkpdo->prepare($sqlVictoires);
        $stmtVictoires->execute(['numLicense' => $numLicense]);
        $totalVictoires = $stmtVictoires->fetch(PDO::FETCH_ASSOC)['total_victoires'] ?? 0;

        $sqlTotalMatchs = "SELECT COUNT(*) AS total_matchs 
            FROM PARTICIPATION 
            WHERE numLicense = :numLicense";
        $stmtMatchs = $linkpdo->prepare($sqlTotalMatchs);
        $stmtMatchs->execute(['numLicense' => $numLicense]);
        $totalMatchs = $stmtMatchs->fetch(PDO::FETCH_ASSOC)['total_matchs'] ?? 0;

        $pourcentageVictoires = $totalMatchs > 0 ? ($totalVictoires * 100.0) / $totalMatchs : 0;

        // Sélections consécutives
        $sqlConsecutives = "
            WITH CTE AS (
                SELECT 
                    dateMatch,
                    ROW_NUMBER() OVER (ORDER BY dateMatch) AS rn1,
                    ROW_NUMBER() OVER (PARTITION BY numLicense ORDER BY dateMatch) AS rn2
                FROM PARTICIPATION 
                WHERE numLicense = :numLicense
            )
            SELECT MAX(consecutive_count) AS selections_consecutives
            FROM (
                SELECT COUNT(*) AS consecutive_count
                FROM CTE
                GROUP BY rn1 - rn2
            ) AS consecutive_groups";
        $stmtConsecutives = $linkpdo->prepare($sqlConsecutives);
        $stmtConsecutives->execute(['numLicense' => $numLicense]);
        $selectionsConsecutives = $stmtConsecutives->fetch(PDO::FETCH_ASSOC)['selections_consecutives'] ?? 0;

        // Assembler toutes les statistiques
        $playerStats = [
            'postePrefere' => $postePrefere,
            'totalTitulaire' => $totalTitulaire,
            'totalRemplacant' => $totalRemplacant,
            'pourcentageMatchsGagnes' => round($pourcentageVictoires, 2),
            'moyenneEndurance' => round($moyennes['endurance'], 2),
            'moyenneVitesse' => round($moyennes['vitesse'], 2),
            'moyenneDefense' => round($moyennes['defense'], 2),
            'moyenneTirs' => round($moyennes['tirs'], 2),
            'moyennePasses' => round($moyennes['passes'], 2),
            'selectionsConsecutives' => $selectionsConsecutives
        ];

        deliver_response(200, "success", "Statistiques du joueur récupérées avec succès.", $playerStats);
    } catch (PDOException $e) {
        deliver_response(500, "fatal error", "Erreur lors de la récupération des statistiques du joueur : " . $e->getMessage());
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
