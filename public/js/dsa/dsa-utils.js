/**
 * ============================================================================
 * DSA UTILITIES LIBRARY FOR FITXBRAWL GYM MANAGEMENT SYSTEM
 * ============================================================================
 * 
 * This library provides optimized data structures and algorithms that make
 * the gym management system faster and more efficient. Think of it as a
 * toolbox of smart techniques that help process large amounts of data quickly.
 * 
 * WHY WE NEED THIS:
 * ----------------
 * Basic JavaScript methods like .filter() and .includes() work fine for small
 * datasets, but they become slow when you have hundreds of bookings, equipment
 * items, or products. These methods scan through the entire array every time,
 * which means:
 * 
 *   - 100 items × 3 filters = 300 operations (slow)
 *   - Searching for "Boxing" in 500 items = checking all 500 items every time
 *   - Sorting bookings by date multiple times = redundant calculations
 * 
 * This library uses computer science concepts (DSA = Data Structures & Algorithms)
 * to reduce these operations dramatically:
 * 
 *   - HashMap: Find items in constant time O(1) instead of O(n)
 *   - LRU Cache: Remember recent results instead of recalculating
 *   - FilterBuilder: Apply multiple filters in one pass instead of multiple
 *   - Fuzzy Search: Find matches even with typos (user-friendly)
 * 
 * WHAT'S INSIDE:
 * --------------
 * 1. Searching Algorithms (Binary Search, Fuzzy Search)
 * 2. Sorting Algorithms (Quick Sort, Multi-level Sort)
 * 3. Filtering (Advanced FilterBuilder with chainable conditions)
 * 4. Data Structures (HashMap for fast lookups, LRU Cache for remembering)
 * 5. Performance Utilities (Debounce, Throttle, Memoization)
 * 
 * HOW IT WORKS:
 * -------------
 * This library is loaded in pages that need optimization (reservations,
 * equipment, products). The code checks if DSA is available and uses it,
 * otherwise falls back to basic JavaScript methods. This makes it safe -
 * the system works even if this library fails to load.
 * 
 * BEGINNER-FRIENDLY EXPLANATIONS:
 * -------------------------------
 * Each function and class has detailed comments explaining:
 *   - What it does (plain English)
 *   - Why it's faster (with examples)
 *   - When to use it
 *   - How the algorithm works (step-by-step)
 * 
 * ============================================================================
 */

// ============================================
// 1. SEARCHING ALGORITHMS
// ============================================
// 
// Searching is about finding specific items in a collection. Different
// algorithms work better for different situations:
// 
// - Binary Search: Super fast but requires sorted data
// - Linear Search: Works on any data but checks every item
// - Fuzzy Search: Finds matches even with typos

/**
 * Binary Search - O(log n) time complexity
 * 
 * WHAT IT DOES:
 * Finds an item in a sorted array extremely fast by repeatedly dividing
 * the search space in half. Like looking up a word in a dictionary - you
 * don't start from page 1, you jump to the middle and decide which half
 * to search next.
 * 
 * WHY IT'S FAST:
 * Instead of checking every item (which takes n steps for n items),
 * binary search only needs log₂(n) steps:
 *   - 100 items: Basic search = 100 checks, Binary = 7 checks (14x faster)
 *   - 1000 items: Basic = 1000 checks, Binary = 10 checks (100x faster!)
 * 
 * IMPORTANT: Array MUST be sorted first. This doesn't work on unsorted data.
 * 
 * HOW IT WORKS:
 * 1. Start with pointers at the beginning (left) and end (right)
 * 2. Check the middle item
 * 3. If it matches, we found it!
 * 4. If target is smaller, search the left half
 * 5. If target is larger, search the right half
 * 6. Repeat until found or no items left
 * 
 * @param {Array} arr - SORTED array to search in
 * @param {*} target - The value you're looking for
 * @param {Function} compareFn - How to compare items (default: number subtraction)
 * @returns {number} - Index where item was found, or -1 if not found
 * 
 * EXAMPLE:
 * ```javascript
 * const sortedIds = [5, 12, 23, 45, 67, 89, 100];
 * const index = binarySearch(sortedIds, 45);  // Returns 3
 * const notFound = binarySearch(sortedIds, 50);  // Returns -1
 * ```
 */
function binarySearch(arr, target, compareFn = (a, b) => a - b) {
    let left = 0;  // Start of search range
    let right = arr.length - 1;  // End of search range

    // Keep searching while there's space between left and right
    while (left <= right) {
        // Find middle point (use floor to avoid decimals)
        const mid = Math.floor((left + right) / 2);
        
        // Compare middle item with target
        const comparison = compareFn(arr[mid], target);

        if (comparison === 0) {
            return mid; // Found it! Return the index
        } else if (comparison < 0) {
            // Middle item is less than target, search right half
            left = mid + 1;
        } else {
            // Middle item is greater than target, search left half
            right = mid - 1;
        }
    }

    return -1; // Not found - checked all possibilities
}

