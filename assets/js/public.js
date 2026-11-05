// assets/js/public.js - Client Script Khusus Publik (Katalog Pakan Jadi BRMP DIY)

let currentPublicDetailItem = null;

document.addEventListener('DOMContentLoaded', () => {
    initPublicFilters();
});

// Modal Helpers
function openModal(modalId) {
    const dialog = document.getElementById(modalId);
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
}

function closeModal(modalId) {
    const dialog = document.getElementById(modalId);
    if (dialog && typeof dialog.close === 'function') {
        dialog.close();
    }
}

// Close dialog on backdrop click
document.querySelectorAll('dialog').forEach(dialog => {
    dialog.addEventListener('click', (e) => {
        const rect = dialog.getBoundingClientRect();
        const isInDialog = (
            rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
            rect.left <= e.clientX && e.clientX <= rect.left + rect.width
        );
        if (!isInDialog) {
            dialog.close();
        }
    });
});

// 1. Live Filter & Search Katalog Pakan Jadi
function initPublicFilters() {
    const searchInput = document.getElementById('search-pakan');
    const statusSelect = document.getElementById('status-pakan');
    const categoryPills = document.querySelectorAll('.cat-pill-pakan');
    const cards = document.querySelectorAll('.item-card-pakan');
    const noResults = document.getElementById('no-results-pakan');

    let currentCategory = '';

    function applyFilter() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const statusVal = statusSelect ? statusSelect.value : 'all';
        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const code = (card.getAttribute('data-code') || '').toLowerCase();
            const cat = card.getAttribute('data-category') || '';
            const isCritical = card.getAttribute('data-critical') === '1';

            let matchQuery = !query || name.includes(query) || code.includes(query) || cat.toLowerCase().includes(query);
            let matchCategory = !currentCategory || cat === currentCategory;
            let matchStatus = true;

            if (statusVal === 'safe') matchStatus = !isCritical;
            if (statusVal === 'critical') matchStatus = isCritical;

            if (matchQuery && matchCategory && matchStatus) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (noResults) {
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (statusSelect) statusSelect.addEventListener('change', applyFilter);

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => {
                p.classList.remove('bg-emerald-600', 'text-white', 'shadow-xs');
                p.classList.add('bg-white', 'text-slate-600', 'hover:bg-slate-50');
            });

            pill.classList.add('bg-emerald-600', 'text-white', 'shadow-xs');
            pill.classList.remove('bg-white', 'text-slate-600', 'hover:bg-slate-50');

            currentCategory = pill.getAttribute('data-category') || '';
            applyFilter();
        });
    });
}

// 2. Modal Detail Spesifikasi Pakan Jadi (Read-Only)
function openDetailModalPublic(item) {
    if (!item) return;
    currentPublicDetailItem = item;

    document.getElementById('pub-detail-code').textContent = item.code || '-';
    document.getElementById('pub-detail-name').textContent = item.name || '-';
    document.getElementById('pub-detail-category').textContent = item.category || '-';
    document.getElementById('pub-detail-stage').textContent = item.target_stage || 'Semua Fase Pertumbuhan';
    document.getElementById('pub-detail-stock').textContent = `${(parseFloat(item.stock_current) || 0).toLocaleString('id-ID')} ${item.unit || 'kg'}`;
    document.getElementById('pub-detail-price').textContent = item.price_per_unit > 0 ? `Rp ${(parseFloat(item.price_per_unit) || 0).toLocaleString('id-ID')} / ${item.unit}` : 'Harga Pembinaan / Subsidi';

    document.getElementById('pub-detail-spec1-label').textContent = item.spec_1_label || 'Protein Kasar';
    document.getElementById('pub-detail-spec1-val').textContent = item.spec_1_value || '-';
    document.getElementById('pub-detail-spec2-label').textContent = item.spec_2_label || 'Kadar Air';
    document.getElementById('pub-detail-spec2-val').textContent = item.spec_2_value || '-';
    document.getElementById('pub-detail-spec3-label').textContent = item.spec_3_label || 'FCR Target';
    document.getElementById('pub-detail-spec3-val').textContent = item.spec_3_value || '-';

    document.getElementById('pub-detail-location').textContent = item.storage_location || 'Gudang Utama BRMP DIY';
    document.getElementById('pub-detail-desc').textContent = item.description || 'Pakan olahan bermutu hasil formulasi riset BRMP D.I. Yogyakarta yang telah melalui pengujian laboratorium terstandarisasi.';

    const imgEl = document.getElementById('pub-detail-img');
    if (imgEl) {
        imgEl.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
        imgEl.alt = item.name;
    }

    openModal('modal-detail-public');
}
