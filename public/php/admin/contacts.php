<?php include_once('../../../includes/init.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin | Contact Inquiries</title>
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <?php include_once('admin_sidebar.php'); ?>

    <main class="admin-main">
        <header>
            <h1>Contact Inquiries</h1>
            <p>Manage and respond to user messages</p>
        </header>

        <!-- Table -->
        <table id="contactsTable" border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date Sent</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" align="center">Loading...</td>
                </tr>
            </tbody>
        </table>
    </main>

    <script src="js/admin_contacts.js"></script>
</body>

</html>