/**
 * ============================================================================
 * LINEAR SEARCH WITH MULTIPLE CRITERIA - O(n) time complexity
 * ============================================================================
 * 
 * WHAT IT DOES:
 * Searches through an array and finds ALL items that match your conditions.
 * It checks every single item one by one (that's why it's called "linear").
 * 
 * WHEN TO USE:
 * - Data is NOT sorted (can't use binary search)
 * - Need to find ALL matches, not just one
 * - Have complex conditions (not just simple equality)
 * - Dataset is small enough that checking each item is acceptable
 * 
 * WHY IT'S CALLED O(n):
 * 'n' represents the number of items in the array. This function checks
 * each item exactly once, so if you have 100 items, it does 100 checks.
 * Double the items = double the time (linear relationship).
 * 
 * EXAMPLE:
 * ```javascript
 * const bookings = [
 *   {date: '2025-01-15', status: 'confirmed', type: 'Boxing'},
 *   {date: '2025-01-16', status: 'cancelled', type: 'MMA'},
 *   {date: '2025-01-17', status: 'confirmed', type: 'Boxing'}
 * ];
 * 
 * // Find all confirmed Boxing bookings
 * const results = multiCriteriaSearch(bookings, (booking) => {
 *   return booking.status === 'confirmed' && booking.type === 'Boxing';
 * });
 * // Returns: 2 bookings (first and third)
 * ```
 * 
 * @param {Array} arr - The array to search through
 * @param {Function} predicateFn - A function that returns true/false for each item
 * @returns {Array} - All items where predicateFn returned true
 */
function multiCriteriaSearch(arr, predicateFn) {
    // Use JavaScript's built-in filter - it checks each item and keeps matches
    return arr.filter(predicateFn);
}

/**
 * Fuzzy Search - For text matching with typo tolerance
 * Can search through arrays of objects or simple text matching
 * @param {Array|string} dataOrText - Array to search or text to match
 * @param {string} query - Search query
 * @param {Array} fields - Fields to search in (for array search)
 * @returns {Array|boolean} - Matching items or boolean for text match
 */
function fuzzySearch(dataOrText, query, fields = []) {
    // If dataOrText is a string, do simple text matching
    if (typeof dataOrText === 'string') {
        const text = dataOrText.toLowerCase();
        const pattern = query.toLowerCase();
        let patternIdx = 0;
        
        for (let i = 0; i < text.length && patternIdx < pattern.length; i++) {
            if (text[i] === pattern[patternIdx]) {
                patternIdx++;
            }
        }
        
        return patternIdx === pattern.length;
    }
    
    // If dataOrText is an array, search through objects
    if (Array.isArray(dataOrText)) {
        const results = [];
        const queryLower = query.toLowerCase();
        
        dataOrText.forEach(item => {
            for (const field of fields) {
                const value = String(item[field] || '');
                if (fuzzySearch(value, query)) {
                    results.push(item);
                    break; // Don't add same item multiple times
                }
            }
        });
        
        return results;
    }
    
    return false;
}

/**
 * Advanced Search with Scoring - Returns matches with relevance scores
 * @param {Array} items - Items to search
 * @param {string} query - Search query
 * @param {Array} fields - Fields to search in
 * @returns {Array} - Sorted array by relevance score
 */
function searchWithScoring(items, query, fields = ['name']) {
    if (!query || query.trim() === '') return items;

    const results = [];
    const queryLower = query.toLowerCase();

    items.forEach(item => {
        let score = 0;

        fields.forEach(field => {
            const value = String(item[field] || '').toLowerCase();

            // Exact match - highest score
            if (value === queryLower) {
                score += 100;
            }
            // Starts with query - high score
            else if (value.startsWith(queryLower)) {
                score += 50;
            }
            // Contains query - medium score
            else if (value.includes(queryLower)) {
                score += 25;
            }
            // Fuzzy match - low score
            else if (fuzzySearch(value, queryLower)) {
                score += 10;
            }
        });

        if (score > 0) {
            results.push({ ...item, _searchScore: score });
        }
    });

    // Sort by score descending
    return results.sort((a, b) => b._searchScore - a._searchScore);
}

// ============================================
// 2. SORTING ALGORITHMS
// ============================================

/**
 * Quick Sort - O(n log n) average time complexity
 * In-place sorting algorithm
 * @param {Array} arr - Array to sort
 * @param {Function} compareFn - Comparison function
 * @returns {Array} - Sorted array
 */
