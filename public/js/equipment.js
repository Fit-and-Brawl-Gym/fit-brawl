const container = document.getElementById('equipment-container');
const searchInput = document.getElementById('equipmentSearch');
const statusFilter = document.getElementById('statusFilter');
const categoryChips = document.getElementById('category-filters');

let EQUIPMENT_DATA = [];
let FILTERED_DATA = [];
let activeCategory = null; // no filter by default
let currentPage = 1;
const itemsPerPage = 12;
let paginationContainer = null;

function capitalize(s) {
  return s && s.length ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

function renderEquipment(items) {
  FILTERED_DATA = items;

  if (!items || items.length === 0) {
    container.innerHTML = '<div class="no-equipment">No equipment found</div>';
    if (paginationContainer) paginationContainer.style.display = 'none';
    return;
  }

  // Calculate pagination
  const totalPages = Math.ceil(items.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedItems = items.slice(startIndex, endIndex);

  container.innerHTML = paginatedItems.map(item => {
    const categories = item.category ? item.category.split(',').map(c => c.trim()) : [];
    const statusClass = (item.status || '').toLowerCase().replace(/\s+/g, '-');
    const isMaintenance = item.status === 'Maintenance';

    // Use image if available, otherwise use emoji
    let imageContent;
    if (item.image_path && item.image_path.trim() !== '') {
      imageContent = `<img src="${item.image_path}"
               alt="${escapeHtml(item.name)}"
               class="equipment-image"
               loading="lazy"
               onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\\'equipment-emoji\\'>${escapeHtml(item.emoji || '🥊')}</span>';">`;
    } else if (item.emoji) {
      imageContent = `<span class="equipment-emoji">${escapeHtml(item.emoji)}</span>`;
    } else {
      imageContent = `<span class="equipment-emoji">🥊</span>`;
    }

    // Format maintenance dates if in maintenance
    let maintenanceInfo = '';
    if (isMaintenance && item.maintenance_start_date && item.maintenance_end_date) {
      const startDate = new Date(item.maintenance_start_date);
      const endDate = new Date(item.maintenance_end_date);
      const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

      maintenanceInfo = `
        <div class="maintenance-banner">
          <div class="maintenance-icon">🔧</div>
          <div class="maintenance-details">
            <div class="maintenance-title">Under Maintenance</div>
            <div class="maintenance-dates">
              <i class="fas fa-calendar"></i>
              ${startFormatted} - ${endFormatted}
            </div>
            ${item.maintenance_reason ? `<div class="maintenance-reason">${escapeHtml(item.maintenance_reason)}</div>` : ''}
          </div>
        </div>
      `;
    }

    return `
      <div class="equipment-card ${isMaintenance ? 'maintenance-mode' : ''}" data-id="${item.id}" data-status="${statusClass}" data-category="${categories.join(',')}">
        <div class="equipment-image-container">
          ${imageContent}
        </div>

        <div class="equipment-content">
          <div class="equipment-header">
            <div class="equipment-info">
              <h3>${escapeHtml(item.name)}</h3>
              <span class="equipment-category">${escapeHtml(categories.join(', '))}</span>
            </div>

            <div class="equipment-status">
              <span class="status-dot ${statusClass}"></span>
              <span class="status-text">${escapeHtml(item.status)}</span>
            </div>
          </div>

          ${maintenanceInfo}

          <div class="equipment-desc">${escapeHtml(item.description || 'No description available.')}</div>
        </div>
      </div>
    `;
  }).join('');

  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  if (!paginationContainer) {
    paginationContainer = document.createElement('div');
    paginationContainer.className = 'pagination';
    container.parentNode.appendChild(paginationContainer);
  }

  if (totalPages <= 1) {
    paginationContainer.style.display = 'none';
    return;
  }

  paginationContainer.style.display = 'flex';
  paginationContainer.innerHTML = createPaginationHTML(totalPages);
  attachPaginationEvents(paginationContainer);
}

function createPaginationHTML(totalPages) {
  let html = '';

  // Previous button
  html += `<button class="pagination-btn prev-btn" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Previous</button>`;

  // Page numbers
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
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
    html += `<button class="pagination-btn page-number ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      html += `<span class="pagination-dots">...</span>`;
    }
    html += `<button class="pagination-btn page-number" data-page="${totalPages}">${totalPages}</button>`;
  }

  // Next button
  html += `<button class="pagination-btn next-btn" ${currentPage === totalPages ? 'disabled' : ''}>Next &raquo;</button>`;

  return html;
}

function attachPaginationEvents(paginationContainer) {
  // Previous button
  const prevBtn = paginationContainer.querySelector('.prev-btn');
  if (prevBtn) {
    prevBtn.onclick = () => {
      if (currentPage > 1) {
        currentPage--;
        renderEquipment(FILTERED_DATA);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
  }

  // Next button
  const nextBtn = paginationContainer.querySelector('.next-btn');
  if (nextBtn) {
    nextBtn.onclick = () => {
      const totalPages = Math.ceil(FILTERED_DATA.length / itemsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderEquipment(FILTERED_DATA);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
  }

  // Page number buttons
  const pageButtons = paginationContainer.querySelectorAll('.page-number');
  pageButtons.forEach(btn => {
    btn.onclick = () => {
      const pageNum = parseInt(btn.dataset.page);
      currentPage = pageNum;
      renderEquipment(FILTERED_DATA);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
  });
}

function escapeHtml(unsafe) {
  return String(unsafe)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function applyFilters() {
  const q = (searchInput.value || '').toLowerCase().trim();
  const status = statusFilter.value;

  const filtered = EQUIPMENT_DATA.filter(item => {
    const normalize = str => str.trim().toLowerCase().replace(/\s+/g, ' ');
    const categories = item.category ? item.category.split(',').map(c => normalize(c)) : [];

    if (activeCategory) {
      // Normalize chip category and get first word
      const chipCategory = normalize(activeCategory);
      const chipFirstWord = chipCategory.split(' ')[0];
      const hasCategory = categories.some(cat => {
        const catFirstWord = cat.split(' ')[0];
        return catFirstWord === chipFirstWord;
      });
      if (!hasCategory) return false;
    }

    if (status !== 'all' && status !== '') {
      if (!item.status || item.status.toLowerCase() !== status.toLowerCase()) {
        return false;
      }
    }

    if (q) {
      const hay = (item.name + ' ' + (item.description || '') + ' ' + (item.category || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }

    return true;
  });

  // Reset to page 1 when filters change
  currentPage = 1;
  renderEquipment(filtered);
}

// Load data from API
fetch('equipment.php?api=true')
  .then(r => r.json())
  .then(res => {
    if (!res.success) throw new Error(res.error || 'Failed to load equipment');
    EQUIPMENT_DATA = res.data.map(d => ({
      id: d.id,
      name: d.name,
      category: d.category,
      status: d.status,
      description: d.description,
      image_path: d.image_path || null
    }));
    applyFilters();
  })
  .catch(err => console.error('Error loading equipment:', err));

// Event listeners
searchInput.addEventListener('input', () => applyFilters());
statusFilter.addEventListener('change', () => applyFilters());

// Category chip handling
categoryChips.querySelectorAll('.category-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    const wasActive = chip.classList.contains('active');
    categoryChips.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
    if (wasActive) {
      activeCategory = null;
    } else {
      chip.classList.add('active');
      activeCategory = chip.dataset.category;
    }
    applyFilters();
  });
});

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
