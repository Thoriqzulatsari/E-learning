<?php
// forum/process_topic.php
require_once '../includes/header.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $forum_id = isset($_POST['forum_id']) ? intval($_POST['forum_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Basic validation
    $errors = [];
    if (empty($forum_id)) {
        $errors[] = "Please select a forum";
    }
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($message)) {
        $errors[] = "Message is required";
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
    
    // If there are no errors, save the topic
    if (empty($errors)) {
        // In a real application, you would save to database here
        // For now, we'll just simulate success
        $success = true;
        
        if ($success) {
            // Redirect to the topic page (with a success message)
            $_SESSION['message'] = "Topic created successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Failed to create topic. Please try again.";
        }
    }
    
    // If we got here, there were errors
    if (!empty($errors)) {
        echo '<div class="container my-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4>Error creating topic:</h4>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '<a href="create.php" class="btn btn-primary">Go Back</a>';
        echo '</div>';
    }
} else {
    // If accessed directly without form submission, redirect to create page
    header("Location: create.php");
    exit;
}

require_once '../includes/footer.php';
?>