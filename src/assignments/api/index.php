<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json

header('Content-Type: application/json; charset=utf-8');


// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');


// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}



// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class

// TODO: Create database connection


// TODO: Set PDO to throw exceptions on errors
// DB credentials
$host = "localhost";
$dbname = "assignments_db";
$username = "root";
$password = "";

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed", "details" => $e->getMessage()]);
    exit;
}


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests

$inputData = json_decode(file_get_contents("php://input"), true);

// TODO: Parse query parameters
$id = isset($_GET['id']) ? $_GET['id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : "assignments";  



// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
      $sql = "SELECT * FROM assignments WHERE 1";

    $params = [];
    
    // TODO: Check if 'search' query parameter exists in $_GET
     if (isset($_GET['search']) && $_GET['search'] !== "") {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }
    
    // TODO: Check if 'sort' and 'order' query parameters exist
        $allowedSort = ["title", "due_date", "created_at"];
    $allowedOrder = ["asc", "desc"];

    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)
        ? $_GET['sort']
        : "created_at";

    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrder)
        ? strtoupper($_GET['order'])
        : "ASC";

    $sql .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    
    // TODO: Bind parameters if search is used
       return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    // TODO: Execute the prepared statement
       $stmt->execute();
    
    // TODO: Fetch all results as associative array
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: For each assignment, decode the 'files' field from JSON to array
      foreach ($results as &$row) {
        if (isset($row['files']) && $row['files'] !== "") {
            $row['files'] = json_decode($row['files'], true);
        } else {
            $row['files'] = [];
        }
    }
    
    // TODO: Return JSON response
     return $results;
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
     if (empty($assignmentId)) {
        http_response_code(400); // Bad Request
        return [
            "error" => "Assignment ID is required."
        ];
    }
    
    // TODO: Prepare SQL query to select assignment by id
        $sql = "SELECT * FROM assignments WHERE id = :id";
    
      // TODO: Prepare statement
    $stmt = $db->prepare($sql);

    
    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
     $stmt->execute();
 
    
    // TODO: Fetch the result as associative array
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    
    // TODO: Check if assignment was found
    if (!$assignment) {
        http_response_code(404); // Not Found
        return [
            "error" => "Assignment not found."
        ];
    }


    // TODO: Decode the 'files' field from JSON to array
       if (isset($assignment['files']) && $assignment['files'] !== '') {
        $assignment['files'] = json_decode($assignment['files'], true);
    } else {
        $assignment['files'] = [];
    }
    
    // TODO: Return success response with assignment data
        return $assignment;
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
      $title       = isset($data['title'])       ? trim($data['title'])       : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $dueDate     = isset($data['due_date'])    ? trim($data['due_date'])    : '';
    $files       = isset($data['files'])       ? $data['files']             : [];

    if ($title === '' || $description === '' || $dueDate === '') {
        http_response_code(400); // Bad Request
        return [
            "error" => "title, description, and due_date are required."
        ];
    }
    
    // TODO: Sanitize input data
    $title       = htmlspecialchars($title,       ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    
    // TODO: Validate due_date format
     $dt = DateTime::createFromFormat('Y-m-d', $dueDate);
    if (!$dt || $dt->format('Y-m-d') !== $dueDate) {
        http_response_code(400);
        return [
            "error" => "due_date must be in YYYY-MM-DD format."
        ];
    }
    
    // TODO: Generate a unique assignment ID
    
    
    // TODO: Handle the 'files' field
       if (!is_array($files)) {
        $files = [];
    }
    $filesJson = json_encode($files);
    
    // TODO: Prepare INSERT query
        $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
        $stmt->bindValue(':title',       $title,       PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':due_date',    $dueDate,     PDO::PARAM_STR);
    $stmt->bindValue(':files',       $filesJson,   PDO::PARAM_STR);
    
    // TODO: Execute the statement
    $success = $stmt->execute();
    
    // TODO: Check if insert was successful
    if (!$success) {
    
    // TODO: If insert failed, return 500 error
      http_response_code(500);
    return [
        "error" => "Failed to create assignment."
    ];
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
     if (!isset($data['id']) || trim($data['id']) === '') {
        http_response_code(400); // Bad Request
        return [
            "error" => "Assignment id is required for update."
        ];
    }
    
    // TODO: Store assignment ID in variable
        $id = (int)$data['id'];
    
    // TODO: Check if assignment exists
     $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404); // Not Found
        return [
            "error" => "Assignment not found."
        ];
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
      $setParts = [];
    $params   = [];
    
    // TODO: Check which fields are provided and add to SET clause
    $setParts = [];
$params = [];

if (isset($data['title'])) {
    $setParts[] = "title = :title";
    $params[':title'] = htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8');
}

if (isset($data['description'])) {
    $setParts[] = "description = :description";
    $params[':description'] = htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8');
}

if (isset($data['due_date'])) {
    $dueDate = trim($data['due_date']);
    $dt = DateTime::createFromFormat('Y-m-d', $dueDate);

    if (!$dt || $dt->format('Y-m-d') !== $dueDate) {
        http_response_code(400);
        return ["error" => "Invalid due_date format. Use YYYY-MM-DD"];
    }

    $setParts[] = "due_date = :due_date";
    $params[':due_date'] = $dueDate;
}

if (isset($data['files'])) {
    $files = is_array($data['files']) ? $data['files'] : [];
    $setParts[] = "files = :files";
    $params[':files'] = json_encode($files);
}
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (count($setParts) === 0) {
    http_response_code(400);
    return ["error" => "No fields provided to update"];
}
    
    // TODO: Complete the UPDATE query
    $setParts[] = "updated_at = NOW()";
$sql = "UPDATE assignments SET " . implode(", ", $setParts) . " WHERE id = :id";

    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Check if update was successful
    if ($stmt->rowCount() === 0) {
    return [
        "success" => false,
        "message" => "No changes were made or assignment not found"
    ];
}
    
    // TODO: If no rows affected, return appropriate message
    return [
    "success" => true,
    "message" => "Assignment updated successfully"
];
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
      $id = (int)$assignmentId;
    if ($id <= 0) {
        http_response_code(400);
        return ["error" => "Invalid assignment id"];
    }

    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        return ["error" => "Assignment not found"];
    }
    
    // TODO: Delete associated comments first (due to foreign key constraint)
       $deleteCommentsStmt = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $deleteCommentsStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $deleteCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for assignment
        $deleteAssignmentStmt = $db->prepare("DELETE FROM assignments WHERE id = :id");

    
    // TODO: Bind the :id parameter
        $deleteAssignmentStmt->bindValue(':id', $id, PDO::PARAM_INT);

    
    // TODO: Execute the statement
        $deleteAssignmentStmt->execute();

    
    // TODO: Check if delete was successful
    if ($stmt->rowCount() === 0) {
    
    // TODO: If delete failed, return 500 error
        http_response_code(500);
    return ["error" => "Failed to delete assignment"];
}

