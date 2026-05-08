<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit;
}

/* FUNCTION PARA MALINIS ANG REDIRECT ERROR */
function redirect_error($message) {
    header("Location: register.php?error=" . urlencode($message));
    exit;
}

/* KUHANIN AT LINISIN ANG MGA INPUT */
$firstname        = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastname         = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$email            = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$contact_number   = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
$password         = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
$role             = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : '';
$course           = isset($_POST['course']) ? trim($_POST['course']) : '';

/* BASIC VALIDATION */
if (
    $firstname === '' ||
    $lastname === '' ||
    $email === '' ||
    $contact_number === '' ||
    $password === '' ||
    $confirm_password === '' ||
    $role === ''
) {
    redirect_error("Please fill in all required fields.");
}

/* NAME VALIDATION */
if (!preg_match("/^[a-zA-Z\sñÑ.'-]+$/u", $firstname)) {
    redirect_error("First name contains invalid characters.");
}

if (!preg_match("/^[a-zA-Z\sñÑ.'-]+$/u", $lastname)) {
    redirect_error("Last name contains invalid characters.");
}

/* CONTACT NUMBER VALIDATION */
if (!preg_match('/^[0-9]+$/', $contact_number)) {
    redirect_error("Contact number must contain numbers only.");
}

if (strlen($contact_number) !== 11) {
    redirect_error("Contact number must be exactly 11 digits.");
}

if (!preg_match('/^09[0-9]{9}$/', $contact_number)) {
    redirect_error("Contact number must start with 09.");
}

/* EMAIL VALIDATION */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("Please enter a valid email address.");
}

/* SCHOOL EMAIL ONLY */
if (!preg_match('/^c[0-9]{2}-[0-9]{4}-[0-9]{2}@spist\.edu\.ph$/', $email)) {
    redirect_error("Invalid school email. Please use your official school email.");
}

/* PASSWORD MATCH CHECK */
if ($password !== $confirm_password) {
    redirect_error("Password and Confirm Password do not match.");
}

/* STRONG PASSWORD VALIDATION */
if (strlen($password) < 12) {
    redirect_error("Password must be at least 12 characters long.");
}

if (!preg_match('/[A-Z]/', $password)) {
    redirect_error("Password must include at least 1 uppercase letter.");
}

if (!preg_match('/[0-9]/', $password)) {
    redirect_error("Password must include at least 1 number.");
}

if (!preg_match('/[\W_]/', $password)) {
    redirect_error("Password must include at least 1 special character.");
}

/* ROLE VALIDATION */
if ($role !== 'student' && $role !== 'teacher') {
    redirect_error("Invalid role selected.");
}

/* COURSE VALIDATION */
$allowed_courses = ['BSIT 1', 'BSIT 2', 'BSIT 3', 'BSIT 4'];

if ($role === 'student') {
    if ($course === '') {
        redirect_error("Please select a course for student account.");
    }

    if (!in_array($course, $allowed_courses)) {
        redirect_error("Invalid course selected.");
    }
} else {
    $course = null;
}

/* CHECK DATABASE CONNECTION */
if (!isset($conn) || $conn->connect_error) {
    redirect_error("Database connection failed.");
}

/* CHECK DUPLICATE EMAIL */
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

if (!$check_stmt) {
    redirect_error("Database error: failed to prepare email check.");
}

$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    redirect_error("Email is already registered.");
}

$check_stmt->close();

/*
    IMPORTANT:
    Ginamit pa rin ang md5 para tugma sa current login system mo.
    Kapag na-update na login mo sa password_hash/password_verify,
    saka natin papalitan ito.
*/
$hashed_password = md5($password);

/* INSERT USER */
$insert_stmt = $conn->prepare("
    INSERT INTO users 
    (firstname, lastname, email, contact_number, password, role, course)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$insert_stmt) {
    redirect_error("Database error: failed to prepare insert.");
}

$insert_stmt->bind_param(
    "sssssss",
    $firstname,
    $lastname,
    $email,
    $contact_number,
    $hashed_password,
    $role,
    $course
);

if ($insert_stmt->execute()) {
    $insert_stmt->close();
    header("Location: login.php?registered=1");
    exit;
} else {
    $insert_stmt->close();
    redirect_error("Registration failed. Please try again.");
}
?>