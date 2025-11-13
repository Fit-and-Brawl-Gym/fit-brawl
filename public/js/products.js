(function() {
    "use strict";

    const state = {
        allProducts: [],
        renderedProducts: [],
        currentPage: 1,
        itemsPerPage: 12
    };

    const ui = {
        grid: document.getElementById('grid'),
        searchBox: document.getElementById('q'),
        statusFilter: document.getElementById('statusFilter'),
        categoryChips: document.querySelectorAll('.category-chip, .cat, [data-cat]'),
        pagination: null // Will be created dynamically
    };

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, match => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[match]));
    }

    function getStatusBadge(status) {
        const s = String(status || '').toLowerCase();
        // For members: Only show IN STOCK or UNAVAILABLE
        // UNAVAILABLE = low stock or out of stock
        if (s === 'in' || s.includes('in stock')) {
            return '<span class="badge in">IN STOCK</span>';
        }
        // Low stock and out of stock both show as UNAVAILABLE
        return '<span class="badge out">UNAVAILABLE</span>';
    }

    function createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'card';
        
        // Use image if available, otherwise use emoji
        let imageContent;
        if (product.image) {
            imageContent = `<img src="${escapeHtml(product.image)}"
                     alt="${escapeHtml(product.name)}"
                     loading="lazy"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='${escapeHtml(product.emoji || '📦')}';">`;
        } else {
            imageContent = `<span class="product-emoji">${escapeHtml(product.emoji || '📦')}</span>`;
        }
        
        card.innerHTML = `
            <div class="product-image">
                ${imageContent}
            </div>
            <h4>${escapeHtml(product.name)}</h4>
            <div>${getStatusBadge(product.status)}</div>
        `;
        return card;
    }

    function renderProductGrid(products) {
        if (!ui.grid) return;

        state.renderedProducts = products;

        if (!products || products.length === 0) {
            ui.grid.innerHTML = '<div class="no-products">No products found</div>';
            if (ui.pagination) ui.pagination.style.display = 'none';
            return;
        }

        // Calculate pagination
        const totalPages = Math.ceil(products.length / state.itemsPerPage);
        const startIndex = (state.currentPage - 1) * state.itemsPerPage;
        const endIndex = startIndex + state.itemsPerPage;
        const paginatedProducts = products.slice(startIndex, endIndex);

        // Render products
        ui.grid.innerHTML = '';
        const fragment = document.createDocumentFragment();
        paginatedProducts.forEach(product => {
            fragment.appendChild(createProductCard(product));
        });
        ui.grid.appendChild(fragment);

        // Render pagination
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        if (!ui.pagination) {
            ui.pagination = document.createElement('div');
            ui.pagination.className = 'pagination';
            ui.grid.parentNode.appendChild(ui.pagination);
        }

        if (totalPages <= 1) {
            ui.pagination.style.display = 'none';
            return;
        }

        ui.pagination.style.display = 'flex';
        ui.pagination.innerHTML = createPaginationHTML(totalPages);
        attachPaginationEvents(ui.pagination);
    }

    function createPaginationHTML(totalPages) {
        let html = '';

        // Previous button
        html += `<button class="pagination-btn prev-btn" ${state.currentPage === 1 ? 'disabled' : ''}>&laquo; Previous</button>`;

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            html += `<button class="pagination-btn page-number" data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span class="pagination-dots">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="pagination-btn page-number ${i === state.currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="pagination-dots">...</span>`;
            }
            html += `<button class="pagination-btn page-number" data-page="${totalPages}">${totalPages}</button>`;
        }

        // Next button
        html += `<button class="pagination-btn next-btn" ${state.currentPage === totalPages ? 'disabled' : ''}>Next &raquo;</button>`;

        return html;
    }

    function attachPaginationEvents(container) {
        // Previous button
        const prevBtn = container.querySelector('.prev-btn');
        if (prevBtn) {
            prevBtn.onclick = () => {
                if (state.currentPage > 1) {
                    state.currentPage--;
                    renderProductGrid(state.renderedProducts);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };
        }

        // Next button
        const nextBtn = container.querySelector('.next-btn');
        if (nextBtn) {
            nextBtn.onclick = () => {
                const totalPages = Math.ceil(state.renderedProducts.length / state.itemsPerPage);
                if (state.currentPage < totalPages) {
                    state.currentPage++;
                    renderProductGrid(state.renderedProducts);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };
        }

        // Page number buttons
        const pageButtons = container.querySelectorAll('.page-number');
        pageButtons.forEach(btn => {
            btn.onclick = () => {
                const pageNum = parseInt(btn.dataset.page);
                state.currentPage = pageNum;
                renderProductGrid(state.renderedProducts);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        });
    }

    function applyFilters() {
        const searchTerm = (ui.searchBox.value || '').trim().toLowerCase();
        const status = ui.statusFilter.value || 'all';

        const filtered = state.allProducts.filter(p => {
            const nameMatch = (p.name || '').toLowerCase().includes(searchTerm);
            const categoryMatch = (p.cat || '').toLowerCase().includes(searchTerm);
            const searchMatch = searchTerm ? (nameMatch || categoryMatch) : true;

            if (!searchMatch) return false;

            if (status !== 'all') {
                const s = (p.status || '').toLowerCase();
                if (status === 'in') {
                    return s === 'in' || s.includes('in stock');
                } else if (status === 'unavailable') {
                    // Unavailable = low stock OR out of stock
                    return s === 'low' || s.includes('low') || s === 'out' || s.includes('out');
                }
            }
            return true;
        });

        // Reset to page 1 when filters change
        state.currentPage = 1;
        renderProductGrid(filtered);
    }

    function handleCategoryClick(event) {
        const chip = event.currentTarget;
        const category = chip.dataset.cat;

        if (ui.searchBox.value === category) {
            ui.searchBox.value = '';
            chip.classList.remove('active');
        } else {
            ui.searchBox.value = category;
            ui.categoryChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
        }
        applyFilters();
    }

    function debounce(fn, wait = 200){
        let t = null;
        return function(...args){
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function attachEventListeners() {
        if (ui.searchBox) {
            ui.searchBox.addEventListener('input', debounce(applyFilters, 180));
        }
        if (ui.statusFilter) {
            ui.statusFilter.addEventListener('change', applyFilters);
        }
        ui.categoryChips.forEach(chip => {
            chip.addEventListener('click', handleCategoryClick);
        });
    }

    async function fetchProducts() {
    const apiUrl = "products.php?api=true";
    try {
        const response = await fetch(apiUrl, { cache: 'no-store' });
        if (!response.ok) throw new Error(`Network error: ${response.status}`);

        const data = await response.json();



        if (!data.success || !Array.isArray(data.data)) {
            throw new Error("Invalid API format — expected { success:true, data:[...] }");
        }


        return data.data.map(item => ({
            id: item.id || item.product_id || null,
            name: item.name || item.product_name || "Unnamed Product",
            cat: (item.cat || item.category || "uncategorized").toLowerCase(),
            status: (item.status || "").toLowerCase(),
            image: item.image || item.image_path || ""
        }));
    } catch (error) {
        console.error("Could not load products from API, using fallback data.", error);
        return [];
    }
}



    async function init() {
        attachEventListeners();
        state.allProducts = await fetchProducts();
        renderProductGrid(state.allProducts);
    }

    document.addEventListener('DOMContentLoaded', init);

    // Back to top button functionality
    const backToTopBtn = document.querySelector('.back-to-top');

    if (backToTopBtn) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        // Scroll to top when clicked
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

})();
