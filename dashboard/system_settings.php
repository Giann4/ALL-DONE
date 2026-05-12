<?php
session_start();
include("../config/db.php");

/* SUPER ADMIN ONLY */
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    !isset($_SESSION['admin_role']) ||
    $_SESSION['admin_role'] !== 'super_admin'
) {
    header("Location: admin.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$success = "";
$error = "";

$upload_dir = "../assets/uploads/settings/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* GET SETTINGS */
$settings_result = $conn->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $settings_result ? $settings_result->fetch_assoc() : null;

if (!$settings) {
    $conn->query("
        INSERT INTO system_settings 
        (id, system_name, school_name, school_year, maintenance_mode, theme_mode, theme_color, backup_reminder, auto_logout_time)
        VALUES 
        (1, 'Online Clearance Management System', 'Southern Philippines Institute of Science and Technology', '2025-2026', 'OFF', 'dark', '#6d28d9', 'ON', 30)
    ");
    $settings_result = $conn->query("SELECT * FROM system_settings WHERE id = 1");
    $settings = $settings_result->fetch_assoc();
}

/* SAVE SETTINGS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $system_name = trim($_POST['system_name']);
    $school_name = trim($_POST['school_name']);
    $school_year = trim($_POST['school_year']);
    $maintenance_mode = $_POST['maintenance_mode'];
    $theme_mode = $_POST['theme_mode'];
    $theme_color = $_POST['theme_color'];
    $smtp_email = trim($_POST['smtp_email']);
    $smtp_password = trim($_POST['smtp_password']);
    $backup_reminder = $_POST['backup_reminder'];
    $auto_logout_time = intval($_POST['auto_logout_time']);

    $logo = $settings['logo'];
    $favicon = $settings['favicon'];

    /* LOGO UPLOAD */
    if (!empty($_FILES['logo']['name'])) {
        $logo_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($logo_ext, $allowed)) {
            $logo_name = "system_logo_" . time() . "." . $logo_ext;
            $logo_path = $upload_dir . $logo_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                $logo = $logo_path;
            }
        } else {
            $error = "Invalid logo file type.";
        }
    }

    /* FAVICON UPLOAD */
    if (!empty($_FILES['favicon']['name'])) {
        $favicon_ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        $allowed_icon = ['jpg', 'jpeg', 'png', 'ico', 'webp'];

        if (in_array($favicon_ext, $allowed_icon)) {
            $favicon_name = "favicon_" . time() . "." . $favicon_ext;
            $favicon_path = $upload_dir . $favicon_name;

            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $favicon_path)) {
                $favicon = $favicon_path;
            }
        } else {
            $error = "Invalid favicon file type.";
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("
            UPDATE system_settings 
            SET 
                system_name = ?,
                school_name = ?,
                school_year = ?,
                maintenance_mode = ?,
                theme_mode = ?,
                theme_color = ?,
                smtp_email = ?,
                smtp_password = ?,
                logo = ?,
                favicon = ?,
                backup_reminder = ?,
                auto_logout_time = ?
            WHERE id = 1
        ");

        $stmt->bind_param(
            "sssssssssssi",
            $system_name,
            $school_name,
            $school_year,
            $maintenance_mode,
            $theme_mode,
            $theme_color,
            $smtp_email,
            $smtp_password,
            $logo,
            $favicon,
            $backup_reminder,
            $auto_logout_time
        );

        if ($stmt->execute()) {
            $success = "System settings updated successfully.";
            $settings_result = $conn->query("SELECT * FROM system_settings WHERE id = 1");
            $settings = $settings_result->fetch_assoc();
        } else {
            $error = "Failed to update settings.";
        }
    }
}

$display_logo = !empty($settings['logo']) ? $settings['logo'] : "../assets/logo2.png";
$display_favicon = !empty($settings['favicon']) ? $settings['favicon'] : "../assets/logo2.png";

if (!file_exists($display_logo)) {
    $display_logo = "../assets/southern.png";
}

