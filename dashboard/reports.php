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

/* FILTERS */
$course_filter = isset($_GET['course']) ? trim($_GET['course']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where = "WHERE 1";
if (!empty($course_filter)) {
    $course_safe = $conn->real_escape_string($course_filter);
    $where .= " AND u.course = '$course_safe'";
}

if (!empty($date_from) && !empty($date_to)) {
    $from_safe = $conn->real_escape_string($date_from);
    $to_safe = $conn->real_escape_string($date_to);
    $where .= " AND DATE(cr.date_signed) BETWEEN '$from_safe' AND '$to_safe'";
}

/* COUNTS */
$total_students = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='student' AND is_deleted=0");
$total_teachers = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='teacher' AND is_deleted=0");

$total_requests = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where
");

$reviewed_requests = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where AND cr.status = 'Reviewed'
");

$pending_requests = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where AND cr.status != 'Reviewed'
");

$passed_count = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where AND cr.result = 'Passed'
");

$failed_count = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where AND cr.result = 'Failed'
");

$incomplete_count = getCount($conn, "
    SELECT COUNT(*) AS total 
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where AND cr.result = 'Incomplete'
");

/* CHART DATA BY RESULT */
$chart_labels = ['Passed', 'Failed', 'Incomplete', 'Pending'];
$chart_values = [$passed_count, $failed_count, $incomplete_count, $pending_requests];

/* REPORT TABLE */
$report_sql = "
    SELECT 
        cr.id,
        cr.subject,
        cr.status,
        cr.result,
        cr.comment,
        cr.date_signed,
        u.firstname,
        u.lastname,
        u.course
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    $where
    ORDER BY cr.id DESC
    LIMIT 100
";

$report_result = $conn->query($report_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports & Analytics | Super Admin</title>
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
    padding:18px;
}

.filters{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:16px;
    margin-bottom:16px;
}

.filter-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
}

label{
    font-size:12px;
    color:#cbd5e1;
    display:block;
    margin-bottom:6px;
}

input,
select{
    width:100%;
    padding:11px;
    border-radius:7px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:white;
    outline:none;
}

select option{
    color:#111;
}

.btn{
    border:none;
    padding:12px 15px;
    border-radius:7px;
    color:white;
    cursor:pointer;
    font-weight:700;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    height:42px;
}

.btn-filter{
    background:linear-gradient(135deg,#6d28d9,#2563eb);
}

.btn-print{
    background:#047857;
}

.btn-reset{
    background:#374151;
}

.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    margin-bottom:16px;
}

.stat-card{
    background:rgba(255,255,255,.055);
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
    padding:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.stat-card h2{
    font-size:28px;
}

.stat-card p{
    color:#cbd5e1;
    font-size:12px;
}

.stat-icon{
    width:52px;
    height:52px;
    border-radius:8px;
    display:grid;
    place-items:center;
    font-size:25px;
}

.purple{background:linear-gradient(135deg,#6d28d9,#4f46e5);}
.blue{background:linear-gradient(135deg,#1d4ed8,#2563eb);}
.green{background:linear-gradient(135deg,#059669,#047857);}
.orange{background:linear-gradient(135deg,#d97706,#b45309);}
.red{background:linear-gradient(135deg,#dc2626,#991b1b);}
.yellow{background:linear-gradient(135deg,#ca8a04,#a16207);}

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:16px;
}

.panel{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.28);
}

.panel h3{
    font-size:15px;
    margin-bottom:15px;
}

.chart-wrap{
    height:280px;
}

.table-wrap{
    overflow-x:auto;
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

.badge{
    padding:5px 10px;
    border-radius:20px;
    font-size:11px;
    display:inline-block;
}

.badge-pass{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
}

.badge-fail{
    background:rgba(220,38,38,.18);
    color:#ffb4b4;
}

.badge-inc{
    background:rgba(234,179,8,.18);
    color:#fde68a;
}

.badge-pending{
    background:rgba(59,130,246,.18);
    color:#bfdbfe;
}

.footer{
    padding:15px 18px;
    color:#7e899d;
    font-size:12px;
    border-top:1px solid rgba(255,255,255,.08);
}

.print-header{
    display:none;
}

@media(max-width:1100px){
    .stats{
        grid-template-columns:repeat(2,1fr);
    }

    .grid{
        grid-template-columns:1fr;
    }

    .filter-grid{
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

    .stats,
    .filter-grid{
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

@media print{
    body{
        background:white;
        color:black;
    }

    body::before,
    .sidebar,
    .topbar,
    .filters,
    .footer,
    .chart-panel{
        display:none !important;
    }

    .main{
        margin-left:0;
    }

    .content{
        padding:0;
    }

    .print-header{
        display:block;
        text-align:center;
        margin-bottom:20px;
    }

    .print-header img{
        width:75px;
        height:75px;
        object-fit:contain;
    }

    .print-header h2{
        color:#000;
        margin-top:8px;
    }

    .print-header p{
        color:#333;
    }

    .panel,
    .stat-card{
        box-shadow:none;
        border:1px solid #ccc;
        background:white;
        color:black;
    }

    .stats{
        grid-template-columns:repeat(4,1fr);
    }

    th,td{
        color:black;
        border-bottom:1px solid #ddd;
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
            <p>Reports & Analytics</p>
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

        <a href="reports.php" class="active">
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
        <h1><span>REPORTS</span> & ANALYTICS</h1>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-chart-column"></i>
            </div>
            <div>
                <strong>Super Admin</strong><br>
                <small style="color:#aab5ca;">System Reports</small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="print-header">
            <img src="<?= htmlspecialchars($top_logo) ?>" alt="Logo">
            <h2>Southern Philippines Institute of Science and Technology</h2>
            <p>Online Clearance Management System</p>
            <p>Reports & Analytics</p>
        </div>

        <form method="GET" class="filters">
            <div class="filter-grid">
                <div>
                    <label>Course</label>
                    <select name="course">
                        <option value="">All Courses</option>
                        <option value="BSIT 1" <?= ($course_filter == 'BSIT 1') ? 'selected' : '' ?>>BSIT 1</option>
                        <option value="BSIT 2" <?= ($course_filter == 'BSIT 2') ? 'selected' : '' ?>>BSIT 2</option>
                        <option value="BSIT 3" <?= ($course_filter == 'BSIT 3') ? 'selected' : '' ?>>BSIT 3</option>
                        <option value="BSIT 4" <?= ($course_filter == 'BSIT 4') ? 'selected' : '' ?>>BSIT 4</option>
                    </select>
                </div>

                <div>
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div>
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div style="display:flex;gap:8px;align-items:end;">
                    <button type="submit" class="btn btn-filter">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>

                    <a href="reports.php" class="btn btn-reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>

                    <button type="button" onclick="window.print()" class="btn btn-print">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>
        </form>

        <div class="stats">
            <div class="stat-card">
                <div>
                    <h2><?= number_format($total_students) ?></h2>
                    <p>Total Students</p>
                </div>
                <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($total_teachers) ?></h2>
                    <p>Total Teachers</p>
                </div>
                <div class="stat-icon blue"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($total_requests) ?></h2>
                    <p>Total Requests</p>
                </div>
                <div class="stat-icon orange"><i class="fa-solid fa-file-lines"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($reviewed_requests) ?></h2>
                    <p>Reviewed</p>
                </div>
                <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($pending_requests) ?></h2>
                    <p>Pending</p>
                </div>
                <div class="stat-icon blue"><i class="fa-solid fa-clock"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($passed_count) ?></h2>
                    <p>Passed</p>
                </div>
                <div class="stat-icon green"><i class="fa-solid fa-thumbs-up"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($failed_count) ?></h2>
                    <p>Failed</p>
                </div>
                <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
            </div>

            <div class="stat-card">
                <div>
                    <h2><?= number_format($incomplete_count) ?></h2>
                    <p>Incomplete</p>
                </div>
                <div class="stat-icon yellow"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <div class="grid">
            <div class="panel chart-panel">
                <h3>Clearance Result Analytics</h3>
                <div class="chart-wrap">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

            <div class="panel chart-panel">
                <h3>Report Summary</h3>

                <table>
                    <tr>
                        <td>Selected Course</td>
                        <td><?= !empty($course_filter) ? htmlspecialchars($course_filter) : 'All Courses' ?></td>
                    </tr>
                    <tr>
                        <td>Date Range</td>
                        <td>
                            <?= (!empty($date_from) && !empty($date_to)) ? htmlspecialchars($date_from . " to " . $date_to) : 'All Dates' ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Total Requests</td>
                        <td><?= number_format($total_requests) ?></td>
                    </tr>
                    <tr>
                        <td>Reviewed Requests</td>
                        <td><?= number_format($reviewed_requests) ?></td>
                    </tr>
                    <tr>
                        <td>Generated Date</td>
                        <td><?= date("M d, Y h:i A") ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="panel">
            <h3>Clearance Report Records</h3>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Comment</th>
                            <th>Date Signed</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($report_result && $report_result->num_rows > 0): ?>
                            <?php $no = 1; ?>
                            <?php while ($row = $report_result->fetch_assoc()): ?>
                                <?php
                                    $result_text = !empty($row['result']) ? $row['result'] : 'Pending';

                                    if ($result_text == 'Passed') {
                                        $badge_class = 'badge-pass';
                                    } elseif ($result_text == 'Failed') {
                                        $badge_class = 'badge-fail';
                                    } elseif ($result_text == 'Incomplete') {
                                        $badge_class = 'badge-inc';
                                    } else {
                                        $badge_class = 'badge-pending';
                                    }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars(($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($row['course'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['subject'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['status'] ?? 'Pending') ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($result_text) ?></span></td>
                                    <td><?= htmlspecialchars($row['comment'] ?? '') ?></td>
                                    <td>
                                        <?= !empty($row['date_signed']) ? date("M d, Y", strtotime($row['date_signed'])) : 'N/A' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;color:#9ca3af;">No report records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <div class="footer">
        © <?= date("Y") ?> SPIST Online Clearance Management System. All rights reserved.
    </div>

</main>

<script>
const ctx = document.getElementById('reportChart').getContext('2d');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: [
                '#059669',
                '#dc2626',
                '#ca8a04',
                '#2563eb'
            ],
            borderColor: '#07111f',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#dbeafe'
                }
            }
        }
    }
});
</script>

</body>
</html>