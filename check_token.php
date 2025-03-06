<?php

function get_authorization_header(){
	$headers = null;

	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} else if (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		//print_r($requestHeaders);
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}

	return $headers;
}

function get_bearer_token() {
    $headers = get_authorization_header();
    
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            if($matches[1]=='null') //$matches[1] est de type string et peut contenir 'null'
                return null;
            else
                return $matches[1];
        }
    }
    return null;
}

function checkTokenValidity($token) {
    $url = 'https://drafteamauthentication.lespi.fr/authentication.php?token=' . urlencode($token);

    // Initialiser une session cURL
    $ch = curl_init($url);

    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    // Exécuter la requête
    $response = curl_exec($ch);

    // Vérifier les erreurs cURL
    if (curl_errno($ch)) {
        echo 'Erreur cURL: ' . curl_error($ch);
        return false;
    }

    // Fermer la session cURL
    curl_close($ch);

    // Décoder la réponse JSON
    $responseData = json_decode($response, true);

    // Vérifier le statut de la réponse
    if (isset($responseData['status']) && $responseData['status'] === 'success') {
        return true; // Le token est valide
    } else {
        return false; // Le token est invalide
    }
}

?> 