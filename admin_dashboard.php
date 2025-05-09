<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('index.php');
}

// Get admin info
$admin_id = $_SESSION['admin_id'];


// Set default active tab
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : (isset($_POST['active_tab']) ? sanitize($_POST['active_tab']) : 'stats');

// Handle question operations
$message = '';
$error = '';

// Add new question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $question_text = sanitize($_POST['question_text']);
    $option_a = sanitize($_POST['option_a']);
    $option_b = sanitize($_POST['option_b']);
    $option_c = sanitize($_POST['option_c']);
    $option_d = sanitize($_POST['option_d']);
    $correct_answer = sanitize($_POST['correct_answer']);
    $difficulty_level = sanitize($_POST['difficulty_level']);
    
    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answer)) {
        $error = "All fields are required";
    } else {
        $insert_query = "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, difficulty_level) 
                        VALUES ('$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_answer', '$difficulty_level')";
        
        if ($conn->query($insert_query) === TRUE) {
            $message = "Question added successfully";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    // Make sure to stay on questions tab
    $active_tab = 'questions';
}

// Edit question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_question'])) {
    $question_id = sanitize($_POST['question_id']);
    $question_text = sanitize($_POST['question_text']);
    $option_a = sanitize($_POST['option_a']);
    $option_b = sanitize($_POST['option_b']);
    $option_c = sanitize($_POST['option_c']);
    $option_d = sanitize($_POST['option_d']);
    $correct_answer = sanitize($_POST['correct_answer']);
    $difficulty_level = sanitize($_POST['difficulty_level']);
    
    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answer)) {
        $error = "All fields are required";
    } else {
        $update_query = "UPDATE questions 
                        SET question_text = '$question_text', 
                            option_a = '$option_a', 
                            option_b = '$option_b', 
                            option_c = '$option_c', 
                            option_d = '$option_d', 
                            correct_answer = '$correct_answer', 
                            difficulty_level = '$difficulty_level' 
                        WHERE question_id = $question_id";
        
        if ($conn->query($update_query) === TRUE) {
            $message = "Question updated successfully";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    // Make sure to stay on questions tab
    $active_tab = 'questions';
}

// Delete question
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $question_id = sanitize($_GET['delete']);
    
    $delete_query = "DELETE FROM questions WHERE question_id = $question_id";
    
    if ($conn->query($delete_query) === TRUE) {
        $message = "Question deleted successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
    // Make sure to stay on questions tab
    $active_tab = 'questions';
}

// Get all questions
// Get all questions
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;

// Set default to 'easy' if no filter selected
$filter_difficulty = isset($_GET['difficulty']) ? sanitize($_GET['difficulty']) : 'easy';

// Validate difficulty level
if (!in_array($filter_difficulty, ['easy', 'average', 'difficult'])) {
    $filter_difficulty = 'easy';
}

// Always filter by difficulty level
$where_clause = "WHERE difficulty_level = '$filter_difficulty'";

$count_query = "SELECT COUNT(*) as total FROM questions $where_clause";
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

$query = "SELECT * FROM questions $where_clause ORDER BY question_id DESC LIMIT $offset, $items_per_page";
$questions = $conn->query($query);
// Get question for editing
$edit_question = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $question_id = sanitize($_GET['edit']);
    $edit_query = "SELECT * FROM questions WHERE question_id = $question_id";
    $edit_result = $conn->query($edit_query);
    
    if ($edit_result->num_rows > 0) {
        $edit_question = $edit_result->fetch_assoc();
    }
    // Make sure to stay on questions tab
    $active_tab = 'questions';
}

// Get user statistics
$user_stats_query = "SELECT COUNT(*) as total_users FROM users";
$user_result = $conn->query($user_stats_query);
$total_users = $user_result->fetch_assoc()['total_users'];

$quiz_stats_query = "SELECT COUNT(*) as quizzes_taken FROM quiz_results";
$quiz_result = $conn->query($quiz_stats_query);
$quizzes_taken = $quiz_result->fetch_assoc()['quizzes_taken'];

$avg_score_query = "SELECT AVG(percentage) as avg_score FROM quiz_results";
$avg_result = $conn->query($avg_score_query);
$avg_score = $avg_result->fetch_assoc()['avg_score'];

