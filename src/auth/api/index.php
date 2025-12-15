<?php
session_start();


header("Content-Type: application/json");

header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS")
{
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); 
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}
require_once __DIR__ . "/db.php";



$rawData = file_get_contents("php://input");




$data = json_decode($rawData, true);



if (!isset($data["email"]) || !isset($data["password"])) {
    http_response_code(400); 
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}



$email = trim($data["email"]);
$password = $data["password"];


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); 
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400); 
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long."]);
    exit;
}


try {
$database = getDBConnection();
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";


    $stmt = $database->prepare($sql);


    $stmt->execute([$email]);


    $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password"])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit;
    }

    
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["logged_in"] = true;
    $role = $user["is_admin"] ? "admin" : "user";
    $_SESSION["role"] =$role;


    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"] , "role"=>$role
        ]
    ]);
    exit;

} catch (PDOException $e) {

    error_log("Login DB Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error. Please try again later."
    ]);
    exit;

}
?>