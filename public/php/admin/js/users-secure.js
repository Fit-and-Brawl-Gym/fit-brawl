/**
 * Secure User Management - Account-Focused with DSA Optimization
 * Comprehensive user management with role-based filters
 * Implements efficient data structures and algorithms for performance
 */

// ==================== DATA STRUCTURES ====================

/**
 * HashTable implementation for O(1) user lookups
 */
class UserHashTable {
    constructor() {
        this.table = new Map();
    }

    insert(userId, user) {
        this.table.set(userId, user);
    }

    get(userId) {
        return this.table.get(userId);
    }

    has(userId) {
        return this.table.has(userId);
    }

    delete(userId) {
        return this.table.delete(userId);
    }

    clear() {
        this.table.clear();
    }

    get size() {
        return this.table.size;
    }
}

/**
 * Trie (Prefix Tree) for efficient search autocomplete
 */
class TrieNode {
    constructor() {
        this.children = new Map();
        this.userIds = new Set(); // Store user IDs at this node
        this.isEndOfWord = false;
    }
}

class Trie {
    constructor() {
        this.root = new TrieNode();
    }

    /**
     * Insert a word (name/email/username) with associated userId
     * Time Complexity: O(m) where m is word length
     */
    insert(word, userId) {
        if (!word) return;

        word = word.toLowerCase();
        let node = this.root;

        for (const char of word) {
            if (!node.children.has(char)) {
                node.children.set(char, new TrieNode());
            }
            node = node.children.get(char);
            node.userIds.add(userId); // Track all users with this prefix
        }

        node.isEndOfWord = true;
    }

    /**
     * Search for all userIds matching prefix
     * Time Complexity: O(p) where p is prefix length
     */
    searchPrefix(prefix) {
        if (!prefix) return new Set();

        prefix = prefix.toLowerCase();
        let node = this.root;

        for (const char of prefix) {
            if (!node.children.has(char)) {
                return new Set();
            }
            node = node.children.get(char);
        }

        return node.userIds;
    }

    clear() {
        this.root = new TrieNode();
    }
}

/**
 * Binary Search Tree for sorted data with O(log n) operations
 */
class BSTNode {
    constructor(user, compareKey) {
        this.user = user;
        this.compareKey = compareKey;
        this.left = null;
        this.right = null;
    }
}

class BinarySearchTree {
    constructor(compareFunction) {
        this.root = null;
        this.compareFunction = compareFunction;
        this.size = 0;
    }

    insert(user, compareKey) {
        const newNode = new BSTNode(user, compareKey);

        if (!this.root) {
            this.root = newNode;
            this.size++;
            return;
        }

        this._insertNode(this.root, newNode);
        this.size++;
    }

    _insertNode(node, newNode) {
        if (this.compareFunction(newNode.compareKey, node.compareKey) < 0) {
            if (!node.left) {
                node.left = newNode;
            } else {
                this._insertNode(node.left, newNode);
            }
        } else {
            if (!node.right) {
                node.right = newNode;
            } else {
                this._insertNode(node.right, newNode);
            }
        }
    }

    /**
     * In-order traversal for sorted output
     * Time Complexity: O(n)
     */
    inOrderTraversal(callback) {
        this._inOrder(this.root, callback);
    }

    _inOrder(node, callback) {
        if (node) {
            this._inOrder(node.left, callback);
            callback(node.user);
            this._inOrder(node.right, callback);
        }
    }

    clear() {
        this.root = null;
        this.size = 0;
    }
}

/**
 * Min/Max Heap for priority-based operations
 */
class MinHeap {
    constructor(compareFunction) {
        this.heap = [];
        this.compareFunction = compareFunction;
    }

    insert(item) {
        this.heap.push(item);
        this._bubbleUp(this.heap.length - 1);
    }

    extractMin() {
        if (this.heap.length === 0) return null;
        if (this.heap.length === 1) return this.heap.pop();

        const min = this.heap[0];
        this.heap[0] = this.heap.pop();
        this._bubbleDown(0);
        return min;
    }

