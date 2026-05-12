<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$is_super_admin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';

$courseFilter = isset($_GET['course']) ? trim($_GET['course']) : '';
$viewRole = isset($_GET['view']) ? trim($_GET['view']) : 'students';

if ($viewRole !== 'students' && $viewRole !== 'teachers') {
    $viewRole = 'students';
}

$current_page = basename($_SERVER['PHP_SELF']);

$returnUrl = "admin.php?view=" . urlencode($viewRole);
if ($viewRole === 'students' && !empty($courseFilter)) {
    $returnUrl .= "&course=" . urlencode($courseFilter);
}

$top_logo = "../assets/logo2.png";
if (!file_exists($top_logo)) {
    $top_logo = "../assets/southern.png";
}

/* GET USERS */
$sql = "SELECT * FROM users WHERE is_deleted = 0";
$params = [];
$types = "";

if ($viewRole === 'students') {
    $sql .= " AND role = 'student'";
} elseif ($viewRole === 'teachers') {
    $sql .= " AND role = 'teacher'";
}

if (!empty($courseFilter) && $viewRole === 'students') {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* COUNTS */
$totalStudentsQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND is_deleted = 0");
$totalStudents = $totalStudentsQuery->fetch_assoc()['total'] ?? 0;

$totalTeachersQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='teacher' AND is_deleted = 0");
$totalTeachers = $totalTeachersQuery->fetch_assoc()['total'] ?? 0;

$totalCurrent = ($viewRole === 'students') ? $totalStudents : $totalTeachers;

/* ADMIN INFO */
$adminName = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';

$default_admin_photo = "../assets/logo2.png";
$admin_photo = $default_admin_photo;
$admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT profile_photo FROM admin WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_data = $admin_stmt->get_result()->fetch_assoc();

    if ($admin_data && !empty($admin_data['profile_photo']) && file_exists("../assets/uploads/admin/" . $admin_data['profile_photo'])) {
        $admin_photo = "../assets/uploads/admin/" . $admin_data['profile_photo'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= ($viewRole === 'students') ? 'Student Management' : 'Teacher Management' ?></title>
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

/* CONTROLS */
.controls{
    display:grid;
    grid-template-columns:1fr 220px 180px 180px;
    gap:12px;
    margin-bottom:16px;
}

.control-box{
    background:rgba(255,255,255,.045);
    border:1px solid rgba(255,255,255,.11);
    border-radius:8px;
    padding:14px;
    box-shadow:0 12px 30px rgba(0,0,0,.22);
}

.search-input,
select{
    width:100%;
    height:44px;
    border-radius:7px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.08);
    color:#fff;
    padding:0 13px;
    outline:none;
}

.search-input::placeholder{
    color:#9ca3af;
}

select option{
    color:#111;
}

.filter-btn,
.reset-btn{
    width:100%;
    height:44px;
    border:none;
    border-radius:7px;
    color:white;
    cursor:pointer;
    font-weight:800;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}

.filter-btn{
    background:linear-gradient(135deg,#6d28d9,#2563eb);
}

.reset-btn{
    background:#374151;
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
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
    gap:15px;
}

.stat-icon{
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

.stat-card h2{
    font-size:28px;
    color:#fff;
}

.stat-card p{
    color:#cbd5e1;
    font-size:13px;
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
    align-items:center;
    justify-content:space-between;
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
    min-width:1000px;
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

tbody tr:hover td{
    background:rgba(255,255,255,.035);
}

.user-name{
    font-weight:800;
    color:#fff;
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    display:inline-block;
}

.role-student{
    background:rgba(139,92,246,.18);
    color:#c4b5fd;
}

.role-teacher{
    background:rgba(37,99,235,.18);
    color:#bfdbfe;
}

.course-badge{
    background:rgba(5,150,105,.18);
    color:#8ff0c4;
}

.password-mask{
    letter-spacing:2px;
    color:#aab5ca;
    font-weight:900;
}

.action-group{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

.action-btn{
    width:34px;
    height:34px;
    border-radius:8px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
}

.view-btn{
    background:rgba(37,99,235,.18);
    color:#93c5fd;
}

.edit-btn{
    background:rgba(234,179,8,.18);
    color:#fde68a;
}

.archive-btn{
    background:rgba(220,38,38,.18);
    color:#ff9c9c;
}

.empty-row{
    text-align:center;
    color:#9ca3af;
    padding:30px;
}

/* RESPONSIVE */
@media(max-width:1100px){
    .controls{
        grid-template-columns:1fr;
    }

    .stats{
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
            <p><?= htmlspecialchars($adminName) ?></p>
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

            <a href="admin.php?view=students" class="<?= ($viewRole === 'students') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> User Management
            </a>

            <a href="admin.php?view=teachers" class="<?= ($viewRole === 'teachers') ? 'active' : '' ?>">
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
            <a href="admin.php?view=students" class="<?= ($viewRole === 'students') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> List of Students
            </a>

            <a href="admin.php?view=teachers" class="<?= ($viewRole === 'teachers') ? 'active' : '' ?>">
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
            <h1>
                <span><?= ($viewRole === 'students') ? 'STUDENT' : 'TEACHER' ?></span>
                MANAGEMENT
            </h1>
        </div>

        <div class="profile">
            <div class="profile-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($adminName) ?></strong><br>
                <small><?= $is_super_admin ? 'Super Admin' : 'Admin' ?></small>
            </div>
        </div>
    </header>

    <section class="content">

        <div class="page-head">
            <div class="breadcrumb">
                Dashboard / <?= ($viewRole === 'students') ? 'Student Management' : 'Teacher Management' ?>
            </div>

            <h2 class="page-title">
                <?= ($viewRole === 'students') ? 'Student Management' : 'Teacher Management' ?>
            </h2>

            <p class="page-subtitle">
                View, search, edit, and archive <?= ($viewRole === 'students') ? 'student' : 'teacher' ?> accounts.
            </p>
        </div>

        <form method="GET" class="controls">
            <div class="control-box">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewRole) ?>">
                <input type="text" id="liveSearch" class="search-input" placeholder="Search users..." autocomplete="off">
            </div>

            <?php if ($viewRole === 'students'): ?>
                <div class="control-box">
                    <select name="course">
                        <option value="">All Courses</option>
                        <option value="BSIT 1" <?= ($courseFilter === 'BSIT 1') ? 'selected' : '' ?>>BSIT 1</option>
                        <option value="BSIT 2" <?= ($courseFilter === 'BSIT 2') ? 'selected' : '' ?>>BSIT 2</option>
                        <option value="BSIT 3" <?= ($courseFilter === 'BSIT 3') ? 'selected' : '' ?>>BSIT 3</option>
                        <option value="BSIT 4" <?= ($courseFilter === 'BSIT 4') ? 'selected' : '' ?>>BSIT 4</option>
                    </select>
                </div>

                <div class="control-box">
                    <button type="submit" class="filter-btn">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>

                <div class="control-box">
                    <a href="admin.php?view=students" class="reset-btn">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            <?php else: ?>
                <div class="control-box">
                    <a href="admin.php?view=students" class="filter-btn">
                        <i class="fa-solid fa-users"></i> Students
                    </a>
                </div>

                <div class="control-box">
                    <a href="admin.php?view=teachers" class="reset-btn">
                        <i class="fa-solid fa-chalkboard-user"></i> Teachers
                    </a>
                </div>

                <div class="control-box">
                    <button type="button" class="filter-btn" onclick="document.getElementById('liveSearch').focus();">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h2><?= number_format($totalStudents) ?></h2>
                    <p>Total Students</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div>
                    <h2><?= number_format($totalTeachers) ?></h2>
                    <p>Total Teachers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-list"></i>
                </div>
                <div>
                    <h2><?= number_format($totalCurrent) ?></h2>
                    <p>Current View Records</p>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>
                    <i class="fa-solid fa-table"></i>
                    <?= ($viewRole === 'students') ? 'Student List' : 'Teacher List' ?>
                </h3>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>CONTACT</th>
                            <th>PASSWORD</th>
                            <?php if ($viewRole === 'students'): ?>
                                <th>COURSE</th>
                            <?php endif; ?>
                            <th>ROLE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody id="usersTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php $display_id = 1; ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                    $search_text = strtolower(
                                        $display_id . ' ' .
                                        $row['id'] . ' ' .
                                        $row['lastname'] . ' ' .
                                        $row['firstname'] . ' ' .
                                        $row['lastname'] . ', ' . $row['firstname'] . ' ' .
                                        $row['email'] . ' ' .
                                        $row['contact_number'] . ' ' .
                                        $row['role'] . ' ' .
                                        ($row['course'] ?? '')
                                    );
                                ?>

                                <tr class="searchable-row" data-search="<?= htmlspecialchars($search_text) ?>">
                                    <td><?= $display_id ?></td>

                                    <td>
                                        <span class="user-name">
                                            <?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($row['email']) ?></td>

                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>

                                    <td><span class="password-mask">••••••••</span></td>

                                    <?php if ($viewRole === 'students'): ?>
                                        <td>
                                            <span class="badge course-badge">
                                                <?= htmlspecialchars($row['course']) ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <span class="badge <?= ($row['role'] === 'student') ? 'role-student' : 'role-teacher' ?>">
                                            <?= strtoupper(htmlspecialchars($row['role'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-group">
                                            <?php if ($viewRole === 'students'): ?>
                                                <a href="view_user.php?id=<?= $row['id'] ?>&return=<?= urlencode($returnUrl) ?>" class="action-btn view-btn" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="edit_user.php?id=<?= $row['id'] ?>&return=<?= urlencode($returnUrl) ?>" class="action-btn edit-btn" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <a href="delete_user.php?id=<?= $row['id'] ?>&return=<?= urlencode($returnUrl) ?>"
                                               class="action-btn archive-btn"
                                               title="Archive"
                                               onclick="return confirm('Move this user to Recently Deleted?')">
                                               <i class="fa-solid fa-box-archive"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <?php $display_id++; ?>
                            <?php endwhile; ?>

                            <tr id="noSearchResultRow" style="display:none;">
                                <td colspan="<?= ($viewRole === 'students') ? '8' : '7' ?>" class="empty-row">
                                    No matching users found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= ($viewRole === 'students') ? '8' : '7' ?>" class="empty-row">
                                    No records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById("liveSearch");
    const rows = document.querySelectorAll(".searchable-row");
    const noSearchResultRow = document.getElementById("noSearchResultRow");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const value = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchText = row.getAttribute("data-search") || "";

                if (searchText.includes(value)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            if (noSearchResultRow) {
                noSearchResultRow.style.display = visibleCount === 0 ? "" : "none";
            }
        });
    }
});
</script>

</body>
</html>