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

function getCount($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'] ?? 0;
    }
    return 0;
}

/* COUNTS */
$total_students = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='student' AND is_deleted=0");
$total_teachers = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='teacher' AND is_deleted=0");
$total_admins = getCount($conn, "SELECT COUNT(*) AS total FROM admin WHERE role='admin'");
$total_super = getCount($conn, "SELECT COUNT(*) AS total FROM admin WHERE role='super_admin'");
$total_clearance = getCount($conn, "SELECT COUNT(*) AS total FROM class_requests");
$reviewed_clearance = getCount($conn, "SELECT COUNT(*) AS total FROM class_requests WHERE status='Reviewed'");
$archived_students = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='student' AND is_deleted=1");
$archived_teachers = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='teacher' AND is_deleted=1");

/* LOGIN ACTIVITY GRAPH DATA */
$chart_labels = [];
$chart_values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $chart_labels[] = date("M d", strtotime($date));

    $count = getCount($conn, "
        SELECT COUNT(*) AS total 
        FROM activity_logs
        WHERE DATE(created_at) = '$date'
        AND (
            action_type = 'Login'
            OR action_type = 'login'
            OR action_type = 'Logged In'
            OR action_type = 'User Login'
        )
    ");

    $chart_values[] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="../assets/logo2.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

.top-left{
    display:flex;
    align-items:center;
    gap:18px;
}

.top-left i{
    font-size:20px;
    color:#c7d2fe;
}

.top-left h1{
    font-size:18px;
}

.top-left span{
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

.stats{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:16px;
    margin-bottom:16px;
}

.stat-card{
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.stat-body{
    padding:14px;
    display:flex;
    align-items:center;
    gap:15px;
}

.stat-icon{
    width:55px;
    height:55px;
    border-radius:7px;
    display:grid;
    place-items:center;
    font-size:27px;
}

.purple{background:linear-gradient(135deg,#6d28d9,#4f46e5);}
.blue{background:linear-gradient(135deg,#1d4ed8,#2563eb);}
.green{background:linear-gradient(135deg,#059669,#047857);}
.orange{background:linear-gradient(135deg,#d97706,#b45309);}
.pink{background:linear-gradient(135deg,#be185d,#9d174d);}

.stat-card h2{
    font-size:25px;
}

.stat-card p{
    font-size:12px;
    color:#cbd5e1;
}

.view{
    display:flex;
    justify-content:space-between;
    padding:10px 14px;
    border-top:1px solid rgba(255,255,255,.08);
    color:#e9f1ff;
    font-size:12px;
    text-decoration:none;
}

.grid-main{
    display:grid;
    grid-template-columns:1.1fr 1fr;
    gap:16px;
    margin-bottom:16px;
}

.grid-bottom{
    display:grid;
    grid-template-columns:1.2fr .8fr .65fr;
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
    font-size:15px;
}

.panel a{
    color:#8b5cf6;
    text-decoration:none;
    font-size:13px;
}

.chart-wrap{
    height:260px;
}

.logs{
    display:grid;
    gap:15px;
}

.log-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    border-bottom:1px solid rgba(255,255,255,.08);
    padding-bottom:13px;
}

.log-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.log-icon{
    width:30px;
    height:30px;
    border-radius:50%;
    display:grid;
    place-items:center;
    font-size:14px;
}

.log-item span{
    font-size:13px;
}

.log-item small{
    color:#b7c0d4;
    font-size:12px;
    white-space:nowrap;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,.08);
    font-size:13px;
}

th{
    color:#b9c3d9;
    font-size:12px;
}

.user-type{
    display:flex;
    align-items:center;
    gap:9px;
}

.mini-icon{
    width:26px;
    height:26px;
    border-radius:50%;
    display:grid;
    place-items:center;
    font-size:12px;
}

.manage-btn{
    background:#5b21b6;
    padding:7px 13px;
    border-radius:5px;
    color:#fff !important;
    text-decoration:none;
    font-size:12px;
}

.info-table td{
    padding:8px 0;
    display:block;
    border-bottom:0;
}

.info-table tr{
    display:block;
    border-bottom:1px solid rgba(255,255,255,.08);
    padding:6px 0;
}

.info-table td:first-child{
    color:#fff;
    font-size:12px;
    font-weight:700;
}

.info-table td:last-child{
    color:#aeb8ca;
    font-size:12px;
}

.online{
    background:#059669;
    color:white;
    padding:4px 10px;
    border-radius:20px;
    font-size:11px;
}

.actions{
    display:grid;
    gap:10px;
}

.action-btn{
    padding:12px;
    border-radius:5px;
    color:#fff;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:13px;
}

.action-purple{background:#5b21b6;}
.action-blue{background:#1d4ed8;}
.action-green{background:#047857;}
.action-orange{background:#b45309;}
.action-dark{background:#1f2937;}

.footer{
    padding:15px 18px;
    color:#7e899d;
    font-size:12px;
    border-top:1px solid rgba(255,255,255,.08);
}

@media(max-width:1200px){
    .stats{
        grid-template-columns:repeat(2,1fr);
    }

    .grid-main,
    .grid-bottom{
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

    .stats{
        grid-template-columns:1fr;
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
        <img src="<?= htmlspecialchars($top_logo) ?>" alt="SPIST Logo">
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
            <h1><span>SUPER ADMIN</span> DASHBOARD</h1>
        </div>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <strong>Super Admin</strong><br>
                <small>Full System Control</small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="stats">
            <div class="stat-card">
                <div class="stat-body">
                    <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <h2><?= number_format($total_students) ?></h2>
                        <p>Total Students</p>
                    </div>
                </div>
                <a class="view" href="admin.php?view=students">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <div class="stat-card">
                <div class="stat-body">
                    <div class="stat-icon blue"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div>
                        <h2><?= number_format($total_teachers) ?></h2>
                        <p>Total Teachers</p>
                    </div>
                </div>
                <a class="view" href="admin.php?view=teachers">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <div class="stat-card">
                <div class="stat-body">
                    <div class="stat-icon green"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <h2><?= number_format($total_admins) ?></h2>
                        <p>Total Admins</p>
                    </div>
                </div>
                <a class="view" href="manage_admins.php">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <div class="stat-card">
                <div class="stat-body">
                    <div class="stat-icon orange"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div>
                        <h2><?= number_format($reviewed_clearance) ?></h2>
                        <p>Reviewed Clearance</p>
                    </div>
                </div>
                <a class="view" href="reports.php">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </div>

            <div class="stat-card">
                <div class="stat-body">
                    <div class="stat-icon pink"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <h2><?= number_format($total_clearance) ?></h2>
                        <p>Total Clearances</p>
                    </div>
                </div>
                <a class="view" href="reports.php">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="grid-main">
            <div class="panel">
                <div class="panel-head">
                    <h3>SYSTEM LOGIN ACTIVITY</h3>
                    <a href="activity_logs.php">Last 7 Days</a>
                </div>

                <div class="chart-wrap">
                    <canvas id="clearanceChart"></canvas>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h3>RECENT ACTIVITY LOGS</h3>
                    <a href="activity_logs.php">View All</a>
                </div>

                <div class="logs">
                    <div class="log-item">
                        <div class="log-left">
                            <div class="log-icon blue"><i class="fa-solid fa-user"></i></div>
                            <span>Super Admin logged in</span>
                        </div>
                        <small>Today</small>
                    </div>

                    <div class="log-item">
                        <div class="log-left">
                            <div class="log-icon green"><i class="fa-solid fa-plus"></i></div>
                            <span>Admin account management ready</span>
                        </div>
                        <small>System</small>
                    </div>

                    <div class="log-item">
                        <div class="log-left">
                            <div class="log-icon orange"><i class="fa-solid fa-key"></i></div>
                            <span>Password reset module ready</span>
                        </div>
                        <small>System</small>
                    </div>

                    <div class="log-item">
                        <div class="log-left">
                            <div class="log-icon purple"><i class="fa-solid fa-database"></i></div>
                            <span>System backup available</span>
                        </div>
                        <small>Current</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-bottom">
            <div class="panel">
                <div class="panel-head">
                    <h3>USER MANAGEMENT OVERVIEW</h3>
                    <a href="admin.php?view=students">View All</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>USER TYPE</th>
                            <th>TOTAL</th>
                            <th>ACTIVE</th>
                            <th>ARCHIVED</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td><div class="user-type"><span class="mini-icon purple"><i class="fa-solid fa-users"></i></span>Students</div></td>
                            <td><?= $total_students + $archived_students ?></td>
                            <td><?= $total_students ?></td>
                            <td><?= $archived_students ?></td>
                            <td><a class="manage-btn" href="admin.php?view=students">Manage</a></td>
                        </tr>

                        <tr>
                            <td><div class="user-type"><span class="mini-icon blue"><i class="fa-solid fa-graduation-cap"></i></span>Teachers</div></td>
                            <td><?= $total_teachers + $archived_teachers ?></td>
                            <td><?= $total_teachers ?></td>
                            <td><?= $archived_teachers ?></td>
                            <td><a class="manage-btn" href="admin.php?view=teachers">Manage</a></td>
                        </tr>

                        <tr>
                            <td><div class="user-type"><span class="mini-icon green"><i class="fa-solid fa-shield"></i></span>Admins</div></td>
                            <td><?= $total_admins ?></td>
                            <td><?= $total_admins ?></td>
                            <td>0</td>
                            <td><a class="manage-btn" href="manage_admins.php">Manage</a></td>
                        </tr>

                        <tr>
                            <td><div class="user-type"><span class="mini-icon orange"><i class="fa-solid fa-crown"></i></span>Super Admins</div></td>
                            <td><?= $total_super ?></td>
                            <td><?= $total_super ?></td>
                            <td>0</td>
                            <td><a class="manage-btn" href="manage_admins.php">Manage</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h3>SYSTEM INFORMATION</h3>

                <table class="info-table">
                    <tr>
                        <td>System Name</td>
                        <td>Online Clearance Management System</td>
                    </tr>

                    <tr>
                        <td>Version</td>
                        <td>1.0.0</td>
                    </tr>

                    <tr>
                        <td>Server Time</td>
                        <td><?= date("M d, Y h:i A") ?></td>
                    </tr>

                    <tr>
                        <td>PHP Version</td>
                        <td><?= phpversion(); ?></td>
                    </tr>

                    <tr>
                        <td>Database</td>
                        <td>MySQL</td>
                    </tr>

                    <tr>
                        <td>Server Status</td>
                        <td><span class="online">Online</span></td>
                    </tr>
                </table>
            </div>

            <div class="panel">
                <h3>QUICK ACTIONS</h3>

                <br>

                <div class="actions">
                    <a href="manage_admins.php" class="action-btn action-purple">
                        <i class="fa-solid fa-plus"></i> Create Admin Account
                    </a>

                    <a href="reset_user_password.php" class="action-btn action-blue">
                        <i class="fa-solid fa-key"></i> Reset User Password
                    </a>

                    <a href="backup_restore.php" class="action-btn action-green">
                        <i class="fa-solid fa-database"></i> Backup Database
                    </a>

                    <a href="system_settings.php" class="action-btn action-orange">
                        <i class="fa-solid fa-gear"></i> System Settings
                    </a>

                    <a href="activity_logs.php" class="action-btn action-dark">
                        <i class="fa-solid fa-list"></i> View Activity Logs
                    </a>
                </div>
            </div>
        </div>

    </section>

    <div class="footer">
        © <?= date("Y") ?> SPIST Online Clearance Management System. All rights reserved.
    </div>

</main>

<script>
const ctx = document.getElementById('clearanceChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 260);
gradient.addColorStop(0, 'rgba(139, 92, 246, 0.55)');
gradient.addColorStop(1, 'rgba(139, 92, 246, 0.02)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'User Logins',
            data: <?= json_encode($chart_values) ?>,
            borderColor: '#8b5cf6',
            backgroundColor: gradient,
            fill: true,
            tension: 0.45,
            borderWidth: 3,
            pointRadius: 5,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#8b5cf6',
            pointBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#dbeafe'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#cbd5e1'
                },
                grid: {
                    color: 'rgba(255,255,255,.06)'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5e1',
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(255,255,255,.06)'
                }
            }
        }
    }
});
</script>

</body>
</html>