    _bubbleUp(index) {
        while (index > 0) {
            const parentIndex = Math.floor((index - 1) / 2);
            if (this.compareFunction(this.heap[index], this.heap[parentIndex]) >= 0) break;

            [this.heap[index], this.heap[parentIndex]] = [this.heap[parentIndex], this.heap[index]];
            index = parentIndex;
        }
    }

    _bubbleDown(index) {
        while (true) {
            let smallest = index;
            const leftChild = 2 * index + 1;
            const rightChild = 2 * index + 2;

            if (leftChild < this.heap.length &&
                this.compareFunction(this.heap[leftChild], this.heap[smallest]) < 0) {
                smallest = leftChild;
            }

            if (rightChild < this.heap.length &&
                this.compareFunction(this.heap[rightChild], this.heap[smallest]) < 0) {
                smallest = rightChild;
            }

            if (smallest === index) break;

            [this.heap[index], this.heap[smallest]] = [this.heap[smallest], this.heap[index]];
            index = smallest;
        }
    }

    get size() {
        return this.heap.length;
    }

    clear() {
        this.heap = [];
    }
}

// ==================== GLOBAL STATE ====================

let allUsers = [];
let filteredUsers = [];
let currentFilters = {
    role: 'all',
    status: 'all',
    verified: 'all',
    membership: 'all',
    search: '',
    sort: 'created_at_desc'
};
let editableFields = {};

// DSA Structures for optimization
let userHashTable = new UserHashTable();
let searchTrie = new Trie();
let sortedBST = null;

// Index maps for O(1) filtering
let roleIndex = {
    'member': new Set(),
    'trainer': new Set(),
    'admin': new Set()
};

let statusIndex = {
    'active': new Set(),
    'locked': new Set(),
    'pending': new Set()
};

let verifiedIndex = {
    verified: new Set(),
    unverified: new Set()
};

let membershipIndex = {
    active: new Set(),
    expired: new Set()
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    loadEditableFields();
    loadUsers();
    setupEventListeners();
    createModals();
});

// Setup event listeners
function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function (e) {
            currentFilters.search = e.target.value;
            applyFilters();
        }, 300));
    }

    // Filter button toggle
    const filterBtn = document.getElementById('filterBtn');
    const filterSection = document.getElementById('filterSection');
    if (filterBtn && filterSection) {
        filterBtn.addEventListener('click', function () {
            filterSection.classList.toggle('active');
            filterBtn.classList.toggle('active');
        });
    }

    // Role filter tabs
    document.querySelectorAll('.filter-tab[data-role]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.filter-tab[data-role]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilters.role = this.dataset.role;
            applyFilters();
        });
    });

    // Status filter tabs
    document.querySelectorAll('.filter-tab[data-status]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.filter-tab[data-status]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilters.status = this.dataset.status;
            applyFilters();
        });
    });

    // Verified filter tabs
    document.querySelectorAll('.filter-tab[data-verified]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.filter-tab[data-verified]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilters.verified = this.dataset.verified;
            applyFilters();
        });
    });

    // Membership filter tabs
    document.querySelectorAll('.filter-tab[data-membership]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.filter-tab[data-membership]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilters.membership = this.dataset.membership;
            applyFilters();
        });
    });

    // Sort select
    const sortSelect = document.getElementById('sortBy');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            currentFilters.sort = this.value;
            applyFilters();
        });
    }
}

// Load editable fields for current admin
async function loadEditableFields() {
    try {
        const response = await fetch('api/admin_users_api.php?action=getEditableFields');
        const data = await response.json();

        if (data.success) {
            editableFields = data.fields;
        }
    } catch (error) {
        console.error('Failed to load editable fields:', error);
    }
}

// Load users from secure API with DSA indexing
async function loadUsers() {
    try {
        const response = await fetch('api/admin_users_api.php?action=getAllUsers');
        const data = await response.json();

        if (!data.success) {
            if (data.requires_permission) {
                showError(`Permission denied: You need ${data.requires_permission} permission`);
                return;
            }
            throw new Error(data.message || 'Failed to load users');
        }

        allUsers = data.users;

        // Build efficient data structures for O(1) lookups
        buildDataStructures();

        updateStatCards();
        applyFilters();

    } catch (error) {
        console.error('Error loading users:', error);
        showError('Failed to load users: ' + error.message);
    }
}

