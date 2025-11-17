/**
 * DSA INTEGRATION FOR RESERVATIONS.JS
 * This extends the existing reservations.js with DSA utilities
 */

console.log('[DSA-INTEGRATION] Starting to load reservations DSA integration...');
console.log('[DSA-INTEGRATION] Checking for DSA object:', typeof DSA);

// Wait for DSA utils to be available
if (typeof DSA === 'undefined') {
    console.error('❌ DSA utilities not loaded! Please include dsa-utils.js before this file.');
    console.error('Make sure dsa-utils.js is loaded first in the page.');
} else {
    console.log('[DSA-INTEGRATION] ✓ DSA object found, proceeding with integration...');
}

(function() {
    'use strict';
    
    console.log('[DSA-INTEGRATION] Inside IIFE, starting integration...');
    
    // ===================================
    // ENHANCED BOOKING FILTER WITH DSA
    // ===================================
    
    // Store original filter function
    const originalApplyFilter = window.applyBookingsFilter;
    
    // Enhanced filter using FilterBuilder
    window.applyBookingsFilter = function() {
        const filterValue = document.getElementById('classFilter')?.value || 'all';
        
        // Use DSA FilterBuilder for efficient filtering
        const upcomingFilter = new DSA.FilterBuilder(allBookingsData.upcoming || []);
        const pastFilter = new DSA.FilterBuilder(allBookingsData.past || []);
        const cancelledFilter = new DSA.FilterBuilder(allBookingsData.cancelled || []);
        
        // Apply class type filter
        if (filterValue !== 'all') {
            upcomingFilter.where('class_type', '===', filterValue);
            pastFilter.where('class_type', '===', filterValue);
            cancelledFilter.where('class_type', '===', filterValue);
        }
        
        // Get filtered results
        const filteredUpcoming = upcomingFilter.execute();
        const filteredPast = pastFilter.execute();
        const filteredCancelled = cancelledFilter.execute();
        
        // Render filtered data
        renderBookingList('upcomingBookings', filteredUpcoming);
        renderBookingList('pastBookings', filteredPast);
        renderBookingList('cancelledBookings', filteredCancelled);
        
        // Update counts with filtered data
        document.getElementById('upcomingCount').textContent = filteredUpcoming.length;
        document.getElementById('pastCount').textContent = filteredPast.length;
        document.getElementById('cancelledCount').textContent = filteredCancelled.length;
        
        console.log('[DSA] Filtered bookings:', {
            upcoming: filteredUpcoming.length,
            past: filteredPast.length,
            cancelled: filteredCancelled.length
        });
    };
    
    // ===================================
    // ENHANCED BOOKING SORT WITH DSA
    // ===================================
    
    /**
     * Sort bookings by multiple criteria using DSA sortMultiField
     */
    window.sortBookings = function(bookings, type = 'upcoming') {
        if (!bookings || bookings.length === 0) return bookings;
        
        const criteria = type === 'upcoming' 
            ? [
                { key: 'date', order: 'asc' },
                { key: 'session_time', order: 'asc' }
            ]
            : [
                { key: 'date', order: 'desc' },
                { key: 'session_time', order: 'desc' }
            ];
        
        const sorted = DSA.sortMultiField(bookings, criteria);
        console.log(`[DSA] Sorted ${type} bookings:`, sorted.length);
        return sorted;
    };
    
    // ===================================
    // ENHANCED TRAINER SEARCH WITH DSA
    // ===================================
    
    /**
     * Search trainers using fuzzy search
     */
    window.searchTrainers = function(query) {
        if (!query || query.trim() === '') {
            // Show all trainers
            if (typeof renderTrainers === 'function') {
                renderTrainers(window.cachedTrainers || []);
            }
            return;
        }
        
        const trainers = window.cachedTrainers || [];
        if (trainers.length === 0) return;
        
        // Use DSA fuzzy search
        const results = DSA.fuzzySearch(trainers, query, ['name', 'specialization']);
        
        console.log(`[DSA] Trainer search for "${query}":`, results.length, 'results');
        
        if (typeof renderTrainers === 'function') {
            renderTrainers(results);
        }
    };
    
    // ===================================
    // ENHANCED DEBOUNCED API CALLS
    // ===================================
    
    // Debounce availability checks
    const debouncedAvailabilityCheck = DSA.debounce(function(trainerId, date) {
        console.log('[DSA] Debounced availability check:', { trainerId, date });
        if (typeof loadTrainerAvailability === 'function') {
            loadTrainerAvailability();
        }
    }, 500);
    
    // Expose debounced function
    window.debouncedAvailabilityCheck = debouncedAvailabilityCheck;
    
    // ===================================
    // MEMOIZED CALCULATIONS
    // ===================================
    
    // Memoize weekly booking calculations
    const getBookingCountMemoized = DSA.memoize(function(dateStr) {
        if (!dateStr) return 0;
        
        const weekBounds = getWeekBoundaries(dateStr);
        const count = (allBookingsData.all || []).filter(booking => {
            if (booking.status !== 'confirmed' && booking.status !== 'completed') {
                return false;
            }
            return booking.date >= weekBounds.start && booking.date <= weekBounds.end;
        }).length;
        
        console.log(`[DSA] Memoized booking count for ${dateStr}:`, count);
        return count;
    });
    
    window.getBookingCountMemoized = getBookingCountMemoized;
    
    // Memoize duration calculations
    const calculateDurationMemoized = DSA.memoize(function(startTime, endTime) {
        if (!startTime || !endTime) return 0;
        
        const start = parseTime(startTime);
        const end = parseTime(endTime);
        
        if (!start || !end || end <= start) return 0;
        
        const durationMinutes = (end - start) / (1000 * 60);
        console.log(`[DSA] Memoized duration: ${startTime} - ${endTime} = ${durationMinutes}m`);
        return durationMinutes;
    });
    
    window.calculateDurationMemoized = calculateDurationMemoized;
    
    // ===================================
    // LRU CACHE FOR TRAINER DATA
    // ===================================
    
    const trainerCache = new DSA.LRUCache(20); // Cache up to 20 trainer availability results
    
    /**
     * Get trainer availability with caching
     */
    window.getCachedTrainerAvailability = function(trainerId, date) {
        const cacheKey = `${trainerId}_${date}`;
        
        // Check cache first
        const cached = trainerCache.get(cacheKey);
        if (cached) {
            console.log('[DSA] Using cached trainer availability:', cacheKey);
            return Promise.resolve(cached);
        }
        
        // Fetch from API
        console.log('[DSA] Fetching trainer availability from API:', cacheKey);
        const formData = new FormData();
        formData.append('trainer_id', trainerId);
        formData.append('date', date);
        
        return fetch('api/get_trainer_availability.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store in cache
                trainerCache.set(cacheKey, data);
                console.log('[DSA] Cached trainer availability:', cacheKey);
            }
            return data;
        });
    };
    
    // ===================================
    // HASHMAP FOR QUICK BOOKING LOOKUP
    // ===================================
    
    const bookingHashMap = new DSA.HashMap();
    
    /**
     * Index bookings for O(1) lookup
     */
    window.indexBookings = function(bookings) {
        if (!bookings || !Array.isArray(bookings)) return;
        
        bookingHashMap.clear();
        
        bookings.forEach(booking => {
            bookingHashMap.set(booking.id, booking);
            
            // Also index by date for quick date lookups
            const dateKey = `date_${booking.date}`;
            const dateBookings = bookingHashMap.get(dateKey) || [];
            dateBookings.push(booking);
            bookingHashMap.set(dateKey, dateBookings);
        });
        
        console.log('[DSA] Indexed bookings:', bookings.length, 'records');
    };
    
    /**
     * Get booking by ID with O(1) lookup
     */
    window.getBookingById = function(bookingId) {
        return bookingHashMap.get(bookingId);
    };
    
    /**
     * Get bookings for a specific date with O(1) lookup
     */
    window.getBookingsByDate = function(date) {
        return bookingHashMap.get(`date_${date}`) || [];
    };
    
    // ===================================
    // THROTTLED CALENDAR RENDERING
    // ===================================
    
    // Throttle calendar updates to prevent excessive redraws
    const throttledRenderCalendar = DSA.throttle(function() {
        console.log('[DSA] Throttled calendar render');
        if (typeof renderCalendar === 'function') {
            renderCalendar();
        }
    }, 200);
    
    window.throttledRenderCalendar = throttledRenderCalendar;
    
    // ===================================
    // PERFORMANCE MONITORING
    // ===================================
    
    /**
     * Monitor and log DSA performance improvements
     */
    window.logDSAPerformance = function() {
        console.group('[DSA] Performance Summary');
        console.log('✓ FilterBuilder: O(n) filtering with chaining');
        console.log('✓ Memoization: Cached calculations prevent redundant work');
        console.log('✓ LRU Cache: ' + trainerCache.size() + '/20 trainer results cached');
        console.log('✓ HashMap: O(1) booking lookups (' + bookingHashMap.size() + ' entries)');
        console.log('✓ Debounce: Reduced API calls by batching rapid requests');
        console.log('✓ Throttle: Limited calendar redraws for smooth UI');
        console.groupEnd();
    };
    
    // ===================================
    // AUTO-ENHANCE EXISTING FUNCTIONS
    // ===================================
    
    // Wrap renderBookings to automatically index bookings
    if (typeof window.renderBookings !== 'undefined') {
        const originalRenderBookings = window.renderBookings;
        window.renderBookings = function(grouped) {
            // Index all bookings for fast lookup
            const allBookings = [
                ...(grouped.today || []),
                ...(grouped.upcoming || []),
                ...(grouped.past || [])
            ];
            window.indexBookings(allBookings);
            
            // Call original function
            return originalRenderBookings.call(this, grouped);
        };
        console.log('[DSA] Enhanced renderBookings with automatic indexing');
    }
    
    // ===================================
    // INITIALIZATION
    // ===================================
    
    console.log('✅ DSA integration loaded successfully!');
    console.log('🚀 Enhanced features: FilterBuilder, Sorting, Caching, Memoization, HashMap indexing');
    
})();