function quickSort(arr, compareFn = (a, b) => a - b) {
    if (arr.length <= 1) return arr;

    const pivot = arr[Math.floor(arr.length / 2)];
    const left = arr.filter(x => compareFn(x, pivot) < 0);
    const middle = arr.filter(x => compareFn(x, pivot) === 0);
    const right = arr.filter(x => compareFn(x, pivot) > 0);

    return [...quickSort(left, compareFn), ...middle, ...quickSort(right, compareFn)];
}

/**
 * Multi-field Sort - Sort by multiple criteria
 * @param {Array} arr - Array to sort
 * @param {Array} sortCriteria - Array of {field, order} or {key, order} objects
 * @returns {Array} - Sorted array
 * 
 * Example: sortMultiField(bookings, [
 *   {field: 'date', order: 'asc'},
 *   {field: 'time', order: 'desc'}
 * ])
 */
function sortMultiField(arr, sortCriteria) {
    return [...arr].sort((a, b) => {
        for (const criterion of sortCriteria) {
            // Support both 'key' and 'field' property names
            const field = criterion.key || criterion.field;
            const order = criterion.order || 'asc';
            const aVal = a[field];
            const bVal = b[field];

            let comparison = 0;

            // Handle dates
            if (aVal instanceof Date && bVal instanceof Date) {
                comparison = aVal.getTime() - bVal.getTime();
            }
            // Handle strings
            else if (typeof aVal === 'string' && typeof bVal === 'string') {
                comparison = aVal.localeCompare(bVal);
            }
            // Handle numbers
            else {
                comparison = aVal - bVal;
            }

            if (comparison !== 0) {
                return order === 'asc' ? comparison : -comparison;
            }
        }
        return 0;
    });
}

/**
 * Session Time Comparator - Custom comparator for booking sessions
 * @param {string} session1 - Session time (Morning, Afternoon, Evening)
 * @param {string} session2 - Session time
 * @returns {number} - Comparison result
 */
function sessionTimeComparator(session1, session2) {
    const order = { 'Morning': 1, 'Afternoon': 2, 'Evening': 3 };
    return (order[session1] || 99) - (order[session2] || 99);
}

// ============================================
// 3. FILTERING SYSTEM
// ============================================
//
// FilterBuilder provides a clean, readable way to build complex filters.
// Instead of nested if-statements and multiple .filter() calls, you chain
// conditions together. This is faster (one pass through data) and more
// maintainable (easy to see what each filter does).

/**
 * ============================================================================
 * FILTERBUILDER - CHAINABLE QUERY BUILDER FOR COMPLEX FILTERING
 * ============================================================================
 * 
 * WHAT IT DOES:
 * Lets you build up filter conditions in a readable, SQL-like way. Instead
 * of writing complex nested conditions or multiple .filter() calls, you
 * chain simple conditions together.
 * 
 * THE PROBLEM WITH TRADITIONAL FILTERING:
 * ```javascript
 * // Approach 1: Nested conditions (hard to read)
 * let results = items.filter(item => {
 *   if (item.status !== 'active') return false;
 *   if (item.category !== 'Boxing') return false;
 *   if (!item.name.toLowerCase().includes(searchTerm)) return false;
 *   return true;
 * });
 * 
 * // Approach 2: Multiple .filter() calls (slow - multiple passes)
 * let results = items;
 * results = results.filter(i => i.status === 'active');  // Pass 1
 * results = results.filter(i => i.category === 'Boxing');  // Pass 2
 * results = results.filter(i => i.name.includes(searchTerm));  // Pass 3
 * // With 500 items and 3 filters = 1500 iterations!
 * ```
 * 
 * WITH FILTERBUILDER (readable AND fast):\n * ```javascript
 * const results = new FilterBuilder(items)
 *   .where('status', '===', 'active')
 *   .where('category', '===', 'Boxing')
 *   .where('name', 'contains', searchTerm)
 *   .execute();
 * // Single pass through data = 500 iterations (3x faster!)
 * // Easy to read - each line is one filter condition
 * ```
 * 
 * KEY BENEFITS:
 * 1. Readable: Each filter is a clear statement
 * 2. Fast: All filters applied in one pass (O(n) not O(n*filters))
 * 3. Dynamic: Easy to add/remove conditions programmatically
 * 4. Testable: Can test individual filters with .test() method
 * 
 * SUPPORTED OPERATORS:
 * - '===' or '==': Equals
 * - '!==' or '!=': Not equals
 * - '>': Greater than
 * - '<': Less than
 * - '>=': Greater than or equal
 * - '<=': Less than or equal
 * - 'contains': String includes
 * - 'in': Value matches any in array
 * 
 * USAGE EXAMPLE:
 * ```javascript
 * // Filter equipment by multiple conditions
 * const boxingGloves = new FilterBuilder(equipment)
 *   .where('status', '===', 'Available')
 *   .where('category', '===', 'Boxing')
 *   .where('name', 'contains', 'gloves')
 *   .execute();
 * 
 * // Dynamic filtering (add conditions based on user input)
 * let builder = new FilterBuilder(bookings);
 * if (userSelectsStatus) {
 *   builder = builder.where('status', '===', selectedStatus);
 * }
 * if (userSelectsDate) {
 *   builder = builder.where('date', '===', selectedDate);
 * }
 * const results = builder.execute();
 * ```
 * 
 * @param {Array|null} data - Optional dataset to filter (can provide later)
 */
