<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

if (empty($email) || empty($password)) {
    header("Location: login.php?error=" . urlencode("Please enter your email and password."));
    exit;
}

$hashed_password = md5($password);

/* ACTIVITY LOG FUNCTION */
function add_activity_log($conn, $user_id, $user_name, $user_role, $action_type, $description) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $stmt = $conn->prepare("
        INSERT INTO activity_logs 
        (user_id, user_name, user_role, action_type, action_description, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "isssss",
            $user_id,
            $user_name,
            $user_role,
            $action_type,
            $description,
            $ip_address
        );
        $stmt->execute();
    }
}

/* ADMIN / SUPER ADMIN LOGIN */
$admin_stmt = $conn->prepare("SELECT id, name, email, password, role FROM admin WHERE email = ?");
$admin_stmt->bind_param("s", $email);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();

if ($admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();

    if ($admin['password'] === $hashed_password) {

        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['name'] = $admin['name'];
        $_SESSION['email'] = $admin['email'];
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_role'] = $admin['role'];

        $display_role = ($admin['role'] === 'super_admin') ? 'Super Admin' : 'Admin';

        add_activity_log(
            $conn,
            $admin['id'],
            $admin['name'],
            $admin['role'],
            'LOGIN',
            $display_role . " logged in to the system."
        );

        if ($admin['role'] === 'super_admin') {
            header("Location: ../dashboard/super_admin.php");
            exit;
        } else {
            header("Location: ../dashboard/admin.php");
            exit;
        }
    }
}

/* STUDENT / TEACHER LOGIN */
$user_stmt = $conn->prepare("
    SELECT id, firstname, lastname, email, role, course 
    FROM users 
    WHERE email = ? AND password = ?
");
$user_stmt->bind_param("ss", $email, $hashed_password);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['course'] = $user['course'];

    $full_name = trim($user['firstname'] . " " . $user['lastname']);
    $display_role = ucfirst($user['role']);

    add_activity_log(
        $conn,
        $user['id'],
        $full_name,
        $user['role'],
        'LOGIN',
        $display_role . " logged in to the system."
    );

    if ($user['role'] === 'teacher') {
        header("Location: ../dashboard/teacher.php");
        exit;
    } elseif ($user['role'] === 'student') {
        header("Location: ../dashboard/student.php");
        exit;
    } else {
        header("Location: login.php?error=" . urlencode("Invalid user role found."));
        exit;
    }
}

header("Location: login.php?error=" . urlencode("Invalid email or password."));
exit;
?>