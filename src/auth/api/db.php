<?php

function getDBConnection(): PDO
{
    $host = "localhost";
    $db   = "web_db";
    $user = "root";
    $pass = "";

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass
        );

       
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;

    } catch (PDOException $e) {
        
        error_log("DB Connection Error: " . $e->getMessage());
        die("Database connection failed");
    }
}