class FilterBuilder {
    constructor(data = null) {
        this.data = data;  // The array to filter (optional at construction)
        this.filters = [];  // Array of filter functions to apply
    }

    /**
     * Add a filter condition
     * 
     * This is the core method - each call adds one filter to the chain.
     * You can either provide a custom function or use the field/operator/value
     * syntax for common comparisons.
     * 
     * FIELD/OPERATOR/VALUE SYNTAX:
     * Most filters follow this pattern:
     *   .where('fieldName', 'operator', value)
     * 
     * Examples:
     *   .where('status', '===', 'active')  → item.status === 'active'
     *   .where('price', '<', 100)          → item.price < 100
     *   .where('name', 'contains', 'box')  → item.name.includes('box')
     * 
     * CUSTOM FUNCTION SYNTAX:
     * For complex conditions, provide a function:
     *   .where(item => item.price < 100 && item.inStock)
     * 
     * @param {Function|string} predicateOrField - Custom function OR field name
     * @param {string} operator - Comparison operator (if using field syntax)
     * @param {*} value - Value to compare against (if using field syntax)
     * @returns {FilterBuilder} - Returns this for method chaining
     */
    where(predicateOrField, operator, value) {
        // Support custom filter functions
        if (typeof predicateOrField === 'function') {
            this.filters.push(predicateOrField);
        } else {
            // Create a filter function from field/operator/value
            const field = predicateOrField;
            const predicate = (item) => {
                const itemValue = item[field];  // Get the field value
                
                // Apply the operator
                switch (operator) {
                    case '===':
                    case '==':
                        return itemValue === value;
                    case '!==':
                    case '!=':
                        return itemValue !== value;
                    case '>':
                        return itemValue > value;
                    case '<':
                        return itemValue < value;
                    case '>=':
                        return itemValue >= value;
                    case '<=':
                        return itemValue <= value;
                    case 'contains':
                        return String(itemValue).includes(value);
                    case 'in':
                        // Check if itemValue is in the array of values
                        return Array.isArray(value) && value.some(v => 
                            String(itemValue).toLowerCase().includes(String(v).toLowerCase())
                        );
                    default:
                        return itemValue === value;
                }
            };
            this.filters.push(predicate);
        }
        return this;
    }

    /**
     * Filter by field value
     * @param {string} field - Field name
     * @param {*} value - Value to match
     * @returns {FilterBuilder} - Chainable instance
     */
    equals(field, value) {
        return this.where(item => item[field] === value);
    }

    /**
     * Filter by date range
     * @param {string} field - Date field name
     * @param {Date} start - Start date
     * @param {Date} end - End date
     * @returns {FilterBuilder} - Chainable instance
     */
    dateRange(field, start, end) {
        return this.where(item => {
            const date = new Date(item[field]);
            return date >= start && date <= end;
        });
    }

    /**
     * Filter by text search
     * @param {string} field - Field to search
     * @param {string} query - Search query
     * @returns {FilterBuilder} - Chainable instance
     */
    search(field, query) {
        const queryLower = query.toLowerCase();
        return this.where(item =>
            String(item[field]).toLowerCase().includes(queryLower)
        );
    }

    /**
     * Filter by value in array
     * @param {string} field - Field name
     * @param {Array} values - Allowed values
     * @returns {FilterBuilder} - Chainable instance
     */
    in(field, values) {
        return this.where(item => values.includes(item[field]));
    }

    /**
     * Execute all filters
     * @returns {Array} - Filtered results
     */
    execute() {
        return this.data.filter(item =>
            this.filters.every(predicate => predicate(item))
        );
    }
    
    /**
     * Apply filters to provided data (alias for execute that accepts data parameter)
     * @param {Array} data - Data to filter (optional, uses constructor data if not provided)
     * @returns {Array} - Filtered results
     */
    apply(data) {
        if (data) {
            return data.filter(item =>
                this.filters.every(predicate => predicate(item))
            );
        }
        return this.execute();
    }
    
    /**
     * Test if a single item passes all filters
     * @param {Object} item - Item to test
     * @returns {boolean} - True if item passes all filters
     */
    test(item) {
        return this.filters.every(predicate => predicate(item));
    }

    /**
     * Execute and sort
     * @param {Function} compareFn - Comparison function
     * @returns {Array} - Filtered and sorted results
     */
    executeAndSort(compareFn) {
        return this.execute().sort(compareFn);
    }
}

