<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EcoHarvest - Supply Chain</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    /* Nav & Button Hover/Active Effect */
    nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
      padding: 0;
    }

    nav ul li a {
      text-decoration: none;
      color: white;
      padding: 8px 15px;
      font-weight: 500;
      position: relative;
      transition: all 0.3s ease;
      border-radius: 50px;  /* Round indicator */
    }

    nav ul li a:hover,
    nav ul li a.active {
      background-color: rgba(40, 167, 69, 0.8);
      color: #fff !important;
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
      border-radius: 50px;  /* Keep the round effect on hover and active */
    }

    /* Reuse style for Add Button */
    .btn-eco {
      background-color: rgba(46, 139, 87, 0.9);
      border: none;
      transition: all 0.3s ease;
    }

    .btn-eco:hover {
      background-color: #2e8b57;
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f0f4f1;
      color: white;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: rgba(46, 139, 87, 0.9);
      padding: 20px 50px;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.3s ease;
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
      color: white;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }

    .hero {
      padding: 200px 20px 100px;
      background: url('image/backgraund.png') no-repeat center center/cover;
      position: relative;
      min-height: 100vh;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: 0;
    }

    .hero-content {
      position: relative;
      z-index: 1;
      max-width: 1000px;
      margin: auto;
    }

    .form-title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
      text-align: center;
    }

    .form-control {
      background-color: rgba(255, 255, 255, 0.9);
      color: #000;
    }

    .form-section, .table-section {
      background-color: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(6px);
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 40px;
    }

    .table thead th {
      background-color: rgba(46, 139, 87, 0.6);
      color: white;
    }

    .table tbody td {
      background-color: rgba(255, 255, 255, 0.9);
      color: black;
    }

    .footer {
      text-align: center;
      padding: 20px;
      font-size: 14px;
      background-color: rgba(255, 255, 255, 0.05);
      color: #ddd;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .modal-content {
      background: rgba(0, 0, 0, 0.8);
      color: white;
      backdrop-filter: blur(5px);
      border-radius: 10px;
    }

    @media (max-width: 768px) {
      nav ul {
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }
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
                    <li class="nav-item"><a href="transport.php" class="nav-link"><i class="fas fa-shipping-fast me-1"></i> Transportation</a></li>
                    <li class="nav-item"><a href="supplychain.php" class="nav-link active"><i class="fas fa-truck me-1"></i> Supply Chain</a></li>
                    <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<section class="hero">
  <div class="hero-content">

    <div class="form-section">
      <h2 class="form-title">Supply Chain</h2>
<form class="row g-3">
  <div class="col-md-4">
    <label for="stage" class="form-label">Stage</label>
    <select class="form-control" id="stage">
      <option value="">Select Stage</option>
      <option value="Grading">Grading</option>
      <option value="Packaging">Packaging</option>
      <option value="Transport">Transport</option>
    </select>
  </div>
  <div class="col-md-4">
    <label for="status" class="form-label">Status</label>
    <select class="form-control" id="status">
      <option value="">Select Status</option>
      <option value="Completed">Completed</option>
      <option value="In Progress">In Progress</option>
      <option value="In Route">In Route</option>
    </select>
  </div>
  <div class="col-md-4">
    <label for="location" class="form-label">Location</label>
    <input type="text" class="form-control" id="location" placeholder="Enter Location">
  </div>
  <div class="col-md-12 text-center">
    <button type="button" onclick="addRow()" class="btn btn-eco px-4 mt-3">Submit</button>
  </div>
</form>

    </div>

    <div class="table-section">
      <h2 class="form-title">📋 Supply Chain Tracking Table</h2>
<table class="table table-bordered text-center" id="dataTable">
  <thead>
    <tr>
      <th>No</th>
      <th>Stage</th>
      <th>Status</th>
      <th>Location</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody id="tableBody"></tbody>
</table>

    </div>

  </div>
</section>

<!-- Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title">Edit Graded Product</h5>
        <button type="button" class="btn-close bg-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="editGrade" class="form-label">Grade</label>
          <input type="text" id="editGrade" class="form-control" placeholder="Edit Grade">
        </div>
        <div class="mb-3">
          <label for="editProduct" class="form-label">Product</label>
          <input type="text" id="editProduct" class="form-control" placeholder="Edit Product">
        </div>
        <div class="mb-3">
          <label for="editWeight" class="form-label">Weight (kg)</label>
          <input type="text" id="editWeight" class="form-control" placeholder="Edit Weight (kg)">
        </div>
        <div class="mb-3">
          <label for="editTrack" class="form-label">Tracking Info</label>
          <input type="text" id="editTrack" class="form-control" placeholder="Edit Tracking Info">
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="applyEdit()">Update</button>
      </div>
    </div>
  </div>
</div>

<footer class="footer">
  &copy; 2025 EcoHarvest. All rights reserved.
</footer>

<script>
  let count = 1;
  let currentRow = null;

function addRow() {
  const stage = document.getElementById('stage').value.trim();
  const status = document.getElementById('status').value.trim();
  const location = document.getElementById('location').value.trim();

  if (!stage || !status || !location) {
    alert("Please fill all fields!");
    return;
  }

  const table = document.getElementById("tableBody");
  const row = table.insertRow();
  row.innerHTML = `
    <td>${count++}</td>
    <td>${stage}</td>
    <td>${status}</td>
    <td>${location}</td>
    <td>
      <button class="btn btn-warning" onclick="editRow(this)">Edit</button>
      <button class="btn btn-danger" onclick="deleteRow(this)">Delete</button>
    </td>
  `;

  document.getElementById('stage').value = '';
  document.getElementById('status').value = '';
  document.getElementById('location').value = '';
}


  function editRow(button) {
    const row = button.parentElement.parentElement;
    currentRow = row;

    document.getElementById('editGrade').value = row.cells[1].textContent;
    document.getElementById('editProduct').value = row.cells[2].textContent;
    document.getElementById('editWeight').value = row.cells[3].textContent;
    document.getElementById('editTrack').value = row.cells[4].textContent;

    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
  }

  function applyEdit() {
    const grade = document.getElementById('editGrade').value.trim();
    const product = document.getElementById('editProduct').value.trim();
    const weight = document.getElementById('editWeight').value.trim();
    const track = document.getElementById('editTrack').value.trim();

    if (!grade || !product || !weight || !track) {
      alert("Please fill all fields!");
      return;
    }

    currentRow.cells[1].textContent = grade;
    currentRow.cells[2].textContent = product;
    currentRow.cells[3].textContent = weight;
    currentRow.cells[4].textContent = track;

    const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
    modal.hide();
  }

  function deleteRow(button) {
    const row = button.parentElement.parentElement;
    row.remove();
  }
</script>

</body>
</html>