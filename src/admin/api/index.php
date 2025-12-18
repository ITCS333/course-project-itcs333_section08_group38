<?php
session_start();
if (empty($_SESSION['logged_in'])) {
  http_response_code(401);
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student"s university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// TODO: Get the PDO database connection
require_once __DIR__ . "/Database.php";

$db = getDBConnection();


// TODO: Get the HTTP request method
// Use $_SERVER["REQUEST_METHOD"]
$method = $_SERVER["REQUEST_METHOD"];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents("php://input") to get raw POST data
// Decode JSON data using json_decode()

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true)??[];



// TODO: Parse query parameters for filtering and searching
$search = $_GET["search"] ?? null;
$sort = $_GET["sort"] ?? null;
$order = $_GET["order"] ?? null;
$studentId = $_GET["student_id"] ?? null;
$action = $_GET["action"] ?? null;

function getUserStudents($db) {
    $stmt = $db->prepare("SELECT name, email FROM users WHERE is_admin = 0 ORDER BY id DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        "status" => "success",
        "data" => $rows
    ]);
}

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    $query="SELECT id, student_id, name, email, created_at FROM students";
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    if(isset($_GET["search"])){
        $search=$_GET["search"];
        $like="%".$search."%";
        $query.=" WHERE name LIKE ? OR student_id LIKE ? OR email LIKE ?";
    } 
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    if(isset($_GET["sort"])){
        $sort=$_GET["sort"];
        $allowedSortFields = ["name", "student_id", "email"];
        $allowedOrder = ["asc", "desc"];
        if(in_array($sort,$allowedSortFields)){
            $query.=" ORDER BY ".$sort;

            if(isset($_GET["order"]) ){
                $order=$_GET["order"];
                if(in_array($order,$allowedOrder)){ 
                $query.=" ".$order;}
            }
        }
    }
    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    $stmt = $db->prepare($query);

    // TODO: Bind parameters if using search
    if(isset($_GET["search"])){
        $stmt->bindParam(1,$like);
        $stmt->bindParam(2,$like);
        $stmt->bindParam(3,$like);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    sendResponse([
        "status" => "success",
        "data" => $students
    ]);
}



/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student"s university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $stmt = $db->prepare("SELECT id, student_id, name, email, created_at FROM students WHERE student_id = ?");

    // TODO: Bind the student_id parameter
    $stmt->bindParam(1, $studentId);

    // TODO: Execute the query
    $stmt ->execute();
    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);


    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if($student){
        sendResponse([
            "status" => "success",
            "data" => $student
        ]);
    } else {
        sendResponse([
            "status" => "error",
            "message" => "Student not found"
        ], 404);
    }
}

function createUserStudent($db, $data) {
    if (
        !isset($data["name"]) || !isset($data["email"]) || !isset($data["password"]) ||
        empty($data["name"]) || empty($data["email"]) || empty($data["password"])
    ) {
        sendResponse([
            "status" => "error",
            "message" => "Missing required information"
        ], 400);
    }

    $name = trim($data["name"]);
    $email = trim($data["email"]);
    $password = trim($data["password"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse([
            "status" => "error",
            "message" => "Invalid email format"
        ], 400);
    }

    // check email exists in users
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            "status" => "error",
            "message" => "Email already exists"
        ], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
    $ok = $stmt->execute([$name, $email, $hashedPassword]);

    if ($ok) {
        sendResponse([
            "status" => "success",
            "message" => "User student created successfully"
        ], 201);
    }

    sendResponse([
        "status" => "error",
        "message" => "Failed to create user student"
    ], 500);
}