/**
 * Build optimized data structures from user data
 * Time Complexity: O(n * m) where n is users count, m is avg word length
 * Space Complexity: O(n)
 */
function buildDataStructures() {
    console.time('Building DSA structures');

    // Clear existing structures
    userHashTable.clear();
    searchTrie.clear();
    roleIndex = { 'member': new Set(), 'trainer': new Set(), 'admin': new Set() };
    statusIndex = { 'active': new Set(), 'locked': new Set(), 'pending': new Set() };
    verifiedIndex = { verified: new Set(), unverified: new Set() };
    membershipIndex = { active: new Set(), expired: new Set() };

    // Build all indexes in single pass - O(n)
    for (const user of allUsers) {
        const userId = user.id;

        // HashTable for O(1) user lookup by ID
        userHashTable.insert(userId, user);

        // Trie for O(m) prefix search (m = prefix length)
        if (user.full_name) {
            searchTrie.insert(user.full_name, userId);
        }
        if (user.username) {
            searchTrie.insert(user.username, userId);
        }
        if (user.email) {
            searchTrie.insert(user.email, userId);
        }

        // Role index for O(1) role filtering
        if (roleIndex[user.role]) {
            roleIndex[user.role].add(userId);
        }

        // Status index for O(1) status filtering
        if (statusIndex[user.account_status]) {
            statusIndex[user.account_status].add(userId);
        }

        // Verified index for O(1) verification filtering
        if (user.is_verified == 1) {
            verifiedIndex.verified.add(userId);
        } else {
            verifiedIndex.unverified.add(userId);
        }

        // Membership index for O(1) membership filtering
        // Check if user has active membership/subscription
        if (user.membership_status === 'active' || user.membership_status === 'Active') {
            membershipIndex.active.add(userId);
        } else {
            membershipIndex.expired.add(userId);
        }
    }

    console.timeEnd('Building DSA structures');
    console.log('Index Stats:', {
        totalUsers: userHashTable.size,
        roles: Object.keys(roleIndex).map(k => `${k}: ${roleIndex[k].size}`),
        statuses: Object.keys(statusIndex).map(k => `${k}: ${statusIndex[k].size}`)
    });
}

// Update stat cards using indexed data - O(1) instead of O(n)
function updateStatCards() {
    const totalUsers = userHashTable.size;
    const allMembers = roleIndex.member?.size || 0;
    const subscribedMembers = membershipIndex.active?.size || 0;
    const regularMembers = allMembers - subscribedMembers;
    const admins = roleIndex.admin?.size || 0;
    const trainers = roleIndex.trainer?.size || 0;

    document.getElementById('totalUsers').textContent = totalUsers;
    document.getElementById('regularMembers').textContent = regularMembers;
    document.getElementById('subscribedMembers').textContent = subscribedMembers;
    document.getElementById('adminCount').textContent = admins;
    document.getElementById('trainerCount').textContent = trainers;
}

/**
 * Apply filters using optimized data structures
 * Uses Set intersection for O(k) complexity where k is result set size
 * instead of O(n) linear filtering
 */
function applyFilters() {
    console.time('Filtering with DSA');

    // Start with all user IDs
    let resultSet = new Set(userHashTable.table.keys());

    // Apply role filter using index - O(1) lookup
    if (currentFilters.role !== 'all') {
        resultSet = setIntersection(resultSet, roleIndex[currentFilters.role] || new Set());
    }

    // Apply status filter using index - O(1) lookup
    if (currentFilters.status !== 'all') {
        resultSet = setIntersection(resultSet, statusIndex[currentFilters.status] || new Set());
    }

    // Apply verified filter using index - O(1) lookup
    if (currentFilters.verified !== 'all') {
        const verifiedSet = currentFilters.verified == '1' ? verifiedIndex.verified : verifiedIndex.unverified;
        resultSet = setIntersection(resultSet, verifiedSet);
    }

    // Apply membership filter using index - O(1) lookup
    if (currentFilters.membership !== 'all') {
        const membershipSet = currentFilters.membership === 'active' ? membershipIndex.active : membershipIndex.expired;
        resultSet = setIntersection(resultSet, membershipSet);
    }

    // Apply search filter using Trie - O(p) where p is prefix length
    if (currentFilters.search && currentFilters.search.trim()) {
        const searchResults = searchTrie.searchPrefix(currentFilters.search.trim());
        resultSet = setIntersection(resultSet, searchResults);
    }

    // Convert result set to user objects using HashTable - O(k)
    filteredUsers = Array.from(resultSet).map(userId => userHashTable.get(userId));

    console.timeEnd('Filtering with DSA');
    console.log(`Filtered ${filteredUsers.length} users from ${userHashTable.size} total`);

    // Apply sorting
    sortUsers();

    // Render
    renderUsersTable();
}

