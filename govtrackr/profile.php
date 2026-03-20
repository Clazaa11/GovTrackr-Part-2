<?php
session_start();
require_once 'config.php';
require_once 'includes/positions.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id    = $_SESSION['user_id'];
$activePage = 'profile';
$pageTitle  = 'My Profile';
$message    = '';
$msg_type   = '';

$conn = getDatabaseConnection();
// Load current user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// ── HANDLE PROFILE UPDATE ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name'] ?? '');
    $lname = trim($_POST['last_name']  ?? '');
    $pw    = $_POST['new_password']    ?? '';
    $cpw   = $_POST['confirm_password']?? '';

    if (!$fname || !$lname) {
        $message  = "Name fields cannot be empty.";
        $msg_type = 'error';
    } elseif ($pw && strlen($pw) < 6) {
        $message  = "New password must be at least 6 characters.";
        $msg_type = 'error';
    } elseif ($pw && $pw !== $cpw) {
        $message  = "Passwords do not match.";
        $msg_type = 'error';
    } else {
        // Handle photo upload
        $photo_path = $user['profile_pic'];
        if (!empty($_FILES['profile_pic']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $dir       = 'assets/uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename  = 'user_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $filename)) {
                    $photo_path = $dir . $filename;
                }
            }
        }

        if ($pw) {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, profile_pic=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $fname, $lname, $photo_path, $hash, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, profile_pic=? WHERE id=?");
            $stmt->bind_param("sssi", $fname, $lname, $photo_path, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['user_name']   = "$fname $lname";
            $_SESSION['profile_pic'] = $photo_path;
            $message  = "Profile updated successfully.";
            $msg_type = 'success';
            $user     = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
        } else {
            $message  = "Update failed. Please try again.";
            $msg_type = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Profile</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="dash-body">

<?php include 'includes/sidebar.php'; ?>

<div style="flex:1; display:flex; flex-direction:column;">
    <?php include 'includes/topbar.php'; ?>

    <main class="dash-content">

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Profile header -->
        <div class="profile-header">
            <div class="profile-pic-wrap">
                <?php if ($user['profile_pic']): ?>
                    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile" class="profile-pic">
                <?php else: ?>
                    <div class="profile-pic">👤</div>
                <?php endif; ?>
            </div>
            <div>
                <div class="profile-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                <div class="profile-sn">Student No. <?= htmlspecialchars($user['student_number']) ?></div>
                <?php if (!empty($user['college'])): ?>
                <div style="font-size:.85rem; opacity:.8; margin-top:3px;">
                    <?= htmlspecialchars(college_full($user['college']) ?? $user['college']) ?>
                    <span style="background:var(--gold);color:#1A0A0F;border-radius:20px;padding:1px 10px;font-size:.75rem;font-weight:700;margin-left:6px;">
                        <?= htmlspecialchars($user['college']) ?>
                    </span>
                </div>
                <?php endif; ?>
                <span class="profile-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="grid-2" style="align-items:start;">
            <!-- Edit form -->
            <div class="card">
                <h3 class="card-title">Edit Profile</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="input-row">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <div class="input-wrap">
                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <div class="input-wrap">
                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Student Number</label>
                        <div class="input-wrap" style="opacity:.7;">
                            <input type="text" value="<?= htmlspecialchars($user['student_number']) ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <div class="input-wrap" style="padding:8px 14px;">
                            <input type="file" name="profile_pic" accept="image/*" style="padding:4px 0;">
                        </div>
                    </div>

                    <hr style="border:none; border-top:1px solid var(--border); margin:18px 0;">
                    <p style="font-size:.82rem; color:var(--muted); margin-bottom:12px;">Leave password fields blank to keep your current password.</p>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrap">
                            <input type="password" name="new_password" placeholder="Min. 6 characters">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-wrap">
                            <input type="password" name="confirm_password" placeholder="Re-enter new password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-full">Save Changes</button>
                </form>
            </div>

            <!-- Activity summary -->
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="card card-dark">
                    <h3 class="card-title">Account Summary</h3>
                    <?php
                    $voted = $conn->query("SELECT COUNT(*) AS n FROM votes WHERE voter_id=$user_id")->fetch_assoc()['n'];
                    $lineup_count = $conn->query("SELECT COUNT(*) AS n FROM lineups WHERE user_id=$user_id")->fetch_assoc()['n'];
                    ?>
                    <div style="display:flex; flex-direction:column; gap:12px; margin-top:4px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.1);">
                            <span style="opacity:.8; font-size:.9rem;">Mock Vote Status</span>
                            <span class="badge <?= $voted ? 'badge-green' : 'badge-red' ?>">
                                <?= $voted ? 'Voted' : 'Not yet voted' ?>
                            </span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.1);">
                            <span style="opacity:.8; font-size:.9rem;">Lineup Saved</span>
                            <span class="badge <?= $lineup_count ? 'badge-green' : 'badge-gold' ?>">
                                <?= $lineup_count ? "$lineup_count picks saved" : 'No lineup yet' ?>
                            </span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.1);">
                            <span style="opacity:.8; font-size:.9rem;">College</span>
                            <span style="color:var(--gold); font-weight:700;">
                                <?= !empty($user['college']) ? htmlspecialchars($user['college']) : '—' ?>
                            </span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0;">
                            <span style="opacity:.8; font-size:.9rem;">Member Since</span>
                            <span style="color:var(--gold); font-weight:700;"><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Quick Links</h3>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <a href="candidates.php"   class="btn btn-purple">View Candidates</a>
                        <a href="mock_election.php" class="btn btn-gold">Go to Mock Election</a>
                        <a href="partylists.php"   class="btn btn-outline">View Partylists</a>
                    </div>
                </div>

                <div class="card">
                <h3 class="card-title">About GovTrackr</h3>
                <p style="font-size:.9rem; line-height:1.7; color:var(--muted);">
                    GovTrackr is the Official HAU COMELEC Candidate Tracking System for Holy Angel University.
                    It allows students to view candidates, explore party platforms, participate in mock elections,
                    and stay updated on election schedules.
                </p>
                <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">
                <p style="font-size:.82rem; color:var(--muted);">Version 1.0.0 · HAU COMELEC</p>
                <a href="logout.php" class="btn btn-danger btn-full" style="margin-top:14px;"
                   onclick="return confirm('Are you sure you want to log out?')">
                    Sign Out
                </a>
            </div>
            </div>
        </div>

    </main>
</div>

</body>
</html>