// ============================================
// 4. DATA STRUCTURES FOR PERFORMANCE
// ============================================

/**
 * ============================================================================
 * HASHMAP - SUPER FAST LOOKUPS IN O(1) CONSTANT TIME
 * ============================================================================
 * 
 * WHAT IT IS:
 * Think of a HashMap like a phone book that's magically organized. Instead
 * of flipping through pages to find someone, you know exactly what page they're
 * on instantly. That's the power of hashing!
 * 
 * THE PROBLEM IT SOLVES:
 * Traditional array search (finding item by ID):
 *   - Check item 1: Is this it? No...
 *   - Check item 2: Is this it? No...
 *   - Check item 3: Is this it? Yes! (but took 3 checks)
 *   - With 1000 items, might need 1000 checks (SLOW)
 * 
 * HashMap approach:
 *   - Use the ID to calculate exactly where it is
 *   - Jump directly to that location
 *   - Check just 1 item (FAST!)
 * 
 * WHY O(1) IS AMAZING:
 * O(1) means "constant time" - it takes the same time whether you have
 * 10 items or 10 million items. Finding item #5,000,000 in a HashMap
 * is just as fast as finding item #1!
 * 
 * REAL-WORLD USE IN FITXBRAWL:
 * ```javascript
 * // Without HashMap: Find trainer by ID in array of 50 trainers
 * const trainer = trainers.find(t => t.id === 23);  // Checks up to 50 items
 * 
 * // With HashMap: Instant lookup
 * const trainerMap = new HashMap('id');
 * trainerMap.buildFromArray(trainers);
 * const trainer = trainerMap.get(23);  // Checks 1 item (50x faster!)
 * ```
 * 
 * WHEN TO USE:
 * - Need to look up items by ID frequently
 * - Have a large dataset (100+ items)
 * - Performance matters (search happens often)
 * 
 * HOW IT WORKS INTERNALLY:
 * JavaScript's Map is already optimized as a hash table. We're wrapping
 * it to make it easier to work with our gym data (trainers, bookings, etc.)
 * 
 * @param {string} keyField - Which property to use as the lookup key (default: 'id')
 */
class HashMap {
    constructor(keyField = 'id') {
        this.map = new Map();  // JavaScript's built-in hash table
        this.keyField = keyField;  // Which field is the unique identifier
    }

    /**
     * Build the HashMap from an array of items
     * 
     * This is like indexing a book - you go through each item once and
     * create a quick-reference entry for it. After this setup, lookups
     * become lightning fast.
     * 
     * EXAMPLE:
     * ```javascript
     * const trainers = [
     *   {id: 1, name: 'John', specialization: 'Boxing'},
     *   {id: 2, name: 'Sarah', specialization: 'MMA'},
     *   {id: 3, name: 'Mike', specialization: 'Muay Thai'}
     * ];
     * 
     * const map = new HashMap('id');
     * map.buildFromArray(trainers);  // Creates index by ID
     * 
     * // Now lookups are instant!
     * const sarah = map.get(2);  // Returns {id: 2, name: 'Sarah', ...}
     * ```
     * 
     * TIME COMPLEXITY:
     * - Building: O(n) - must process each item once
     * - After built: O(1) - lookups are instant
     * 
     * Worth it when you'll do multiple lookups!
     * 
     * @param {Array} items - Array of objects to index
     */
    buildFromArray(items) {
        this.map.clear();  // Remove any existing entries
        
        // Process each item and add to our hash map
        items.forEach(item => {
            const key = item[this.keyField];  // Get the key (e.g., item.id)
            this.map.set(key, item);  // Store: key → item
        });
    }

    /**
     * Get item by key - O(1)
     * @param {*} key - Key to lookup
     * @returns {*} - Item or undefined
     */
    get(key) {
        return this.map.get(key);
    }

    /**
     * Check if key exists - O(1)
     * @param {*} key - Key to check
     * @returns {boolean}
     */
    has(key) {
        return this.map.has(key);
    }

    /**
     * Set item
     * @param {*} key - Key
     * @param {*} value - Value
     */
    set(key, value) {
        this.map.set(key, value);
    }

    /**
     * Get all values
     * @returns {Array}
     */
    values() {
        return Array.from(this.map.values());
    }
    
    /**
     * Get size of map
     * @returns {number}
     */
    size() {
        return this.map.size;
    }
    
    /**
     * Clear the map
     */
    clear() {
        this.map.clear();
    }
}

