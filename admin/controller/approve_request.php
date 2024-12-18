<?php
session_start();
require '../../vendor/autoload.php';
require '../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['requestId'])) {
    $requestId = $_POST['requestId'];

    $query = "UPDATE request SET status = 'Approved' WHERE requestId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Request has been approved successfully!";
        $_SESSION['message_type'] = "success"; 
    } else {
        $_SESSION['message'] = "Error occurred while updating the request.";
        $_SESSION['message_type'] = "danger";
    }

    $stmt->close();
    $con->close();
}
?>
