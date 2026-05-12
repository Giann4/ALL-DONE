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
$message = "";
$message_type = "";

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

/* CREATE ADMIN */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_admin'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Please fill out all fields.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Password and confirm password do not match.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM admin WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Email already exists.";
            $message_type = "error";
        } else {
            $hashed_password = md5($password);
            $role = "admin";
            $profile_photo = "";

            $stmt = $conn->prepare("INSERT INTO admin (name, email, password, role, profile_photo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $profile_photo);

            if ($stmt->execute()) {
                $message = "Admin account created successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to create admin account.";
                $message_type = "error";
            }
        }
    }
}

/* DELETE ADMIN */
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    if ($delete_id == ($_SESSION['user_id'] ?? 0)) {
        $message = "You cannot delete your own Super Admin account.";
        $message_type = "error";
    } else {
        $check_role = $conn->prepare("SELECT role FROM admin WHERE id = ?");
        $check_role->bind_param("i", $delete_id);
        $check_role->execute();
        $role_result = $check_role->get_result()->fetch_assoc();

        if ($role_result && $role_result['role'] === 'super_admin') {
            $message = "Super Admin account cannot be deleted.";
            $message_type = "error";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
            $delete_stmt->bind_param("i", $delete_id);

            if ($delete_stmt->execute()) {
                $message = "Admin account deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to delete admin account.";
                $message_type = "error";
            }
        }
    }
}

/* GET COUNTS */
$total_admins = $conn->query("SELECT COUNT(*) AS total FROM admin")->fetch_assoc()['total'] ?? 0;
$normal_admins = $conn->query("SELECT COUNT(*) AS total FROM admin WHERE role='admin'")->fetch_assoc()['total'] ?? 0;
$super_admins = $conn->query("SELECT COUNT(*) AS total FROM admin WHERE role='super_admin'")->fetch_assoc()['total'] ?? 0;