/**
 * Set intersection algorithm - O(min(a, b))
 */
function setIntersection(setA, setB) {
    const result = new Set();

    // Iterate through smaller set for efficiency
    const [smaller, larger] = setA.size < setB.size ? [setA, setB] : [setB, setA];

    for (const item of smaller) {
        if (larger.has(item)) {
            result.add(item);
        }
    }

    return result;
}

/**
 * Sort users using optimized algorithms
 * For small datasets: Use native sort (TimSort) - O(n log n)
 * For large datasets: Use BST for pre-sorted insertion - O(n log n)
 */
function sortUsers() {
    console.time('Sorting');

    const LARGE_DATASET_THRESHOLD = 1000;

    if (filteredUsers.length < LARGE_DATASET_THRESHOLD) {
        // Use native TimSort for smaller datasets
        filteredUsers.sort((a, b) => {
            switch (currentFilters.sort) {
                case 'created_at_desc':
                    return new Date(b.created_at) - new Date(a.created_at);
                case 'created_at_asc':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'name_asc':
                    return (a.full_name || '').localeCompare(b.full_name || '');
                case 'name_desc':
                    return (b.full_name || '').localeCompare(a.full_name || '');
                default:
                    return 0;
            }
        });
    } else {
        // Use BST for larger datasets
        sortedBST = new BinarySearchTree(getCompareFunctionForSort());

        for (const user of filteredUsers) {
            const compareKey = getCompareKeyForUser(user);
            sortedBST.insert(user, compareKey);
        }

        // Extract sorted results from BST
        const sortedResults = [];
        sortedBST.inOrderTraversal(user => sortedResults.push(user));

        // Reverse if descending order
        if (currentFilters.sort.includes('desc')) {
            sortedResults.reverse();
        }

        filteredUsers = sortedResults;
    }

    console.timeEnd('Sorting');
}

/**
 * Get compare function based on current sort option
 */
function getCompareFunctionForSort() {
    switch (currentFilters.sort) {
        case 'created_at_desc':
        case 'created_at_asc':
            return (a, b) => new Date(a) - new Date(b);
        case 'name_asc':
        case 'name_desc':
            return (a, b) => (a || '').localeCompare(b || '');
        default:
            return (a, b) => 0;
    }
}

/**
 * Get compare key from user object based on sort option
 */
function getCompareKeyForUser(user) {
    switch (currentFilters.sort) {
        case 'created_at_desc':
        case 'created_at_asc':
            return user.created_at;
        case 'name_asc':
        case 'name_desc':
            return user.full_name || '';
        default:
            return user.id;
    }
}

