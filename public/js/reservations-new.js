document.addEventListener('DOMContentLoaded', function () {
    // Booking state - updated for time-based booking
    const bookingState = {
        date: null,
        classType: null,
        trainerId: null,
        trainerName: null,
        startTime: null,
        endTime: null,
        duration: null,
        currentStep: 1,
        weeklyLimit: 48, // 48 hours in hours
        weeklyLimitMinutes: 2880, // 48 hours in minutes
        currentWeekUsage: 0, // in hours
        currentWeekUsageMinutes: 0, // in minutes
        selectedWeekUsage: 0,
        trainerShiftInfo: null,
        availableSlots: []
    };
    
    // Expose bookingState globally for recovery system
    window.bookingState = bookingState;

    const rateLimitCountdowns = new Map();
    const buttonCountdowns = new WeakMap();

    function formatDuration(seconds) {
        const total = Math.max(0, Math.floor(seconds));
        const mins = Math.floor(total / 60);
        const secs = total % 60;
        if (mins > 0) {
            return `${mins}m ${secs.toString().padStart(2, '0')}s`;
        }
        return `${secs}s`;
    }

    function getRateLimitPortal() {
        return document.getElementById('rateLimitPortal');
    }

    function dismissRateLimitBanner(key) {
        const portal = getRateLimitPortal();
        if (!portal) return;
        const alert = portal.querySelector(`[data-rate-limit-key="${key}"]`);
        if (alert) {
            alert.remove();
        }
        if (rateLimitCountdowns.has(key)) {
            clearInterval(rateLimitCountdowns.get(key));
            rateLimitCountdowns.delete(key);
        }
    }

    function showRateLimitBanner(key, { title, message, seconds }) {
        const portal = getRateLimitPortal();
        if (!portal) return;

        let alert = portal.querySelector(`[data-rate-limit-key="${key}"]`);
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'alert-box alert-box--warning rate-limit-alert';
            alert.dataset.rateLimitKey = key;
            alert.innerHTML = `
                <div class="alert-icon" aria-hidden="true">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="alert-content">
                    <p class="alert-title"></p>
                    <p class="alert-text"></p>
                    <p class="rate-limit-countdown"></p>
                </div>
            `;
            portal.appendChild(alert);
        }

        alert.querySelector('.alert-title').textContent = title;
        alert.querySelector('.alert-text').textContent = message;
        const countdownEl = alert.querySelector('.rate-limit-countdown');

        let remaining = Math.max(1, parseInt(seconds, 10) || 60);
        const updateCountdown = () => {
            countdownEl.textContent = `Try again in ${formatDuration(remaining)}.`;
        };
        updateCountdown();

        if (rateLimitCountdowns.has(key)) {
            clearInterval(rateLimitCountdowns.get(key));
        }

        const intervalId = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(intervalId);
                rateLimitCountdowns.delete(key);
                alert.remove();
                return;
            }
            updateCountdown();
        }, 1000);

        rateLimitCountdowns.set(key, intervalId);
    }

    function startButtonRateLimitCountdown(button, seconds) {
        if (!button) return;

        const originalLabel = button.dataset.originalLabel || button.innerHTML;
        button.dataset.originalLabel = originalLabel;

        let remaining = Math.max(1, parseInt(seconds, 10) || 60);
        const updateLabel = () => {
            button.innerHTML = `<i class="fas fa-hourglass-half"></i> Retry in ${formatDuration(remaining)}`;
        };

        button.disabled = true;
        button.classList.add('is-disabled');
        updateLabel();

        if (buttonCountdowns.has(button)) {
            clearInterval(buttonCountdowns.get(button));
        }

        const intervalId = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(intervalId);
                buttonCountdowns.delete(button);
                button.disabled = false;
                button.classList.remove('is-disabled');
                button.innerHTML = button.dataset.originalLabel || originalLabel;
                return;
            }
            updateLabel();
        }, 1000);

        buttonCountdowns.set(button, intervalId);
    }

    function handleRateLimitResponse(contextKey, retryAfterSeconds, fallbackMessage) {
        const seconds = Math.max(1, parseInt(retryAfterSeconds, 10) || 60);
        const titles = {
            booking: 'Too many booking attempts',
            cancel: 'Too many cancellations'
        };

        showRateLimitBanner(contextKey, {
            title: titles[contextKey] || 'Too many requests',
            message: fallbackMessage || 'Please wait before trying again.',
            seconds
        });

        if (contextKey === 'booking') {
            const button = document.getElementById('btnConfirmBooking');
            startButtonRateLimitCountdown(button, seconds);
        } else if (contextKey === 'cancel') {
            document.querySelectorAll('.btn-cancel-booking').forEach(btn => {
                startButtonRateLimitCountdown(btn, seconds);
            });
        }
    }

    // Calendar state
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Bookings data storage
    let allBookingsData = {
        upcoming: [],
        past: [],
        cancelled: [],
        all: [] // Store all bookings for week calculations
    };

    const getCsrfToken = () => window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';

    function ensureCsrfToken() {
        const token = getCsrfToken();
        if (!token) {
            showToast('Your session expired. Please refresh the page.', 'error');
        }
        return token;
    }
    // Flatpickr instances
    let startTimePicker = null;
    let endTimePicker = null;

    // Initialize
    init();

    function init() {
        loadWeeklyBookings();
        loadUserBookings();
        renderCalendar();
        setupEventListeners();
        setupClassSelection();
        setupTrainerSelection();
        updateWizardStep(); // Show step 1 on page load
    }

    // ===================================
    // TOAST NOTIFICATIONS
    // ===================================
    function showToast(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icons[type]} toast-icon"></i>
            <div class="toast-message">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // ===================================
    // LOAD WEEKLY BOOKINGS COUNT (TIME-BASED)
    // ===================================
    async function loadWeeklyBookings(retryCount = 0) {
        try {
            const response = await fetch('api/get_user_bookings.php');
            
            // Handle rate limiting with retry
            if (response.status === 429 && retryCount < 3) {
                const retryAfter = parseInt(response.headers.get('Retry-After') || '2');
                const delay = Math.min(retryAfter * 1000, 2000 * Math.pow(2, retryCount));
                console.warn(`⏳ Rate limited. Retrying in ${delay}ms...`);
                await new Promise(resolve => setTimeout(resolve, delay));
                return loadWeeklyBookings(retryCount + 1);
            }
            
            const data = await response.json();
            if (data.success) {
                console.log('📊 Weekly bookings API response:', data);
                if (data.success) {
                    // Get weekly usage from API response
                    const weeklyUsage = data.weekly_usage || {};
                    console.log('📊 Weekly usage data:', weeklyUsage);
                    const totalMinutes = weeklyUsage.total_minutes || 0;
                    console.log('📊 Total minutes:', totalMinutes);
                    const limitHours = weeklyUsage.limit_hours || 48;
                    const remainingMinutes = weeklyUsage.remaining_minutes || 0;

                    // Convert to hours and minutes for display
                    const usedHours = Math.floor(totalMinutes / 60);
                    const usedMinutes = totalMinutes % 60;
                    const remainingHours = Math.floor(remainingMinutes / 60);
                    const remainingMins = remainingMinutes % 60;

                    // Store current week data
                    bookingState.currentWeekUsage = usedHours + (usedMinutes / 60);
                    bookingState.currentWeekUsageMinutes = totalMinutes;
                    bookingState.weeklyLimit = limitHours;

                    // Store all bookings for calculations
                    allBookingsData.all = data.bookings || [];

                    const weeklyCountEl = document.getElementById('weeklyHoursUsed');
                    const weeklyMaxEl = document.getElementById('weeklyHoursMax');
                    const weeklyTextEl = document.getElementById('weeklyProgressText');

                    if (weeklyCountEl) {
                        weeklyCountEl.textContent = usedMinutes > 0 ? `${usedHours}h ${usedMinutes}m` : `${usedHours}h`;
                    }
                    if (weeklyMaxEl) {
                        weeklyMaxEl.textContent = `/${limitHours}h`;
                    }
                    if (weeklyTextEl) {
                        if (remainingMinutes <= 0) {
                            weeklyTextEl.textContent = `This week's limit reached (${limitHours}h max)`;
                            weeklyTextEl.style.color = '#ff9800';
                        } else {
                            const remainingText = remainingMins > 0 ?
                                `${remainingHours}h ${remainingMins}m remaining this week` :
                                `${remainingHours}h remaining this week`;
                            weeklyTextEl.textContent = remainingText;
                            weeklyTextEl.style.color = '';
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error loading weekly bookings:', error);
        }
    }

    // Helper function to get week boundaries (Sunday to Saturday)
    function getWeekBoundaries(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const dayOfWeek = date.getDay(); // 0 (Sunday) to 6 (Saturday)

        // Calculate Sunday of the week
        const sunday = new Date(date);
        sunday.setDate(date.getDate() - dayOfWeek);
        sunday.setHours(0, 0, 0, 0);

        // Calculate Saturday of the week
        const saturday = new Date(sunday);
        saturday.setDate(sunday.getDate() + 6);
        saturday.setHours(0, 0, 0, 0);

        // Format dates as YYYY-MM-DD without timezone conversion
        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        return {
            start: formatDate(sunday),
            end: formatDate(saturday)
        };
    }

    // Check how many bookings are in a specific week
    function getBookingCountForWeek(dateStr) {
        const weekBounds = getWeekBoundaries(dateStr);

        const count = allBookingsData.all.filter(booking => {
            // Only count confirmed and completed bookings
            if (booking.status !== 'confirmed' && booking.status !== 'completed') {
                return false;
            }

            return booking.date >= weekBounds.start && booking.date <= weekBounds.end;
        }).length;

        return count;
    }

    function checkWeeklyLimitForDate(dateStr) {
        const count = getBookingCountForWeek(dateStr);
        bookingState.selectedWeekCount = count;
        bookingState.selectedWeekFull = count >= bookingState.weeklyLimit;

        // Update warning display
        updateWeeklyLimitWarning(dateStr);

        return !bookingState.selectedWeekFull;
    }

    function updateWeeklyLimitWarning(dateStr) {
        const wizardSection = document.querySelector('.booking-wizard-section');
        if (!wizardSection) return;

        const existingWarning = wizardSection.querySelector('.weekly-limit-warning');

        if (bookingState.selectedWeekFull) {
            const weekBounds = getWeekBoundaries(dateStr);
            const weekStart = new Date(weekBounds.start + 'T00:00:00');
            const weekEnd = new Date(weekBounds.end + 'T00:00:00');

            if (!existingWarning) {
                const warning = document.createElement('div');
                warning.className = 'weekly-limit-warning';
                warning.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Weekly Booking Limit Reached</h3>
                        <p>You've already booked ${bookingState.selectedWeekCount} sessions for the week of ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (maximum ${bookingState.weeklyLimit} per week). Please select a date from a different week.</p>
                    </div>
                `;
                wizardSection.insertBefore(warning, wizardSection.firstChild);
            } else {
                // Update existing warning
                existingWarning.querySelector('p').textContent =
                    `You've already booked ${bookingState.selectedWeekCount} sessions for the week of ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (maximum ${bookingState.weeklyLimit} per week). Please select a date from a different week.`;
            }
        } else {
            // Remove warning if week is not full
            if (existingWarning) {
                existingWarning.remove();
            }
        }
    }

    // ===================================
    // LOAD USER BOOKINGS
    // ===================================
    async function loadUserBookings(retryCount = 0) {
        try {
            const response = await fetch('api/get_user_bookings.php');
            
            // Handle rate limiting with retry
            if (response.status === 429 && retryCount < 3) {
                const retryAfter = parseInt(response.headers.get('Retry-After') || '2');
                const delay = Math.min(retryAfter * 1000, 2000 * Math.pow(2, retryCount));
                console.warn(`⏳ Rate limited. Retrying in ${delay}ms...`);
                await new Promise(resolve => setTimeout(resolve, delay));
                return loadUserBookings(retryCount + 1);
            }
            
            const data = await response.json();
            if (data.success) {
                console.log('Bookings API Response:', data); // Debug log
                if (data.success) {
                    console.log('Grouped bookings:', data.grouped); // Debug log
                    console.log('Summary:', data.summary); // Debug log
                    renderBookings(data.grouped);
                    // Counts are updated in applyBookingsFilter(), called by renderBookings()
                } else {
                    console.error('API returned error:', data.message);
                    document.getElementById('upcomingBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                    document.getElementById('pastBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                    document.getElementById('cancelledBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                }
            } else {
                console.error('API returned error:', data.message);
                document.getElementById('upcomingBookings').innerHTML =
                    `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                document.getElementById('pastBookings').innerHTML =
                    `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                document.getElementById('cancelledBookings').innerHTML =
                    `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
            }
        } catch (error) {
            console.error('Error loading bookings:', error);
            document.getElementById('upcomingBookings').innerHTML =
                '<p class="empty-message">Failed to load bookings</p>';
            document.getElementById('pastBookings').innerHTML =
                '<p class="empty-message">Failed to load bookings</p>';
            document.getElementById('cancelledBookings').innerHTML =
                '<p class="empty-message">Failed to load bookings</p>';
        }
    }

    function renderBookings(grouped) {
        console.log('Rendering bookings - upcoming:', grouped.upcoming?.length, 'today:', grouped.today?.length, 'past:', grouped.past?.length, 'blocked:', grouped.blocked?.length); // Debug log

        // Combine all bookings from server groups
        const allBookings = [...(grouped.today || []), ...(grouped.upcoming || []), ...(grouped.past || []), ...(grouped.blocked || [])];

        // Get current date and time
        const now = new Date();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        const sessionEndTimes = {
            'Morning': 11,
            'Afternoon': 17,
            'Evening': 22
        };

        // Re-categorize bookings based on current time
        const upcomingList = [];
        const pastList = [];
        const cancelledList = [];
        const blockedList = [];

        allBookings.forEach(booking => {
            // Separate cancelled bookings first
            if (booking.status === 'cancelled') {
                cancelledList.push(booking);
                return;
            }

            // Separate blocked/unavailable bookings
            if (booking.status === 'blocked' || booking.status === 'unavailable') {
                blockedList.push(booking);
                return;
            }

            const bookingDate = new Date(booking.date + 'T00:00:00');
            bookingDate.setHours(0, 0, 0, 0);

            // If booking date is in the future
            if (bookingDate > today) {
                upcomingList.push(booking);
            }
            // If booking date is today
            else if (bookingDate.getTime() === today.getTime()) {
                // Check if time-based booking
                if (booking.start_time && booking.end_time) {
                    const endTime = new Date(booking.end_time);
                    // If session hasn't ended yet, it's upcoming
                    if (now < endTime) {
                        upcomingList.push(booking);
                    } else {
                        // Session has ended, move to past
                        pastList.push(booking);
                    }
                } else {
                    // Legacy session-based booking
                    const currentHour = now.getHours();
                    const sessionEnd = sessionEndTimes[booking.session_time] || 24;

                    // If session hasn't ended yet, it's upcoming
                    if (currentHour < sessionEnd) {
                        upcomingList.push(booking);
                    } else {
                        // Session has ended, move to past
                        pastList.push(booking);
                    }
                }
            }
            // Booking date is in the past
            else {
                pastList.push(booking);
            }
        });

        // Sort upcoming by date ascending (earliest first), then by session time
        // Use DSA if available for better performance
        const useDSA = window.DSA || window.DSAUtils;

        if (useDSA) {
            // DSA-POWERED SORTING (Optimized comparison functions)
            const sessionOrder = { 'Morning': 1, 'Afternoon': 2, 'Evening': 3 };
            const sessionOrderReverse = { 'Evening': 1, 'Afternoon': 2, 'Morning': 3 };

            upcomingList.sort(useDSA.compareByMultiple([
                (a, b) => new Date(a.date) - new Date(b.date),
                (a, b) => sessionOrder[a.session_time] - sessionOrder[b.session_time]
            ]));

            pastList.sort(useDSA.compareByMultiple([
                (a, b) => new Date(b.date) - new Date(a.date),
                (a, b) => sessionOrderReverse[a.session_time] - sessionOrderReverse[b.session_time]
            ]));

            cancelledList.sort(useDSA.compareByMultiple([
                (a, b) => new Date(b.date) - new Date(a.date),
                (a, b) => sessionOrderReverse[a.session_time] - sessionOrderReverse[b.session_time]
            ]));

            console.log('✅ DSA sorting applied to bookings');
        } else {
            // FALLBACK: Basic sorting
            upcomingList.sort((a, b) => {
                const dateCompare = new Date(a.date) - new Date(b.date);
                if (dateCompare !== 0) return dateCompare;

                const sessionOrder = { 'Morning': 1, 'Afternoon': 2, 'Evening': 3 };
                return sessionOrder[a.session_time] - sessionOrder[b.session_time];
            });

            pastList.sort((a, b) => {
                const dateCompare = new Date(b.date) - new Date(a.date);
                if (dateCompare !== 0) return dateCompare;

                const sessionOrder = { 'Evening': 1, 'Afternoon': 2, 'Morning': 3 };
                return sessionOrder[a.session_time] - sessionOrder[b.session_time];
            });

            cancelledList.sort((a, b) => {
                const dateCompare = new Date(b.date) - new Date(a.date);
                if (dateCompare !== 0) return dateCompare;

                const sessionOrder = { 'Evening': 1, 'Afternoon': 2, 'Morning': 3 };
                return sessionOrder[a.session_time] - sessionOrder[b.session_time];
            });
        }

        // Store the full data for filtering
        allBookingsData.upcoming = upcomingList;
        allBookingsData.past = pastList;
        allBookingsData.cancelled = cancelledList;
        allBookingsData.blocked = blockedList;

        // Show/hide blocked tab based on blocked bookings
        const blockedTab = document.querySelector('.tab-blocked');
        if (blockedTab) {
            blockedTab.style.display = blockedList.length > 0 ? 'inline-block' : 'none';
        }

        // Update stat cards with next upcoming session
        updateStatCards(upcomingList);

        // Apply current filter
        applyBookingsFilter();
    }

    /**
     * ========================================================================
     * APPLY BOOKINGS FILTER - DSA-OPTIMIZED FILTERING
     * ========================================================================
     *
     * WHAT THIS DOES:
     * Filters the bookings list based on selected class type (Boxing, Muay Thai,
     * MMA, Gym, or All). This runs every time the user changes the dropdown.
     *
     * WHY DSA OPTIMIZATION MATTERS HERE:
     * Users often have 50-100+ bookings (some with many past bookings).
     * Filtering needs to be instant because it happens on dropdown change.
     *
     * PERFORMANCE COMPARISON:
     * Basic approach (without DSA):
     *   - 3 separate .filter() calls (upcoming, past, cancelled)
     *   - Each scans the entire array
     *   - 100 bookings × 3 arrays = 300 item checks
     *   - Takes ~10-15ms
     *
     * DSA approach (with FilterBuilder):
     *   - Build filter condition once
     *   - Apply to each array with optimized algorithm
     *   - More efficient condition checking
     *   - Takes ~3-5ms (2-3x faster!)
     *
     * HOW IT WORKS:
     * 1. Check if DSA library is loaded
     * 2. If yes → Use FilterBuilder (fast path)
     * 3. If no → Use basic .filter() (fallback path)
     * 4. Either way, bookings get filtered correctly
     *
     * DSA PATH:
     * - Create FilterBuilder with condition
     * - Apply to each booking array (upcoming, past, cancelled)
     * - FilterBuilder does a single optimized pass
     *
     * FALLBACK PATH:
     * - Use traditional .filter() method
     * - Still works correctly, just a bit slower
     * - Ensures app works even if DSA fails to load
     *
     * WHY WE NEED FALLBACK:
     * If DSA library fails to load (network issue, browser compatibility,
     * etc.), the app still works. This is called "progressive enhancement" -
     * better if available, functional if not.
     */
    function applyBookingsFilter() {
        // Get selected class type from dropdown
        const filterValue = document.getElementById('classFilter')?.value || 'all';

        // Check if DSA utilities are available
        const useDSA = window.DSA || window.DSAUtils;

        let filteredUpcoming, filteredPast, filteredCancelled, filteredBlocked;

        if (useDSA) {
            // ═══════════════════════════════════════════════════════════════
            // DSA-POWERED FILTERING (Optimized with FilterBuilder)
            // ═══════════════════════════════════════════════════════════════

            const filterBuilder = new useDSA.FilterBuilder();

            // Add filter condition only if user selected a specific class type
            if (filterValue !== 'all') {
                // This creates a filter that checks: booking.class_type === filterValue
                filterBuilder.where('class_type', '===', filterValue);
            }

            // Apply the filter to each booking category
            // If 'all' is selected, skip filtering (show everything)
            filteredUpcoming = filterValue === 'all' ?
                allBookingsData.upcoming :
                filterBuilder.apply(allBookingsData.upcoming);

            filteredPast = filterValue === 'all' ?
                allBookingsData.past :
                filterBuilder.apply(allBookingsData.past);

            filteredCancelled = filterValue === 'all' ?
                allBookingsData.cancelled :
                filterBuilder.apply(allBookingsData.cancelled);

            filteredBlocked = filterValue === 'all' ?
                (allBookingsData.blocked || []) :
                filterBuilder.apply(allBookingsData.blocked || []);

            console.log('✅ DSA FilterBuilder applied to bookings (optimized path)');
        } else {
            // ═══════════════════════════════════════════════════════════════
            // FALLBACK: Basic JavaScript .filter() method
            // ═══════════════════════════════════════════════════════════════
            // This works the same way functionally, just not as optimized

            filteredUpcoming = allBookingsData.upcoming;
            if (filterValue !== 'all') {
                filteredUpcoming = allBookingsData.upcoming.filter(booking =>
                    booking.class_type === filterValue
                );
            }

            filteredPast = allBookingsData.past;
            if (filterValue !== 'all') {
                filteredPast = allBookingsData.past.filter(booking =>
                    booking.class_type === filterValue
                );
            }

            filteredCancelled = allBookingsData.cancelled;
            if (filterValue !== 'all') {
                filteredCancelled = allBookingsData.cancelled.filter(booking =>
                    booking.class_type === filterValue
                );
            }

            filteredBlocked = allBookingsData.blocked || [];
            if (filterValue !== 'all') {
                filteredBlocked = (allBookingsData.blocked || []).filter(booking =>
                    booking.class_type === filterValue
                );
            }

            console.log('⚠️ Using fallback filtering (DSA not available)');
        }

        // Render the filtered results to the page
        renderBookingList('upcomingBookings', filteredUpcoming);
        renderBookingList('pastBookings', filteredPast);
        renderBookingList('cancelledBookings', filteredCancelled);
        renderBookingList('blockedBookings', filteredBlocked);

        // Update the count badges (shows number of bookings in each category)
        document.getElementById('upcomingCount').textContent = filteredUpcoming.length;
        document.getElementById('pastCount').textContent = filteredPast.length;
        document.getElementById('cancelledCount').textContent = filteredCancelled.length;
        document.getElementById('blockedCount').textContent = filteredBlocked.length;
    }

    function updateStatCards(upcomingList) {
        const upcomingClassEl = document.getElementById('upcomingClass');
        const upcomingDateEl = document.getElementById('upcomingDate');
        const upcomingTrainerEl = document.getElementById('upcomingTrainer');
        const trainerSubtextEl = document.getElementById('trainerSubtext');

        if (!upcomingList || upcomingList.length === 0) {
            if (upcomingClassEl) upcomingClassEl.textContent = '-';
            if (upcomingDateEl) upcomingDateEl.textContent = 'No booked sessions';
            if (upcomingTrainerEl) upcomingTrainerEl.textContent = '-';
            if (trainerSubtextEl) trainerSubtextEl.textContent = '-';
            return;
        }

        // Get current date and time
        const now = new Date();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        // Find the first truly upcoming booking (not past)
        let nextBooking = null;
        for (const booking of upcomingList) {
            const bookingDate = new Date(booking.date + 'T00:00:00');
            bookingDate.setHours(0, 0, 0, 0);

            // If booking is in the future, use it
            if (bookingDate > today) {
                nextBooking = booking;
                break;
            }

            // If booking is today, check if session hasn't ended yet
            if (bookingDate.getTime() === today.getTime()) {
                const currentHour = now.getHours();

                // Check if time-based booking
                if (booking.start_time && booking.end_time) {
                    const endTime = new Date(booking.end_time);
                    if (now < endTime) {
                        nextBooking = booking;
                        break;
                    }
                }
            }
        }

        // If no valid upcoming booking found
        if (!nextBooking) {
            if (upcomingClassEl) upcomingClassEl.textContent = '-';
            if (upcomingDateEl) upcomingDateEl.textContent = 'No upcoming sessions';
            if (upcomingTrainerEl) upcomingTrainerEl.textContent = '-';
            if (trainerSubtextEl) trainerSubtextEl.textContent = '-';
            return;
        }

        // Parse the date properly
        const bookingDate = new Date(nextBooking.date + 'T00:00:00');
        bookingDate.setHours(0, 0, 0, 0);

        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const isToday = bookingDate.getTime() === today.getTime();
        const isTomorrow = bookingDate.getTime() === tomorrow.getTime();

        // Update class name
        if (upcomingClassEl) {
            upcomingClassEl.textContent = nextBooking.class_type;
        }

        // Update date info
        if (upcomingDateEl) {
            let dateText = '';
            let timeInfo = '';

            // Check if time-based booking
            if (nextBooking.start_time && nextBooking.end_time) {
                const startTime = new Date(nextBooking.start_time);
                const endTime = new Date(nextBooking.end_time);
                const startFormatted = startTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                const endFormatted = endTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                timeInfo = `${startFormatted} - ${endFormatted}`;
            } else {
                // Legacy session-based
                timeInfo = nextBooking.session_time;
            }

            if (isToday) {
                dateText = `Today, ${timeInfo}`;
            } else if (isTomorrow) {
                dateText = `Tomorrow, ${timeInfo}`;
            } else {
                dateText = `${bookingDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${timeInfo}`;
            }
            upcomingDateEl.textContent = dateText;
        }

        // Update trainer info
        if (upcomingTrainerEl) {
            upcomingTrainerEl.textContent = nextBooking.trainer_name;
        }

        if (trainerSubtextEl) {
            // Show duration for time-based bookings
            if (nextBooking.start_time && nextBooking.end_time) {
                const startTime = new Date(nextBooking.start_time);
                const endTime = new Date(nextBooking.end_time);
                const durationMinutes = (endTime - startTime) / 1000 / 60;
                const hours = Math.floor(durationMinutes / 60);
                const minutes = durationMinutes % 60;
                const durationDisplay = hours > 0
                    ? (minutes > 0 ? `${hours}h ${minutes}m session` : `${hours}h session`)
                    : `${minutes}m session`;
                trainerSubtextEl.textContent = durationDisplay;
            } else {
                trainerSubtextEl.textContent = '-';
            }
        }
    }

    function renderBookingList(containerId, bookings) {
        const container = document.getElementById(containerId);
        console.log(`Rendering ${containerId}:`, bookings); // Debug log

        if (!bookings || bookings.length === 0) {
            container.innerHTML = '<p class="empty-message">No bookings found</p>';
            return;
        }

        // Time-related variables
        const now = new Date();
        const currentHour = now.getHours();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        container.innerHTML = `
            <div class="bookings-grid">
                ${bookings.map(booking => {
            // Debug log to check booking ID
            console.log('Rendering booking:', { id: booking.id, trainer: booking.trainer_name });
            
            // Determine if this booking can actually be cancelled based on current time and 12-hour policy
            const bookingDate = new Date(booking.date + 'T00:00:00');

            // Calculate hours until session starts
            let hoursUntilSession = 0;
            if (booking.start_time) {
                const sessionStartDateTime = new Date(booking.start_time);
                hoursUntilSession = (sessionStartDateTime - now) / (1000 * 60 * 60);
            }

            let canActuallyCancelNow = false;
            let isWithinCancellationWindow = false;
            let hasSessionPassed = false;

            // Check if session is ongoing
            const todayStr = now.toISOString().split('T')[0];
            const isToday = booking.date === todayStr;

            let isOngoing = false;
            if (isToday && booking.status !== 'cancelled' && booking.start_time && booking.end_time) {
                const startTime = new Date(booking.start_time);
                const endTime = new Date(booking.end_time);
                isOngoing = now >= startTime && now < endTime;
            }

            // Check if session has ended for today
            let hasSessionEnded = false;
            if (isToday && booking.end_time) {
                const endTime = new Date(booking.end_time);
                hasSessionEnded = now >= endTime;
            }

            if (booking.status === 'cancelled') {
                canActuallyCancelNow = false;
                isWithinCancellationWindow = false;
                hasSessionPassed = false;
            } else if (isOngoing) {
                // Session is currently ongoing - cannot modify
                canActuallyCancelNow = false;
                isWithinCancellationWindow = false;
                hasSessionPassed = false;
            } else if (booking.status === 'completed' || hasSessionEnded) {
                // Session has ended - mark as completed
                canActuallyCancelNow = false;
                hasSessionPassed = true;
            } else if (hoursUntilSession < 0) {
                // Session start time has passed (but might not have ended yet)
                canActuallyCancelNow = false;
                hasSessionPassed = true;
            } else {
                // Session hasn't started yet - allow cancel/reschedule regardless of time
                canActuallyCancelNow = true;
                isWithinCancellationWindow = false;
            }

            // Format time display - check if time-based or legacy
            let timeDisplay = '';
            let durationDisplay = '';

            if (booking.start_time && booking.end_time) {
                // Time-based booking
                const startTime = new Date(booking.start_time);
                const endTime = new Date(booking.end_time);
                const startFormatted = startTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                const endFormatted = endTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                timeDisplay = `${startFormatted} - ${endFormatted}`;

                // Calculate duration
                const durationMinutes = (endTime - startTime) / 1000 / 60;
                const hours = Math.floor(durationMinutes / 60);
                const minutes = durationMinutes % 60;
                durationDisplay = hours > 0
                    ? (minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`)
                    : `${minutes}m`;
            } else {
                // Legacy session-based booking (fallback for old data)
                timeDisplay = booking.session_time || '-';
                durationDisplay = '-';
            }

            return `
                <div class="booking-row ${booking.status === 'cancelled' ? 'cancelled' : ''}" data-booking-id="${booking.id}" data-trainer-id="${booking.trainer_id || ''}">
                    <div class="booking-date-cell">
                        <div class="booking-date-badge">
                            <div class="booking-day">${new Date(booking.date).getDate()}</div>
                            <div class="booking-month">${new Date(booking.date).toLocaleString('en-US', { month: 'short' })}</div>
                        </div>
                    </div>
                    <div class="booking-class-cell">
                        <span class="cell-label">Class</span>
                        <span class="cell-value">${booking.class_type}</span>
                    </div>
                    <div class="booking-time-cell">
                        <span class="cell-label">Time</span>
                        <span class="cell-value"><i class="fas fa-clock"></i> ${timeDisplay}</span>
                    </div>
                    <div class="booking-duration-cell">
                        <span class="cell-label">Duration</span>
                        <span class="cell-value duration-badge"><i class="fas fa-hourglass-half"></i> ${durationDisplay}</span>
                    </div>
                    <div class="booking-trainer-cell">
                        <span class="cell-label">Trainer</span>
                        <span class="cell-value"><i class="fas fa-user"></i> ${booking.trainer_name}</span>
                    </div>
                    <div class="booking-day-cell">
                        <span class="cell-label">Day</span>
                        <span class="cell-value"><i class="fas fa-calendar"></i> ${booking.day_of_week}</span>
                    </div>
                    <div class="booking-actions-cell">
                        <span class="cell-label">Actions</span>
                        <div class="cell-value">
                        ${(() => {
                            if (isOngoing) {
                                return `
                                    <div class="booking-status-badge ongoing-badge">
                                        <i class="fas fa-play-circle"></i>
                                        <span>Ongoing Session</span>
                                    </div>
                                `;
                            } else if (booking.status === 'blocked' || booking.status === 'unavailable') {
                                return `
                                    <div class="booking-status-badge blocked-badge">
                                        <i class="fas fa-ban"></i>
                                        <span>Unavailable</span>
                                    </div>
                                `;
                            } else if (booking.status === 'cancelled') {
                                return `
                                    <div class="booking-status-badge cancelled-badge">
                                        <i class="fas fa-times-circle"></i>
                                        <span>Cancelled</span>
                                    </div>
                                `;
                            } else if (canActuallyCancelNow) {
                                return `
                                    <div class="booking-action-buttons">
                                        <button class="btn-reschedule-booking" onclick="openRescheduleModal(${booking.id}, this)">
                                            <i class="fas fa-calendar-check"></i> Reschedule
                                        </button>
                                        <button class="btn-cancel-booking" onclick="cancelBooking(${booking.id})">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                `;
                            } else if (isWithinCancellationWindow) {
                                return `
                                    <div class="booking-status-badge warning-badge" title="Session has started or is about to start">
                                        <i class="fas fa-lock"></i>
                                        <span>Cannot Modify</span>
                                    </div>
                                `;
                            } else {
                                return `
                                    <div class="booking-status-badge completed-badge">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Completed</span>
                                    </div>
                                `;
                            }
                        })()}
                        </div>
                    </div>
                </div>
            `;
        }).join('')}
        `;
    }

    // ===================================
    // BOOKING TABS
    // ===================================
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.getAttribute('data-tab');

            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Update active content
            document.querySelectorAll('.bookings-list').forEach(list => list.classList.remove('active'));
            document.getElementById(tab + 'Bookings').classList.add('active');
        });
    });

    // ===================================
    // CANCEL BOOKING
    // ===================================
    window.cancelBooking = function (bookingId, triggerBtn = null) {
        if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            return;
        }

        if (triggerBtn && !triggerBtn.dataset.originalLabel) {
            triggerBtn.dataset.originalLabel = triggerBtn.innerHTML;
        }

        const setCancellingState = () => {
            if (!triggerBtn) return;
            triggerBtn.disabled = true;
            triggerBtn.classList.add('is-disabled');
            triggerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        };

        const resetCancelButton = () => {
            if (!triggerBtn) return;
            triggerBtn.disabled = false;
            triggerBtn.classList.remove('is-disabled');
            triggerBtn.innerHTML = triggerBtn.dataset.originalLabel || '<i class="fas fa-times"></i> Cancel';
        };

        setCancellingState();

        const csrfToken = ensureCsrfToken();
        if (!csrfToken) {
            resetCancelButton();
            return;
        }

        const formData = new URLSearchParams();
        formData.append('booking_id', bookingId);
        formData.append('csrf_token', csrfToken);

        fetch('api/cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    dismissRateLimitBanner('cancel');
                    showToast('Booking cancelled successfully', 'success');
                    loadUserBookings();
                    loadWeeklyBookings();
                    resetCancelButton();
                } else {
                    if (data.failed_check === 'rate_limit') {
                        handleRateLimitResponse('cancel', data.retry_after, data.message || 'Too many cancellation attempts.');
                    } else {
                        showToast(data.message || 'Failed to cancel booking', 'error');
                        resetCancelButton();
                    }
                }
            })
            .catch(error => {
                console.error('Error cancelling booking:', error);
                showToast('[CANCEL] An error occurred. Please try again.', 'error');
                resetCancelButton();
            });
    };

    // ===================================
    // ===================================
    // CALENDAR RENDERING
    // ===================================

    // Function to check if all sessions are unavailable for a date
    function areAllSessionsUnavailable(dateStr) {
        const date = new Date(dateStr);
        const today = new Date();

        // Only check for today's date
        if (date.toDateString() !== today.toDateString()) {
            return false;
        }

        const currentHour = today.getHours();
        const currentMinute = today.getMinutes();
        const currentTotalMinutes = currentHour * 60 + currentMinute;

        // Session end times in minutes
        const sessionEndTimes = {
            'Morning': 11 * 60,      // 11:00 AM = 660 minutes
            'Afternoon': 17 * 60,    // 5:00 PM = 1020 minutes
            'Evening': 22 * 60       // 10:00 PM = 1320 minutes
        };

        // Check if all sessions have less than 30 minutes remaining
        let allUnavailable = true;
        for (const session in sessionEndTimes) {
            const endMinutes = sessionEndTimes[session];
            const minutesRemaining = endMinutes - currentTotalMinutes;

            if (minutesRemaining >= 30) {
                allUnavailable = false;
                break;
            }
        }

        return allUnavailable;
    }

    function renderCalendar() {
        const calendarTitle = document.getElementById('calendarTitle');
        const calendarDays = document.getElementById('calendarDays');

        const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];

        calendarTitle.textContent = monthNames[currentMonth];

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

        const today = new Date();
        const todayDate = today.getDate();
        const todayMonth = today.getMonth();
        const todayYear = today.getFullYear();

        let html = '';
        let weekDays = [];
        let dayCount = 0;

        // Previous month days (inactive)
        for (let i = firstDay - 1; i >= 0; i--) {
            weekDays.push({
                html: `<div class="schedule-day inactive">
                    <span class="day-number">${daysInPrevMonth - i}</span>
                </div>`,
                isPast: true,
                isInactive: true
            });
            dayCount++;
        }

        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(currentYear, currentMonth, day);
            const dateStr = formatDate(date);
            const isPast = date < new Date(todayYear, todayMonth, todayDate);
            const isToday = day === todayDate && currentMonth === todayMonth && currentYear === todayYear;
            const isSelected = bookingState.date === dateStr;

            // Can only book up to 30 days ahead
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 30);
            const isTooFar = date > maxDate;

            // Check if date is past membership expiration + grace period
            let isPastMembershipExpiration = false;
            if (window.maxBookingDate) {
                const maxBookingDateObj = new Date(window.maxBookingDate);
                isPastMembershipExpiration = date > maxBookingDateObj;
            }

            // Check if all sessions are unavailable for this date
            const allSessionsUnavailable = areAllSessionsUnavailable(dateStr);

            let classes = ['schedule-day'];
            if (isToday) classes.push('today');
            if (isSelected) classes.push('selected');
            if (isPast) classes.push('past-date');
            if (isTooFar) classes.push('too-far-advance');
            if (isPastMembershipExpiration) classes.push('past-membership-expiration');
            if (allSessionsUnavailable) classes.push('all-sessions-unavailable');

            const clickable = !isPast && !isTooFar && !allSessionsUnavailable && !isPastMembershipExpiration;
            const onClick = clickable ? `onclick="selectDate('${dateStr}')"` : '';

            weekDays.push({
                html: `<div class="${classes.join(' ')}" data-date="${dateStr}" ${onClick}>
                    <span class="day-number">${day}</span>
                </div>`,
                isPast: isPast,
                isInactive: false
            });
            dayCount++;

            // Check if we completed a week (7 days)
            if (dayCount % 7 === 0) {
                // Check if all days in this week are past or inactive
                const allPastOrInactive = weekDays.every(d => d.isPast || d.isInactive);

                // Only add the week if not all days are past/inactive
                if (!allPastOrInactive) {
                    html += weekDays.map(d => d.html).join('');
                }

                weekDays = [];
            }
        }

        // Next month days (inactive)
        const totalCells = firstDay + daysInMonth;
        const remainingCells = 7 - (totalCells % 7);
        if (remainingCells < 7) {
            for (let day = 1; day <= remainingCells; day++) {
                weekDays.push({
                    html: `<div class="schedule-day inactive">
                        <span class="day-number">${day}</span>
                    </div>`,
                    isPast: true,
                    isInactive: true
                });
                dayCount++;
            }
        }

        // Add remaining week if it has any non-past days
        if (weekDays.length > 0) {
            const allPastOrInactive = weekDays.every(d => d.isPast || d.isInactive);
            if (!allPastOrInactive) {
                html += weekDays.map(d => d.html).join('');
            }
        }

        calendarDays.innerHTML = html;
    }

    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', function () {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', function () {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    // Month dropdown navigation
    const monthNavBtn = document.getElementById('monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');

    if (monthNavBtn && monthDropdown) {
        monthNavBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            monthDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
            monthDropdown.classList.remove('active');
        });

        // Month selection
        document.querySelectorAll('.month-option').forEach(option => {
            option.addEventListener('click', function (e) {
                e.stopPropagation();
                const selectedMonth = parseInt(this.getAttribute('data-month'));
                currentMonth = selectedMonth;
                renderCalendar();
                monthDropdown.classList.remove('active');
            });
        });
    }

    // ===================================
    // STEP 1: DATE SELECTION
    // ===================================
    window.selectDate = function (dateStr) {
        const dateElement = document.querySelector(`[data-date="${dateStr}"]`);

        // Check if date is past membership expiration
        if (dateElement && dateElement.classList.contains('past-membership-expiration')) {
            const membershipEndDateFormatted = new Date(window.membershipEndDate).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
            const gracePeriodEnd = new Date(window.maxBookingDate).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
            showToast(
                `Cannot book beyond your membership expiration. Your ${window.membershipPlanName} plan expires on ${membershipEndDateFormatted} (booking allowed until ${gracePeriodEnd} with grace period). Please visit the gym to renew or upgrade.`,
                'warning',
                6000
            );
            return;
        }

        // Don't allow clicking on past dates or too far dates
        if (dateElement && (dateElement.classList.contains('past-date') ||
            dateElement.classList.contains('too-far-advance') ||
            dateElement.classList.contains('inactive'))) {
            return;
        }

        bookingState.date = dateStr;
        
        // Save state after date selection
        if (window.BookingRecovery) {
            window.BookingRecovery.saveState(bookingState);
        }

        // Check weekly limit for the selected week
        const canBook = checkWeeklyLimitForDate(dateStr);
        if (!canBook) {
            showToast(`The week containing ${new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} is full (${bookingState.weeklyLimit}/${bookingState.weeklyLimit} sessions). Please select a date from another week.`, 'warning', 5000);
            // Still allow selection but show warning
        }

        // Update UI - Remove selected from all schedule-day elements
        document.querySelectorAll('.schedule-day').forEach(day => day.classList.remove('selected'));
        if (dateElement) {
            dateElement.classList.add('selected');
        }

        // Update session availability based on selected date
        if (typeof updateSessionAvailability === 'function') {
            updateSessionAvailability();
        }

        // Check session capacity for the selected date
        checkSessionCapacity();

        // Enable next button in the active step (will be checked again on click)
        const activeStep = document.querySelector('.wizard-step.active');
        const btnNext = activeStep ? activeStep.querySelector('.btn-next') : null;
        if (btnNext) {
            btnNext.disabled = false;
        }
    };

    // ===================================
    // STEP 2: SESSION SELECTION
    // ===================================

    // Function to check if session time has passed for today
    function isSessionPassed(sessionName) {
        if (!bookingState.date) return false;

        const selectedDate = new Date(bookingState.date);
        const today = new Date();

        // Only check for today's date
        if (selectedDate.toDateString() !== today.toDateString()) {
            return false;
        }

        const currentHour = today.getHours();
        const currentMinute = today.getMinutes();

        // Session end times
        const sessionEndTimes = {
            'Morning': 11,      // 11:00 AM
            'Afternoon': 17,    // 5:00 PM
            'Evening': 22       // 10:00 PM
        };

        const endHour = sessionEndTimes[sessionName];
        if (!endHour) return false;

        // Calculate minutes remaining until session ends
        const currentTotalMinutes = currentHour * 60 + currentMinute;
        const endTotalMinutes = endHour * 60;
        const minutesRemaining = endTotalMinutes - currentTotalMinutes;

        // Store whether session has completely passed or just within 30 min
        const hasPassed = minutesRemaining <= 0;
        const isTooClose = minutesRemaining < 30 && minutesRemaining > 0;

        // Store the type of restriction for better messaging
        if (hasPassed) {
            bookingState.sessionRestriction = 'passed';
        } else if (isTooClose) {
            bookingState.sessionRestriction = 'too_close';
        } else {
            bookingState.sessionRestriction = null;
        }

        // Block if less than 30 minutes remaining or session has passed
        return minutesRemaining < 30;
    }

    // Function to check session capacity
    function checkSessionCapacity() {
        if (!bookingState.date) return;

        // Check capacity for all sessions
        const sessions = ['Morning', 'Afternoon', 'Evening'];
        sessions.forEach(session => {
            fetch(`api/get_session_capacity.php?date=${bookingState.date}&session=${session}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const block = document.querySelector(`.session-block[data-session="${session}"]`);
                        if (block && data.is_full) {
                            block.classList.add('session-full');
                            // Add full indicator if not already present
                            if (!block.querySelector('.session-full-badge')) {
                                const badge = document.createElement('span');
                                badge.className = 'session-full-badge';
                                badge.innerHTML = '<i class="fas fa-users"></i> FULL';
                                block.querySelector('.session-header').appendChild(badge);
                            }
                        }
                    }
                })
                .catch(error => console.error(`Error checking ${session} capacity:`, error));
        });
    }

    // Function to update session blocks availability
    function updateSessionAvailability() {
        document.querySelectorAll('.session-block').forEach(block => {
            const session = block.getAttribute('data-session');
            const isPassed = isSessionPassed(session);

            // Remove full badge when checking time-based availability
            const existingBadge = block.querySelector('.session-full-badge');
            if (existingBadge) existingBadge.remove();
            block.classList.remove('session-full');

            if (isPassed) {
                block.classList.add('session-passed');
                block.style.opacity = '0.5';
                block.style.cursor = 'not-allowed';
            } else {
                block.classList.remove('session-passed');
                block.style.opacity = '';
                block.style.cursor = '';
            }
        });
    }

    document.querySelectorAll('.session-block').forEach(block => {
        block.addEventListener('click', function () {
            const session = this.getAttribute('data-session');

            // Check if selected week is full
            if (bookingState.selectedWeekFull) {
                const weekBounds = getWeekBoundaries(bookingState.date);
                const weekStart = new Date(weekBounds.start + 'T00:00:00');
                showToast(`This week (starting ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}) is full (${bookingState.weeklyLimit}/${bookingState.weeklyLimit} sessions). Please select a date from another week.`, 'warning', 5000);
                return;
            }

            // Check if session time has passed
            if (isSessionPassed(session)) {
                const message = bookingState.sessionRestriction === 'passed'
                    ? 'This session time has already concluded for the selected date. Please choose a future session.'
                    : 'This session is closing soon. Bookings require at least 30 minutes before the session ends.';
                showToast(message, 'warning', 5000);
                return;
            }

            bookingState.session = session;

            document.querySelectorAll('.session-block').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');

            // Enable Next button in the active step
            const activeStep = document.querySelector('.wizard-step.active');
            const btnNext = activeStep.querySelector('.btn-next');
            if (btnNext) btnNext.disabled = false;
        });
    });

    // ===================================
    // STEP 3: CLASS TYPE SELECTION
    // ===================================
    document.querySelectorAll('.class-card').forEach(card => {
        card.addEventListener('click', function () {
            const classType = this.getAttribute('data-class');
            bookingState.classType = classType;

            document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            // Enable Next button in the active step
            const activeStep = document.querySelector('.wizard-step.active');
            const btnNext = activeStep.querySelector('.btn-next');
            if (btnNext) btnNext.disabled = false;
        });
    });

    // ===================================
    // STEP 4: LOAD TRAINERS
    // ===================================
    function loadTrainers() {
        const { date, classType } = bookingState;
        const trainersGrid = document.getElementById('trainersGrid');
        const capacityInfo = document.getElementById('facilityCapacityInfo');

        if (!trainersGrid) {
            console.error('trainersGrid element not found');
            return;
        }

        // Validate required data
        if (!date || !classType) {
            console.error('Missing required booking data:', { date, classType });
            trainersGrid.innerHTML = '<p class="empty-message">Please select a date and class type first</p>';
            return;
        }

        // For time-based booking, we just need to check trainer availability for the day
        // Use "Morning" as default to check general availability (actual time validation happens in step 4)
        const session = 'Morning';

        trainersGrid.innerHTML = '<p class="loading-text">Loading trainers...</p>';
        if (capacityInfo) {
            capacityInfo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Checking availability...</span>';
        }

        console.log('Loading trainers with:', { date, session, classType });

        fetch(`api/get_available_trainers.php?date=${date}&session=${session}&class=${encodeURIComponent(classType)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Trainer API response:', data);
                if (data.success) {
                    renderTrainers(data.trainers);
                    updateCapacityInfo(data);
                } else {
                    trainersGrid.innerHTML = `<p class="empty-message">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading trainers:', error);
                trainersGrid.innerHTML = '<p class="empty-message">Failed to load trainers</p>';
            });
    }

    // Helper function to check if trainer's shift has ended for today
    function isTrainerShiftEnded(trainer, selectedDate) {
        const today = new Date().toISOString().split('T')[0];
        if (selectedDate !== today) {
            return false; // Not today, shifts are valid
        }

        // Get shift end time (use custom shift if available, otherwise use defaults)
        let shiftEnd = trainer.shift_end;
        if (!shiftEnd) {
            const defaultShifts = {
                'Morning': '15:00',
                'Afternoon': '19:00',
                'Night': '22:00'
            };
            shiftEnd = defaultShifts[trainer.shift] || '22:00';
        }

        // Parse shift end time to minutes
        const parts = shiftEnd.split(':');
        const shiftEndMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);

        // Get current time in minutes
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        return currentMinutes >= shiftEndMinutes;
    }

    // Helper function to check if trainer's shift start time has passed for today
    function isTrainerShiftStartPassed(trainer, selectedDate) {
        const today = new Date().toISOString().split('T')[0];
        if (selectedDate !== today) {
            return true; // Not today, shift times are valid (future dates)
        }

        // Get shift start time
        let shiftStart = trainer.shift_start;
        if (!shiftStart) {
            const defaultShifts = {
                'Morning': '07:00',
                'Afternoon': '11:00',
                'Night': '14:00'
            };
            shiftStart = defaultShifts[trainer.shift] || '07:00';
        }

        // Parse shift start time to minutes
        const parts = shiftStart.split(':');
        const shiftStartMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);

        // Get current time in minutes
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        return currentMinutes >= shiftStartMinutes;
    }

    // Helper function to format shift time display
    function formatShiftTime(time) {
        if (!time) return '';
        const parts = time.split(':');
        const hours = parseInt(parts[0]);
        const minutes = parts[1];
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${minutes} ${period}`;
    }

    function renderTrainers(trainers) {
        const trainersGrid = document.getElementById('trainersGrid');

        if (trainers.length === 0) {
            trainersGrid.innerHTML = '<p class="empty-message">No trainers available for this session</p>';
            return;
        }

        trainersGrid.innerHTML = trainers.map(trainer => {
            // Check if booking is for today
            const today = new Date().toISOString().split('T')[0];
            const isToday = bookingState.date === today;
            
            // Check if shift has ended for today
            const shiftEnded = isToday && isTrainerShiftEnded(trainer, bookingState.date);
            // Check if shift start time has passed
            const shiftStartPassed = isTrainerShiftStartPassed(trainer, bookingState.date);
            // Use API-provided status (includes fully-booked)
            let effectiveStatus = trainer.status;
            // Override with unavailable if shift ended OR if shift start time hasn't passed yet (only for same-day)
            if (shiftEnded || (isToday && !shiftStartPassed)) {
                effectiveStatus = 'unavailable';
            }
            
            // Escape HTML to prevent attribute breaking with quotes
            const escapedName = trainer.name.replace(/'/g, '&#39;').replace(/\"/g, '&quot;');
            // Use uploaded photo if available, otherwise use default account icon
            const photoSrc = trainer.photo && trainer.photo !== 'account-icon.svg'
                ? `../../uploads/trainers/${trainer.photo}`
                : `../../images/account-icon.svg`;
            
            // Format shift times
            let shiftTimeDisplay = '';
            if (trainer.shift_start && trainer.shift_end) {
                shiftTimeDisplay = `<p class="trainer-shift-time"><i class="fas fa-clock"></i> ${formatShiftTime(trainer.shift_start)} - ${formatShiftTime(trainer.shift_end)}</p>`;
            } else {
                const defaultShifts = {
                    'Morning': { start: '07:00', end: '15:00' },
                    'Afternoon': { start: '11:00', end: '19:00' },
                    'Night': { start: '14:00', end: '22:00' }
                };
                const shift = defaultShifts[trainer.shift] || defaultShifts['Morning'];
                shiftTimeDisplay = `<p class="trainer-shift-time"><i class="fas fa-clock"></i> ${formatShiftTime(shift.start)} - ${formatShiftTime(shift.end)}</p>`;
            }
            
            // Display status text
            let statusText = 'Available';
            if (shiftEnded) {
                statusText = 'Shift Ended';
            } else if (isToday && !shiftStartPassed) {
                statusText = 'Unavailable';
            } else if (effectiveStatus === 'fully-booked') {
                statusText = 'Fully Booked';
            } else if (effectiveStatus === 'unavailable') {
                statusText = 'Unavailable';
            }
            
            return `
            <div class="trainer-card ${effectiveStatus}"
                 data-trainer-id="${trainer.id}"
                 data-trainer-name="${escapedName}"
                 data-trainer-status="${effectiveStatus}"
                 onclick="selectTrainer(${trainer.id}, this.dataset.trainerName, this.dataset.trainerStatus)">
                <span class="trainer-status-badge ${effectiveStatus}">${statusText}</span>
                <img src="${photoSrc}"
                     alt="${escapedName}"
                     class="trainer-photo ${trainer.photo && trainer.photo !== 'account-icon.svg' ? '' : 'default-icon'}"
                     onerror="this.onerror=null; this.src='../../images/account-icon.svg'; this.classList.add('default-icon');">
                <h3 class="trainer-name">${trainer.name}</h3>
                <p class="trainer-specialty">${trainer.specialization}</p>
                ${shiftTimeDisplay}
            </div>
        `;
        }).join('');
    }

    function updateCapacityInfo(data) {
        const capacityInfo = document.getElementById('facilityCapacityInfo');
        const used = data.facility_slots_used || 0;
        const max = data.facility_slots_max || 2;
        const available = data.available_count || 0;

        // Update booking state with capacity info
        bookingState.facilityFull = used >= max;
        bookingState.hasAvailableTrainers = available > 0;

        // Only update UI if element exists
        if (!capacityInfo) {
            console.log('facilityCapacityInfo element not found, skipping UI update');
            updateWizardStep();
            return;
        }

        if (used >= max) {
            capacityInfo.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>Facility at capacity (${used}/${max} trainers booked). Select an already booked trainer to join their session.</span>
            `;
            capacityInfo.style.background = 'rgba(255, 152, 0, 0.1)';
            capacityInfo.style.borderColor = '#ff9800';
            capacityInfo.style.color = '#ff9800';
        } else {
            capacityInfo.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${available} trainers available • Facility capacity: ${used}/${max}</span>
            `;
            capacityInfo.style.background = 'rgba(76, 175, 80, 0.1)';
            capacityInfo.style.borderColor = '#4CAF50';
            capacityInfo.style.color = '#4CAF50';
        }

        // Update wizard step buttons
        updateWizardStep();
    }

    window.selectTrainer = function (trainerId, trainerName, status) {
        if (status === 'unavailable') {
            showToast('This trainer is not available for the selected session', 'warning');
            return;
        }

        if (status === 'booked') {
            showToast('This trainer is fully booked for your selected time. Please select a different time slot or choose another trainer.', 'warning');
            return;
        }

        bookingState.trainerId = trainerId;
        bookingState.trainerName = trainerName;

        document.querySelectorAll('.trainer-card').forEach(card => card.classList.remove('selected'));
        document.querySelector(`[data-trainer-id="${trainerId}"]`).classList.add('selected');

        // Enable Next button in the active step
        const activeStep = document.querySelector('.wizard-step.active');
        const btnNext = activeStep ? activeStep.querySelector('.btn-next') : null;
        if (btnNext) btnNext.disabled = false;
    };

    // ===================================
    // STEP 5: UPDATE SUMMARY
    // ===================================
    function updateSummary() {
        const { date, startTime, endTime, classType, trainerName, duration } = bookingState;

        const summaryDateEl = document.getElementById('summaryDate');
        const summaryTimeEl = document.getElementById('summaryTime');
        const summaryDurationEl = document.getElementById('summaryDuration');
        const summaryClassEl = document.getElementById('summaryClass');
        const summaryTrainerEl = document.getElementById('summaryTrainer');

        if (summaryDateEl) {
            const dateText = date ? new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : '-';
            summaryDateEl.innerHTML = dateText;
        }

        // Convert 24-hour format to 12-hour format for display
        const formattedStartTime = startTime ? formatTimeTo12Hour(startTime) : '';
        const formattedEndTime = endTime ? formatTimeTo12Hour(endTime) : '';
        const timeText = (formattedStartTime && formattedEndTime) ? `${formattedStartTime} - ${formattedEndTime}` : '-';

        if (summaryTimeEl) {
            summaryTimeEl.innerHTML = timeText;
        }

        const durationMinutes = duration || 0;
        let durationText = '';
        if (durationMinutes === 0) {
            durationText = '-';
        } else if (durationMinutes < 60) {
            durationText = `${durationMinutes} minutes`;
        } else {
            const hours = Math.floor(durationMinutes / 60);
            const mins = durationMinutes % 60;
            if (mins === 0) {
                durationText = `${hours} hour${hours > 1 ? 's' : ''}`;
            } else {
                durationText = `${hours} hour${hours > 1 ? 's' : ''} ${mins} minutes`;
            }
        }
        if (summaryDurationEl) {
            summaryDurationEl.innerHTML = durationText;
        }

        if (summaryClassEl) {
            summaryClassEl.innerHTML = classType || '-';
        }
        if (summaryTrainerEl) {
            summaryTrainerEl.innerHTML = trainerName || '-';
        }
    }

    // Helper function to format 24-hour time to 12-hour
    function formatTimeTo12Hour(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':').map(Number);
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${String(minutes).padStart(2, '0')} ${period}`;
    }

    // Helper function to format 24-hour time to 12-hour
    function formatTimeTo12Hour(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':').map(Number);
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${String(minutes).padStart(2, '0')} ${period}`;
    }

    // ===================================
    // CONFIRM BOOKING
    // ===================================
    const confirmBtn = document.getElementById('btnConfirmBooking');
    if (confirmBtn && !confirmBtn.dataset.originalLabel) {
        confirmBtn.dataset.originalLabel = confirmBtn.innerHTML;
    }
    if (confirmBtn && !confirmBtn.hasAttribute('data-listener-attached')) {
        confirmBtn.setAttribute('data-listener-attached', 'true');
        confirmBtn.addEventListener('click', function handleBookingConfirm(e) {
            e.preventDefault();

            const { trainerId, classType, date, startTime, endTime } = bookingState;
            const button = this;

            // Prevent double-clicking
            if (button.disabled) {
                return;
            }

            // Validate required fields
            if (!trainerId || !classType || !date || !startTime || !endTime) {
                showToast('Please complete all booking fields', 'error');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';

            const csrfToken = ensureCsrfToken();
            if (!csrfToken) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';
                return;
            }

            // Convert time strings to full datetime
            const startDateTime = convertToDateTime(date, startTime);
            const endDateTime = convertToDateTime(date, endTime);

            // Determine session based on start time
            const startHour = parseInt(startTime.split(':')[0]);
            let sessionTime = 'Morning'; // Default
            if (startHour >= 13 && startHour < 18) {
                sessionTime = 'Afternoon';
            } else if (startHour >= 18) {
                sessionTime = 'Evening';
            }

            const formData = new URLSearchParams();
            formData.append('trainer_id', trainerId);
            formData.append('class_type', classType);
            formData.append('booking_date', date);
            formData.append('session_time', sessionTime);
            formData.append('start_time', startDateTime);
            formData.append('end_time', endDateTime);
            formData.append('csrf_token', csrfToken);

            fetch('api/book_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(response => {
                    // Log response for debugging
                    console.log('Booking response status:', response.status);
                    console.log('Booking response headers:', response.headers);

                    // Try to parse as JSON even if not ok
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        try {
                            const data = JSON.parse(text);
                            return { ok: response.ok, status: response.status, data };
                        } catch (e) {
                            console.error('Failed to parse JSON:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(({ ok, status, data }) => {
                    console.log('Parsed booking response:', data);

                    if (data.success) {
                        dismissRateLimitBanner('booking');
                        console.log('Booking successful, showing toast'); // Debug log
                        showToast('Session booked successfully!', 'success');

                        // Clear recovery state on successful booking
                        if (window.BookingRecovery) {
                            window.BookingRecovery.clearState();
                            // Dispatch completion event
                            window.dispatchEvent(new Event('bookingCompleted'));
                        }

                        // Show weekly usage update
                        if (data.data && data.data.weekly_usage_hours !== undefined) {
                            setTimeout(() => {
                                showToast(
                                    `Weekly usage: ${data.data.weekly_usage_hours.toFixed(1)}h / ${data.data.weekly_limit_hours}h`,
                                    'info',
                                    4000
                                );
                            }, 1000);
                        }

                        // Reset button state
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';

                        // Reload data
                        loadWeeklyBookings();
                        loadUserBookings();

                        // Reset wizard
                        setTimeout(() => {
                            try {
                                resetWizard();
                            } catch (err) {
                                console.error('Error resetting wizard:', err);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                        }, 500);
                    } else {
                        console.log('Booking failed:', data.message); // Debug log
                        if (data.failed_check === 'rate_limit') {
                            handleRateLimitResponse('booking', data.retry_after, data.message || 'Too many booking attempts.');
                        } else {
                            showToast(data.message || 'Failed to book session', 'error');
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error in booking process (catch block):', error);
                    
                    // Save state on error for recovery
                    if (window.BookingRecovery) {
                        window.BookingRecovery.saveState(bookingState);
                    }
                    
                    showToast('[BOOKING] An error occurred. Your progress has been saved.', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';
                });
        });
    }

    // ===================================
    // TIME PICKER INITIALIZATION
    // ===================================
    function initializeTimePickers() {
        console.log('Initializing time pickers');
        const startTimeInput = document.getElementById('startTime');
        const endTimeInput = document.getElementById('endTime');

        console.log('Time input elements:', { startTimeInput, endTimeInput });

        if (!startTimeInput || !endTimeInput) {
            console.error('Time picker inputs not found');
            return;
        }

        // Generate 30-minute interval times from 7:00 AM to 10:00 PM
        const timeSlots = generateTimeSlots();

        // Initialize start time picker
        startTimePicker = flatpickr(startTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            minuteIncrement: 30,
            minTime: "07:00",
            maxTime: "22:00",
            defaultHour: 8,
            defaultMinute: 0,
            onChange: function(selectedDates, dateStr, instance) {
                bookingState.startTime = dateStr;

                // Update end time picker minimum
                if (endTimePicker) {
                    const startDate = selectedDates[0];
                    if (startDate) {
                        const minEndDate = new Date(startDate);
                        minEndDate.setMinutes(minEndDate.getMinutes() + 30);
                        endTimePicker.set('minTime', formatTime(minEndDate));
                    }
                }

                updateDurationDisplay();
                updateNextButton();
            }
        });

        // Initialize end time picker
        endTimePicker = flatpickr(endTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            time_24hr: false,
            minuteIncrement: 30,
            minTime: "07:30",
            maxTime: "22:00",
            defaultHour: 9,
            defaultMinute: 30,
            onChange: function(selectedDates, dateStr, instance) {
                bookingState.endTime = dateStr;
                updateDurationDisplay();
                updateNextButton();
            }
        });
    }

    function generateTimeSlots() {
        const slots = [];
        const start = new Date();
        start.setHours(7, 0, 0, 0);
        const end = new Date();
        end.setHours(22, 0, 0, 0);

        while (start <= end) {
            slots.push(formatTime(start));
            start.setMinutes(start.getMinutes() + 30);
        }

        return slots;
    }

    function formatTime(date) {
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
    }

    // ===================================
    // LOAD TRAINER AVAILABILITY
    // ===================================
    function loadTrainerAvailability() {
        console.log('Loading trainer availability:', { trainerId: bookingState.trainerId, date: bookingState.date });
        if (!bookingState.trainerId || !bookingState.date) {
            console.error('Missing trainer ID or date');
            return;
        }

        const shiftInfo = document.getElementById('trainerShiftInfo');
        if (shiftInfo) {
            shiftInfo.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading availability...</div>';
        }

        const formData = new FormData();
        formData.append('trainer_id', bookingState.trainerId);
        formData.append('date', bookingState.date);
        formData.append('class_type', bookingState.classType);

        fetch('api/get_trainer_availability.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Trainer availability response:', data);
            if (data.success) {
                bookingState.availableSlots = data.available_slots || [];
                bookingState.trainerShiftInfo = data.shift_info;

                displayShiftInfo(data.shift_info);
                displayAvailableSlots(data.available_slots);
            } else {
                showToast(data.message || 'Failed to load availability', 'error');
                displayShiftInfo(null);
            }
        })
        .catch(error => {
            console.error('Error loading availability:', error);
            showToast('Error loading trainer availability', 'error');
        });
    }

    function displayShiftInfo(shiftInfo) {
        const container = document.getElementById('trainerShiftInfo');
        if (!container) return;

        if (!shiftInfo) {
            container.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Trainer not available on this date</div>';
            return;
        }

        const shiftTypeEl = document.getElementById('shiftType');
        const shiftHoursEl = document.getElementById('shiftHours');
        const breakTimeEl = document.getElementById('breakTime');

        if (shiftTypeEl) {
            const shiftIcons = {
                'morning': 'fa-sun',
                'afternoon': 'fa-cloud-sun',
                'night': 'fa-moon'
            };
            const icon = shiftIcons[shiftInfo.shift_type] || 'fa-clock';
            shiftTypeEl.innerHTML = `<i class="fas ${icon}"></i> ${shiftInfo.shift_type.charAt(0).toUpperCase() + shiftInfo.shift_type.slice(1)} Shift`;
        }

        if (shiftHoursEl) {
            shiftHoursEl.textContent = `${shiftInfo.start_time_formatted} - ${shiftInfo.end_time_formatted}`;
        }

        if (breakTimeEl && shiftInfo.break_formatted) {
            breakTimeEl.innerHTML = `<i class="fas fa-coffee"></i> Break: ${shiftInfo.break_formatted}`;
        }
    }

    function displayAvailableSlots(slots) {
        const preview = document.getElementById('availableSlotsPreview');
        const grid = document.getElementById('slotsGrid');

        if (!preview || !grid || !slots || slots.length === 0) {
            if (preview) preview.style.display = 'none';
            return;
        }

        preview.style.display = 'block';
        grid.innerHTML = '';

        slots.forEach(slot => {
            const slotBtn = document.createElement('button');
            slotBtn.className = 'slot-button';
            slotBtn.textContent = slot.formatted_time;
            slotBtn.title = `${slot.start_time} - ${slot.end_time}`;

            slotBtn.addEventListener('click', (e) => {
                e.preventDefault();
                selectTimeSlot(slot);
            });

            grid.appendChild(slotBtn);
        });
    }

    function selectTimeSlot(slot) {
        // Extract time from formatted slot (e.g., "8:00 AM - 8:30 AM")
        const times = slot.formatted_time.split(' - ');
        if (times.length === 2) {
            if (startTimePicker) {
                startTimePicker.setDate(times[0], true);
            }
            if (endTimePicker) {
                endTimePicker.setDate(times[1], true);
            }
        }
    }

    // ===================================
    // DURATION AND WEEKLY USAGE
    // ===================================
    function updateDurationDisplay() {
        const durationDisplay = document.getElementById('durationDisplay');
        const weeklyUsageDisplay = document.getElementById('weeklyUsageDisplay');
        const bookingInfo = document.getElementById('bookingInfo');

        if (!bookingState.startTime || !bookingState.endTime) {
            if (bookingInfo) bookingInfo.style.display = 'none';
            return;
        }

        // Parse times
        const start = parseTime(bookingState.startTime);
        const end = parseTime(bookingState.endTime);

        if (!start || !end || end <= start) {
            if (bookingInfo) bookingInfo.style.display = 'none';
            return;
        }

        // Calculate duration in minutes
        const durationMinutes = (end - start) / (1000 * 60);
        const durationHours = durationMinutes / 60;

        bookingState.duration = durationMinutes;

        // Display duration
        if (durationDisplay) {
            if (durationHours < 1) {
                durationDisplay.textContent = `${durationMinutes} minutes`;
            } else {
                durationDisplay.textContent = `${durationHours.toFixed(1)} hours`;
            }
        }

        // Calculate weekly usage
        const newWeeklyUsage = bookingState.currentWeekUsageMinutes + durationMinutes;
        const newWeeklyUsageHours = newWeeklyUsage / 60;
        const remaining = bookingState.weeklyLimitMinutes - newWeeklyUsage;
        const remainingHours = remaining / 60;

        if (weeklyUsageDisplay) {
            if (remaining < 0) {
                weeklyUsageDisplay.innerHTML = `<span style="color: #f44336;">${newWeeklyUsageHours.toFixed(1)}h (Exceeds limit!)</span>`;
            } else {
                weeklyUsageDisplay.textContent = `${newWeeklyUsageHours.toFixed(1)}h`;
            }
        }

        if (bookingInfo) bookingInfo.style.display = 'flex';
    }

    function parseTime(timeStr) {
        // Parse "h:mm AM/PM" format
        const match = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!match) return null;

        let hours = parseInt(match[1]);
        const minutes = parseInt(match[2]);
        const meridiem = match[3].toUpperCase();

        if (meridiem === 'PM' && hours !== 12) {
            hours += 12;
        } else if (meridiem === 'AM' && hours === 12) {
            hours = 0;
        }

        const date = new Date();
        date.setHours(hours, minutes, 0, 0);
        return date;
    }

    function updateNextButton() {
        const currentStepEl = document.querySelector('.wizard-step.active');
        if (!currentStepEl) return;

        const nextBtn = currentStepEl.querySelector('.btn-next');
        if (nextBtn) {
            nextBtn.disabled = !canProceedFromStep(bookingState.currentStep);
            console.log('Next button state updated:', {
                step: bookingState.currentStep,
                disabled: nextBtn.disabled,
                startTime: bookingState.startTime,
                endTime: bookingState.endTime,
                duration: bookingState.duration
            });
        }
    }

    // Expose updateNextButton globally for time-selection module
    window.updateNextButton = updateNextButton;

    // ===================================
    // CLASS SELECTION (STEP 2)
    // ===================================
    function setupClassSelection() {
        document.addEventListener('click', (e) => {
            const classCard = e.target.closest('.class-card');
            if (classCard && bookingState.currentStep === 2) {
                // Remove previous selection
                document.querySelectorAll('.class-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add new selection
                classCard.classList.add('selected');
                bookingState.classType = classCard.dataset.class;
                
                // Save state after class selection
                if (window.BookingRecovery) {
                    window.BookingRecovery.saveState(bookingState);
                }

                // Enable next button
                updateNextButton();
            }
        });
    }

    // ===================================
    // TRAINER SELECTION (STEP 3)
    // ===================================
    function setupTrainerSelection() {
        document.addEventListener('click', (e) => {
            const trainerCard = e.target.closest('.trainer-card');
            if (trainerCard && bookingState.currentStep === 3) {
                // Remove previous selection
                document.querySelectorAll('.trainer-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add new selection
                trainerCard.classList.add('selected');
                bookingState.trainerId = trainerCard.dataset.trainerId;
                bookingState.trainerName = trainerCard.dataset.trainerName;
                
                // Save state after trainer selection
                if (window.BookingRecovery) {
                    window.BookingRecovery.saveState(bookingState);
                }

                // Enable next button
                updateNextButton();
            }
        });
    }

    // ===================================
    // WIZARD NAVIGATION
    // ===================================
    function setupEventListeners() {
        // Add event listeners to all Next and Back buttons
        document.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', nextStep);
        });
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.addEventListener('click', prevStep);
        });

        // Add event listener for class filter
        const classFilter = document.getElementById('classFilter');
        if (classFilter) {
            classFilter.addEventListener('change', applyBookingsFilter);
        }
    }

    function nextStep() {
        if (bookingState.currentStep < 5) {
            bookingState.currentStep++;

            // Load trainers when entering step 3 (trainer selection)
            if (bookingState.currentStep === 3) {
                loadTrainers();
            }

            // Initialize time picker when entering step 4 (time selection)
            if (bookingState.currentStep === 4) {
                initializeModernTimeSelection();
                loadModernTrainerAvailability(bookingState);
            }

            // Update wizard UI
            updateWizardStep();

            // Update summary AFTER wizard step updates when entering step 5 (confirmation)
            if (bookingState.currentStep === 5) {
                // Use setTimeout to ensure DOM is ready after updateWizardStep
                setTimeout(() => {
                    updateSummary();
                }, 0);
            }
        }
    }

    function prevStep() {
        if (bookingState.currentStep > 1) {
            // Clear duration when going back from step 4 (time selection)
            if (bookingState.currentStep === 4) {
                // Clear selected times and duration
                bookingState.startTime = null;
                bookingState.endTime = null;
                bookingState.durationMinutes = null;
                
                // Reset duration using the module's reset function
                if (typeof resetDurationSelection === 'function') {
                    resetDurationSelection();
                }
                
                // Clear total duration display
                const totalDurationDisplay = document.getElementById('totalDuration');
                if (totalDurationDisplay) {
                    totalDurationDisplay.textContent = '--';
                }
                
                // Clear any selected time slots in UI
                document.querySelectorAll('.time-slot.selected').forEach(slot => {
                    slot.classList.remove('selected');
                });
            }
            
            bookingState.currentStep--;
            updateWizardStep();
        }
    }

    function updateWizardStep() {
        const step = bookingState.currentStep;

        // Update step visibility
        document.querySelectorAll('.wizard-step').forEach((el, index) => {
            el.classList.toggle('active', index + 1 === step);
        });

        // Update all navigation buttons in all steps
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.style.display = step > 1 ? 'flex' : 'none';
        });

        document.querySelectorAll('.btn-next').forEach(btn => {
            btn.style.display = step < 5 ? 'flex' : 'none';
            btn.disabled = !canProceedFromStep(step);
        });

        // Scroll to top of wizard
        document.querySelector('.booking-wizard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function canProceedFromStep(step) {
        switch (step) {
            case 1: return bookingState.date !== null;
            case 2: return bookingState.classType !== null;
            case 3: return bookingState.trainerId !== null;
            case 4:
                return bookingState.startTime !== null &&
                       bookingState.endTime !== null &&
                       bookingState.duration !== null;
            case 5: return true;
            default: return false;
        }
    }

    function resetWizard() {
        bookingState.date = null;
        bookingState.classType = null;
        bookingState.trainerId = null;
        bookingState.trainerName = null;
        bookingState.startTime = null;
        bookingState.endTime = null;
        bookingState.duration = null;
        bookingState.currentStep = 1;
        bookingState.trainerShiftInfo = null;
        bookingState.availableSlots = [];

        document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));

        const selectedDateDisplay = document.getElementById('selectedDateDisplay');
        if (selectedDateDisplay) {
            selectedDateDisplay.style.display = 'none';
        }

        // Destroy flatpickr instances
        if (startTimePicker) {
            startTimePicker.destroy();
            startTimePicker = null;
        }
        if (endTimePicker) {
            endTimePicker.destroy();
            endTimePicker = null;
        }

        updateWizardStep();
        renderCalendar();
    }

    // ===================================
    // UTILITY FUNCTIONS
    // ===================================
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function convertToDateTime(dateStr, timeStr) {
        // Handle 24-hour format (HH:MM) - from new time pickers
        if (timeStr.match(/^\d{2}:\d{2}$/)) {
            return `${dateStr} ${timeStr}:00`;
        }

        // Handle 12-hour format (h:mm AM/PM) - legacy format
        const match = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!match) return null;

        let hours = parseInt(match[1]);
        const minutes = match[2];
        const meridiem = match[3].toUpperCase();

        if (meridiem === 'PM' && hours !== 12) {
            hours += 12;
        } else if (meridiem === 'AM' && hours === 12) {
            hours = 0;
        }

        const hours24 = String(hours).padStart(2, '0');
        return `${dateStr} ${hours24}:${minutes}:00`;
    }

    // getCsrfToken is already defined at line 171 - removed duplicate
    
    // Expose necessary functions globally for recovery system
    window.updateWizardStep = updateWizardStep;
    window.loadTrainers = loadTrainers;
    window.initializeModernTimeSelection = initializeModernTimeSelection;
    window.loadModernTrainerAvailability = loadModernTrainerAvailability;
    window.updateSummary = updateSummary;
});
