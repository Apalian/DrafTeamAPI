<?PHP
$server = 'localhost';
$login = 'u847486544_root';
$mdp = 'Jesaplgrout123456789*';
$db = 'u847486544_drafteam';


try {
    $linkpdo = new PDO("mysql:host=$server;dbname=$db", $login, $mdp);
}
catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>