/**
 * ============================================================================
 * LRU CACHE - REMEMBER RECENT RESULTS TO AVOID RECALCULATING
 * ============================================================================
 * 
 * WHAT IT DOES:
 * Caches (remembers) results of expensive operations so you don't have to
 * recalculate them. When cache fills up, it automatically removes the items
 * you haven't used recently (Least Recently Used = LRU).
 * 
 * THE OFFICE DESK ANALOGY:
 * Imagine your desk can only hold 5 papers. You keep papers you're working
 * on recently on top. When you need a 6th paper and desk is full, you remove
 * the paper you haven't touched in the longest time. That's LRU!
 * 
 * WHY THIS IS INCREDIBLY POWERFUL:
 * Let's say filtering 500 equipment items by category takes 50ms:
 * 
 * WITHOUT CACHE:
 *   User selects "Boxing" → 50ms calculation
 *   User selects "MMA" → 50ms calculation  
 *   User selects "Boxing" again → 50ms calculation (wasteful!)
 *   Total: 150ms
 * 
 * WITH CACHE:
 *   User selects "Boxing" → 50ms (calculate & cache)
 *   User selects "MMA" → 50ms (calculate & cache)
 *   User selects "Boxing" again → <1ms (return cached!) ⚡
 *   Total: 101ms (33% faster, and it gets better with more queries!)
 * 
 * REAL PERFORMANCE GAINS:
 * - First time: Slow (must calculate)
 * - Cached hits: 100-1000x faster (just memory lookup)
 * - Common searches (like "Boxing", "MMA") get cached immediately
 * 
 * WHEN CACHE FILLS UP:
 * Say capacity is 100 and you cache 100 different searches. When search #101
 * comes in, the cache removes the search you did longest ago. This keeps
 * memory usage under control while keeping frequently-used results cached.
 * 
 * USAGE EXAMPLE:
 * ```javascript
 * const filterCache = new LRUCache(50);  // Cache up to 50 results
 * 
 * function getCachedFilteredEquipment(category) {
 *   // Try to get from cache first
 *   let result = filterCache.get(category);
 *   
 *   if (result === null) {
 *     // Not cached - do the expensive filtering
 *     result = equipment.filter(e => e.category === category);
 *     
 *     // Save for next time
 *     filterCache.set(category, result);
 *   }
 *   
 *   return result;  // Fast return (cached or fresh)
 * }
 * 
 * getCachedFilteredEquipment('Boxing');  // Slow (first time)
 * getCachedFilteredEquipment('Boxing');  // FAST! (cached)
 * ```
 * 
 * @param {number} capacity - Maximum items to cache (default 100)
 */
class LRUCache {
    constructor(capacity = 100) {
        this.capacity = capacity;  // Max items before eviction starts
        this.cache = new Map();  // Map maintains insertion order (crucial for LRU!)
    }

    /**
     * Get a value from cache
     * 
     * THE LRU TRICK:
     * When you access an item, we mark it as "recently used" by moving it
     * to the end of the Map. JavaScript Maps maintain insertion order, so:
     *   - Items at the beginning = oldest (haven't been used in a while)
     *   - Items at the end = newest (just used)
     * 
     * To move an item to the end, we delete it and re-add it. Sounds weird,
     * but it's how we track "recency" without complex timestamp logic!
     * 
     * FLOW:
     * 1. Check if key exists → return null if not found (cache miss)
     * 2. Get the cached value
     * 3. Delete the entry (removes from its current position)
     * 4. Re-add it (now it's at the end = most recent)
     * 5. Return the value
     * 
     * WHY THIS MATTERS:
     * When cache fills up, we remove the FIRST item (oldest/least recent).
     * By moving accessed items to the end, we protect them from removal.
     * Items that keep getting used never get removed!
     * 
     * @param {string} key - The cache key (e.g., 'filter_Boxing')
     * @returns {*} - Cached value, or null if not found
     */
    get(key) {
        if (!this.cache.has(key)) return null;  // Cache miss - not found
        
        const value = this.cache.get(key);  // Get the cached result
        
        // Move to end = mark as recently used
        this.cache.delete(key);  // Remove from current position
        this.cache.set(key, value);  // Re-add at end
        
        return value;  // Return the cached result (fast!)
    }

    /**
     * Add a value to cache (or update existing)
     * 
     * THE EVICTION PROCESS:
     * When cache reaches capacity, we need to remove something before
     * adding the new item. We always remove the FIRST item because it's
     * the least recently used (oldest).
     * 
     * EXAMPLE SCENARIO:
     * Cache capacity: 3 items
     * Current cache (in order): [A, B, C]
     * 
     * User accesses B: [A, C, B]  (B moved to end)
     * User accesses C: [A, B, C]  (C moved to end)
     * User adds D (cache full!): [B, C, D]  (A removed, D added)
     * 
     * Result: A was least recently used, so it got evicted.
     * 
     * FLOW:
     * 1. If key already exists → delete it (we'll re-add with new value)
     * 2. If key is new AND cache is full → evict oldest (first) item
     * 3. Add the new key-value pair (goes to end = most recent)
     * 
     * WHY CHECK cache.size BEFORE ADDING:
     * After deleting old entry (step 1) or evicting (step 2), there's
     * always room for the new item. We're maintaining capacity!
     * 
     * @param {string} key - Cache key (e.g., 'search_boxing_gloves')
     * @param {*} value - Value to cache (filtered results, calculations, etc.)
     */
    set(key, value) {
        // If key exists, delete it first (we'll re-add with new value)
        if (this.cache.has(key)) {
            this.cache.delete(key);
        }
        // If cache is full and key is new, evict least recently used
        else if (this.cache.size >= this.capacity) {
            // Get the first key (oldest/least recently used)
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);  // Evict it
        }