return [
    "success" => true,
    "message" => "Assignment deleted successfully"
];
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
      if (!$assignmentId) {
        http_response_code(400);
        return ["error" => "assignment_id is required"];
    }

    
    // TODO: Prepare SQL query to select all comments for the assignment
        $sql = "SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :assignment_id parameter
        $stmt->bindParam(":assignment_id", $assignmentId, PDO::PARAM_INT);

    
    // TODO: Execute the statement
        $stmt->execute();
    
    // TODO: Fetch all results as associative array
     $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // TODO: Return success response with comments data
      return [
        "success" => true,
        "comments" => $comments
    ];
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
        if (
        !isset($data['assignment_id']) ||
        !isset($data['author']) ||
        !isset($data['text'])
    ) {
        http_response_code(400);
        return ["error" => "assignment_id, author and text are required"];
    }
    
    // TODO: Sanitize input data
     $assignmentId = (int)$data['assignment_id'];
    $author       = trim($data['author']);
    $text         = trim($data['text']);

    
    // TODO: Validate that text is not empty after trimming
        if ($author === '' || $text === '') {
        http_response_code(400);
        return ["error" => "author and text cannot be empty"];
    }
    
    // TODO: Verify that the assignment exists
      $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindParam(":id", $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        http_response_code(404);
        return ["error" => "Assignment not found"];
    }
    
    // TODO: Prepare INSERT query for comment
        $sql = "INSERT INTO comments (assignment_id, author, text, created_at)
            VALUES (:assignment_id, :author, :text, NOW())";

    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
        $stmt->bindParam(":assignment_id", $assignmentId, PDO::PARAM_INT);
    $stmt->bindParam(":author", $author, PDO::PARAM_STR);
    $stmt->bindParam(":text", $text, PDO::PARAM_STR);

    
    // TODO: Execute the statement
        $stmt->execute();

    
    // TODO: Get the ID of the inserted comment
    $commentId = $db->lastInsertId();

    
    // TODO: Return success response with created comment data
    return [
    "success" => true,
    "comment" => [
        "id" => (int)$commentId,
        "assignment_id" => $assignmentId,
        "author" => $author,
        "text" => $text,
        "created_at" => date("Y-m-d H:i:s")
    ]
];
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
        if (empty($commentId)) {
        return [
            "success" => false,
            "message" => "Comment ID is required"
        ];
    }
    
    // TODO: Check if comment exists
        $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $checkStmt->bindParam(":id", $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        return [
            "success" => false,
            "message" => "Comment not found"
        ];
    }

    
    // TODO: Prepare DELETE query
        $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");

    
    // TODO: Bind the :id parameter
        $stmt->bindParam(":id", $commentId, PDO::PARAM_INT);

    
    // TODO: Execute the statement
        $stmt->execute();

    
    // TODO: Check if delete was successful
        if ($stmt->rowCount() > 0) {
        return [
            "success" => true,
            "message" => "Comment deleted successfully"
        ];
    }

    
    // TODO: If delete failed, return 500 error
        return [
        "success" => false,
        "message" => "Failed to delete comment"
    ];
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
     $resource = $_GET['resource'] ?? null;

    if (!$resource) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing 'resource' query parameter"
        ]);
        exit;
    }
    
    // TODO: Route based on HTTP method and resource type
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
                if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $response = getAssignmentById($db, $id);
            } else {
                $response = getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if (!isset($_GET['assignment_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "assignment_id is required"]);
    exit;
}
$response = getCommentsByAssignment($db, $_GET['assignment_id']);


        } else {
            // TODO: Invalid resource, return 400 error
               http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid resource"
            ]);
            exit;
        
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
             $response = createAssignment($db, $data);

        } elseif ($resource === 'comments') {
            $response = createComment($db, $data);

        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid resource"
            ]);
            exit;
        }
            // TODO: Call createAssignment($db, $data)
            $response = createAssignment($db, $data);

        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            $response = createComment($db, $data);

        } else {
            // TODO: Invalid resource, return 400 error
            http_response_code(400);
echo json_encode(["success" => false, "message" => "Invalid resource for POST"]);
exit;

        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
            if ($resource === 'assignments') {
            $response = updateAssignment($db, $data);
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid resource for PUT"
            ]);
            exit;
        }
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            $response = updateAssignment($db, $data);

        } else {
            // TODO: PUT not supported for other resources
            http_response_code(400);
echo json_encode(["success" => false, "message" => "PUT not allowed for this resource"]);
exit;

        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
             if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Assignment ID is required"
            ]);
            exit;
        }
       $id = $_GET['id'];
        $response = deleteAssignment($db, $id);
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Comment ID is required"
            ]);
            exit;
        }

        $id = $_GET['id'];
        $response = deleteComment($db, $id);
        } else {
            // TODO: Invalid resource, return 400 error
                   http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid resource for DELETE"
        ]);
        exit;
    }
        }
        
    } else {
        // TODO: Method not supported
            http_response_code(405); // Method Not Allowed
    echo json_encode([
        "success" => false,
        "message" => "HTTP method not supported"
    ]);
    exit;
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
        http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred",
        "error" => $e->getMessage()
    ]);
    exit;

} catch (Exception $e) {
    // TODO: Handle general errors
        http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unexpected error occurred",
        "error" => $e->getMessage()
    ]);
    exit;
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
        http_response_code($statusCode);

    
    // TODO: Ensure data is an array
       if (!is_array($data)) {
        $data = ["data" => $data];
    }
    
    // TODO: Echo JSON encoded data
        echo json_encode($data, JSON_PRETTY_PRINT);

    
    // TODO: Exit to prevent further execution
        exit;

}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
        $data = trim($data);

    
    // TODO: Remove HTML and PHP tags
        $data = strip_tags($data);

    
    // TODO: Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    
    // TODO: Return the sanitized data
        return $data;

}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
        $d = DateTime::createFromFormat('Y-m-d', $date);

    
    // TODO: Return true if valid, false otherwise
        return $d && $d->format('Y-m-d') === $date;

}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    $isValid = in_array($value, $allowedValues, true);

    
    // TODO: Return the result
        return $isValid;

}

?>
