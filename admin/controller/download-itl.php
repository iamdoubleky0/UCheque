<?php
session_start();
require '../config/config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['file_id'])) {
    $file_id = $_GET['file_id'];

    $query = "SELECT `fileName`, `filePath` FROM `itl_extracted_data` WHERE `id` = ?";
    $stmt = $con->prepare($query);

    if (!$stmt) {
        die("Error preparing the query: " . $con->error);
    }

    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($fileName, $filePath);

    if ($stmt->fetch()) {
        $uploadsDir = realpath('../../uploads/');
        $fullFilePath = $uploadsDir . DIRECTORY_SEPARATOR . $filePath;

        echo "Resolved Full File Path: " . $fullFilePath . "<br>";

        if (file_exists($fullFilePath)) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // Correct MIME type for .xlsx files
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Content-Length: ' . filesize($fullFilePath));

            readfile($fullFilePath);
            exit;
        } else {
            $_SESSION['status'] = "File not found at: " . $fullFilePath;
            $_SESSION['status_code'] = "error";
            header('Location: ../itl.php');
            exit;
        }
    } else {
        $_SESSION['status'] = "Invalid file ID";
        $_SESSION['status_code'] = "error";
        header('Location: ../itl.php');
        exit;
    }
} else {
    $_SESSION['status'] = "Invalid request";
    $_SESSION['status_code'] = "error";
    header('Location: ../itl.php');
    exit;
}