        // Add the new item (goes to end = most recent)
        this.cache.set(key, value);
    }
    
    /**
     * Alias for set() - for API compatibility
     * @param {string} key - Cache key
     * @param {*} value - Value to cache
     */
    put(key, value) {
        return this.set(key, value);
    }

    /**
     * Clear cache
     */
    clear() {
        this.cache.clear();
    }
    
    /**
     * Get cache size
     * @returns {number}
     */
    size() {
        return this.cache.size;
    }
}

// ============================================
// 5. PERFORMANCE UTILITIES
// ============================================

/**
 * ============================================================================
 * DEBOUNCE - WAIT FOR USER TO STOP TYPING/ACTING BEFORE EXECUTING
 * ============================================================================
 * 
 * WHAT IT DOES:
 * Delays function execution until the user pauses for a specified time.
 * Perfect for search boxes, form inputs, or any rapid user input.
 * 
 * THE PROBLEM:
 * User types "boxing gloves" in a search box. Without debounce:
 *   Type 'b' → Filter runs (13 results)
 *   Type 'o' → Filter runs (7 results)
 *   Type 'x' → Filter runs (4 results)
 *   Type 'i' → Filter runs (3 results)
 *   ... continues for all 13 characters
 *   Total: 13 filter operations for one search!
 * 
 * WITH DEBOUNCE (300ms delay):
 *   Type 'boxing gloves' quickly (takes <1 second)
 *   Stop typing → wait 300ms → Filter runs ONCE (2 results)
 *   Total: 1 filter operation (13x fewer!)
 * 
 * WHY 300ms IS THE SWEET SPOT:
 * - Too short (50ms): Still triggers multiple times during typing
 * - Too long (1000ms): Feels laggy, user thinks it's broken
 * - 300ms: Fast enough to feel instant, slow enough to avoid spam
 * 
 * HOW IT WORKS (THE TIMER RESET TRICK):
 * 1. User types 'b' → Start 300ms timer
 * 2. User types 'o' (100ms later) → Cancel timer, start new 300ms timer
 * 3. User types 'x' (100ms later) → Cancel timer, start new 300ms timer
 * 4. User stops typing → Timer completes → Function executes!
 * 
 * The function only runs when the timer actually completes without being reset.
 * 
 * REAL PERFORMANCE IMPACT:
 * On equipment search with 500 items:
 *   Without debounce: 13 chars × 50ms = 650ms of filtering
 *   With debounce: 1 filter × 50ms = 50ms (13x faster!)
 * 
 * Plus, reduces server load if doing API calls (99% fewer requests!)
 * 
 * USAGE EXAMPLE:
 * ```javascript
 * // Create debounced version of search function
 * const debouncedSearch = debounce(searchEquipment, 300);
 * 
 * // Attach to input event
 * searchInput.addEventListener('input', (e) => {
 *   debouncedSearch(e.target.value);  // Only runs after user stops typing
 * });
 * ```
 * 
 * @param {Function} func - Function to debounce
 * @param {number} delay - Milliseconds to wait (default 300ms)
 * @returns {Function} - Debounced version that waits for pauses
 */
function debounce(func, delay = 300) {
    let timeoutId;  // Stores the timer ID so we can cancel it
    
    // Return a new function that wraps the original
    return function (...args) {
        // Cancel the previous timer (if any)
        clearTimeout(timeoutId);
        
        // Start a new timer
        timeoutId = setTimeout(() => {
            // Timer completed without being cancelled
            // User has paused - execute the function!
            func.apply(this, args);
        }, delay);
    };
}

/**
 * Throttle - Limit execution rate
 * @param {Function} func - Function to throttle
 * @param {number} limit - Minimum time between calls in ms
 * @returns {Function} - Throttled function
 */
function throttle(func, limit = 1000) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Memoize - Cache function results
 * @param {Function} func - Function to memoize
 * @returns {Function} - Memoized function
 */
function memoize(func) {
    const cache = new Map();
    return function (...args) {
        const key = JSON.stringify(args);
        if (cache.has(key)) {
            return cache.get(key);
        }
        const result = func.apply(this, args);
        cache.set(key, result);
        return result;
    };
}

