<?php
session_start();
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['userId'];
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];
    $phoneNumber = $_POST['phoneNumber'];
    $status = $_POST['status'];
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

    // Prepare the SQL update query for user details
    $query = "UPDATE employee 
              SET firstName = ?, middleName = ?, lastName = ?, emailAddress = ?, phoneNumber = ?, status = ?
              WHERE userId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('ssssssi', $firstName, $middleName, $lastName, $emailAddress, $phoneNumber, $status, $userId);

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        // Only update roles if any are selected
        if (!empty($roles)) {
            // Delete current roles and insert new ones
            $deleteRolesQuery = "DELETE FROM employee_role WHERE userId = ?";
            $stmtDelete = $con->prepare($deleteRolesQuery);
            $stmtDelete->bind_param('i', $userId);
            $stmtDelete->execute();

            // Insert new roles
            $insertRolesQuery = "INSERT INTO employee_role (userId, role_id) VALUES (?, ?)";
            $stmtInsert = $con->prepare($insertRolesQuery);
            foreach ($roles as $role) {
                $stmtInsert->bind_param('ii', $userId, $role);
                $stmtInsert->execute();
            }
        }

        $_SESSION['status'] = 'User Updated!';
        $_SESSION['status_code'] = 'success';
        header('Location: ../user.php');
        exit;
    } else {
        // Redirect back with an error message
        header('Location: ../users.php?error=Failed to update user');
    }

    $stmt->close();
}
?>
