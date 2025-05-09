<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('user_dashboard.php');
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = sanitize($_POST['full_name']);
    $age = sanitize($_POST['age']);
    $course_details = sanitize($_POST['course_details']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Simple form validation
    if (empty($full_name) || empty($age) || empty($course_details) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!is_numeric($age) || $age < 1) {
        $error = "Please enter a valid age";
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($check_query);
        
        if ($result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_query = "INSERT INTO users (full_name, age, course_details, email, password) 
                             VALUES ('$full_name', $age, '$course_details', '$email', '$hashed_password')";
            
            if ($conn->query($insert_query) === TRUE) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Quiz System - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }
        
        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.9),
                        0 0 20px rgba(255, 255, 255, 0.5);
            animation: twinkle var(--duration) infinite ease-in-out;
            opacity: var(--opacity);
        }
        
        @keyframes twinkle {
            0%, 100% {
                opacity: var(--opacity);
                transform: scale(1);
            }
            50% {
                opacity: var(--opacity-mid);
                transform: scale(1.2);
            }
        }
        
        .container {
            width: 100%;
            max-width: 480px;
            background: rgba(22, 22, 31, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            transform: translateY(0);
            transition: all 0.5s;
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: 1px solid rgba(79, 70, 229, 0.2);
        }
        
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }
        
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #6366F1, #8B5CF6, #EC4899);
        }
        
        .glow {
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.15);
            filter: blur(50px);
            pointer-events: none;
        }
        
        .glow-1 {
            top: -50px;
            left: -50px;
        }
        
        .glow-2 {
            bottom: -50px;
            right: -50px;
            background: rgba(236, 72, 153, 0.15);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2),
                        inset 0 0 20px rgba(99, 102, 241, 0.3);
            animation: pulse 3s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2),
                            inset 0 0 20px rgba(99, 102, 241, 0.3);
            }
            50% {
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3),
                            inset 0 0 25px rgba(99, 102, 241, 0.5);
            }
        }
        
        .logo i {
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 0 10px rgba(99, 102, 241, 0.6);
        }
        
        h1 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            display: inline-block;
        }
        
        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .input-group {
            position: relative;
            z-index: 1;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2),
                        inset 0 1px 3px rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 15px;
            color: rgba(99, 102, 241, 0.8);
            transition: all 0.3s;
            font-size: 1.2rem;
        }
        
        .input-group:focus-within .form-icon {
            color: #6366F1;
            transform: scale(1.1);
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #6366F1, #8B5CF6, #EC4899);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 35px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.6);
        }
        
        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.4);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .links {
            margin-top: 25px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
        }
        
        .links a {
            color: #6366F1;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }
        
        .links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: #6366F1;
            transition: width 0.3s ease;
        }
        
        .links a:hover {
            color: #8B5CF6;
            text-shadow: 0 0 5px rgba(139, 92, 246, 0.5);
        }
        
        .links a:hover::after {
            width: 100%;
        }
        
        .error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            border-left: 4px solid #ef4444;
            display: flex;
            align-items: center;
            backdrop-filter: blur(4px);
        }
        
        .error i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            border-left: 4px solid #10b981;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 10px;
            backdrop-filter: blur(4px);
        }
        
        .success i {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .form-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 30px;
            padding-top: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            input[type="text"],
            input[type="number"],
            input[type="email"],
            input[type="password"] {
                padding: 12px 12px 12px 45px;
            }
            
            .btn {
                padding: 14px;
            }
            
            .logo {
                width: 70px;
                height: 70px;
            }
            
            .logo i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>
    
    <div class="container">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
        
        <div class="header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>ICT Quiz Registration</h1>
            <p class="subtitle">Join the challenge and test your knowledge</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
                <p><a href="index.php">Go to Login</a></p>
            </div>
        <?php else: ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-group">
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                        <i class="fas fa-user form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="age">Age</label>
                    <div class="input-group">
                        <input type="number" id="age" name="age" placeholder="Enter your age" required>
                        <i class="fas fa-birthday-cake form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="course_details">Course/Year/Section</label>
                    <div class="input-group">
                        <input type="text" id="course_details" name="course_details" placeholder="e.g., BSIT-3A" required>
                        <i class="fas fa-graduation-cap form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        <i class="fas fa-envelope form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Create a secure password" required>
                        <i class="fas fa-lock form-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <i class="fas fa-check-circle form-icon"></i>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn">Register Now</button>
            </form>
            
            <div class="links">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
            
            <div class="form-footer">
                <p>© <?php echo date('Y'); ?> ICT Quiz System. All rights reserved.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Create twinkling stars effect
        const starsContainer = document.getElementById('stars');
        const starsCount = 100;
        
        for (let i = 0; i < starsCount; i++) {
            const star = document.createElement('div');
            star.classList.add('star');
            
            // Random position
            const x = Math.random() * 100;
            const y = Math.random() * 100;
            
            // Random size and animation
            const size = Math.random() * 3 + 1;
            const duration = Math.random() * 3 + 2 + 's';
            const opacity = Math.random() * 0.5 + 0.3;
            const opacityMid = opacity + (Math.random() * 0.3);
            
            star.style.left = `${x}%`;
            star.style.top = `${y}%`;
            star.style.width = `${size}px`;
            star.style.height = `${size}px`;
            star.style.setProperty('--duration', duration);
            star.style.setProperty('--opacity', opacity);
            star.style.setProperty('--opacity-mid', opacityMid);
            
            starsContainer.appendChild(star);
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>