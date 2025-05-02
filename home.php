<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ecoharvest";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];

  if ($action === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
      $_SESSION['user'] = $email;
      header("Location: dashboard.php");
      exit();
    } else {
      echo "<script>alert('Invalid credentials');</script>";
    }
  } elseif ($action === 'signup') {
    $name = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
      echo "<script>alert('Email already registered');</script>";
    } else {
      $insert = "INSERT INTO users (fullname, email, password) VALUES ('$name', '$email', '$password')";
      if (mysqli_query($conn, $insert)) {
        echo "<script>alert('Signup successful! You can now login.');</script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ toggleForm('login'); });</script>";
      } else {
        echo "<script>alert('Signup failed');</script>";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Eco Harvest - Home</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    #bgVideo {
      position: fixed;
      top: 0; left: 0;
      width: 100vw; height: 100vh;
      object-fit: cover;
      z-index: -1;
    }
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      overflow-x: hidden;
    }
  .navbar {
    background: transparent;  /* Remove background color */
    padding: 8px 15px;  /* Reduced padding for smaller navbar */
    box-shadow: none;  /* Remove box-shadow */
  }

.navbar-brand {
  font-weight: 700;
  font-size: 2rem; /* slightly smaller */
  color: #fff;
}

.navbar img {
  height: 50px !important; /* smaller logo */
  margin-right: 8px;
}

    .btn-login {
      background-color: #fff;
      color: #28a745;
      font-weight: 600;
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s;
      border: none;
    }
    .btn-login:hover {
      background-color: #218838;
      color: #fff;
    }
    .hero-content {
      height: calc(100vh - 80px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: #fff;
      padding: 20px;
    }
    .hero-content h1 {
      font-size: 3.5rem;
      font-weight: 700;
    }
    .hero-content p {
      font-size: 1.5rem;
      margin-top: 15px;
    }
    .modal-content {
      background: transparent;
      border: none;
    }
    .login-container {
      background: rgba(255, 255, 255, 0.97);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      max-width: 420px;
      width: 100%;
    }
    .form-label {
      font-weight: 600;
    }
    .btn-custom:hover {
      background-color: #218838;
      transform: scale(1.05);
    }
    .form-toggle {
      cursor: pointer;
      color: #28a745;
      font-weight: bold;
    }
    
  </style>
</head>
<body>

<!-- Background Video -->
<video autoplay muted loop playsinline id="bgVideo">
  <source src="image/Untitled design (2).mp4" type="video/mp4">
</video>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="d-flex justify-content-center align-items-center w-100">
      <img src="image/Logo.PNG" style="height: 60px; margin-right: 10px;">
      <a class="navbar-brand mb-0" href="#">Eco Harvest</a>
    </div>
    <button class="btn btn-login ml-auto" data-toggle="modal" data-target="#authModal">Login</button>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-content">
  <h1 class="animate__animated animate__fadeInDown">Welcome to Eco Harvest</h1>
  <p class="animate__animated animate__fadeInUp animate__delay-1s">This is the future of agriculture</p>
</section>

<!-- Our Services -->
<section class="py-5" style="background-color: rgba(255,255,255,0.9);">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="font-weight-bold text-success">OUR SERVICES</h2>
      <p class="text-muted">Food Grading • Packaging • Transport</p>
    </div>
    <div class="row">
      <div class="col-md-4 mb-4">
        <img src="image/Grading.jpg" class="img-fluid rounded shadow" style="height: 250px; object-fit: cover;">
        <h4 class="text-success font-weight-bold mt-3">Grading</h4>
        <p>We ensure each product is assessed based on industry standards.</p>
      </div>
      <div class="col-md-4 mb-4">
        <img src="image/Packaging.jpg" class="img-fluid rounded shadow" style="height: 250px; object-fit: cover;">
        <h4 class="text-success font-weight-bold mt-3">Packaging</h4>
        <p>Packaging methods preserve freshness and reduce waste, aligning with our commitment to sustainability.</p>
      </div>
      <div class="col-md-4 mb-4">
        <img src="image/Transport.jpg" class="img-fluid rounded shadow" style="height: 250px; object-fit: cover;">
        <h4 class="text-success font-weight-bold mt-3">Transport</h4>
        <p>Our logistics network ensures timely delivery across all regions.</p>
      </div>
    </div>
  </div>
</section>

<!-- Auth Modal -->
<div class="modal fade" id="authModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content animate__animated animate__zoomIn">
      <div class="modal-body">
        <div class="login-container text-center">
          <!-- Login Form -->
          <div id="loginForm">
            <h4 class="mb-4">Login to Eco Harvest</h4>
            <form method="POST" action="">
              <input type="hidden" name="action" value="login">
              <div class="form-group text-left">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="form-group text-left">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success btn-block btn-custom">Login</button>
            </form>
            <p class="mt-3">Don't have an account? <span class="form-toggle" onclick="toggleForm('signup')">Sign Up</span></p>
            <a href="forgot_password.php" class="form-toggle">Forgot Password?</a>
          </div>

          <!-- Signup Form -->
          <div id="signupForm" style="display:none;">
            <h4 class="mb-4">Create Your Account</h4>
            <form method="POST" action="">
              <input type="hidden" name="action" value="signup">
              <div class="form-group text-left">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
              </div>
              <div class="form-group text-left">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="form-group text-left">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success btn-block btn-custom">Sign Up</button>
            </form>
            <p class="mt-3">Already have an account? <span class="form-toggle" onclick="toggleForm('login')">Login</span></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>
<script>
  function toggleForm(type) {
    if(type === 'signup') {
      document.getElementById('loginForm').style.display = 'none';
      document.getElementById('signupForm').style.display = 'block';
    } else {
      document.getElementById('signupForm').style.display = 'none';
      document.getElementById('loginForm').style.display = 'block';
    }
    $('#authModal').modal('show');
  }
</script>
</body>
</html>
