<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecoharvest";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Add record
if (isset($_POST['add'])) {
  $batch_id = $_POST['batch_id'];
  $crop_name = $_POST['crop_name'];
  $grade = $_POST['grade'];
  $weight = $_POST['weight'];
  $packaging_type = $_POST['packaging_type'];
  $packaging_date = $_POST['packaging_date'];

  $conn->query("INSERT INTO quality_report (batch_id, crop_name, grade, weight, packaging_type, packaging_date)
                VALUES ('$batch_id', '$crop_name', '$grade', '$weight', '$packaging_type', '$packaging_date')");
  header("Location: quality-report.php");
}

// Delete record
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $conn->query("DELETE FROM quality_report WHERE id=$id");
  header("Location: quality-report.php");
}

// Search
$search = "";
if (isset($_POST['search'])) {
  $search = $_POST['search'];
  $result = $conn->query("SELECT * FROM quality_report WHERE 
    batch_id LIKE '%$search%' OR 
    crop_name LIKE '%$search%' OR 
    grade LIKE '%$search%' OR 
    weight LIKE '%$search%' OR 
    packaging_type LIKE '%$search%' OR 
    packaging_date LIKE '%$search%' 
    ORDER BY id DESC");
} else {
  $result = $conn->query("SELECT * FROM quality_report ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Eco Harvest | Quality Report</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #3c3c3c;
      color: white;
    }

    header {
      background-color: #2e8b57;
      padding: 15px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
      color: white;
    }

    nav ul {
      list-style: none;
      display: flex;
      gap: 25px;
      margin: 0;
      padding: 0;
    }

    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 5px 10px;
    }

    nav ul li a:hover {
      background-color: #256b45;
      border-radius: 4px;
    }

    .container {
      max-width: 900px;
      margin: 80px auto 40px;
      padding: 20px;
      background-color: #4f4f4f;
      border-radius: 10px;
    }

    .container h2 {
      text-align: center;
      margin-bottom: 30px;
    }

    .form-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }

    .form-group input[type="text"],
    .form-group input[type="date"] {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 5px;
      font-size: 14px;
      min-width: 150px;
    }

    .form-group button {
      background-color: #2e8b57;
      color: white;
      border: none;
      padding: 10px 20px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
    }

    .form-group button:hover {
      background-color: #256b45;
    }

    .search-bar {
      display: flex;
      justify-content: center;
      margin-bottom: 15px;
    }

    .search-bar input {
      padding: 8px;
      width: 60%;
      border-radius: 5px;
      border: none;
      font-size: 14px;
      margin-right: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table thead {
      background-color: #2e8b57;
    }

    table thead th {
      padding: 12px;
      color: white;
    }

    table tbody td {
      padding: 10px;
      text-align: center;
      background-color: #5f5f5f;
    }

    .actions a {
      padding: 5px 10px;
      margin: 0 3px;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      color: white;
      background-color: #dc3545;
    }

    .actions a:hover {
      background-color: #c82333;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">Eco Harvest</div>
  <nav>
    <ul>
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="quality-report.php">Quality Report</a></li>
      <li><a href="#">Graded Produced</a></li>
      <li><a href="#">Packaging</a></li>
      <li><a href="#">Transportation</a></li>
      <li><a href="#">Logout</a></li>
    </ul>
  </nav>
</header>

<div class="container">
  <h2>📋 Add Quality Report</h2>

  <form method="POST" class="form-group">
    <input type="text" name="batch_id" placeholder="e.g. B001" required>
    <input type="text" name="crop_name" placeholder="e.g. Tomato" required>
    <input type="text" name="grade" placeholder="e.g. Grade A" required>
    <input type="text" name="weight" placeholder="e.g. 2 kg" required>
    <input type="text" name="packaging_type" placeholder="e.g. Eco Box" required>
    <input type="date" name="packaging_date" required>
    <button type="submit" name="add">Add</button>
  </form>

  <!-- Search Bar -->
  <form method="POST" class="search-bar">
    <input type="text" name="search" placeholder="Search by any field..." value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Batch ID</th>
        <th>Crop</th>
        <th>Grade</th>
        <th>Weight</th>
        <th>Packaging</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $count = 1;
      while ($row = $result->fetch_assoc()):
      ?>
      <tr>
        <td><?php echo $count++; ?></td>
        <td><?php echo $row['batch_id']; ?></td>
        <td><?php echo $row['crop_name']; ?></td>
        <td><?php echo $row['grade']; ?></td>
        <td><?php echo $row['weight']; ?></td>
        <td><?php echo $row['packaging_type']; ?></td>
        <td><?php echo $row['packaging_date']; ?></td>
        <td class="actions">
          <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>
