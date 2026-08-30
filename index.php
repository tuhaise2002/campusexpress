<?php
session_start();
include "db.php";

$success = "";
$error = "";

// Handle vendor form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["vendor_name"])) {
    $vendor_name = trim($_POST["vendor_name"]);
    $phone = trim($_POST["phone"]);
    $location = trim($_POST["location"]);
    $menu = trim($_POST["menu"]);

    // Handle file uploads
    $photos = [];
    if (!empty($_FILES["attachment"]["name"][0])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES["attachment"]["tmp_name"] as $key => $tmp_name) {
            if ($_FILES["attachment"]["error"][$key] === UPLOAD_ERR_OK) {
                $filename = uniqid() . "_" . basename($_FILES["attachment"]["name"][$key]);
                $filepath = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $filepath)) {
                    $photos[] = $filepath;
                }
            }
        }
    }

    // Insert vendor submission
    $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, phone, location, menu, photos) VALUES (?, ?, ?, ?, ?)");
    $photos_json = json_encode($photos);
    $stmt->bind_param("sssss", $vendor_name, $phone, $location, $menu, $photos_json);

    if ($stmt->execute()) {
        $success = "Thank you! Your food items have been submitted for review. We'll contact you soon.";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION["user_id"]);
$user_email = $_SESSION["user_email"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campus Express Food – Vendors to Students</title>
   <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
    .user-nav {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .user-nav a {
      text-decoration: none;
      color: var(--dark);
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 8px;
      transition: background 0.2s;
    }
    .user-nav a:hover {
      background: rgba(0,0,0,0.05);
    }
    .user-nav .login-btn {
      background: var(--primary);
      color: white;
    }
    .user-nav .login-btn:hover {
      background: var(--primary-dark);
    }
    .user-nav .logout-btn {
      color: #dc2626;
    }
    .alert {
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-weight: 500;
    }
    .alert-success {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }
    .alert-error {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }
  </style>
</head>

<body>

  <header>
    <div class="container">
      <nav class="nav">
        <a href="index.php" class="brand">
          <img src="1logo.png" alt="Campus Express Food Logo" class="brand-logo">
          <span class="brand-text">Campus Express Food</span>
        </a>

        <div class="header-search">
          <input id="headerSearch" type="text" placeholder="Search food…">
        </div>

        <div class="user-nav">
          <?php if ($is_logged_in): ?>
            <span>Welcome, <?php echo htmlspecialchars($user_email); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
          <?php else: ?>
            <a href="email-login.php" class="login-btn">Login</a>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Order food direct from campus vendors</h1>
      <p>They cook fresh and deliver themselves. Browse listings — contact via call/WhatsApp.</p>

      <div class="hero-cta">
        <a class="hero-btn" href="#listings">Browse Listings</a>
        <a class="hero-btn-outline" href="#vendor">Become a Vendor</a>
      </div>
    </div>
  </section>

  <section class="section" id="listings">
    <div class="container">
      <h2>Current Campus Listings</h2>

      <div class="toolbar">
        <input id="searchInput" class="search-input" type="text" placeholder="Search food… (pizza, katogo, soda)">
        <div class="filters" id="filterBar">
          <button class="filter-btn active" data-filter="all">All</button>
          <button class="filter-btn" data-filter="fast">Fast Food</button>
          <button class="filter-btn" data-filter="local">Local</button>
          <button class="filter-btn" data-filter="drinks">Drinks</button>
          <button class="filter-btn" data-filter="snacks">Snacks</button>
        </div>
      </div>

      <div class="listings">
        <div class="listing-card" data-category="fast">
          <div class="listing-img">
            <img src="https://images.pexels.com/photos/825661/pexels-photo-825661.jpeg" alt="Pizza on wooden board">
          </div>
          <div class="listing-info">
            <div class="listing-title">Pizza (slice / full)</div>
            <div class="listing-price">UGX 15,000 – 45,000</div>
            <div class="listing-vendor">Vendor: Pizza Spot – Campus vicinity</div>
            <div class="buttons">
              <a class="btn btn-call" href="tel:+256700000000">Call</a>
              <a class="btn btn-wa" href="https://wa.me/256700000000" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

        <div class="listing-card" data-category="fast">
          <div class="listing-img">
            <img src="https://images.pexels.com/photos/2456435/pexels-photo-2456435.jpeg" alt="Indomie with egg and veggies">
          </div>
          <div class="listing-info">
            <div class="listing-title">Indomie + Egg + Veggies (single / double)</div>
            <div class="listing-price">UGX 6,000 – 12,000</div>
            <div class="listing-vendor">Vendor: Indomie Mama – Makerere</div>
            <div class="buttons">
              <a class="btn btn-call" href="tel:+256700000000">Call</a>
              <a class="btn btn-wa" href="https://wa.me/256700000000" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

        <div class="listing-card" data-category="snacks">
          <div class="listing-img">
            <img src="https://images.pexels.com/photos/1583884/pexels-photo-1583884.jpeg" alt="Fried chips">
          </div>
          <div class="listing-info">
            <div class="listing-title">Chips (Biggy / Noodles mix packs)</div>
            <div class="listing-price">UGX 20,000 – 30,000 (bulk)</div>
            <div class="listing-vendor">Vendor: Snacks Corner – Kikumi / Bwaise</div>
            <div class="buttons">
              <a class="btn btn-call" href="tel:+256700000000">Call</a>
              <a class="btn btn-wa" href="https://wa.me/256700000000" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

        <div class="listing-card" data-category="drinks">
          <div class="listing-img">
            <img src="https://images.pexels.com/photos/1384039/pexels-photo-1384039.jpeg" alt="Soda bottles mix">
          </div>
          <div class="listing-info">
            <div class="listing-title">Soda Mix (Coke, Fanta, Sprite 500ml)</div>
            <div class="listing-price">UGX 15,000 – 25,000 (6-pack)</div>
            <div class="listing-vendor">Vendor: Cold Drinks – Campus vicinity</div>
            <div class="buttons">
              <a class="btn btn-call" href="tel:+256700000000">Call</a>
              <a class="btn btn-wa" href="https://wa.me/256700000000" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

        <div class="listing-card" data-category="local">
          <div class="listing-img">
            <img src="https://images.pexels.com/photos/18411466/pexels-photo-18411466.jpeg" alt="Katogo meal">
          </div>
          <div class="listing-info">
            <div class="listing-title">Katogo (beans + matooke / posho)</div>
            <div class="listing-price">UGX 8,000 – 12,000</div>
            <div class="listing-vendor">Vendor: Katogo Spot – Makerere / Kyambogo</div>
            <div class="buttons">
              <a class="btn btn-call" href="tel:+256700000000">Call</a>
              <a class="btn btn-wa" href="https://wa.me/256700000000" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <section class="vendor-section" id="vendor">
    <div class="container">
      <h2>Are you a campus food seller?</h2>
      <p>Upload your own food items & prices — add photos too. We'll review quickly and list approved ones for students to see.</p>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
          <div>
            <label for="vendor-name">Your Name / Shop Name *</label>
            <input type="text" id="vendor-name" name="vendor_name" required>
          </div>
          <div>
            <label for="phone">WhatsApp / Phone Number *</label>
            <input type="tel" id="phone" name="phone" required placeholder="+256 7xx xxx xxx">
          </div>
        </div>

        <div style="margin-top:1rem;">
          <label for="location">Campus Area / Location *</label>
          <input type="text" id="location" name="location" required placeholder="e.g. Wandegeya, Kikoni, Makerere North">
        </div>

        <div style="margin-top:1rem;">
          <label for="menu">Food Items & Prices *</label>
          <textarea id="menu" name="menu" required
            placeholder="Example:
• Rolex (eggs + veggies) - UGX 5,000 - 8,000
• Indomie + egg - UGX 6,000
• Chips small pack - UGX 2,000
• Soda 500ml - UGX 3,000"></textarea>
        </div>

        <div style="margin-top:1rem;">
          <label for="photos">Upload Photos of Your Food / Menu (optional – up to 3–4 best ones)</label>
          <input type="file" id="photos" name="attachment[]" accept="image/*" multiple>
          <small>jpg / png — max ~5MB each recommended</small>
        </div>

        <button class="submit-btn" type="submit">Upload My Food Items</button>
      </form>

      <p style="margin-top:1.5rem;font-size:.95rem;opacity:.9;">
        Submissions go straight to us — we'll review & add good ones within 1–2 days. Thanks!
      </p>
    </div>
  </section>

  <footer>
    <div class="container">
      &copy; 2026 Campus Express Food – Direct from campus sellers to students in Kampala
    </div>
  </footer>

  <button id="backToTop" class="back-to-top" aria-label="Back to top">↑</button>
  <div id="toast" class="toast">Copied!</div>

  <script>
    window.addEventListener("scroll", () => {
      const header = document.querySelector("header");
      if (!header) return;
      if (window.scrollY > 30) header.classList.add("shrink");
      else header.classList.remove("shrink");
    });

    const backToTop = document.getElementById("backToTop");
    window.addEventListener("scroll", () => {
      if (!backToTop) return;
      backToTop.classList.toggle("show", window.scrollY > 400);
    });
    backToTop?.addEventListener("click", () => window.scrollTo({top:0, behavior:"smooth"}));

    const cards = Array.from(document.querySelectorAll(".listing-card"));
    cards.forEach(el => el.classList.add("reveal"));
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) e.target.classList.add("visible");
      });
    }, { threshold: 0.12 });
    cards.forEach(el => io.observe(el));

    const searchInput = document.getElementById("searchInput");
    const headerSearch = document.getElementById("headerSearch");
    const filterBar = document.getElementById("filterBar");

    function applyFilters(){
      const q = ((searchInput?.value || headerSearch?.value || "")).trim().toLowerCase();
      const activeBtn = filterBar?.querySelector(".filter-btn.active");
      const cat = activeBtn?.dataset.filter || "all";

      cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        const cardCat = card.dataset.category || "all";
        const matchSearch = !q || text.includes(q);
        const matchCat = (cat === "all") || (cardCat === cat);
        card.style.display = (matchSearch && matchCat) ? "" : "none";
      });
    }

    searchInput?.addEventListener("input", applyFilters);
    headerSearch?.addEventListener("input", () => {
      if (searchInput) searchInput.value = headerSearch.value;
      applyFilters();
    });

    filterBar?.addEventListener("click", (e) => {
      const btn = e.target.closest(".filter-btn");
      if (!btn) return;
      filterBar.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      applyFilters();
    });

    applyFilters();
  </script>

</body>
</html>
