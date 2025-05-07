<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "ecoharvest";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add quality report
if (isset($_POST['add'])) {
    $bid_input = filter_input(INPUT_POST, 'bid_input', FILTER_SANITIZE_STRING);
    $bid = $bid_input ? "BID$bid_input" : '';
    $product_name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);

    $quality_scores = ['A' => 100, 'B' => 75, 'C' => 50, 'D' => 25];
    $quality_score = $quality_scores[$grade] ?? 0;

    $stmt = $conn->prepare("INSERT INTO quality_analysis (bid, product_name, category, weight, grade, quality_score, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsds", $bid, $product_name, $category, $weight, $grade, $quality_score, $expiry_date);
    
    if ($stmt->execute()) {
        header("Location: qualityanalysis.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Edit quality report
if (isset($_POST['edit'])) {
    $id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);
    $bid_input = filter_input(INPUT_POST, 'bid_input', FILTER_SANITIZE_STRING);
    $bid = $bid_input ? "BID$bid_input" : '';
    $product_name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);

    $quality_scores = ['A' => 100, 'B' => 75, 'C' => 50, 'D' => 25];
    $quality_score = $quality_scores[$grade] ?? 0;

    $stmt = $conn->prepare("UPDATE quality_analysis SET bid=?, product_name=?, category=?, weight=?, grade=?, quality_score=?, expiry_date=? WHERE id=?");
    $stmt->bind_param("sssdsdsi", $bid, $product_name, $category, $weight, $grade, $quality_score, $expiry_date, $id);
    
    if ($stmt->execute()) {
        header("Location: qualityanalysis.php");
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
}

// Delete quality report
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM quality_analysis WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: qualityanalysis.php");
    exit();
}

// Fetch products for dropdown, excluding those already in quality_analysis
$products_result = $conn->query("SELECT DISTINCT g.product, g.category, g.weight, g.grade 
                                FROM grading g 
                                LEFT JOIN quality_analysis qa ON g.product = qa.product_name 
                                WHERE qa.product_name IS NULL 
                                ORDER BY g.product");

// Fetch distinct categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM quality_analysis ORDER BY category");

// Fetch quality analysis records with filters and search
$grade_filter = filter_input(INPUT_GET, 'grade', FILTER_SANITIZE_STRING) ?? 'all';
$category_filter = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING) ?? 'all';
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';