// Get all quiz results
$results_query = "SELECT r.*, u.full_name, u.email, u.course_details 
                  FROM quiz_results r
                  JOIN users u ON r.user_id = u.user_id
                  ORDER BY r.taken_at DESC";
$results = $conn->query($results_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ICT Quiz System</title>
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
            max-width: 1400px;
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

        .admin-tabs {
            background-color: var(--secondary-dark);
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px var(--shadow-color);
            overflow: hidden;
        }

        .admin-tabs ul {
            display: flex;
            list-style: none;
            background: var(--tertiary-dark);
            padding: 0;
            margin: 0;
        }

        .admin-tabs ul li {
            flex: 1;
            text-align: center;
            transition: all var(--transition-speed);
        }

        .admin-tabs ul li a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 15px 0;
            display: block;
            font-weight: 500;
            transition: all var(--transition-speed);
            position: relative;
        }

        .admin-tabs ul li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            transition: width var(--transition-speed);
        }

        .admin-tabs ul li.active a,
        .admin-tabs ul li a:hover {
            color: var(--text-primary);
        }

        .admin-tabs ul li.active a::after,
        .admin-tabs ul li a:hover::after {
            width: 100%;
        }

        .tab-content {
            padding: 30px;
        }

        .success-message {
            background: linear-gradient(to right, rgba(0, 184, 148, 0.1), rgba(0, 184, 148, 0.2));
            border-left: 4px solid var(--accent-success);
            color: #2ecc71;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        .error-message {
            background: linear-gradient(to right, rgba(231, 76, 60, 0.1), rgba(231, 76, 60, 0.2));
            border-left: 4px solid var(--accent-danger);
            color: #e74c3c;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
            transition: transform var(--transition-speed);
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
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

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-item h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--text-secondary);
        }

        .stat-item p {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h2 {
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
            padding-bottom: 5px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 60px;
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
        }

        .question-form, .questions-filter {
            background-color: var(--tertiary-dark);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .form-group {
            margin-bottom: 15px;
        }
		/* Compact form styles */
.question-form textarea,
.question-form input[type="text"] {
    padding: 8px 12px;
    font-size: 14px;
    max-width: 500px;
}

.question-form .form-group {
    margin-bottom: 12px;
}

.question-form label {
    font-size: 14px;
    margin-bottom: 5px;
}
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select,
        .questions-filter select {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid #444;
            border-radius: 5px;
            color: var(--text-primary);
            transition: all var(--transition-speed);
        }
		/* Compact filter styles */
.questions-filter {
    padding: 12px 15px;
}

.questions-filter select {
    padding: 8px 12px;
    width: 120px;
    max-width: none;
}

.questions-filter .cancel-button {
    padding: 8px 12px;
    display: <?php echo ($filter_difficulty == 'easy') ? 'none' : 'inline-block'; ?>;
}
        /* Add this to your style section */
select[name="difficulty"] {
    color: #000000 !important;
    background-color: #ffffff !important;
    border: 1px solid #ccc !important;
}

select[name="difficulty"] option {
    color: #000000;
    background-color: #ffffff;
}
        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus,
        .questions-filter select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px rgba(78, 84, 200, 0.3);
        }

        button,
        .cancel-button {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed);
            margin-right: 10px;
            display: inline-block;
        }

        button[type="submit"] {
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            color: white;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.4);
        }

        .cancel-button {
            background-color: transparent;
            border: 1px solid #666;
            color: var(--text-secondary);
            text-decoration: none;
        }

        .cancel-button:hover {
            border-color: #999;
            color: var(--text-primary);
        }

        .questions-filter {
            display: flex;
            align-items: center;
            padding: 15px 25px;
        }

        .questions-filter h3 {
            margin: 0 15px 0 0;
            min-width: 120px;
        }

        .questions-filter form {
            display: flex;
            flex-grow: 1;
            gap: 15px;
        }

        .questions-filter select {
            max-width: 200px;
        }
		/* Center numbers in Questions and Correct Answers columns */
		table#quizResultsTable tbody tr td:nth-child(5),
		table#quizResultsTable tbody tr td:nth-child(6) {
			text-align: center;
		}
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: var(--tertiary-dark);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        th {
            background-color: rgba(0, 0, 0, 0.2);
            font-weight: 600;
            color: var(--accent-purple);
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }

        tr:last-child td {
            border-bottom: none;
        }

        table a {
            color: var(--accent-purple);
            margin-right: 10px;
            text-decoration: none;
            transition: all var(--transition-speed);
        }

        table a:hover {
            color: var(--accent-blue);
            text-decoration: underline;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .pagination ul {
            display: flex;
            list-style: none;
            background: var(--tertiary-dark);
            border-radius: 30px;
            padding: 5px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .pagination li {
            margin: 0 2px;
        }

        .pagination li a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-speed);
        }

        .pagination li a:hover,
        .pagination li.active a {
            background: linear-gradient(to right, var(--accent-blue), var(--accent-purple));
            color: white;
        }

        /* New styles for two-column layout in questions section */
        .questions-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .questions-left-column, .questions-right-column {
            display: flex;
            flex-direction: column;
        }

        .questions-right-column {
            height: fit-content;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .questions-container {
                grid-template-columns: 1fr;
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
            
            .admin-tabs ul {
                flex-direction: column;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .questions-filter {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .questions-filter h3 {
                margin-bottom: 10px;
            }
            
            .questions-filter form {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Glassmorphism effect */
        .glass-effect {
            background: rgba(46, 49, 65, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chalkboard-teacher"></i> ICT Assessment Manager</h1>
            <div class="admin-info">
               <!-- <p><i class="fas fa-user-shield"></i> Logged in as: <?php echo $admin_id; ?></p> -->
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-tabs">
            <ul>
                <li class="<?php echo $active_tab == 'stats' ? 'active' : ''; ?>" data-tab="stats"><a href="#stats"><i class="fas fa-chart-line"></i> Statistics</a></li>
                <li class="<?php echo $active_tab == 'questions' ? 'active' : ''; ?>" data-tab="questions"><a href="#questions"><i class="fas fa-question-circle"></i> Manage Questions</a></li>
                <li class="<?php echo $active_tab == 'results' ? 'active' : ''; ?>" data-tab="results"><a href="#results"><i class="fas fa-poll"></i> Quiz Results</a></li>
            </ul>
            
   <div id="stats" class="tab-content" style="display: <?php echo $active_tab == 'stats' ? 'block' : 'none'; ?>; opacity: 1;">
    <h2><i class="fas fa-chart-pie"></i> System Statistics</h2>
    <div class="stats-container">
        <div class="stat-item">
            <h3><i class="fas fa-users"></i> Total Users</h3>
            <p><?php echo $total_users; ?></p>
        </div>
        <div class="stat-item">
            <h3><i class="fas fa-tasks"></i> Quizzes Taken</h3>
            <p><?php echo $quizzes_taken; ?></p>
        </div>
        <div class="stat-item">
            <h3><i class="fas fa-percentage"></i> Average Score</h3>
            <p><?php echo number_format($avg_score, 2); ?>%</p>
        </div>
    </div>

    <h2 style="margin-top: 40px;"><i class="fas fa-user-graduate"></i> Students Quiz Status</h2>
    <?php
    // Get all users and their exam status
    $users_query = "SELECT u.user_id, u.full_name, u.course_details, u.email, 
                    CASE WHEN r.user_id IS NOT NULL THEN 'Taken' ELSE 'Not Taken' END as exam_status
                    FROM users u
                    LEFT JOIN quiz_results r ON u.user_id = r.user_id
                    GROUP BY u.user_id";
    $users_result = $conn->query($users_query);
    ?>
    
    <table style="margin-top: 20px;">
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Course</th>
                <th>Email</th>
                <th>Exam Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users_result->num_rows > 0): ?>
                <?php 
                $counter = 1;
                while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo $user['course_details']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <?php if ($user['exam_status'] == 'Taken'): ?>
                                <span style="color: var(--accent-success);">
                                    <i class="fas fa-check-circle"></i> Taken
                                </span>
                            <?php else: ?>
                                <span style="color: var(--accent-danger);">
                                    <i class="fas fa-times-circle"></i> Not Taken
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
            
            <div id="questions" class="tab-content" style="display: <?php echo $active_tab == 'questions' ? 'block' : 'none'; ?>; opacity: 1;">
                <h2><i class="fas fa-edit"></i> Manage Questions</h2>
                
                <div class="questions-container">
                    <!-- Left Column: Question Form -->
                    <div class="questions-left-column">
                        <div class="question-form glass-effect">
                            <h3><?php echo $edit_question ? '<i class="fas fa-pencil-alt"></i> Edit Question' : '<i class="fas fa-plus-circle"></i> Add New Question'; ?></h3>
                            <form method="post" action="" id="questionForm">
                                <!-- Hidden field to track active tab -->
                                <input type="hidden" name="active_tab" value="questions">
                                
                                <?php if ($edit_question): ?>
                                    <input type="hidden" name="question_id" value="<?php echo $edit_question['question_id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="question_text">Question Text:</label>
                                    <textarea id="question_text" name="question_text" rows="3" required><?php echo $edit_question ? $edit_question['question_text'] : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="option_a">Option A:</label>
                                    <input type="text" id="option_a" name="option_a" value="<?php echo $edit_question ? $edit_question['option_a'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="option_b">Option B:</label>
                                    <input type="text" id="option_b" name="option_b" value="<?php echo $edit_question ? $edit_question['option_b'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="option_c">Option C:</label>
                                    <input type="text" id="option_c" name="option_c" value="<?php echo $edit_question ? $edit_question['option_c'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="option_d">Option D:</label>
                                    <input type="text" id="option_d" name="option_d" value="<?php echo $edit_question ? $edit_question['option_d'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="correct_answer">Correct Answer:</label>
                                    <select id="correct_answer" name="correct_answer" required>
                                        <option value="A" <?php echo ($edit_question && $edit_question['correct_answer'] == 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo ($edit_question && $edit_question['correct_answer'] == 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="C" <?php echo ($edit_question && $edit_question['correct_answer'] == 'C') ? 'selected' : ''; ?>>C</option>
                                        <option value="D" <?php echo ($edit_question && $edit_question['correct_answer'] == 'D') ? 'selected' : ''; ?>>D</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="difficulty_level">Difficulty Level:</label>
                                    <select id="difficulty_level" name="difficulty_level" required>
                                        <option value="easy" <?php echo ($edit_question && $edit_question['difficulty_level'] == 'easy') ? 'selected' : ''; ?>>Easy</option>
                                        <option value="average" <?php echo ($edit_question && $edit_question['difficulty_level'] == 'average') ? 'selected' : ''; ?>>Average</option>
                                        <option value="difficult" <?php echo ($edit_question && $edit_question['difficulty_level'] == 'difficult') ? 'selected' : ''; ?>>Difficult</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="<?php echo $edit_question ? 'edit_question' : 'add_question'; ?>">
                                    <?php echo $edit_question ? '<i class="fas fa-save"></i> Update Question' : '<i class="fas fa-plus"></i> Add Question'; ?>
                                </button>
                                
                                <?php if ($edit_question): ?>
                                    <a href="?tab=questions" class="cancel-button"><i class="fas fa-times"></i> Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
						</div>
                    
                    <!-- Right Column: Questions List -->
                    <div class="questions-right-column">
						<div class="questions-filter glass-effect">
							<h3 style="margin: 0 10px 0 0; display: inline-block;"><i class="fas fa-filter"></i> Filter</h3>
							<form action="" method="get" style="display: inline-block;">
								<input type="hidden" name="tab" value="questions">
								<select name="difficulty" onchange="this.form.submit()">
									<option value="easy" <?php echo ($filter_difficulty == 'easy') ? 'selected' : ''; ?>>Easy</option>
									<option value="average" <?php echo ($filter_difficulty == 'average') ? 'selected' : ''; ?>>Average</option>
									<option value="difficult" <?php echo ($filter_difficulty == 'difficult') ? 'selected' : ''; ?>>Difficult</option>
								</select>
								<?php if ($filter_difficulty != 'easy'): ?>
									<a href="?tab=questions&difficulty=easy" class="cancel-button">
										<i class="fas fa-sync-alt"></i> Reset
									</a>
								<?php endif; ?>
							</form>
						</div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Difficulty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($questions->num_rows > 0): ?>
                                    <?php while ($question = $questions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $question['question_id']; ?></td>
                                            <td><?php echo strlen($question['question_text']) > 50 ? substr($question['question_text'], 0, 50) . '...' : $question['question_text']; ?></td>
                                            <td>
                                                <?php if ($question['difficulty_level'] == 'easy'): ?>
                                                    <span style="color: var(--accent-success)"><i class="fas fa-smile"></i> Easy</span>
                                                <?php elseif ($question['difficulty_level'] == 'average'): ?>
                                                    <span style="color: var(--accent-warning)"><i class="fas fa-meh"></i> Average</span>
                                                <?php else: ?>
                                                    <span style="color: var(--accent-danger)"><i class="fas fa-frown"></i> Difficult</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?tab=questions&edit=<?php echo $question['question_id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="?tab=questions&delete=<?php echo $question['question_id']; ?>" onclick="return confirm('Are you sure you want to delete this question?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No questions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        
                    </div>
                </div>
            </div>
            
          <div id="results" class="tab-content" style="display: <?php echo $active_tab == 'results' ? 'block' : 'none'; ?>; opacity: 1;">
    <h2><i class="fas fa-poll-h"></i> Quiz Results</h2>
    
    <?php
    // Get all quiz results ordered by oldest first (chronological order)
    $results_query = "SELECT r.*, u.full_name, u.email, u.course_details 
                     FROM quiz_results r
                     JOIN users u ON r.user_id = u.user_id
                     ORDER BY r.taken_at ASC";
    $results = $conn->query($results_query);
    ?>
    
    <table id="quizResultsTable">
        <thead>
            <tr>
                <th>No.</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Email</th>
                <th>Quiz Date</th>
                <th>Questions</th>
                <th>Correct Answers</th>
                <th>Score (%)</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php 
                $counter = 1;
                while ($result = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo $result['full_name']; ?></td>
                        <td><?php echo $result['course_details']; ?></td>
                        <td><?php echo $result['email']; ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($result['taken_at'])); ?></td>
                        <td style="text-align: center;"><?php echo $result['total_questions']; ?></td>
                        <td style="text-align: center;"><?php echo $result['correct_answers']; ?></td>
                        <td>
                            <?php 
                                $score_color = '';
                                if ($result['percentage'] >= 80) {
                                    $score_color = 'var(--accent-success)';
                                } elseif ($result['percentage'] >= 50) {
                                    $score_color = 'var(--accent-warning)';
                                } else {
                                    $score_color = 'var(--accent-danger)';
                                }
                            ?>
                            <span style="color: <?php echo $score_color; ?>; font-weight: bold;">
                                <?php echo $result['percentage']; ?>%
                            </span>
                        </td>
                        <td>
                            <a href="view_result.php?id=<?php echo $result['result_id']; ?>" title="View Details">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No quiz results found.</td>
                </tr>
            <?php endif; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.admin-tabs ul li');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Function to set URL hash and active tab
            function setActiveTab(tabId) {
                window.location.hash = tabId;
                
                tabs.forEach(tab => {
                    if (tab.dataset.tab === tabId) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
                
                tabContents.forEach(content => {
                    if (content.id === tabId) {
                        content.style.display = 'block';
                        // Trigger reflow for animation
                        void content.offsetWidth;
                        content.style.opacity = 1;
                    } else {
                        content.style.opacity = 0;
                        setTimeout(() => {
                            content.style.display = 'none';
                        }, 300);
                    }
                });
            }
            
            // Initialize tabs based on URL hash or active_tab
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                setActiveTab(hash);
            } else {
                setActiveTab('<?php echo $active_tab; ?>');
            }
            
            // Set up tab click events
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.dataset.tab;
                    setActiveTab(tabId);
                });
            });
            
            // Add subtle animations for better UX
            document.querySelectorAll('.stat-item, .question-form, .questions-filter').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>