<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$message_type = "";
$current_page = basename($_SERVER['PHP_SELF']);

$is_super_admin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';

$admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$admin_name = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';
$admin_email = "admin@gmail.com";

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

$default_photo = "../assets/logo2.png";
$upload_dir = "../assets/uploads/admin/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* GET ADMIN INFO */
if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT id, name, email, password, profile_photo, role FROM admin WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin = $admin_stmt->get_result()->fetch_assoc();

    if ($admin) {
        $admin_name = $admin['name'];
        $admin_email = $admin['email'];
    } else {
        die("Admin not found.");
    }
} else {
    die("Admin session not found.");
}

/* UPLOAD CROPPED PHOTO */
if (isset($_POST['upload_photo'])) {
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $cropped_image = trim($_POST['cropped_image']);

        if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,/', $cropped_image, $matches)) {
            $file_ext = strtolower($matches[1]);

            if ($file_ext === 'jpeg') {
                $file_ext = 'jpg';
            }

            $allowed = ['jpg', 'png', 'gif', 'webp'];

            if (!in_array($file_ext, $allowed)) {
                $message = "Invalid cropped image format.";
                $message_type = "error";
            } else {
                $image_data = substr($cropped_image, strpos($cropped_image, ',') + 1);
                $image_data = str_replace(' ', '+', $image_data);
                $decoded_image = base64_decode($image_data);

                if ($decoded_image === false) {
                    $message = "Invalid cropped image data.";
                    $message_type = "error";
                } elseif (strlen($decoded_image) > 8 * 1024 * 1024) {
                    $message = "Cropped image is too large. Max 8MB only.";
                    $message_type = "error";
                } else {
                    $new_file_name = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
                    $target_file = $upload_dir . $new_file_name;

                    if (file_put_contents($target_file, $decoded_image) !== false) {
                        if (!empty($admin['profile_photo'])) {
                            $old_file = $upload_dir . $admin['profile_photo'];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }

                        $update_photo = $conn->prepare("UPDATE admin SET profile_photo = ? WHERE id = ?");
                        $update_photo->bind_param("si", $new_file_name, $admin_id);

                        if ($update_photo->execute()) {
                            $message = "Admin profile photo uploaded successfully.";
                            $message_type = "success";
                            $admin['profile_photo'] = $new_file_name;
                        } else {
                            if (file_exists($target_file)) {
                                @unlink($target_file);
                            }
                            $message = "Photo saved, but database update failed.";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Failed to save cropped photo.";
                        $message_type = "error";
                    }
                }
            }
        } else {
            $message = "Invalid cropped image format.";
            $message_type = "error";
        }
    } else {
        $message = "Please choose and crop an image first.";
        $message_type = "error";
    }
}

/* CHANGE PASSWORD */
if (isset($_POST['change_password'])) {
    $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    $has_length = strlen($new_password) >= 12;
    $has_uppercase = preg_match('/[A-Z]/', $new_password);
    $has_number = preg_match('/[0-9]/', $new_password);
    $has_special = preg_match('/[\W_]/', $new_password);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all password fields.";
        $message_type = "error";
    } elseif (!$has_length || !$has_uppercase || !$has_number || !$has_special) {
        $message = "New password must meet all password requirements.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $message_type = "error";
    } else {
        $stored_password = $admin['password'];
        $password_matched = false;

        if ($stored_password === md5($current_password)) {
            $password_matched = true;
        } elseif (password_verify($current_password, $stored_password)) {
            $password_matched = true;
        } elseif ($stored_password === $current_password) {
            $password_matched = true;
        }

        if (!$password_matched) {
            $message = "Current password is incorrect.";
            $message_type = "error";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed_password, $admin_id);

            if ($update_stmt->execute()) {
                $message = "Admin password changed successfully.";
                $message_type = "success";
                $admin['password'] = $new_hashed_password;
            } else {
                $message = "Failed to update admin password.";
                $message_type = "error";
            }
        }
    }
}

