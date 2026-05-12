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

$success = "";
$error = "";
$current_page = basename($_SERVER['PHP_SELF']);

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

/* DATABASE NAME */
$db_result = $conn->query("SELECT DATABASE() AS dbname");
$db_row = $db_result->fetch_assoc();
$db_name = $db_row['dbname'] ?? 'database';

/* BACKUP DATABASE */
if (isset($_GET['download_backup'])) {
    $backup = "-- Online Clearance Management System Database Backup\n";
    $backup .= "-- Database: {$db_name}\n";
    $backup .= "-- Date: " . date("Y-m-d H:i:s") . "\n\n";
    $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $backup .= "START TRANSACTION;\n";
    $backup .= "SET time_zone = \"+00:00\";\n\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $table_result = $conn->query("SHOW TABLES");

    while ($row = $table_result->fetch_array()) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $backup .= "\n-- --------------------------------------------------------\n";
        $backup .= "-- Table structure for table `$table`\n\n";
        $backup .= "DROP TABLE IF EXISTS `$table`;\n";

        $create_result = $conn->query("SHOW CREATE TABLE `$table`");
        $create_row = $create_result->fetch_assoc();
        $backup .= $create_row['Create Table'] . ";\n\n";

        $backup .= "-- Data for table `$table`\n\n";

        $data_result = $conn->query("SELECT * FROM `$table`");

        while ($data = $data_result->fetch_assoc()) {
            $columns = array_map(function ($col) {
                return "`" . $col . "`";
            }, array_keys($data));

            $values = array_map(function ($value) use ($conn) {
                if ($value === null) {
                    return "NULL";
                }
                return "'" . $conn->real_escape_string($value) . "'";
            }, array_values($data));

            $backup .= "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
        }

        $backup .= "\n";
    }

    $backup .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    $backup .= "COMMIT;\n";

    $filename = "ocms_backup_" . date("Y-m-d_H-i-s") . ".sql";

    if (ob_get_length()) {
        ob_end_clean();
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $backup;
    exit;
}

