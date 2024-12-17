<?php
session_start();
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] == 0) {
        $originalFileName = basename($file['name']);
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        $newFileName = time() . '-' . uniqid() . '.' . $fileExtension;
        
        $uploadDirectory = '../../uploads/'; 
        
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true); 
        }

        $filePath = $uploadDirectory . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $monthYear = $sheet->getCell("G5")->getValue();
            $monthYear = str_replace("Month/Year: ", "", $monthYear);

            $days = [];
            $totals = [];

            for ($row = 10; $row <= 40; $row++) {
                $day = $sheet->getCell("A$row")->getValue();
                $total = $sheet->getCell("G$row")->getValue();
                $days[] = $day;
                $totals[] = $total;
            }

            function convertToDecimal($time) {
                return str_replace(":", ".", $time); 
            }

            $weekTotals = [];
            $weekCount = 1;
            $weekSum = 0;

            foreach ($totals as $index => $total) {
                $totalDecimal = convertToDecimal($total);
                $weekSum += (float)$totalDecimal; 
                if (($index + 1) % 7 == 0 || $index == count($totals) - 1) {
                    $weekTotals["week$weekCount"] = $weekSum;
                    $weekCount++;
                    $weekSum = 0; 
                }
            }

            $overallTotal = 0;
            foreach ($weekTotals as $weekTotal) {
                $overallTotal += $weekTotal;
            }

            $userId = $_POST['userId'];
            $academicYearId = $_POST['academic_year_id'];
            $semesterId = $_POST['semester_id'];

            $dateCreated = date('Y-m-d H:i:s');

            $query = "INSERT INTO dtr_extracted_data (userId, academic_year_id, semester_id, week1, week2, week3, week4, week5, overall_total, filePath, dateCreated, month_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $con->prepare($query);

            if (!$stmt) {
                die('Error preparing statement: ' . $con->error);
            }

            $stmt->bind_param("iiidddddddss", $userId, $academicYearId, $semesterId, 
            $weekTotals['week1'], $weekTotals['week2'], 
            $weekTotals['week3'], $weekTotals['week4'], 
            $weekTotals['week5'], $overallTotal, $filePath, $dateCreated, $monthYear);

            $executeResult = $stmt->execute();

            if ($executeResult) {
                $_SESSION['success_message'] = "DTR imported successfully!";
                header("Location: ../dtr.php");
                exit();
            }            

            $stmt->close();
        } else {
            echo "Error uploading file!";
        }
    } else {
        echo "Error uploading file!";
    }
}
?>