/* ADMIN PHOTO PATH */
if (!empty($admin['profile_photo']) && file_exists($upload_dir . $admin['profile_photo'])) {
    $admin_photo = $upload_dir . $admin['profile_photo'];
} else {
    $admin_photo = $default_photo;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Change Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="../assets/logo2.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#07111f;
    color:#e9f1ff;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:
        radial-gradient(circle at 15% 10%, rgba(124,58,237,.25), transparent 30%),
        radial-gradient(circle at 90% 20%, rgba(37,99,235,.20), transparent 25%),
        linear-gradient(135deg,#06101d,#0a1424,#07111f);
    z-index:-1;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:255px;
    height:100vh;
    background:rgba(7,15,28,.86);
    border-right:1px solid rgba(255,255,255,.10);
    backdrop-filter:blur(18px);
    padding:18px 14px;
    overflow-y:auto;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:22px;
}

.brand img{
    width:45px;
    height:45px;
    object-fit:contain;
}

.brand h2{
    font-size:20px;
    color:#fff;
}

.brand span{
    font-size:11px;
    color:#d7def0;
    font-weight:700;
}

.super-card{
    background:linear-gradient(135deg,#312e81,#6d28d9);
    padding:15px;
    border-radius:10px;
    display:flex;
    gap:13px;
    align-items:center;
    margin-bottom:24px;
}

.super-card img{
    width:48px;
    height:48px;
    border-radius:50%;
    object-fit:cover;
    background:rgba(255,255,255,.10);
    padding:2px;
}

.super-card h3{
    font-size:14px;
}

.super-card p{
    font-size:12px;
    color:#d8d4ff;
}

.menu-title{
    font-size:11px;
    color:#8894aa;
    margin:18px 0 10px;
}

.nav a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:13px 12px;
    margin-bottom:8px;
    border-radius:7px;
    color:#d7def0;
    text-decoration:none;
    font-size:14px;
    transition:.2s;
}

.nav a:hover,
.nav a.active{
    background:linear-gradient(135deg,#4c1d95,#5b21b6);
    color:#fff;
}

.nav a.logout{
    color:#ff5d5d;
}

.nav i{
    width:20px;
}

/* MAIN */
.main{
    margin-left:255px;
    min-height:100vh;
}

.topbar{
    height:64px;
    border-bottom:1px solid rgba(255,255,255,.10);
    background:rgba(8,16,29,.70);
    backdrop-filter:blur(14px);
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 22px;
}

.top-left{
    display:flex;
    align-items:center;
    gap:15px;
}

.top-left i{
    color:#c7d2fe;
    font-size:20px;
}

.topbar h1{
    font-size:18px;
    color:#fff;
}

.topbar h1 span{
    color:#8b5cf6;
}

.profile-top{
    display:flex;
    align-items:center;
    gap:12px;
}

.profile-icon{
    width:42px;
    height:42px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#7c3aed,#2563eb);
    font-size:20px;
}

.profile-top small{
    color:#aab5ca;
}

.content{
    padding:18px;
}

.page-head{
    margin-bottom:18px;
}

.breadcrumb{
    font-size:13px;
    color:#9ca3af;
    margin-bottom:8px;
}

.page-title{
    font-size:28px;
    color:#ffffff;
    margin-bottom:5px;
}

.page-subtitle{
    color:#9ca3af;
    font-size:14px;
}

.message{
    padding:14px 16px;
    border-radius:8px;
    margin-bottom:18px;
    font-weight:700;
}

.message.success{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
    border:1px solid rgba(5,150,105,.35);
}

.message.error{
    background:rgba(220,38,38,.18);
    color:#ffb4b4;
    border:1px solid rgba(220,38,38,.35);
}

.page-grid{
    display:grid;
    grid-template-columns:1.3fr .7fr;
    gap:18px;
    align-items:start;
}

.card,
.profile-panel-card{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.card h2,
.profile-panel-card h2{
    color:#fff;
    font-size:18px;
    margin-bottom:8px;
}

.card-sub{
    color:#9ca3af;
    margin-bottom:18px;
    font-size:14px;
    line-height:1.5;
}

.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;
    font-size:13px;
    font-weight:700;
    color:#dbeafe;
    margin-bottom:7px;
}

.required{
    color:#ff6b6b;
}

.input-wrap{
    position:relative;
}

.form-group input{
    width:100%;
    height:50px;
    padding:0 78px 0 14px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    outline:none;
    font-size:14px;
}

.form-group input:focus{
    border-color:#8b5cf6;
    box-shadow:0 0 0 4px rgba(139,92,246,.15);
}

.toggle-password{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    border:none;
    border-radius:7px;
    background:#374151;
    color:#fff;
    padding:7px 11px;
    font-size:12px;
    font-weight:800;
    cursor:pointer;
}

.save-btn{
    border:none;
    padding:14px 22px;
    border-radius:8px;
    background:linear-gradient(135deg,#6d28d9,#2563eb);
    color:white;
    font-weight:900;
    cursor:pointer;
    font-size:14px;
}

.save-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(109,40,217,.35);
}

/* PHOTO CARD */
.upload-row{
    display:flex;
    justify-content:flex-end;
    margin-bottom:12px;
}

.upload-btn{
    display:inline-flex;
    align-items:center;
    gap:7px;
    background:#374151;
    color:#fff;
    border:none;
    padding:10px 14px;
    border-radius:8px;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
}

.hidden-file{
    display:none;
}

.big-photo{
    width:150px;
    height:150px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid rgba(139,92,246,.65);
    margin:0 auto 14px;
    display:block;
    background:rgba(255,255,255,.10);
    padding:4px;
}

.selected-file{
    margin-top:8px;
    font-size:13px;
    color:#cbd5e1;
    word-break:break-word;
    min-height:20px;
    text-align:center;
}

.upload-submit-wrap{
    display:flex;
    justify-content:center;
    margin-top:14px;
}

.upload-submit{
    border:none;
    border-radius:8px;
    background:linear-gradient(135deg,#059669,#047857);
    color:#fff;
    font-weight:900;
    padding:12px 18px;
    cursor:pointer;
    font-size:14px;
}

.info-block{
    margin-top:18px;
    text-align:center;
}

.info-label{
    color:#9ca3af;
    font-size:12px;
    font-weight:900;
    margin-bottom:6px;
    text-transform:uppercase;
}

.info-value{
    font-size:16px;
    font-weight:900;
    color:#fff;
    word-break:break-word;
}

.helper-box{
    margin-top:20px;
    padding:18px;
    border-radius:10px;
    background:linear-gradient(135deg,rgba(7,94,55,.88),rgba(17,140,82,.82),rgba(143,188,103,.72));
    border:1px solid rgba(255,255,255,.25);
    box-shadow:0 18px 40px rgba(0,0,0,.22), 0 0 30px rgba(19,207,116,.20);
}

.helper-box h4{
    color:#fff;
    font-size:14px;
    font-weight:900;
    margin-bottom:13px;
}

.password-side-box ul{
    list-style:none;
    display:grid;
    gap:9px;
}

.password-side-box li{
    color:#fff;
    font-size:13px;
    font-weight:800;
    line-height:1.4;
    transition:.25s ease;
}

.password-side-box li::before{
    content:"○";
    margin-right:9px;
    color:#fff;
    font-weight:900;
}

.password-side-box li.valid{
    color:#d9ff9d;
    font-weight:900;
    text-shadow:0 0 10px rgba(217,255,157,.9);
}

.password-side-box li.valid::before{
    content:"✓";
    color:#d9ff9d;
}

/* CROP MODAL */
.crop-modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:20px;
}

.crop-modal.show{
    display:flex;
}

.crop-modal-box{
    width:100%;
    max-width:820px;
    background:#111827;
    border:1px solid rgba(255,255,255,.12);
    border-radius:18px;
    padding:20px;
    box-shadow:0 20px 60px rgba(0,0,0,.30);
}

.crop-modal-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:14px;
}

.crop-modal-title{
    color:#fff;
    font-size:20px;
    font-weight:900;
}

.crop-close{
    background:transparent;
    border:none;
    color:#fff;
    font-size:32px;
    line-height:1;
    cursor:pointer;
}

.crop-container{
    width:100%;
    max-height:500px;
    overflow:hidden;
    border-radius:12px;
    background:#0f172a;
}

.crop-container img{
    display:block;
    max-width:100%;
}

.crop-modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:16px;
    flex-wrap:wrap;
}

