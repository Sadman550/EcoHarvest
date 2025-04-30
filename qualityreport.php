<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default password empty
$dbname = "eco_harvest_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Add New Batch
if (isset($_POST['add'])) {
    $batch_id = $_POST['batch_id'];
    $crop_name = $_POST['crop_name'];
    $grade = $_POST['grade'];
    $weight = $_POST['weight'];
    $packaging_type = $_POST['packaging_type'];
    $packaging_date = $_POST['packaging_date'];

    $conn->query("INSERT INTO batches (batch_id, crop_name, grade, weight, packaging_type, packaging_date) VALUES ('$batch_id', '$crop_name', '$grade', '$weight', '$packaging_type', '$packaging_date')");
    header("Location: quality-report.php");
}

// Delete Batch
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM batches WHERE id=$id");
    header("Location: quality-report.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Eco Harvest | Quality Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-image: url('image/backgraund.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      color: white;
      font-family: 'Arial', sans-serif;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 50px;
      background-color: rgba(46, 139, 87, 0.9);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 100;
    }
    nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
      padding: 0;
      margin: 0;
    }
    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    .main {
      padding: 150px 20px 40px;
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 20px;
      color: white;
      margin-bottom: 30px;
    }
    table, th, td {
      color: white;
    }
    .form-control, .btn {
      border-radius: 6px;
    }
    .form-control {
      background-color: rgba(255, 255, 255, 0.15);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .form-control::placeholder {
      color: #ccc;
    }
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 15px;
      }
      nav ul {
        flex-wrap: wrap;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="logo fw-bold fs-4">Eco Harvest</div>
  <nav>
    <ul>
      <li><a href="#">Dashboard</a></li>
      <li><a href="#">Quality Report</a></li>
      <li><a href="#">Packaging</a></li>
      <li><a href="#">Transportation</a></li>
      <li><a href="#">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="main container">

  <h2 class="text-center mb-4">📋 Quality Report</h2>

  <!-- Add Crop Form -->
  <div class="glass-card">
    <h5 class="mb-3">➕ Add New Crop Batch</h5>
    <form method="POST" class="row g-2">
      <div class="col-md-2"><input class="form-control" name="batch_id" placeholder="Batch ID"></div>
      <div class="col-md-2"><input class="form-control" name="crop_name" placeholder="Crop Name"></div>
      <div class="col-md-2"><input class="form-control" name="grade" placeholder="Grade"></div>
      <div class="col-md-2"><input class="form-control" name="weight" placeholder="Weight (kg)" type="number"></div>
      <div class="col-md-2"><input class="form-control" name="packaging_type" placeholder="Packaging Type"></div>
      <div class="col-md-2"><input class="form-control" name="packaging_date" placeholder="Packaging Date" type="date"></div>
      <div class="col-12">
        <button type="submit" name="add" class="btn btn-success mt-3">Add</button>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="glass-card">
    <h5>📦 Crop Batch List</h5>
    <div class="table-responsive">
      <table class="table table-bordered text-white">
        <thead>
          <tr>
            <th>Batch ID</th>
            <th>Crop Name</th>
            <th>Grade</th>
            <th>Weight (kg)</th>
            <th>Packaging Type</th>
            <th>Packaging Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM batches ORDER BY id DESC");
          while ($row = $result->fetch_assoc()):
          ?>
          <tr>
            <td><?php echo $row['batch_id']; ?></td>
            <td><?php echo $row['crop_name']; ?></td>
            <td><?php echo $row['grade']; ?></td>
            <td><?php echo $row['weight']; ?></td>
            <td><?php echo $row['packaging_type']; ?></td>
            <td><?php echo $row['packaging_date']; ?></td>
            <td>
              <a href="quality-report.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<footer class="text-center mt-4 py-3" style="background-color: rgba(255,255,255,0.2); color: white;">
  &copy; 2025 Eco Harvest
</footer>

</body>
</html>
