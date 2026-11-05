// assets/js/public.js - Client Script Khusus Publik (Katalog Pakan Jadi BRMP DIY)

let publicDistMap = null;
let publicDistMarkers = {};
let publicProductMap = null;
let publicProductMarkers = {};
let currentPublicDetailItem = null;

document.addEventListener('DOMContentLoaded', () => {
    initPublicFilters();
    initPublicDistributionMap();
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
        const query = searchInput.value.toLowerCase().trim();
        const statusVal = statusSelect.value;
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
    currentPublicDetailItem = item;

    document.getElementById('pub-detail-code').textContent = item.code || '-';
    document.getElementById('pub-detail-name').textContent = item.name || '-';
    document.getElementById('pub-detail-category').textContent = item.category || '-';
    document.getElementById('pub-detail-stage').textContent = item.target_stage || 'Semua Fase Pertumbuhan';
    document.getElementById('pub-detail-stock').textContent = `${Number(item.stock_current).toLocaleString('id-ID')} ${item.unit || 'kg'}`;
    document.getElementById('pub-detail-price').textContent = item.price_per_unit > 0 ? `Rp ${Number(item.price_per_unit).toLocaleString('id-ID')} / ${item.unit}` : 'Harga Pembinaan / Subsidi';

    document.getElementById('pub-detail-spec1-label').textContent = item.spec_1_label || 'Protein Kasar';
    document.getElementById('pub-detail-spec1-val').textContent = item.spec_1_value || '-';
    document.getElementById('pub-detail-spec2-label').textContent = item.spec_2_label || 'Kadar Air';
    document.getElementById('pub-detail-spec2-val').textContent = item.spec_2_value || '-';
    document.getElementById('pub-detail-spec3-label').textContent = item.spec_3_label || 'FCR Target';
    document.getElementById('pub-detail-spec3-val').textContent = item.spec_3_value || '-';

    document.getElementById('pub-detail-location').textContent = item.storage_location || 'Gudang Utama BRMP DIY';
    document.getElementById('pub-detail-desc').textContent = item.description || 'Pakan olahan bermutu hasil formulasi riset BRMP D.I. Yogyakarta yang telah melalui pengujian laboratorium terstandarisasi.';

    const imgEl = document.getElementById('pub-detail-img');
    imgEl.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
    imgEl.alt = item.name;

    openModal('modal-detail-public');
}

function triggerPublicMapFromDetail() {
    closeModal('modal-detail-public');
    if (currentPublicDetailItem) {
        openProductMapPublic(currentPublicDetailItem);
    }
}

// 3. Peta Sebaran Umum Distribusi Pakan Jadi
function initPublicDistributionMap() {
    const mapContainer = document.getElementById('public-distribution-map');
    if (!mapContainer || typeof L === 'undefined') return;

    // Koordinat pusat D.I. Yogyakarta
    publicDistMap = L.map('public-distribution-map', {
        zoomControl: false,
        attributionControl: false
    }).setView([-7.82, 110.37], 10);

    L.control.zoom({ position: 'topright' }).addTo(publicDistMap);

    // Tile layer CartoDB Positron yang bersih & minimalis
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19
    }).addTo(publicDistMap);

    loadPublicDistributionLocations();
}

