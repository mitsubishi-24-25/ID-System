<?php
// AJAX endpoint for real-time database availability checks
header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

$response = ['available' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

if (empty($value)) {
    $response['message'] = 'Value is required';
    echo json_encode($response);
    exit;
}

switch ($field) {
    case 'employee_id':
        if (!preg_match('/^\d{7}$/', $value)) {
            $response['message'] = 'Employee ID must be exactly 7 digits';
            break;
        }
        $sql = "SELECT id FROM users WHERE employee_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $value);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $response['available'] = false;
                $response['message'] = 'Employee ID already registered';
            } else {
                $response['available'] = true;
                $response['message'] = 'Employee ID is available';
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error';
        }
        break;

    case 'full_name':
        if (strlen($value) < 2) {
            $response['message'] = 'Name is too short';
            break;
        }
        $sql = "SELECT id FROM users WHERE full_name = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $value);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $response['available'] = false;
                $response['message'] = 'This name is already registered';
            } else {
                $response['available'] = true;
                $response['message'] = 'Name is available';
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error';
        }
        break;

    case 'section':
        $sql = "SELECT id FROM users WHERE section = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $value);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $response['available'] = false;
                $response['message'] = 'This section is already taken';
            } else {
                $response['available'] = true;
                $response['message'] = 'Section is available';
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error';
        }
        break;

    default:
        $response['message'] = 'Invalid field';
        break;
}

echo json_encode($response);
exit;
?>
