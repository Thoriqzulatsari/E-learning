<?php
// footer.php - Simple & Clean Footer for Mini E-Learning
?>
<style>
    .footer {
        background-color: #2c3e50;
        color: #ecf0f1;
        padding: 40px 0;
        text-align: center;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
    }

    .footer-section {
        flex: 1;
        margin: 10px;
        min-width: 200px;
    }

    .footer-section h4 {
        color: #3498db;
        margin-bottom: 15px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-section h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background-color: #3498db;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin: 10px 0;
    }

    .footer-links a {
        color: #bdc3c7;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: #ffffff;
    }

    .social-icons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    .social-icons a {
        color: #bdc3c7;
        font-size: 24px;
        transition: color 0.3s ease;
    }

    .social-icons a:hover {
        color: #3498db;
    }

    .footer-bottom {
        background-color: #34495e;
        color: #ecf0f1;
        padding: 15px 0;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            align-items: center;
        }

        .footer-section {
            text-align: center;
            width: 100%;
        }
    }
</style>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>Mini E-Learning</h4>
            <p>The leading digital education platform in Indonesia.</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul class="footer-links">
                <li><a href="/courses">Courses</a></li>
                <li><a href="/about">About Us</a></li>
                <li><a href="/instructors">Instructors</a></li>
                <li><a href="/blog">Blog</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4>Support</h4>
            <ul class="footer-links">
                <li><a href="/faq">FAQ</a></li>
                <li><a href="/support">Help Center</a></li>
                <li><a href="/terms">Terms of Service</a></li>
                <li><a href="/privacy">Privacy</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Mini E-Learning. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>