/* GET ADMIN LIST */
$admins = $conn->query("SELECT id, name, email, role, profile_photo FROM admin ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Admins | Super Admin</title>
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

.profile small{
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

/* ALERT */
.alert{
    padding:14px 16px;
    border-radius:8px;
    margin-bottom:18px;
    font-weight:700;
}

.alert.success{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
    border:1px solid rgba(5,150,105,.35);
}

.alert.error{
    background:rgba(220,38,38,.18);
    color:#ffb4b4;
    border:1px solid rgba(220,38,38,.35);
}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:18px;
}

.card{
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
    padding:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
    display:flex;
    align-items:center;
    gap:15px;
}

.card-icon{
    width:55px;
    height:55px;
    border-radius:8px;
    display:grid;
    place-items:center;
    font-size:25px;
    color:#fff;
}

.purple{background:linear-gradient(135deg,#6d28d9,#4f46e5);}
.blue{background:linear-gradient(135deg,#1d4ed8,#2563eb);}
.green{background:linear-gradient(135deg,#059669,#047857);}
.orange{background:linear-gradient(135deg,#d97706,#b45309);}

.card h2{
    font-size:28px;
    color:#fff;
}

.card p{
    color:#cbd5e1;
    font-size:13px;
}

/* GRID */
.manage-grid{
    display:grid;
    grid-template-columns:2fr .95fr;
    gap:16px;
}

.panel{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.panel h3{
    color:#ffffff;
    font-size:16px;
}

.table-wrap{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#111827;
    color:#dbeafe;
    padding:13px;
    font-size:12px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,.10);
}

td{
    padding:14px 13px;
    border-bottom:1px solid rgba(255,255,255,.08);
    color:#e5edff;
    font-size:13px;
}

.admin-profile{
    display:flex;
    align-items:center;
    gap:10px;
}

.admin-photo{
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
    background:rgba(255,255,255,.10);
    padding:2px;
}

.role-badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    display:inline-block;
}

.role-badge.super{
    background:rgba(139,92,246,.18);
    color:#c4b5fd;
}

.role-badge.admin{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
}

.action{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    border-radius:8px;
    text-decoration:none;
    margin-right:5px;
}

.delete{
    background:rgba(220,38,38,.18);
    color:#ff9c9c;
}

.delete:hover{
    background:rgba(220,38,38,.35);
}

.protected{
    background:rgba(255,255,255,.10);
    color:#9ca3af;
    cursor:not-allowed;
}

/* FORM */
.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;
    margin-bottom:7px;
    font-weight:700;
    color:#dbeafe;
    font-size:13px;
}

.form-group input{
    width:100%;
    padding:13px 14px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    border-radius:8px;
    outline:none;
    font-size:14px;
}

.form-group input::placeholder{
    color:#9ca3af;
}

.form-group input:focus{
    border-color:#8b5cf6;
    box-shadow:0 0 0 4px rgba(139,92,246,.15);
}

.submit-btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:8px;
    background:linear-gradient(135deg,#6d28d9,#2563eb);
    color:#fff;
    font-weight:900;
    font-size:14px;
    cursor:pointer;
}

.submit-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(109,40,217,.35);
}

.note{
    margin-top:12px;
    font-size:12px;
    color:#9ca3af;
    line-height:1.5;
}

/* RESPONSIVE */
@media(max-width:1100px){
    .cards{
        grid-template-columns:1fr;
    }

    .manage-grid{
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
        <i class="fa-solid fa-crown"></i>
        <div>
            <h3>SUPER ADMIN</h3>
            <p>Full System Access</p>
        </div>
    </div>

    <div class="menu-title">MAIN NAVIGATION</div>

    <nav class="nav">
        <a href="super_admin.php" class="<?= ($current_page == 'super_admin.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <a href="manage_admins.php" class="<?= ($current_page == 'manage_admins.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-shield"></i> Admin Management
        </a>

        <a href="admin.php?view=students">
            <i class="fa-solid fa-users"></i> User Management
        </a>

        <a href="activity_logs.php" class="<?= ($current_page == 'activity_logs.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-list"></i> Activity Logs
        </a>

        <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-column"></i> Reports & Analytics
        </a>

        <a href="backup_restore.php" class="<?= ($current_page == 'backup_restore.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-database"></i> Backup & Restore
        </a>

        <a href="system_settings.php" class="<?= ($current_page == 'system_settings.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i> System Settings
        </a>
    </nav>

    <div class="menu-title">OTHER</div>

    <nav class="nav">
        <a href="admin_profile.php" class="<?= ($current_page == 'admin_profile.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> Profile
        </a>

        <a href="admin_change_password.php" class="<?= ($current_page == 'admin_change_password.php') ? 'active' : '' ?>">
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
            <h1><span>ADMIN</span> MANAGEMENT</h1>
        </div>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Super Admin') ?></strong><br>
                <small>Super Admin</small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="page-head">
            <div class="breadcrumb">Dashboard / Admin Management</div>
            <h2 class="page-title">Manage Admins</h2>
            <p class="page-subtitle">Create, view, and remove admin accounts. Only Super Admin can access this page.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="cards">
            <div class="card">
                <div class="card-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h2><?= number_format($total_admins) ?></h2>
                    <p>Total Admin Accounts</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon green">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h2><?= number_format($normal_admins) ?></h2>
                    <p>Normal Admins</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon orange">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div>
                    <h2><?= number_format($super_admins) ?></h2>
                    <p>Super Admins</p>
                </div>
            </div>
        </div>

        <div class="manage-grid">

            <div class="panel">
                <div class="panel-head">
                    <h3><i class="fa-solid fa-list"></i> Admin List</h3>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>PROFILE</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($admins && $admins->num_rows > 0): ?>
                                <?php while ($row = $admins->fetch_assoc()): ?>
                                    <?php
                                        $photo = "../assets/logo2.png";
                                        if (!empty($row['profile_photo']) && file_exists("../assets/uploads/admin/" . $row['profile_photo'])) {
                                            $photo = "../assets/uploads/admin/" . $row['profile_photo'];
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>

                                        <td>
                                            <div class="admin-profile">
                                                <img src="<?= htmlspecialchars($photo) ?>" class="admin-photo" alt="Admin">
                                                <span><?= htmlspecialchars($row['name']) ?></span>
                                            </div>
                                        </td>

                                        <td><?= htmlspecialchars($row['email']) ?></td>

                                        <td>
                                            <?php if ($row['role'] === 'super_admin'): ?>
                                                <span class="role-badge super">SUPER ADMIN</span>
                                            <?php else: ?>
                                                <span class="role-badge admin">ADMIN</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($row['role'] === 'super_admin'): ?>
                                                <span class="action protected" title="Protected account">
                                                    <i class="fa-solid fa-lock"></i>
                                                </span>
                                            <?php else: ?>
                                                <a 
                                                    href="manage_admins.php?delete=<?= $row['id'] ?>" 
                                                    class="action delete"
                                                    onclick="return confirm('Are you sure you want to delete this admin account?');"
                                                    title="Delete Admin"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#9ca3af;">No admin accounts found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h3><i class="fa-solid fa-user-plus"></i> Add New Admin</h3>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="Enter full name" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Enter email address" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    </div>

                    <button type="submit" name="create_admin" class="submit-btn">
                        <i class="fa-solid fa-user-plus"></i> Create Admin
                    </button>

                    <div class="note">
                        New accounts created here will be normal Admin accounts only. Super Admin accounts are protected.
                    </div>
                </form>
            </div>

        </div>

    </section>

</main>

</body>
</html>