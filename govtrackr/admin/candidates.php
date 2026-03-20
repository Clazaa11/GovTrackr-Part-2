<?php
require_once 'auth_guard.php';
require_once '../includes/positions.php';
$activePage = 'candidates';
$pageTitle  = 'Manage Candidates';
$message    = '';
$msg_type   = '';

// ── HANDLE FORM SUBMISSIONS ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $full_name   = trim($_POST['full_name']    ?? '');
            $position    = trim($_POST['position']     ?? '');
            $college_val = trim($_POST['college']      ?? '');
            // University-wide positions don't get a college tag
            $college     = in_array($position, POSITIONS['University-Wide']) ? null : ($college_val ?: null);
            $partylist   = (int)($_POST['partylist_id'] ?? 0) ?: null;
            $bio         = trim($_POST['bio']           ?? '');
            $platform    = trim($_POST['platform']      ?? '');
            $year_level  = trim($_POST['year_level']    ?? '');
            $course      = trim($_POST['course']        ?? '');

            // Photo upload
            $photo = $_POST['existing_photo'] ?? null;
            if (!empty($_FILES['photo']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $dir = '../assets/uploads/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fname = 'cand_' . time() . '_' . rand(100,999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $fname)) {
                        $photo = 'assets/uploads/' . $fname;
                    }
                }
            }

            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO candidates (full_name, position, college, partylist_id, photo, bio, platform, year_level, course) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssissss", $full_name, $position, $college, $partylist, $photo, $bio, $platform, $year_level, $course);
                $stmt->execute();
                $message = "Candidate added successfully."; $msg_type = 'success';
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE candidates SET full_name=?, position=?, college=?, partylist_id=?, photo=?, bio=?, platform=?, year_level=?, course=? WHERE id=?");
                $stmt->bind_param("ssssissssi", $full_name, $position, $college, $partylist, $photo, $bio, $platform, $year_level, $course, $id);
                $stmt->execute();
                $message = "Candidate updated."; $msg_type = 'success';
            }
            $stmt->close();
        }

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $conn->query("DELETE FROM candidates WHERE id=$id");
            $message = "Candidate deleted."; $msg_type = 'success';
        }
    }
}

// Load for edit
$edit_cand = null;
if (isset($_GET['edit'])) {
    $edit_id   = (int)$_GET['edit'];
    $edit_cand = $conn->query("SELECT * FROM candidates WHERE id=$edit_id")->fetch_assoc();
}

$show_form = (isset($_GET['action']) && $_GET['action'] === 'add') || $edit_cand;

