<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecoharvest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete action
if(isset($_POST['delete_transport'])) {
    $transport_id = $_POST['transport_id'];
    $sql = "DELETE FROM transports WHERE transport_id = '$transport_id'";
    
    if ($conn->query($sql) === TRUE) {
        $delete_message = "Transport record deleted successfully";
    } else {
        $delete_message = "Error deleting record: " . $conn->error;
    }
}

// Handle add action
if(isset($_POST['add_transport'])) {
    $transport_id = $_POST['transport_id'];
    $driver_name = $_POST['driver_name'];
    $driver_id = $_POST['driver_id'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $status = $_POST['status'];
    
    $sql = "INSERT INTO transports (transport_id, driver_name, driver_id, origin, destination, status) 
            VALUES ('$transport_id', '$driver_name', '$driver_id', '$origin', '$destination', '$status')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "New transport record added successfully";
    } else {
        $message = "Error adding record: " . $conn->error;
    }
}

// Handle update action
if(isset($_POST['update_transport'])) {
    $transport_id = $_POST['transport_id'];
    $driver_name = $_POST['driver_name'];
    $driver_id = $_POST['driver_id'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $status = $_POST['status'];
    
    $sql = "UPDATE transports SET 
            driver_name = '$driver_name',
            driver_id = '$driver_id',
            origin = '$origin',
            destination = '$destination',
            status = '$status'
            WHERE transport_id = '$transport_id'";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Transport record updated successfully";
    } else {
        $message = "Error updating record: " . $conn->error;
    }
}

// Fetch transport data
$sql = "SELECT * FROM transports";
$result = $conn->query($sql);

