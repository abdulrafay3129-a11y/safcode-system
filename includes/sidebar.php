<?php
if(!isset($_SESSION)) session_start();

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>

<style>
.sidebar{
    width:260px;
    min-width:260px;   /* FIX width */
    max-width:260px;   /* FIX width */
    min-height:100vh;
    background: linear-gradient(180deg, #0d6efd, #0b5ed7);
    padding:15px;
    color:white;
    overflow-x:hidden; /* FIX shaking */
}

.sidebar h4{
    font-weight:700;
    text-align:center;
    margin-bottom:20px;
    color:#fff;
}

.sidebar a{
    display:block;
    padding:10px 12px;
    margin:4px 0;
    border-radius:8px;
    text-decoration:none;
    color:#eaf2ff;
    transition:0.2s;
    font-size:14px;
}

/* ✅ SAME HOVER FOR ALL */
.sidebar a:hover,
.sidebar .dropdown-toggle:hover{
    background:rgba(255,255,255,0.15);
    color:#fff;
    transform: translateX(3px);
}

.sidebar a.active{
    background:#ffffff;
    color:#0d6efd !important;
    font-weight:600;
}

/* dropdown button */
.sidebar .dropdown-toggle{
    width:100%;
    text-align:left;
    background:rgba(255,255,255,0.12);
    border:1px solid rgba(255,255,255,0.2);
    border-radius:8px;
    padding:10px;
    font-weight:500;
    margin-top:8px;
    color:#fff;
    transition:0.2s;
}

/* collapse fix */
.sidebar .collapse{
    width:100%;
}

.sidebar .collapse a{
    margin-left:12px;
    font-size:13px;
    padding:8px 10px;
    border-left:2px solid rgba(255,255,255,0.3);
}

.sidebar a.logout{
    color:#ffdddd;
    margin-top:15px;
}
</style>

<div class="sidebar">

<h4>🎓 Safcode IMS</h4>

<!-- DASHBOARD -->
<a href="dashboard.php" class="<?= $current_page=='dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>

<!-- ================= ADMIN ================= -->
<?php if($role == 'admin'): ?>

<!-- Teacher -->
<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#teacherMenu">
👨‍🏫 Teacher
</button>
<div class="collapse <?= ($current_page=='add_teacher.php' || $current_page=='view_teachers.php') ? 'show' : '' ?>" id="teacherMenu">
<a href="add_teacher.php" class="<?= $current_page=='add_teacher.php' ? 'active' : '' ?>">➕ Add Teacher</a>
<a href="view_teachers.php" class="<?= $current_page=='view_teachers.php' ? 'active' : '' ?>">📋 View Teachers</a>
</div>
</div>

<!-- Student -->
<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#studentMenu">
👨‍🎓 Students
</button>
<div class="collapse <?= ($current_page=='add_student.php' || $current_page=='view_students.php' || $current_page=='dropout_students.php') ? 'show' : '' ?>" id="studentMenu">
<a href="add_student.php" class="<?= $current_page=='add_student.php' ? 'active' : '' ?>">➕ Add Student</a>
<a href="view_students.php" class="<?= $current_page=='view_students.php' ? 'active' : '' ?>">📋 View Students</a>

<!-- ✅ FIXED Student Status -->
<a href="../admin/dropout_students.php" class="<?= $current_page=='dropout_students.php' ? 'active' : '' ?>">📊 Student Status</a>

</div>
</div>

<!-- Courses -->
<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#courseMenu">
📚 Courses
</button>
<div class="collapse <?= ($current_page=='add_course.php' || $current_page=='view_course.php') ? 'show' : '' ?>" id="courseMenu">
<a href="add_course.php" class="<?= $current_page=='add_course.php' ? 'active' : '' ?>">➕ Add Course</a>
<a href="view_course.php" class="<?= $current_page=='view_course.php' ? 'active' : '' ?>">📋 View Courses</a>
</div>
</div>

<!-- Attendance -->
<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#attendanceMenu">
📝 Attendance
</button>
<div class="collapse <?= ($current_page=='attendance.php' || $current_page=='attendance_view.php') ? 'show' : '' ?>" id="attendanceMenu">
<a href="attendance.php" class="<?= $current_page=='attendance.php' ? 'active' : '' ?>">✅ Mark Attendance</a>
<a href="attendance_view.php" class="<?= $current_page=='attendance_view.php' ? 'active' : '' ?>">📋 View Attendance</a>
</div>
</div>

<!-- Fees -->
<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#feeMenu">
💰 Fees
</button>
<div class="collapse <?= ($current_page=='fee_submit.php' || $current_page=='view_fee.php' || $current_page=='view_totalfee.php') ? 'show' : '' ?>" id="feeMenu">
<a href="fee_submit.php" class="<?= $current_page=='fee_submit.php' ? 'active' : '' ?>">➕ Add Fee</a>
<a href="view_fee.php" class="<?= $current_page=='view_fee.php' ? 'active' : '' ?>">📋 View Fee</a>
<a href="view_totalfee.php" class="<?= $current_page=='view_totalfee.php' ? 'active' : '' ?>">📋 View Total Fee</a>
</div>
</div>

<!-- Manage Users -->
<a href="../admin/manage_users.php" class="<?= $current_page=='manage_users.php' ? 'active' : '' ?>">
👤 Manage Users
</a>

<?php endif; ?>

<!-- ================= TEACHER ================= -->
<?php if($role == 'teacher'): ?>

<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#studentMenu">
👨‍🎓 Students
</button>
<div class="collapse show" id="studentMenu">
<a href="view_students.php">📋 View Students</a>
<a href="add_student.php">➕ Add Student</a>
</div>
</div>

<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#attendanceMenu">
📝 Attendance
</button>
<div class="collapse <?= ($current_page=='attendance.php' || $current_page=='attendance_view.php') ? 'show' : '' ?>" id="attendanceMenu">
<a href="attendance.php">✅ Mark Attendance</a>
<a href="attendance_view.php">📋 View Attendance</a>
</div>
</div>

<?php endif; ?>

<!-- ================= RECEPTIONIST ================= -->
<?php if($role == 'receptionist'): ?>

<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#studentMenu">
👨‍🎓 Students
</button>
<div class="collapse show" id="studentMenu">
<a href="add_student.php">➕ Add Student</a>
<a href="view_students.php">📋 View Students</a>
</div>
</div>

<div>
<button class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#feeMenu">
💰 Fees
</button>
<div class="collapse show" id="feeMenu">
<a href="fee_submit.php">➕ Submit Fee</a>
<a href="view_fee.php">📋 View Fee</a>
</div>
</div>

<?php endif; ?>

<!-- ================= STUDENT ================= -->
<?php if($role == 'student'): ?>

<a href="my_attendance.php">✅ My Attendance</a>
<a href="my_fees.php">💰 My Fees</a>
<a href="quiz.php">📝 Quiz</a>

<?php endif; ?>

<!-- LOGOUT -->
<a href="../auth/logout.php" class="logout">🚪 Logout</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>