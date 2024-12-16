<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileName = $_FILES['file']['tmp_name'];
    $fileType = $_FILES['file']['type'];

    if (!in_array($fileType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
        $_SESSION['status'] = "Invalid file type. Please upload an Excel file.";
        $_SESSION['status_code'] = "error";
        header('Location: ../user.php');
        exit(0);
    }

    try {
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $conn = new mysqli('localhost', 'root', '', 'ucheque');
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("
            INSERT INTO employee (employeeId, lastName, firstName, emailAddress, password)
            VALUES (?, ?, ?, ?, ?)
        ");

        $roleStmt = $conn->prepare("
            INSERT INTO employee_role (userId, role_id)
            VALUES (?, ?)
        ");

        $facultyRoleId = 2;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $facultyId = trim($row[0]);
            $lastName = trim($row[1]);
            $firstName = trim($row[2]);
            $email = trim($row[3]);

            $password = $lastName . $facultyId;

            $checkStmt = $conn->prepare("SELECT * FROM employee WHERE employeeId = ?");
            $checkStmt->bind_param('s', $facultyId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->bind_param(
                    'sssss',
                    $facultyId,
                    $lastName,
                    $firstName,
                    $email,
                    $password
                );
                $stmt->execute();

                $userId = $stmt->insert_id;

                $roleStmt->bind_param('ii', $userId, $facultyRoleId);
                $roleStmt->execute();
            }

            $checkStmt->close();
        }

        $_SESSION['status'] = "Data successfully imported.";
        $_SESSION['status_code'] = "success";

        $stmt->close();
        $roleStmt->close();
        $conn->close();

        header('Location: ../user.php');
        exit(0);

    } catch (Exception $e) {
        $_SESSION['status'] = "Error: " . $e->getMessage();
        $_SESSION['status_code'] = "error";
        header('Location: ../user.php');
        exit(0);
    }
}
?>