// Check for success/error messages from add or update operations
$message = '';
if(isset($_GET['message'])) {
    $message = $_GET['message'];
}
if(isset($_GET['error'])) {
    $message = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Green Harvest - Transport Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Arial', sans-serif;
    }

    body {
      color: #333;
      margin: 0;
      padding: 0;
    }

    /* Background with sunset image */
    .background-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
    }
    
    .background-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .overlay {
      background-color: rgba(0, 0, 0, 0.5);
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 50px;
      background-color: transparent;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 100;
      transition: all 0.3s ease;
    }

    header.scrolled {
      background-color: rgba(222, 140, 89, 0.9);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .logo {
      font-size: 24px;
      font-weight: bold;
      color: white;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
    }

    nav ul {
      display: flex;
      list-style: none;
    }

    nav ul li {
      margin-left: 30px;
    }

    nav ul li a {
      text-decoration: none;
      color: white;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 5px 0;
      position: relative;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    nav ul li a:hover {
      color: #ffd700;
    }

    nav ul li a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: #ffd700;
      transition: width 0.3s ease;
    }

    nav ul li a:hover::after {
      width: 100%;
    }

    .main-content {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 100px 20px;
    }

    /* Transport Table Styles */
    .transport-container {
      background-color: rgba(255, 255, 255, 0.85);
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 1200px;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      position: relative;
    }

    .table-title {
      color: #e67e22;
      font-size: 28px;
      font-weight: bold;
      margin: 0 auto;
      text-align: center;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
    }

    .add-transport-btn {
      background-color: #e67e22;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
      position: absolute;
      left: 0;
    }

    .add-transport-btn:hover {
      background-color: #d35400;
      transform: translateY(-2px);
    }
    
    .search-container {
      display: flex;
      align-items: center;
      gap: 10px;
      position: absolute;
      right: 0;
    }

    #searchInput {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 200px;
      background-color: rgba(255, 255, 255, 0.8);
    }

    .search-btn {
      background-color: #e67e22;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .search-btn:hover {
      background-color: #d35400;
    }

    .transport-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .transport-table th {
      background-color: #e67e22;
      color: white;
      padding: 12px;
      text-align: left;
    }

    .transport-table tr {
      border-bottom: 1px solid #ddd;
    }

    .transport-table tr:nth-child(even) {
      background-color: rgba(255, 255, 255, 0.7);
    }

    .transport-table tr:nth-child(odd) {
      background-color: rgba(255, 255, 255, 0.9);
    }

    .transport-table td {
      padding: 12px;
      color: #333;
    }

    .status-pending {
      color: #f39c12;
      font-weight: bold;
    }

    .status-in-transit {
      color: #3498db;
      font-weight: bold;
    }

    .status-delivered {
      color: #2ecc71;
      font-weight: bold;
    }

    .status-cancelled {
      color: #e74c3c;
      font-weight: bold;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .edit-btn {
      background-color: #3498db;
      color: white;
    }

    .delete-btn {
      background-color: #e74c3c;
      color: white;
    }

    .track-btn {
      background-color: #2ecc71;
      color: white;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.7);
    }

    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 25px;
      border-radius: 10px;
      width: 60%;
      max-width: 800px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .close:hover {
      color: #e74c3c;
      transform: rotate(90deg);
    }

    /* Map Modal */
    #mapContainer {
      height: 400px;
      width: 100%;
      margin-top: 20px;
      border-radius: 8px;
      overflow: hidden;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: #555;
    }

    .form-group input, .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: rgba(255, 255, 255, 0.8);
      transition: all 0.3s ease;
    }

    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: #e67e22;
      box-shadow: 0 0 5px rgba(230, 126, 34, 0.5);
    }

    .form-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 25px;
    }

    .submit-btn {
      background-color: #2ecc71;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .submit-btn:hover {
      background-color: #27ae60;
      transform: translateY(-2px);
    }

    .cancel-btn {
      background-color: #95a5a6;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .cancel-btn:hover {
      background-color: #7f8c8d;
      transform: translateY(-2px);
    }

    /* Alert message */
    .alert {
      padding: 12px 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-weight: bold;
    }

    .alert-success {
      background-color: rgba(46, 204, 113, 0.2);
      color: #27ae60;
      border-left: 4px solid #27ae60;
    }

    .alert-danger {
      background-color: rgba(231, 76, 60, 0.2);
      color: #c0392b;
      border-left: 4px solid #c0392b;
    }

    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 15px;
      }

      nav ul {
        margin-top: 15px;
        flex-wrap: wrap;
        justify-content: center;
      }

      nav ul li {
        margin: 5px 15px;
      }
      
      .transport-table {
        display: block;
        overflow-x: auto;
      }
      
      .modal-content {
        width: 90%;
        padding: 15px;
      }
      
      .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .search-container {
        margin-top: 15px;
        width: 100%;
        position: relative;
        right: auto;
        left: auto;
      }
      
      #searchInput {
        width: 100%;
      }
      
      .add-transport-btn {
        position: relative;
        left: auto;
        margin-bottom: 15px;
      }
      
      .action-buttons {
        flex-direction: column;
        gap: 5px;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <!-- Background Image -->
  <div class="background-container">
    <img src="https://storage.googleapis.com/a1aa/image/8a49b2f6-6aca-4c5f-334f-daf5afef20c3.jpg" alt="Sunset landscape background">
  </div>

  <!-- Header -->
  <header id="navbar">
    <div class="logo">Eco Harvest</div>
    <nav>
      <ul>
        <li><a href="#">Dashboard</a></li>
        <li><a href="#">Quality Report</a></li>
        <li><a href="#">Graded Products</a></li>
        <li><a href="#">Supply Chain</a></li>
        <li><a href="#">Quality Analysis</a></li>
        <li><a href="#">Packaging</a></li>
        <li><a href="#" class="active">Transportation</a></li>
        <li><a href="#">Logout</a></li>
      </ul>
    </nav>
  </header>

  <!-- Main Content -->
  <div class="main-content">
    <div class="transport-container glass-effect">
      <div class="table-header">
        <button class="add-transport-btn" onclick="openAddModal()">
          <i class="fas fa-plus"></i> Add Transport
        </button>
        <div class="table-title">Transport Management</div>
        <div class="search-container">
          <input type="text" id="searchInput" placeholder="Search...">
          <button class="search-btn" onclick="performSearch()">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>
      
      <?php if(!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <?php if(isset($delete_message)): ?>
        <div class="alert <?php echo strpos($delete_message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
          <?php echo $delete_message; ?>
        </div>
      <?php endif; ?>
      
      <table class="transport-table">
        <thead>
          <tr>
            <th>Transport ID</th>
            <th>Driver Name</th>
            <th>Driver ID</th>
            <th>From</th>
            <th>To</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $status_class = "";
              switch($row["status"]) {
                case "Pending":
                  $status_class = "status-pending";
                  break;
                case "In Transit":
                  $status_class = "status-in-transit";
                  break;
                case "Delivered":
                  $status_class = "status-delivered";
                  break;
                case "Cancelled":
                  $status_class = "status-cancelled";
                  break;
              }
              ?>
              <tr>
                <td><?php echo $row["transport_id"]; ?></td>
                <td><?php echo $row["driver_name"]; ?></td>
                <td><?php echo $row["driver_id"]; ?></td>
                <td><?php echo $row["origin"]; ?></td>
                <td><?php echo $row["destination"]; ?></td>
                <td class="<?php echo $status_class; ?>"><?php echo $row["status"]; ?></td>
                <td class="action-buttons">
                  <button class="btn edit-btn" onclick="openEditModal('<?php echo $row["transport_id"]; ?>', '<?php echo htmlspecialchars($row["driver_name"]); ?>', '<?php echo $row["driver_id"]; ?>', '<?php echo htmlspecialchars($row["origin"]); ?>', '<?php echo htmlspecialchars($row["destination"]); ?>', '<?php echo $row["status"]; ?>')">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this transport?');">
                    <input type="hidden" name="transport_id" value="<?php echo $row["transport_id"]; ?>">
                    <button type="submit" name="delete_transport" class="btn delete-btn">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                  <button class="btn track-btn" onclick="openTrackingModal('<?php echo $row["transport_id"]; ?>', '<?php echo htmlspecialchars($row["driver_name"]); ?>', '<?php echo htmlspecialchars($row["origin"]); ?>', '<?php echo htmlspecialchars($row["destination"]); ?>')">
                    <i class="fas fa-map-marker-alt"></i> Track
                  </button>
                </td>
              </tr>
              <?php
            }
          } else {
            echo "<tr><td colspan='7' style='text-align: center;'>No transport records found</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2>Edit Transport</h2>
      <form id="editForm" method="post" action="">
        <input type="hidden" id="edit_transport_id" name="transport_id">
        <div class="form-group">
          <label for="edit_driver_name">Driver Name:</label>
          <input type="text" id="edit_driver_name" name="driver_name" required>
        </div>
        <div class="form-group">
          <label for="edit_driver_id">Driver ID:</label>
          <input type="text" id="edit_driver_id" name="driver_id" required>
        </div>
        <div class="form-group">
          <label for="edit_origin">From:</label>
          <input type="text" id="edit_origin" name="origin" required>
        </div>
        <div class="form-group">
          <label for="edit_destination">To:</label>
          <input type="text" id="edit_destination" name="destination" required>
        </div>
        <div class="form-group">
          <label for="edit_status">Status:</label>
          <select id="edit_status" name="status" required>
            <option value="Pending">Pending</option>
            <option value="In Transit">In Transit</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-buttons">
          <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
          <button type="submit" name="update_transport" class="submit-btn">Update Transport</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAddModal()">&times;</span>
      <h2>Add New Transport</h2>
      <form id="addForm" method="post" action="">
        <div class="form-group">
          <label for="add_transport_id">Transport ID:</label>
          <input type="text" id="add_transport_id" name="transport_id" required>
        </div>
        <div class="form-group">
          <label for="add_driver_name">Driver Name:</label>
          <input type="text" id="add_driver_name" name="driver_name" required>
        </div>
        <div class="form-group">
          <label for="add_driver_id">Driver ID:</label>
          <input type="text" id="add_driver_id" name="driver_id" required>
        </div>
        <div class="form-group">
          <label for="add_origin">From:</label>
          <input type="text" id="add_origin" name="origin" required>
        </div>
        <div class="form-group">
          <label for="add_destination">To:</label>
          <input type="text" id="add_destination" name="destination" required>
        </div>
        <div class="form-group">
          <label for="add_status">Status:</label>
          <select id="add_status" name="status" required>
            <option value="Pending">Pending</option>
            <option value="In Transit">In Transit</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-buttons">
          <button type="button" class="cancel-btn" onclick="closeAddModal()">Cancel</button>
          <button type="submit" name="add_transport" class="submit-btn">Add Transport</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tracking Modal -->
  <div id="trackingModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeTrackingModal()">&times;</span>
      <h2>Tracking Transport: <span id="tracking_transport_id"></span></h2>
      <p><strong>Driver:</strong> <span id="tracking_driver_name"></span></p>
      <p><strong>Route:</strong> <span id="tracking_origin"></span> to <span id="tracking_destination"></span></p>
      <div id="mapContainer"></div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <script>
    // Navigation scroll effect
    function setupScrollEffect() {
      const navbar = document.getElementById("navbar");
      window.addEventListener("scroll", () => {
        navbar.classList.toggle("scrolled", window.scrollY > 50);
      });
    }

    // Search functionality
    function performSearch() {
      const query = document.getElementById("searchInput").value.toLowerCase();
      if (query.trim() !== "") {
        const rows = document.querySelectorAll(".transport-table tbody tr");
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          if (text.includes(query)) {
            row.style.display = "";
          } else {
            row.style.display = "none";
          }
        });
      }
    }

    // Edit Modal Functions
    function openEditModal(id, driverName, driverId, origin, destination, status) {
      document.getElementById("edit_transport_id").value = id;
      document.getElementById("edit_driver_name").value = driverName;
      document.getElementById("edit_driver_id").value = driverId;
      document.getElementById("edit_origin").value = origin;
      document.getElementById("edit_destination").value = destination;
      document.getElementById("edit_status").value = status;
      document.getElementById("editModal").style.display = "block";
    }

    function closeEditModal() {
      document.getElementById("editModal").style.display = "none";
    }

    // Add Modal Functions
    function openAddModal() {
      document.getElementById("addModal").style.display = "block";
    }

    function closeAddModal() {
      document.getElementById("addModal").style.display = "none";
    }

    // Tracking Modal Functions
    function openTrackingModal(id, driverName, origin, destination) {
      document.getElementById("tracking_transport_id").textContent = id;
      document.getElementById("tracking_driver_name").textContent = driverName;
      document.getElementById("tracking_origin").textContent = origin;
      document.getElementById("tracking_destination").textContent = destination;
      document.getElementById("trackingModal").style.display = "block";
      
      // Initialize map after a short delay to ensure the container is fully visible
      setTimeout(() => {
        initMap(origin, destination);
      }, 100);
    }

    function closeTrackingModal() {
      document.getElementById("trackingModal").style.display = "none";
    }

    // Initialize map for tracking
    function initMap(origin, destination) {
      // Clear any existing map
      const mapContainer = document.getElementById("mapContainer");
      mapContainer.innerHTML = '';
      
      // Create map
      const map = L.map('mapContainer').setView([40.7128, -74.0060], 10); // Default to NYC coordinates
      
      // Add tile layer (OpenStreetMap)
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);
      
      // Simulate origin and destination points
      // In a real application, you would get these coordinates from your database or geocoding API
      const originPoint = [40.7128, -74.0060]; // Example: NYC
      const destinationPoint = [39.9526, -75.1652]; // Example: Philadelphia
      
      // Add markers
      const originMarker = L.marker(originPoint).addTo(map)
        .bindPopup(`<b>Origin:</b> ${origin}`);
      
      const destinationMarker = L.marker(destinationPoint).addTo(map)
        .bindPopup(`<b>Destination:</b> ${destination}`);
      
      // Draw route line
      const routeLine = L.polyline([originPoint, destinationPoint], {color: '#e67e22'}).addTo(map);
      
      // Add current location marker (simulated)
      const currentPosition = [
        (originPoint[0] + destinationPoint[0]) / 2, 
        (originPoint[1] + destinationPoint[1]) / 2
      ];
      
      const truckIcon = L.divIcon({
        html: '<i class="fas fa-truck" style="font-size: 24px; color: #e67e22;"></i>',
        className: 'truck-icon',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      });
      
      const currentMarker = L.marker(currentPosition, {icon: truckIcon}).addTo(map)
        .bindPopup("<b>Current Location</b><br>In transit");
      
      // Fit bounds to show all markers
      const bounds = L.latLngBounds([originPoint, destinationPoint, currentPosition]);
      map.fitBounds(bounds, {padding: [50, 50]});
    }

    // Add event listener for Enter key in search box
    document.getElementById("searchInput").addEventListener("keyup", function(event) {
      if (event.key === "Enter") {
        performSearch();
      }
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
      const modals = document.getElementsByClassName("modal");
      for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
          modals[i].style.display = "none";
        }
      }
    }

    document.addEventListener("DOMContentLoaded", () => {
      setupScrollEffect();
    });
  </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>