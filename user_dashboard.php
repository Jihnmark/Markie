<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

// Get user info
$user_id = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['user_name'] ?? 'User'; // Use a default if not set

// Check if user has already taken the quiz
$check_result_query = "SELECT * FROM quiz_results WHERE user_id = $user_id";
$result_exists = $conn->query($check_result_query);
$has_taken_quiz = $result_exists->num_rows > 0;
$quiz_result = $has_taken_quiz ? $result_exists->fetch_assoc() : null;

// Process quiz submission
$quiz_submitted = false;
$quiz_score = 0;
$total_questions = 0;
$correct_answers = 0;
$easy_score = 0;
$average_score = 0;
$difficult_score = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    // Get all questions without limiting to 10 per difficulty level
    $easy_query = "SELECT * FROM questions WHERE difficulty_level = 'easy'";
    $average_query = "SELECT * FROM questions WHERE difficulty_level = 'average'";
    $difficult_query = "SELECT * FROM questions WHERE difficulty_level = 'difficult'";
    
    $easy_questions = $conn->query($easy_query);
    $average_questions = $conn->query($average_query);
    $difficult_questions = $conn->query($difficult_query);
    
    // Calculate scores for each difficulty level
    while ($question = $easy_questions->fetch_assoc()) {
        $question_id = $question['question_id'];
        $correct_answer = $question['correct_answer'];
        
        if (isset($_POST["q{$question_id}"]) && $_POST["q{$question_id}"] == $correct_answer) {
            $easy_score++;
            $correct_answers++;
        }
        $total_questions++;
    }
    
    while ($question = $average_questions->fetch_assoc()) {
        $question_id = $question['question_id'];
        $correct_answer = $question['correct_answer'];
        
        if (isset($_POST["q{$question_id}"]) && $_POST["q{$question_id}"] == $correct_answer) {
            $average_score++;
            $correct_answers++;
        }
        $total_questions++;
    }
    
    while ($question = $difficult_questions->fetch_assoc()) {
        $question_id = $question['question_id'];
        $correct_answer = $question['correct_answer'];
        
        if (isset($_POST["q{$question_id}"]) && $_POST["q{$question_id}"] == $correct_answer) {
            $difficult_score++;
            $correct_answers++;
        }
        $total_questions++;
    }
    
    // Calculate total score
    $quiz_score = $easy_score + $average_score + $difficult_score;
    $percentage = ($quiz_score / $total_questions) * 100;
    
    // Insert or update quiz result
    if ($has_taken_quiz) {
        $update_query = "UPDATE quiz_results SET 
                          total_questions = $total_questions,
                          correct_answers = $correct_answers,
                          easy_score = $easy_score,
                          average_score = $average_score,
                          difficult_score = $difficult_score,
                          total_score = $quiz_score,
                          percentage = $percentage,
                          taken_at = NOW()
                         WHERE user_id = $user_id";
        $conn->query($update_query);
    } else {
        $insert_query = "INSERT INTO quiz_results 
                         (user_id, total_questions, correct_answers, easy_score, average_score, difficult_score, total_score, percentage)
                         VALUES ($user_id, $total_questions, $correct_answers, $easy_score, $average_score, $difficult_score, $quiz_score, $percentage)";
        $conn->query($insert_query);
    }
    
    $quiz_submitted = true;
    
    // Get updated quiz result
    $get_result_query = "SELECT * FROM quiz_results WHERE user_id = $user_id";
    $result = $conn->query($get_result_query);
    $quiz_result = $result->fetch_assoc();
    $has_taken_quiz = true;
}

// Function to get grade based on percentage
function getGrade($percentage) {
    if ($percentage >= 90) return 'A';
    else if ($percentage >= 80) return 'B';
    else if ($percentage >= 70) return 'C';
    else if ($percentage >= 60) return 'D';
    else return 'F';
}

// Get all questions for display (without limits)
$easy_query_display = "SELECT * FROM questions WHERE difficulty_level = 'easy'";
$average_query_display = "SELECT * FROM questions WHERE difficulty_level = 'average'";
$difficult_query_display = "SELECT * FROM questions WHERE difficulty_level = 'difficult'";

$easy_questions_display = $conn->query($easy_query_display);
$average_questions_display = $conn->query($average_query_display);
$difficult_questions_display = $conn->query($difficult_query_display);

