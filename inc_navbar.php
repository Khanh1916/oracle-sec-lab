<?php
// inc_navbar.php  –  Shared navigation bar
if (empty($_SESSION['user_id'])) return;
$role     = $_SESSION['role']     ?? 'student';
$username = $_SESSION['username'] ?? '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= APP_BASE ?>/dashboard.php">🎓 FPT Student Portal</a>
        <span class="nav-subtitle">DBS401 Security Lab</span>
    </div>
    <div class="navbar-links">
        <a href="<?= APP_BASE ?>/dashboard.php">Dashboard</a>
        <a href="<?= APP_BASE ?>/courses.php">Courses</a>
        <a href="<?= APP_BASE ?>/schedule.php">Timetable</a>
        <?php if ($role === 'student'): ?>
            <a href="<?= APP_BASE ?>/transcript.php">Transcript</a>
            <a href="<?= APP_BASE ?>/tuition.php">Tuition</a>
            <a href="<?= APP_BASE ?>/store.php">Store</a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="<?= APP_BASE ?>/grades.php">Grading</a>
        <?php endif; ?>
        <a href="<?= APP_BASE ?>/search.php">Search</a>
        <?php if ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin.php">Admin</a>
            <a href="<?= APP_BASE ?>/audit.php">Audit</a>
        <?php endif; ?>
        <a href="<?= APP_BASE ?>/profile.php">Profile</a>
        <span class="nav-user">
            👤 <?= htmlspecialchars($username) ?>
            [<span class="badge-inline badge-<?= $role ?>"><?= $role ?></span>]
        </span>
        <a href="<?= APP_BASE ?>/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
