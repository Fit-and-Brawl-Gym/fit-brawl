(function() {
    "use strict";

    const state = {
        allProducts: [],
        renderedProducts: []
    };

    const ui = {
        grid: document.getElementById('grid'),
        searchBox: document.getElementById('q'),
        statusFilter: document.getElementById('statusFilter'),
        categoryChips: document.querySelectorAll('.category-chip, .cat, [data-cat]')
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
        if (s === 'in' || s.includes('in stock')) {
            return '<span class="badge in">IN STOCK</span>';
        }
        if (s === 'low' || s.includes('low')) {
            return '<span class="badge low">LOW ON STOCK</span>';
        }
        return '<span class="badge out">OUT OF STOCK</span>';
    }

    function createProductCard(product) {
        // Use specific product image if available, otherwise generate from name
        let imageSrc = product.image;

        if (!imageSrc) {
            // Generate image path from product name (e.g., "Whey Protein Powder" -> "whey-protein-powder.jpg")
            const imageName = product.name.toLowerCase().replace(/\s+/g, '-');
            imageSrc = `../../images/${imageName}.jpg`;
        }
 
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <div class="product-image">
                <img src="${escapeHtml(imageSrc)}"
                     alt="${escapeHtml(product.name)}"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='../../images/default-product.svg';">
            </div>
            <h4>${escapeHtml(product.name)}</h4>
            <div>${getStatusBadge(product.status)}</div>
        `;
        return card;
    }

    function renderProductGrid(products) {
        if (!ui.grid) return;

        ui.grid.innerHTML = '';
        state.renderedProducts = products;

        if (!products || products.length === 0) {
            ui.grid.innerHTML = '<div class="no-products">No products found</div>';
            return;
        }

        const fragment = document.createDocumentFragment();
        products.forEach(product => {
            fragment.appendChild(createProductCard(product));
        });
        ui.grid.appendChild(fragment);
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
                return (status === 'in' && (s === 'in' || s.includes('in'))) ||
                       (status === 'low' && (s === 'low' || s.includes('low'))) ||
                       (status === 'out' && (s === 'out' || s.includes('out')));
            }
            return true;
        });

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
        const apiUrl = `${window.location.pathname}?api=true`;
        try {
            const response = await fetch(apiUrl, { cache: 'no-store' });
            if (!response.ok) throw new Error('Network response failed');

            const data = await response.json();
            return data.map(item => ({
                id: item.id || item.product_id || null,
                name: item.name || item.product_name || 'Unnamed Product',
                cat: (item.cat || item.category || (item.category_name && item.category_name.toLowerCase())) || 'uncategorized',
                status: (item.status || item.stock || '').toString().toLowerCase()
            }));
        } catch (error) {
            console.warn('Could not load products from API, using fallback data.', error);
            return [
                { id: 1, name: "Whey Protein Powder", cat: "supplements", status: "in", image: "../../images/whey-protein-powder.jpg" },
                { id: 2, name: "Pre-Workout Supplement", cat: "supplements", status: "low", image: "../../images/workout-supplement.jpg" },
                { id: 3, name: "Bottled Water", cat: "hydration", status: "in", image: "../../images/bottled-water.jpg" },
                { id: 4, name: "Recovery Bar", cat: "snacks", status: "in", image: "../../images/recovery-bar.jpg" },
                { id: 5, name: "Muscle Roller", cat: "equipment", status: "in", image: "../../images/muscle-roller.jpg" },
                { id: 6, name: "Ice Pack", cat: "equipment", status: "low", image: "../../images/ice-pack.jpg" },
                { id: 7, name: "Resistance Bands", cat: "equipment", status: "out", image: "../../images/resistance-bands.jpg" },
                { id: 8, name: "Mouth Guards", cat: "boxing", status: "in", image: "../../images/mouth-guards.jpg" }
            ];
        }
    }

    async function init() {
        attachEventListeners();
        state.allProducts = await fetchProducts();
        renderProductGrid(state.allProducts);
    }

    document.addEventListener('DOMContentLoaded', init);

})();
