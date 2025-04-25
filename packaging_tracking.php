<?php
// Database connection with error handling
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecoharvest";

// Create connection with error handling
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new packaging
    if (isset($_POST['packaging-type'])) {
        $stmt = $conn->prepare("INSERT INTO packaging (type, weight, date, batch_id, transport_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsss", 
            $_POST['packaging-type'],
            $_POST['weight'],
            $_POST['packaging-date'],
            $_POST['batch-id'],
            $_POST['transport-id']
        );
        
        if ($stmt->execute()) {
            echo "<script>alert('Record added successfully'); window.location.href='packaging_tracking.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    // Edit packaging
    elseif (isset($_POST['edit-packaging-type'])) {
        $stmt = $conn->prepare("UPDATE packaging SET type=?, weight=?, date=?, batch_id=?, transport_id=? WHERE id=?");
        $stmt->bind_param("sdsssi",
            $_POST['edit-packaging-type'],
            $_POST['edit-weight'],
            $_POST['edit-packaging-date'],
            $_POST['edit-batch-id'],
            $_POST['edit-transport-id'],
            $_POST['edit_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo "Error updating record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete operation
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM packaging WHERE id=?");
    $stmt->bind_param("i", $_GET['delete']);
    
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully'); window.location.href='packaging_tracking.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all records
$result = $conn->query("SELECT * FROM packaging");
$packagingData = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Harvest - Packaging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Existing CSS preserved */
        body {
            background-color: #85193C;
            background-image: url(images/backgraund_image.png);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-blend-mode: overlay;
            padding-top: 90px;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        header {
            background-color: rgba(46, 139, 87, 0.9);
            color: white;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1030;
            padding: 15px 50px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand img {
            height: 60px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link {
            color: white;
            font-weight: 500;
            border-radius: 5px;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(40, 167, 69, 0.8);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            transform: translateY(-2px);
        }

        .card-transparent {
            background-color: rgba(255, 255, 255, 0.85);
            border: 1px solid #28a745;
            padding: 20px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* New enhancements */
        :root {
            --red: #e63946;
            --light-blue: #a8dadc;
            --dark-blue: #457b9d;
            --deep-blue: #1d3557;
            --yellow: #ffb703;
            --green: #28a745;
        }

        /* Button enhancements */
        .btn {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: none;
            font-weight: 500;
            padding: 8px 16px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-success {
            background-color: var(--green);
        }

        .btn-warning {
            background-color: var(--yellow);
            color: #212529;
        }

        .btn-danger {
            background-color: var(--red);
        }

        /* Table enhancements */
        .table {
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .table-hover tbody tr {
            transition: all 0.2s ease;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1);
            transform: scale(1.01);
        }

        /* Modal enhancements */
        .modal-content {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--green);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            header {
                padding: 10px 15px;
            }
            
            .navbar-brand img {
                height: 40px;
            }
            
            .navbar-nav {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin: 2px;
            }
            
            .nav-link {
                padding: 5px 8px;
                font-size: 0.9rem;
            }
            
            .card-transparent {
                padding: 15px;
            }
            
            .charts-container {
                flex-direction: column;
            }
            
            .chart-card {
                width: 100% !important;
            }
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="navbar-brand d-flex align-items-center text-white fw-bold">
                <img src="images/logo.PNG" alt="Logo" class="img-fluid">
                <span class="fs-4 ms-2">Eco Harvest</span>
            </div>
            <nav>
                <ul class="navbar-nav flex-row gap-2 gap-md-3">
                    <li class="nav-item"><a href="Dashboard.html" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Quality Report</a></li>
                    <li class="nav-item"><a href="Graded_Produced_Track.html" class="nav-link">Graded Produced</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Supply Chain</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Quality Analysis</a></li>
                    <li class="nav-item"><a href="packaging_tracking.html" class="nav-link active">Packaging</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Transportation</a></li>
                    <li class="nav-item"><a href="login.html" class="nav-link">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container py-4 py-md-5">
        <!-- Packaging Records Table -->
        <div class="card-transparent rounded-4 fade-in mb-4">
            <div class="card-body">
                <h2 class="text-center text-success fw-bold mb-4">Packaging Records</h2>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i>Add New Record
                    </button>
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" id="searchInput" class="form-control border-success" placeholder="Search...">
                        <button class="btn btn-success" onclick="filterTable()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-success">
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Weight (kg)</th>
                                <th>Date</th>
                                <th>Batch ID</th>
                                <th>Transport ID</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach ($packagingData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['type']) ?></td>
                                <td><?= htmlspecialchars($row['weight']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['batch_id']) ?></td>
                                <td><?= htmlspecialchars($row['transport_id']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm me-2 edit-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-type="<?= htmlspecialchars($row['type']) ?>"
                                            data-weight="<?= htmlspecialchars($row['weight']) ?>"
                                            data-date="<?= htmlspecialchars($row['date']) ?>"
                                            data-batch="<?= htmlspecialchars($row['batch_id']) ?>"
                                            data-transport="<?= htmlspecialchars($row['transport_id']) ?>">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <a href="packaging_tracking.php?delete=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this record?')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4 mt-md-5">
            <div class="col-lg-6 mb-4">
                <div class="card-transparent rounded-4 h-100">
                    <div class="card-body">
                        <h3 class="text-center text-success fw-bold mb-4">Packaging by Type</h3>
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="packagingPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card-transparent rounded-4 h-100">
                    <div class="card-body">
                        <h3 class="text-center text-success fw-bold mb-4">Monthly Packaging Volume</h3>
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="packagingBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content slide-in">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addModalLabel">Add Packaging Info</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="packagingForm" action="packaging_tracking.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="packaging-type" class="form-label fw-semibold">Packaging Type</label>
                                <input type="text" name="packaging-type" id="packaging-type" class="form-control" placeholder="e.g. Eco Box" required>
                            </div>
                            <div class="col-md-6">
                                <label for="weight" class="form-label fw-semibold">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" id="weight" class="form-control" placeholder="e.g. 2.5" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="packaging-date" class="form-label fw-semibold">Packaging Date</label>
                                <input type="date" name="packaging-date" id="packaging-date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="batch-id" class="form-label fw-semibold">Batch ID</label>
                                <input type="text" name="batch-id" id="batch-id" class="form-control" placeholder="e.g. BATCH001" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="transport-id" class="form-label fw-semibold">Transport ID</label>
                            <input type="text" name="transport-id" id="transport-id" class="form-control" placeholder="e.g. TRANS100" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="packagingForm" class="btn btn-success">
                        <i class="fas fa-plus-circle me-1"></i>Add Record
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content slide-in">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Packaging Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit-packaging-type" class="form-label">Packaging Type</label>
                            <input type="text" class="form-control" id="edit-packaging-type" name="edit-packaging-type" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-weight" class="form-label">Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="edit-weight" name="edit-weight" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-packaging-date" class="form-label">Packaging Date</label>
                            <input type="date" class="form-control" id="edit-packaging-date" name="edit-packaging-date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-batch-id" class="form-label">Batch ID</label>
                            <input type="text" class="form-control" id="edit-batch-id" name="edit-batch-id" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-transport-id" class="form-label">Transport ID</label>
                            <input type="text" class="form-control" id="edit-transport-id" name="edit-transport-id" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editForm" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize edit buttons
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const type = this.getAttribute('data-type');
                    const weight = this.getAttribute('data-weight');
                    const date = this.getAttribute('data-date');
                    const batch = this.getAttribute('data-batch');
                    const transport = this.getAttribute('data-transport');
                    
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit-packaging-type').value = type;
                    document.getElementById('edit-weight').value = weight;
                    document.getElementById('edit-packaging-date').value = date;
                    document.getElementById('edit-batch-id').value = batch;
                    document.getElementById('edit-transport-id').value = transport;
                    
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                });
            });

            // Initialize charts
            initCharts();
            
            // Animate cards on scroll
            animateOnScroll();
            window.addEventListener('scroll', animateOnScroll);
        });

        // Filter table function
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        // Chart initialization
        function initCharts() {
            const typeCounts = {};
            const monthlyTotals = {};
            const colorScheme = [
                '#e63946', // Red
                '#a8dadc', // Light Blue
                '#457b9d', // Dark Blue
                '#1d3557', // Deep Blue
                '#ffb703'  // Yellow
            ];

            // Process data
            document.querySelectorAll('#tableBody tr').forEach(row => {
                const type = row.cells[1].textContent;
                const weight = parseFloat(row.cells[2].textContent);
                const date = row.cells[3].textContent;
                
                // Count types
                typeCounts[type] = (typeCounts[type] || 0) + 1;
                
                // Sum weights by month
                if (date) {
                    const dateObj = new Date(date);
                    const month = dateObj.toLocaleString('default', { month: 'long', year: 'numeric' });
                    monthlyTotals[month] = (monthlyTotals[month] || 0) + weight;
                }
            });

            // Pie Chart
            const pieCtx = document.getElementById('packagingPieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(typeCounts),
                    datasets: [{
                        data: Object.values(typeCounts),
                        backgroundColor: colorScheme,
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((context.raw / total) * 100);
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });

            // Bar Chart
            const barCtx = document.getElementById('packagingBarChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(monthlyTotals).sort(),
                    datasets: [{
                        label: 'Total Weight (kg)',
                        data: Object.keys(monthlyTotals).sort().map(label => monthlyTotals[label]),
                        backgroundColor: '#457b9d',
                        borderRadius: 4,
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 5,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Weight: ${context.raw} kg`;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // Handle edit form submission via AJAX
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('packaging_tracking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    modal.hide();
                    alert('Record updated successfully');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating record');
            });
        });

        // Animate elements on scroll
        function animateOnScroll() {
            const cards = document.querySelectorAll('.card-transparent');
            const windowHeight = window.innerHeight;
            
            cards.forEach((card, index) => {
                const cardPosition = card.getBoundingClientRect().top;
                const animationDelay = index * 100;
                
                if (cardPosition < windowHeight - 100) {
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, animationDelay);
                }
            });
        }
    </script>
</body>
</html>