$field_expr = position_field_expr('c.position');
$candidates = $conn->query("
    SELECT c.*, p.name AS partylist_name
    FROM   candidates c
    LEFT JOIN partylists p ON c.partylist_id = p.id
    ORDER BY $field_expr, COALESCE(c.college, ''), c.full_name
");
$partylists = $conn->query("SELECT id, name FROM partylists ORDER BY name");
$pl_options = [];
while ($pl = $partylists->fetch_assoc()) { $pl_options[] = $pl; }

// Determine if edit candidate is university-wide (for JS toggle)
$edit_is_uni = $edit_cand
    ? in_array($edit_cand['position'], POSITIONS['University-Wide'])
    : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Manage Candidates</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="dash-body">

<?php include 'sidebar.php'; ?>

<div style="flex:1;display:flex;flex-direction:column;">
<header class="topbar">
    <div class="topbar-left">
        <p class="topbar-welcome">Admin Panel</p>
        <h1 class="topbar-title">Candidates</h1>
    </div>
    <div class="topbar-right">
        <a href="candidates.php?action=add" class="btn btn-gold">+ Add Candidate</a>
    </div>
</header>

<main class="dash-content">

    <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($show_form): ?>
    <!-- ── ADD / EDIT FORM ── -->
    <div class="card" style="max-width:720px;">
        <h3 class="card-title"><?= $edit_cand ? 'Edit Candidate' : 'Add New Candidate' ?></h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_cand ? 'edit' : 'add' ?>">
            <?php if ($edit_cand): ?>
            <input type="hidden" name="id" value="<?= $edit_cand['id'] ?>">
            <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($edit_cand['photo'] ?? '') ?>">
            <?php endif; ?>

            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <div class="input-wrap">
                    <input type="text" name="full_name" required
                           value="<?= htmlspecialchars($edit_cand['full_name'] ?? '') ?>">
                </div>
            </div>

            <!-- Position + College row -->
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Position *</label>
                    <div class="input-wrap">
                        <select name="position" id="positionSelect" required
                                style="border:none;outline:none;background:transparent;width:100%;padding:12px 0;">
                            <option value="">— Select Position —</option>
                            <optgroup label="University-Wide">
                                <?php foreach (POSITIONS['University-Wide'] as $pos): ?>
                                <option value="<?= $pos ?>"
                                    <?= ($edit_cand['position'] ?? '') === $pos ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Per College">
                                <?php foreach (POSITIONS['Per College'] as $pos): ?>
                                <option value="<?= $pos ?>"
                                    <?= ($edit_cand['position'] ?? '') === $pos ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="collegeGroup"
                     style="<?= $edit_is_uni ? 'opacity:.4;pointer-events:none;' : '' ?>">
                    <label class="form-label">College <span id="collegeRequired" style="color:var(--danger);"><?= $edit_is_uni ? '' : '*' ?></span></label>
                    <div class="input-wrap">
                        <select name="college" id="collegeSelect"
                                style="border:none;outline:none;background:transparent;width:100%;padding:12px 0;"
                                <?= $edit_is_uni ? '' : 'required' ?>>
                            <option value="">— Select College —</option>
                            <?php foreach (COLLEGES as $full_name => $abbr): // key=full name, value=abbr ?>
                            <option value="<?= htmlspecialchars($full_name) ?>"
                                <?= ($edit_cand['college'] ?? '') === $full_name ? 'selected' : '' ?>>
                                <?= htmlspecialchars($abbr) ?> — <?= htmlspecialchars($full_name) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p style="font-size:.75rem; color:var(--muted); margin-top:4px;">
                        Required for Governor, Vice-Governor, Councilor, Senator
                    </p>
                </div>
            </div>

            <!-- Partylist + Photo -->
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Partylist</label>
                    <div class="input-wrap">
                        <select name="partylist_id"
                                style="border:none;outline:none;background:transparent;width:100%;padding:12px 0;">
                            <option value="">— None / Independent —</option>
                            <?php foreach ($pl_options as $pl): ?>
                            <option value="<?= $pl['id'] ?>"
                                    <?= ($edit_cand['partylist_id'] ?? 0) == $pl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Photo</label>
                    <div class="input-wrap" style="padding:8px 14px;">
                        <input type="file" name="photo" accept="image/*" style="padding:4px 0;">
                    </div>
                </div>
            </div>

            <!-- Year level + Course -->
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Year Level</label>
                    <div class="input-wrap">
                        <input type="text" name="year_level" placeholder="e.g. 3rd Year"
                               value="<?= htmlspecialchars($edit_cand['year_level'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <div class="input-wrap">
                        <input type="text" name="course" placeholder="e.g. BS Computer Science"
                               value="<?= htmlspecialchars($edit_cand['course'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div class="form-group">
                <label class="form-label">Bio / About</label>
                <div class="input-wrap" style="align-items:flex-start;padding:12px 14px;">
                    <textarea name="bio" rows="3"
                              style="border:none;outline:none;background:transparent;width:100%;"><?= htmlspecialchars($edit_cand['bio'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Platform -->
            <div class="form-group">
                <label class="form-label">Platform & Advocacies</label>
                <div class="input-wrap" style="align-items:flex-start;padding:12px 14px;">
                    <textarea name="platform" rows="3"
                              style="border:none;outline:none;background:transparent;width:100%;"><?= htmlspecialchars($edit_cand['platform'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-gold"><?= $edit_cand ? 'Save Changes' : 'Add Candidate' ?></button>
                <a href="candidates.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- ── CANDIDATES TABLE ── -->
    <div class="card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>College</th>
                        <th>Partylist</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $candidates->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($c['photo']): ?>
                                <img src="../<?= htmlspecialchars($c['photo']) ?>"
                                     style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                            <?php else: ?>
                                <span style="font-size:1.6rem;">👤</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                        <td><span class="badge badge-purple"><?= htmlspecialchars($c['position']) ?></span></td>
                        <td style="font-size:.82rem; color:var(--muted);">
                            <?= htmlspecialchars($c['college'] ?? '—') ?>
                        </td>
                        <td><?= htmlspecialchars($c['partylist_name'] ?? '—') ?></td>
                        <td style="font-size:.82rem; color:var(--muted);">
                            <?= htmlspecialchars($c['course'] ?? '—') ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="candidates.php?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Delete this candidate?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $c['id'] ?>">
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

<script>
// Toggle college field based on selected position
const UNI_WIDE = <?= json_encode(POSITIONS['University-Wide']) ?>;
const positionSelect = document.getElementById('positionSelect');
const collegeGroup   = document.getElementById('collegeGroup');
const collegeSelect  = document.getElementById('collegeSelect');
const collegeReq     = document.getElementById('collegeRequired');

function toggleCollege() {
    const isUni = UNI_WIDE.includes(positionSelect.value);
    collegeGroup.style.opacity         = isUni ? '.4' : '1';
    collegeGroup.style.pointerEvents   = isUni ? 'none' : 'auto';
    collegeSelect.required             = !isUni;
    collegeReq.textContent             = isUni ? '' : '*';
    if (isUni) collegeSelect.value = '';
}
positionSelect.addEventListener('change', toggleCollege);
toggleCollege(); // run on page load for edit mode
</script>

</body>
</html>
