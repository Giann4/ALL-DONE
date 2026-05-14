<?php
$registered = isset($_GET['registered']) ? $_GET['registered'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | SPIST</title>

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

    overflow:hidden;
    position:relative;
}

/* OVERLAY */
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

/* FLOATING LOGO */
.floating-logo{
    position:fixed;

    top:50%;
    left:50%;

    transform:translate(-50%, -50%);

    width:520px;
    height:520px;

    background:
        url('../assets/logo2.png')
        no-repeat center/contain;

    opacity:.10;

    filter:
        drop-shadow(0 0 40px rgba(255,255,255,.35));

    animation:
        floatingLogo 7s ease-in-out infinite,
        rotateLogo 18s linear infinite;

    z-index:1;

    pointer-events:none;
}

@keyframes floatingLogo{

    0%,100%{
        transform:
            translate(-50%, -50%)
            translateY(0)
            scale(1);
    }

    50%{
        transform:
            translate(-50%, -50%)
            translateY(-18px)
            scale(1.03);
    }
}

@keyframes rotateLogo{

    from{
        transform:
            translate(-50%, -50%)
            rotate(0deg);
    }

    to{
        transform:
            translate(-50%, -50%)
            rotate(360deg);
    }
}

/* MAIN CARD */
.login-card{
    position:relative;
    z-index:2;

    width:100%;
    max-width:1180px;

    display:grid;
    grid-template-columns:1fr 1fr;

    background:rgba(255,255,255,.12);

    border:1px solid rgba(255,255,255,.18);

    border-radius:35px;

    overflow:hidden;

    backdrop-filter:blur(22px);
    -webkit-backdrop-filter:blur(22px);

    box-shadow:0 35px 90px rgba(0,0,0,.38);
}

/* LEFT */
.left-side{
    position:relative;

    padding:65px 55px;

    background:
        radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 35%),
        linear-gradient(135deg,#0f5132 0%,#0b6b40 28%,#0d8c4e 62%,#10b15d 100%);

    display:flex;
    flex-direction:column;
    justify-content:center;

    overflow:hidden;
}

.left-side::after{
    content:"";

    position:absolute;
    inset:0;

    background:
        url('../assets/logo2.png')
        no-repeat center center;

    background-size:360px;

    opacity:.15;

    filter:
        brightness(1.3)
        drop-shadow(0 0 18px rgba(255,255,255,.20));
}

.brand-badge{
    position:relative;
    z-index:2;

    width:max-content;

    padding:9px 18px;

    border-radius:999px;

    background:rgba(255,255,255,.14);

    border:1px solid rgba(255,255,255,.18);

    color:#fff;

    font-size:12px;
    font-weight:900;

    letter-spacing:1px;

    margin-bottom:24px;
}

.school-title{
    position:relative;
    z-index:2;

    color:#fff;

    font-size:42px;
    line-height:1.18;

    font-weight:900;

    margin-bottom:22px;

    text-shadow:0 4px 16px rgba(0,0,0,.35);
}

.school-subtitle{
    position:relative;
    z-index:2;

    color:rgba(255,255,255,.94);

    font-size:21px;
    line-height:1.7;

    font-weight:600;

    max-width:470px;
}

.glass-line{
    position:absolute;

    right:0;
    top:12%;

    width:2px;
    height:76%;

    background:
        linear-gradient(
            to bottom,
            transparent,
            rgba(255,255,255,.95),
            transparent
        );

    box-shadow:
        0 0 12px rgba(255,255,255,.55),
        0 0 28px rgba(0,255,140,.22);

    animation:lineGlow 3s ease-in-out infinite;
}

@keyframes lineGlow{

    0%,100%{
        opacity:.55;
    }

    50%{
        opacity:1;
    }
}

/* RIGHT */
.right-side{
    position:relative;

    background:rgba(255,255,255,.92);

    padding:55px 48px;

    display:flex;
    align-items:center;
    justify-content:center;
}

.form-container{
    width:100%;
    max-width:430px;
}

.logo-circle{
    width:105px;
    height:105px;

    margin:0 auto 18px;

    border-radius:50%;

    padding:7px;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #16d56f
        );

    box-shadow:0 18px 35px rgba(0,0,0,.20);
}

.logo-circle img{
    width:100%;
    height:100%;

    border-radius:50%;

    object-fit:contain;

    background:#fff;
}

.form-title{
    text-align:center;

    font-size:38px;
    font-weight:900;

    color:#0a5c2d;

    margin-bottom:8px;
}

.form-subtitle{
    text-align:center;

    color:#667085;

    font-size:15px;

    margin-bottom:24px;

    font-weight:600;
}

/* ALERTS */
.success-message,
.error-message{
    padding:14px;

    border-radius:16px;

    font-size:14px;

    font-weight:800;

    text-align:center;

    margin-bottom:16px;

    line-height:1.5;
}

.success-message{
    background:#ebfff3;
    border:1px solid #9de0b7;
    color:#0a7a3d;
}

.error-message{
    background:#ffe9e9;
    border:1px solid #ffb3b3;
    color:#b10000;
}