// Render users table
function renderUsersTable() {
    const tbody = document.getElementById('usersTableBody');

    if (filteredUsers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fa-solid fa-users-slash"></i>
                    <h3>No Users Found</h3>
                    <p>No users match your current filters</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredUsers.map(user => createUserRow(user)).join('');
}

// Create user table row
function createUserRow(user) {
    const roleBadge = getRoleBadge(user.role);
    const statusBadge = getStatusBadge(user.account_status);
    const verifiedBadge = getVerifiedBadge(user.is_verified);
    const membershipBadge = getMembershipBadge(user.membership_status);

    // Determine avatar source with proper path logic
    // For trainers, prioritize trainer_photo over user avatar
    let avatarSrc = `${IMAGES_PATH}/account-icon.svg`; // Default

    if (user.role === 'trainer' && user.trainer_photo && user.trainer_photo.trim() !== '') {
        // Trainer with photo from trainers table
        avatarSrc = `${BASE_PATH}/uploads/trainers/${user.trainer_photo}`;
    } else if (user.avatar &&
               user.avatar !== 'account-icon.svg' &&
               user.avatar !== 'account-icon-white.svg' &&
               user.avatar !== 'default-avatar.png' &&
               user.avatar.trim() !== '') {
        // User with custom avatar from users table
        avatarSrc = `${BASE_PATH}/uploads/avatars/${user.avatar}`;
    }

    return `
        <tr>
            <td>
                <div class="user-cell">
                    <img src="${avatarSrc}" alt="${escapeHtml(user.full_name)}" class="user-avatar" onerror="this.src='${IMAGES_PATH}/account-icon.svg'">
                    <div class="user-info">
                        <div class="user-name">${escapeHtml(user.full_name)}</div>
                        <div class="user-username">@${escapeHtml(user.username)}</div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <div class="badges-container">
                    ${roleBadge}
                    ${membershipBadge}
                </div>
            </td>
            <td>${statusBadge}</td>
            <td>${verifiedBadge}</td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button onclick="viewUserDetails('${user.id}')" class="action-btn action-btn-primary" title="View Details">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button onclick="editUser('${user.id}')" class="action-btn action-btn-secondary" title="Edit User">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button onclick="resetPassword('${user.id}')" class="action-btn action-btn-secondary" title="Reset Password">
                        <i class="fa-solid fa-key"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// Get role badge HTML
function getRoleBadge(role) {
    const badges = {
        'admin': '<span class="badge badge-admin"><i class="fa-solid fa-user-shield"></i> Admin</span>',
        'trainer': '<span class="badge badge-trainer"><i class="fa-solid fa-user-tie"></i> Trainer</span>',
        'member': '<span class="badge badge-member"><i class="fa-solid fa-user"></i> Member</span>'
    };
    return badges[role] || '';
}

// Get status badge HTML
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-active"><i class="fa-solid fa-circle-check"></i> Active</span>',
        'locked': '<span class="badge badge-locked"><i class="fa-solid fa-lock"></i> Locked</span>',
        'pending': '<span class="badge badge-pending"><i class="fa-solid fa-circle-dot"></i> Pending</span>'
    };
    return badges[status] || '';
}

// Get verified badge HTML
function getVerifiedBadge(isVerified) {
    return isVerified == 1
        ? '<span class="badge badge-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>'
        : '<span class="badge badge-unverified"><i class="fa-solid fa-circle-xmark"></i> Unverified</span>';
}

// Get membership badge HTML
function getMembershipBadge(membershipStatus) {
    if (membershipStatus === 'active' || membershipStatus === 'Active') {
        return '<span class="badge badge-membership-active"><i class="fa-solid fa-gem"></i></span>';
    }
    return '';
}

// ==================== USER ACTIONS ====================

// View user details with O(1) cache lookup
async function viewUserDetails(userId) {
    try {
        // Check cache first - O(1)
        const cachedUser = userHashTable.get(userId);

        if (cachedUser) {
            // Show basic info immediately from cache
            showUserDetailsPanel(cachedUser, null);
        }

        // Fetch full details including activity
        const response = await fetch(`api/admin_users_api.php?action=getUserDetails&user_id=${userId}`);
        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            return;
        }

        // Update cache
        userHashTable.insert(userId, data.user);

        // Update panel with full details
        showUserDetailsPanel(data.user, data.recent_activity);
    } catch (error) {
        console.error('Error fetching user details:', error);
        alert('Failed to load user details');
    }
}

// Show user details panel
function showUserDetailsPanel(user, recentActivity) {
    const panel = document.getElementById('userDetailsPanel');
    const content = document.getElementById('userDetailsContent');

    const activityHtml = recentActivity && recentActivity.length > 0
        ? recentActivity.map(log => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fa-solid fa-circle-dot"></i>
                </div>
                <div class="activity-content">
                    <strong>${escapeHtml(log.action_type)}</strong>
                    <p>${escapeHtml(log.details || 'No details')}</p>
                    <span class="activity-time">${formatDate(log.timestamp)}</span>
                </div>
            </div>
        `).join('')
        : '<p class="no-activity">No recent activity</p>';

    content.innerHTML = `
        <div class="user-details-section">
            <h3><i class="fa-solid fa-user-circle"></i> Account Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Username:</label>
                    <span>${escapeHtml(user.username)}</span>
                </div>
                <div class="detail-item">
                    <label>Full Name:</label>
                    <span>${escapeHtml(user.full_name)}</span>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <span>${escapeHtml(user.email)}</span>
                </div>
                <div class="detail-item">
                    <label>Contact:</label>
                    <span>${user.contact_number || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <label>Role:</label>
                    <span>${getRoleBadge(user.role)}</span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span>${getStatusBadge(user.account_status)}</span>
                </div>
                <div class="detail-item">
                    <label>Verified:</label>
                    <span>${getVerifiedBadge(user.is_verified)}</span>
                </div>
                <div class="detail-item">
                    <label>Joined:</label>
                    <span>${formatDate(user.created_at)}</span>
                </div>
            </div>
        </div>

        ${user.plan_name ? `
        <div class="user-details-section">
            <h3><i class="fa-solid fa-id-card"></i> Membership Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Plan:</label>
                    <span>${escapeHtml(user.plan_name)}</span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span>${escapeHtml(user.membership_status || 'N/A')}</span>
                </div>
                <div class="detail-item">
                    <label>Start Date:</label>
                    <span>${formatDate(user.start_date)}</span>
                </div>
                <div class="detail-item">
                    <label>End Date:</label>
                    <span>${formatDate(user.end_date)}</span>
                </div>
            </div>
        </div>
        ` : ''}

        <div class="user-details-section">
            <h3><i class="fa-solid fa-history"></i> Recent Activity</h3>
            <div class="activity-timeline">
                ${activityHtml}
            </div>
        </div>
    `;

    panel.classList.add('active');
}