/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student"s university ID (must be unique)
 *   - name: Student"s full name
 *   - email: Student"s email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data["student_id"]) || 
        !isset($data["name"]) || 
        !isset($data["email"]) || 
        !isset($data["password"]) || empty($data["student_id"]) || 
        empty($data["name"]) || empty($data["email"]) || 
        empty($data["password"]))
    {
        sendResponse([
            "status" => "error",
            "message" => "Missing required information"
        ],400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $student_id = trim($data["student_id"]);
    $name = trim($data["name"]);
    $email = trim($data["email"]);
    $password = trim($data["password"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse([
            "status" => "error",
            "message" => "Invalid email format"
        ],400);
    }

   

    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $stmt= $db ->prepare("SELECT * FROM students WHERE student_id = ? OR email = ?");
    $stmt->execute([$student_id,$email]);

    if($stmt-> fetch(PDO::FETCH_ASSOC))
    {
        sendResponse([
            "status" => "error",
            "message" => "Student ID or Email already exists"
        ],409);
    }

    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password , PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
    $stmt = $db ->prepare("INSERT INTO students (student_id, name, email, password, created_at) VALUES (?, ?, ?, ?, NOW())");

    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $stmt -> bindParam (1, $student_id);
    $stmt -> bindParam (2, $name);
    $stmt -> bindParam (3, $email);
    $stmt -> bindParam (4, $hashedPassword);
    // TODO: Execute the query
    $check = $stmt -> execute();

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status

    if($check){
        sendResponse([
            "status" => "success",
            "message" => "Student created successfully"
        ],201);
    } else {
        sendResponse([
            "status" => "error",
            "message" => "Failed to create student"
        ],500);
    }


}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student"s university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!isset($data["student_id"])|| empty($data["student_id"])) {
        sendResponse([
            "status" => "error",
            "message" => "Missing student_id"
        ],400);
        return;
    }

    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt -> execute([$data["student_id"]]);
    if(!$stmt-> fetch(PDO::FETCH_ASSOC)){
        sendResponse([
            "status" => "error",
            "message" => "Student not found"
        ],404);
        return;
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request

   $allowedFields = ["name", "email"];
        $query = "UPDATE students SET ";
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = trim((string)$data[$field]);
                if ($value !== "") {
                    if (count($params) > 0) {
                        $query .= ", ";
                    }
                    $query .= "$field = ? ";
                    $params[] = $value;
                }
            }
        }
        if (count($params) === 0) {
    sendResponse([
        "status" => "error",
        "message" => "No fields provided to update"
    ], 400);
    return;
    }
            $query .= " WHERE student_id = ? ";
            $params[] = $data["student_id"];
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
        sendResponse([
            "status" => "success",
            "message" => "Student updated successfully"
        ]);
    } else {
        sendResponse([
            "status" => "error",
            "message" => "Failed to update student"
        ], 500);
    }

}

function deleteUserStudent($db, $data) {

    $email = $data["email"] ?? null;

    if (!$email || trim($email) === "") {
        sendResponse([
            "status" => "error",
            "message" => "Missing email"
        ], 400);
    }

    // check user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            "status" => "error",
            "message" => "User not found"
        ], 404);
    }

    // delete from users
    $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$email]);

    sendResponse([
        "status" => "success",
        "message" => "User student deleted successfully"
    ]);
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student"s university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!$studentId || trim($studentId) === "") {
        sendResponse([
            "status" => "error",
            "message" => "Missing student_id"
        ],400);
        return;
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt -> execute([$studentId]);
    if(!$stmt-> fetch(PDO::FETCH_ASSOC)){
        sendResponse([
            "status" => "error",
            "message" => "Student not found"
        ],404);
        return;
    }

    // TODO: Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");

    // TODO: Bind the student_id parameter
    $stmt->bindParam(1, $studentId);

    // TODO: Execute the query
   $stmt -> execute();

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if($stmt -> rowCount() > 0){
        sendResponse([
            "status" => "success",
            "message" => "Student deleted successfully"
        ]);
    } else {
        sendResponse([
            "status" => "error",
            "message" => "Failed to delete student"
        ],500);
    }

}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student"s university ID (identifies whose password to change)
 *   - current_password: The student"s current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {

    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data["student_id"]) || 
        !isset($data["current_password"]) || 
        !isset($data["new_password"]) || 
        empty($data["student_id"]) || 
        empty($data["current_password"]) || 
        empty($data["new_password"]))
    {
        sendResponse([
            "status" => "error",
            "message" => "Missing required information"
        ],400);
        return;
    }
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($data["new_password"]) < 8) {
        sendResponse([
            "status" => "error",
            "message" => "New password must be at least 8 characters long"
        ],400);
        return;
    }

    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = ?");
    $stmt -> execute([$data["student_id"]]);
    $row = $stmt-> fetch(PDO::FETCH_ASSOC);

    if (!$row) {
    sendResponse([
        "status" => "error",
        "message" => "Student not found"
    ], 404);
}
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($data["current_password"], $row["password"])) {
        sendResponse([
            "status" => "error",
            "message" => "Current password is incorrect"
        ],401);
        return;
    }

    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $newHashedPassword = password_hash($data["new_password"], PASSWORD_DEFAULT);

    // TODO: Update password in database
    // Prepare UPDATE query
    $stmt = $db->prepare("UPDATE students SET password = ? WHERE student_id = ?");

    // TODO: Bind parameters and execute
     $stmt->bindParam(1, $newHashedPassword);
    $stmt->bindParam(2, $data["student_id"]);
    $stmt -> execute();

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if($stmt -> rowCount() > 0){
        sendResponse([
            "status" => "success",
            "message" => "Password changed successfully"
        ]);
    } else {
        sendResponse([
            "status" => "error",
            "message" => "Failed to change password"
        ],500);
    }
}
function changeUserPassword($db, $data) {

    if (
        !isset($data["email"]) ||
        !isset($data["current_password"]) ||
        !isset($data["new_password"]) ||
        empty($data["email"]) ||
        empty($data["current_password"]) ||
        empty($data["new_password"])
    ) {
        sendResponse([
            "status" => "error",
            "message" => "Missing required information"
        ], 400);
    }

    $email = trim($data["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse([
            "status" => "error",
            "message" => "Invalid email format"
        ], 400);
    }

    // get user by email (from users table)
    $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse([
            "status" => "error",
            "message" => "User not found"
        ], 404);
    }

    if (!password_verify($data["current_password"], $row["password"])) {
        sendResponse([
            "status" => "error",
            "message" => "Current password is incorrect"
        ], 401);
    }

    if (strlen($data["new_password"]) < 8) {
        sendResponse([
            "status" => "error",
            "message" => "New password must be at least 8 characters long"
        ], 400);
    }

    $newHashedPassword = password_hash($data["new_password"], PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$newHashedPassword, $email]);

    if ($stmt->rowCount() > 0) {
        sendResponse([
            "status" => "success",
            "message" => "Password changed successfully"
        ]);
    }

    sendResponse([
        "status" => "error",
        "message" => "Failed to change password"
    ], 500);
}

