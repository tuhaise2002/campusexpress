<?php
include "db.php";

if (!isset($_SESSION["vendor_id"])) {
    header("Location: login.php");
    exit();
}

$vendor_id = $_SESSION["vendor_id"];
$vendor_name = $_SESSION["vendor_name"];
$phone = $_SESSION["phone"];

// Fetch vendor status for display
$v_stmt = $conn->prepare("SELECT status FROM vendors WHERE id = ?");
$v_stmt->bind_param("i", $vendor_id);
$v_stmt->execute();
$v_status = $v_stmt->get_result()->fetch_assoc()['status'];

$stmt = $conn->prepare("SELECT * FROM menu_items WHERE vendor_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$items = $stmt->get_result();

$stats_stmt = $conn->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'Sold Out' THEN 1 ELSE 0 END) as soldout
    FROM menu_items WHERE vendor_id = ?");
$stats_stmt->bind_param("i", $vendor_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=2">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor Portal | CampusExpress</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f8fafc; }
    .side-panel {
        position: sticky;
        top: 100px;
    }
    .dashboard-header {
        background: white;
        padding: 40px 0;
        border-bottom: 1px solid var(--border);
        margin-bottom: 40px;
    }
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
    }
    .stat-box {
        padding: 20px;
        text-align: left;
    }
    .stat-box .val { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
    .stat-box .lab { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }

    .item-manage-card {
        display: grid;
        grid-template-columns: 100px 1fr auto;
        gap: 20px;
        align-items: center;
        padding: 15px;
        margin-bottom: 15px;
    }
    .item-img-small {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: var(--radius-md);
    }
    .item-info h4 { margin-bottom: 5px; font-size: 1.1rem; }
    .item-info p { font-size: 0.85rem; color: var(--text-muted); }

    @media (max-width: 768px) {
        .item-manage-card {
            grid-template-columns: 1fr;
            text-align: center;
        }
        .item-img-small { margin: 0 auto; width: 100%; height: 200px; }
        .side-panel { position: static; margin-bottom: 30px; }
    }
  </style>
</head>
<body>

  <nav class="navbar glass">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <a href="dashboard.php" class="logo">
        <img src="logo-128.png?v=2" alt="" class="site-logo-image" width="42" height="42">
        <span>Seller Central</span>
      </a>
      <div style="display: flex; gap: 15px; align-items: center;">
        <span class="badge <?php echo $v_status == 'Approved' ? 'badge-approved' : 'badge-pending'; ?>">
            <?php echo $v_status; ?>
        </span>
        <a href="index.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.8rem;">View Site</a>
        <a href="logout.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.8rem;">Logout</a>
      </div>
    </div>
  </nav>

  <header class="dashboard-header">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;">
            <div>
                <p class="text-muted" style="font-weight: 700; margin-bottom: 5px;">Welcome back,</p>
                <h1><?php echo htmlspecialchars($vendor_name); ?></h1>
            </div>
            <div class="stat-grid">
                <div class="card stat-box">
                    <div class="val"><?php echo $stats['total'] ?: 0; ?></div>
                    <div class="lab">Total Items</div>
                </div>
                <div class="card stat-box">
                    <div class="val" style="color: var(--success);"><?php echo $stats['available'] ?: 0; ?></div>
                    <div class="lab">Available</div>
                </div>
                <div class="card stat-box">
                    <div class="val" style="color: var(--danger);"><?php echo $stats['soldout'] ?: 0; ?></div>
                    <div class="lab">Sold Out</div>
                </div>
            </div>
        </div>
    </div>
  </header>

  <main class="container">
    <div class="grid" style="grid-template-columns: 1fr 2fr; align-items: start;">

        <!-- Add Item Form -->
        <div class="side-panel">
            <div class="card glass animate-fade" style="padding: 30px;" id="add-item">
                <h3 style="margin-bottom: 20px;">Add New Item</h3>
                <form action="save_item.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="food_name" class="form-control" placeholder="e.g. Special Rolex" required>
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 0;">
                        <div class="form-group">
                            <label class="form-label">Price</label>
                            <input type="text" name="price" class="form-control" placeholder="UGX 5,000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="Food">Food</option>
                                <option value="Drinks">Drinks</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Groceries">Groceries</option>
                                <option value="Stationery">Stationery</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief details about the item..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Status</label>
                        <select name="status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Sold Out">Sold Out</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">List Item</button>
                </form>
            </div>
        </div>

        <!-- Manage Items -->
        <div class="manage-panel animate-fade" style="animation-delay: 0.1s;">
            <h3 style="margin-bottom: 20px;">Your Live Menu</h3>
            <?php if ($items->num_rows > 0): ?>
                <?php while ($row = $items->fetch_assoc()): ?>
                    <div class="card item-manage-card">
                        <img src="<?php echo htmlspecialchars($row['image']); ?>" class="item-img-small">
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($row['food_name']); ?></h4>
                            <p><?php echo htmlspecialchars($row['category']); ?> • <strong><?php echo htmlspecialchars($row['price']); ?></strong></p>
                            <span class="badge <?php echo $row['status'] == 'Available' ? 'badge-approved' : 'badge-rejected'; ?>" style="font-size: 0.65rem;">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 10px 15px;">Edit</a>
                            <form action="delete_item.php" method="POST" onsubmit="return confirm('Delete this item?');" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="color: var(--danger); border-color: #fee2e2;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 60px; border-style: dashed;">
                    <p class="text-muted">You haven't listed any items yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
  </main>

  <footer style="margin-top: 80px; padding: 40px 0; background: white; border-top: 1px solid var(--border);">
    <div class="container text-muted" style="text-align: center; font-size: 0.9rem;">
        &copy; <?php echo date('Y'); ?> CampusExpress Seller Dashboard.
    </div>
  </footer>

</body>
</html>

