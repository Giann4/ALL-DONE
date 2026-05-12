<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$success = "";
$error = "";

$is_super_admin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

$admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$upload_dir = "../assets/uploads/admin/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* GET ADMIN INFO */
$stmt = $conn->prepare("SELECT id, name, email, role, profile_photo FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    die("Admin account not found.");
}

/* UPDATE PROFILE */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $profile_photo = $admin['profile_photo'];

    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        if (!empty($_FILES['profile_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.";
            } else {
                $new_file = "admin_" . $admin_id . "_" . time() . "." . $ext;
                $target_path = $upload_dir . $new_file;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                    if (!empty($profile_photo) && file_exists($upload_dir . $profile_photo)) {
                        unlink($upload_dir . $profile_photo);
                    }

                    $profile_photo = $new_file;
                } else {
                    $error = "Failed to upload profile photo.";
                }
            }
        }

        if (empty($error)) {
            $update = $conn->prepare("UPDATE admin SET name = ?, email = ?, profile_photo = ? WHERE id = ?");
            $update->bind_param("sssi", $name, $email, $profile_photo, $admin_id);

            if ($update->execute()) {
                $_SESSION['name'] = $name;
                $success = "Profile updated successfully.";

                $stmt = $conn->prepare("SELECT id, name, email, role, profile_photo FROM admin WHERE id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}

$admin_photo = "../assets/logo2.png";
if (!empty($admin['profile_photo']) && file_exists($upload_dir . $admin['profile_photo'])) {
    $admin_photo = $upload_dir . $admin['profile_photo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="../assets/logo2.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

.alert{
    padding:14px 16px;
    border-radius:8px;
    margin-bottom:18px;
    font-weight:700;
}

.success{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
    border:1px solid rgba(5,150,105,.35);
}

.error{
    background:rgba(220,38,38,.18);
    color:#ffb4b4;
    border:1px solid rgba(220,38,38,.35);
}

.profile-grid{
    display:grid;
    grid-template-columns:.8fr 1.2fr;
    gap:18px;
}

.panel{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.panel h3{
    color:#fff;
    font-size:17px;
    margin-bottom:18px;
}

.avatar-box{
    text-align:center;
}

.avatar-preview{
    width:160px;
    height:160px;
    border-radius:50%;
    object-fit:cover;
    background:rgba(255,255,255,.10);
    border:4px solid rgba(139,92,246,.65);
    padding:4px;
    margin-bottom:16px;
}

.role-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border-radius:20px;
    background:rgba(139,92,246,.18);
    color:#c4b5fd;
    font-size:12px;
    font-weight:800;
}

.info-list{
    margin-top:18px;
    display:grid;
    gap:12px;
}

.info-item{
    padding:13px;
    border-radius:8px;
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.08);
}

.info-item small{
    color:#9ca3af;
    display:block;
    margin-bottom:4px;
}

.info-item strong{
    color:#fff;
}

.form-group{
    margin-bottom:15px;
}

label{
    display:block;
    margin-bottom:7px;
    color:#dbeafe;
    font-size:13px;
    font-weight:700;
}

input{
    width:100%;
    padding:13px 14px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    outline:none;
    font-size:14px;
}

input::placeholder{
    color:#9ca3af;
}

input:focus{
    border-color:#8b5cf6;
    box-shadow:0 0 0 4px rgba(139,92,246,.15);
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

.note{
    color:#9ca3af;
    font-size:12px;
    line-height:1.5;
    margin-top:8px;
}

@media(max-width:950px){
    .profile-grid{
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
        <img src="<?= htmlspecialchars($admin_photo) ?>" alt="Admin" onerror="this.src='../assets/logo2.png';">
        <div>
            <h3><?= $is_super_admin ? 'SUPER ADMIN' : 'ADMIN' ?></h3>
            <p><?= htmlspecialchars($admin['name']) ?></p>
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
        <a href="admin_profile.php" class="active">
            <i class="fa-solid fa-user"></i> Profile
        </a>

        <a href="admin_change_password.php">
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
            <h1><span>ADMIN</span> PROFILE</h1>
        </div>

        <div class="profile-top">
            <div class="profile-icon">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($admin['name']) ?></strong><br>
                <small><?= htmlspecialchars($admin['role']) ?></small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="page-head">
            <div class="breadcrumb">Dashboard / Profile</div>
            <h1 class="page-title">Admin Profile</h1>
            <p class="page-subtitle">View and update your admin account information.</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert success">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">

            <div class="panel avatar-box">
                <h3><i class="fa-solid fa-id-card"></i> Profile Overview</h3>

                <img src="<?= htmlspecialchars($admin_photo) ?>" class="avatar-preview" alt="Admin Photo" onerror="this.src='../assets/logo2.png';">

                <h2><?= htmlspecialchars($admin['name']) ?></h2>
                <br>

                <span class="role-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <?= strtoupper(htmlspecialchars($admin['role'])) ?>
                </span>

                <div class="info-list">
                    <div class="info-item">
                        <small>Email Address</small>
                        <strong><?= htmlspecialchars($admin['email']) ?></strong>
                    </div>

                    <div class="info-item">
                        <small>Admin ID</small>
                        <strong><?= htmlspecialchars($admin['id']) ?></strong>
                    </div>

                    <div class="info-item">
                        <small>Access Level</small>
                        <strong><?= $is_super_admin ? 'Full System Access' : 'Admin Access' ?></strong>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Profile</h3>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <div class="note">Allowed image types: JPG, PNG, GIF, WEBP.</div>
                    </div>

                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-save"></i> Save Profile
                    </button>
                </form>
            </div>

        </div>

    </section>

</main>

</body>
</html>