function updateUserStudent($db, $data) {
    $oldEmail = trim($data["old_email"] ?? "");
    $name = trim($data["name"] ?? "");
    $newEmail = trim($data["email"] ?? "");

    if ($oldEmail === "" || $name === "" || $newEmail === "") {
        sendResponse(["status"=>"error","message"=>"Missing required information"], 400);
    }

    if (!filter_var($oldEmail, FILTER_VALIDATE_EMAIL) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        sendResponse(["status"=>"error","message"=>"Invalid email format"], 400);
    }

    // user exists?
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$oldEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(["status"=>"error","message"=>"User not found"], 404);
    }

    // if changing email, check duplicate
    if ($oldEmail !== $newEmail) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$newEmail]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            sendResponse(["status"=>"error","message"=>"Email already exists"], 409);
        }
    }

    $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE email = ?");
    $stmt->execute([$name, $newEmail, $oldEmail]);

    sendResponse(["status"=>"success","message"=>"User updated successfully"]);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method

    if ($method === "GET") {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if ($studentId) {
            getStudentById($db, $studentId);
        } else {
            getStudents($db);
        }

    } elseif ($method === "POST") {

        if ($action === "change_password") {
            changePassword($db, $data);

        } elseif ($action === "change_user_password") {
            changeUserPassword($db, $data);

        } elseif ($action === "create_user_student") {
            createUserStudent($db, $data);

        } else {
            createStudent($db, $data);
        }
    } elseif ($method === "PUT") {

        if ($action === "update_user_student") {
            updateUserStudent($db, $data);
        } else {
            updateStudent($db, $data);
        }

    }
 elseif ($method === "DELETE") {

        if ($action === "delete_user_student") {
            deleteUserStudent($db, $data);
        } else {
            $studentIdToDelete = $studentId ?? ($data["student_id"] ?? null);
            deleteStudent($db, $studentIdToDelete);
        }
    }else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        http_response_code(405);
        echo json_encode([
            "status" => "error",
            "message" => "Method Not Allowed"
        ]);
        exit;
    }

} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log($e->getMessage());
    sendResponse([
        "status" => "error",
        "message" => "Database error occurred"
    ], 500);
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    sendResponse([
        "status" => "error",
        "message" => "An unexpected error occurred"
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
      if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>