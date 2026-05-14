<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = "";
$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (
        strlen($password) < 12 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^a-zA-Z0-9]/', $password)
    ) {

        $error = "Password must be at least 12 characters with uppercase, number, and special character.";

    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            SELECT user_type 
            FROM password_resets 
            WHERE email = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        $user_type = $data['user_type'] ?? '';

        if ($user_type === "admin") {

            $update = $conn->prepare("
                UPDATE admin 
                SET password = ? 
                WHERE email = ?
            ");

        } else {

            $update = $conn->prepare("
                UPDATE users 
                SET password = ? 
                WHERE email = ?
            ");
        }

        $update->bind_param("ss", $hashed_password, $email);

        if ($update->execute()) {

            /* ACTIVITY LOG - PASSWORD RESET */
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            $user_name = $email;
            $user_role = $user_type;

            $action_type = "PASSWORD RESET";

            $description = ucfirst($user_type) . " reset password using Gmail OTP verification.";

            $log = $conn->prepare("
                INSERT INTO activity_logs
                (
                    user_id,
                    user_name,
                    user_role,
                    action_type,
                    action_description,
                    ip_address
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($log) {

                $zero_id = 0;

                $log->bind_param(
                    "isssss",
                    $zero_id,
                    $user_name,
                    $user_role,
                    $action_type,
                    $description,
                    $ip_address
                );

                $log->execute();
                $log->close();
            }

            /* MARK OTP AS USED */
            $mark = $conn->prepare("
                UPDATE password_resets 
                SET is_used = 1 
                WHERE email = ?
            ");

            $mark->bind_param("s", $email);
            $mark->execute();
            $mark->close();

            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);

            header("Location: login.php?success=" . urlencode("Password reset successfully. You can now log in."));
            exit;

        } else {

            $error = "Failed to update password.";
        }

        $update->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="../assets/logo2.png">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    min-height:100vh;

    background:
        url('../assets/southern-night.png')
        no-repeat center center/cover;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:25px;

    position:relative;
    overflow:hidden;
}

body::before{
    content:"";

    position:fixed;
    inset:0;

    background:
        radial-gradient(circle at 20% 20%, rgba(22,199,103,.35), transparent 30%),
        linear-gradient(135deg, rgba(0,40,25,.70), rgba(0,0,0,.55));

    backdrop-filter:blur(5px);
}

.floating-logo{
    position:absolute;

    width:520px;
    height:520px;

    background:
        url('../assets/logo2.png')
        no-repeat center/contain;

    opacity:.10;

    filter:drop-shadow(0 0 40px rgba(255,255,255,.35));

    animation:floatLogo 7s ease-in-out infinite;
}

@keyframes floatLogo{
    0%,100%{
        transform:translateY(0) scale(1);
    }

    50%{
        transform:translateY(-18px) scale(1.03);
    }
}

.card{
    position:relative;
    z-index:2;

    width:100%;
    max-width:540px;

    background:rgba(255,255,255,.16);

    border:1px solid rgba(255,255,255,.28);

    border-radius:32px;

    padding:34px;

    backdrop-filter:blur(22px);
    -webkit-backdrop-filter:blur(22px);

    box-shadow:0 30px 80px rgba(0,0,0,.35);

    overflow:hidden;
}

.card::before{
    content:"";

    position:absolute;

    top:0;
    left:0;
    right:0;

    height:7px;

    background:
        linear-gradient(
            90deg,
            #063946,
            #16c767,
            #8fbc67
        );
}

.logo-wrap{
    width:105px;
    height:105px;

    margin:0 auto 18px;

    border-radius:50%;

    padding:7px;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #19d875
        );

    box-shadow:0 18px 35px rgba(0,0,0,.25);
}

.logo-wrap img{
    width:100%;
    height:100%;

    border-radius:50%;

    object-fit:contain;

    background:#fff;
}

.badge{
    width:max-content;

    margin:0 auto 14px;

    padding:8px 16px;

    border-radius:999px;

    background:rgba(255,255,255,.20);

    color:#eafff2;

    font-size:12px;
    font-weight:900;

    letter-spacing:.8px;

    text-transform:uppercase;

    border:1px solid rgba(255,255,255,.22);
}

h2{
    text-align:center;

    color:#ffffff;

    font-size:32px;
    font-weight:900;

    margin-bottom:10px;

    text-shadow:0 4px 14px rgba(0,0,0,.35);
}

.desc{
    text-align:center;

    color:rgba(255,255,255,.90);

    font-size:15px;
    line-height:1.6;

    margin-bottom:22px;

    font-weight:600;
}

.error{
    background:rgba(255,230,230,.95);

    color:#b10000;

    border:1px solid #ffb3b3;

    padding:13px;

    border-radius:16px;

    text-align:center;

    font-weight:800;

    margin-bottom:17px;

    line-height:1.4;
}

.input-group{
    position:relative;
    margin-bottom:15px;
}

.input-group input{
    width:100%;
    height:60px;

    border:2px solid rgba(255,255,255,.35);

    border-radius:18px;

    padding:20px 55px 8px 52px;

    font-size:16px;

    outline:none;

    background:rgba(255,255,255,.93);

    color:#123;

    font-weight:700;

    transition:.25s ease;
}

.input-group input:focus{
    border-color:#19d875;

    box-shadow:0 0 0 5px rgba(25,216,117,.18);
}

.input-group label{
    position:absolute;

    left:52px;
    top:20px;

    color:#61706a;

    font-size:15px;

    pointer-events:none;

    transition:.25s ease;

    font-weight:700;
}

.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label{
    top:7px;

    font-size:11px;

    color:#0a8a45;
}

.input-icon{
    position:absolute;

    left:18px;
    top:50%;

    transform:translateY(-50%);

    font-size:20px;

    z-index:2;
}

.toggle-password{
    position:absolute;

    right:18px;
    top:50%;

    transform:translateY(-50%);

    cursor:pointer;

    font-size:18px;

    z-index:2;
}

.strength-box{
    margin:8px 0 18px;
}

.strength-top{
    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:8px;

    color:#ecfff6;

    font-size:13px;

    font-weight:900;
}

.strength-track{
    width:100%;
    height:10px;

    background:rgba(255,255,255,.18);

    border-radius:999px;

    overflow:hidden;

    border:1px solid rgba(255,255,255,.16);
}

.strength-fill{
    height:100%;
    width:0%;

    border-radius:999px;

    background:#ef4444;

    transition:.25s ease;
}

.requirements{
    display:grid;
    grid-template-columns:1fr 1fr;

    gap:9px;

    margin-bottom:18px;
}

.req{
    background:rgba(255,255,255,.13);

    border:1px solid rgba(255,255,255,.18);

    color:#ecfff6;

    border-radius:14px;

    padding:10px 12px;

    font-size:13px;

    line-height:1.3;

    font-weight:800;

    transition:.2s ease;
}

.req.done{
    background:rgba(34,197,94,.22);

    border-color:rgba(34,197,94,.45);

    color:#dfffee;
}

.match-note{
    margin:-4px 0 16px;

    font-size:13px;

    font-weight:900;

    color:#ffdddd;

    min-height:18px;
}

.match-note.ok{
    color:#b9ffd6;
}

.btn{
    width:100%;
    height:58px;

    border:none;

    border-radius:18px;

    background:
        linear-gradient(
            135deg,
            #16c767,
            #06984b
        );

    color:#fff;

    font-size:16px;
    font-weight:900;

    cursor:pointer;

    box-shadow:0 15px 30px rgba(5,143,72,.35);

    transition:.25s ease;

    position:relative;
    overflow:hidden;
}

.btn:hover{
    transform:translateY(-2px);
}

.btn.loading{
    pointer-events:none;
    opacity:.85;
}

.btn.loading::after{
    content:"";

    width:20px;
    height:20px;

    border:3px solid rgba(255,255,255,.45);

    border-top-color:#fff;

    border-radius:50%;

    position:absolute;

    right:22px;
    top:50%;

    transform:translateY(-50%);

    animation:spin .8s linear infinite;
}

@keyframes spin{
    to{
        transform:translateY(-50%) rotate(360deg);
    }
}

.note{
    margin-top:18px;

    background:rgba(255,255,255,.13);

    border:1px solid rgba(255,255,255,.18);

    color:#ecfff6;

    border-radius:16px;

    padding:13px;

    font-size:13px;

    line-height:1.5;

    text-align:center;

    font-weight:700;
}

@media(max-width:520px){

    .card{
        padding:28px 20px;
        border-radius:26px;
    }

    h2{
        font-size:27px;
    }

    .floating-logo{
        width:340px;
        height:340px;
    }

    .requirements{
        grid-template-columns:1fr;
    }
}

</style>
</head>

<body>

<div class="floating-logo"></div>

<div class="card">

    <div class="logo-wrap">
        <img src="../assets/logo2.png" alt="SPIST Logo">
    </div>

    <div class="badge">
        Create New Password
    </div>

    <h2>Reset Password</h2>

    <p class="desc">
        Create a strong password to protect your Online Clearance account.
    </p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return submitReset();">

        <div class="input-group">

            <span class="input-icon">🔒</span>

            <input
                type="password"
                name="password"
                id="newPassword"
                placeholder=" "
                required
                oninput="checkStrength()"
            >

            <label for="newPassword">
                New Password
            </label>

            <span
                class="toggle-password"
                onclick="togglePassword('newPassword', this)"
            >
                👁
            </span>

        </div>

        <div class="strength-box">

            <div class="strength-top">
                <span>Password Strength</span>
                <span id="strengthText">Weak</span>
            </div>

            <div class="strength-track">
                <div class="strength-fill" id="strengthFill"></div>
            </div>

        </div>

        <div class="requirements">

            <div class="req" id="reqLength">
                ○ At least 12 characters
            </div>

            <div class="req" id="reqUpper">
                ○ Uppercase letter
            </div>

            <div class="req" id="reqNumber">
                ○ Number
            </div>

            <div class="req" id="reqSpecial">
                ○ Special character
            </div>

        </div>

        <div class="input-group">

            <span class="input-icon">✅</span>

            <input
                type="password"
                name="confirm_password"
                id="confirmPassword"
                placeholder=" "
                required
                oninput="checkMatch()"
            >

            <label for="confirmPassword">
                Confirm Password
            </label>

            <span
                class="toggle-password"
                onclick="togglePassword('confirmPassword', this)"
            >
                👁
            </span>

        </div>

        <div class="match-note" id="matchNote"></div>

        <button
            type="submit"
            class="btn"
            id="resetBtn"
        >
            RESET PASSWORD
        </button>

    </form>

    <div class="note">
        Your new password must be unique and should not be shared with anyone.
    </div>

</div>

<script>

const passwordInput = document.getElementById("newPassword");
const confirmInput = document.getElementById("confirmPassword");

const strengthFill = document.getElementById("strengthFill");
const strengthText = document.getElementById("strengthText");

const resetBtn = document.getElementById("resetBtn");

const matchNote = document.getElementById("matchNote");

function togglePassword(inputId, icon){

    const input = document.getElementById(inputId);

    if(input.type === "password"){

        input.type = "text";
        icon.textContent = "🙈";

    }else{

        input.type = "password";
        icon.textContent = "👁";
    }
}

function setReq(id, passed, text){

    const el = document.getElementById(id);

    el.classList.toggle("done", passed);

    el.textContent = (passed ? "✓ " : "○ ") + text;
}

function checkStrength(){

    const pass = passwordInput.value;

    const hasLength = pass.length >= 12;
    const hasUpper = /[A-Z]/.test(pass);
    const hasNumber = /[0-9]/.test(pass);
    const hasSpecial = /[^a-zA-Z0-9]/.test(pass);

    setReq("reqLength", hasLength, "At least 12 characters");
    setReq("reqUpper", hasUpper, "Uppercase letter");
    setReq("reqNumber", hasNumber, "Number");
    setReq("reqSpecial", hasSpecial, "Special character");

    let score = 0;

    if(hasLength) score++;
    if(hasUpper) score++;
    if(hasNumber) score++;
    if(hasSpecial) score++;

    const width = [0,25,50,75,100][score];

    strengthFill.style.width = width + "%";

    if(score <= 1){

        strengthFill.style.background = "#ef4444";
        strengthText.textContent = "Weak";

    }else if(score === 2){

        strengthFill.style.background = "#f59e0b";
        strengthText.textContent = "Fair";

    }else if(score === 3){

        strengthFill.style.background = "#84cc16";
        strengthText.textContent = "Good";

    }else{

        strengthFill.style.background = "#22c55e";
        strengthText.textContent = "Strong";
    }

    checkMatch();
}

function checkMatch(){

    const pass = passwordInput.value;
    const confirm = confirmInput.value;

    if(confirm.length === 0){

        matchNote.textContent = "";
        matchNote.classList.remove("ok");
        return;
    }

    if(pass === confirm){

        matchNote.textContent = "✓ Passwords match.";
        matchNote.classList.add("ok");

    }else{

        matchNote.textContent = "Passwords do not match.";
        matchNote.classList.remove("ok");
    }
}

function submitReset(){

    const pass = passwordInput.value;
    const confirm = confirmInput.value;

    if(
        pass.length < 12 ||
        !/[A-Z]/.test(pass) ||
        !/[0-9]/.test(pass) ||
        !/[^a-zA-Z0-9]/.test(pass)
    ){

        alert("Please complete all password requirements.");
        return false;
    }

    if(pass !== confirm){

        alert("Passwords do not match.");
        return false;
    }

    resetBtn.classList.add("loading");
    resetBtn.textContent = "RESETTING...";

    return true;
}

checkStrength();

</script>

</body>
</html>