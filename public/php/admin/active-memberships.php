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
    <title>Active Memberships - Fit & Brawl Gym</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/active-memberships.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Active Memberships</h1>
                <p class="subtitle">Monitor and manage active member subscriptions</p>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Active</div>
                    <div class="stat-value" id="totalActive">0</div>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Expiring Soon</div>
                    <div class="stat-value" id="expiringSoon">0</div>
                    <div class="stat-hint">Within 7 days</div>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">New This Month</div>
                    <div class="stat-value" id="newThisMonth">0</div>
                </div>
            </div>

            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Revenue This Month</div>
                    <div class="stat-value" id="revenueThisMonth">₱0</div>
                </div>
            </div>

            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-rotate"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Renewal Rate</div>
                    <div class="stat-value" id="renewalRate">0%</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-card">
            <div class="filters-group">
                <div class="filter-item">
                    <label for="billingTypeFilter">Billing Type</label>
                    <select id="billingTypeFilter" class="filter-select">
                        <option value="">All Billing Types</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="expirationFilter">Expiration</label>
                    <select id="expirationFilter" class="filter-select">
                        <option value="">All Memberships</option>
                        <option value="expiring_soon">Expiring Soon (7 days)</option>
                        <option value="this_month">Expiring This Month</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="paymentStatusFilter">Payment Status</label>
                    <select id="paymentStatusFilter" class="filter-select">
                        <option value="">All Payment Status</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>

                <div class="filter-item filter-search">
                    <label for="searchFilter">Search</label>
                    <input type="text" id="searchFilter" class="filter-input" placeholder="Search by name, email, or contact...">
                </div>

                <div class="filter-actions">
                    <button class="btn btn-secondary" id="clearFiltersBtn">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <div class="toggle-expired">
                        <label class="toggle-label">
                            <input type="checkbox" id="showExpiredToggle">
                            <span>Show Expired</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Memberships Table -->
        <div class="table-card">
            <div class="table-header">
                <h2>Active Memberships</h2>
                <div class="table-info">
                    Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> memberships
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="membershipsTableBody">
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-spinner fa-spin"></i> Loading memberships...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div class="pagination-wrapper" id="paginationContainer">
                <div class="pagination-info">
                    <label for="itemsPerPageSelect">Items per page:</label>
                    <select id="itemsPerPageSelect" class="items-per-page-select">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                
                <div class="pagination-controls">
                    <button id="prevPageBtn" class="btn-pagination" disabled>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <span class="page-info">
                        Page <span id="currentPageSpan">1</span> of <span id="totalPagesSpan">1</span>
                    </span>
                    <button id="nextPageBtn" class="btn-pagination" disabled>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Membership Modal -->
    <div id="addMembershipModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>ADD MEMBERSHIP</h3>
                <button class="modal-close" id="closeAddMembershipModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addMembershipForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="memberName">Full Name <span class="required">*</span></label>
                            <input type="text" id="memberName" name="memberName" required>
                        </div>
                        <div class="form-group">
                            <label for="memberEmail">Email <span class="required">*</span></label>
                            <input type="email" id="memberEmail" name="memberEmail" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="memberContact">Contact Number <span class="required">*</span></label>
                            <input type="tel" id="memberContact" name="memberContact" required>
                        </div>
                        <div class="form-group">
                            <label for="memberUsername">Username <span class="required">*</span></label>
                            <input type="text" id="memberUsername" name="memberUsername" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="memberPlan">Membership Plan <span class="required">*</span></label>
                            <select id="memberPlan" name="memberPlan" required>
                                <option value="">Select Plan</option>
                                <option value="gladiator">Gladiator Plan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="billingType">Billing Type <span class="required">*</span></label>
                            <select id="billingType" name="billingType" required>
                                <option value="">Select Billing</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date <span class="required">*</span></label>
                            <input type="date" id="startDate" name="startDate" required>
                        </div>
                    </div>

                    <div class="form-info">
                        <i class="fas fa-info-circle"></i>
                        <span>A password will be auto-generated and sent to the member's email address.</span>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddMembership">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Membership
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment History Modal -->
    <div id="paymentHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>PAYMENT HISTORY</h3>
                <button class="modal-close" id="closePaymentHistoryModal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="paymentHistoryContent">
                    <table class="payment-history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryTableBody">
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-spinner fa-spin"></i> Loading payment history...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Panel -->
    <div id="detailsPanel" class="details-panel">
        <div class="details-header">
            <h3>Membership Details</h3>
            <button class="close-details" id="closeDetailsPanel">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="details-content" id="detailsContent">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i> Loading details...
            </div>
        </div>
    </div>

    <script>
        const PUBLIC_PATH = '<?= PUBLIC_PATH ?>';
        const IMAGES_PATH = '<?= IMAGES_PATH ?>';
    </script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/active-memberships.js"></script>
</body>

</html>
