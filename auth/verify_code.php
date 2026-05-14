<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = "";
$email = $_SESSION['reset_email'];
$locked_until_time = 0;
$is_locked = false;

/* CHECK CURRENT LOCK STATUS ON PAGE LOAD */
$check_lock = $conn->prepare("
    SELECT locked_until
    FROM password_resets
    WHERE email = ?
    ORDER BY id DESC
    LIMIT 1
");
$check_lock->bind_param("s", $email);
$check_lock->execute();
$lock_row = $check_lock->get_result()->fetch_assoc();
$check_lock->close();

if ($lock_row && !empty($lock_row['locked_until']) && strtotime($lock_row['locked_until']) > time()) {
    $locked_until_time = strtotime($lock_row['locked_until']);
    $is_locked = true;
    $error = "Too many failed attempts. Please wait before requesting a new code.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$is_locked) {

    $otp = preg_replace('/[^0-9]/', '', $_POST['otp_code']);

    $stmt = $conn->prepare("
        SELECT id, email, otp_code, expires_at, is_used, failed_attempts, locked_until
        FROM password_resets
        WHERE email = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {

        $error = "No OTP record found. Please send code again.";

    } elseif ($row['is_used'] == 1) {

        $error = "This code was already used. Please send code again.";

    } elseif (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {

        $locked_until_time = strtotime($row['locked_until']);
        $is_locked = true;
        $error = "Too many failed attempts. Please wait before requesting a new code.";

    } elseif (strtotime($row['expires_at']) < time()) {

        $error = "Your verification code has expired. Please send code again.";

    } elseif ((int)$row['failed_attempts'] >= 5) {

        $locked_until = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $locked_until_time = strtotime($locked_until);
        $is_locked = true;

        $lock = $conn->prepare("
            UPDATE password_resets
            SET locked_until = ?
            WHERE id = ?
        ");
        $lock->bind_param("si", $locked_until, $row['id']);
        $lock->execute();
        $lock->close();

        $error = "Too many failed attempts. Please wait before requesting a new code.";

    } elseif (trim($row['otp_code']) !== trim($otp)) {

        $new_attempts = (int)$row['failed_attempts'] + 1;

        if ($new_attempts >= 5) {

            $locked_until = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            $locked_until_time = strtotime($locked_until);
            $is_locked = true;

            $update = $conn->prepare("
                UPDATE password_resets
                SET failed_attempts = ?, locked_until = ?
                WHERE id = ?
            ");
            $update->bind_param("isi", $new_attempts, $locked_until, $row['id']);
            $update->execute();
            $update->close();

            $error = "Too many failed attempts. Please wait before requesting a new code.";

        } else {

            $update = $conn->prepare("
                UPDATE password_resets
                SET failed_attempts = ?
                WHERE id = ?
            ");
            $update->bind_param("ii", $new_attempts, $row['id']);
            $update->execute();
            $update->close();

            $remaining = 5 - $new_attempts;
            $error = "Incorrect verification code. Attempts left: " . max(0, $remaining);
        }

    } else {

        $verified_at = date("Y-m-d H:i:s");

        $update = $conn->prepare("
            UPDATE password_resets
            SET verified_at = ?
            WHERE id = ?
        ");
        $update->bind_param("si", $verified_at, $row['id']);
        $update->execute();
        $update->close();

        $_SESSION['otp_verified'] = true;

        header("Location: reset_password.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Code</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../assets/logo2.png">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

html, body{
    width:100%;
    min-height:100%;
    overflow-x:hidden;
}

body{
    min-height:100vh;
    background:url('../assets/southern-night.png') no-repeat center center/cover;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:25px;
    position:relative;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:
        radial-gradient(circle at 20% 20%, rgba(22,199,103,.35), transparent 30%),
        linear-gradient(135deg, rgba(0,40,25,.72), rgba(0,0,0,.58));
    backdrop-filter:blur(5px);
    z-index:1;
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
    opacity:.09;
    filter:drop-shadow(0 0 40px rgba(255,255,255,.35));
    z-index:1;
    pointer-events:none;
    animation:logoFloat 7s ease-in-out infinite;
}

@keyframes logoFloat{
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
    max-width:520px;
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

.email-text{
    color:#b9ffd6;
    font-weight:900;
    word-break:break-word;
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

.otp-group{
    width:100%;
    display:grid;
    grid-template-columns:repeat(6, minmax(0, 1fr));
    gap:10px;
    margin-bottom:18px;
}

.otp-box{
    width:100%;
    min-width:0;
    height:62px;
    border:2px solid rgba(255,255,255,.35);
    border-radius:16px;
    background:rgba(255,255,255,.94);
    text-align:center;
    font-size:26px;
    font-weight:900;
    color:#063946;
    outline:none;
    transition:.22s ease;
}

.otp-box:focus{
    border-color:#19d875;
    box-shadow:0 0 0 4px rgba(25,216,117,.18);
}

.otp-box:disabled{
    opacity:.45;
    cursor:not-allowed;
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

.actions{
    display:flex;
    gap:10px;
    margin-top:18px;
    flex-wrap:wrap;
}

.back,
.resend{
    flex:1;
    min-width:160px;
    text-align:center;
    color:#ffffff;
    text-decoration:none;
    font-weight:800;
    padding:13px 12px;
    border-radius:16px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.18);
    text-shadow:0 3px 10px rgba(0,0,0,.35);
}

.back:hover,
.resend:hover{
    background:rgba(255,255,255,.20);
}

.resend.disabled{
    opacity:.45;
    pointer-events:none;
}

@media(max-width:520px){
    body{
        padding:18px;
    }

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

    .otp-group{
        gap:7px;
    }

    .otp-box{
        height:54px;
        font-size:22px;
        border-radius:13px;
    }

    .actions{
        flex-direction:column;
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

    <div class="badge">Secure Verification</div>

    <h2>Verify Code</h2>

    <p class="desc">
        Enter the 6-digit code sent to:<br>
        <span class="email-text"><?php echo htmlspecialchars($email); ?></span>
    </p>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($is_locked): ?>
        <div class="lock-box">
            You can request a new code after:<br>
            <strong id="lockCountdown">10:00</strong>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return submitOtp();">
        <input type="hidden" name="otp_code" id="otpCode">

        <div class="otp-group">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box" <?php echo $is_locked ? 'disabled' : ''; ?>>
        </div>

        <button 
            type="submit" 
            class="btn" 
            id="verifyBtn"
            <?php echo $is_locked ? 'disabled' : ''; ?>
        >
            <?php echo $is_locked ? 'LOCKED TEMPORARILY' : 'VERIFY CODE'; ?>
        </button>
    </form>

    <div class="note">
        Your code expires after 5 minutes. You have up to 5 attempts before requesting a new code.
    </div>

    <div class="actions">
        <a href="forgot_password.php" class="resend <?php echo $is_locked ? 'disabled' : ''; ?>">
            Send Code Again
        </a>
        <a href="login.php" class="back">Back to Login</a>
    </div>

</div>

<script>
const boxes = document.querySelectorAll(".otp-box");
const hiddenOtp = document.getElementById("otpCode");
const verifyBtn = document.getElementById("verifyBtn");
const isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;

window.addEventListener("load", function(){
    if (!isLocked && boxes.length > 0) {
        boxes[0].focus();
    }
});

boxes.forEach((box, index) => {
    box.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "");

        if (this.value && index < boxes.length - 1) {
            boxes[index + 1].focus();
        }

        updateHiddenOtp();
    });

    box.addEventListener("keydown", function (e) {
        if (e.key === "Backspace" && !this.value && index > 0) {
            boxes[index - 1].focus();
        }
    });

    box.addEventListener("paste", function (e) {
        e.preventDefault();

        const paste = (e.clipboardData || window.clipboardData)
            .getData("text")
            .replace(/[^0-9]/g, "")
            .slice(0, 6);

        paste.split("").forEach((num, i) => {
            if (boxes[i]) {
                boxes[i].value = num;
            }
        });

        updateHiddenOtp();

        if (paste.length === 6) {
            boxes[5].focus();
        }
    });
});

function updateHiddenOtp() {
    let code = "";
    boxes.forEach(box => code += box.value);
    hiddenOtp.value = code;
}

function submitOtp() {
    if (isLocked) {
        return false;
    }

    updateHiddenOtp();

    if (hiddenOtp.value.length !== 6) {
        alert("Please enter the complete 6-digit code.");
        return false;
    }

    verifyBtn.classList.add("loading");
    verifyBtn.textContent = "VERIFYING...";

    return true;
}

<?php if ($is_locked): ?>
let lockEndTime = <?php echo $locked_until_time * 1000; ?>;
let countdownEl = document.getElementById("lockCountdown");

const lockTimer = setInterval(function(){
    let now = new Date().getTime();
    let distance = lockEndTime - now;

    if(distance <= 0){
        clearInterval(lockTimer);
        window.location.href = "forgot_password.php";
        return;
    }

    let minutes = Math.floor(distance / (1000 * 60));
    let seconds = Math.floor((distance % (1000 * 60)) / 1000);

    countdownEl.textContent =
        String(minutes).padStart(2, "0") + ":" +
        String(seconds).padStart(2, "0");
}, 1000);
<?php endif; ?>
</script>

</body>
</html>