<?php 
// forum/index.php
require_once '../includes/header.php';

// Sample forum data
$forums = [
    [
        'id' => 1,
        'title' => 'Introduction to Web Development',
        'description' => 'Discuss the basics of web development and share your learning journey.',
        'topics_count' => 12,
        'posts_count' => 58,
        'last_post' => '2 hours ago'
    ],
    [
        'id' => 2,
        'title' => 'Advanced JavaScript Techniques',
        'description' => 'Share and learn advanced JavaScript concepts and best practices.',
        'topics_count' => 8,
        'posts_count' => 42,
        'last_post' => 'Yesterday'
    ],
    [
        'id' => 3,
        'title' => 'UI/UX Design Tips',
        'description' => 'Discuss user interface and user experience design principles and techniques.',
        'topics_count' => 15,
        'posts_count' => 76,
        'last_post' => '5 hours ago'
    ]
];
?>

<div class="container my-4">
    <h1>Forum</h1>
    <p>Welcome to the Mini E-Learning Forum! Feel free to discuss, ask questions, and share knowledge with fellow learners.</p>
    
    <div class="text-end mb-3">
        <a href="create.php" class="btn btn-primary">Create New Topic</a>
    </div>
    
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Forum</th>
                <th>Topics</th>
                <th>Posts</th>
                <th>Last Post</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forums as $forum): ?>
                <tr>
                    <td>
                        <h5>
                            <a href="topic.php?id=<?php echo $forum['id']; ?>">
                                <?php echo htmlspecialchars($forum['title']); ?>
                            </a>
                        </h5>
                        <p><?php echo htmlspecialchars($forum['description']); ?></p>
                    </td>
                    <td><?php echo $forum['topics_count']; ?></td>
                    <td><?php echo $forum['posts_count']; ?></td>
                    <td><?php echo $forum['last_post']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>