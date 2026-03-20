<?php
require_once 'auth_guard.php';
$activePage = 'partylists';
$pageTitle  = 'Manage Partylists';
$message    = '';
$msg_type   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        $logo = $_POST['existing_logo'] ?? null;
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $dir = '../assets/uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'pl_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fname)) {
                    $logo = 'assets/uploads/' . $fname;
                }
            }
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO partylists (name, description, logo) VALUES (?,?,?)");
            $stmt->bind_param("sss", $name, $desc, $logo);
            $stmt->execute(); $stmt->close();
            $message = "Partylist added."; $msg_type = 'success';
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE partylists SET name=?, description=?, logo=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $desc, $logo, $id);
            $stmt->execute(); $stmt->close();
            $message = "Partylist updated."; $msg_type = 'success';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM partylists WHERE id=$id");
        $message = "Partylist deleted."; $msg_type = 'success';
    }
}

$edit_pl = null;
if (isset($_GET['edit'])) {
    $edit_pl = $conn->query("SELECT * FROM partylists WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}
$show_form = (isset($_GET['action']) && $_GET['action'] === 'add') || $edit_pl;
$partylists = $conn->query("SELECT p.*, COUNT(c.id) AS member_count FROM partylists p LEFT JOIN candidates c ON c.partylist_id = p.id GROUP BY p.id ORDER BY p.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovTrackr — Partylists</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="dash-body">
<?php include 'sidebar.php'; ?>
<div style="flex:1;display:flex;flex-direction:column;">
<header class="topbar">
    <div class="topbar-left"><p class="topbar-welcome">Admin Panel</p><h1 class="topbar-title">Partylists</h1></div>
    <div class="topbar-right"><a href="partylists.php?action=add" class="btn btn-gold">Add Partylist</a></div>
</header>
<main class="dash-content">
    <?php if ($message): ?><div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <?php if ($show_form): ?>
    <div class="card" style="max-width:580px;">
        <h3 class="card-title"><?= $edit_pl ? 'Edit Partylist' : 'Add Partylist' ?></h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_pl ? 'edit' : 'add' ?>">
            <?php if ($edit_pl): ?>
            <input type="hidden" name="id" value="<?= $edit_pl['id'] ?>">
            <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($edit_pl['logo'] ?? '') ?>">
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Partylist Name *</label>
                <div class="input-wrap"><input type="text" name="name" required value="<?= htmlspecialchars($edit_pl['name'] ?? '') ?>"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="input-wrap" style="align-items:flex-start;padding:12px 14px;">
                    <textarea name="description" rows="3" style="border:none;outline:none;background:transparent;width:100%;"><?= htmlspecialchars($edit_pl['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Logo</label>
                <div class="input-wrap" style="padding:8px 14px;"><input type="file" name="logo" accept="image/*" style="padding:4px 0;"></div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-gold"><?= $edit_pl ? 'Save Changes' : 'Add Partylist' ?></button>
                <a href="partylists.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <div class="card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Logo</th><th>Name</th><th>Description</th><th>Members</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while ($pl = $partylists->fetch_assoc()): ?>
                    <tr>
                        <td><?php if ($pl['logo']): ?><img src="../<?= htmlspecialchars($pl['logo']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"><?php else: ?><?php endif; ?></td>
                        <td><strong><?= htmlspecialchars($pl['name']) ?></strong></td>
                        <td style="font-size:.85rem;color:var(--muted);max-width:240px;"><?= htmlspecialchars(mb_strimwidth($pl['description'] ?? '', 0, 80, '…')) ?></td>
                        <td><span class="badge badge-purple"><?= $pl['member_count'] ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="partylists.php?edit=<?= $pl['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this partylist?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $pl['id'] ?>">
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
