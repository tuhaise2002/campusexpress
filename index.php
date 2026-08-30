<?php
include "db.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$available = isset($_GET['available']) ? trim($_GET['available']) : '';

$sql = "SELECT menu_items.*, vendors.vendor_name, vendors.phone
        FROM menu_items
        JOIN vendors ON menu_items.vendor_id = vendors.id
        WHERE vendors.status = 'Approved'"; // Only show items from approved vendors

$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (menu_items.food_name LIKE ? OR menu_items.description LIKE ? OR vendors.vendor_name LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($category !== "") {
    $sql .= " AND menu_items.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($available === "1") {
    $sql .= " AND menu_items.status = 'Available'";
}

$sql .= " ORDER BY menu_items.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$count = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png?v=2">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusExpress | Student Marketplace</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .hero {
        padding: 100px 0;
        background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85)),
                    url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1600&q=80');
        background-size: cover;
        background-position: center;
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
        color: white;
        text-align: center;
        margin-bottom: 0;
    }
    .hero h1 { font-size: clamp(2.5rem, 8vw, 4rem); line-height: 1.1; margin-bottom: 20px; }
    .hero p { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto 30px; }

    .filter-bar {
        margin-top: -60px;
        position: relative;
        z-index: 10;
    }
    .filter-card {
        padding: 30px;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 15px;
        align-items: end;
    }

    .item-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }
    .item-image {
        height: 220px;
        width: 100%;
        object-fit: cover;
    }
    .item-content {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .item-vendor {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary);
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .item-price {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
    }
    .item-actions {
        margin-top: 20px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .status-badge-float {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(4px);
    }

    .marketplace-topbar { background: #14213d; color: white; border-top: 3px solid #ff6b0b; }
    .marketplace-nav-inner { min-height: 66px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
    .marketplace-brand { display: inline-flex; align-items: center; gap: 11px; color: white; font-size: 1.35rem; font-weight: 800; }
    .marketplace-brand .site-logo-image { width: 46px; height: 46px; }
    .marketplace-account-actions { display: flex; align-items: center; gap: 22px; font-size: .86rem; font-weight: 700; white-space: nowrap; }
    .customer-entry-links { display: flex; align-items: center; gap: 9px; }
    .customer-entry-links a, .marketplace-text-link { color: white; }
    .customer-entry-links a:hover, .marketplace-text-link:hover { color: #ffb27f; }
    .marketplace-separator { color: #64748b; }
    .marketplace-sell-button { min-width: 120px; padding: 13px 24px; border-radius: 9px; background: #ff6b0b; color: white; text-align: center; box-shadow: 0 6px 16px rgba(255,107,11,.25); }
    .marketplace-sell-button:hover { background: #e85d04; transform: translateY(-1px); }
    .marketplace-identity { color: #e2e8f0; font-weight: 600; }

    .site-footer { background: #0f172a; color: white; margin-top: 30px; }
    .footer-main { display: grid; grid-template-columns: minmax(260px, 1.8fr) 1fr 1fr; gap: 60px; padding: 58px 0 44px; }
    .footer-logo { color: white; margin-bottom: 16px; }
    .footer-brand p { max-width: 410px; color: #94a3b8; font-size: .9rem; line-height: 1.7; }
    .footer-links { display: flex; flex-direction: column; align-items: flex-start; gap: 11px; }
    .footer-links h3 { color: white; font-size: .86rem; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 5px; }
    .footer-links a { color: #cbd5e1; font-size: .88rem; }
    .footer-links a:hover, .footer-links a:focus { color: white; }
    .footer-bottom { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 20px 0; border-top: 1px solid #273449; color: #94a3b8; font-size: .78rem; }
    .admin-access { color: #64748b; font-weight: 600; }
    .admin-access:hover, .admin-access:focus { color: #cbd5e1; }

    @media (max-width: 992px) {
        .filter-card { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .marketplace-nav-inner { min-height: 60px; gap: 8px; }
        .marketplace-brand span { display: none; }
        .marketplace-brand .site-logo-image { width: 40px; height: 40px; }
        .marketplace-account-actions { gap: 8px; font-size: .72rem; }
        .customer-entry-links { gap: 5px; }
        .marketplace-sell-button { min-width: auto; padding: 10px 12px; border-radius: 7px; }
        .marketplace-identity { display: none; }
        .footer-main { grid-template-columns: 1fr 1fr; gap: 34px 24px; padding: 42px 0 32px; }
        .footer-brand { grid-column: 1 / -1; min-width: 0; }
        .footer-brand p { max-width: 100%; }
        .footer-logo { width: auto; }
        .footer-bottom { align-items: flex-start; }
        .filter-card { grid-template-columns: 1fr; margin-top: -30px; }
        .hero { padding: 80px 0 100px; }
    }  </style>
</head>
<body>

  <nav class="marketplace-topbar" aria-label="Main navigation">
    <div class="container marketplace-nav-inner">
      <a href="index.php" class="marketplace-brand" aria-label="CampusExpress home">
        <img src="logo-128.png?v=2" alt="" class="site-logo-image" width="46" height="46">
        <span>CampusExpress</span>
      </a>

      <div class="marketplace-account-actions">
        <?php if(isset($_SESSION['user_id'])): ?>
          <span class="marketplace-identity"><?php echo e($_SESSION['user_email']); ?></span>
          <a href="logout.php" class="marketplace-text-link">Logout</a>
        <?php elseif(isset($_SESSION['vendor_id'])): ?>
          <a href="dashboard.php" class="marketplace-text-link">Vendor Dashboard</a>
          <span class="marketplace-separator">|</span>
          <a href="logout.php" class="marketplace-text-link">Logout</a>
        <?php else: ?>
          <div class="customer-entry-links">
            <a href="user_login.php">Sign in</a>
            <span class="marketplace-separator">|</span>
            <a href="user_login.php?mode=register">Registration</a>
          </div>
          <a href="register.php" class="marketplace-sell-button">SELL</a>
        <?php endif; ?>
      </div>
    </div>
  </nav><header class="hero">
    <div class="container animate-fade">
        <span class="badge" style="background: var(--primary); color: white; margin-bottom: 20px;">University Marketplace</span>
        <h1>Campus Favorites, <br>Delivered Daily.</h1>
        <p>The smartest way to find the best campus vendors. Fresh meals, stationery, and essentials at your fingertips.</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="#marketplace" class="btn btn-primary" style="padding: 15px 30px;">Browse Market</a>
            <a href="register.php" class="btn btn-outline" style="padding: 15px 30px; border-color: white; color: white;">Become a Vendor</a>
        </div>
    </div>
  </header>

  <section class="container filter-bar" id="marketplace">
    <form class="card filter-card glass animate-fade" style="animation-delay: 0.2s;" method="GET">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Search Items</label>
            <input type="text" name="search" class="form-control" placeholder="Rolex, Rice, Stationery..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <option value="Food" <?php echo $category == 'Food' ? 'selected' : ''; ?>>Food</option>
                <option value="Drinks" <?php echo $category == 'Drinks' ? 'selected' : ''; ?>>Drinks</option>
                <option value="Snacks" <?php echo $category == 'Snacks' ? 'selected' : ''; ?>>Snacks</option>
                <option value="Groceries" <?php echo $category == 'Groceries' ? 'selected' : ''; ?>>Groceries</option>
                <option value="Stationery" <?php echo $category == 'Stationery' ? 'selected' : ''; ?>>Stationery</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px; padding-bottom: 12px;">
            <input type="checkbox" name="available" value="1" id="avail" <?php echo $available == '1' ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
            <label for="avail" style="font-weight: 700; font-size: 0.85rem; cursor: pointer; color: var(--text-muted);">Available Only</label>
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 14px 25px;">Search</button>
    </form>
  </section>

  <main class="container" style="padding: 60px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2>Browse Menu</h2>
        <p class="text-muted" style="font-weight: 600;">Found <?php echo $count; ?> results</p>
    </div>

    <div class="grid">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="card item-card animate-fade" id="item-<?php echo (int)$row['id']; ?>">
            <div style="position: relative;">
                <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['food_name']); ?>" class="item-image">
                <span class="badge status-badge-float <?php echo $row['status'] == 'Available' ? 'badge-approved' : 'badge-rejected'; ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                </span>
            </div>
            <div class="item-content">
                <span class="item-vendor"><?php echo htmlspecialchars($row['vendor_name']); ?></span>
                <h3 style="margin-bottom: 10px; font-size: 1.4rem;"><?php echo htmlspecialchars($row['food_name']); ?></h3>
                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 15px; flex-grow: 1;"><?php echo htmlspecialchars($row['description']); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.7rem;"><?php echo htmlspecialchars($row['category']); ?></span>
                    <span class="item-price"><?php echo htmlspecialchars($row['price']); ?></span>
                </div>

                <div class="item-actions<?php echo !isset($_SESSION['user_id']) ? ' login-required' : ''; ?>">
                    <?php if ($row['status'] !== 'Available'): ?>
                        <span class="btn btn-secondary" style="grid-column:1/-1; cursor:not-allowed; opacity:.7;">Currently unavailable</span>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <?php
                            $whatsappMsg = urlencode("Hello " . $row['vendor_name'] . ", I'm interested in ordering " . $row['food_name'] . " (" . $row['price'] . ") from CampusExpress.");
                            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $row['phone']) . "?text=" . $whatsappMsg;
                        ?>
                        <a href="<?php echo e($waLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background:#25D366; box-shadow:none;">Order on WhatsApp</a>
                        <a href="tel:<?php echo e($row['phone']); ?>" class="btn btn-secondary">Call vendor</a>
                    <?php else: ?>
                        <a href="user_login.php?return=<?php echo rawurlencode('index.php#item-' . (int)$row['id']); ?>" class="btn btn-primary" style="grid-column:1/-1;">Sign in to order</a>
                    <?php endif; ?>
                </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 100px 0;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🔎</div>
            <h3>No items found</h3>
            <p class="text-muted">Try adjusting your filters or search terms.</p>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 20px;">Reset Filters</a>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-main">
      <div class="footer-brand">
        <a href="index.php" class="logo footer-logo">
          <img src="logo-128.png?v=2" alt="" class="site-logo-image" width="42" height="42">
          <span>CampusExpress</span>
        </a>
        <p>Your trusted campus marketplace for meals, essentials, and local student businesses.</p>
      </div>
      <nav class="footer-links" aria-label="Marketplace links">
        <h3>Marketplace</h3>
        <a href="#marketplace">Browse items</a>
        <a href="user_login.php">Student sign in</a>
      </nav>
      <nav class="footer-links" aria-label="Vendor links">
        <h3>For Vendors</h3>
        <a href="login.php">Vendor sign in</a>
        <a href="register.php">Register your business</a>
      </nav>
    </div>
    <div class="container footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> CampusExpress Marketplace. All rights reserved.</p>
      <a href="admin_login.php" class="admin-access">Admin access</a>
    </div>
  </footer>

</body>
</html>