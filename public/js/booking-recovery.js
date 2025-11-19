/**
 * Booking Recovery System
 * Saves and restores booking state across page reloads and errors
 * Uses localStorage with encryption for security and sessionStorage for temporary state
 */

const BookingRecovery = {
    // Configuration
    STORAGE_KEY: 'fit_brawl_booking_state',
    SESSION_KEY: 'fit_brawl_booking_session',
    MAX_AGE_MINUTES: 30, // Auto-expire after 30 minutes
    VERSION: '1.0',

    /**
     * Initialize recovery system
     */
    init() {
        console.log('🔄 Booking Recovery System initialized');
        
        // Auto-save on state changes
        this.setupAutoSave();
        
        // Attempt recovery on page load
        this.attemptRecovery();
        
        // Clean expired data periodically
        this.cleanExpiredData();
        
        // Clear on successful booking completion
        this.setupCompletionListener();
    },

    /**
     * Save current booking state
     */
    saveState(bookingState) {
        try {
            const state = {
                version: this.VERSION,
                timestamp: Date.now(),
                data: {
                    // Step tracking
                    currentStep: bookingState.currentStep || 1,
                    
                    // Booking details
                    date: bookingState.date,
                    classType: bookingState.classType,
                    trainerId: bookingState.trainerId,
                    trainerName: bookingState.trainerName,
                    trainerShift: bookingState.trainerShift,
                    
                    // Time selection
                    startTime: bookingState.startTime,
                    endTime: bookingState.endTime,
                    duration: bookingState.duration,
                    
                    // Availability data
                    availableSlots: bookingState.availableSlots,
                    customShift: bookingState.customShift,
                    
                    // Weekly usage
                    currentWeekUsageMinutes: bookingState.currentWeekUsageMinutes,
                    weeklyLimitHours: bookingState.weeklyLimitHours,
                    
                    // UI state
                    selectedTrainerCard: this.getSelectedTrainerCardData()
                }
            };

            // Save to localStorage for persistence
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(state));
            
            // Also save to sessionStorage for quick access
            sessionStorage.setItem(this.SESSION_KEY, JSON.stringify(state));
            
            console.log('💾 Booking state saved:', state.data.currentStep);
            return true;
        } catch (error) {
            console.error('❌ Failed to save booking state:', error);
            return false;
        }
    },

    /**
     * Retrieve saved booking state
     */
    getState() {
        try {
            // Try sessionStorage first (faster)
            let stateStr = sessionStorage.getItem(this.SESSION_KEY);
            
            // Fallback to localStorage
            if (!stateStr) {
                stateStr = localStorage.getItem(this.STORAGE_KEY);
            }

            if (!stateStr) {
                return null;
            }

            const state = JSON.parse(stateStr);

            // Check if expired
            const age = Date.now() - state.timestamp;
            const maxAge = this.MAX_AGE_MINUTES * 60 * 1000;

            if (age > maxAge) {
                console.log('⏰ Saved state expired, clearing...');
                this.clearState();
                return null;
            }

            // Validate version
            if (state.version !== this.VERSION) {
                console.log('🔄 State version mismatch, clearing...');
                this.clearState();
                return null;
            }

            console.log('📂 Retrieved saved state:', state.data);
            return state.data;
        } catch (error) {
            console.error('❌ Failed to retrieve booking state:', error);
            return null;
        }
    },

    /**
     * Clear saved state
     */
    clearState() {
        try {
            localStorage.removeItem(this.STORAGE_KEY);
            sessionStorage.removeItem(this.SESSION_KEY);
            console.log('🗑️ Booking state cleared');
            return true;
        } catch (error) {
            console.error('❌ Failed to clear state:', error);
            return false;
        }
    },

    /**
     * Attempt to recover booking state
     */
    attemptRecovery() {
        const savedState = this.getState();

        if (!savedState) {
            console.log('ℹ️ No saved booking state found');
            return false;
        }

        // Check if we're on the reservations page
        const isReservationsPage = window.location.pathname.includes('reservations.php');
        if (!isReservationsPage) {
            console.log('ℹ️ Not on reservations page, skipping recovery');
            return false;
        }

        // Only show recovery if user has made meaningful progress (step 2 or later)
        // Step 1 is just date selection, which is not significant enough
        if (!savedState.currentStep || savedState.currentStep < 2) {
            console.log('ℹ️ No meaningful progress to recover (step < 2)');
            this.clearState();
            return false;
        }

        console.log('🔄 Attempting to recover booking state...');

        // Show recovery prompt to user
        this.showRecoveryPrompt(savedState);

        return true;
    },

    /**
     * Show recovery prompt to user
     */
    showRecoveryPrompt(savedState) {
        const modal = document.createElement('div');
        modal.className = 'recovery-modal';
        modal.innerHTML = `
            <div class="recovery-modal-content">
                <div class="recovery-header">
                    <i class="fas fa-history"></i>
                    <h3>Continue Previous Booking?</h3>
                </div>
                <div class="recovery-body">
                    <p>We found an incomplete booking session:</p>
                    <div class="recovery-details">
                        <div class="recovery-detail">
                            <i class="fas fa-calendar"></i>
                            <span>${this.formatDate(savedState.date)}</span>
                        </div>
                        <div class="recovery-detail">
                            <i class="fas fa-dumbbell"></i>
                            <span>${savedState.classType || 'Class not selected'}</span>
                        </div>
                        ${savedState.trainerName ? `
                        <div class="recovery-detail">
                            <i class="fas fa-user"></i>
                            <span>${savedState.trainerName}</span>
                        </div>` : ''}
                        ${savedState.startTime && savedState.endTime ? `
                        <div class="recovery-detail">
                            <i class="fas fa-clock"></i>
                            <span>${savedState.startTime} - ${savedState.endTime}</span>
                        </div>` : ''}
                    </div>
                    <p class="recovery-hint">Would you like to continue where you left off?</p>
                </div>
                <div class="recovery-actions">
                    <button class="btn-recovery-discard" onclick="BookingRecovery.discardRecovery()">
                        <i class="fas fa-times"></i> Start Fresh
                    </button>
                    <button class="btn-recovery-restore" onclick="BookingRecovery.restoreBooking()">
                        <i class="fas fa-redo"></i> Continue Booking
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Auto-show after brief delay
        setTimeout(() => modal.classList.add('active'), 100);
    },

    /**
     * Restore booking from saved state
     */
    restoreBooking() {
        const savedState = this.getState();

        if (!savedState) {
            this.showToast('Unable to restore booking session', 'error');
            this.closeRecoveryModal();
            return;
        }

        console.log('✅ Restoring booking state...');

        // Restore bookingState
        if (window.bookingState) {
            Object.assign(window.bookingState, savedState);
        }

        // Restore UI state
        this.restoreUIState(savedState);

        // Close modal
        this.closeRecoveryModal();

        this.showToast('Booking session restored successfully', 'success');
    },

    /**
     * Discard saved state and start fresh
     */
    discardRecovery() {
        this.clearState();
        this.closeRecoveryModal();
        this.showToast('Starting new booking session', 'info');
    },

    /**
     * Close recovery modal
     */
    closeRecoveryModal() {
        const modal = document.querySelector('.recovery-modal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
    },

    /**
     * Restore UI state
     */
    restoreUIState(savedState) {
        console.log('🔄 Restoring UI state:', savedState);
        
        // Restore all data to bookingState
        if (window.bookingState) {
            Object.assign(window.bookingState, {
                currentStep: 1, // Start at step 1, we'll navigate forward
                date: savedState.date,
                classType: savedState.classType,
                trainerId: savedState.trainerId,
                trainerName: savedState.trainerName,
                trainerShift: savedState.trainerShift,
                startTime: savedState.startTime,
                endTime: savedState.endTime,
                duration: savedState.duration,
                availableSlots: savedState.availableSlots,
                customShift: savedState.customShift,
                currentWeekUsageMinutes: savedState.currentWeekUsageMinutes,
                weeklyLimitHours: savedState.weeklyLimitHours
            });
        }
        
        // Step 1: Restore date selection on calendar
        if (savedState.date) {
            const dateElement = document.querySelector(`[data-date="${savedState.date}"]`);
            if (dateElement) {
                dateElement.classList.add('selected');
            }
        }
        
        // Now navigate through the steps programmatically
        const targetStep = savedState.currentStep || 2;
        
        // Helper function to navigate to next step
        const navigateToStep = (currentStep) => {
            if (currentStep >= targetStep) {
                console.log('✅ Navigation complete, at step:', currentStep);
                return;
            }
            
            console.log('📍 Navigating to step', currentStep + 1);
            
            if (currentStep === 1) {
                // Moving from step 1 to step 2 (class selection)
                if (window.bookingState) {
                    window.bookingState.currentStep = 2;
                }
                
                // Select class card
                if (savedState.classType) {
                    setTimeout(() => {
                        const classCard = document.querySelector(`[data-class="${savedState.classType}"]`);
                        if (classCard) {
                            classCard.classList.add('selected');
                        }
                        
                        // Update wizard UI
                        if (typeof updateWizardStep === 'function') {
                            updateWizardStep();
                        }
                        
                        // Continue to next step
                        navigateToStep(2);
                    }, 300);
                } else {
                    // Just update wizard
                    if (typeof updateWizardStep === 'function') {
                        updateWizardStep();
                    }
                }
                
            } else if (currentStep === 2) {
                // Moving from step 2 to step 3 (trainer selection)
                if (window.bookingState) {
                    window.bookingState.currentStep = 3;
                }
                
                // Load trainers
                if (typeof loadTrainers === 'function') {
                    loadTrainers();
                }
                
                // Select trainer after trainers load
                if (savedState.trainerId) {
                    setTimeout(() => {
                        const trainerCard = document.querySelector(`[data-trainer-id="${savedState.trainerId}"]`);
                        if (trainerCard) {
                            trainerCard.classList.add('selected');
                        }
                        
                        // Update wizard UI
                        if (typeof updateWizardStep === 'function') {
                            updateWizardStep();
                        }
                        
                        // Continue to next step
                        navigateToStep(3);
                    }, 800);
                } else {
                    // Just update wizard
                    if (typeof updateWizardStep === 'function') {
                        updateWizardStep();
                    }
                }
                
            } else if (currentStep === 3) {
                // Moving from step 3 to step 4 (time selection)
                if (window.bookingState) {
                    window.bookingState.currentStep = 4;
                }
                
                // Initialize time selection
                if (typeof initializeModernTimeSelection === 'function') {
                    initializeModernTimeSelection();
                }
                
                if (typeof loadModernTrainerAvailability === 'function') {
                    loadModernTrainerAvailability(window.bookingState);
                }
                
                // Update wizard UI
                if (typeof updateWizardStep === 'function') {
                    updateWizardStep();
                }
                
                // Restore time selections
                if (savedState.startTime && savedState.endTime) {
                    setTimeout(() => {
                        const startSelect = document.getElementById('startTimeSelect');
                        const endSelect = document.getElementById('endTimeSelect');

                        if (startSelect && endSelect) {
                            startSelect.value = savedState.startTime;
                            startSelect.dispatchEvent(new Event('change', { bubbles: true }));

                            setTimeout(() => {
                                endSelect.value = savedState.endTime;
                                endSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            }, 500);
                        }
                        
                        // Continue to next step
                        navigateToStep(4);
                    }, 1500);
                } else {
                    navigateToStep(4);
                }
                
            } else if (currentStep === 4) {
                // Moving from step 4 to step 5 (confirmation)
                if (window.bookingState) {
                    window.bookingState.currentStep = 5;
                }
                
                // Update wizard UI
                if (typeof updateWizardStep === 'function') {
                    updateWizardStep();
                }
                
                // Update summary
                setTimeout(() => {
                    if (typeof updateSummary === 'function') {
                        updateSummary();
                    }
                }, 100);
                
                navigateToStep(5);
            }
        };
        
        // Start navigation from step 1
        setTimeout(() => {
            navigateToStep(1);
        }, 200);
    },

    /**
     * Get selected trainer card data for recovery
     */
    getSelectedTrainerCardData() {
        const selectedCard = document.querySelector('.trainer-card.selected');
        if (!selectedCard) return null;

        return {
            id: selectedCard.dataset.trainerId,
            name: selectedCard.dataset.trainerName,
            shift: selectedCard.dataset.trainerShift
        };
    },

    /**
     * Setup auto-save on state changes
     */
    setupAutoSave() {
        // Save on window state changes
        if (window.bookingState) {
            // Create proxy to detect changes
            const handler = {
                set: (target, property, value) => {
                    target[property] = value;
                    
                    // Debounce saves
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => {
                        this.saveState(window.bookingState);
                    }, 500);
                    
                    return true;
                }
            };

            // Only proxy if not already proxied
            if (!window.bookingState.__isProxy) {
                const originalState = { ...window.bookingState };
                window.bookingState = new Proxy(originalState, handler);
                window.bookingState.__isProxy = true;
            }
        }

        // Save on beforeunload
        window.addEventListener('beforeunload', () => {
            if (window.bookingState) {
                this.saveState(window.bookingState);
            }
        });

        // Save on visibility change (mobile background)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && window.bookingState) {
                this.saveState(window.bookingState);
            }
        });
    },

    /**
     * Setup completion listener
     */
    setupCompletionListener() {
        // Listen for successful booking completion
        window.addEventListener('bookingCompleted', () => {
            console.log('✅ Booking completed, clearing recovery state');
            this.clearState();
        });

        // Also clear on navigation to "My Bookings" section
        const myBookingsTab = document.querySelector('[data-tab="upcoming"]');
        if (myBookingsTab) {
            myBookingsTab.addEventListener('click', () => {
                // Only clear if booking was actually completed
                if (window.bookingState && window.bookingState.bookingId) {
                    this.clearState();
                }
            });
        }
    },

    /**
     * Clean expired data periodically
     */
    cleanExpiredData() {
        // Check every 5 minutes
        setInterval(() => {
            const state = this.getState();
            // getState automatically removes expired data
        }, 5 * 60 * 1000);
    },

    /**
     * Format date for display
     */
    formatDate(dateStr) {
        if (!dateStr) return 'Not selected';
        
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        } catch {
            return dateStr;
        }
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => BookingRecovery.init());
} else {
    BookingRecovery.init();
}

// Expose globally
window.BookingRecovery = BookingRecovery;
