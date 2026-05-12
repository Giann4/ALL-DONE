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

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

/* FILTERS */
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_filter = isset($_GET['user_name']) ? trim($_GET['user_name']) : '';
$action_filter = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';

$where = "WHERE 1";
$params = [];
$types = "";

if (!empty($date_from) && !empty($date_to)) {
    $where .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

if (!empty($user_filter)) {
    $where .= " AND user_name = ?";
    $params[] = $user_filter;
    $types .= "s";
}

if (!empty($action_filter)) {
    $where .= " AND UPPER(action_type) = ?";
    $params[] = strtoupper($action_filter);
    $types .= "s";
}

/* SAFE COUNT */
function getCount($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'] ?? 0;
    }
    return 0;
}

/* COUNTS */
$total_logs = getCount($conn, "SELECT COUNT(*) AS total FROM activity_logs");

$login_logs = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM activity_logs 
    WHERE UPPER(action_type) IN ('LOGIN', 'LOGGED IN', 'USER LOGIN')
");

$data_changes = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM activity_logs 
    WHERE UPPER(action_type) IN ('CREATE','UPDATE','DELETE','RESET','BACKUP')
");

$admin_logs = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM activity_logs 
    WHERE user_role IN ('admin','super_admin')
");

/* USERS */
$users_result = $conn->query("
    SELECT DISTINCT user_name 
    FROM activity_logs 
    WHERE user_name IS NOT NULL AND user_name != ''
    ORDER BY user_name ASC
");

/* LOGS */
$sql = "SELECT * FROM activity_logs $where ORDER BY id DESC LIMIT 100";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$logs = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Activity Logs | Super Admin</title>
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

/* CARDS */
.cards{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    margin-bottom:16px;
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
    color:white;
}

.blue{background:linear-gradient(135deg,#1d4ed8,#2563eb);}
.green{background:linear-gradient(135deg,#059669,#047857);}
.orange{background:linear-gradient(135deg,#d97706,#b45309);}
.red{background:linear-gradient(135deg,#dc2626,#991b1b);}
.purple{background:linear-gradient(135deg,#6d28d9,#4f46e5);}

.card h2{
    font-size:28px;
    color:#fff;
}

.card p{
    color:#cbd5e1;
    font-size:13px;
}

/* FILTER */
.filter-box{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:16px;
    display:grid;
    grid-template-columns:1.3fr 1fr 1fr 150px 150px;
    gap:12px;
    align-items:end;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
    margin-bottom:16px;
}

.form-group label{
    display:block;
    font-size:12px;
    color:#cbd5e1;
    font-weight:700;
    margin-bottom:7px;
}

.form-group input,
.form-group select{
    width:100%;
    height:44px;
    border-radius:7px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    padding:0 12px;
    outline:none;
}

.form-group select option{
    color:#111;
}

.filter-btn,
.reset-btn{
    height:44px;
    border-radius:7px;
    border:none;
    font-weight:800;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    color:white;
}

.filter-btn{
    background:linear-gradient(135deg,#6d28d9,#2563eb);
}

.reset-btn{
    background:#374151;
}

/* TABLE */
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
    min-width:1050px;
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
    vertical-align:middle;
}

tbody tr:hover td{
    background:rgba(255,255,255,.035);
}

.user-cell{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:700;
}

.user-avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#6d28d9,#2563eb);
    color:#fff;
    font-weight:900;
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    display:inline-block;
}

.role-super{background:rgba(139,92,246,.18);color:#c4b5fd;}
.role-admin{background:rgba(5,150,105,.18);color:#8ff0c4;}
.role-teacher{background:rgba(37,99,235,.18);color:#bfdbfe;}
.role-student{background:rgba(234,179,8,.18);color:#fde68a;}

.login{background:rgba(5,150,105,.18);color:#8ff0c4;}
.create{background:rgba(37,99,235,.18);color:#bfdbfe;}
.update{background:rgba(234,179,8,.18);color:#fde68a;}
.delete{background:rgba(220,38,38,.18);color:#ffb4b4;}
.reset{background:rgba(234,179,8,.18);color:#fde68a;}
.backup{background:rgba(139,92,246,.18);color:#c4b5fd;}
.system{background:rgba(156,163,175,.18);color:#d1d5db;}

.table-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:15px;
    color:#9ca3af;
    font-size:13px;
}

.empty-row{
    text-align:center;
    color:#9ca3af;
    padding:25px;
}

/* RESPONSIVE */
@media(max-width:1200px){
    .cards{
        grid-template-columns:repeat(2,1fr);
    }

    .filter-box{
        grid-template-columns:1fr 1fr;
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

    .cards,
    .filter-box{
        grid-template-columns:1fr;
    }

    .topbar{
        height:auto;
        padding:15px;
        flex-direction:column;
        align-items:flex-start;
        gap:12px;
    }

    .table-footer{
        flex-direction:column;
        align-items:flex-start;
        gap:8px;
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
            <p>Activity Monitoring</p>
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

        <a href="admin.php?view=teachers">
            <i class="fa-solid fa-chalkboard-user"></i> Teacher Management
        </a>

        <a href="activity_logs.php" class="active">
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
            <h1><span>ACTIVITY</span> LOGS</h1>
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
            <div class="breadcrumb">Dashboard / Activity Logs</div>
            <h1 class="page-title">Activity Logs</h1>
            <p class="page-subtitle">View all system activities and actions performed by users in the system.</p>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-icon blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div>
                    <p>Total Logs</p>
                    <h2><?= number_format($total_logs) ?></h2>
                    <p>All System Activities</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon green">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <div>
                    <p>User Logins</p>
                    <h2><?= number_format($login_logs) ?></h2>
                    <p>Login Activities</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon orange">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div>
                    <p>Data Changes</p>
                    <h2><?= number_format($data_changes) ?></h2>
                    <p>Create / Update / Delete</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon red">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p>Admin Actions</p>
                    <h2><?= number_format($admin_logs) ?></h2>
                    <p>Admin Related Actions</p>
                </div>
            </div>
        </div>

        <form method="GET" class="filter-box">
            <div class="form-group">
                <label>Date Range</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>User</label>
                <select name="user_name">
                    <option value="">All Users</option>

                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php while ($u = $users_result->fetch_assoc()): ?>
                            <option 
                                value="<?= htmlspecialchars($u['user_name']) ?>"
                                <?= $user_filter === $u['user_name'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($u['user_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Action Type</label>
                <select name="action_type">
                    <option value="">All Actions</option>
                    <option value="LOGIN" <?= strtoupper($action_filter) === 'LOGIN' ? 'selected' : '' ?>>LOGIN</option>
                    <option value="CREATE" <?= strtoupper($action_filter) === 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                    <option value="UPDATE" <?= strtoupper($action_filter) === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= strtoupper($action_filter) === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    <option value="RESET" <?= strtoupper($action_filter) === 'RESET' ? 'selected' : '' ?>>RESET</option>
                    <option value="BACKUP" <?= strtoupper($action_filter) === 'BACKUP' ? 'selected' : '' ?>>BACKUP</option>
                    <option value="SYSTEM" <?= strtoupper($action_filter) === 'SYSTEM' ? 'selected' : '' ?>>SYSTEM</option>
                </select>
            </div>

            <button type="submit" class="filter-btn">
                <i class="fa-solid fa-filter"></i> Filter
            </button>

            <a href="activity_logs.php" class="reset-btn">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        </form>

        <div class="panel">
            <div class="panel-head">
                <h3><i class="fa-solid fa-table"></i> Activity Log List</h3>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USER</th>
                            <th>ROLE</th>
                            <th>ACTION</th>
                            <th>DESCRIPTION</th>
                            <th>IP ADDRESS</th>
                            <th>DATE & TIME</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <?php while ($row = $logs->fetch_assoc()): ?>
                                <?php
                                    $actionClass = strtolower($row['action_type']);

                                    if (!in_array($actionClass, ['login','create','update','delete','reset','backup','system'])) {
                                        $actionClass = 'system';
                                    }

                                    $roleClass = 'role-student';

                                    if ($row['user_role'] === 'super_admin') {
                                        $roleClass = 'role-super';
                                    } elseif ($row['user_role'] === 'admin') {
                                        $roleClass = 'role-admin';
                                    } elseif ($row['user_role'] === 'teacher') {
                                        $roleClass = 'role-teacher';
                                    }

                                    $initial = !empty($row['user_name']) ? strtoupper(substr($row['user_name'], 0, 1)) : "?";
                                ?>

                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>

                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?= htmlspecialchars($initial) ?>
                                            </div>
                                            <?= htmlspecialchars($row['user_name']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge <?= $roleClass ?>">
                                            <?= htmlspecialchars($row['user_role']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge <?= $actionClass ?>">
                                            <?= htmlspecialchars($row['action_type']) ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($row['action_description']) ?></td>

                                    <td><?= htmlspecialchars($row['ip_address']) ?></td>

                                    <td>
                                        <?= !empty($row['created_at']) ? date("M d, Y h:i:s A", strtotime($row['created_at'])) : 'N/A' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-row">
                                    No activity logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>Showing latest 100 activity log entries</span>
                <span>Filtered results are based on selected date, user, and action type.</span>
            </div>
        </div>

    </section>

</main>

</body>
</html>