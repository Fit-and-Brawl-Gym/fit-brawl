document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const plansViewport = document.querySelector('.plans-viewport');
    const planCards = document.querySelectorAll('.plan-card');
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const memberTable = document.getElementById('memberTable');
    const nonMemberTable = document.getElementById('nonMemberTable');

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

    let currentIndex = 1; // Start with Gladiator in the middle
    const totalPlans = planCards.length;

    function updateCarousel() {
        // Remove all position classes from all cards
        planCards.forEach((card) => {
            card.classList.remove('center', 'left', 'right', 'hidden', 'featured-size');
        });

        // Calculate which 3 plans to show
        const leftIndex = currentIndex - 1;
        const centerIndex = currentIndex;
        const rightIndex = currentIndex + 1;

        // Position cards
        planCards.forEach((card, index) => {
            if (index === leftIndex && leftIndex >= 0) {
                card.classList.add('left');
            } else if (index === centerIndex) {
                card.classList.add('center');

                // Add featured-size class only to center card if it's not Gladiator
                if (!card.classList.contains('gladiator-plan')) {
                    card.classList.add('featured-size');
                }
            } else if (index === rightIndex && rightIndex < totalPlans) {
                card.classList.add('right');
            } else {
                card.classList.add('hidden');
            }
        });

        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === totalPlans - 1;

        if (currentIndex === 0) {
            prevBtn.style.opacity = '0.5';
            prevBtn.style.cursor = 'not-allowed';
        } else {
            prevBtn.style.opacity = '1';
            prevBtn.style.cursor = 'pointer';
        }

        if (currentIndex === totalPlans - 1) {
            nextBtn.style.opacity = '0.5';
            nextBtn.style.cursor = 'not-allowed';
        } else {
            nextBtn.style.opacity = '1';
            nextBtn.style.cursor = 'pointer';
        }
    }

    function nextSlide() {
        if (currentIndex < totalPlans - 1) {
            currentIndex++;
            updateCarousel();
        }
    }

    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }

    // Toggle button functionality
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
        } else {
            memberTable.classList.remove('active');
            nonMemberTable.classList.add('active');
        }
    }

    // Modal functionality
    function openModal(price, service, benefits) {
        modalPrice.textContent = price;
        modalService.textContent = service;
        modalBenefits.textContent = benefits;
        serviceModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        serviceModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Add click handlers to table rows
    function addTableRowHandlers() {
        const tables = document.querySelectorAll('.pricing-table tbody');

        tables.forEach(table => {
            const rows = table.querySelectorAll('tr');

            rows.forEach(row => {
                row.style.cursor = 'pointer';

                row.addEventListener('click', function() {
                    const cells = this.querySelectorAll('td');
                    const price = cells[0].textContent.trim();
                    const service = cells[1].textContent.trim();
                    const benefits = cells[2].textContent.trim();

                    openModal(price, service, benefits);
                });
            });
        });
    }

    // Modal action handlers
    purchaseBtn.addEventListener('click', function() {
        const service = modalService.textContent;
        const price = modalPrice.textContent;
        alert(`Proceeding to purchase: ${service} for ${price}`);
        // TODO: Redirect to payment page or add to cart
        closeModal();
    });

    inquireBtn.addEventListener('click', function() {
        const service = modalService.textContent;
        // Redirect to contact page with service info
        window.location.href = `contact.php?service=${encodeURIComponent(service)}`;
    });

    cancelBtn.addEventListener('click', closeModal);
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);

    // Event listeners for toggle buttons
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tableType = this.getAttribute('data-table');
            switchPricingTable(tableType);
        });
    });

    // Event listeners for carousel
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        nextSlide();
    });

    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        prevSlide();
    });

    // Initialize
    updateCarousel();
    addTableRowHandlers();

    // Optional: Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (serviceModal.classList.contains('active')) {
            if (e.key === 'Escape') {
                closeModal();
            }
        } else {
            if (e.key === 'ArrowLeft') {
                prevSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        }
    });
});
