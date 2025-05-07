<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "ecoharvest");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$transportInfo = [];
$result = $conn->query("SELECT * FROM transportation ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transportInfo[] = [
            'id' => $row['id'],
            'transport_id' => $row['transport_id'],
            'driver_name' => $row['driver_name'],
            'driver_id' => $row['driver_id'],
            'from_location' => $row['from_location'],
            'to_location' => $row['to_location'],
            'status' => $row['status'],
            'package_id' => $row['package_id'],
            'transport_type' => $row['transport_type'],
            'transport_date' => $row['transport_date']
        ];
    }
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // AJAX Delete Handler
    if (isset($_POST['delete_id']) && isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("DELETE FROM transportation WHERE id = ?");
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

    // Add or Update Handler
    if (isset($_POST['action'])) {
        $transport_id = filter_input(INPUT_POST, 'transport_id', FILTER_SANITIZE_STRING);
        $driver_name = filter_input(INPUT_POST, 'driver_name', FILTER_SANITIZE_STRING);
        $driver_id = filter_input(INPUT_POST, 'driver_id', FILTER_SANITIZE_STRING);
        $from_location = filter_input(INPUT_POST, 'from_location', FILTER_SANITIZE_STRING);
        $to_location = filter_input(INPUT_POST, 'to_location', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $package_id = filter_input(INPUT_POST, 'package_id', FILTER_SANITIZE_STRING);
        $transport_type = filter_input(INPUT_POST, 'transport_type', FILTER_SANITIZE_STRING);
        $transport_date = filter_input(INPUT_POST, 'transport_date', FILTER_SANITIZE_STRING);

        // Validate inputs
        $errors = [];
        if (empty($transport_id) || !preg_match("/^[0-9]+$/", $transport_id)) {
            $errors[] = "Transport ID must be numeric.";
        }
        if (empty($driver_name) || !preg_match("/^[a-zA-Z\s]+$/", $driver_name)) {
            $errors[] = "Driver Name must contain only letters and spaces.";
        }
        if (empty($driver_id) || !preg_match("/^[0-9]+$/", $driver_id)) {
            $errors[] = "Driver ID must be numeric.";
        }
        if (empty($from_location)) {
            $errors[] = "From Location is required.";
        }
        if (empty($to_location)) {
            $errors[] = "To Location is required.";
        }
        if (empty($status) || !in_array($status, ['Pending', 'In Transit', 'Delivered', 'Cancelled'])) {
            $errors[] = "Invalid Status.";
        }
        if (empty($package_id)) {
            $errors[] = "Package ID is required.";
        }
        if (empty($transport_type)) {
            $errors[] = "Transport Type is required.";
        }
        if (empty($transport_date) || strtotime($transport_date) > time()) {
            $errors[] = "Invalid date or date cannot be in the future.";
        }

        // Check for duplicate transport_id or package_id
        $stmt = $conn->prepare("SELECT id FROM transportation WHERE (transport_id = ? OR package_id = ?) AND id != ?");
        if ($stmt) {
            $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
            $full_transport_id = "TRANS$transport_id";
            $stmt->bind_param("ssi", $full_transport_id, $package_id, $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Transport ID or Package ID already exists.";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $full_transport_id = "TRANS$transport_id";
            $full_driver_id = "DRIVER$driver_id";

            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO transportation (transport_id, driver_name, driver_id, from_location, to_location, status, package_id, transport_type, transport_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssssssss", $full_transport_id, $driver_name, $full_driver_id, $from_location, $to_location, $status, $package_id, $transport_type, $transport_date);
                    if ($stmt->execute()) {
                        $_SESSION['notification'] = "Transport record added successfully!";
                        $_SESSION['notification_class'] = "alert-success";
                    } else {
                        $_SESSION['notification'] = "Error adding record: " . $conn->error;
                        $_SESSION['notification_class'] = "alert-danger";
                    }
                    $stmt->close();
                }
            } elseif ($_POST['action'] == 'edit' && isset($_POST['edit_id'])) {
                $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_SANITIZE_NUMBER_INT);
                $stmt = $conn->prepare("UPDATE transportation SET transport_id = ?, driver_name = ?, driver_id = ?, from_location = ?, to_location = ?, status = ?, package_id = ?, transport_type = ?, transport_date = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("sssssssssi", $full_transport_id, $driver_name, $full_driver_id, $from_location, $to_location, $status, $package_id, $transport_type, $transport_date, $edit_id);
                    if ($stmt->execute()) {
                        $_SESSION['notification'] = "Transport record updated successfully!";
                        $_SESSION['notification_class'] = "alert-success";
                    } else {
                        $_SESSION['notification'] = "Error updating record: " . $conn->error;
                        $_SESSION['notification_class'] = "alert-danger";
                    }
                    $stmt->close();
                }
            }
        } else {
            $_SESSION['notification'] = implode(" ", $errors);
            $_SESSION['notification_class'] = "alert-danger";
        }

        // Refresh transportInfo
        $transportInfo = [];
        $result = $conn->query("SELECT * FROM transportation ORDER BY id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $transportInfo[] = [
                    'id' => $row['id'],
                    'transport_id' => $row['transport_id'],
                    'driver_name' => $row['driver_name'],
                    'driver_id' => $row['driver_id'],
                    'from_location' => $row['from_location'],
                    'to_location' => $row['to_location'],
                    'status' => $row['status'],
                    'package_id' => $row['package_id'],
                    'transport_type' => $row['transport_type'],
                    'transport_date' => $row['transport_date']
                ];
            }
        }

        if (!isset($_POST['ajax'])) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Fetch available Package IDs
