<?php
session_start();
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require '../../config/config.php';

// Check if file is uploaded and userId is provided
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0 && isset($_POST['userId'])) {
    $userId = $_POST['userId'];
    $file = $_FILES['file'];

    // Validate file type
    $allowedExts = ['xlsx'];
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array($fileExt, $allowedExts)) {
        $_SESSION['status'] = "Invalid file type. Only .xlsx files are allowed.";
        $_SESSION['status_code'] = "error";
        header('Location: ../dtr.php');
        exit;
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($file['tmp_name']);

        // Assuming that the data is in the 3rd sheet (Table 3)
        $sheet = $spreadsheet->getSheet(2); // Index starts from 0, so Sheet 3 is index 2

        // Extract data from H4 to H34 (weekly data)
        $weekData = [];
        for ($row = 4; $row <= 34; $row++) {
            $remarksCell = $sheet->getCell('L' . $row)->getValue();
            $cellValue = $sheet->getCell('H' . $row)->getValue();

            // Convert time values with ':' to decimal format
            $cellValue = str_replace(':', '.', $cellValue); // Replace ':' with '.'

            // Check if the remarks meet the condition
            if (in_array($remarksCell, ["On Travel", "Holiday", "Health Break"])) {
                $cellValue = 8; // Set to 8 hours if remarks match
            } else {
                $cellValue = is_numeric($cellValue) ? round((float)$cellValue, 2) : 0; // Ensure numeric and round to 2 decimal places
            }
            $weekData[] = $cellValue;
        }

        // Extract the total from H35
        $totalValue = $sheet->getCell('H35')->getValue();
        $totalValue = str_replace(':', '.', $totalValue); // Replace ':' with '.'
        $totalValue = round((float)$totalValue, 2); // Convert to float and round

        // Divide the week data into 4 weeks
        $week1 = round(array_sum(array_slice($weekData, 0, 7)), 2);  // Sum first 7 days for Week 1
        $week2 = round(array_sum(array_slice($weekData, 7, 7)), 2);  // Sum next 7 days for Week 2
        $week3 = round(array_sum(array_slice($weekData, 14, 7)), 2); // Sum next 7 days for Week 3
        $week4 = round(array_sum(array_slice($weekData, 21, 7)), 2); // Sum next 7 days for Week 4

        // Calculate overtime for each week
        $otWeek1 = max(0, $week1 - 40);
        $otWeek2 = max(0, $week2 - 40);
        $otWeek3 = max(0, $week3 - 40);
        $otWeek4 = max(0, $week4 - 40);
        $otTotal = round($otWeek1 + $otWeek2 + $otWeek3 + $otWeek4, 2);

        // File upload path
        $fileName = $file['name'];
        $filePath = 'uploads/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], '../../uploads/' . $fileName)) {
            $_SESSION['status'] = "File upload failed.";
            $_SESSION['status_code'] = "error";
            header('Location: ../dtr.php');
            exit;
        }

        // Insert into dtr_data table
        $insertQuery = "INSERT INTO `dtr_data`(`userId`, `week1`, `week2`, `week3`, `week4`, `otWeek1`, `otWeek2`, `otWeek3`, `otWeek4`, `otTotal`, `total`, `filePath`, `fileName`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insertQuery);
        $stmt->bind_param('iddddddddsdss', $userId, $week1, $week2, $week3, $week4, $otWeek1, $otWeek2, $otWeek3, $otWeek4, $otTotal, $totalValue, $filePath, $fileName);


        // Execute the query and provide feedback
        if ($stmt->execute()) {
            $_SESSION['status'] = "Data imported successfully!";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "Failed to import data.";
            $_SESSION['status_code'] = "error";
        }

        header('Location: ../dtr.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['status'] = "Error loading file: " . $e->getMessage();
        $_SESSION['status_code'] = "error";
        header('Location: ../dtr.php');
        exit;
    }
} else {
    $_SESSION['status'] = "Please select a user and upload a file.";
    $_SESSION['status_code'] = "error";
    header('Location: ../dtr.php');
    exit;
}
?>
