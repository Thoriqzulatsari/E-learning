<?php
// forum/process_reply.php
require_once '../includes/header.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    // Basic validation
    $errors = [];
    if (empty($topic_id)) {
        $errors[] = "Invalid topic";
    }
    if (empty($content)) {
        $errors[] = "Reply content is required";
    }
    
    // Process file uploads if any
    $attachments = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $upload_dir = '../uploads/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                $name = basename($name);
                $filename = uniqid() . '_' . $name;
                $destination = $upload_dir . $filename;
                
                // Check file size (5MB max)
                if ($_FILES['attachments']['size'][$key] > 5242880) {
                    $errors[] = "File $name exceeds maximum size (5MB)";
                    continue;
                }
                
                // Move the uploaded file
                if (move_uploaded_file($tmp_name, $destination)) {
                    $attachments[] = $filename;
                } else {
                    $errors[] = "Failed to upload file: $name";
                }
            } else if ($_FILES['attachments']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading file: $name";
            }
        }
    }
    
    // If there are no errors, save the reply
    if (empty($errors)) {
        // Prepare the insert statement
        $insert_query = $conn->prepare("
            INSERT INTO forum_replies 
            (topic_id, user_id, content) 
            VALUES (?, ?, ?)
        ");
        
        // Get current logged-in user's ID
        $user_id = $_SESSION['user_id'];
        
        $insert_query->bind_param("iis", $topic_id, $user_id, $content);
        
        if ($insert_query->execute()) {
            // Optional: Handle attachments if needed
            
            // Set success message
            $_SESSION['message'] = "Reply posted successfully!";
            $_SESSION['message_type'] = "success";
            
            // Redirect back to the topic page
            header("Location: topic.php?id=" . $topic_id);
            exit;
        } else {
            $errors[] = "Failed to post reply. Please try again.";
        }
    }
    
    // If we got here, there were errors
    if (!empty($errors)) {
        echo '<div class="container my-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4>Error posting reply:</h4>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '<a href="topic.php?id=' . $topic_id . '" class="btn btn-primary">Go Back</a>';
        echo '</div>';
    }
} else {
    // If accessed directly without form submission, redirect to forums
    header("Location: index.php");
    exit;
}

require_once '../includes/footer.php';
?>