$where = [];
$params = [];
if ($grade_filter !== 'all') {
    $where[] = "grade = ?";
    $params[] = $grade_filter;
}
if ($category_filter !== 'all') {
    $where[] = "category = ?";
    $params[] = $category_filter;
}
if ($search) {
    $where[] = "(bid LIKE ? OR product_name LIKE ? OR category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql = "SELECT * FROM quality_analysis";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY expiry_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();
$stmt->close();

// Fetch data for chart (no filters applied)
$chart_result = $conn->query("SELECT product, grade, category, COUNT(*) as count 
                             FROM grading 
                             GROUP BY product, grade, category");
$chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Eco Harvest - Quality Analysis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
        :root {
            --primary-color: #2e8b57;
            --primary-dark: #1e6b47;
            --primary-light: #3da76d;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #fd7e14;
            --info-color: #17a2b8;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--primary-dark);
        }

        body {
            background-image: url('image/BG2.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            font-family: 'Poppins', sans-serif;
            padding-top: 90px;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1030;
            padding: 15px 50px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        header.scrolled {
            padding: 10px 50px;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }

        .navbar-brand img {
            height: 60px;
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: rotate(-5deg) scale(1.05);
        }

        .navbar-brand span {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .navbar-nav {
            gap: 5px;
        }

        .navbar-nav .nav-link {
            color: white;
            font-weight: 500;
            border-radius: 5px;
            padding: 8px 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 0 5px;
            font-size: 0.95rem;
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: white;
            transition: width 0.3s ease;
        }

        .navbar-nav .nav-link:hover::after,
        .navbar-nav .nav-link.active::after {
            width: 100%;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .btn {
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px 20px;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            border: none;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
        }

        .btn-success:active {
            transform: translateY(1px);
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        .card-body {
            padding: 25px;
        }

        .card-title {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 3px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 15px;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            border-bottom: none;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: none;
            padding: 20px;
            background: rgba(249, 249, 249, 0.9);
            border-radius: 0 0 15px 15px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }

        @media (max-width: 992px) {
            header {
                padding: 15px 20px;
            }
            
            .navbar-brand img {
                height: 50px;
            }
            
            .navbar-brand span {
                font-size: 1.3rem;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .charts-container {
                padding: 0 10px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .page-title {
            font-weight: 700;
            color: var(--primary-dark);
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }
        .chart-container {
      max-width: 800px;
      margin: 40px auto;
      padding: 20px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .filter-container {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .card-transparent {
      background-color: rgba(255, 255, 255, 0.95);
      border: none;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 30px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      border-left: 5px solid var(--primary-color);
    }

    .modal-content {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      border-bottom: none;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
      color: white;
      padding: 25px 30px;
    }

    .modal-title {
      font-size: 1.6rem;
      font-weight: 700;
    }

    .btn-close {
      filter: brightness(0) invert(1);
    }

    .modal-body {
      padding: 30px;
    }

    .modal-footer {
      border-top: none;
      padding: 20px 30px;
      background: rgba(249, 249, 249, 0.9);
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-control, .form-select {
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.7);
    }

    .form-label {
      position: absolute;
      top: -10px;
      left: 15px;
      background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(255,255,255,0.8));
      padding: 0 8px;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--primary-dark);
    }

    .btn-premium-success {
      background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
      border: none;
      color: white;
    }

    .btn-premium-warning {
      background: linear-gradient(135deg, var(--warning-color), #e67300);
      border: none;
      color: white;
    }

    .btn-premium-danger {
      background: linear-gradient(135deg, #ff5e62, #d32f2f);
      border: none;
    }

    .btn-premium-secondary {
      background: linear-gradient(135deg, #e0e0e0, #c0c0c0);
      border: none;
      color: var(--dark-color);
    }

    .table-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .records-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-container {
      position: relative;
      max-width: 300px;
    }

    .search-container .form-control {
      padding-left: 40px;
      border-radius: 50px;
      border: 1px solid #ddd;
    }

    .search-container i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
    }
  </style>
</head>
<body>
<!-- Navbar -->
<header class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
  <div class="d-flex justify-content-between align-items-center">
    <div class="navbar-brand d-flex align-items-center text-white">
      <img src="image/Logo.PNG" alt="Eco Harvest Logo" style="height: 60px; margin-right: 10px;" onerror="this.src='https://via.placeholder.com/60';">
      <span>Eco Harvest</span>
    </div>  
    <div class="collapse navbar-collapse" id="navbarNav">
      <nav>
        <ul class="navbar-nav flex-row">
          <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a href="qualityreport.php" class="nav-link"><i class="fas fa-clipboard-check me-1"></i> Quality Report</a></li>
          <li class="nav-item"><a href="grading.php" class="nav-link"><i class="fas fa-seedling me-1"></i> Graded Produced</a></li>
          <li class="nav-item"><a href="qualityanalysis.php" class="nav-link active"><i class="fas fa-chart-line me-1"></i> Quality Analysis</a></li>
          <li class="nav-item"><a href="packaging.php" class="nav-link"><i class="fas fa-box-open me-1"></i> Packaging</a></li>
          <li class="nav-item"><a href="transport.php" class="nav-link"><i class="fas fa-shipping-fast me-1"></i> Transport</a></li>
          <li class="nav-item"><a href="supplychain.php" class="nav-link"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
          <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
  </div>
</header>

<div class="container py-5">
  <h2 class="mb-4 text-center">Quality Analysis</h2>

  <!-- Filters and Search -->
  <div class="filter-container">
    <div class="form-group">
      <label class="form-label">Grade</label>
      <select class="form-select" id="gradeFilter" onchange="applyFilters()">
        <option value="all" <?= $grade_filter == 'all' ? 'selected' : '' ?>>All Grades</option>
        <option value="A" <?= $grade_filter == 'A' ? 'selected' : '' ?>>A</option>
        <option value="B" <?= $grade_filter == 'B' ? 'selected' : '' ?>>B</option>
        <option value="C" <?= $grade_filter == 'C' ? 'selected' : '' ?>>C</option>
        <option value="D" <?= $grade_filter == 'D' ? 'selected' : '' ?>>D</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Category</label>
      <select class="form-select" id="categoryFilter" onchange="applyFilters()">
        <option value="all" <?= $category_filter == 'all' ? 'selected' : '' ?>>All Categories</option>
        <?php while ($cat = $categories_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category_filter == $cat['category'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['category']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-group search-container">
      <label class="form-label">Search</label>
      <form method="get" class="d-flex">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="form-control" placeholder="Search by Batch ID, Product, or Category" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-premium-success ms-2">Search</button>
      </form>
    </div>
  </div>

  <!-- Records Table -->
  <div class="table-container">
    <div class="card-transparent">
      <div class="records-header">
        <h4 class="records-title">Quality Analysis Records</h4>
        <button class="btn btn-success btn-premium btn-premium-success" data-bs-toggle="modal" data-bs-target="#addModal">Add Quality Report</button>
      </div>
      <table class="table table-bordered">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Batch ID</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Weight (kg)</th>
            <th>Grade</th>
            <th>Quality Score (%)</th>
            <th>Expiry Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = $records_result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['bid']) ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['weight']) ?></td>
            <td><?= htmlspecialchars($row['grade']) ?></td>
            <td><?= htmlspecialchars($row['quality_score']) ?></td>
            <td><?= htmlspecialchars($row['expiry_date']) ?></td>
            <td>
              <button class="btn btn-warning btn-sm btn-premium btn-premium-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
              <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-premium btn-premium-danger" onclick="return confirm('Delete this record?')">Delete</a>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <div class="modal-header bg-warning">
                    <h5 class="modal-title">Edit Quality Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                    <div class="form-group">
                      <label class="form-label">Batch ID</label>
                      <input type="text" name="bid_input" class="form-control" value="<?= htmlspecialchars(str_replace('BID', '', $row['bid'])) ?>">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Product Name</label>
                      <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($row['product_name']) ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Category</label>
                      <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($row['category']) ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Weight (kg)</label>
                      <input type="number" step="0.01" name="weight" class="form-control" value="<?= htmlspecialchars($row['weight']) ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Grade</label>
                      <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($row['grade']) ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Expiry Date</label>
                      <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($row['expiry_date']) ?>" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" name="edit" class="btn btn-premium btn-premium-warning">Update</button>
                    <button type="button" class="btn btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Chart -->
  <div class="chart-container">
    <canvas id="qualityChart"></canvas>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add Quality Report</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Batch ID</label>
            <input type="text" name="bid_input" id="bidInput" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Product Name</label>
            <select name="product_name" id="productSelect" class="form-select" onchange="autofillFields()" required>
              <option value="">Select Product</option>
              <?php while ($product = $products_result->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($product['product']) ?>" 
                        data-category="<?= htmlspecialchars($product['category']) ?>" 
                        data-weight="<?= htmlspecialchars($product['weight']) ?>"
                        data-grade="<?= htmlspecialchars($product['grade']) ?>">
                  <?= htmlspecialchars($product['product']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" id="categoryInput" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Weight (kg)</label>
            <input type="number" step="0.01" name="weight" id="weightInput" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Grade</label>
            <input type="text" name="grade" id="gradeInput" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-premium btn-premium-success">Add Report</button>
          <button type="button" class="btn btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const chartData = <?= json_encode($chart_data) ?>;

function applyFilters() {
  const grade = document.getElementById('gradeFilter').value;
  const category = document.getElementById('categoryFilter').value;
  const search = new URLSearchParams(window.location.search).get('search') || '';
  window.location.href = `?grade=${grade}&category=${category}${search ? '&search=' + encodeURIComponent(search) : ''}`;
}

function autofillFields() {
  const select = document.getElementById('productSelect');
  const selectedOption = select.options[select.selectedIndex];
  
  document.getElementById('categoryInput').value = selectedOption.getAttribute('data-category') || '';
  document.getElementById('weightInput').value = selectedOption.getAttribute('data-weight') || '';
  document.getElementById('gradeInput').value = selectedOption.getAttribute('data-grade') || '';
}

// Chart.js configuration
const ctx = document.getElementById('qualityChart').getContext('2d');
const labels = [...new Set(chartData.map(item => item.product))];
const grades = ['A', 'B', 'C', 'D'];

const datasets = grades.map(grade => ({
  label: `Grade ${grade}`,
  data: labels.map(label => {
    const item = chartData.find(d => d.product === label && d.grade === grade);
    return item ? item.count : 0;
  }),
  backgroundColor: {
    'A': 'rgba(40, 167, 69, 0.8)',
    'B': 'rgba(255, 193, 7, 0.8)',
    'C': 'rgba(255, 159, 64, 0.8)',
    'D': 'rgba(220, 53, 69, 0.8)'
  }[grade]
}));

new Chart(ctx, {
  type: 'bar',
  data: {
    labels: labels,
    datasets: datasets
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        title: { display: true, text: 'Count' }
      },
      x: {
        title: { display: true, text: 'Products' }
      }
    },
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Quality Analysis by Grade' }
    }
  }
});
</script>
</body>
</html>

<?php $conn->close(); ?>