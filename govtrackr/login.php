<?php
session_start();
require_once 'config.php';

// Redirect user if already logged in.
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
    exit();
}

$conn = getDatabaseConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sn = trim($_POST['student_number'] ?? '');
    $pw = $_POST['password'] ?? '';

    if (!$sn || !$pw) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role, profile_pic FROM users WHERE student_number = ?");
        $stmt->bind_param("s", $sn);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($pw, $row['password'])) {
            $_SESSION['user_id']     = $row['id'];
            $_SESSION['user_name']   = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['role']        = $row['role'];
            $_SESSION['profile_pic'] = $row['profile_pic'];

            header("Location: " . ($row['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
            exit();
        } else {
            $error = "Invalid student number or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Sign In</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="auth-body auth-page">

<div class="auth-card">
    <div class="auth-left">
        <div>
            <h1>Welcome to<br><span class="brand-gov">Gov</span><span style="color:#fff">Trackr</span></h1>
        </div>
        <p>Official HAU COMELEC Candidate Tracking System.</p>
        <ul class="auth-features">
            <li>Mock Election</li>
            <li>Real-time Results</li>
            <li>Election Calendar</li>
            <li>Candidate Profiles</li>
        </ul>
    </div>

    <div class="auth-right">
        <h2>Sign In</h2>
        <p class="sub">Enter your student credentials to proceed</p>

        <div class="auth-notice">
            🔑 Your default password is your <strong>Student ID number</strong>. You can change it after logging in via your Profile.
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Student Number</label>
                <div class="input-wrap">
                    <input type="text" name="student_number" placeholder="e.g. 20626727"
                           value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" placeholder="Default: your Student ID" required>
                </div>
            </div>
            <button type="submit" class="btn btn-gold btn-full">Sign In</button>
        </form>

        <p style="text-align:center; font-size:0.8rem; color:var(--muted); margin-top:20px;">
            HAU COMELEC · Holy Angel University
        </p>
    </div>
</div>

</body>
</html>