function closeUserDetailsPanel() {
    document.getElementById('userDetailsPanel').classList.remove('active');
}

// Edit user with O(1) HashTable lookup
async function editUser(userId) {
    const user = userHashTable.get(userId);
    if (!user) {
        alert('User not found');
        return;
    }

    showEditModal(user);
}

// Reset password (SECURE - Admin never sees password) with O(1) lookup
async function resetPassword(userId) {
    const user = userHashTable.get(userId);
    if (!user) {
        alert('User not found');
        return;
    }

    if (!confirm(`Send password reset email to ${user.full_name}?\n\nThey will set their own password. You will NEVER see it.`)) {
        return;
    }

    try {
        const response = await fetch('api/admin_users_api.php?action=resetPassword', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            const maskedEmail = data.email || 'their email';
            alert(`✓ Password reset email sent successfully to ${maskedEmail}\n\nThe user will receive an email with instructions to set their own password.`);
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    } catch (error) {
        console.error('Error resetting password:', error);
        alert('Failed to reset password: ' + (error.message || 'Network error. Please check your connection and try again.'));
    }
}

// Activate user with O(1) lookup
async function activateUser(userId) {
    const user = userHashTable.get(userId);
    if (!user) {
        alert('User not found');
        return;
    }

    if (!confirm(`Activate ${user.full_name}'s account?`)) {
        return;
    }

    try {
        const response = await fetch('api/admin_users_api.php?action=activateUser', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            alert('✓ User activated successfully');
            await refreshSingleUser(userId);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error activating user:', error);
        alert('Failed to activate user');
    }
}

// Update user profile with O(1) lookup
async function saveUserProfile(userId, updates) {
    // Check if role is being changed using HashTable - O(1)
    const user = userHashTable.get(userId);

    if (!user) {
        alert('User not found');
        return;
    }

    if (updates.role && user.role !== updates.role) {
        // Generate security code for role change
        await generateAndShowSecurityCode(userId, updates);
        return;
    }

    // Normal update
    await submitUserUpdate(userId, updates);
}

// Generate security code for role changes
async function generateAndShowSecurityCode(userId, updates) {
    try {
        const response = await fetch('api/admin_users_api.php?action=generateSecurityCode', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ purpose: 'CHANGE_USER_ROLE' })
        });

        const data = await response.json();

        if (data.success) {
            showSecurityCodeModal(data.code, userId, updates);
        } else {
            alert('Failed to generate security code: ' + data.message);
        }
    } catch (error) {
        console.error('Error generating security code:', error);
        alert('Failed to generate security code');
    }
}

