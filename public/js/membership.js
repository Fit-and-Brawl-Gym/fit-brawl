document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const slides = document.querySelectorAll('.plans-slide');
    const pricingHeader = document.getElementById('pricingHeader');
    const memberTable = document.getElementById('memberTable');
    const nonMemberTable = document.getElementById('nonMemberTable');

    let currentSlide = 0;

    function showSlide(index) {
        // Hide all slides
        slides.forEach(slide => slide.classList.remove('active'));

        // Show current slide
        slides[index].classList.add('active');

        // Update pricing table based on category
        const category = slides[index].getAttribute('data-category');

        if (category === 'member') {
            pricingHeader.querySelector('h2').textContent = 'FOR MEMBERS';
            memberTable.classList.add('active');
            nonMemberTable.classList.remove('active');
        } else {
            pricingHeader.querySelector('h2').innerHTML = 'FOR NON<span class="hyphen">-</span>MEMBERS';
            memberTable.classList.remove('active');
            nonMemberTable.classList.add('active');
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }

    // Event listeners
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    // Initialize
    showSlide(0);

    // Optional: Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            prevSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        }
    });
});