if (!file_exists($display_favicon)) {
    $display_favicon = "../assets/logo2.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Settings | Super Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="<?= htmlspecialchars($display_favicon) ?>">
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

.super-card i{
    color:#fbbf24;
    font-size:31px;
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

.topbar h1{
    font-size:18px;
}

.topbar h1 span{
    color:#8b5cf6;
}

.profile{
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

.content{
    padding:20px;
}

.alert{
    padding:14px 18px;
    border-radius:8px;
    margin-bottom:18px;
    font-weight:600;
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

.settings-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.panel{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:10px;
    padding:18px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.panel h3{
    font-size:15px;
    margin-bottom:18px;
    color:#fff;
}

.form-group{
    margin-bottom:15px;
}

label{
    display:block;
    margin-bottom:7px;
    font-size:13px;
    color:#cbd5e1;
}

input,
select{
    width:100%;
    padding:12px 13px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    outline:none;
}

select option{
    color:#111;
}

input[type="color"]{
    height:45px;
    padding:4px;
}

input[type="file"]{
    cursor:pointer;
}

.preview-box{
    display:flex;
    align-items:center;
    gap:15px;
    padding:14px;
    border:1px dashed rgba(255,255,255,.18);
    border-radius:10px;
    margin-bottom:14px;
}

.preview-box img{
    width:70px;
    height:70px;
    object-fit:contain;
    background:rgba(255,255,255,.10);
    border-radius:10px;
    padding:6px;
}

.preview-box p{
    font-size:13px;
    color:#b8c3d8;
}

.save-wrap{
    margin-top:18px;
    display:flex;
    justify-content:flex-end;
}

.save-btn{
    border:none;
    padding:14px 24px;
    border-radius:8px;
    background:linear-gradient(135deg,#6d28d9,#2563eb);
    color:white;
    font-weight:700;
    cursor:pointer;
    font-size:14px;
}

.save-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(109,40,217,.35);
}

.note{
    font-size:12px;
    color:#9ca3af;
    margin-top:6px;
    line-height:1.5;
}

.full{
    grid-column:1 / -1;
}

@media(max-width:1000px){
    .settings-grid{
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
        <img src="<?= htmlspecialchars($display_logo) ?>" alt="SPIST Logo">
        <div>
            <h2>SPIST</h2>
            <span>ONLINE CLEARANCE<br>MANAGEMENT SYSTEM</span>
        </div>
    </div>

    <div class="super-card">
        <i class="fa-solid fa-crown"></i>
        <div>
            <h3>SUPER ADMIN</h3>
            <p>System Configuration</p>
        </div>
    </div>

    <div class="menu-title">MAIN NAVIGATION</div>

    <nav class="nav">
        <a href="super_admin.php">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <a href="manage_admins.php">
            <i class="fa-solid fa-user-shield"></i> Admin Management
        </a>

        <a href="admin.php?view=students">
            <i class="fa-solid fa-users"></i> User Management
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

        <a href="system_settings.php" class="active">
            <i class="fa-solid fa-gear"></i> System Settings
        </a>
    </nav>

    <div class="menu-title">OTHER</div>

    <nav class="nav">
        <a href="admin_profile.php">
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
        <h1><span>SYSTEM</span> SETTINGS</h1>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div>
                <strong>Super Admin</strong><br>
                <small style="color:#aab5ca;">Manage System Settings</small>
            </div>
        </div>
    </header>

    <section class="content">

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

        <form method="POST" enctype="multipart/form-data">

            <div class="settings-grid">

                <div class="panel">
                    <h3><i class="fa-solid fa-school"></i> Basic System Information</h3>

                    <div class="form-group">
                        <label>System Name</label>
                        <input type="text" name="system_name" value="<?= htmlspecialchars($settings['system_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>School Name</label>
                        <input type="text" name="school_name" value="<?= htmlspecialchars($settings['school_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>School Year</label>
                        <input type="text" name="school_year" value="<?= htmlspecialchars($settings['school_year']) ?>" required>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="fa-solid fa-palette"></i> Appearance Settings</h3>

                    <div class="form-group">
                        <label>Theme Mode</label>
                        <select name="theme_mode">
                            <option value="dark" <?= ($settings['theme_mode'] == 'dark') ? 'selected' : '' ?>>Dark Mode</option>
                            <option value="light" <?= ($settings['theme_mode'] == 'light') ? 'selected' : '' ?>>Light Mode</option>
                        </select>
                        <div class="note">This will be used as the global theme setting for your system pages.</div>
                    </div>

                    <div class="form-group">
                        <label>Theme Color</label>
                        <input type="color" name="theme_color" value="<?= htmlspecialchars($settings['theme_color']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <select name="maintenance_mode">
                            <option value="OFF" <?= ($settings['maintenance_mode'] == 'OFF') ? 'selected' : '' ?>>OFF</option>
                            <option value="ON" <?= ($settings['maintenance_mode'] == 'ON') ? 'selected' : '' ?>>ON</option>
                        </select>
                        <div class="note">When ON, you can later block normal users from accessing the system.</div>
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="fa-solid fa-image"></i> Logo Upload</h3>

                    <div class="preview-box">
                        <img src="<?= htmlspecialchars($display_logo) ?>" alt="System Logo">
                        <div>
                            <strong>Current Logo</strong>
                            <p>Upload a new logo for the system header/sidebar.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload System Logo</label>
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.gif,.webp">
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="fa-solid fa-star"></i> Favicon Upload</h3>

                    <div class="preview-box">
                        <img src="<?= htmlspecialchars($display_favicon) ?>" alt="Favicon">
                        <div>
                            <strong>Current Favicon</strong>
                            <p>This appears on the browser tab.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload Favicon</label>
                        <input type="file" name="favicon" accept=".jpg,.jpeg,.png,.ico,.webp">
                    </div>
                </div>

                <div class="panel">
                    <h3><i class="fa-solid fa-envelope"></i> SMTP / Email Settings</h3>

                    <div class="form-group">
                        <label>SMTP Email</label>
                        <input type="email" name="smtp_email" value="<?= htmlspecialchars($settings['smtp_email']) ?>" placeholder="example@gmail.com">
                    </div>

                    <div class="form-group">
                        <label>SMTP App Password</label>
                        <input type="password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password']) ?>" placeholder="Enter app password">
                    </div>

                    <div class="note">Use Gmail App Password if you are using Gmail SMTP.</div>
                </div>

                <div class="panel">
                    <h3><i class="fa-solid fa-clock"></i> System Control</h3>

                    <div class="form-group">
                        <label>Backup Reminder</label>
                        <select name="backup_reminder">
                            <option value="ON" <?= ($settings['backup_reminder'] == 'ON') ? 'selected' : '' ?>>ON</option>
                            <option value="OFF" <?= ($settings['backup_reminder'] == 'OFF') ? 'selected' : '' ?>>OFF</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Auto Logout Time</label>
                        <input type="number" name="auto_logout_time" min="5" max="240" value="<?= htmlspecialchars($settings['auto_logout_time']) ?>">
                        <div class="note">Value is in minutes. Example: 30 means auto logout after 30 minutes.</div>
                    </div>
                </div>

                <div class="panel full">
                    <h3><i class="fa-solid fa-floppy-disk"></i> Save Changes</h3>
                    <p class="note">
                        Review all settings before saving. These values will be used as your main system configuration.
                    </p>

                    <div class="save-wrap">
                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>

            </div>

        </form>

    </section>

</main>

</body>
</html>