// Submit user update with security code
async function submitUserUpdate(userId, updates) {
    try {
        updates.user_id = userId;

        const response = await fetch('api/admin_users_api.php?action=updateUserProfile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updates)
        });

        const data = await response.json();

        if (data.success) {
            alert('✓ User profile updated successfully');
            closeEditModal();

            // Optimized update: Refresh single user instead of reloading all
            await refreshSingleUser(userId);
        } else {
            if (data.requires_security_code) {
                alert('Security code required for this action');
            } else if (data.requires_permission) {
                alert(`Permission denied: You need ${data.requires_permission} permission`);
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error updating user:', error);
        alert('Failed to update user profile');
    }
}

// ==================== MODALS ====================

function createModals() {
    if (!document.getElementById('modals-container')) {
        const container = document.createElement('div');
        container.id = 'modals-container';
        document.body.appendChild(container);
    }
}

function showSecurityCodeModal(code, userId, updates) {
    const modal = `
        <div id="securityCodeModal" class="modal-overlay" onclick="closeSecurityCodeModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2><i class="fa-solid fa-shield-halved"></i> Security Code Required</h2>
                    <button onclick="closeSecurityCodeModal()" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="security-code-display">
                        <p>Enter this security code to confirm role change:</p>
                        <div class="code-box">${code}</div>
                        <p class="code-hint"><i class="fa-solid fa-clock"></i> This code expires in 5 minutes</p>
                    </div>
                    <div class="form-group">
                        <label>Enter Security Code</label>
                        <input type="text" id="securityCodeInput" placeholder="Enter code" class="form-control" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="closeSecurityCodeModal()" class="btn-secondary">Cancel</button>
                    <button onclick="confirmWithSecurityCode('${userId}', ${JSON.stringify(updates).replace(/"/g, '&quot;')})" class="btn-primary">
                        <i class="fa-solid fa-check"></i> Confirm Change
                    </button>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modals-container').innerHTML = modal;
    setTimeout(() => document.getElementById('securityCodeInput')?.focus(), 100);
}

function closeSecurityCodeModal() {
    const modal = document.getElementById('securityCodeModal');
    if (modal) modal.remove();
}

async function confirmWithSecurityCode(userId, updates) {
    const code = document.getElementById('securityCodeInput').value.trim();

    if (!code) {
        alert('Please enter the security code');
        return;
    }

    updates.security_code = code;
    closeSecurityCodeModal();
    await submitUserUpdate(userId, updates);
}

function showEditModal(user) {
    const modal = `
        <div id="editUserModal" class="modal-overlay" onclick="closeEditModal()">
            <div class="modal-content modal-large" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2><i class="fa-solid fa-user-edit"></i> Edit User: ${escapeHtml(user.full_name)}</h2>
                    <button onclick="closeEditModal()" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" onsubmit="return false;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="edit_name" value="${escapeHtml(user.full_name || '')}" class="form-control">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Role <span class="badge badge-warning"><i class="fa-solid fa-shield"></i> Security Code Required</span></label>
                                <select id="edit_role" class="form-control">
                                    <option value="member" ${user.role === 'member' ? 'selected' : ''}>Member</option>
                                    <option value="trainer" ${user.role === 'trainer' ? 'selected' : ''}>Trainer</option>
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Account Status</label>
                                <select id="edit_status" class="form-control">
                                    <option value="active" ${user.account_status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="locked" ${user.account_status === 'locked' ? 'selected' : ''}>Locked</option>
                                    <option value="pending" ${user.account_status === 'pending' ? 'selected' : ''}>Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="alert-info">
                            <i class="fa-solid fa-info-circle"></i>
                            <strong>Security Notes:</strong>
                            <ul style="margin: 8px 0 0 20px; padding: 0;">
                                <li>Role changes require a security code verification</li>
                                <li>Email/phone changes require user confirmation via email</li>
                                <li>Password resets are handled separately for security</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button onclick="closeEditModal()" class="btn-secondary">Cancel</button>
                    <button onclick="submitEditForm('${user.id}')" class="btn-primary">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modals-container').innerHTML = modal;
}

function closeEditModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) modal.remove();
}