function loadPublicDistributionLocations() {
    // Ambil data lokasi khusus produk pakan
    fetch('api.php?action=get_locations&type=produk')
        .then(res => res.json())
        .then(data => {
            const listEl = document.getElementById('public-points-list');
            const countBadge = document.getElementById('label-count-titik-public');

            if (!data.success || !data.locations || data.locations.length === 0) {
                if (countBadge) countBadge.textContent = '0 Titik Lokasi';
                if (listEl) {
                    listEl.innerHTML = `
                        <div class="text-xs text-slate-400 py-10 text-center">
                            Belum ada titik penyaluran pakan yang tercatat di peta.
                        </div>
                    `;
                }
                return;
            }

            const locations = data.locations;
            if (countBadge) countBadge.textContent = `${locations.length} Titik Penyaluran DIY`;

            // Reset markers
            Object.values(publicDistMarkers).forEach(m => publicDistMap.removeLayer(m));
            publicDistMarkers = {};

            let listHtml = '';
            const bounds = [];

            locations.forEach((loc, index) => {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                bounds.push([lat, lng]);

                // Minimalist Custom HTML Pin
                const icon = L.divIcon({
                    className: 'custom-minimap-marker',
                    html: `
                        <div class="minimap-pin group">
                            <div class="minimap-pin-pulse"></div>
                            <div class="minimap-pin-inner"></div>
                        </div>
                    `,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                const popupContent = `
                    <div class="p-3 max-w-[240px] text-xs font-sans">
                        <div class="flex items-center space-x-1.5 mb-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-extrabold text-slate-900 line-clamp-1">${loc.recipient_location || 'Kelompok Peternak DIY'}</span>
                        </div>
                        <p class="text-[11px] font-bold text-emerald-700">${loc.item_name}</p>
                        <p class="text-[11px] text-slate-500 mt-1">Disalurkan: <strong>${Number(loc.amount).toLocaleString('id-ID')} ${loc.unit}</strong></p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Tanggal: ${loc.mutation_date || '-'}</p>
                    </div>
                `;

                const marker = L.marker([lat, lng], { icon })
                    .addTo(publicDistMap)
                    .bindPopup(popupContent);

                publicDistMarkers[loc.id] = marker;

                // Sidebar item
                listHtml += `
                    <div onclick="focusPublicMapPoint(${loc.id}, ${lat}, ${lng})" class="p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-200 cursor-pointer transition-smooth">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-extrabold text-slate-800 line-clamp-1">${loc.recipient_location || 'Penerima Pakan'}</span>
                            <span class="text-[10px] font-black text-emerald-700 shrink-0 ml-2">${Number(loc.amount).toLocaleString('id-ID')} ${loc.unit}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">${loc.item_name}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">${loc.mutation_date}</div>
                    </div>
                `;
            });

            if (listEl) listEl.innerHTML = listHtml;

            if (bounds.length > 0) {
                publicDistMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
            }
        })
        .catch(err => {
            console.error('Error fetching public locations:', err);
        });
}

function focusPublicMapPoint(id, lat, lng) {
    if (!publicDistMap) return;
    publicDistMap.flyTo([lat, lng], 13, { duration: 1 });
    if (publicDistMarkers[id]) {
        setTimeout(() => {
            publicDistMarkers[id].openPopup();
        }, 500);
    }
}

function resetPublicMapView() {
    if (!publicDistMap) return;
    publicDistMap.setView([-7.82, 110.37], 10);
}

// 4. Modal Peta Khusus Produk Pakan Tertentu
function openProductMapPublic(item) {
    document.getElementById('pub-pmap-code').textContent = item.code || '-';
    document.getElementById('pub-pmap-title').textContent = item.name || 'Peta Distribusi';
    
    const imgEl = document.getElementById('pub-pmap-img');
    imgEl.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
    imgEl.alt = item.name;

    openModal('modal-product-map-public');

    // Inisialisasi peta setelah dialog terbuka
    setTimeout(() => {
        initSingleProductMap(item.id);
    }, 200);
}

function initSingleProductMap(productId) {
    const container = document.getElementById('public-product-specific-map');
    if (!container || typeof L === 'undefined') return;

    if (!publicProductMap) {
        publicProductMap = L.map('public-product-specific-map', {
            zoomControl: false,
            attributionControl: false
        }).setView([-7.82, 110.37], 10);

        L.control.zoom({ position: 'topright' }).addTo(publicProductMap);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(publicProductMap);
    } else {
        publicProductMap.invalidateSize();
    }

    // Ambil lokasi khusus item ini
    fetch(`api.php?action=get_locations&item_id=${productId}`)
        .then(res => res.json())
        .then(data => {
            const pointsList = document.getElementById('pub-pmap-points-list');
            const volumeStat = document.getElementById('pub-pmap-stat-volume');
            const pointsStat = document.getElementById('pub-pmap-stat-points');
            const gridContent = document.getElementById('pub-pmap-content-grid');
            const emptyState = document.getElementById('pub-pmap-empty');

            // Reset markers
            Object.values(publicProductMarkers).forEach(m => publicProductMap.removeLayer(m));
            publicProductMarkers = {};

            if (!data.success || !data.locations || data.locations.length === 0) {
                gridContent.classList.add('hidden');
                emptyState.classList.remove('hidden');
                volumeStat.textContent = '0 kg';
                pointsStat.textContent = '0 Titik';
                return;
            }

            gridContent.classList.remove('hidden');
            emptyState.classList.add('hidden');

            const locations = data.locations;
            let totalVol = 0;
            let listHtml = '';
            const bounds = [];

            locations.forEach(loc => {
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                totalVol += parseFloat(loc.amount) || 0;
                bounds.push([lat, lng]);

                const icon = L.divIcon({
                    className: 'custom-minimap-marker',
                    html: `
                        <div class="minimap-pin">
                            <div class="minimap-pin-pulse"></div>
                            <div class="minimap-pin-inner"></div>
                        </div>
                    `,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                const popup = `
                    <div class="p-2.5 text-xs font-sans">
                        <strong class="text-slate-900 block">${loc.recipient_location || 'Lokasi Penerima'}</strong>
                        <span class="text-emerald-700 font-bold block mt-0.5">${Number(loc.amount).toLocaleString('id-ID')} ${loc.unit}</span>
                        <span class="text-[10px] text-slate-400 block">${loc.mutation_date}</span>
                    </div>
                `;

                const marker = L.marker([lat, lng], { icon })
                    .addTo(publicProductMap)
                    .bindPopup(popup);

                publicProductMarkers[loc.id] = marker;

                listHtml += `
                    <div onclick="focusSingleMapPoint(${lat}, ${lng}, ${loc.id})" class="p-2 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-100 cursor-pointer text-xs transition-smooth">
                        <div class="font-bold text-slate-800 line-clamp-1">${loc.recipient_location}</div>
                        <div class="text-[11px] text-emerald-700 font-bold">${Number(loc.amount).toLocaleString('id-ID')} ${loc.unit}</div>
                    </div>
                `;
            });

            volumeStat.textContent = `${totalVol.toLocaleString('id-ID')} kg`;
            pointsStat.textContent = `${locations.length} Titik`;
            if (pointsList) pointsList.innerHTML = listHtml;

            if (bounds.length > 0) {
                publicProductMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
            }
        });
}

function focusSingleMapPoint(lat, lng, id) {
    if (!publicProductMap) return;
    publicProductMap.flyTo([lat, lng], 13, { duration: 0.8 });
    if (publicProductMarkers[id]) {
        setTimeout(() => {
            publicProductMarkers[id].openPopup();
        }, 400);
    }
}
