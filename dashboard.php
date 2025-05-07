<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "ecoharvest");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Grading chart data
$grades = [];
$weights = [];
$gradingResult = $conn->query("SELECT grade, SUM(weight) AS total_weight FROM grading GROUP BY grade");
if ($gradingResult->num_rows > 0) {
    while ($row = $gradingResult->fetch_assoc()) {
        $grades[] = $row['grade'];
        $weights[] = $row['total_weight'];
    }
} else {
    $grades = ['No Data'];
    $weights = [0];
}

// Qualified vs Disqualified data
$qualifiedDisqualifiedData = ['Qualified' => 0, 'Disqualified' => 0];
$qualifiedDisqualifiedResult = $conn->query("SELECT grade, SUM(weight) AS total_weight FROM grading GROUP BY grade");
if ($qualifiedDisqualifiedResult->num_rows > 0) {
    while ($row = $qualifiedDisqualifiedResult->fetch_assoc()) {
        $grade = strtoupper($row['grade']);
        if ($grade == 'D') {
            $qualifiedDisqualifiedData['Disqualified'] += $row['total_weight'];
        } else {
            $qualifiedDisqualifiedData['Qualified'] += $row['total_weight'];
        }
    }
} else {
    $qualifiedDisqualifiedData = ['No Data' => 1];
}
$qualifiedDisqualifiedLabels = json_encode(array_keys($qualifiedDisqualifiedData));
$qualifiedDisqualifiedValues = json_encode(array_values($qualifiedDisqualifiedData));

// Packaging chart data
$packagingPieLabels = [];
$packagingPieData = [];
$packagingBarLabels = [];
$packagingBarData = [];
$packagingResult = $conn->query("SELECT type, weight, date FROM packaging");
$typeCounts = [];
$monthlyTotals = [];
if ($packagingResult->num_rows > 0) {
    while ($row = $packagingResult->fetch_assoc()) {
        $type = $row['type'];
        $weight = (float)$row['weight'];
        $month = date("M Y", strtotime($row['date']));
        $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        $monthlyTotals[$month] = ($monthlyTotals[$month] ?? 0) + $weight;
    }
}
$packagingPieLabels = json_encode(array_keys($typeCounts) ?: ['No Data']);
$packagingPieData = json_encode(array_values($typeCounts) ?: [0]);
$packagingBarLabels = json_encode(array_keys($monthlyTotals) ?: ['No Data']);
$packagingBarData = json_encode(array_values($monthlyTotals) ?: [0]);

// Transport chart data
$chart_data = [
    'labels' => [],
    'counts' => [],
    'colors' => [
        'Pending' => '#ff9800',
        'In Transit' => '#2196F3',
        'Delivered' => '#4CAF50',
        'Cancelled' => '#f44336'
    ]
];
$query = "SELECT status, COUNT(*) as count FROM transportation GROUP BY status";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_data['labels'][] = $row['status'];
        $chart_data['counts'][] = $row['count'];
    }
} else {
    $chart_data['labels'] = ['No Data'];
    $chart_data['counts'] = [1];
    $chart_data['colors']['No Data'] = '#cccccc';
}

$conn->close();
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
                        <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="qualityreport.php" class="nav-link"><i class="fas fa-clipboard-check me-1"></i> Quality Report</a></li>
                        <li class="nav-item"><a href="grading.php" class="nav-link"><i class="fas fa-seedling me-1"></i> Graded Produced</a></li>
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

    <div class="container mt-5">
        <h2 class="mb-4 page-title">EcoHarvest Dashboard</h2>
        <div class="charts-container">
            <!-- Grading Chart -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Weight by Grade</h6>
                    <canvas id="gradeChart" style="height: 200px; width: 100%;"></canvas>
                </div>
            </div>

            <!-- Qualified vs Disqualified Pie Chart -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Qualified vs Disqualified Products</h6>
                    <canvas id="qualifiedDisqualifiedChart" style="height: 200px; width: 100%;"></canvas>
                </div>
            </div>

            <!-- Packaging Type Pie Chart -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Packaging by Type</h6>
                    <canvas id="packagingPieChart" style="height: 200px; width: 100%;"></canvas>
                </div>
            </div>

            <!-- Monthly Packaged Weight -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Monthly Packaged Weight</h6>
                    <canvas id="packagingBarChart" style="height: 200px; width: 100%;"></canvas>
                </div>
            </div>

            <!-- Transport Status Pie Chart -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Transport Report</h6>
                    <canvas id="transportPieChart" style="height: 200px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Grading Bar Chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($grades); ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo json_encode($weights); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Qualified vs Disqualified Pie Chart
        const qualifiedDisqualifiedCtx = document.getElementById('qualifiedDisqualifiedChart').getContext('2d');
        new Chart(qualifiedDisqualifiedCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $qualifiedDisqualifiedLabels; ?>,
                datasets: [{
                    data: <?php echo $qualifiedDisqualifiedValues; ?>,
                    backgroundColor: <?php echo json_encode(array_keys($qualifiedDisqualifiedData)[0] === 'No Data' ? ['#cccccc'] : ['#4CAF50', '#f44336']); ?>,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Poppins', size: 12 },
                            color: '#333'
                        }
                    },
                    title: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                if (total === 0) return `${context.label}: No Data`;
                                let percentage = ((context.parsed / total) * 100).toFixed(1) + '%';
                                return `${context.label}: ${percentage}`;
                            }
                        }
                    }
                }
            }
        });

        // Packaging Pie Chart
        const pieCtx = document.getElementById('packagingPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $packagingPieLabels; ?>,
                datasets: [{
                    data: <?php echo $packagingPieData; ?>,
                    backgroundColor: <?php echo json_encode(array_keys($typeCounts) ? ['#4CAF50', '#FF9800', '#03A9F4', '#E91E63', '#9C27B0'] : ['#cccccc']); ?>,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Poppins', size: 12 },
                            color: '#333'
                        }
                    },
                    title: { display: false }
                }
            }
        });

        // Packaging Bar Chart
        const barCtx = document.getElementById('packagingBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $packagingBarLabels; ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo $packagingBarData; ?>,
                    backgroundColor: '#2196F3',
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Transport Pie Chart
        const transportCtx = document.getElementById('transportPieChart').getContext('2d');
        const chartData = <?php echo json_encode($chart_data); ?>;
        new Chart(transportCtx, {
            type: 'pie',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.counts,
                    backgroundColor: chartData.labels.map(label => chartData.colors[label] || '#cccccc'),
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Poppins', size: 12 },
                            color: '#333'
                        }
                    },
                    title: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { family: 'Poppins' },
                        bodyFont: { family: 'Poppins' }
                    }
                }
            }
        });
    </script>
</body>
</html>