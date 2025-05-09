<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('index.php');
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// Check if result ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin_dashboard.php?tab=results');
}

$result_id = sanitize($_GET['id']);

// Get quiz result details
$result_query = "SELECT r.*, u.user_id, u.full_name, u.email, u.course_details, u.created_at as user_registered 
                FROM quiz_results r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.result_id = $result_id";
$result = $conn->query($result_query);

if ($result->num_rows == 0) {
    redirect('admin_dashboard.php?tab=results');
}

$quiz_data = $result->fetch_assoc();

// Properly initialize variables from quiz_data with fallback values
$total_easy_questions = isset($quiz_data['easy_questions']) ? (int)$quiz_data['easy_questions'] : 10;
$total_avg_questions = isset($quiz_data['average_questions']) ? (int)$quiz_data['average_questions'] : 10;
$total_difficult_questions = isset($quiz_data['difficult_questions']) ? (int)$quiz_data['difficult_questions'] : 10;

$correct_easy = isset($quiz_data['easy_score']) ? (int)$quiz_data['easy_score'] : 0;
$correct_avg = isset($quiz_data['average_score']) ? (int)$quiz_data['average_score'] : 0;
$correct_difficult = isset($quiz_data['difficult_score']) ? (int)$quiz_data['difficult_score'] : 0;

// Calculate percentages
$easy_percentage = $total_easy_questions > 0 ? ($correct_easy / $total_easy_questions) * 100 : 0;
$avg_percentage = $total_avg_questions > 0 ? ($correct_avg / $total_avg_questions) * 100 : 0;
$difficult_percentage = $total_difficult_questions > 0 ? ($correct_difficult / $total_difficult_questions) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result Details - ICT Quiz System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #121212;
            --secondary-dark: #1e1e1e;
            --tertiary-dark: #2d2d2d;
            --accent-blue: #4e54c8;
            --accent-purple: #8f94fb;
            --accent-success: #00b894;
            --accent-danger: #e74c3c;
            --accent-warning: #f39c12;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --border-radius: 10px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-bottom: 30px;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            border-radius: var(--border-radius);
            box-shadow: 0 10px 20px var(--shadow-color);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
            animation: shimmer 15s infinite linear;
            pointer-events: none;
        }

        @keyframes shimmer {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .admin-info {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .admin-info p {
            margin-right: 15px;
            font-weight: 500;
        }

        .admin-info a {
            padding: 8px 16px;
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all var(--transition-speed);
            font-weight: 500;
        }

        .admin-info a:hover {
            background-color: rgba(0, 0, 0, 0.4);
            transform: translateY(-2px);
        }

        .card {
            background-color: var(--tertiary-dark);
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, 
                        transparent 0%, 
                        rgba(255, 255, 255, 0.03) 50%, 
                        transparent 100%);
            z-index: 1;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header h2 {
            position: relative;
            display: inline-block;
            padding-bottom: 5px;
            margin: 0;
            font-size: 1.5rem;
        }

        .card-header h2::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            height: 3px;
            width: 60px;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
        }

        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.4);
        }

        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            border-radius: 8px;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .info-item h3 {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .info-item p {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .score-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .score-item {
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .score-item h4 {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .score-item .score-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .progress-container {
            height: 8px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .progress-text {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 5px;
            text-align: right;
        }

        .date-taken-container {
            margin-top: 30px;
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .date-taken-container i {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--accent-purple);
        }

        .date-taken-container strong {
            font-weight: 600;
            margin-right: 10px;
            color: var(--text-secondary);
        }

        .date-taken-container span {
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .user-info,
            .score-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-info {
                margin-top: 15px;
            }
            
            .user-info,
            .score-summary {
                grid-template-columns: 1fr;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--secondary-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--tertiary-dark);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-blue);
        }

        /* Animation for progress bars */
        @keyframes progressAnimation {
            0% { width: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-poll-h"></i> Quiz Result Details</h1>
            <div class="admin-info">
                <p><i class="fas fa-user-shield"></i> Logged in as: <?php echo $admin_email; ?></p>
                <a href="admin_dashboard.php?tab=results"><i class="fas fa-arrow-left"></i> Back to Results</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user"></i> User Information</h2>
            </div>
            <div class="user-info">
                <div class="info-item">
                    <h3>Full Name</h3>
                    <p><?php echo $quiz_data['full_name']; ?></p>
                </div>
                <div class="info-item">
                    <h3>Email</h3>
                    <p><?php echo $quiz_data['email']; ?></p>
                </div>
                <div class="info-item">
                    <h3>Course</h3>
                    <p><?php echo $quiz_data['course_details']; ?></p>
                </div>
                <div class="info-item">
                    <h3>Registered On</h3>
                    <p><?php echo date('M d, Y h:i A', strtotime($quiz_data['user_registered'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Quiz Performance Summary</h2>
                <div class="actions">
                    <span style="color: <?php echo $quiz_data['percentage'] >= 75 ? 'var(--accent-success)' : ($quiz_data['percentage'] >= 50 ? 'var(--accent-warning)' : 'var(--accent-danger)'); ?>; font-size: 1.5rem; font-weight: bold;">
                        <?php echo number_format($quiz_data['percentage'], 2); ?>%
                    </span>
                </div>
            </div>
            
            <div class="score-summary">
                <div class="score-item">
                    <h4>Total Score</h4>
                    <p class="score-value"><?php echo $quiz_data['total_score']; ?>/<?php echo $quiz_data['total_questions']; ?></p>
                </div>
                <div class="score-item">
                    <h4>Easy Questions</h4>
                    <p class="score-value"><?php echo $correct_easy; ?>/<?php echo $total_easy_questions; ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $easy_percentage; ?>%;"></div>
                    </div>
                    <div class="progress-text"><?php echo number_format($easy_percentage, 1); ?>%</div>
                </div>
                <div class="score-item">
                    <h4>Average Questions</h4>
                    <p class="score-value"><?php echo $correct_avg; ?>/<?php echo $total_avg_questions; ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $avg_percentage; ?>%;"></div>
                    </div>
                    <div class="progress-text"><?php echo number_format($avg_percentage, 1); ?>%</div>
                </div>
                <div class="score-item">
                    <h4>Difficult Questions</h4>
                    <p class="score-value"><?php echo $correct_difficult; ?>/<?php echo $total_difficult_questions; ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $difficult_percentage; ?>%;"></div>
                    </div>
                    <div class="progress-text"><?php echo number_format($difficult_percentage, 1); ?>%</div>
                </div>
            </div>
            
            <div class="date-taken-container">
                <i class="fas fa-calendar-alt"></i>
                <strong>Date Taken:</strong>
                <span><?php echo date('F d, Y h:i A', strtotime($quiz_data['taken_at'])); ?></span>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to progress bars
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                    bar.style.animation = 'progressAnimation 1s ease-out forwards';
                }, 300);
            });
        });
    </script>
</body>
</html>