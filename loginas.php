<?php
session_start();

$user_roles = isset($_SESSION['roles']) ? $_SESSION['roles'] : [];

if (empty($user_roles)) {
    header("Location: ../login.php");
    exit();
}

if (count($user_roles) === 1) {
    $single_role = $user_roles[0];
    switch ($single_role) {
        case 'Admin':
            $_SESSION['status'] = "Welcome " . $_SESSION['auth_user']['firstName'] . ' ' . $_SESSION['auth_user']['lastName'];
            $_SESSION['status_code'] = "success";
            header("Location: ./admin/index.php");
            break;
        case 'Hr':
            header("Location: ./hr/h_dash.php");
            break;
        case 'Staff':
            header("Location: ./staff/s_dash.php");
            break;
        case 'Faculty':
            header("Location: ./faculty/f_dash.php");
            break;
        default:
            $_SESSION['status'] = "Role not recognized.";
            $_SESSION['status_code'] = "error";
            header("Location: ../login.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/login.css">
    <title>Ucheque</title>
</head>

<body>
    <div class="login">
        <img src="./admin/assets/images/login-bg.jpg" alt="login image" class="login__img">

        <form method="POST" action="./controller/role_redirect.php" class="login__form">
            <div class="logo--cont">
                <img src="./admin/assets/images/logo-dark.png" alt="" />
            </div>

            <h1 class="login__title">Login as</h1>
            <br>

            <!-- Dynamically render buttons based on roles -->
            <?php if (in_array('Hr', $user_roles)): ?>
            <button type="submit" name="selected_role" value="Hr" class="login__button1">HR</button>
            <?php endif; ?>

            <?php if (in_array('Staff', $user_roles)): ?>
            <button type="submit" name="selected_role" value="Staff" class="login__button1">Staff</button>
            <?php endif; ?>

            <?php if (in_array('Faculty', $user_roles)): ?>
            <button type="submit" name="selected_role" value="Faculty" class="login__button1">Faculty</button>
            <?php endif; ?>

            <?php if (in_array('Admin', $user_roles)): ?>
            <button type="submit" name="selected_role" value="Admin" class="login__button1">Admin</button>
            <?php endif; ?>
        </form>

    </div>

    <script src="assets/main.js"></script>
</body>

</html>
