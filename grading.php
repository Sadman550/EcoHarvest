<?php
$host = "localhost";
$user = "root";
$password = ""; // Default for XAMPP
$database = "ecoharvest";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add product
if (isset($_POST['add'])) {
    $grade = $_POST['grade'];
    $product = $_POST['product'];
    $weight = $_POST['weight'];
    $category = $_POST['category'];

    $conn->query("INSERT INTO grading (grade, product, weight, category) VALUES ('$grade', '$product', '$weight', '$category')");
    header("Location: grading.php");
    exit();
}

// Edit/Update product
if (isset($_POST['edit'])) {
    $id = $_POST['edit_id'];
    $grade = $_POST['grade'];
    $product = $_POST['product'];
    $weight = $_POST['weight'];
    $category = $_POST['category'];

    $sql = "UPDATE grading SET grade='$grade', product='$product', weight='$weight', category='$category' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: grading.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM grading WHERE id=$id");
    header("Location: grading.php");
    exit();
}

// Fetch records
$result = $conn->query("SELECT * FROM grading ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Eco Harvest - Graded Products</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

/* Consistent heading colors */
h1, h2, h3, h4, h5, h6 {
    color: var(--primary-dark);
}

body {
  background-image: url('image/BG2.png'); /* Added ./ to ensure correct relative path */
  background-size: cover;
  background-repeat: no-repeat;
  background-attachment: fixed;
  background-position: center;
  font-family: 'Poppins', sans-serif;
  }

header {
    background-color: var(--primary-color);
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
    background-color: var(--primary-dark);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.navbar {
  padding-top: 0.3rem;
  padding-bottom: 0.3rem;
}
.navbar-brand {
  font-size: 1.1rem;
}

.navbar {
  padding-top: 0.3rem;
  padding-bottom: 0.3rem;
}
.navbar-brand {
  font-size: 1.1rem;
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

.btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #e67300);
    border: none;
    color: white;
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e67300, #d15e00);
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(255, 193, 7, 0.4);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff5e62, #d32f2f);
    border: none;
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #d32f2f, #b71c1c);
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
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

.card-transparent:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 15px 20px;
    font-weight: 600;
    color: var(--primary-dark);
}

.summary-card {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}

.summary-item {
    flex: 1;
    min-width: 150px;
    text-align: center;
}

.summary-item h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 8px;
}

.summary-item p {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
}

table tbody tr:focus {
  outline: none;
  box-shadow: none;
}

table tbody tr:hover {
  transform: scale(1.005);       /* Slight zoom effect */
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  background-color: rgba(8, 0, 0, 0.31);
}

.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 15px;
}

.chart-card {
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 420px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.chart-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    border-radius: 16px 16px 0 0;
}

.chart-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 14px 36px rgba(0,0,0,0.15);
}

.chart-card h3 {
    color: var(--primary-dark) !important;
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 20px;
    text-align: left;
    position: relative;
    padding-left: 40px;
    display: flex;
    align-items: center;
}

.chart-card h3 i {
    font-size: 1.6rem;
    color: var(--primary-color);
    margin-right: 10px;
    transition: transform 0.3s ease;
}

.chart-card:hover h3 i {
    transform: scale(1.2);
}

.chart-card h3::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 40px;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    border-radius: 3px;
}

.chart-card canvas {
    flex-grow: 1;
    max-height: 320px;
    width: 100% !important;
    transition: opacity 0.3s ease;
}

.chart-card:hover canvas {
    opacity: 0.95;
}

.modal-content {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
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
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding: 20px;
    background-color: #f9f9f9;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
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

.notification {
    position: fixed;
    top: 100px;
    right: 30px;
    z-index: 1100;
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    transform: translateX(100%);
    border-radius: 8px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    border: none;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification i {
    font-size: 1.2rem;
    margin-right: 10px;
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
    z-index: 2;
}

.records-header {
    background-color: rgba(46, 139, 87, 0.05);
    padding: 10px;
    border-radius: 8px;
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
}

@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }
    
    .table-card {
        padding: 20px;
    }
    
    .charts-container {
        grid-template-columns: 1fr;
        padding: 0 10px;
    }
    
    .chart-card {
        height: 380px;
    }
    
    .chart-card h3 {
        font-size: 1.2rem;
        padding-left: unset;
    }
    
    .chart-card h3::after {
        left: 35px;
    }
    
    .chart-card canvas {
        max-height: 280px;
    }
    
    .summary-card {
        flex-direction: column;
        align-items: center;
    }
    
    .summary-item {
        min-width: 100%;
    }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 1.5s infinite;
}

.animate__animated {
    animation-duration: 0.5s;
}

@media (prefers-reduced-motion: reduce) {
    .animate__animated {
        animation: none;
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

.action-buttons .btn {
    padding: 8px 12px;
    font-size: 0.85rem;
    min-width: 80px;
}

.badge {
    padding: 6px 10px;
    font-weight: 500;
    border-radius: 50px;
    font-size: 0.8rem;
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

.page-subtitle {
    font-weight: 700;
    color: var(--primary-dark);
    font-size: 1.1rem;
}

.records-title {
    color: var(--primary-dark);
    font-weight: 700;
    margin-bottom: 0;
}

.records-count {
    color: var(--primary-dark);
    font-weight: 700;
    font-size: 0.9rem;
}
</style>

</head>
<body>
<!-- Navbar -->
<!-- Fixed top navbar -->
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
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-clipboard-check me-1"></i> Quality Report</a></li>
          <li class="nav-item"><a href="grading.php" class="nav-link active"><i class="fas fa-seedling me-1"></i> Graded Produced</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-line me-1"></i> Quality Analysis</a></li>
          <li class="nav-item"><a href="packaging.php" class="nav-link"><i class="fas fa-box-open me-1"></i> Packaging</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-shipping-fast me-1"></i> Transportation</a></li>
          <li class="nav-item"><a href="home.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
  </div>
</header>

<!-- Adjust body padding so content doesn't hide behind navbar -->
<body style="padding-top: 70px;">





<div class="container py-5 d-flex justify-content-center">
    <div class="w-100" style="max-width: 1200px;">
        <h2 class="mb-4 text-center">Graded Products</h2>

        <div class="d-flex justify-content-center mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Add New Product</button>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Grade</th>
                    <th>Product</th>
                    <th>Weight (kg)</th>
                    <th>Crop Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <td><?= htmlspecialchars($row['product']) ?></td>
                    <td><?= htmlspecialchars($row['weight']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</a>
                    </td>
                </tr>
                <!-- Edit Modal (same as before) -->



      <!-- Edit Modal -->
      <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post">
              <div class="modal-header bg-warning">
                <h5 class="modal-title">Edit Graded Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                <div class="mb-3">
                  <label>Grade</label>
                  <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($row['grade']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Product</label>
                  <input type="text" name="product" class="form-control" value="<?= htmlspecialchars($row['product']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Weight (kg)</label>
                  <input type="number" step="0.01" name="weight" class="form-control" value="<?= htmlspecialchars($row['weight']) ?>" required>
                </div>
                <div class="mb-3">
                  <label>Crop Category</label>
                  <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($row['category']) ?>" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit" class="btn btn-warning">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add Graded Product</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Grade</label>
            <input type="text" name="grade" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Product</label>
            <input type="text" name="product" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Weight (kg)</label>
            <input type="number" step="0.01" name="weight" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Crop Category</label>
            <input type="text" name="category" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-success">Add Product</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>