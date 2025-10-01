<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit and Brawl</title>
    <link rel="stylesheet" href="public/css/pages/feedback-form.css">
    <link rel="stylesheet" href="public/css/components/form.css">
    <link rel="stylesheet" href="public/css/components/footer.css">
    <link rel="stylesheet" href="public/css/components/header.css">
    <link rel="shortcut icon" href="" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <div class="title">
                <img src="images/fnb-logo-yellow.svg" alt="fxb-logo" />
                <a href="">
                    <p class="logo-title">FIT<span>X</span>BRAWL</p>
                </a>

            </div>
            <nav class="nav-bar">
                <ul>
                <li><a href="public/php/index.php">Home</a></li>
                    <li><a href="public/php/membership.php">Membership</a></li>
                    <li><a href="public/php/equipment.php">Equipment</a></li>
                    <li><a href="public/php/products.php">Products</a></li>
                    <li><a href="public/php/contact.php">Contact</a></li>
                    <li><a href="public/php/feedback.php" class="active">Feedback</a></li>
                </ul>
            </nav>
        </div>

    </header>

    <!--Main-->
    <main>
        <div class="bg"></div>
        <div class="contact-container">
            <div class="glowing-bg"></div>
            <div class="contact-section">
                <div class="contact-header">
                    <h1>Share your feedback</h1>
                </div>
                <div class="contact-details">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon">
                                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                                </svg>
                            </label>
                            <input type="text" id="first-name" name="first-name" placeholder="Name (Optional)">
                        </div>
                        <div class="form-group">
                            <label for="last-name" class="email-label">
                                <svg class="icon email-icon" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
                                    <path fill="#fff" d="M20 4H4a2 2 0 0 0-2 2v12a2
                                    2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2
                                    2 0 0 0-2-2zm0 4.2l-8 4.8-8-4.8V6l8
                                    4.8L20 6v2.2z"/>
                                </svg>
                            </label>
                            <input type="text" id="last-name" name="last-name" placeholder="Email (Optional)">
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea id="message" name="message" placeholder="Leave us a message..." required></textarea>
                    </div>
                    <div class="buttons">
                        <a href="feedback.html">Cancel</a>
                        <button type="submit">Submit</button>
                    </div>
                </div>

            </div>
        </div>
    </main>


    <!--Footer-->
    <footer>
        <div class="container">
            <div class="logo-section">
                <div class="logo-container">
                    <img src="images/fnb-logo-yellow.svg" alt="fxb-logo" />

                </div>
                <div class="title">
                    <p class="logo-title">FIT<span>X</span>BRAWL</p>
                </div>
                <div class="social-icons">
                    <a href="" target="_blank">
                        <i class="fa-brands fa-facebook social-icon"></i>
                    </a>
                    <a href="" target="_blank">
                        <i class="fa-brands fa-instagram social-icon"></i>
                    </a>
                </div>
            </div>
               <div class="links-section">
                <div class="section-title">
                    <p>Quick Links:</p>
                </div>
                <div class="quick-links">
                    <div class="links-column">
                        <ul>
                            <li><a href="index.html">Home</a></li>
                            <li><a href="membership.html">Membership</a></li>
                            <li><a href="equipment.html">Equipment</a></li>
                            <li><a href="products.html">Products</a></li>
                        </ul>
                    </div>
                    <div class="links-column">
                        <ul>
                            <li><a href="contact.html">Contact</a></li>
                            <li><a href="feedback.html">Feedback</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="contact-section">
                               <div class="section-title">
                    <p>For more information, you may contact us at:</p>
                </div>
                <div class="contact-text">
                    <p>Address: 1832 Oroquieta Rd, Santa Cruz, Manila, 1008 Metro Manila</p>
                    <p>Email: <a href="mailto:ithelp@plm.edu.ph">Email: fitxbrawl@gmail.com</a></p>
                    <p>FXB Hotline: (02) 8 995-46-70</p>
                </div>
            </div>
               <div class="open-hours-section">
       <div class="section-title">
           <p>Opening Hours</p>
       </div>
       <div class="opening-text">
           <p>Sunday-Friday: 9AM to 10PM</p>
           <p>Saturday: 10PM to 7PM</p>
       </div>
   </div>
        </div>

        <div class="copyright">
            <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
