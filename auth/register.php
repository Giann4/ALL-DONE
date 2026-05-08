<?php session_start(); ?>

<?php
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error   = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<link rel="icon" type="image/png" href="../assets/logo2.png">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body.register-page{
    min-height:100vh;
    background:url("../assets/southern-night.png") no-repeat center center/cover;
    overflow-x:hidden;
}

.register-bg-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    z-index:1;
}

.register-wrapper{
    position:relative;
    z-index:2;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.register-card{
    position:relative;
    width:100%;
    max-width:1150px;
    display:grid;
    grid-template-columns:1fr 1fr;
    border-radius:30px;
    overflow:hidden;
    background:rgba(255,255,255,.15);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    box-shadow:0 25px 60px rgba(0,0,0,.28);
}

.divider-glow{
    position:absolute;
    top:10%;
    left:50%;
    transform:translateX(-50%);
    width:2px;
    height:80%;
    background:linear-gradient(to bottom, transparent, rgba(255,255,255,.95), transparent);
    box-shadow:0 0 10px rgba(255,255,255,.55), 0 0 20px rgba(0,255,140,.25);
    z-index:5;
}

.register-form-side{
    background:rgba(255,255,255,.96);
    padding:40px;
    position:relative;
}

.back-btn{
    position:absolute;
    top:20px;
    left:20px;
    width:42px;
    height:42px;
    border-radius:50%;
    background:rgba(10,92,45,.10);
    color:#0a5c2d;
    text-decoration:none;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:24px;
    font-weight:bold;
}

.form-title{
    text-align:center;
    font-size:32px;
    font-weight:900;
    color:#0a5c2d;
    margin-top:6px;
}

.form-subtitle{
    text-align:center;
    font-size:14px;
    color:#666;
    margin:8px 0 22px;
    font-weight:600;
}

.message-box{
    margin-bottom:16px;
    padding:12px 14px;
    border-radius:12px;
    text-align:center;
    font-size:14px;
    font-weight:700;
}

.message-error{
    background:#ffe8e8;
    color:#b30000;
    border:1px solid #ffb3b3;
}

.auto-hide-message{
    transition:opacity .4s ease, transform .4s ease, margin .4s ease, padding .4s ease;
}

.auto-hide-message.hide{
    opacity:0;
    transform:translateY(-8px);
    pointer-events:none;
    margin:0;
    padding-top:0;
    padding-bottom:0;
    overflow:hidden;
}

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.input-group{
    display:flex;
    flex-direction:column;
    position:relative;
}

.input-group label{
    font-size:13px;
    font-weight:700;
    color:#0a5c2d;
    margin-bottom:6px;
}

.input-group input,
.input-group select{
    width:100%;
    height:52px;
    border-radius:14px;
    border:2px solid #ddd;
    padding:0 15px;
    font-size:15px;
    background:#fff;
    outline:none;
    transition:.3s;
}

.input-group input:focus,
.input-group select:focus{
    border-color:#0bb15d;
    box-shadow:0 0 0 3px rgba(11,177,93,.12);
}

.password-group input{
    padding-right:48px;
}

.toggle-password{
    position:absolute;
    right:15px;
    top:40px;
    cursor:pointer;
    font-size:18px;
}

.helper-text{
    margin-top:6px;
    font-size:12px;
    font-weight:700;
    min-height:18px;
}

.helper-error{
    color:#c62828;
}

.helper-success{
    color:#0a8a48;
}

.email-note{
    margin-top:6px;
    font-size:12px;
    font-weight:700;
    color:#0a5c2d;
}

.submit-btn{
    width:100%;
    margin-top:22px;
    height:52px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#10c766,#06984b);
    color:#fff;
    font-size:16px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 12px 24px rgba(5,143,72,.25);
}

.footer{
    text-align:center;
    margin-top:14px;
    font-size:14px;
    color:#333;
    font-weight:600;
}

.footer a{
    color:#4f46e5;
    text-decoration:none;
    font-weight:800;
}

.register-header{
    padding:50px 46px;
    color:#fff;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,.18), transparent 28%),
        linear-gradient(135deg,#0f5132 0%,#0b6b40 28%,#0d8c4e 62%,#10b15d 100%);
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.school-title{
    font-size:34px;
    font-weight:900;
    line-height:1.15;
    margin-bottom:15px;
    text-transform:uppercase;
    text-shadow:0 3px 10px rgba(0,0,0,.25);
}

.school-subtitle{
    font-size:26px;
    font-weight:800;
    margin-bottom:14px;
    text-shadow:0 3px 10px rgba(0,0,0,.22);
}

.info-text{
    font-size:15px;
    line-height:1.8;
    margin-bottom:24px;
    color:#ecfff4;
}

.password-rules-side{
    margin-top:5px;
    padding:20px;
    border-radius:22px;
    text-align:left;

    background:
        linear-gradient(
            135deg,
            rgba(7,94,55,0.88),
            rgba(17,140,82,0.82),
            rgba(143,188,103,0.72)
        );

    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);

    border:2px solid rgba(255,255,255,0.35);

    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.25),
        0 18px 40px rgba(0,0,0,0.22),
        0 0 30px rgba(19,207,116,0.35);
}

.password-rules-title{
    color:#ffffff;
    font-size:15px;
    font-weight:900;
    margin-bottom:13px;
    text-transform:uppercase;
    letter-spacing:.4px;
    text-shadow:0 2px 8px rgba(0,0,0,0.35);
}

.password-rules-side ul{
    list-style:none;
}

.password-rules-side li{
    color:#ffffff;
    font-size:14px;
    margin:9px 0;
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:800;
    transition:.25s ease;
    text-shadow:0 2px 6px rgba(0,0,0,0.30);
}

.rule-dot{
    width:auto;
    height:auto;
    border:none;
    border-radius:0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.rule-dot::before{
    content:"○";
    font-weight:900;
    color:#ffffff;
    transition:.25s ease;
}

.password-rules-side li.valid{
    color:#d9ff9d;
    font-weight:900;
    text-shadow:0 0 10px rgba(217,255,157,.9);
}

.password-rules-side li.valid .rule-dot::before{
    content:"✓";
    color:#d9ff9d;
    text-shadow:0 0 10px rgba(217,255,157,.9);
}

.popup-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:999;
}

.popup-overlay.show{
    display:flex;
}

.success-popup{
    width:100%;
    max-width:420px;
    background:#fff;
    border-radius:24px;
    padding:30px 24px;
    text-align:center;
}

.success-icon{
    width:76px;
    height:76px;
    margin:0 auto 16px;
    border-radius:50%;
    background:linear-gradient(135deg,#10c766,#06984b);
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:38px;
    font-weight:900;
}

.redirect-text{
    margin-top:8px;
    font-size:13px;
    color:#666;
    font-weight:700;
}

@media(max-width:900px){
    .register-card{
        grid-template-columns:1fr;
    }

    .divider-glow{
        display:none;
    }
}

@media(max-width:800px){
    .grid{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body class="register-page">

<div class="register-bg-overlay"></div>

<div class="register-wrapper">
<div class="register-card">

<div class="divider-glow"></div>

<div class="register-form-side">

<a href="login.php" class="back-btn">✕</a>

<h1 class="form-title">CREATE ACCOUNT</h1>
<p class="form-subtitle">Fill in your details to continue</p>

<?php if(!empty($error)): ?>
<div class="message-box message-error auto-hide-message" id="registerMessage">
<?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<form action="process_register.php" method="POST" id="registerForm">

<div class="grid">

<div class="input-group">
<label>First Name</label>
<input type="text" name="firstname" required>
</div>

<div class="input-group">
<label>Last Name</label>
<input type="text" name="lastname" required>
</div>

<div class="input-group">
<label>Email</label>
<input
type="email"
name="email"
id="email"
required
placeholder="Enter school email"
>
<div class="email-note">Use school email only.</div>
<div id="emailText" class="helper-text"></div>
</div>

<div class="input-group">
<label>Contact Number</label>
<input
type="tel"
name="contact_number"
id="contact_number"
maxlength="11"
inputmode="numeric"
pattern="[0-9]*"
placeholder="09XXXXXXXXX"
required
>
</div>

<div class="input-group password-group">
<label>Password</label>
<input type="password" id="pass" name="password" required>
<span class="toggle-password" onclick="togglePassword('pass',this)">👁</span>
<div id="passwordStrengthText" class="helper-text"></div>
</div>

<div class="input-group">
<label>Role</label>
<select name="role" id="role" onchange="toggleCourse()" required>
<option value="">Select Role</option>
<option value="student">STUDENT</option>
<option value="teacher">TEACHER</option>
</select>
</div>

<div class="input-group password-group">
<label>Confirm Password</label>
<input type="password" id="cpass" name="confirm_password" required>
<span class="toggle-password" onclick="togglePassword('cpass',this)">👁</span>
<div id="confirmPasswordText" class="helper-text"></div>
</div>

<div class="input-group" id="courseBox" style="display:none;">
<label>Course</label>
<select name="course" id="course">
<option value="">Select Course</option>
<option value="BSIT 1">BSIT 1</option>
<option value="BSIT 2">BSIT 2</option>
<option value="BSIT 3">BSIT 3</option>
<option value="BSIT 4">BSIT 4</option>
</select>
</div>

</div>

<button type="submit" class="submit-btn">SIGN UP</button>

<div class="footer">
Already have an account? <a href="login.php">Back to Login</a>
</div>

</form>
</div>

<div class="register-header">
<h2 class="school-title">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</h2>
<p class="school-subtitle">Online Clearance Management System</p>
<p class="info-text">
Create your account to access a faster, smoother, and more organized academic clearance process for students and teachers.
</p>

<div class="password-rules-side">
<div class="password-rules-title">🛡 Password Requirements</div>
<ul>
<li id="rule-length"><span class="rule-dot"></span><span class="rule-text">At least 12 characters long</span></li>
<li id="rule-uppercase"><span class="rule-dot"></span><span class="rule-text">At least 1 uppercase letter</span></li>
<li id="rule-number"><span class="rule-dot"></span><span class="rule-text">At least 1 number</span></li>
<li id="rule-special"><span class="rule-dot"></span><span class="rule-text">At least 1 special character</span></li>
<li id="rule-match"><span class="rule-dot"></span><span class="rule-text">Password and confirm password match</span></li>
</ul>
</div>
</div>

</div>
</div>

<div id="successPopup" class="popup-overlay <?php echo ($success==='1') ? 'show' : ''; ?>">
<div class="success-popup">
<div class="success-icon">✓</div>
<h2>Success!</h2>
<p>Your account has been created successfully.</p>
<div class="redirect-text">Redirecting in <span id="countdown">3</span>...</div>
</div>
</div>

<script>
function togglePassword(id,icon){
    let input=document.getElementById(id);

    if(input.type==="password"){
        input.type="text";
        icon.textContent="🙈";
    }else{
        input.type="password";
        icon.textContent="👁";
    }
}

function toggleCourse(){
    let role=document.getElementById("role").value;
    let courseBox=document.getElementById("courseBox");
    let course=document.getElementById("course");

    if(role==="student"){
        courseBox.style.display="block";
        course.required=true;
    }else{
        courseBox.style.display="none";
        course.required=false;
        course.value="";
    }
}

const contactInput = document.getElementById("contact_number");

if (contactInput) {
    contactInput.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "").slice(0, 11);
    });

    contactInput.addEventListener("keypress", function (e) {
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });
}

const registerMessage = document.getElementById("registerMessage");

if (registerMessage) {
    setTimeout(function () {
        registerMessage.classList.add("hide");

        setTimeout(function () {
            registerMessage.remove();
        }, 400);
    }, 4000);
}

const emailInput=document.getElementById("email");
const emailText=document.getElementById("emailText");

const passwordInput=document.getElementById("pass");
const confirmPasswordInput=document.getElementById("cpass");
const passwordStrengthText=document.getElementById("passwordStrengthText");
const confirmPasswordText=document.getElementById("confirmPasswordText");
const registerForm=document.getElementById("registerForm");

const ruleLength=document.getElementById("rule-length");
const ruleUppercase=document.getElementById("rule-uppercase");
const ruleNumber=document.getElementById("rule-number");
const ruleSpecial=document.getElementById("rule-special");
const ruleMatch=document.getElementById("rule-match");

function validateSchoolEmail(){
    const email=emailInput.value.trim().toLowerCase();
    const pattern=/^c[0-9]{2}-[0-9]{4}-[0-9]{2}@spist\.edu\.ph$/;

    if(email.length===0){
        emailText.textContent="";
        return false;
    }

    if(pattern.test(email)){
        emailText.textContent="Valid school email ✔";
        emailText.className="helper-text helper-success";
        return true;
    }else{
        emailText.textContent="Invalid school email format.";
        emailText.className="helper-text helper-error";
        return false;
    }
}

function setRuleStatus(element, condition){
    if(condition){
        element.classList.add("valid");
    }else{
        element.classList.remove("valid");
    }
}

function validatePasswordRules(){
    const pass=passwordInput.value;
    const cpass=confirmPasswordInput.value;

    const hasLength=pass.length>=12;
    const hasUpper=/[A-Z]/.test(pass);
    const hasNum=/\d/.test(pass);
    const hasSpec=/[\W_]/.test(pass);
    const isMatch=pass !== "" && cpass !== "" && pass === cpass;

    setRuleStatus(ruleLength, hasLength);
    setRuleStatus(ruleUppercase, hasUpper);
    setRuleStatus(ruleNumber, hasNum);
    setRuleStatus(ruleSpecial, hasSpec);
    setRuleStatus(ruleMatch, isMatch);

    if(pass.length===0){
        passwordStrengthText.textContent="";
        passwordStrengthText.className="helper-text";
        return false;
    }

    if(hasLength && hasUpper && hasNum && hasSpec){
        passwordStrengthText.textContent="Strong password ✔";
        passwordStrengthText.className="helper-text helper-success";
        return true;
    }else{
        passwordStrengthText.textContent="Password does not meet requirements.";
        passwordStrengthText.className="helper-text helper-error";
        return false;
    }
}

function validateConfirmPassword(){
    const pass=passwordInput.value;
    const cpass=confirmPasswordInput.value;

    validatePasswordRules();

    if(cpass===""){
        confirmPasswordText.textContent="";
        confirmPasswordText.className="helper-text";
        return false;
    }

    if(pass===cpass){
        confirmPasswordText.textContent="Password matched ✔";
        confirmPasswordText.className="helper-text helper-success";
        return true;
    }else{
        confirmPasswordText.textContent="Password does not match.";
        confirmPasswordText.className="helper-text helper-error";
        return false;
    }
}

emailInput.addEventListener("input",validateSchoolEmail);

passwordInput.addEventListener("input",function(){
    validatePasswordRules();
    validateConfirmPassword();
});

confirmPasswordInput.addEventListener("input",validateConfirmPassword);

registerForm.addEventListener("submit",function(e){
    const validEmail=validateSchoolEmail();
    const strongPass=validatePasswordRules();
    const matched=validateConfirmPassword();

    if(!validEmail){
        e.preventDefault();
        alert("Please use valid school email format.");
        return;
    }

    if(!strongPass){
        e.preventDefault();
        alert("Password does not meet requirements.");
        return;
    }

    if(!matched){
        e.preventDefault();
        alert("Confirm password does not match.");
        return;
    }
});

<?php if($success==='1'): ?>
let timeLeft=3;
const countdown=document.getElementById("countdown");

const timer=setInterval(function(){
    timeLeft--;
    countdown.textContent=timeLeft;

    if(timeLeft<=0){
        clearInterval(timer);
        window.location.href="login.php";
    }
},1000);
<?php endif; ?>
</script>

</body>
</html>
