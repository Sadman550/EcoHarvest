<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecoharvest";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['batch_id'])) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE quality_analysis SET 
            crop_name=?, grade=?, weight=?, expiry_date=?, 
            name_quantity=?, packaging_type=?, packaging_date=? 
            WHERE batch_id=?");
        $stmt->bind_param("ssdsssss", 
            $_POST['crop_name'], $_POST['grade'], $_POST['weight'],
            $_POST['expiry_date'], $_POST['name_quantity'],
            $_POST['packaging_type'], $_POST['packaging_date'],
            $_POST['batch_id']);
        $stmt->execute();
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO quality_analysis 
            (batch_id, crop_name, grade, weight, expiry_date, 
            name_quantity, packaging_type, packaging_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssss", 
            $_POST['new_batch_id'], $_POST['crop_name'], $_POST['grade'],
            $_POST['weight'], $_POST['expiry_date'], $_POST['name_quantity'],
            $_POST['packaging_type'], $_POST['packaging_date']);
        $stmt->execute();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM quality_analysis WHERE batch_id=?");
    $stmt->bind_param("s", $_GET['delete']);
    $stmt->execute();
}

// Fetch records with filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$gradeFilter = isset($_GET['grade']) ? $_GET['grade'] : '';

$sql = "SELECT * FROM quality_analysis WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (batch_id LIKE '%$search%' OR crop_name LIKE '%$search%')";
}
if (!empty($gradeFilter)) {
    $sql .= " AND grade = '$gradeFilter'";
}
$records = $conn->query($sql);

// Calculate stats
$stats = [
    'total_batches' => 0,
    'highest_weight' => 0,
    'top_grade' => 'N/A'
];

$weights = [];
$grades = [];

if ($records->num_rows > 0) {
    $stats['total_batches'] = $records->num_rows;
    while($row = $records->fetch_assoc()) {
        $weights[] = $row['weight'];
        $grades[] = $row['grade'];
    }
    $stats['highest_weight'] = max($weights);
    $gradeCounts = array_count_values($grades);
    arsort($gradeCounts);
    $stats['top_grade'] = key($gradeCounts);
}