/* RESTORE DATABASE */
if (isset($_POST['restore_database'])) {
    if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid SQL backup file.";
    } else {
        $file_name = $_FILES['sql_file']['name'];
        $file_tmp = $_FILES['sql_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== "sql") {
            $error = "Only .sql files are allowed.";
        } else {
            $sql_content = file_get_contents($file_tmp);

            if (trim($sql_content) === "") {
                $error = "The uploaded SQL file is empty.";
            } else {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

                try {
                    $conn->begin_transaction();

                    if ($conn->multi_query($sql_content)) {
                        do {
                            if ($result = $conn->store_result()) {
                                $result->free();
                            }
                        } while ($conn->more_results() && $conn->next_result());
                    }

                    $conn->commit();
                    $success = "Database restored successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Restore failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Backup & Restore | Super Admin</title>
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

.cards{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:16px;
}

.card{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.card h2{
    color:#fff;
    font-size:18px;
    margin-bottom:12px;
    display:flex;
    align-items:center;
    gap:10px;
}

.card p{
    color:#cbd5e1;
    line-height:1.6;
    font-size:14px;
    margin-bottom:16px;
}

.card-icon-box{
    width:58px;
    height:58px;
    border-radius:10px;
    display:grid;
    place-items:center;
    color:#fff;
    font-size:26px;
    margin-bottom:16px;
}

.backup-icon{
    background:linear-gradient(135deg,#2563eb,#4f46e5);
}

.restore-icon{
    background:linear-gradient(135deg,#dc2626,#991b1b);
}

.btn,
.btn-link{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    border:none;
    padding:13px 18px;
    border-radius:7px;
    cursor:pointer;
    font-weight:800;
    font-size:14px;
    text-decoration:none;
    color:white;
}

.btn-backup{
    background:linear-gradient(135deg,#2563eb,#4f46e5);
}

.btn-restore{
    background:linear-gradient(135deg,#dc2626,#991b1b);
}

.btn:hover,
.btn-link:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 22px rgba(0,0,0,.25);
}

.file-input{
    width:100%;
    padding:13px;
    border-radius:7px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    margin:12px 0 16px;
}

.warning{
    background:rgba(234,179,8,.12);
    border:1px solid rgba(234,179,8,.25);
    color:#fde68a;
    padding:14px;
    border-radius:7px;
    margin-top:16px;
    font-size:13px;
    line-height:1.5;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:16px;
}

.info-card{
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
    padding:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
    display:flex;
    align-items:center;
    gap:15px;
}

.info-icon{
    width:48px;
    height:48px;
    border-radius:8px;
    display:grid;
    place-items:center;
    color:#fff;
    font-size:22px;
}

.purple{background:linear-gradient(135deg,#6d28d9,#4f46e5);}
.green{background:linear-gradient(135deg,#059669,#047857);}
.orange{background:linear-gradient(135deg,#d97706,#b45309);}

.info-card h3{
    font-size:18px;
    color:#fff;
}

.info-card p{
    color:#cbd5e1;
    font-size:13px;
}

@media(max-width:1000px){
    .cards,
    .info-grid{
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
            <p>Database Management</p>
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

        <a href="admin.php?view=teachers">
            <i class="fa-solid fa-chalkboard-user"></i> Teacher Management
        </a>

        <a href="activity_logs.php">
            <i class="fa-solid fa-clipboard-list"></i> Activity Logs
        </a>

        <a href="reports.php">
            <i class="fa-solid fa-chart-column"></i> Reports & Analytics
        </a>

        <a href="backup_restore.php" class="active">
            <i class="fa-solid fa-database"></i> Backup & Restore
        </a>

        <a href="system_settings.php">
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
        <div class="top-left">
            <i class="fa-solid fa-bars"></i>
            <h1><span>BACKUP</span> & RESTORE</h1>
        </div>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-database"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Super Admin') ?></strong><br>
                <small>Super Admin</small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="page-head">
            <div class="breadcrumb">Dashboard / Backup & Restore</div>
            <h1 class="page-title">Backup & Restore</h1>
            <p class="page-subtitle">Manage database backup and restoration for the Online Clearance Management System.</p>
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

        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon purple">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <p>Current Database</p>
                    <h3><?= htmlspecialchars($db_name) ?></h3>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon green">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p>Access Level</p>
                    <h3>Super Admin</h3>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon orange">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <p>Server Time</p>
                    <h3><?= date("M d, Y") ?></h3>
                </div>
            </div>
        </div>

        <div class="cards">

            <div class="card">
                <div class="card-icon-box backup-icon">
                    <i class="fa-solid fa-download"></i>
                </div>

                <h2>Backup Database</h2>

                <p>
                    Download a full SQL backup of the current system database.
                    This includes users, admins, classes, clearance requests, results, and system records.
                </p>

                <a href="backup_restore.php?download_backup=1" class="btn-link btn-backup">
                    <i class="fa-solid fa-download"></i> Download Backup
                </a>

                <div class="warning">
                    <strong>Reminder:</strong> Create a backup before making major changes to the system.
                </div>
            </div>

            <div class="card">
                <div class="card-icon-box restore-icon">
                    <i class="fa-solid fa-upload"></i>
                </div>

                <h2>Restore Database</h2>

                <p>
                    Upload an SQL backup file to restore the database.
                    This action may overwrite existing system data.
                </p>

                <form method="POST" action="backup_restore.php" enctype="multipart/form-data" onsubmit="return confirmRestore();">
                    <input type="file" name="sql_file" class="file-input" accept=".sql" required>

                    <button type="submit" name="restore_database" class="btn btn-restore">
                        <i class="fa-solid fa-upload"></i> Restore Database
                    </button>
                </form>

                <div class="warning">
                    <strong>Warning:</strong> Restore only a trusted SQL backup file. This action can replace current data.
                </div>
            </div>

        </div>

    </section>

</main>

<script>
function confirmRestore() {
    return confirm("Are you sure you want to restore this database? This may overwrite current data.");
}
</script>

</body>
</html>