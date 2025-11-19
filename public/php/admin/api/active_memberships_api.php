<?php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/user_id_generator.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set JSON header
header('Content-Type: application/json');

// Only admins can access this API
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getMemberships':
        $includeExpired = isset($_GET['include_expired']) && $_GET['include_expired'] === 'true';
        
        // Base query - only approved memberships
        $query = "SELECT 
                    um.id,
                    um.user_id,
                    um.plan_id,
                    um.plan_name,
                    um.billing_type,
                    um.start_date,
                    um.end_date,
                    um.membership_status,
                    um.payment_method,
                    um.cash_payment_status,
                    um.cash_payment_date,
                    um.name,
                    u.email,
                    u.contact_number,
                    u.avatar as profile_image
                FROM user_memberships um
                JOIN users u ON um.user_id = u.id
                WHERE um.request_status = 'approved'
                AND (
                    um.payment_method = 'online' 
                    OR (um.payment_method = 'cash' AND um.cash_payment_status = 'paid')
                )";
        
        if (!$includeExpired) {
            // Only active memberships (not expired) - filter by actual end date
            $query .= " AND um.end_date >= CURDATE()";
        }
        
        // Order by end date to show expiring soon first
        $query .= " ORDER BY um.end_date ASC";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $memberships = [];
            
            while ($row = $result->fetch_assoc()) {
                $memberships[] = $row;
            }
            
            $stmt->close();
            
            // Calculate statistics
            $stats = calculateStats($memberships);
            
            echo json_encode([
                'success' => true, 
                'data' => $memberships,
                'stats' => $stats
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'getMembershipDetails':
        $membershipId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($membershipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid membership ID']);
            exit;
        }
        
        // Get membership details
        $membershipQuery = "SELECT 
                            um.*,
                            u.username,
                            u.email,
                            u.contact_number,
                            u.role,
                            u.avatar as profile_image,
                            u.account_status
                        FROM user_memberships um
                        JOIN users u ON um.user_id = u.id
                        WHERE um.id = ?";
        
        $stmt = $conn->prepare($membershipQuery);
        $stmt->bind_param('i', $membershipId);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $membership = $result->fetch_assoc();
                
                // Get user details
                $userQuery = "SELECT * FROM users WHERE id = ?";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param('s', $membership['user_id']);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                
                // Add name from membership table to user data
                if ($user) {
                    $user['name'] = $membership['name'];
                }
                
                $userStmt->close();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'membership' => $membership,
                        'user' => $user
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Membership not found']);
            }
            
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'getPaymentHistory':
        $membershipId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($membershipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid membership ID']);
            exit;
        }
        
        // Get membership details first
        $membershipQuery = "SELECT * FROM user_memberships WHERE id = ?";
        $stmt = $conn->prepare($membershipQuery);
        $stmt->bind_param('i', $membershipId);
        $stmt->execute();
        $result = $stmt->get_result();
        $membership = $result->fetch_assoc();
        $stmt->close();
        
        if (!$membership) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Membership not found']);
            exit;
        }
        
        // Build payment history from membership data
        $payments = [];
        
        // Calculate amount based on plan - Real pricing from membership.php
        // Gladiator: ₱14,500/month or ₱43,500/quarter (48 hrs/week, Boxing & MMA)
        // Clash: ₱13,500/month or ₱40,500/quarter (36 hrs/week, MMA only)
        // Brawler: ₱11,500/month or ₱34,500/quarter (36 hrs/week, Muay Thai)
        // Champion: ₱7,000/month or ₱21,000/quarter (36 hrs/week, Boxing only)
        // Resolution: ₱2,200/month or ₱6,600/quarter (24 hrs/week, Gym only)
        $planPricing = [
            'Gladiator' => ['monthly' => 14500, 'quarterly' => 43500],
            'Clash' => ['monthly' => 13500, 'quarterly' => 40500],
            'Brawler' => ['monthly' => 11500, 'quarterly' => 34500],
            'Champion' => ['monthly' => 7000, 'quarterly' => 21000],
            'Resolution Regular' => ['monthly' => 2200, 'quarterly' => 6600],
            'Resolution' => ['monthly' => 2200, 'quarterly' => 6600]
        ];
        
        $planName = $membership['plan_name'];
        $billingType = $membership['billing_type'];
        $amount = $planPricing[$planName][$billingType] ?? 14500;
        
        // Add the initial payment
        $payments[] = [
            'payment_date' => $membership['date_submitted'] ?? $membership['start_date'],
            'amount' => $amount,
            'payment_method' => $membership['payment_method'],
            'cash_payment_status' => $membership['cash_payment_status'],
            'reference_number' => $membership['payment_method'] === 'online' ? 'ONL-' . $membership['id'] : 'CASH-' . $membership['id']
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $payments
        ]);
        break;

    case 'createMembershipWithAccount':
        // Validate CSRF token for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!CSRFProtection::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
                exit;
            }
        }
        
        // Get form data
        $name = trim($_POST['memberName'] ?? '');
        $email = trim($_POST['memberEmail'] ?? '');
        $contact = trim($_POST['memberContact'] ?? '');
        $username = trim($_POST['memberUsername'] ?? '');
        $plan = trim($_POST['memberPlan'] ?? '');
        $billingType = trim($_POST['billingType'] ?? '');
        $startDate = trim($_POST['startDate'] ?? '');
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($contact) || empty($username) || empty($plan) || empty($billingType) || empty($startDate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        // Check if username or email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param('ss', $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        $checkStmt->close();
        
        // Generate password (12-character alphanumeric)
        $password = generateRandomPassword(12);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Generate user_id using the helper function
        $userId = generateFormattedUserId($conn, 'member');
        
        // Calculate end date based on billing type
        $endDate = calculateEndDate($startDate, $billingType);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert user account
            $userStmt = $conn->prepare("INSERT INTO users (id, username, email, contact_number, password, role, account_status, is_verified) VALUES (?, ?, ?, ?, ?, 'member', 'active', 1)");
            $userStmt->bind_param('sssss', $userId, $username, $email, $contact, $hashedPassword);
            
            if (!$userStmt->execute()) {
                throw new Exception('Failed to create user account');
            }
            $userStmt->close();
            
            // Get plan name based on plan value
            $planName = ucfirst($plan) . ' Plan';
            
            // Insert membership with name
            $membershipStmt = $conn->prepare("INSERT INTO user_memberships (user_id, name, plan_name, billing_type, start_date, end_date, request_status, membership_status, payment_method, date_submitted, date_approved, admin_id) VALUES (?, ?, ?, ?, ?, ?, 'approved', 'active', 'cash', NOW(), NOW(), ?)");
            $membershipStmt->bind_param('sssssss', $userId, $name, $planName, $billingType, $startDate, $endDate, $adminId);
            
            if (!$membershipStmt->execute()) {
                throw new Exception('Failed to create membership');
            }
            $membershipStmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Send email with credentials
            $emailSent = sendCredentialsEmail($email, $name, $username, $password);
            
            // Log activity
            ActivityLogger::log(
                'membership_created',
                $username,
                $userId,
                "Created new membership account for {$name} with {$planName} ({$billingType})"
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Membership created successfully',
                'email_sent' => $emailSent
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create membership: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Calculate statistics from memberships data
 */
function calculateStats($memberships) {
    $today = new DateTime();
    $thisMonthStart = new DateTime('first day of this month');
    
    $stats = [
        'total_active' => 0,
        'expiring_soon' => 0,
        'new_this_month' => 0,
        'revenue_this_month' => 0,
        'renewal_rate' => 0
    ];
    
    foreach ($memberships as $membership) {
        // Only count active memberships for stats
        if ($membership['membership_status'] === 'active') {
            $stats['total_active']++;
            
            // Check if expiring soon (within 7 days)
            $endDate = new DateTime($membership['end_date']);
            $daysRemaining = $today->diff($endDate)->days;
            
            if ($daysRemaining <= 7 && $endDate >= $today) {
                $stats['expiring_soon']++;
            }
            
            // Check if new this month
            $startDate = new DateTime($membership['start_date']);
            if ($startDate >= $thisMonthStart) {
                $stats['new_this_month']++;
                
                // Calculate revenue with real pricing from membership.php
                $planPricing = [
                    'Gladiator' => ['monthly' => 14500, 'quarterly' => 43500],
                    'Clash' => ['monthly' => 13500, 'quarterly' => 40500],
                    'Brawler' => ['monthly' => 11500, 'quarterly' => 34500],
                    'Champion' => ['monthly' => 7000, 'quarterly' => 21000],
                    'Resolution Regular' => ['monthly' => 2200, 'quarterly' => 6600],
                    'Resolution' => ['monthly' => 2200, 'quarterly' => 6600]
                ];
                
                $planName = $membership['plan_name'];
                $billingType = $membership['billing_type'];
                $amount = $planPricing[$planName][$billingType] ?? 14500;
                $stats['revenue_this_month'] += $amount;
            }
        }
    }
    
    // Calculate renewal rate (simplified - can be enhanced)
    // For now, just show 0% as placeholder
    $stats['renewal_rate'] = 0;
    
    return $stats;
}

/**
 * Calculate end date based on billing type
 */
function calculateEndDate($startDate, $billingType) {
    $start = new DateTime($startDate);
    
    if ($billingType === 'monthly') {
        $start->modify('+1 month');
    } else {
        $start->modify('+3 months');
    }
    
    return $start->format('Y-m-d');
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Send credentials email to new member
 */
function sendCredentialsEmail($toEmail, $toName, $username, $password) {
    require_once __DIR__ . '/../../../../includes/mail_config.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');
        
        // Recipients
        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Gym');
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Welcome to Fit & Brawl Gym - Your Account Credentials";
        
        $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 8px; margin-top: 20px; }
            .credentials { background: white; padding: 20px; border-left: 4px solid #1e3c72; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #777; font-size: 12px; }
            strong { color: #1e3c72; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to Fit & Brawl Gym!</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                <p>Your membership account has been successfully created by our admin team. Below are your login credentials:</p>
                
                <div class='credentials'>
                        <p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>
                    <p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
                </div>
                
                <p><strong>Important Security Notes:</strong></p>
                <ul>
                    <li>Please change your password after your first login</li>
                    <li>Do not share your credentials with anyone</li>
                    <li>Keep this email in a secure location</li>
                </ul>
                
                <p>You can now log in to your account and start enjoying your membership benefits!</p>
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                
                <p>Best regards,<br>Fit & Brawl Gym Team</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Fit & Brawl Gym. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Failed to send credentials email: ' . $e->getMessage());
        return false;
    }
}

?>
