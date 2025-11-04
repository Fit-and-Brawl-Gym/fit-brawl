<?php
include_once('../../../includes/init.php');

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Inquiries - Fit & Brawl Gym</title>
    <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/contacts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Contact Inquiries</h1>
                <p class="subtitle">Manage and respond to customer messages</p>
            </div>
        </header>

        <!-- Search and Stats -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by name or email...">
            </div>
            <div class="stats-summary">
                <span class="stat-label">Total Inquiries:</span>
                <strong id="totalContacts">0</strong>
                <span class="stat-divider">|</span>
                <span class="stat-label">Unread:</span>
                <strong id="unreadCount" class="unread-badge">0</strong>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="tabs">
            <button class="tab active" data-filter="all">All</button>
            <button class="tab" data-filter="unread">Unread</button>
            <button class="tab" data-filter="read">Read</button>
        </div>

        <!-- Contacts List -->
        <div class="contacts-container">
            <div id="contactsList" class="contacts-list">
                <!-- Contacts will be loaded here via JavaScript -->
                <div class="loading-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading contacts...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-overlay" onclick="closeReplyModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reply to Inquiry</h2>
                <button class="close-btn" onclick="closeReplyModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <input type="hidden" id="replyContactId">

                    <div class="form-group">
                        <label for="replyTo">To:</label>
                        <input type="email" id="replyTo" readonly>
                    </div>

                    <div class="form-group">
                        <label for="replySubject">Subject:</label>
                        <input type="text" id="replySubject" placeholder="Re: Contact Inquiry" required>
                    </div>

                    <div class="form-group">
                        <label for="replyMessage">Your Reply:</label>
                        <textarea id="replyMessage" rows="8" placeholder="Type your response here..."
                            required></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" id="includeCopy" checked>
                            Send copy to admin email
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeReplyModal()">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-paper-plane"></i>
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/contacts.js"></script>
</body>

</html>