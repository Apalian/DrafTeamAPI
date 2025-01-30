<?php
$pdo = new PDO("mysql:host=localhost;dbname=u847486544_drafteam", "u847486544_root", "Jesaplgrout123456789*");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
return $pdo;