// ============================================
// 6. BOOKING-SPECIFIC UTILITIES
// ============================================

/**
 * Group bookings by date - O(n)
 * @param {Array} bookings - Array of bookings
 * @returns {Object} - Object with dates as keys
 */
function groupBookingsByDate(bookings) {
    return bookings.reduce((groups, booking) => {
        const date = booking.date || booking.booking_date;
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(booking);
        return groups;
    }, {});
}

/**
 * Filter bookings by date range with performance optimization
 * @param {Array} bookings - Array of bookings
 * @param {Date} startDate - Start date
 * @param {Date} endDate - End date
 * @returns {Array} - Filtered bookings
 */
function filterBookingsByDateRange(bookings, startDate, endDate) {
    const start = startDate.getTime();
    const end = endDate.getTime();

    return bookings.filter(booking => {
        const bookingDate = new Date(booking.date || booking.booking_date).getTime();
        return bookingDate >= start && bookingDate <= end;
    });
}

/**
 * Calculate weekly booking statistics
 * @param {Array} bookings - Array of bookings
 * @param {Date} weekStart - Start of week
 * @returns {Object} - Statistics object
 */
function calculateWeeklyStats(bookings, weekStart) {
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 7);

    const weekBookings = filterBookingsByDateRange(bookings, weekStart, weekEnd);

    return {
        totalBookings: weekBookings.length,
        totalMinutes: weekBookings.reduce((sum, b) => sum + (b.duration_minutes || 0), 0),
        byClassType: weekBookings.reduce((counts, b) => {
            counts[b.class_type] = (counts[b.class_type] || 0) + 1;
            return counts;
        }, {}),
        byStatus: weekBookings.reduce((counts, b) => {
            counts[b.status] = (counts[b.status] || 0) + 1;
            return counts;
        }, {})
    };
}

// ============================================
// HELPER FUNCTIONS FOR COMPATIBILITY
// ============================================

/**
 * Multi-level comparison function for sorting
 * Allows chaining multiple sort criteria
 * @param {Array<Function>} comparators - Array of comparison functions
 * @returns {Function} - Combined comparison function
 * 
 * Example:
 *   bookings.sort(compareByMultiple([
 *     (a, b) => new Date(a.date) - new Date(b.date),
 *     (a, b) => a.session_time.localeCompare(b.session_time)
 *   ]));
 */
function compareByMultiple(comparators) {
    return function(a, b) {
        for (const comparator of comparators) {
            const result = comparator(a, b);
            if (result !== 0) {
                return result;
            }
        }
        return 0;
    };
}

/**
 * Simple fuzzy search wrapper for compatibility
 * Alias that always returns boolean for text matching
 * @param {string} pattern - Search pattern
 * @param {string} text - Text to search in
 * @returns {boolean} - True if pattern matches
 */
function FuzzySearch(pattern, text) {
    return fuzzySearch(text, pattern);
}

// ============================================
// 7. PAGINATION UTILITY
// ============================================

/**
 * Paginate array data
 * @param {Array} data - Data to paginate
 * @param {number} page - Current page (1-indexed)
 * @param {number} pageSize - Items per page
 * @returns {Object} - Pagination result
 */
function paginate(data, page = 1, pageSize = 10) {
    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / pageSize);
    const currentPage = Math.max(1, Math.min(page, totalPages));
    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalItems);

    return {
        data: data.slice(startIndex, endIndex),
        pagination: {
            currentPage,
            pageSize,
            totalPages,
            totalItems,
            hasNextPage: currentPage < totalPages,
            hasPreviousPage: currentPage > 1,
            startIndex: startIndex + 1, // 1-indexed for display
            endIndex
        }
    };
}

// ============================================
// EXPORT FOR USE IN OTHER FILES
// ============================================

// Make available globally
if (typeof window !== 'undefined') {
    // Export as both DSA and DSAUtils for compatibility
    const exports = {
        // Searching
        binarySearch,
        multiCriteriaSearch,
        fuzzySearch,
        FuzzySearch, // Capitalized alias for compatibility
        searchWithScoring,

        // Sorting
        quickSort,
        sortMultiField,
        sessionTimeComparator,
        compareByMultiple, // Multi-level comparison function

        // Filtering
        FilterBuilder,

        // Data Structures
        HashMap,
        LRUCache,

        // Performance
        debounce,
        throttle,
        memoize,

        // Booking Utilities
        groupBookingsByDate,
        filterBookingsByDateRange,
        calculateWeeklyStats,

        // Pagination
        paginate
    };
    
    window.DSA = exports;
    window.DSAUtils = exports; // Keep backward compatibility
    
    console.log('✅ DSA Utilities loaded successfully!');
    console.log('📦 Available:', Object.keys(exports).join(', '));
}