// Count questions for display
$total_easy = $easy_questions_display->num_rows;
$total_average = $average_questions_display->num_rows;
$total_difficult = $difficult_questions_display->num_rows;
$total_questions_display = $total_easy + $total_average + $total_difficult;

// Reset pointers for display queries
$easy_questions_display->data_seek(0);
$average_questions_display->data_seek(0);
$difficult_questions_display->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ICT Quiz System</title>
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
            max-width: 800px;
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
            margin-bottom: 30px;
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
            margin-bottom: 30px;
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
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            display: inline-block;
        }
        
        h2 {
            color: #ffffff;
            font-size: 1.8rem;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        h3 {
            color: #ffffff;
            font-size: 1.4rem;
            margin: 25px 0 15px 0;
            font-weight: 600;
            position: relative;
            padding-left: 15px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        h3::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 20px;
            background: linear-gradient(180deg, #6366F1, #8B5CF6);
            border-radius: 3px;
        }
        
        .user-controls {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .user-controls a {
            display: inline-block;
            padding: 8px 15px;
            background: linear-gradient(90deg, #6366F1, #8B5CF6);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);
        }
        
        .user-controls a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(99, 102, 241, 0.3);
        }
        
        .quiz-results {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(99, 102, 241, 0.2);
            backdrop-filter: blur(5px);
        }
        
        .result-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .result-summary p {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 0;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.85);
            border-left: 3px solid #6366F1;
            transition: all 0.3s;
            backdrop-filter: blur(3px);
        }
        
        .result-summary p:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .result-summary p i {
            color: #8B5CF6;
            margin-right: 8px;
        }
        
        .retake-quiz {
            text-align: center;
            margin-top: 25px;
        }
        
        .retake-quiz h3 {
            padding-left: 0;
            text-align: center;
        }
        
        .retake-quiz h3::before {
            display: none;
        }
        
        button {
            padding: 12px 25px;
            background: linear-gradient(90deg, #6366F1, #8B5CF6, #EC4899);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.6);
        }
        
        button:active {
            transform: translateY(1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.4);
        }
        
        button::before {
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
        
        button:hover::before {
            left: 100%;
        }
        
        .quiz-container {
            margin-top: 20px;
        }
        
        .quiz-container p {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        .question {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            position: relative;
            border: 1px solid rgba(99, 102, 241, 0.15);
            backdrop-filter: blur(3px);
        }
        
        .question:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .question p {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
            text-align: left;
        }
        
        .options {
            padding-left: 15px;
        }
        
        .options label {
            display: block;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.85);
        }
        
        .options label:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .options input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }
        
        .submit-section {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        
        .form-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 30px;
            padding-top: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }
            
            .result-summary {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 25px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .question {
                padding: 15px;
            }
            
            .options label {
                padding: 8px 12px;
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
                <i class="fas fa-chart-bar"></i>
            </div>
            <h1>Welcome, <?php echo $full_name; ?>!</h1>
            <div class="user-controls">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if ($quiz_submitted): ?>
            <div class="quiz-results">
                <h2><i class="fas fa-poll"></i> Quiz Results</h2>
                <div class="result-summary">
                    <p><i class="fas fa-list-ol"></i> Total Questions: <?php echo $quiz_result['total_questions']; ?></p>
                    <p><i class="fas fa-check-circle"></i> Correct Answers: <?php echo $quiz_result['correct_answers']; ?></p>
                    <p><i class="fas fa-smile"></i> Easy Questions: <?php echo $quiz_result['easy_score']; ?>/<?php echo $total_easy; ?></p>
                    <p><i class="fas fa-balance-scale"></i> Average Questions: <?php echo $quiz_result['average_score']; ?>/<?php echo $total_average; ?></p>
                    <p><i class="fas fa-brain"></i> Difficult Questions: <?php echo $quiz_result['difficult_score']; ?>/<?php echo $total_difficult; ?></p>
                    <p><i class="fas fa-star"></i> Total Score: <?php echo $quiz_result['total_score']; ?>/<?php echo $total_questions_display; ?></p>
                    <p><i class="fas fa-percent"></i> Percentage: <?php echo number_format($quiz_result['percentage'], 2); ?>%</p>
                    <p><i class="fas fa-award"></i> Grade: <?php echo getGrade($quiz_result['percentage']); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Taken on: <?php echo date('F j, Y, g:i a', strtotime($quiz_result['taken_at'])); ?></p>
                </div>
            </div>
        <?php elseif ($has_taken_quiz): ?>
            <div class="quiz-results">
                <h2><i class="fas fa-history"></i> Your Previous Quiz Results</h2>
                <div class="result-summary">
                    <p><i class="fas fa-list-ol"></i> Total Questions: <?php echo $quiz_result['total_questions']; ?></p>
                    <p><i class="fas fa-check-circle"></i> Correct Answers: <?php echo $quiz_result['correct_answers']; ?></p>
                    <p><i class="fas fa-smile"></i> Easy Questions: <?php echo $quiz_result['easy_score']; ?>/<?php echo $total_easy; ?></p>
                    <p><i class="fas fa-balance-scale"></i> Average Questions: <?php echo $quiz_result['average_score']; ?>/<?php echo $total_average; ?></p>
                    <p><i class="fas fa-brain"></i> Difficult Questions: <?php echo $quiz_result['difficult_score']; ?>/<?php echo $total_difficult; ?></p>
                    <p><i class="fas fa-star"></i> Total Score: <?php echo $quiz_result['total_score']; ?>/<?php echo $total_questions_display; ?></p>
                    <p><i class="fas fa-percent"></i> Percentage: <?php echo number_format($quiz_result['percentage'], 2); ?>%</p>
                    <p><i class="fas fa-award"></i> Grade: <?php echo getGrade($quiz_result['percentage']); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Taken on: <?php echo date('F j, Y, g:i a', strtotime($quiz_result['taken_at'])); ?></p>
                </div>
                
                <div class="retake-quiz">
                    <h3>Would you like to retake the quiz?</h3>
                    <form method="get">
                        <button type="submit" name="retake" value="true"><i class="fas fa-redo"></i> Retake Quiz</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!$has_taken_quiz || (isset($_GET['retake']) && $_GET['retake'] == 'true')): ?>
            <div class="quiz-container">
                <h2><i class="fas fa-laptop-code"></i> ICT Quiz</h2>
                <p>Please answer all <?php echo $total_questions_display; ?> questions. Good luck!</p>
                
                <form method="post" action="">
                    <?php
                    // Display easy questions
                    if ($total_easy > 0) {
                        echo "<h3><i class='fas fa-smile'></i> Easy Questions</h3>";
                        $question_num = 1;
                        while ($question = $easy_questions_display->fetch_assoc()) {
                            echo "<div class='question'>";
                            echo "<p>{$question_num}. {$question['question_text']}</p>";
                            echo "<div class='options'>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='A' required> A. {$question['option_a']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='B'> B. {$question['option_b']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='C'> C. {$question['option_c']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='D'> D. {$question['option_d']}</label>";
                            echo "</div>";
                            echo "</div>";
                            $question_num++;
                        }
                    }
                    
                    // Display average questions
                    if ($total_average > 0) {
                        echo "<h3><i class='fas fa-balance-scale'></i> Average Questions</h3>";
                        while ($question = $average_questions_display->fetch_assoc()) {
                            echo "<div class='question'>";
                            echo "<p>{$question_num}. {$question['question_text']}</p>";
                            echo "<div class='options'>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='A' required> A. {$question['option_a']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='B'> B. {$question['option_b']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='C'> C. {$question['option_c']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='D'> D. {$question['option_d']}</label>";
                            echo "</div>";
                            echo "</div>";
                            $question_num++;
                        }
                    }
                    
                    // Display difficult questions
                    if ($total_difficult > 0) {
                        echo "<h3><i class='fas fa-brain'></i> Difficult Questions</h3>";
                        while ($question = $difficult_questions_display->fetch_assoc()) {
                            echo "<div class='question'>";
                            echo "<p>{$question_num}. {$question['question_text']}</p>";
                            echo "<div class='options'>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='A' required> A. {$question['option_a']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='B'> B. {$question['option_b']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='C'> C. {$question['option_c']}</label>";
                            echo "<label><input type='radio' name='q{$question['question_id']}' value='D'> D. {$question['option_d']}</label>";
                            echo "</div>";
                            echo "</div>";
                            $question_num++;
                        }
                    }
                    ?>
                    
                    <div class="submit-section">
                        <button type="submit" name="submit_quiz"><i class="fas fa-paper-plane"></i> Submit Quiz</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="form-footer">
            <p>© <?php echo date('Y'); ?> ICT Quiz System. All rights reserved.</p>
        </div>
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
    </script>
</body>
</html>