.crop-cancel-btn,
.crop-apply-btn{
    border:none;
    border-radius:8px;
    padding:11px 16px;
    font-size:14px;
    font-weight:900;
    cursor:pointer;
}

.crop-cancel-btn{
    background:rgba(255,255,255,.10);
    color:#fff;
}

.crop-apply-btn{
    background:#8b5cf6;
    color:#fff;
}

@media(max-width:950px){
    .page-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:800px){
    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .main{
        margin-left:0;
    }

    .topbar{
        height:auto;
        padding:15px;
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <img src="<?= htmlspecialchars($top_logo) ?>" alt="Logo">
        <div>
            <h2>SPIST</h2>
            <span>ONLINE CLEARANCE<br>MANAGEMENT SYSTEM</span>
        </div>
    </div>

    <div class="super-card">
        <img src="<?= htmlspecialchars($admin_photo) ?>" alt="Admin" id="sidebarAdminPhoto" onerror="this.src='../assets/logo2.png';">
        <div>
            <h3><?= $is_super_admin ? 'SUPER ADMIN' : 'ADMIN' ?></h3>
            <p><?= htmlspecialchars($admin_name) ?></p>
        </div>
    </div>

    <div class="menu-title">MAIN NAVIGATION</div>

    <nav class="nav">
        <?php if ($is_super_admin): ?>
            <a href="super_admin.php">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <a href="manage_admins.php">
                <i class="fa-solid fa-user-shield"></i> Admin Management
            </a>

            <a href="admin.php?view=students">
                <i class="fa-solid fa-users"></i> User Management
            </a>

            <a href="admin.php?view=teachers">
                <i class="fa-solid fa-chalkboard-user"></i> Teacher Management
            </a>

            <a href="activity_logs.php">
                <i class="fa-solid fa-clipboard-list"></i> Activity Logs
            </a>

            <a href="reports.php">
                <i class="fa-solid fa-chart-column"></i> Reports & Analytics
            </a>

            <a href="backup_restore.php">
                <i class="fa-solid fa-database"></i> Backup & Restore
            </a>

            <a href="system_settings.php">
                <i class="fa-solid fa-gear"></i> System Settings
            </a>
        <?php else: ?>
            <a href="admin.php?view=students">
                <i class="fa-solid fa-users"></i> List of Students
            </a>

            <a href="admin.php?view=teachers">
                <i class="fa-solid fa-chalkboard-user"></i> List of Teachers
            </a>

            <a href="recently_deleted.php">
                <i class="fa-solid fa-trash"></i> Recently Deleted
            </a>

            <a href="admin_teacher_album.php">
                <i class="fa-solid fa-images"></i> Teacher Album
            </a>
        <?php endif; ?>
    </nav>

    <div class="menu-title">OTHER</div>

    <nav class="nav">
        <a href="admin_profile.php">
            <i class="fa-solid fa-user"></i> Profile
        </a>

        <a href="admin_change_password.php" class="active">
            <i class="fa-solid fa-lock"></i> Change Password
        </a>

        <a href="../auth/logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>
</aside>

<main class="main">

    <header class="topbar">
        <div class="top-left">
            <i class="fa-solid fa-bars"></i>
            <h1><span>CHANGE</span> PASSWORD</h1>
        </div>

        <div class="profile-top">
            <div class="profile-icon">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($admin_name) ?></strong><br>
                <small><?= $is_super_admin ? 'Super Admin' : 'Admin' ?></small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="page-head">
            <div class="breadcrumb">Dashboard / Change Password</div>
            <h1 class="page-title">Admin Change Password</h1>
            <p class="page-subtitle">Update your admin password and profile photo securely.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="page-grid">

            <div class="card">
                <h2><i class="fa-solid fa-key"></i> Change Your Password</h2>
                <div class="card-sub">Enter your current password and set a stronger new password for your admin account.</div>

                <form method="POST">
                    <div class="form-group">
                        <label>Current Password <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input type="password" name="current_password" id="current_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">Show</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>New Password <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input type="password" name="new_password" id="new_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">Show</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input type="password" name="confirm_password" id="confirm_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</button>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="save-btn">
                        <i class="fa-solid fa-save"></i> Save New Password
                    </button>
                </form>
            </div>

            <div class="profile-panel-card">
                <form method="POST" enctype="multipart/form-data" id="adminPhotoForm">
                    <div class="upload-row">
                        <label for="fileInput" class="upload-btn">
                            <i class="fa-solid fa-upload"></i> Upload
                        </label>
                    </div>

                    <input type="file" id="fileInput" name="profile_photo" class="hidden-file" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                    <input type="hidden" name="cropped_image" id="croppedImageInput">

                    <img src="<?= htmlspecialchars($admin_photo) ?>" alt="Admin Profile" class="big-photo" id="mainPreviewPhoto" onerror="this.src='../assets/logo2.png';">

                    <div id="selectedFile" class="selected-file">No file selected</div>

                    <div class="upload-submit-wrap">
                        <button type="submit" name="upload_photo" class="upload-submit">
                            <i class="fa-solid fa-image"></i> Save Photo
                        </button>
                    </div>
                </form>

                <div class="info-block">
                    <div class="info-label">Name</div>
                    <div class="info-value"><?= htmlspecialchars($admin_name) ?></div>
                </div>

                <div class="info-block">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($admin_email) ?></div>
                </div>

                <div class="helper-box password-side-box">
                    <h4><i class="fa-solid fa-shield-halved"></i> Password Requirements</h4>
                    <ul>
                        <li id="sideReqLength">At least 12 characters long</li>
                        <li id="sideReqUppercase">At least 1 uppercase letter</li>
                        <li id="sideReqNumber">At least 1 number</li>
                        <li id="sideReqSpecial">At least 1 special character</li>
                        <li id="sideReqMatch">New password and confirm password match</li>
                    </ul>
                </div>
            </div>

        </div>

    </section>

</main>

<div class="crop-modal" id="cropModal">
    <div class="crop-modal-box">
        <div class="crop-modal-header">
            <div class="crop-modal-title">Crop Admin Photo</div>
            <button type="button" class="crop-close" id="closeCropModal">&times;</button>
        </div>

        <div class="crop-container">
            <img id="cropImage" src="" alt="Crop Preview">
        </div>

        <div class="crop-modal-actions">
            <button type="button" class="crop-cancel-btn" id="cancelCropBtn">Cancel</button>
            <button type="button" class="crop-apply-btn" id="applyCropBtn">Crop & Preview</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "Hide";
    } else {
        input.type = "password";
        btn.textContent = "Show";
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const newPasswordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    const sideReqLength = document.getElementById("sideReqLength");
    const sideReqUppercase = document.getElementById("sideReqUppercase");
    const sideReqNumber = document.getElementById("sideReqNumber");
    const sideReqSpecial = document.getElementById("sideReqSpecial");
    const sideReqMatch = document.getElementById("sideReqMatch");

    function setRequirementState(element, isValid) {
        if (element) {
            element.classList.toggle("valid", isValid);
        }
    }

    function checkAdminPasswordRequirements() {
        const value = newPasswordInput.value;
        const confirmValue = confirmPasswordInput.value;

        setRequirementState(sideReqLength, value.length >= 12);
        setRequirementState(sideReqUppercase, /[A-Z]/.test(value));
        setRequirementState(sideReqNumber, /[0-9]/.test(value));
        setRequirementState(sideReqSpecial, /[\W_]/.test(value));
        setRequirementState(
            sideReqMatch,
            value !== "" && confirmValue !== "" && value === confirmValue
        );
    }

    if (newPasswordInput && confirmPasswordInput) {
        newPasswordInput.addEventListener("input", checkAdminPasswordRequirements);
        confirmPasswordInput.addEventListener("input", checkAdminPasswordRequirements);
    }

    const fileInput = document.getElementById("fileInput");
    const selectedFile = document.getElementById("selectedFile");
    const cropModal = document.getElementById("cropModal");
    const cropImage = document.getElementById("cropImage");
    const closeCropModal = document.getElementById("closeCropModal");
    const cancelCropBtn = document.getElementById("cancelCropBtn");
    const applyCropBtn = document.getElementById("applyCropBtn");
    const mainPreviewPhoto = document.getElementById("mainPreviewPhoto");
    const sidebarAdminPhoto = document.getElementById("sidebarAdminPhoto");
    const croppedImageInput = document.getElementById("croppedImageInput");
    const adminPhotoForm = document.getElementById("adminPhotoForm");

    let cropper = null;
    let originalPreview = mainPreviewPhoto.getAttribute("src");

    fileInput.addEventListener("change", function () {
        const file = this.files[0];

        if (!file) {
            selectedFile.textContent = "No file selected";
            return;
        }

        selectedFile.textContent = file.name;

        const allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
            alert("Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.");
            this.value = "";
            croppedImageInput.value = "";
            selectedFile.textContent = "No file selected";
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            cropImage.src = e.target.result;
            cropModal.classList.add("show");

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: "move",
                autoCropArea: 1,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false
            });
        };

        reader.readAsDataURL(file);
    });

    function closeCropModalFunc(resetFile = false) {
        cropModal.classList.remove("show");

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        if (resetFile) {
            fileInput.value = "";
            croppedImageInput.value = "";
            selectedFile.textContent = "No file selected";
            mainPreviewPhoto.src = originalPreview;
            sidebarAdminPhoto.src = originalPreview;
        }
    }

    closeCropModal.addEventListener("click", function () {
        closeCropModalFunc(true);
    });

    cancelCropBtn.addEventListener("click", function () {
        closeCropModalFunc(true);
    });

    cropModal.addEventListener("click", function (e) {
        if (e.target === cropModal) {
            closeCropModalFunc(true);
        }
    });

    applyCropBtn.addEventListener("click", function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 500,
            height: 500,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: "high"
        });

        if (!canvas) {
            alert("Unable to crop image.");
            return;
        }

        const croppedData = canvas.toDataURL("image/png");
        croppedImageInput.value = croppedData;
        mainPreviewPhoto.src = croppedData;
        sidebarAdminPhoto.src = croppedData;

        closeCropModalFunc(false);
    });

    adminPhotoForm.addEventListener("submit", function (e) {
        if (fileInput.files.length > 0 && croppedImageInput.value === "") {
            e.preventDefault();
            alert("Please crop the selected image first before saving.");
        }
    });
});
</script>

</body>
</html>