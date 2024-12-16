<?php
session_start();
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && isset($_POST['userId']) && isset($_POST['academicYear']) && isset($_POST['semester'])) {
    $userId = (int)$_POST['userId'];
    $academicYear = $_POST['academicYear'];
    $semester = $_POST['semester'];

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['status'] = "File upload error";
        $_SESSION['status_code'] = "error";
        header('Location: ../itl.php');
        exit(0);
    }

    // Define upload directory and get the file name
    $uploadDir = '../../uploads/';
    $fileName = basename($file['name']);
    $filePath = $uploadDir . $fileName;

    // Move the uploaded file to the target directory
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $_SESSION['status'] = "Error moving uploaded file";
        $_SESSION['status_code'] = "error";
        header('Location: ../itl.php');
        exit(0);
    }

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $facultyCredit = null;
        $designationLoadRelease = null;

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cellValue = trim($cell->getValue());

                if ($cell->getColumn() == 'D' && stripos($cellValue, 'FACULTY CREDIT') !== false) {
                    $facultyCreditRow = $cell->getRow();
                    $facultyCredit = $sheet->getCell('J' . $facultyCreditRow)->getValue();
                }

                if ($cell->getColumn() == 'E' && stripos($cellValue, 'DESIGNATION, LOAD RELEASED') !== false) {
                    $designationRow = $cell->getRow();
                    $designationLoadRelease = $sheet->getCell('J' . $designationRow)->getValue();
                }

                if ($facultyCredit !== null && $designationLoadRelease !== null) {
                    break 2;
                }
            }
        }

        // Check if necessary data is found
        if ($facultyCredit === null || $designationLoadRelease === null) {
            $_SESSION['status'] = "Required data not found in the Excel file";
            $_SESSION['status_code'] = "error";
            header('Location: ../itl.php');
            exit(0);
        }

        // Validate data
        if (!is_numeric($facultyCredit) || !is_numeric($designationLoadRelease)) {
            $_SESSION['status'] = "Invalid data in the Excel file";
            $_SESSION['status_code'] = "error";
            header('Location: ../itl.php');
            exit(0);
        }

        // Calculate overload hours
        $regularHours = 18;
        $allowableUnit = $regularHours - $designationLoadRelease;
        $totalOverload = $facultyCredit - $allowableUnit;
        $designated = ($designationLoadRelease == 0) ? 'Non-Designated' : 'Designated';

        require_once '../config/config.php';

        // SQL query with filePath and fileName
        $sql = "INSERT INTO itl_extracted_data (userId, academicYear, semester, facultyCredit, designationLoadRelease, regularHours, totalOverload, designated, filePath, fileName)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("isssidisss", $userId, $academicYear, $semester, $facultyCredit, $designationLoadRelease, $regularHours, $totalOverload, $designated, $filePath, $fileName);

            if ($stmt->execute()) {
                $_SESSION['status'] = "Data Import Successfully";
                $_SESSION['status_code'] = "success";
                header('Location: ../itl.php');
                exit(0);
            } else {
                $_SESSION['status'] = "Error inserting data: " . $stmt->error;
                $_SESSION['status_code'] = "error";
                header('Location: ../itl.php');
                exit(0);
            }

            $stmt->close();
        } else {
            $_SESSION['status'] = "Error preparing the statement: " . $con->error;
            $_SESSION['status_code'] = "error";
            header('Location: ../itl.php');
            exit(0);
        }

    } catch (Exception $e) {
        $_SESSION['status'] = 'Error loading file: ' . $e->getMessage();
        $_SESSION['status_code'] = "error";
        header('Location: ../itl.php');
        exit(0);
    }
} else {
    $_SESSION['status'] = 'Invalid request.';
    $_SESSION['status_code'] = "error";
    header('Location: ../itl.php');
    exit(0);
}
?>
