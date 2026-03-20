<?php
require_once 'auth_guard.php';
require_once '../includes/positions.php';
$activePage = 'users';
$message = ''; $msg_type = '';

// ── HANDLE ADD / EDIT / DELETE ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $sn      = trim($_POST['student_number'] ?? '');
        $fname   = trim($_POST['first_name']     ?? '');
        $lname   = trim($_POST['last_name']      ?? '');
        $college = trim($_POST['college']        ?? '') ?: null;

        if ($action === 'add') {
            // Default password = student number
            $hash = password_hash($sn, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (student_number, first_name, last_name, password, role, college) VALUES (?,?,?,?,'student',?)");
            $stmt->bind_param("sssss", $sn, $fname, $lname, $hash, $college);
            if ($stmt->execute()) {
                $message = "Student added. Default password is their Student ID ($sn).";
                $msg_type = 'success';
            } else {
                $message = "Error: " . $conn->error; $msg_type = 'error';
            }
            $stmt->close();
        } else {
            $id = (int)$_POST['id'];
            // Optional password reset
            $new_pw = trim($_POST['new_password'] ?? '');
            if ($new_pw) {
                $hash = password_hash($new_pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, college=?, password=? WHERE id=?");
                $stmt->bind_param("ssssi", $fname, $lname, $college, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, college=? WHERE id=?");
                $stmt->bind_param("sssi", $fname, $lname, $college, $id);
            }
            $stmt->execute(); $stmt->close();
            $message = "Student updated."; $msg_type = 'success';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role='student'");
        $message = "Student removed."; $msg_type = 'success';
    }

    if ($action === 'reset_password') {
        $id = (int)$_POST['id'];
        // Reset to student number
        $row = $conn->query("SELECT student_number FROM users WHERE id=$id")->fetch_assoc();
        if ($row) {
            $hash = password_hash($row['student_number'], PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hash' WHERE id=$id");
            $message = "Password reset to Student ID for that account."; $msg_type = 'success';
        }
    }
}

// Edit mode
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = $conn->query("SELECT * FROM users WHERE id=" . (int)$_GET['edit'] . " AND role='student'")->fetch_assoc();
}
$show_form = (isset($_GET['action']) && $_GET['action'] === 'add') || $edit_user;

$users = $conn->query("
    SELECT u.*, (SELECT COUNT(*) FROM votes WHERE voter_id=u.id) AS vote_count
    FROM   users u
    WHERE  u.role = 'student'
    ORDER BY u.last_name, u.first_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GovTrackr — Students</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="dash-body">
<?php include 'sidebar.php'; ?>
<div style="flex:1;display:flex;flex-direction:column;">
<header class="topbar">
    <div class="topbar-left">
        <p class="topbar-welcome">Admin Panel</p>
        <h1 class="topbar-title">Registered Students</h1>
    </div>
    <div class="topbar-right">
        <a href="users.php?action=add" class="btn btn-gold">+ Add Student</a>
    </div>
</header>
<main class="dash-content">

    <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($show_form): ?>
    <!-- ── ADD / EDIT FORM ── -->
    <div class="card" style="max-width:560px;">
        <h3 class="card-title"><?= $edit_user ? 'Edit Student' : 'Add Student' ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'add' ?>">
            <?php if ($edit_user): ?>
            <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
            <?php endif; ?>

            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <div class="input-wrap">
                        <input type="text" name="first_name" required
                               value="<?= htmlspecialchars($edit_user['first_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <div class="input-wrap">
                        <input type="text" name="last_name" required
                               value="<?= htmlspecialchars($edit_user['last_name'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Student Number *</label>
                    <div class="input-wrap" <?= $edit_user ? 'style="opacity:.6;"' : '' ?>>
                        <input type="text" name="student_number"
                               <?= $edit_user ? 'disabled' : 'required' ?>
                               placeholder="e.g. 20626727"
                               value="<?= htmlspecialchars($edit_user['student_number'] ?? '') ?>">
                    </div>
                    <?php if (!$edit_user): ?>
                    <p style="font-size:.75rem;color:var(--muted);margin-top:4px;">Default password will be set to the Student Number.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">College</label>
                    <div class="input-wrap">
                        <select name="college"
                                style="border:none;outline:none;background:transparent;width:100%;padding:12px 0;">
                            <option value="">— Not set —</option>
                            <?php foreach (COLLEGES as $full => $abbr): ?>
                            <option value="<?= htmlspecialchars($abbr) ?>"
                                <?= ($edit_user['college'] ?? '') === $abbr ? 'selected' : '' ?>>
                                <?= htmlspecialchars($abbr) ?> — <?= htmlspecialchars($full) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p style="font-size:.75rem;color:var(--muted);margin-top:4px;">Determines which college positions the student can vote for.</p>
                </div>
            </div>

            <?php if ($edit_user): ?>
            <div class="form-group">
                <label class="form-label">New Password <span style="font-weight:400;color:var(--muted);">(leave blank to keep current)</span></label>
                <div class="input-wrap">
                    <input type="password" name="new_password" placeholder="Min. 6 characters">
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="submit" class="btn btn-gold"><?= $edit_user ? 'Save Changes' : 'Add Student' ?></button>
                <a href="users.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- ── STUDENTS TABLE ── -->
    <div class="card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Student Number</th>
                        <th>College</th>
                        <th>Registered</th>
                        <th>Voted?</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['student_number']) ?></td>
                        <td>
                            <?php if ($u['college']): ?>
                            <span class="badge badge-gold" title="<?= htmlspecialchars(college_full($u['college']) ?? '') ?>">
                                <?= htmlspecialchars($u['college']) ?>
                            </span>
                            <?php else: ?>
                            <span style="color:var(--muted);font-size:.82rem;">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <span class="badge <?= $u['vote_count'] > 0 ? 'badge-green' : 'badge-red' ?>">
                                <?= $u['vote_count'] > 0 ? 'Voted' : 'Not yet' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Reset password to Student ID?')">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id"     value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-purple btn-sm">Reset PW</button>
                                </form>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Remove this student?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>
</body>
</html>
