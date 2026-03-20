<?php
require_once 'auth_guard.php';
$activePage = 'events';
$pageTitle  = 'Manage Events';
$message = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $title    = trim($_POST['title']       ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $date     = $_POST['event_date']       ?? '';
        $time     = $_POST['event_time']       ?? null;
        $location = trim($_POST['location']    ?? '');
        $uid      = $_SESSION['user_id'];

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, location, created_by) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("sssssi", $title, $desc, $date, $time, $location, $uid);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, event_time=?, location=? WHERE id=?");
            $stmt->bind_param("sssssi", $title, $desc, $date, $time, $location, $id);
        }
        $stmt->execute(); $stmt->close();
        $message = $action === 'add' ? "Event added." : "Event updated."; $msg_type = 'success';
    }

    if ($action === 'delete') {
        $conn->query("DELETE FROM events WHERE id=" . (int)$_POST['id']);
        $message = "Event deleted."; $msg_type = 'success';
    }
}

$edit_ev = null;
if (isset($_GET['edit'])) {
    $edit_ev = $conn->query("SELECT * FROM events WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}
$show_form = (isset($_GET['action']) && $_GET['action'] === 'add') || $edit_ev;
$events    = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>GovTrackr — Events</title><link rel="stylesheet" href="../css/styles.css"></head>
<body class="dash-body">
<?php include 'sidebar.php'; ?>
<div style="flex:1;display:flex;flex-direction:column;">
<header class="topbar">
    <div class="topbar-left"><p class="topbar-welcome">Admin Panel</p><h1 class="topbar-title">Events</h1></div>
    <div class="topbar-right"><a href="events.php?action=add" class="btn btn-gold">Add Event</a></div>
</header>
<main class="dash-content">
    <?php if ($message): ?><div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <?php if ($show_form): ?>
    <div class="card" style="max-width:580px;">
        <h3 class="card-title"><?= $edit_ev ? 'Edit Event' : 'Add Event' ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $edit_ev ? 'edit' : 'add' ?>">
            <?php if ($edit_ev): ?><input type="hidden" name="id" value="<?= $edit_ev['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Event Title *</label>
                <div class="input-wrap"><input type="text" name="title" required value="<?= htmlspecialchars($edit_ev['title'] ?? '') ?>"></div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <div class="input-wrap"><input type="date" name="event_date" required value="<?= $edit_ev['event_date'] ?? '' ?>"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Time</label>
                    <div class="input-wrap"><input type="time" name="event_time" value="<?= $edit_ev['event_time'] ?? '' ?>"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <div class="input-wrap"><input type="text" name="location" placeholder="e.g. HAU Gym" value="<?= htmlspecialchars($edit_ev['location'] ?? '') ?>"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="input-wrap" style="align-items:flex-start;padding:12px 14px;">
                    <textarea name="description" rows="3" style="border:none;outline:none;background:transparent;width:100%;"><?= htmlspecialchars($edit_ev['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-gold"><?= $edit_ev ? 'Save Changes' : 'Add Event' ?></button>
                <a href="events.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <div class="card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Time</th><th>Title</th><th>Location</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while ($ev = $events->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= date('M j, Y', strtotime($ev['event_date'])) ?></strong></td>
                        <td><?= $ev['event_time'] ? date('g:i A', strtotime($ev['event_time'])) : '—' ?></td>
                        <td><?= htmlspecialchars($ev['title']) ?></td>
                        <td style="font-size:.85rem;color:var(--muted);"><?= htmlspecialchars($ev['location'] ?? '—') ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="events.php?edit=<?= $ev['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete event?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $ev['id'] ?>">
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
