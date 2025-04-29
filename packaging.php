<?php
session_start();
$conn = new mysqli("localhost", "root", "", "ecoharvest");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$packagingInfo = [];

$result = $conn->query("SELECT * FROM packaging ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $packagingInfo[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'weight' => $row['weight'],
            'date' => $row['date'],
            'batchId' => $row['batch_id'],
            'transportId' => $row['transport_id']
        ];
    }
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // AJAX Delete Handler
    if (isset($_POST['delete_id']) && isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("DELETE FROM packaging WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Statement preparation failed']);
        }
        exit();
    }

    if (isset($_POST['action'])) {
        $type = filter_input(INPUT_POST, 'packaging-type', FILTER_SANITIZE_STRING);
        $weight = filter_input(INPUT_POST, 'weight', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $date = filter_input(INPUT_POST, 'packaging-date', FILTER_SANITIZE_STRING);
        $batch_id = filter_input(INPUT_POST, 'batch-id', FILTER_SANITIZE_STRING);
        $transport_id = filter_input(INPUT_POST, 'transport-id', FILTER_SANITIZE_STRING);

        // Validate inputs
        $errors = [];
        if (empty($type)) $errors[] = "Packaging type is required.";
        if (!is_numeric($weight) || $weight <= 0 || $weight > 10000) $errors[] = "Weight must be between 0.01 and 10000 kg.";
        if (empty($date) || strtotime($date) > time()) $errors[] = "Invalid date or date cannot be in the future.";
        if (empty($batch_id) || !preg_match("/^[0-9]+$/", $batch_id)) $errors[] = "Batch ID must be numeric.";
        if (empty($transport_id) || !preg_match("/^[0-9]+$/", $transport_id)) $errors[] = "Transport ID must be numeric.";

        $batch_id_full = "BATCH$batch_id";
        $transport_id_full = "TRANS$transport_id";

        // Check for duplicate batch_id or transport_id
        $stmt = $conn->prepare("SELECT id FROM packaging WHERE (batch_id = ? OR transport_id = ?) AND id != ?");
        if ($stmt) {
            $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
            $stmt->bind_param("ssi", $batch_id_full, $transport_id_full, $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Batch ID or Transport ID already exists.";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO packaging (type, weight, date, batch_id, transport_id) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sdsss", $type, $weight, $date, $batch_id_full, $transport_id_full);
                    if ($stmt->execute()) {
                        $_SESSION['notification'] = "New packaging record added successfully!";
                        $_SESSION['notification_class'] = "alert-success";
                    } else {
                        $_SESSION['notification'] = "Error adding record: " . $conn->error;
                        $_SESSION['notification_class'] = "alert-danger";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['notification'] = "Error preparing statement.";
                    $_SESSION['notification_class'] = "alert-danger";
                }
            } elseif ($_POST['action'] == 'edit' && isset($_POST['edit_id'])) {
                $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_SANITIZE_NUMBER_INT);
                $stmt = $conn->prepare("UPDATE packaging SET type = ?, weight = ?, date = ?, batch_id = ?, transport_id = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("sdsssi", $type, $weight, $date, $batch_id_full, $transport_id_full, $edit_id);
                    if ($stmt->execute()) {
                        $_SESSION['notification'] = "Packaging record updated successfully!";
                        $_SESSION['notification_class'] = "alert-success";
                    } else {
                        $_SESSION['notification'] = "Error updating record: " . $conn->error;
                        $_SESSION['notification_class'] = "alert-danger";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['notification'] = "Error preparing statement.";
                    $_SESSION['notification_class'] = "alert-danger";
                }
            }
        } else {
            $_SESSION['notification'] = implode(" ", $errors);
            $_SESSION['notification_class'] = "alert-danger";
        }
    } elseif (isset($_POST['delete_id'])) {
        // Fallback for non-AJAX deletion (kept for compatibility)
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("DELETE FROM packaging WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $_SESSION['notification'] = "Packaging record deleted successfully!";
                $_SESSION['notification_class'] = "alert-success";
            } else {
                $_SESSION['notification'] = "Error deleting record: " . $conn->error;
                $_SESSION['notification_class'] = "alert-danger";
            }
            $stmt->close();
        } else {
            $_SESSION['notification'] = "Error preparing statement.";
            $_SESSION['notification_class'] = "alert-danger";
        }
    }

    // Refresh packagingInfo after modification (for non-AJAX requests)
    $packagingInfo = [];
    $result = $conn->query("SELECT * FROM packaging ORDER BY id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $packagingInfo[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'weight' => $row['weight'],
                'date' => $row['date'],
                'batchId' => $row['batch_id'],
                'transportId' => $row['transport_id']
            ];
        }
    }

    // Redirect for non-AJAX requests
    if (!isset($_POST['ajax'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Prepare data for charts (initial load)
$typeCounts = [];
$monthlyTotals = [];
foreach ($packagingInfo as $item) {
    $typeCounts[$item['type']] = ($typeCounts[$item['type']] ?? 0) + 1;
    $date = new DateTime($item['date']);
    $monthLabel = $date->format('M Y');
    $monthlyTotals[$monthLabel] = ($monthlyTotals[$monthLabel] ?? 0) + floatval($item['weight']);
}
$pieLabels = !empty($typeCounts) ? array_keys($typeCounts) : ['No Data'];
$pieData = !empty($typeCounts) ? array_values($typeCounts) : [1];
$barLabels = !empty($monthlyTotals) ? array_keys($monthlyTotals) : ['No Data'];
$barData = !empty($monthlyTotals) ? array_values($monthlyTotals) : [0];

// Clear notification after it will be displayed
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : '';
$notification_class = isset($_SESSION['notification_class']) ? $_SESSION['notification_class'] : '';
unset($_SESSION['notification'], $_SESSION['notification_class']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Eco Harvest - Packaging</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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
    background-image: linear-gradient(rgba(245, 245, 245, 0.1), rgba(245, 245, 245, 0.1)), 
                      url('images/backgraund_image.png');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-color: #f5f5f5;
    padding-top: 90px;
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    color: #333;
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) #f1f1f1;
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

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: var(--primary-color);
    color: white;
    font-weight: 500;
    padding: 12px 15px;
    border: none;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(46, 139, 87, 0.05);
    transform: translateX(5px);
}

.table tbody td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
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
  <header id="mainHeader">
    <div class="d-flex justify-content-between align-items-center">
      <div class="navbar-brand d-flex align-items-center text-white">
        <img src="images/logo.PNG" alt="Eco Harvest Logo" style="height: 60px; margin-right: 10px;" onerror="this.src='https://via.placeholder.com/60';">
        <span>Eco Harvest</span>
      </div>
      <nav>
        <ul class="navbar-nav flex-row">
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-home me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-clipboard-check me-1"></i> Quality Report</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-seedling me-1"></i> Graded Produced</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-line me-1"></i> Quality Analysis</a></li>
          <li class="nav-item"><a href="#" class="nav-link active"><i class="fas fa-box-open me-1"></i> Packaging</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-shipping-fast me-1"></i> Transportation</a></li>
          <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <?php if ($notification): ?>
  <div class="notification alert <?php echo htmlspecialchars($notification_class); ?>" role="alert">
    <i class="fas fa-<?php echo $notification_class == 'alert-success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
    <span id="notificationText"><?php echo htmlspecialchars($notification); ?></span>
    <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endif; ?>

  <main class="container py-4">
    <div class="text-center mb-5">
      <h1 class="page-title animate__animated animate__fadeIn">Packaging Management</h1>
      <p class="page-subtitle animate__animated animate__fadeIn animate__delay-1s">Track and manage all packaging operations and materials</p>
    </div>

    <div class="table-card animate__animated animate__fadeInUp">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPackagingModal" aria-label="Add new packaging record">
          <i class="fas fa-plus me-2"></i>Add New Record
        </button>
        
        <div class="d-flex flex-column text-center records-header">
          <h2 class="records-title"><i class="fas fa-box-open me-2"></i>Packaging Records</h2>
          <small class="records-count">Total Records: <span id="totalRecords"><?php echo count($packagingInfo); ?></span></small>
        </div>
        
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" class="form-control" placeholder="Search records..." aria-label="Search packaging records">
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Type</th>
              <th scope="col">Weight (kg)</th>
              <th scope="col">Date</th>
              <th scope="col">Batch ID</th>
              <th scope="col">Transport ID</th>
              <th scope="col" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php
            $recordsPerPage = 10;
            $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $start = ($currentPage - 1) * $recordsPerPage;
            $paginatedItems = array_slice($packagingInfo, $start, $recordsPerPage);

            foreach ($paginatedItems as $index => $item):
            ?>
            <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo ($index * 0.05); ?>s;">
              <td><?php echo htmlspecialchars($item['id']); ?></td>
              <td><i class="fas fa-box me-2"></i><?php echo htmlspecialchars($item['type']); ?></td>
              <td><?php echo number_format($item['weight'], 2); ?> kg</td>
              <td><?php echo (new DateTime($item['date']))->format('M d, Y'); ?></td>
              <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['batchId']); ?></span></td>
              <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['transportId']); ?></span></td>
              <td class="text-center action-buttons">
                <button class="btn btn-warning btn-sm me-2" onclick="editPackaging(<?php echo $item['id']; ?>)" aria-label="Edit record <?php echo $item['id']; ?>">
                  <i class="fas fa-edit me-1"></i>Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $item['id']; ?>)" aria-label="Delete record <?php echo $item['id']; ?>">
                  <i class="fas fa-trash-alt me-1"></i>Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($paginatedItems)): ?>
            <tr><td colspan="7" class="text-center">No records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <nav aria-label="Table pagination" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php
          $pageCount = ceil(count($packagingInfo) / $recordsPerPage);
          ?>
          <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous page">Previous</a>
          </li>
          <?php for ($i = 1; $i <= $pageCount; $i++): ?>
          <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>" aria-label="Page <?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $currentPage == $pageCount ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next page">Next</a>
          </li>
        </ul>
      </nav>
    </div>

    <div class="charts-container mt-5">
      <div class="chart-card animate__animated animate__fadeInLeft">
        <h3><i class="fas fa-chart-pie me-2"></i>Packaging by Type</h3>
        <canvas id="packagingPieChart"></canvas>
      </div>
      <div class="chart-card animate__animated animate__fadeInRight">
        <h3><i class="fas fa-chart-bar me-2"></i>Monthly Packaging Volume</h3>
        <canvas id="packagingBarChart"></canvas>
      </div>
    </div>
  </main>

  <div class="modal fade" id="addPackagingModal" tabindex="-1" aria-labelledby="addPackagingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addPackagingModalLabel"><i class="fas fa-box me-2"></i>Add Packaging Info</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="packagingForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="edit_id" id="editId">
            <div class="row mb-3">
              <div class="col-md-6 mb-3 mb-md-0">
                <label for="packaging-type" class="form-label">Packaging Type</label>
                <select id="packaging-type" name="packaging-type" class="form-select" required>
                  <option value="" selected disabled>Select type</option>
                  <option value="Square">Square</option>
                  <option value="Eco Box">Eco Box</option>
                  <option value="Jute Bags">Jute Bags</option>
                  <option value="Cardboard Box">Cardboard Box</option>
                  <option value="Sack Bags">Sack Bags</option>
                  <option value="Plastic Bag">Plastic Bag</option>
                  <option value="Akta hole holo">Akta hole holo</option>
                  <option value="Recycled Plastic">Recycled Plastic</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="weight" class="form-label">Weight (kg)</label>
                <div class="input-group">
                  <input type="number" step="0.01" min="0.01" id="weight" name="weight" class="form-control" placeholder="e.g. 2.5" required aria-describedby="weightUnit">
                  <span class="input-group-text" id="weightUnit">kg</span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6 mb-3 mb-md-0">
                <label for="packaging-date" class="form-label">Packaging Date</label>
                <input type="date" id="packaging-date" name="packaging-date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <div class="col-md-6">
                <label for="batch-id" class="form-label">Batch ID</label>
                <div class="input-group">
                  <span class="input-group-text">BATCH</span>
                  <input type="text" id="batch-id" name="batch-id" class="form-control" placeholder="e.g. 001" required pattern="[0-9]+" aria-describedby="batchIdPrefix">
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="transport-id" class="form-label">Transport ID</label>
              <div class="input-group">
                <span class="input-group-text">TRANS</span>
                <input type="text" id="transport-id" name="transport-id" class="form-control" placeholder="e.g. 100" required pattern="[0-9]+" aria-describedby="transportIdPrefix">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-success" id="savePackagingBtn">
                <i class="fas fa-save me-2"></i>Save Packaging
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let packagingInfo = <?php echo json_encode($packagingInfo); ?>;
    let currentPage = <?php echo $currentPage; ?>;
    const recordsPerPage = <?php echo $recordsPerPage; ?>;
    let debounceTimeout;

    function showNotification(message, isSuccess = true) {
      const notification = document.querySelector('.notification');
      if (!notification) return;
      const notificationText = document.getElementById('notificationText');
      notificationText.textContent = message;
      
      notification.classList.remove('alert-success', 'alert-danger');
      notification.classList.add(isSuccess ? 'alert-success' : 'alert-danger');
      
      notification.style.display = 'flex';
      notification.classList.add('show', 'animate__animated', 'animate__fadeInRight');
      
      setTimeout(() => {
        notification.classList.add('animate__fadeOutRight');
        setTimeout(() => {
          notification.style.display = 'none';
          notification.classList.remove('show', 'animate__animated', 'animate__fadeInRight', 'animate__fadeOutRight');
        }, 500);
      }, 3000);
    }

    function editPackaging(id) {
      const item = packagingInfo.find(p => p.id == id);
      if (!item) return;
      
      document.getElementById("packaging-type").value = item.type;
      document.getElementById("weight").value = item.weight;
      document.getElementById("packaging-date").value = item.date;
      document.getElementById("batch-id").value = item.batchId.replace('BATCH', '');
      document.getElementById("transport-id").value = item.transportId.replace('TRANS', '');
      document.getElementById("formAction").value = 'edit';
      document.getElementById("editId").value = id;
      
      const modal = new bootstrap.Modal(document.getElementById('addPackagingModal'));
      modal.show();
      
      document.getElementById('addPackagingModalLabel').innerHTML = `<i class="fas fa-edit me-2"></i>Edit Packaging Info`;
      document.getElementById('savePackagingBtn').innerHTML = `<i class="fas fa-save me-2"></i>Update Packaging`;
    }

    function confirmDelete(id) {
      if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `delete_id=${id}&ajax=1`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Packaging record deleted successfully!', true);
            packagingInfo = packagingInfo.filter(item => item.id != id);
            updateTable();
            updateCharts();
          } else {
            showNotification('Error deleting record: ' + data.error, false);
          }
        })
        .catch(error => showNotification('Error: ' + error, false));
      }
    }

    function filterTable() {
      const searchTerm = document.getElementById("searchInput").value.toLowerCase();
      const filteredItems = packagingInfo.filter(item => 
        Object.values(item).some(val => 
          val.toString().toLowerCase().includes(searchTerm)
        )
      );
      updateTable(filteredItems);
    }

    function updateTable(filteredItems = packagingInfo) {
      const tableBody = document.getElementById("tableBody");
      const totalRecords = document.getElementById("totalRecords");
      tableBody.innerHTML = "";
      
      if (filteredItems.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No records found.</td></tr>';
        totalRecords.textContent = '0';
        return;
      }

      const start = (currentPage - 1) * recordsPerPage;
      const end = start + recordsPerPage;
      const paginatedItems = filteredItems.slice(start, end);

      paginatedItems.forEach((item, index) => {
        const row = document.createElement("tr");
        row.classList.add('animate__animated', 'animate__fadeIn');
        row.style.animationDelay = `${index * 0.05}s`;
        row.innerHTML = `
          <td>${item.id}</td>
          <td><i class="fas fa-box me-2"></i>${item.type}</td>
          <td>${parseFloat(item.weight).toFixed(2)} kg</td>
          <td>${new Date(item.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
          <td><span class="badge bg-light text-dark">${item.batchId}</span></td>
          <td><span class="badge bg-light text-dark">${item.transportId}</span></td>
          <td class="text-center action-buttons">
            <button class="btn btn-warning btn-sm me-2" onclick="editPackaging(${item.id})" aria-label="Edit record ${item.id}">
              <i class="fas fa-edit me-1"></i>Edit
            </button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(${item.id})" aria-label="Delete record ${item.id}">
              <i class="fas fa-trash-alt me-1"></i>Delete
            </button>
          </td>
        `;
        tableBody.appendChild(row);
      });

      totalRecords.textContent = filteredItems.length;
    }

    let pieChart, barChart;

    function updateCharts() {
      const typeCounts = {};
      const monthlyTotals = {};
      packagingInfo.forEach(item => {
        typeCounts[item.type] = (typeCounts[item.type] || 0) + 1;
        const date = new Date(item.date);
        const monthLabel = date.toLocaleString('en-US', { month: 'short', year: 'numeric' });
        monthlyTotals[monthLabel] = (monthlyTotals[monthLabel] || 0) + parseFloat(item.weight);
      });
      const pieLabels = Object.keys(typeCounts).length ? Object.keys(typeCounts) : ['No Data'];
      const pieData = Object.keys(typeCounts).length ? Object.values(typeCounts) : [1];
      const barLabels = Object.keys(monthlyTotals).length ? Object.keys(monthlyTotals) : ['No Data'];
      const barData = Object.keys(monthlyTotals).length ? Object.values(monthlyTotals) : [0];

      const chartColors = {
        'Square': '#FF90BB',
        'Eco Box': '#3D365C',
        'Jute Bags': '#7AE2CF',
        'Cardboard Box': '#FFD63A',
        'Sack Bags': '#3A59D1',
        'Plastic Bag': '#4A102A',
        'Akta hole holo': '#BB3E00',
        'Recycled Plastic': '#213448',
        'No Data': '#ccc'
      };

      const pieBackgroundColors = pieLabels.map(label => chartColors[label] || '#ccc');
      const barBackgroundColors = barLabels.map((_, i) => Object.values(chartColors)[i % (Object.keys(chartColors).length - 1)]);

      if (pieChart) pieChart.destroy();
      pieChart = new Chart(document.getElementById("packagingPieChart"), {
        type: 'pie',
        data: {
          labels: pieLabels,
          datasets: [{
            data: pieData,
            backgroundColor: pieBackgroundColors,
            borderColor: "#fff",
            borderWidth: 2,
            hoverOffset: 20
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 15 },
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                font: { size: 13, weight: '600' },
                boxWidth: 18,
                padding: 25,
                usePointStyle: true,
                color: '#333'
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0,0,0,0.85)',
              cornerRadius: 8,
              padding: 12,
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = total ? Math.round((value / total) * 100) : 0;
                  return `${label}: ${value} records (${percentage}%)`;
                }
              }
            }
          },
          animation: {
            animateScale: true,
            animateRotate: true,
            duration: 1200,
            easing: 'easeOutQuart'
          }
        }
      });

      if (barChart) barChart.destroy();
      barChart = new Chart(document.getElementById("packagingBarChart"), {
        type: 'bar',
        data: {
          labels: barLabels,
          datasets: [{
            label: "Total Weight (kg)",
            data: barData,
            backgroundColor: barBackgroundColors,
            borderRadius: 8,
            borderWidth: 1,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 15 },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                font: { size: 13, weight: '600' },
                color: '#333',
                callback: value => value + ' kg'
              },
              grid: { color: 'rgba(0, 0, 0, 0.05)' },
              title: {
                display: true,
                text: 'Weight (kg)',
                font: { size: 15, weight: '700' },
                color: '#333'
              }
            },
            x: {
              ticks: {
                font: { size: 13, weight: '600' },
                color: '#333',
                maxRotation: 45,
                minRotation: 45
              },
              grid: { display: false },
              title: {
                display: true,
                text: 'Month',
                font: { size: 15, weight: '700' },
                color: '#333'
              }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(0,0,0,0.85)',
              cornerRadius: 8,
              padding: 12,
              callbacks: {
                label: context => `Weight: ${context.raw} kg`,
                title: context => context[0].label
              }
            }
          },
          animation: {
            duration: 1500,
            easing: 'easeOutQuart'
          }
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('packaging-date').setAttribute('max', today);
      document.getElementById('packaging-date').valueAsDate = new Date();
      
      updateTable();
      updateCharts();
      
      document.getElementById('addPackagingModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById("packagingForm").reset();
        document.getElementById('addPackagingModalLabel').innerHTML = '<i class="fas fa-box me-2"></i>Add Packaging Info';
        document.getElementById('savePackagingBtn').innerHTML = '<i class="fas fa-save me-2"></i>Save Packaging';
        document.getElementById('formAction').value = 'add';
        document.getElementById('editId').value = '';
        document.getElementById('packaging-date').valueAsDate = new Date();
      });
      
      document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(filterTable, 300);
      });

      window.addEventListener('scroll', function() {
        const header = document.getElementById('mainHeader');
        header.classList.toggle('scrolled', window.scrollY > 10);
      });

      <?php if ($notification): ?>
      showNotification("<?php echo addslashes($notification); ?>", <?php echo $notification_class == 'alert-success' ? 'true' : 'false'; ?>);
      <?php endif; ?>
    });
  </script>
</body>
</html>

<?php $conn->close(); ?>