function submitEditForm(userId) {
    const updates = {
        name: document.getElementById('edit_name').value,
        role: document.getElementById('edit_role').value,
        account_status: document.getElementById('edit_status').value
    };

    saveUserProfile(userId, updates);
}

// ==================== HELPER FUNCTIONS ====================

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const tbody = document.getElementById('usersTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fa-solid fa-exclamation-triangle" style="color: #ef4444;"></i>
                    <h3>Error</h3>
                    <p>${escapeHtml(message)}</p>
                </td>
            </tr>
        `;
    }
}

/**
 * Refresh single user data instead of reloading entire dataset
 * O(1) update instead of O(n) full reload
 */
async function refreshSingleUser(userId) {
    try {
        const response = await fetch(`api/admin_users_api.php?action=getUserDetails&user_id=${userId}`);
        const data = await response.json();

        if (data.success) {
            const oldUser = userHashTable.get(userId);
            const newUser = data.user;

            // Update in allUsers array
            const index = allUsers.findIndex(u => u.id === userId);
            if (index !== -1) {
                allUsers[index] = newUser;
            }

            // Update HashTable
            userHashTable.insert(userId, newUser);

            // Update indexes if role/status/verified changed
            if (oldUser) {
                updateIndexesForUser(oldUser, newUser);
            }

            // Reapply filters and render
            updateStatCards();
            applyFilters();
        }
    } catch (error) {
        console.error('Error refreshing user:', error);
        // Fallback to full reload
        await loadUsers();
    }
}

/**
 * Update index structures when user data changes
 */
function updateIndexesForUser(oldUser, newUser) {
    const userId = newUser.id;

    // Update role index
    if (oldUser.role !== newUser.role) {
        roleIndex[oldUser.role]?.delete(userId);
        roleIndex[newUser.role]?.add(userId);
    }

    // Update status index
    if (oldUser.account_status !== newUser.account_status) {
        statusIndex[oldUser.account_status]?.delete(userId);
        statusIndex[newUser.account_status]?.add(userId);
    }

    // Update verified index
    if (oldUser.is_verified !== newUser.is_verified) {
        const oldVerified = oldUser.is_verified == 1;
        const newVerified = newUser.is_verified == 1;

        if (oldVerified !== newVerified) {
            if (oldVerified) {
                verifiedIndex.verified.delete(userId);
                verifiedIndex.unverified.add(userId);
            } else {
                verifiedIndex.unverified.delete(userId);
                verifiedIndex.verified.add(userId);
            }
        }
    }

    // Update Trie if searchable fields changed
    if (oldUser.full_name !== newUser.full_name ||
        oldUser.username !== newUser.username ||
        oldUser.email !== newUser.email) {
        // Rebuild Trie (only needed when search fields change)
        buildSearchTrie();
    }
}

/**
 * Rebuild only the search Trie structure
 */
function buildSearchTrie() {
    searchTrie.clear();

    for (const user of allUsers) {
        const userId = user.id;

        if (user.full_name) {
            searchTrie.insert(user.full_name, userId);
        }
        if (user.username) {
            searchTrie.insert(user.username, userId);
        }
        if (user.email) {
            searchTrie.insert(user.email, userId);
        }
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==================== PERFORMANCE MONITORING ====================

/**
 * Log DSA performance metrics
 */
function logPerformanceMetrics() {
    console.log('=== DSA Performance Metrics ===');
    console.log('HashTable size:', userHashTable.size);
    console.log('Role distribution:', {
        members: roleIndex.member?.size || 0,
        trainers: roleIndex.trainer?.size || 0,
        admins: roleIndex.admin?.size || 0
    });
    console.log('Status distribution:', {
        active: statusIndex.active?.size || 0,
        locked: statusIndex.locked?.size || 0,
        pending: statusIndex.pending?.size || 0
    });
    console.log('Verified distribution:', {
        verified: verifiedIndex.verified?.size || 0,
        unverified: verifiedIndex.unverified?.size || 0
    });
    console.log('Current filter result size:', filteredUsers.length);
    console.log('==============================');
}

// Expose performance logging to console
window.logUserManagementPerformance = logPerformanceMetrics;
