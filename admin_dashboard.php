<?php
include "db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"];

// Handle approval/rejection with a CSRF-protected POST request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_post();
    $action = $_POST['action'] ?? '';
    $vendor_id = (int)($_POST['id'] ?? 0);
    if (!in_array($action, ['approve', 'reject'], true) || $vendor_id < 1) {
        http_response_code(400);
        exit('Invalid request.');
    }
    $status = $action === 'approve' ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE vendors SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $vendor_id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit;
}
// Fetch pending vendors
$pending_stmt = $conn->query("SELECT * FROM vendors WHERE status = 'Pending' ORDER BY id DESC");
$pending_vendors = $pending_stmt->fetch_all(MYSQLI_ASSOC);

// Fetch all vendors count for stats
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM vendors")->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=2">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | CampusExpress</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f1f5f9; }
    .admin-nav {
        background: #1e293b;
        color: white;
        padding: 15px 0;
        margin-bottom: 30px;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .stat-card {
        padding: 25px;
        text-align: center;
    }
    .stat-card h3 { font-size: 2.5rem; color: var(--primary); margin-bottom: 5px; }
    .stat-card p { font-weight: 700; color: var(--text-muted); }

    .table-container {
        background: white;
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid var(--border);
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th {
        background: #f8fafc;
        padding: 15px 20px;
        text-align: left;
        font-size: 0.85rem;
        text-transform: uppercase;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
    }
    td {
        padding: 20px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    .vendor-info h4 { margin-bottom: 5px; }
    .vendor-info p { font-size: 0.85rem; color: var(--text-muted); }

    .actions {
        display: flex;
        gap: 10px;
    }
    .btn-approve { background: #d1fae5; color: #065f46; font-size: 0.8rem; }
    .btn-reject { background: #fee2e2; color: #991b1b; font-size: 0.8rem; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
  </style>
</head>
<body>

<nav class="admin-nav">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">
            <img src="logo-128.png?v=2" alt="" class="site-logo-image" width="42" height="42">
            <span style="color: white;">Admin Portal</span>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <span style="font-weight: 600; font-size: 0.9rem;">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.8rem;">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="stats-row">
        <div class="card stat-card animate-fade">
            <h3><?php echo $stats['total']; ?></h3>
            <p>Total Vendors</p>
        </div>
        <div class="card stat-card animate-fade" style="animation-delay: 0.1s;">
            <h3 style="color: var(--success);"><?php echo $stats['approved']; ?></h3>
            <p>Approved</p>
        </div>
        <div class="card stat-card animate-fade" style="animation-delay: 0.2s;">
            <h3 style="color: var(--warning);"><?php echo $stats['pending']; ?></h3>
            <p>Pending Review</p>
        </div>
    </div>

    <h2 style="margin-bottom: 20px;">Pending Approvals</h2>

    <div class="table-container shadow-md">
        <?php if (empty($pending_vendors)): ?>
            <div class="empty-state">
                <p>No pending vendor registrations at the moment.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Vendor Details</th>
                        <th>Business Info</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_vendors as $v): ?>
                    <tr>
                        <td>
                            <div class="vendor-info">
                                <h4><?php echo htmlspecialchars($v['vendor_name']); ?></h4>
                                <p>Registered: <?php echo date('M d, Y', strtotime($v['registration_date'])); ?></p>
                            </div>
                        </td>
                        <td>
                            <div class="vendor-info">
                                <strong><?php echo htmlspecialchars($v['business_type']); ?></strong>
                                <p><?php echo htmlspecialchars($v['business_address']); ?></p>
                            </div>
                        </td>
                        <td>
                            <div class="vendor-info">
                                <p><?php echo htmlspecialchars($v['email']); ?></p>
                                <p><?php echo htmlspecialchars($v['phone']); ?></p>
                            </div>
                        </td>
                        <td>
                            <form method="POST" class="actions">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

