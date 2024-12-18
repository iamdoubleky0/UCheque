<?php
session_start();
require '../../vendor/autoload.php'; // Load PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileName = $_FILES['file']['tmp_name'];
    $fileType = $_FILES['file']['type'];

    // Check if the file type is Excel (xls, xlsx)
    if (!in_array($fileType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
        $_SESSION['status'] = "Invalid file type. Please upload an Excel file.";
        $_SESSION['status_code'] = "error";
        header('Location: ../user.php');
        exit(0);
    }

    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $conn = new mysqli('localhost', 'root', '', 'ucheque');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("
            INSERT INTO employee (employeeId, lastName, firstName, emailAddress, password, department)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $roleStmt = $conn->prepare("
            INSERT INTO employee_role (userId, role_id)
            VALUES (?, ?)
        ");

        // Default department and roles
        $departments = [
            1 => 'Information Technology',
            2 => 'Technology Communication Management',
            3 => 'Computer Science',
            4 => 'Data Science'
        ];

        // Define faculty role ID
        $facultyRoleId = 2;
        $missingData = false;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; 

            $facultyId = trim($row[0]);  
            $lastName = trim($row[1]);   
            $firstName = trim($row[2]); 
            $email = trim($row[3]);     
            $departmentName = trim($row[4]);

            // Check if any required data is missing
            if (empty($facultyId) || empty($lastName) || empty($firstName) || empty($email) || empty($departmentName)) {
                $missingData = true; 
                break; 
            }

            // Check if department is valid
            $departmentId = array_search($departmentName, $departments);
            if ($departmentId === false) {
                $departmentId = null;
            }

            $password = $lastName . $facultyId;
            
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $checkStmt = $conn->prepare("SELECT * FROM employee WHERE employeeId = ?");
            $checkStmt->bind_param('s', $facultyId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->bind_param(
                    'sssssi',
                    $facultyId, 
                    $lastName, 
                    $firstName, 
                    $email, 
                    $hashedPassword,
                    $departmentId 
                );
                $stmt->execute();

                $userId = $stmt->insert_id;

                $roleStmt->bind_param('ii', $userId, $facultyRoleId);
                $roleStmt->execute();
            }
            $checkStmt->close();
        }

        if ($missingData) {
            $_SESSION['status'] = "Invalid data, the file is missing required data.";
            $_SESSION['status_code'] = "error";
            header('Location: ../user.php');
            exit(0);
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
