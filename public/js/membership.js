document.addEventListener('DOMContentLoaded', function () {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const plansViewport = document.querySelector('.plans-viewport');
    const planCards = document.querySelectorAll('.plan-card');
    // Toggle functionality removed - only member table is shown now
    // const toggleBtns = document.querySelectorAll('.toggle-btn');
    const memberTable = document.getElementById('memberTable');
    // const nonMemberTable = document.getElementById('nonMemberTable');

    // Modal elements
    const serviceModal = document.getElementById('serviceModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalClose = document.getElementById('modalClose');
    const modalTitle = document.getElementById('modalTitle');
    const modalPrice = document.getElementById('modalPrice');
    const modalService = document.getElementById('modalService');
    const modalBenefits = document.getElementById('modalBenefits');
    const purchaseBtn = document.getElementById('purchaseBtn');
    const inquireBtn = document.getElementById('inquireBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    let currentIndex = 2; // Start at Gladiator card (index 2)
    const totalPlans = planCards.length;

    // Check if we're on a smaller/taller screen (portrait mobile)
    function isStackedView() {
        return window.innerWidth <= 768 && window.innerHeight >= 600;
    }

    // Update card positions for overlapping carousel effect
    function updateCarousel() {
        // Skip carousel logic if in stacked view
        if (isStackedView()) {
            // Reset all cards to default positioning for stacked view
            planCards.forEach((card) => {
                card.removeAttribute('data-position');
                card.style.position = 'relative';
                card.style.transform = 'none';
                card.style.opacity = '1';
            });
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
            return;
        }

        // Show carousel buttons
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';

        // Update each card's position relative to current index
        planCards.forEach((card, index) => {
            const position = index - currentIndex;
            card.setAttribute('data-position', position);
        });

        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= totalPlans - 1;

        if (currentIndex === 0) {
            prevBtn.style.opacity = '0.5';
            prevBtn.style.cursor = 'not-allowed';
        } else {
            prevBtn.style.opacity = '1';
            prevBtn.style.cursor = 'pointer';
        }

        if (currentIndex >= totalPlans - 1) {
            nextBtn.style.opacity = '0.5';
            nextBtn.style.cursor = 'not-allowed';
        } else {
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor = 'pointer';
        }
    }

    function nextSlide() {
        if (isStackedView()) return; // Disable in stacked view
        if (currentIndex < totalPlans - 1) {
            currentIndex++;
            updateCarousel();
        }
    }

    function prevSlide() {
        if (isStackedView()) return; // Disable in stacked view
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }

    // Toggle button functionality - REMOVED
    // Non-member table moved to homepage, only member table remains
    /*
    function switchPricingTable(tableType) {
        // Remove active class from all buttons
        toggleBtns.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked button
        const activeBtn = document.querySelector(`[data-table="${tableType}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');

            // Update button text with hyphen formatting for non-member
            if (tableType === 'non-member') {
                activeBtn.innerHTML = 'FOR NON<span class="toggle-hyphen">-</span>MEMBERS';
            }
        }

        // Reset member button text
        const memberBtn = document.querySelector('[data-table="member"]');
        if (memberBtn && tableType !== 'member') {
            memberBtn.textContent = 'FOR MEMBERS';
        }

        // Show/hide appropriate table
        if (tableType === 'member') {
            memberTable.classList.add('active');
            nonMemberTable.classList.remove('active');
            // Hide signup notice for members table
            const signupNotice = document.getElementById('signupNotice');
            if (signupNotice) {
                signupNotice.classList.remove('show');
            }
        } else {
            memberTable.classList.remove('active');
            nonMemberTable.classList.add('active');
            // Show signup notice for non-members table only if user is NOT logged in
            const signupNotice = document.getElementById('signupNotice');
            if (signupNotice) {
                const isLoggedIn = signupNotice.getAttribute('data-logged-in') === 'true';
                if (!isLoggedIn) {
                    signupNotice.classList.add('show');
                } else {
                    signupNotice.classList.remove('show');
                }
            }
        }
    }
    */


    // Modal functionality
    function openModal(price, service, benefits, tableType) {
        modalPrice.textContent = price;
        modalService.textContent = service;
        modalBenefits.textContent = benefits;
        serviceModal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Store table type for later use
        serviceModal.dataset.tableType = tableType;
    }

    function closeModal() {
        serviceModal.classList.remove('active');
        document.body.style.overflow = '';
        delete serviceModal.dataset.tableType;
    }

    // Add click handlers to service cards
    function addServiceCardHandlers() {
        const serviceCards = document.querySelectorAll('.service-card');
        const memberTable = document.getElementById('memberTable');
        const hasMembership = memberTable && memberTable.getAttribute('data-has-membership') === 'true';

        serviceCards.forEach(card => {
            // Skip disabled cards
            if (card.classList.contains('disabled') || hasMembership) {
                return;
            }

            // Handle click on the entire card
            card.addEventListener('click', function (e) {
                // Don't open modal if the select button was clicked
                if (e.target.classList.contains('service-select-btn')) {
                    return;
                }

                const price = this.getAttribute('data-price');
                const service = this.getAttribute('data-service');
                const benefits = this.getAttribute('data-benefits');

                openModal(price, service, benefits, 'member');
            });

            // Handle select button click
            const selectBtn = card.querySelector('.service-select-btn');
            if (selectBtn && !selectBtn.disabled) {
                selectBtn.addEventListener('click', function (e) {
                    e.stopPropagation();

                    const price = card.getAttribute('data-price');
                    const service = card.getAttribute('data-service');
                    const benefits = card.getAttribute('data-benefits');

                    openModal(price, service, benefits, 'member');
                });
            }
        });
    }

    // Add click handlers to table rows (legacy support)
    function addTableRowHandlers() {
        const tables = document.querySelectorAll('.pricing-table tbody');

        tables.forEach((table, index) => {
            const rows = table.querySelectorAll('tr');

            const tableType = index === 0 ? 'member' : 'non-member';

            rows.forEach(row => {
                row.style.cursor = 'pointer';

                row.addEventListener('click', function () {
                    const cells = this.querySelectorAll('td');
                    const price = cells[0].textContent.trim();
                    const service = cells[1].textContent.trim();
                    const benefits = cells[2].textContent.trim();

                    openModal(price, service, benefits, tableType);
                });
            });
        });
    }

    // Add click handlers for "Select Plan" buttons
    function addPlanSelectionHandlers() {
        const selectPlanButtons = document.querySelectorAll('.select-btn');

        selectPlanButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const planCard = this.closest('.plan-card');
                if (!planCard) {
                    console.error('Plan card not found for clicked button.');
                    return;
                }

                let planType = planCard.getAttribute('data-plan');
                let category = planCard.getAttribute('data-category') || 'regular';


                const variant = this.getAttribute('data-variant');
                if (variant) {

                    planType = `${planType}-${variant}`;
                }


                window.location.href = `transaction.php?plan=${encodeURIComponent(planType)}&category=${encodeURIComponent(category)}&billing=monthly`;
            });
        });
    }


    // Run this after your cards are loaded
    document.addEventListener('DOMContentLoaded', addPlanSelectionHandlers);


    // Service name to key mapping
    const serviceMapping = {
        'Day Pass: Gym Access': 'daypass-gym',
        'Day Pass: Student Access': 'daypass-gym-student',
        'Training: Boxing': 'training-boxing',
        'Training: Muay Thai': 'training-muaythai',
        'Training: MMA': 'training-mma'
    };

    // Update the purchase button click handler
    purchaseBtn.addEventListener('click', function () {
        const serviceName = modalService.textContent;
        const serviceKey = serviceMapping[serviceName];
        // Only member table exists now
        const currentTable = 'member';

        if (serviceKey) {
            window.location.href = `transaction_service.php?service=${serviceKey}&type=${currentTable}`;
        } else {
            console.error('Unknown service:', serviceName);
            alert('Service not found. Please try again.');
        }
    });

    inquireBtn.addEventListener('click', function () {
        const service = modalService.textContent;
        // Redirect to contact page with service info
        window.location.href = `contact.php?service=${encodeURIComponent(service)}`;
    });

    cancelBtn.addEventListener('click', closeModal);
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);

    // Toggle button event listeners removed - only member table exists
    // toggleBtns.forEach(btn => {
    //     btn.addEventListener('click', function() {
    //         const tableType = this.getAttribute('data-table');
    //         switchPricingTable(tableType);
    //     });
    // });

    // Event listeners for carousel
    nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        nextSlide();
    });

    prevBtn.addEventListener('click', function (e) {
        e.preventDefault();
        prevSlide();
    });

    // Initialize
    updateCarousel();
    addServiceCardHandlers();
    addTableRowHandlers(); // Keep for backward compatibility
    addPlanSelectionHandlers();
    initComparisonTable();

    // Handle window resize for responsive behavior
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            updateCarousel();
        }, 250);
    });

    // Optional: Add keyboard navigation
    document.addEventListener('keydown', function (e) {
        if (serviceModal.classList.contains('active')) {
            if (e.key === 'Escape') {
                closeModal();
            }
        } else {
            // Only allow keyboard navigation if not in stacked view
            if (!isStackedView()) {
                if (e.key === 'ArrowLeft') {
                    prevSlide();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                }
            }
        }
    });

    // ===================================
    // COMPARISON TABLE FUNCTIONALITY
    // ===================================
    function initComparisonTable() {
        const toggleBtn = document.getElementById('comparisonToggleBtn');
        const tableContainer = document.getElementById('comparisonTableContainer');

        if (!toggleBtn || !tableContainer) {
            console.log('Comparison table elements not found');
            return;
        }

        const toggleText = toggleBtn.querySelector('.toggle-text');
        const toggleIcon = toggleBtn.querySelector('.toggle-icon');

        // Toggle table visibility
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const isActive = tableContainer.classList.contains('active');
            console.log('Click! isActive:', isActive);

            if (isActive) {
                // Close table
                console.log('Removing active class...');
                tableContainer.classList.remove('active');
                toggleBtn.classList.remove('active');
                if (toggleText) toggleText.textContent = 'Compare Plans';
                console.log('Active removed, classes now:', tableContainer.className);
            } else {
                // Open table
                console.log('Adding active class...');
                tableContainer.classList.add('active');
                toggleBtn.classList.add('active');
                if (toggleText) toggleText.textContent = 'Hide Comparison';
                console.log('Active added, classes now:', tableContainer.className);

                // Debug computed styles
                const computedStyle = window.getComputedStyle(tableContainer);
                console.log('Computed display:', computedStyle.display);
                console.log('Computed background:', computedStyle.background);
                console.log('Computed padding:', computedStyle.padding);
                console.log('Computed visibility:', computedStyle.visibility);
                console.log('Element position:', tableContainer.getBoundingClientRect());

                // Smooth scroll to table
                setTimeout(() => {
                    tableContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 300);
            }
        });

        // Add click handlers to comparison select buttons
        const comparisonSelectBtns = document.querySelectorAll('.comparison-select-btn');

        comparisonSelectBtns.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const planType = this.getAttribute('data-plan');
                const category = this.getAttribute('data-category');

                if (planType && category) {
                    window.location.href = `transaction.php?plan=${encodeURIComponent(planType)}&category=${encodeURIComponent(category)}&billing=monthly`;
                }
            });
        });

        console.log('Comparison table initialized successfully');
    }
});
