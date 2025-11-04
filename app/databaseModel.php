<?php
class databaseModel {
    public static function pdo(): PDO {
        // Dessa borde egentligen flyttas till .env
        $db   = 'bilprov';
        $user = 'biluser';
        $pass = 'bilpass';
        $dsn  = "mysql:host=db;dbname=$db;charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}