$package_ids_result = $conn->query("SELECT p.package_id FROM packaging p 
                                   LEFT JOIN transportation t ON p.package_id = t.package_id 
                                   WHERE t.package_id IS NULL 
                                   ORDER BY p.package_id");

// Clear notification after display
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : '';
$notification_class = isset($_SESSION['notification_class']) ? $_SESSION['notification_class'] : '';
unset($_SESSION['notification'], $_SESSION['notification_class']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Eco Harvest - Transport Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
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

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #0d6efd);
            border: none;
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(23, 162, 184, 0.4);
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

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .form-control:focus, .form-select:focus {
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

        .autocomplete-suggestions {
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .autocomplete-suggestion {
            padding: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .autocomplete-suggestion:hover {
            background: var(--secondary-color);
        }

        #trackMap {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate__animated {
            animation-duration: 0.5s;
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

        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .status-in-transit {
            color: #2196F3;
            font-weight: bold;
        }

        .status-delivered {
            color: #4CAF50;
            font-weight: bold;
        }

        .status-cancelled {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a href="qualityanalysis.php" class="nav-link"><i class="fas fa-chart-line me-1"></i> Quality Analysis</a></li>
                    <li class="nav-item"><a href="packaging.php" class="nav-link"><i class="fas fa-box-open me-1"></i> Packaging</a></li>
                    <li class="nav-item"><a href="transport.php" class="nav-link active"><i class="fas fa-shipping-fast me-1"></i> Transportation</a></li>
                    <li class="nav-item"><a href="supplychain.php" class="nav-link"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
                    <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
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
        <h1 class="page-title animate__animated animate__fadeIn">Transport Management</h1>
        <p class="page-subtitle animate__animated animate__fadeIn animate__delay-1s">Track and manage all transportation operations</p>
    </div>

    <div class="table-card animate__animated animate__fadeInUp">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransportModal" aria-label="Add new transport record">
                <i class="fas fa-plus me-2"></i>Add New Record
            </button>
            <div class="d-flex flex-column text-center records-header">
                <h2 class="records-title"><i class="fas fa-shipping-fast me-2"></i>Transport Records</h2>
                <small class="records-count">Total Records: <span id="totalRecords"><?php echo count($transportInfo); ?></span></small>
            </div>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Search records..." aria-label="Search transport records">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Transport ID</th>
                        <th scope="col">Driver Name</th>
                        <th scope="col">Driver ID</th>
                        <th scope="col">From</th>
                        <th scope="col">To</th>
                        <th scope="col">Status</th>
                        <th scope="col">Package ID</th>
                        <th scope="col">Type</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    $recordsPerPage = 10;
                    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $start = ($currentPage - 1) * $recordsPerPage;
                    $paginatedItems = array_slice($transportInfo, $start, $recordsPerPage);

                    foreach ($paginatedItems as $index => $item):
                        $raw_transport_id = preg_replace('/^TRANS/', '', $item['transport_id']);
                        $raw_driver_id = preg_replace('/^DRIVER/', '', $item['driver_id']);
                    ?>
                    <tr class="animate__animated animate__fadeIn" style="animation-delay: <?php echo ($index * 0.05); ?>s;">
                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                        <td><?php echo htmlspecialchars($item['transport_id']); ?></td>
                        <td><?php echo htmlspecialchars($item['driver_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['driver_id']); ?></td>
                        <td><?php echo htmlspecialchars($item['from_location']); ?></td>
                        <td><?php echo htmlspecialchars($item['to_location']); ?></td>
                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $item['status'])); ?>">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['package_id']); ?></span></td>
                        <td><?php echo htmlspecialchars($item['transport_type']); ?></td>
                        <td><?php echo (new DateTime($item['transport_date']))->format('M d, Y'); ?></td>
                        <td class="text-center action-buttons">
                            <button class="btn btn-warning btn-sm me-2" onclick="editTransport(<?php echo $item['id']; ?>)" aria-label="Edit record <?php echo $item['id']; ?>">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-danger btn-sm me-2" onclick="confirmDelete(<?php echo $item['id']; ?>)" aria-label="Delete record <?php echo $item['id']; ?>">
                                <i class="fas fa-trash-alt me-1"></i>Delete
                            </button>
                            <button class="btn btn-info btn-sm" onclick="trackRoute('<?php echo htmlspecialchars(addslashes($item['from_location'])); ?>', '<?php echo htmlspecialchars(addslashes($item['to_location'])); ?>')" aria-label="Track route for record <?php echo $item['id']; ?>">
                                <i class="fas fa-map-marked-alt me-1"></i>Track
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paginatedItems)): ?>
                    <tr><td colspan="11" class="text-center">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav aria-label="Table pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php
                $pageCount = ceil(count($transportInfo) / $recordsPerPage);
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
</main>

<!-- Add Transport Modal -->
<div class="modal fade" id="addTransportModal" tabindex="-1" aria-labelledby="addTransportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransportModalLabel"><i class="fas fa-shipping-fast me-2"></i>Add Transport Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="transportForm" method="POST" action="">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="edit_id" id="editId">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="transport_id" class="form-label">Transport ID</label>
                            <div class="input-group">
                                <span class="input-group-text">TRANS</span>
                                <input type="text" id="transport_id" name="transport_id" class="form-control" placeholder="e.g. 001" required pattern="[0-9]+">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="driver_name" class="form-label">Driver Name</label>
                            <input type="text" id="driver_name" name="driver_name" class="form-control" required pattern="[a-zA-Z\s]+">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="driver_id" class="form-label">Driver ID</label>
                            <div class="input-group">
                                <span class="input-group-text">DRIVER</span>
                                <input type="text" id="driver_id" name="driver_id" class="form-control" placeholder="e.g. 001" required pattern="[0-9]+">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="from_location" class="form-label">From Location</label>
                            <input type="text" id="from_location" name="from_location" class="form-control" required autocomplete="off">
                            <div id="from_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="to_location" class="form-label">To Location</label>
                            <input type="text" id="to_location" name="to_location" class="form-control" required autocomplete="off">
                            <div id="to_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="" selected disabled>Select Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="package_id" class="form-label">Package ID</label>
                            <select id="package_id" name="package_id" class="form-select" required>
                                <option value="" selected disabled>Select Package ID</option>
                                <?php while ($package = $package_ids_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($package['package_id']); ?>">
                                        <?php echo htmlspecialchars($package['package_id']); ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php $package_ids_result->data_seek(0); ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="transport_type" class="form-label">Transport Type</label>
                            <select id="transport_type" name="transport_type" class="form-select" required>
                                <option value="" selected disabled>Select Type</option>
                                <option value="Truck">Truck</option>
                                <option value="Van">Van</option>
                                <option value="Ship">Ship</option>
                                <option value="Air">Air</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="transport_date" class="form-label">Transport Date</label>
                            <input type="date" id="transport_date" name="transport_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="saveTransportBtn">
                            <i class="fas fa-save me-2"></i>Save Transport
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transport Modal -->
<div class="modal fade" id="editTransportModal" tabindex="-1" aria-labelledby="editTransportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTransportModalLabel"><i class="fas fa-edit me-2"></i>Edit Transport Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTransportForm" method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="edit_transport_id" class="form-label">Transport ID</label>
                            <div class="input-group">
                                <span class="input-group-text">TRANS</span>
                                <input type="text" id="edit_transport_id" name="transport_id" class="form-control" required pattern="[0-9]+">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_driver_name" class="form-label">Driver Name</label>
                            <input type="text" id="edit_driver_name" name="driver_name" class="form-control" required pattern="[a-zA-Z\s]+">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_driver_id" class="form-label">Driver ID</label>
                            <div class="input-group">
                                <span class="input-group-text">DRIVER</span>
                                <input type="text" id="edit_driver_id" name="driver_id" class="form-control" required pattern="[0-9]+">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="edit_from_location" class="form-label">From Location</label>
                            <input type="text" id="edit_from_location" name="from_location" class="form-control" required autocomplete="off">
                            <div id="edit_from_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_to_location" class="form-label">To Location</label>
                            <input type="text" id="edit_to_location" name="to_location" class="form-control" required autocomplete="off">
                            <div id="edit_to_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select id="edit_status" name="status" class="form-select" required>
                                <option value="Pending">Pending</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label for="edit_package_id" class="form-label">Package ID</label>
                            <select id="edit_package_id" name="package_id" class="form-select" required>
                                <option value="" selected disabled>Select Package ID</option>
                                <?php while ($package = $package_ids_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($package['package_id']); ?>">
                                        <?php echo htmlspecialchars($package['package_id']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_transport_type" class="form-label">Transport Type</label>
                            <select id="edit_transport_type" name="transport_type" class="form-select" required>
                                <option value="" selected disabled>Select Type</option>
                                <option value="Truck">Truck</option>
                                <option value="Van">Van</option>
                                <option value="Ship">Ship</option>
                                <option value="Air">Air</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_transport_date" class="form-label">Transport Date</label>
                            <input type="date" id="edit_transport_date" name="transport_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning" id="updateTransportBtn">
                            <i class="fas fa-save me-2"></i>Update Transport
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Track Route Modal -->
<div class="modal fade" id="trackRouteModal" tabindex="-1" aria-labelledby="trackRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trackRouteModalLabel"><i class="fas fa-map-marked-alt me-2"></i>Track Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="trackMap" style="height: 400px; width: 100%;"></div>
                <div id="routeInfo" class="mt-3 text-center"></div>
                <div id="mapError" class="mt-3 text-center text-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
<script>
let transportInfo = <?php echo json_encode($transportInfo); ?>;
let currentPage = <?php echo $currentPage; ?>;
const recordsPerPage = <?php echo $recordsPerPage; ?>;
let debounceTimeout;

// Bangladesh districts with coordinates (lat, lng)
const districts = [
    { name: "Bagerhat", lat: 22.6579, lng: 89.7895 },
    { name: "Bandarban", lat: 22.1953, lng: 92.2187 },
    { name: "Barguna", lat: 22.1591, lng: 90.1266 },
    { name: "Barisal", lat: 22.7010, lng: 90.3535 },
    { name: "Bhola", lat: 22.6879, lng: 90.6441 },
    { name: "Bogra", lat: 24.8465, lng: 89.3773 },
    { name: "Brahmanbaria", lat: 23.9571, lng: 91.1115 },
    { name: "Chandpur", lat: 23.2333, lng: 90.6713 },
    { name: "Chittagong", lat: 22.3569, lng: 91.7832 },
    { name: "Chuadanga", lat: 23.6402, lng: 88.8263 },
    { name: "Comilla", lat: 23.4682, lng: 91.1788 },
    { name: "Cox's Bazar", lat: 21.4272, lng: 92.0058 },
    { name: "Dhaka", lat: 23.8103, lng: 90.4125 },
    { name: "Dinajpur", lat: 25.6279, lng: 88.6378 },
    { name: "Faridpur", lat: 23.6071, lng: 89.8428 },
    { name: "Feni", lat: 23.0159, lng: 91.3976 },
    { name: "Gaibandha", lat: 25.3297, lng: 89.5349 },
    { name: "Gazipur", lat: 24.0023, lng: 90.4268 },
    { name: "Gopalganj", lat: 23.0051, lng: 89.8266 },
    { name: "Habiganj", lat: 24.3745, lng: 91.4155 },
    { name: "Jamalpur", lat: 24.9375, lng: 89.9378 },
    { name: "Jessore", lat: 23.1697, lng: 89.2131 },
    { name: "Jhalokati", lat: 22.6406, lng: 90.1987 },
    { name: "Jhenaidah", lat: 23.5448, lng: 89.1539 },
    { name: "Joypurhat", lat: 25.1015, lng: 89.0273 },
    { name: "Khagrachari", lat: 23.1193, lng: 91.9846 },
    { name: "Khulna", lat: 22.8456, lng: 89.5403 },
    { name: "Kishoreganj", lat: 24.2499, lng: 90.7729 },
    { name: "Kurigram", lat: 25.8054, lng: 89.6362 },
    { name: "Kushtia", lat: 23.9013, lng: 89.1201 },
    { name: "Lakshmipur", lat: 22.9443, lng: 90.8418 },
    { name: "Lalmonirhat", lat: 25.9923, lng: 89.2847 },
    { name: "Madaripur", lat: 23.1641, lng: 90.1897 },
    { name: "Magura", lat: 23.4871, lng: 89.4198 },
    { name: "Manikganj", lat: 23.8617, lng: 90.0003 },
    { name: "Meherpur", lat: 23.7622, lng: 88.6318 },
    { name: "Moulvibazar", lat: 24.4829, lng: 91.7773 },
    { name: "Munshiganj", lat: 23.5422, lng: 90.5305 },
    { name: "Mymensingh", lat: 24.7471, lng: 90.4203 },
    { name: "Naogaon", lat: 24.7936, lng: 88.9318 },
    { name: "Narail", lat: 23.1725, lng: 89.5127 },
    { name: "Narayanganj", lat: 23.6337, lng: 90.4966 },
    { name: "Narsingdi", lat: 23.9228, lng: 90.7177 },
    { name: "Natore", lat: 24.4206, lng: 89.0003 },
    { name: "Netrokona", lat: 24.8708, lng: 90.7271 },
    { name: "Nilphamari", lat: 25.8483, lng: 88.9466 },
    { name: "Noakhali", lat: 22.8358, lng: 91.0942 },
    { name: "Pabna", lat: 24.0063, lng: 89.2372 },
    { name: "Panchagarh", lat: 26.3351, lng: 88.5578 },
    { name: "Patuakhali", lat: 22.3596, lng: 90.3297 },
    { name: "Pirojpur", lat: 22.5797, lng: 89.9752 },
    { name: "Rajbari", lat: 23.7574, lng: 89.644 },
    { name: "Rajshahi", lat: 24.3745, lng: 88.6042 },
    { name: "Rangamati", lat: 22.7324, lng: 92.2985 },
    { name: "Rangpur", lat: 25.7558, lng: 89.2444 },
    { name: "Satkhira", lat: 22.7185, lng: 89.0705 },
    { name: "Shariatpur", lat: 23.2423, lng: 90.4348 },
    { name: "Sherpur", lat: 25.0208, lng: 90.0153 },
    { name: "Sirajganj", lat: 24.4539, lng: 89.7007 },
    { name: "Sunamganj", lat: 25.0658, lng: 91.3959 },
    { name: "Sylhet", lat: 24.8949, lng: 91.8687 },
    { name: "Tangail", lat: 24.2513, lng: 89.9167 },
    { name: "Thakurgaon", lat: 26.0337, lng: 88.4611 }
];

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

function setupAutocomplete(inputId, suggestionsId) {
    const input = document.getElementById(inputId);
    const suggestionsContainer = document.getElementById(suggestionsId);

    input.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        suggestionsContainer.innerHTML = '';
        if (!value) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        const matches = districts.filter(d => d.name.toLowerCase().startsWith(value));
        if (matches.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        matches.forEach(district => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            div.textContent = district.name;
            div.addEventListener('click', () => {
                input.value = district.name;
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
            });
            suggestionsContainer.appendChild(div);
        });

        suggestionsContainer.style.display = 'block';
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

function editTransport(id) {
    const item = transportInfo.find(t => t.id == id);
    if (!item) return;

    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_transport_id').value = item.transport_id.replace('TRANS', '');
    document.getElementById('edit_driver_name').value = item.driver_name;
    document.getElementById('edit_driver_id').value = item.driver_id.replace('DRIVER', '');
    document.getElementById('edit_from_location').value = item.from_location;
    document.getElementById('edit_to_location').value = item.to_location;
    document.getElementById('edit_status').value = item.status;
    document.getElementById('edit_package_id').value = item.package_id;
    document.getElementById('edit_transport_type').value = item.transport_type;
    document.getElementById('edit_transport_date').value = item.transport_date;

    const modal = new bootstrap.Modal(document.getElementById('editTransportModal'));
    modal.show();
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
                showNotification('Transport record deleted successfully!', true);
                transportInfo = transportInfo.filter(item => item.id != id);
                updateTable();
            } else {
                showNotification('Error deleting record: ' + data.error, false);
            }
        })
        .catch(error => showNotification('Error: ' + error, false));
    }
}

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const filteredItems = transportInfo.filter(item =>
        Object.values(item).some(val =>
            val.toString().toLowerCase().includes(searchTerm)
        )
    );
    updateTable(filteredItems);
}

function updateTable(filteredItems = transportInfo) {
    const tableBody = document.getElementById('tableBody');
    const totalRecords = document.getElementById('totalRecords');
    tableBody.innerHTML = '';

    if (filteredItems.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="11" class="text-center">No records found.</td></tr>';
        totalRecords.textContent = '0';
        return;
    }

    const start = (currentPage - 1) * recordsPerPage;
    const end = start + recordsPerPage;
    const paginatedItems = filteredItems.slice(start, end);

    paginatedItems.forEach((item, index) => {
        const row = document.createElement('tr');
        row.classList.add('animate__animated', 'animate__fadeIn');
        row.style.animationDelay = `${index * 0.05}s`;
        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.transport_id}</td>
            <td>${item.driver_name}</td>
            <td>${item.driver_id}</td>
            <td>${item.from_location}</td>
            <td>${item.to_location}</td>
            <td class="status-${item.status.toLowerCase().replace(' ', '-')}">${item.status}</td>
            <td><span class="badge bg-light text-dark">${item.package_id}</span></td>
            <td>${item.transport_type}</td>
            <td>${new Date(item.transport_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
            <td class="text-center action-buttons">
                <button class="btn btn-warning btn-sm me-2" onclick="editTransport(${item.id})" aria-label="Edit record ${item.id}">
                    <i class="fas fa-edit me-1"></i>Edit
                </button>
                <button class="btn btn-danger btn-sm me-2" onclick="confirmDelete(${item.id})" aria-label="Delete record ${item.id}">
                    <i class="fas fa-trash-alt me-1"></i>Delete
                </button>
                <button class="btn btn-info btn-sm" onclick="trackRoute('${item.from_location}', '${item.to_location}')" aria-label="Track route for record ${item.id}">
                    <i class="fas fa-map-marked-alt me-1"></i>Track
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    totalRecords.textContent = filteredItems.length;
}

let map, routeLayer;

function trackRoute(from, to) {
    console.log('trackRoute called with:', { from, to });

    const fromDistrict = districts.find(d => d.name.toLowerCase() === from.toLowerCase());
    const toDistrict = districts.find(d => d.name.toLowerCase() === to.toLowerCase());

    const mapError = document.getElementById('mapError');
    const routeInfo = document.getElementById('routeInfo');
    mapError.style.display = 'none';
    routeInfo.innerHTML = '';

    if (!fromDistrict || !toDistrict) {
        console.error('Invalid district names:', { from, to });
        mapError.textContent = 'Error: One or both district names are invalid.';
        mapError.style.display = 'block';
        return;
    }

    console.log('Districts found:', { fromDistrict, toDistrict });

    const modal = new bootstrap.Modal(document.getElementById('trackRouteModal'));
    modal.show();

    // Initialize map after modal is fully shown
    document.getElementById('trackRouteModal').addEventListener('shown.bs.modal', function () {
        try {
            if (map) {
                map.remove();
            }

            map = L.map('trackMap', {
                center: [(fromDistrict.lat + toDistrict.lat) / 2, (fromDistrict.lng + toDistrict.lng) / 2],
                zoom: 7
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(map);

            // Add markers
            L.marker([fromDistrict.lat, fromDistrict.lng])
                .addTo(map)
                .bindPopup(fromDistrict.name)
                .openPopup();
            L.marker([toDistrict.lat, toDistrict.lng])
                .addTo(map)
                .bindPopup(toDistrict.name);

            // Remove existing route layer if any
            if (routeLayer) {
                map.removeLayer(routeLayer);
            }

            // Add routing control
            routeLayer = L.Routing.control({
                waypoints: [
                    L.latLng(fromDistrict.lat, fromDistrict.lng),
                    L.latLng(toDistrict.lat, toDistrict.lng)
                ],
                routeWhileDragging: true,
                lineOptions: {
                    styles: [{ color: 'blue', weight: 4 }]
                },
                createMarker: () => null, // Use existing markers
                addWaypoints: false,
                draggableWaypoints: false,
                show: false // Hide default route instructions
            }).addTo(map);

            // Handle routing errors
            routeLayer.on('routingerror', function(e) {
                console.error('Routing error:', e.error);
                mapError.textContent = 'Error calculating route: ' + e.error.message;
                mapError.style.display = 'block';
            });

            // Update route info with straight-line distance
            const distance = calculateDistance(fromDistrict.lat, fromDistrict.lng, toDistrict.lat, toDistrict.lng);
            routeInfo.innerHTML = `
                <p><strong>From:</strong> ${fromDistrict.name}</p>
                <p><strong>To:</strong> ${toDistrict.name}</p>
                <p><strong>Straight-Line Distance:</strong> ${distance.toFixed(2)} km</p>
            `;

            // Ensure map renders correctly
            setTimeout(() => {
                map.invalidateSize();
                console.log('Map initialized and rendered');
            }, 100);

        } catch (error) {
            console.error('Error initializing map:', error);
            mapError.textContent = 'Error loading map: ' + error.message;
            mapError.style.display = 'block';
        }
    }, { once: true });
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transport_date').setAttribute('max', today);
    document.getElementById('edit_transport_date').setAttribute('max', today);
    document.getElementById('transport_date').valueAsDate = new Date();

    setupAutocomplete('from_location', 'from_suggestions');
    setupAutocomplete('to_location', 'to_suggestions');
    setupAutocomplete('edit_from_location', 'edit_from_suggestions');
    setupAutocomplete('edit_to_location', 'edit_to_suggestions');

    updateTable();

    document.getElementById('addTransportModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('transportForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('editId').value = '';
        document.getElementById('transport_date').valueAsDate = new Date();
        document.getElementById('from_suggestions').style.display = 'none';
        document.getElementById('to_suggestions').style.display = 'none';
    });

    document.getElementById('editTransportModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('editTransportForm').reset();
        document.getElementById('edit_from_suggestions').style.display = 'none';
        document.getElementById('edit_to_suggestions').style.display = 'none';
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(filterTable, 300);
    });

    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        header.classList.toggle('scrolled', window.scrollY > 10);
    });

    <?php if ($notification): ?>
    showNotification("<?php echo addslashes($notification); ?>", <?php echo $notification_class == 'alert-success' ? 'true' : 'false'; ?>);
    <?php endif; ?>

    // Check if Leaflet and Leaflet Routing Machine are loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded');
        document.getElementById('mapError').textContent = 'Error: Leaflet library failed to load.';
        document.getElementById('mapError').style.display = 'block';
    } else if (typeof L.Routing === 'undefined') {
        console.error('Leaflet Routing Machine library not loaded');
        document.getElementById('mapError').textContent = 'Error: Leaflet Routing Machine library failed to load.';
        document.getElementById('mapError').style.display = 'block';
    } else {
        console.log('Leaflet and Leaflet Routing Machine libraries loaded successfully');
    }
});
</script>
</body>
</html>

<?php $conn->close(); ?>