// Get data for chart
$chartData = [];
$chartQuery = $conn->query("SELECT crop_name, grade, weight FROM quality_analysis");
if ($chartQuery->num_rows > 0) {
    while($row = $chartQuery->fetch_assoc()) {
        $chartData[] = [
            'label' => $row['crop_name'] . ' - ' . $row['grade'],
            'weight' => $row['weight']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quality Analysis - Eco Harvest</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  /* Basic styling */
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f8; }
  header { display: flex; justify-content: space-between; align-items: center; background: #2e8b57; padding: 10px 20px; color: white; }
  header .logo { display: flex; align-items: center; }
  header .logo img { height: 40px; margin-right: 10px; }
  nav ul { list-style: none; display: flex; }
  nav ul li { margin: 0 10px; }
  nav ul li a { color: white; text-decoration: none; }
  nav ul li a.active { text-decoration: underline; }

  main { margin-top: 20px; }
  .stats-container { display: flex; gap: 20px; margin-bottom: 20px; }
  .stat-card { background: white; padding: 20px; flex: 1; text-align: center; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
  .stat-value { font-size: 2em; margin-bottom: 5px; }
  .search-container { display: flex; gap: 10px; margin-bottom: 20px; }
  .search-container input, .search-container select, .search-container button { padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
  .filter-btn { background: #2e8b57; color: white; cursor: pointer; }

  table { width: 100%; border-collapse: collapse; background: white; }
  table th, table td { padding: 10px; border: 1px solid #ddd; text-align: center; }
  table th { background: #2e8b57; color: white; }

  .fab { position: fixed; bottom: 30px; right: 30px; background: #2e8b57; color: white; border: none; padding: 15px; border-radius: 50%; font-size: 24px; cursor: pointer; }

  /* Modal */
  .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
  .modal-content { background: white; padding: 20px; border-radius: 10px; width: 600px; }
  .close { float: right; font-size: 24px; cursor: pointer; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
  <div class="logo">
    <img src="Logo/logo-transparent (2.1).PNG" alt="Eco Harvest Logo">
    <span>Eco Harvest</span>
  </div>
  <nav>
    <ul>
      <li><a href="Dashboard.php">Dashboard</a></li>
      <li><a href="Quality_Report.php">Quality Report</a></li>
      <li><a href="Quality_analysis.php" class="active">Quality Analysis</a></li>
      <li><a href="Graded_Produced_Track.php">Graded Produced</a></li>
      <li><a href="packaging_tracking.php">Packaging</a></li>
      <li><a href="Transportation.php">Transportation</a></li>
      <li><a href="Supply_Chain.php">Supply Chain</a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main>
  <h2>Crop Quality Analysis</h2>

  <!-- Stats -->
  <div class="stats-container">
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['total_batches']; ?></div>
      <div class="stat-label">Total Batches</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['highest_weight']; ?>kg</div>
      <div class="stat-label">Highest Weight</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['top_grade']; ?></div>
      <div class="stat-label">Top Grade</div>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" action="Quality_analysis.php" class="search-container">
    <input type="text" name="search" placeholder="Search by crop, grade, or batch ID" value="<?php echo htmlspecialchars($search); ?>">
    <select name="grade">
      <option value="">All Grades</option>
      <option value="A" <?php if($gradeFilter=='A') echo 'selected'; ?>>A</option>
      <option value="B" <?php if($gradeFilter=='B') echo 'selected'; ?>>B</option>
      <option value="C" <?php if($gradeFilter=='C') echo 'selected'; ?>>C</option>
    </select>
    <button type="submit" class="filter-btn">Apply Filters</button>
  </form>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th>Batch ID</th>
        <th>Crop Name</th>
        <th>Grade</th>
        <th>Weight</th>
        <th>Expiry Date</th>
        <th>Name Quantity</th>
        <th>Packaging Type</th>
        <th>Packaging Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $recordResult = $conn->query($sql);
      if ($recordResult->num_rows > 0) {
          while($row = $recordResult->fetch_assoc()) {
              echo "<tr>
                <td>{$row['batch_id']}</td>
                <td>{$row['crop_name']}</td>
                <td>{$row['grade']}</td>
                <td>{$row['weight']}</td>
                <td>{$row['expiry_date']}</td>
                <td>{$row['name_quantity']}</td>
                <td>{$row['packaging_type']}</td>
                <td>{$row['packaging_date']}</td>
                <td>
                  <button class='edit' onclick=\"showEditForm('".htmlspecialchars($row['batch_id'])."','".htmlspecialchars($row['crop_name'])."',
                  '".htmlspecialchars($row['grade'])."','".htmlspecialchars($row['weight'])."',
                  '".htmlspecialchars($row['expiry_date'])."','".htmlspecialchars($row['name_quantity'])."',
                  '".htmlspecialchars($row['packaging_type'])."','".htmlspecialchars($row['packaging_date'])."')\">Edit</button>
                  <a href='Quality_analysis.php?delete={$row['batch_id']}' onclick=\"return confirm('Delete this record?')\">Delete</a>
                </td>
              </tr>";
          }
      } else {
          echo "<tr><td colspan='9'>No records found</td></tr>";
      }
      ?>
    </tbody>
  </table>

  <!-- Chart -->
  <canvas id="qualityChart" height="150"></canvas>
</main>

<button class="fab" onclick="showAddForm()">+</button>

<!-- Modal -->
<div id="recordModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle">Add New Record</h3>
    <form id="recordForm" method="POST" action="Quality_analysis.php">
      <input type="hidden" id="batch_id" name="batch_id">
      <input type="text" id="new_batch_id" name="new_batch_id" placeholder="Batch ID" required><br><br>
      <input type="text" id="crop_name" name="crop_name" placeholder="Crop Name" required><br><br>
      <select id="grade" name="grade">
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
      </select><br><br>
      <input type="number" id="weight" name="weight" step="0.01" placeholder="Weight" required><br><br>
      <input type="date" id="expiry_date" name="expiry_date" required><br><br>
      <input type="number" id="name_quantity" name="name_quantity" placeholder="Name Quantity" required><br><br>
      <input type="text" id="packaging_type" name="packaging_type" placeholder="Packaging Type" required><br><br>
      <input type="date" id="packaging_date" name="packaging_date" required><br><br>
      <button type="submit">Save</button>
    </form>
  </div>
</div>

<script>
// Chart
const ctx = document.getElementById('qualityChart').getContext('2d');
const qualityChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_column($chartData, 'label')); ?>,
      datasets: [{
        label: 'Weight (kg)',
        data: <?php echo json_encode(array_column($chartData, 'weight')); ?>,
        backgroundColor: '#2e8b57'
      }]
    },
    options: {
      responsive: true,
      plugins: { title: { display: true, text: 'Crop Weight by Grade' } },
      scales: { y: { beginAtZero: true } }
    }
});

// Modal JS
function showAddForm() {
    document.getElementById('modalTitle').textContent = 'Add New Record';
    resetForm();
    document.getElementById('recordModal').style.display = 'flex';
}
function showEditForm(batchId, cropName, grade, weight, expiryDate, nameQuantity, packagingType, packagingDate) {
    document.getElementById('modalTitle').textContent = 'Edit Record';
    document.getElementById('batch_id').value = batchId;
    document.getElementById('new_batch_id').value = batchId;
    document.getElementById('crop_name').value = cropName;
    document.getElementById('grade').value = grade;
    document.getElementById('weight').value = weight;
    document.getElementById('expiry_date').value = expiryDate;
    document.getElementById('name_quantity').value = nameQuantity;
    document.getElementById('packaging_type').value = packagingType;
    document.getElementById('packaging_date').value = packagingDate;
    document.getElementById('recordModal').style.display = 'flex';
}
function resetForm() {
    document.getElementById('recordForm').reset();
}
function closeModal() {
    document.getElementById('recordModal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('recordModal')) {
        closeModal();
    }
}
</script>

</body>
</html>

<?php
$conn->close();
?>
