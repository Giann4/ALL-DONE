<?php
session_start();
include("../config/db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../vendor/autoload.php";

/* GMAIL SMTP */
$sender_email = "markparedes54321@gmail.com";
$sender_app_password = "jmhy tbhz wiou mzma";

$error = "";
$lock_remaining = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST['email']));

    if (empty($email)) {

        $error = "Please enter your email.";

    } else {

        /*
            IMPORTANT:
            Huwag ide-delete ang may locked_until pa.
            Delete lang used at expired na hindi locked.
        */
        $conn->query("
            DELETE FROM password_resets
            WHERE is_used = 1
            OR (
                expires_at < NOW()
                AND (locked_until IS NULL OR locked_until < NOW())
            )
        ");

        /* CHECK LATEST OTP RECORD */
        $check = $conn->prepare("
            SELECT last_sent_at, locked_until
            FROM password_resets
            WHERE email = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $check->bind_param("s", $email);
        $check->execute();
        $last = $check->get_result()->fetch_assoc();
        $check->close();

        /* IF ACCOUNT IS LOCKED FROM VERIFY PAGE */
        if (
            $last &&
            !empty($last['locked_until']) &&
            strtotime($last['locked_until']) > time()
        ) {
            $lock_remaining = strtotime($last['locked_until']) - time();
            $error = "Too many failed attempts. Please wait before sending another OTP.";
        }

        /* 60 SECONDS RESEND PROTECTION */
        if (
            empty($error) &&
            $last &&
            !empty($last['last_sent_at'])
        ) {
            $seconds = time() - strtotime($last['last_sent_at']);

            if ($seconds < 60) {
                $error = "Please wait " . (60 - $seconds) . " seconds before sending another code.";
            }
        }

        if (empty($error)) {

            $user_type = "";

            /* CHECK USERS TABLE */
            $stmt = $conn->prepare("
                SELECT id, role
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_type = $user['role'];
            }
            $stmt->close();

            /* CHECK ADMIN TABLE */
            if (empty($user_type)) {
                $admin_stmt = $conn->prepare("
                    SELECT id
                    FROM admin
                    WHERE email = ?
                    LIMIT 1
                ");
                $admin_stmt->bind_param("s", $email);
                $admin_stmt->execute();
                $admin_result = $admin_stmt->get_result();

                if ($admin_result->num_rows > 0) {
                    $user_type = "admin";
                }

                $admin_stmt->close();
            }

            if (empty($user_type)) {

                $error = "Email not found.";

            } else {

                $otp = rand(100000, 999999);
                $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                $last_sent_at = date("Y-m-d H:i:s");

                /* DELETE OLD OTP FOR THIS EMAIL */
                $delete = $conn->prepare("
                    DELETE FROM password_resets
                    WHERE email = ?
                ");
                $delete->bind_param("s", $email);
                $delete->execute();
                $delete->close();

                /* INSERT NEW OTP */
                $insert = $conn->prepare("
                    INSERT INTO password_resets
                    (
                        email,
                        user_type,
                        otp_code,
                        expires_at,
                        is_used,
                        failed_attempts,
                        last_sent_at,
                        locked_until
                    )
                    VALUES
                    (?, ?, ?, ?, 0, 0, ?, NULL)
                ");
                $insert->bind_param(
                    "sssss",
                    $email,
                    $user_type,
                    $otp,
                    $expires_at,
                    $last_sent_at
                );

                if ($insert->execute()) {

                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = "smtp.gmail.com";
                        $mail->SMTPAuth = true;
                        $mail->Username = $sender_email;
                        $mail->Password = $sender_app_password;
                        $mail->SMTPSecure = "tls";
                        $mail->Port = 587;

                        $mail->setFrom($sender_email, "SPIST Online Clearance");
                        $mail->addAddress($email);
                        $mail->isHTML(true);

                        $mail->Subject = "SPIST Password Reset Verification Code";

                        $mail->Body = "
                            <div style='font-family:Arial;background:#f2f7f4;padding:30px;'>
                                <div style='max-width:560px;margin:auto;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #dcefe3;'>
                                    <div style='background:linear-gradient(135deg,#063946,#16c767);padding:28px;text-align:center;color:white;'>
                                        <h2 style='margin:0;font-size:24px;'>SPIST Online Clearance</h2>
                                        <p style='margin:8px 0 0;font-size:14px;'>Password Reset Verification</p>
                                    </div>

                                    <div style='padding:30px;text-align:center;'>
                                        <p style='font-size:15px;color:#333;margin-bottom:18px;'>
                                            Use the verification code below to reset your password.
                                        </p>

                                        <div style='font-size:38px;font-weight:900;letter-spacing:10px;color:#063946;background:#eafff2;border:2px dashed #16c767;border-radius:18px;padding:18px;margin:20px 0;'>
                                            $otp
                                        </div>

                                        <p style='font-size:14px;color:#555;'>
                                            This code will expire in <b>5 minutes</b>.
                                        </p>

                                        <p style='font-size:12px;color:#888;margin-top:24px;'>
                                            If you did not request this, please ignore this email.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ";

                        $mail->send();

                        $_SESSION['reset_email'] = $email;
                        header("Location: verify_code.php");
                        exit;

                    } catch (Exception $e) {
                        $error = "Failed to send OTP code. Please check Gmail App Password.";
                    }

                } else {
                    $error = "Something went wrong.";
                }

                $insert->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password</title>
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
    background:url('../assets/southern-night.png') no-repeat center center/cover;
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
    position:fixed;
    top:50%;
    left:50%;
    width:520px;
    height:520px;
    margin-left:-260px;
    margin-top:-260px;
    background:url('../assets/logo2.png') no-repeat center center/contain;
    opacity:.10;
    filter:drop-shadow(0 0 40px rgba(255,255,255,.35));
    animation:floatLogo 7s ease-in-out infinite;
    z-index:1;
    pointer-events:none;
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
    max-width:480px;
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
    background:linear-gradient(90deg,#063946,#16c767,#8fbc67);
}

.logo-wrap{
    width:105px;
    height:105px;
    margin:0 auto 18px;
    border-radius:50%;
    padding:7px;
    background:linear-gradient(135deg,#ffffff,#19d875);
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

.lock-box{
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.22);
    color:#ecfff6;
    border-radius:16px;
    padding:13px;
    text-align:center;
    font-weight:900;
    margin-bottom:17px;
    line-height:1.5;
}

.lock-box strong{
    color:#b9ffd6;
    font-size:20px;
}

.input-group{
    position:relative;
    margin-bottom:18px;
}

.input-group input{
    width:100%;
    height:60px;
    border:2px solid rgba(255,255,255,.35);
    border-radius:18px;
    padding:20px 16px 8px 52px;
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

.input-group input:disabled{
    opacity:.55;
    cursor:not-allowed;
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

.btn{
    width:100%;
    height:58px;
    border:none;
    border-radius:18px;
    background:linear-gradient(135deg,#16c767,#06984b);
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

.btn:disabled{
    opacity:.55;
    cursor:not-allowed;
    transform:none;
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

.back{
    display:block;
    text-align:center;
    margin-top:20px;
    color:#ffffff;
    text-decoration:none;
    font-weight:800;
    text-shadow:0 3px 10px rgba(0,0,0,.35);
}

.back:hover{
    text-decoration:underline;
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
        margin-left:-170px;
        margin-top:-170px;
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

    <div class="badge">Secure Account Recovery</div>

    <h2>Forgot Password?</h2>

    <p class="desc">
        Enter your registered email address and we will send a secure
        6-digit verification code to reset your password.
    </p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($lock_remaining > 0): ?>
        <div class="lock-box">
            You can request a new OTP after:<br>
            <strong id="lockCountdown">10:00</strong>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return showLoading();">
        <div class="input-group">
            <span class="input-icon">📧</span>
            <input 
                type="email" 
                name="email" 
                id="email" 
                placeholder=" " 
                required
                <?php echo ($lock_remaining > 0) ? 'disabled' : ''; ?>
            >
            <label for="email">Registered Email Address</label>
        </div>

        <button 
            type="submit" 
            class="btn" 
            id="sendBtn"
            <?php echo ($lock_remaining > 0) ? 'disabled' : ''; ?>
        >
            <?php echo ($lock_remaining > 0) ? 'LOCKED TEMPORARILY' : 'SEND VERIFICATION CODE'; ?>
        </button>
    </form>

    <div class="note">
        The verification code will expire after 5 minutes.
        Please do not share your code with anyone.
    </div>

    <a href="login.php" class="back">← Back to Login</a>

</div>

<script>
const isLocked = <?php echo ($lock_remaining > 0) ? 'true' : 'false'; ?>;

function showLoading(){
    if (isLocked) {
        return false;
    }

    const btn = document.getElementById("sendBtn");
    btn.classList.add("loading");
    btn.textContent = "SENDING CODE...";

    return true;
}

<?php if ($lock_remaining > 0): ?>
let remainingSeconds = <?php echo (int)$lock_remaining; ?>;
let countdownEl = document.getElementById("lockCountdown");

function updateCountdown(){
    if (remainingSeconds <= 0) {
        window.location.href = "forgot_password.php";
        return;
    }

    let minutes = Math.floor(remainingSeconds / 60);
    let seconds = remainingSeconds % 60;

    countdownEl.textContent =
        String(minutes).padStart(2, "0") + ":" +
        String(seconds).padStart(2, "0");

    remainingSeconds--;
}

updateCountdown();

setInterval(updateCountdown, 1000);
<?php endif; ?>
</script>

</body>
</html>