/* INPUT */
.input-group{
    position:relative;
    margin-bottom:18px;
}

.input-group input{
    width:100%;
    height:60px;

    border:2px solid #d8e0db;

    border-radius:18px;

    padding:20px 54px 8px 18px;

    outline:none;

    font-size:16px;

    background:#fff;

    transition:.25s ease;
}

.input-group input:focus{
    border-color:#10c766;

    box-shadow:0 0 0 5px rgba(16,199,102,.14);
}

.input-group label{
    position:absolute;

    left:18px;
    top:20px;

    color:#667085;

    font-size:15px;

    pointer-events:none;

    transition:.25s ease;

    background:#fff;

    padding:0 5px;

    font-weight:700;
}

.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label{

    top:-8px;

    font-size:11px;

    color:#0a8a45;
}

.toggle-password{
    position:absolute;

    right:18px;
    top:50%;

    transform:translateY(-50%);

    cursor:pointer;

    font-size:18px;
}

/* BUTTON */
.green-btn{
    width:100%;
    height:58px;

    border:none;

    border-radius:18px;

    background:
        linear-gradient(
            135deg,
            #10c766,
            #06984b
        );

    color:#fff;

    font-size:16px;
    font-weight:900;

    cursor:pointer;

    box-shadow:0 16px 30px rgba(5,143,72,.28);

    transition:.25s ease;

    position:relative;
    overflow:hidden;
}

.green-btn:hover{
    transform:translateY(-2px);
}

.green-btn.loading{
    pointer-events:none;
    opacity:.85;
}

.green-btn.loading::after{
    content:"";

    width:20px;
    height:20px;

    border:3px solid rgba(255,255,255,.45);

    border-top-color:#fff;

    border-radius:50%;

    position:absolute;

    right:20px;
    top:50%;

    transform:translateY(-50%);

    animation:spin .8s linear infinite;
}

@keyframes spin{
    to{
        transform:translateY(-50%) rotate(360deg);
    }
}

.forgot-link{
    display:block;

    margin-top:18px;

    text-align:center;

    text-decoration:none;

    color:#0a5c2d;

    font-size:14px;

    font-weight:800;
}

.forgot-link:hover{
    color:#10c766;
}

.text-link{
    margin-top:24px;

    text-align:center;

    font-size:15px;

    color:#333;

    font-weight:700;
}

.text-link a{
    color:#6d28d9;

    text-decoration:none;

    font-weight:900;
}

.text-link a:hover{
    text-decoration:underline;
}

/* MOBILE */
@media(max-width:950px){

    .login-card{
        grid-template-columns:1fr;
    }

    .glass-line{
        display:none;
    }

    .left-side{
        padding:45px 28px;
        text-align:center;
    }

    .school-title{
        font-size:30px;
    }

    .school-subtitle{
        max-width:100%;
        font-size:18px;
    }

    .right-side{
        padding:38px 22px;
    }
}

@media(max-width:520px){

    .school-title{
        font-size:24px;
    }

    .school-subtitle{
        font-size:16px;
    }

    .form-title{
        font-size:30px;
    }

    .floating-logo{
        width:340px;
        height:340px;
    }
}

</style>
</head>

<body>

<div class="floating-logo"></div>

<div class="login-card">

    <!-- LEFT -->
    <div class="left-side">

        <div class="glass-line"></div>

        <div class="brand-badge">
            ONLINE CLEARANCE MANAGEMENT SYSTEM
        </div>

        <h1 class="school-title">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </h1>

        <p class="school-subtitle">
            Securely manage student clearance transactions,
            teacher approvals, and academic processes in one modern platform.
        </p>

    </div>

    <!-- RIGHT -->
    <div class="right-side">

        <div class="form-container">

            <div class="logo-circle">
                <img src="../assets/logo2.png" alt="Logo">
            </div>

            <h2 class="form-title">
                Welcome Back
            </h2>

            <p class="form-subtitle">
                Sign in to continue to your account
            </p>

            <?php if ($registered === '1'): ?>
                <div class="success-message">
                    Account created successfully. You can now log in.
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form
                action="process_login.php"
                method="POST"
                onsubmit="return startLoading();"
            >

                <div class="input-group">

                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder=" "
                        required
                    >

                    <label for="email">
                        Email Address
                    </label>

                </div>

                <div class="input-group">

                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        placeholder=" "
                        required
                    >

                    <label for="loginPassword">
                        Password
                    </label>

                    <span
                        class="toggle-password"
                        onclick="togglePassword('loginPassword', this)"
                    >
                        👁
                    </span>

                </div>

                <button
                    type="submit"
                    class="green-btn"
                    id="loginBtn"
                >
                    SIGN IN
                </button>

                <a href="forgot_password.php" class="forgot-link">
                    Forgot Password?
                </a>

            </form>

            <p class="text-link">
                Don’t have an account?
                <a href="register.php">
                    Create Account
                </a>
            </p>

        </div>

    </div>

</div>

<script>

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

function startLoading(){

    const btn = document.getElementById("loginBtn");

    btn.classList.add("loading");

    btn.textContent = "SIGNING IN...";

    return true;
}

</script>

</body>
</html>