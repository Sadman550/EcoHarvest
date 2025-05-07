<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "ecoharvest";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add product
if (isset($_POST['add'])) {
    $inspector_id = filter_input(INPUT_POST, 'inspector_id', FILTER_SANITIZE_STRING);
    $inspector_name = filter_input(INPUT_POST, 'inspector_name', FILTER_SANITIZE_STRING);
    $product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);

    if ($weight <= 0) {
        die("Invalid weight value");
    }

    $stmt = $conn->prepare("INSERT INTO grading (inspector_id, inspector_name, product, category, grade, weight) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssd", $inspector_id, $inspector_name, $product, $category, $grade, $weight);
    
    if ($stmt->execute()) {
        header("Location: grading.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Edit/Update product
if (isset($_POST['edit'])) {
    $id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);
    $inspector_id = filter_input(INPUT_POST, 'inspector_id', FILTER_SANITIZE_STRING);
    $inspector_name = filter_input(INPUT_POST, 'inspector_name', FILTER_SANITIZE_STRING);
    $product = filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_SANITIZE_STRING);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);

    if ($weight <= 0 || !$id) {
        die("Invalid input values");
    }

    $stmt = $conn->prepare("UPDATE grading SET inspector_id=?, inspector_name=?, product=?, category=?, grade=?, weight=? WHERE id=?");
    $stmt->bind_param("sssssdi", $inspector_id, $inspector_name, $product, $category, $grade, $weight, $id);

    if ($stmt->execute()) {
        header("Location: grading.php");
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
}

// Delete product
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM grading WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
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
    <title>EcoHarvest Dashboard</title>
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
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="navbar-brand d-flex align-items-center text-white">
                <img src="image/Logo.PNG" alt="Eco Harvest Logo" style="height: 60px; margin-right: 10px;" onerror="this.src='https://via.placeholder.com/60';">
                <span>Eco Harvest</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <nav>
                <ul class="navbar-nav flex-row">
                        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="qualityreport.php" class="nav-link"><i class="fas fa-clipboard-check me-1"></i> Quality Report</a></li>
                        <li class="nav-item"><a href="grading.php" class="nav-link active"><i class="fas fa-seedling me-1"></i> Graded Produced</a></li>
                        <li class="nav-item"><a href="qualityanalysis.php" class="nav-link"><i class="fas fa-chart-line me-1"></i> Quality Analysis</a></li>
                        <li class="nav-item"><a href="packaging.php" class="nav-link"><i class="fas fa-box-open me-1"></i> Packaging</a></li>
                        <li class="nav-item"><a href="transport.php" class="nav-link"><i class="fas fa-shipping-fast me-1"></i> Transportation</a></li>
                        <li class="nav-item"><a href="supplychain.php" class="nav-link"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
                        <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

<div class="container py-5 d-flex justify-content-center">
    <div class="w-100" style="max-width: 1200px;">
        <h2 class="mb-4 text-center">Graded Products</h2>

        <div class="d-flex justify-content-center mb-3">
            <button class="btn btn-success btn-premium btn-premium-success" data-bs-toggle="modal" data-bs-target="#addModal">Add New Product</button>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Inspector ID</th>
                    <th>Inspector Name</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Grade</th>
                    <th>Weight (kg)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['inspector_id']) ?></td>
                    <td><?= htmlspecialchars($row['inspector_name']) ?></td>
                    <td><?= htmlspecialchars($row['product']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <td><?= htmlspecialchars($row['weight']) ?></td>
                    <td>
                        <?php
                        $grade = strtoupper($row['grade']);
                        echo ($grade == 'D') ? '<span class="badge bg-danger">Disqualified</span>' : '<span class="badge bg-success">Qualified</span>';
                        ?>
                    </td>
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
                                    <h5 class="modal-title">Edit Graded Product</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <div class="form-group">
                                        <label class="form-label">Inspector ID</label>
                                        <input type="text" name="inspector_id" class="form-control" value="<?= htmlspecialchars($row['inspector_id']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Inspector Name</label>
                                        <input type="text" name="inspector_name" class="form-control" value="<?= htmlspecialchars($row['inspector_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" name="product" class="form-control" value="<?= htmlspecialchars($row['product']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select" required>
                                            <option value="Grains" <?= $row['category'] == 'Grains' ? 'selected' : '' ?>>Grains</option>
                                            <option value="Pulses" <?= $row['category'] == 'Pulses' ? 'selected' : '' ?>>Pulses</option>
                                            <option value="Root & Tuber Crops" <?= $row['category'] == 'Root & Tuber Crops' ? 'selected' : '' ?>>Root & Tuber Crops</option>
                                            <option value="Vegetables" <?= $row['category'] == 'Vegetables' ? 'selected' : '' ?>>Vegetables</option>
                                            <option value="Fruits" <?= $row['category'] == 'Fruits' ? 'selected' : '' ?>>Fruits</option>
                                            <option value="Oil Crops" <?= $row['category'] == 'Oil Crops' ? 'selected' : '' ?>>Oil Crops</option>
                                            <option value="Spices" <?= $row['category'] == 'Spices' ? 'selected' : '' ?>>Spices</option>
                                            <option value="Sugar Crops" <?= $row['category'] == 'Sugar Crops' ? 'selected' : '' ?>>Sugar Crops</option>
                                            <option value="Drink Crops" <?= $row['category'] == 'Drink Crops' ? 'selected' : '' ?>>Drink Crops</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Grade</label>
                                        <select name="grade" class="form-select" required>
                                            <option value="A" <?= $row['grade'] == 'A' ? 'selected' : '' ?>>A</option>
                                            <option value="B" <?= $row['grade'] == 'B' ? 'selected' : '' ?>>B</option>
                                            <option value="C" <?= $row['grade'] == 'C' ? 'selected' : '' ?>>C</option>
                                            <option value="D" <?= $row['grade'] == 'D' ? 'selected' : '' ?>>D</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.01" name="weight" class="form-control" value="<?= htmlspecialchars($row['weight']) ?>" required>
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
                    <div class="form-group">
                        <label class="form-label">Inspector ID</label>
                        <input type="text" name="inspector_id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Inspector Name</label>
                        <input type="text" name="inspector_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Grains">Grains</option>
                            <option value="Pulses">Pulses</option>
                            <option value="Root & Tuber Crops">Root & Tuber Crops</option>
                            <option value="Vegetables">Vegetables</option>
                            <option value="Fruits">Fruits</option>
                            <option value="Oil Crops">Oil Crops</option>
                            <option value="Spices">Spices</option>
                            <option value="Sugar Crops">Sugar Crops</option>
                            <option value="Drink Crops">Drink Crops</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade</label>
                        <select name="grade" class="form-select" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-premium btn-premium-success">Add Product</button>
                    <button type="button" class="btn btn-premium btn-premium-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>