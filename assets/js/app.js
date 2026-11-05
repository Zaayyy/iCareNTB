// assets/js/app.js - Rumah Produksi BRMP DIY (Manajemen Pakan Jadi & Bahan Baku)

let currentDetailItem = null;
let currentProductRecipe = [];
let distributionMap = null;
let distributionMarkers = {};
let distributionLocationsData = [];

// Single Product Map Variables
let singleProductMap = null;
let singleProductMarkers = {};
let currentMapProductItem = null;

// Distribution Form & Result Map Variables
let currentDistributingItem = null;
let distPickerMap = null;
let distPickerMarker = null;
let resultMinimap = null;
let resultMinimapMarkers = [];

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    setupEventHandlers();
    setupProductionListeners();
    initDistributionMinimap();
    setupLocationPresets();
    setupDistributeLocationPresets();
});

// Toast Notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-notification');
    const toastMsg = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    if (!toast) return;

    toastMsg.textContent = message;
    if (type === 'error') {
        toast.className = 'fixed bottom-5 right-5 z-50 flex items-center p-4 text-white bg-rose-600 rounded-xl shadow-2xl transition-all duration-300 transform translate-y-0 opacity-100';
        toastIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
    } else {
        toast.className = 'fixed bottom-5 right-5 z-50 flex items-center p-4 text-white bg-emerald-600 rounded-xl shadow-2xl transition-all duration-300 transform translate-y-0 opacity-100';
        toastIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
    }

    setTimeout(() => {
        toast.className = 'fixed bottom-5 right-5 z-50 flex items-center p-4 text-white bg-emerald-600 rounded-xl shadow-2xl transition-all duration-300 transform translate-y-10 opacity-0 pointer-events-none';
    }, 4500);
}

// Navigation Tabs for Admin Features
function initTabs() {
    const navButtons = document.querySelectorAll('.feature-tab-btn');
    const sections = {
        'tab-produk': document.getElementById('section-produk'),
        'tab-bahan-baku': document.getElementById('section-bahan-baku'),
        'tab-mutasi': document.getElementById('section-mutasi')
    };

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');

            // Reset buttons to transparent/subtle pill
            navButtons.forEach(b => {
                b.classList.remove('bg-[#059669]', 'bg-emerald-600', 'text-white', 'shadow-md');
                b.classList.add('bg-transparent', 'text-slate-600', 'hover:text-emerald-800', 'hover:bg-slate-100/70');
                const badge = b.querySelector('span:last-child');
                if (badge) {
                    badge.classList.remove('bg-white', 'text-emerald-800');
                    badge.classList.add('bg-slate-100', 'text-slate-700');
                }
            });

            // Activate chosen button
            btn.classList.add('bg-[#059669]', 'text-white', 'shadow-md');
            btn.classList.remove('bg-transparent', 'text-slate-600', 'hover:text-emerald-800', 'hover:bg-slate-100/70');
            const activeBadge = btn.querySelector('span:last-child');
            if (activeBadge) {
                activeBadge.classList.add('bg-white', 'text-emerald-800');
                activeBadge.classList.remove('bg-slate-100', 'text-slate-700');
            }

            // Show active section
            Object.keys(sections).forEach(key => {
                if (sections[key]) {
                    if (key === target) {
                        sections[key].classList.remove('hidden');
                    } else {
                        sections[key].classList.add('hidden');
                    }
                }
            });
        });
    });
}

// Copy Color HEX to Clipboard
function copyColorHex(hex, name) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(hex).then(() => {
            showToast(`Warna ${name} (${hex}) berhasil disalin ke clipboard!`);
        }).catch(() => {
            promptCopy(hex);
        });
    } else {
        promptCopy(hex);
    }
}

function promptCopy(text) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    showToast(`Kode warna ${text} berhasil disalin!`);
}

// Setup Event Handlers
function setupEventHandlers() {
    // Setup filter for each section
    setupSectionFilters('bahan-baku');
    setupSectionFilters('produk');

    // Form Add Item
    const formAddItem = document.getElementById('form-add-item');
    if (formAddItem) {
        formAddItem.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formAddItem);
            formData.append('action', 'add_item');

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-add-item');
                    formAddItem.reset();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal menghubungi server.', 'error');
            }
        });
    }

    // Form Standard Mutation
    const formMutation = document.getElementById('form-mutation');
    if (formMutation) {
        formMutation.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formMutation);
            formData.append('action', 'add_mutation');

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-mutation');
                    formMutation.reset();
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal menghubungi server.', 'error');
            }
        });
    }

    // Form Production (Produksi & Auto Potong Bahan Baku)
    const formProduction = document.getElementById('form-production');
    if (formProduction) {
        formProduction.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formProduction);
            formData.append('action', 'add_mutation');
            formData.append('type', 'IN');
            formData.append('auto_deduct_raw', '1');

            // Fallback memastikan item_id dan quantity selalu terbawa
            if (!formData.get('item_id')) {
                const pid = document.getElementById('prod-product-id')?.value;
                if (pid) formData.set('item_id', pid);
            }
            if (!formData.get('quantity')) {
                const qVal = document.getElementById('prod-amount')?.value || document.getElementById('prod-quantity')?.value;
                if (qVal) formData.set('quantity', qVal);
            }

            const submitBtn = document.getElementById('btn-submit-production');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memproses Produksi...';
            }

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-production');
                    formProduction.reset();
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal memproses produksi pakan.', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Mulai Produksi & Kurangi Bahan Baku';
                }
            }
        });
    }

    // Form Distribusi Benih/Pakan & Penentuan Lokasi
    const formDistribute = document.getElementById('form-distribute') || document.getElementById('form-distribute-seed');
    if (formDistribute) {
        formDistribute.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formDistribute);
            formData.append('action', 'add_mutation');
            formData.append('type', 'OUT');

            // Fallback parameter
            if (!formData.get('item_id')) {
                const itId = document.getElementById('dist-item-id')?.value;
                if (itId) formData.set('item_id', itId);
            }
            if (!formData.get('quantity')) {
                const amt = document.getElementById('dist-amount')?.value || document.getElementById('dist-quantity')?.value;
                if (amt) formData.set('quantity', amt);
            }
            if (!formData.get('destination_or_source')) {
                const rec = document.getElementById('dist-recipient')?.value;
                if (rec) formData.set('destination_or_source', rec);
            }

            const submitBtn = document.getElementById('btn-submit-distribute');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Menyimpan & Memetakan...</span>';
            }

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast('Distribusi berhasil dicatat!');
                    closeModal('modal-distribute');
                    formDistribute.reset();

                    // Tampilkan minimap hasil distribusi langsung dengan titik barunya!
                    showDistributionResultMap(data, currentDistributingItem);
                    
                    // Segarkan data peta utama di latar belakang
                    if (typeof loadDistributionLocations === 'function') {
                        loadDistributionLocations();
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal menghubungi server.', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span>Simpan & Tampilkan di Minimap</span>
                    `;
                }
            }
        });
    }

    // Form Edit Item
    const formEditItem = document.getElementById('form-edit-item');
    if (formEditItem) {
        formEditItem.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formEditItem);
            formData.append('action', 'update_item');

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-edit-item');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal memperbarui item.', 'error');
            }
        });
    }

    // Form Recipe Builder
    const formRecipeBuilder = document.getElementById('form-recipe-builder');
    if (formRecipeBuilder) {
        formRecipeBuilder.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formRecipeBuilder);
            formData.append('action', 'save_recipe');

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-recipe-builder');
                    // Reload resep jika modal produksi terbuka
                    if (typeof loadRecipeAndRecalculate === 'function') {
                        loadRecipeAndRecalculate();
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Gagal menyimpan resep formula.', 'error');
            }
        });
    }
}

// Setup Filters for a specific section
function setupSectionFilters(sectionKey) {
    const searchInput = document.getElementById(`search-${sectionKey}`);
    const statusSelect = document.getElementById(`status-${sectionKey}`);
    const pills = document.querySelectorAll(`.cat-pill-${sectionKey}`);

    const applyFilter = (categoryVal = '') => {
        const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const statusVal = statusSelect ? statusSelect.value : '';

        const cards = document.querySelectorAll(`.item-card-${sectionKey}`);
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name')?.toLowerCase() || '';
            const code = card.getAttribute('data-code')?.toLowerCase() || '';
            const category = card.getAttribute('data-category') || '';
            const isCritical = card.getAttribute('data-critical') === '1';

            let matchSearch = !searchVal || name.includes(searchVal) || code.includes(searchVal) || category.toLowerCase().includes(searchVal);
            let matchCat = !categoryVal || category === categoryVal;
            let matchStatus = true;
            if (statusVal === 'critical') matchStatus = isCritical;
            if (statusVal === 'safe') matchStatus = !isCritical;

            if (matchSearch && matchCat && matchStatus) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const noResult = document.getElementById(`no-results-${sectionKey}`);
        if (noResult) {
            if (visibleCount === 0) noResult.classList.remove('hidden');
            else noResult.classList.add('hidden');
        }
    };

    if (searchInput) searchInput.addEventListener('input', () => applyFilter(activeCategory[sectionKey] || ''));
    if (statusSelect) statusSelect.addEventListener('change', () => applyFilter(activeCategory[sectionKey] || ''));

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            const cat = pill.getAttribute('data-category') || '';
            activeCategory[sectionKey] = cat;

            // Highlight pill
            pills.forEach(p => {
                p.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-xs');
                p.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
            });
            pill.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-xs');
            pill.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');

            applyFilter(cat);
        });
    });
}

const activeCategory = {
    'bahan-baku': '',
    'produk': ''
};

// Open HTML5 Dialog Modal
function openModal(id) {
    const dialog = document.getElementById(id);
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
}

// Close HTML5 Dialog Modal
function closeModal(id) {
    const dialog = document.getElementById(id);
    if (dialog && typeof dialog.close === 'function') {
        dialog.close();
    }
}

// Open Detail Modal
function openDetailModal(item) {
    currentDetailItem = item;

    document.getElementById('detail-code').textContent = item.code;
    document.getElementById('detail-name').textContent = item.name;
    document.getElementById('detail-type').textContent = item.type.toUpperCase().replace('_', ' ');
    document.getElementById('detail-category').textContent = item.category;
    document.getElementById('detail-location').textContent = item.storage_location || 'Gudang Utama BRMP DIY';
    document.getElementById('detail-stock').textContent = `${Number(item.stock_current).toLocaleString('id-ID')} ${item.unit}`;
    document.getElementById('detail-min').textContent = `${Number(item.stock_minimum).toLocaleString('id-ID')} ${item.unit}`;
    document.getElementById('detail-price').textContent = `Rp ${Number(item.price_per_unit).toLocaleString('id-ID')}`;

    const detailImg = document.getElementById('detail-img');
    if (detailImg) {
        detailImg.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
    }

    document.getElementById('detail-desc').textContent = item.description || '-';

    // Specs
    document.getElementById('detail-spec1-label').textContent = item.spec_1_label || 'Parameter 1';
    document.getElementById('detail-spec1-val').textContent = item.spec_1_value || '-';
    document.getElementById('detail-spec2-label').textContent = item.spec_2_label || 'Parameter 2';
    document.getElementById('detail-spec2-val').textContent = item.spec_2_value || '-';
    document.getElementById('detail-spec3-label').textContent = item.spec_3_label || 'Parameter 3';
    document.getElementById('detail-spec3-val').textContent = item.spec_3_value || '-';

    // If it's a product, show the "Produksi Pakan (Auto Potong Bahan Baku)" button
    const btnProdDetail = document.getElementById('btn-produce-from-detail');
    if (btnProdDetail) {
        if (item.type === 'produk') {
            btnProdDetail.classList.remove('hidden');
        } else {
            btnProdDetail.classList.add('hidden');
        }
    }

    // Recipe builder button (only for products)
    const btnRecipeDetail = document.getElementById('btn-recipe-from-detail');
    if (btnRecipeDetail) {
        if (item.type === 'produk') {
            btnRecipeDetail.classList.remove('hidden');
        } else {
            btnRecipeDetail.classList.add('hidden');
        }
    }

    // If it's a finished product, show "Lihat Peta Sebaran Produk" button
    const btnMapDetail = document.getElementById('btn-map-from-detail');
    if (btnMapDetail) {
        if (item.type === 'produk') {
            btnMapDetail.classList.remove('hidden');
        } else {
            btnMapDetail.classList.add('hidden');
        }
    }

    openModal('modal-detail-item');
}

// Quick Mutation Trigger
function openQuickMutation(itemId, itemName, itemUnit, defaultType = 'IN') {
    const select = document.getElementById('mut-item-id');
    const typeSelect = document.getElementById('mut-type');
    const unitLabel = document.getElementById('mut-unit-label');

    if (select) select.value = itemId;
    if (typeSelect) typeSelect.value = defaultType;
    if (unitLabel) unitLabel.textContent = itemUnit;

    openModal('modal-mutation');
}

function triggerMutationFromDetail(type) {
    if (!currentDetailItem) return;
    closeModal('modal-detail-item');
    openQuickMutation(currentDetailItem.id, currentDetailItem.name, currentDetailItem.unit, type);
}

function triggerProductionFromDetail() {
    if (!currentDetailItem || currentDetailItem.type !== 'produk') return;
    closeModal('modal-detail-item');
    openProductionModal(currentDetailItem.id);
}

// Production Modal (Produksi Produk -> Bahan Baku Berkurang Otomatis)
function setupProductionListeners() {
    const prodSelect = document.getElementById('prod-product-id');
    const prodQtyInput = document.getElementById('prod-amount') || document.getElementById('prod-quantity');

    if (prodSelect) {
        prodSelect.addEventListener('change', () => {
            loadRecipeAndRecalculate();
        });
    }

    if (prodQtyInput) {
        prodQtyInput.addEventListener('input', () => {
            recalculateBOM();
        });
    }
}

async function openProductionModal(productId = null) {
    const prodSelect = document.getElementById('prod-product-id');
    if (productId && prodSelect) {
        prodSelect.value = productId;
    }
    await loadRecipeAndRecalculate();
    openModal('modal-production');
}

async function loadRecipeAndRecalculate() {
    const prodSelect = document.getElementById('prod-product-id');
    const productId = prodSelect ? prodSelect.value : null;

    if (!productId) return;

    // Update unit label
    const selectedOption = prodSelect.options[prodSelect.selectedIndex];
    const unit = selectedOption ? selectedOption.getAttribute('data-unit') : 'Unit';
    const prodUnitLabel = document.getElementById('prod-unit-label');
    if (prodUnitLabel) prodUnitLabel.textContent = unit;

    try {
        const res = await fetch(`api.php?action=get_recipe&product_id=${productId}`);
        const data = await res.json();
        if (data.success) {
            currentProductRecipe = data.recipe || [];
            recalculateBOM();
        }
    } catch (e) {
        console.error('Gagal mengambil resep formula pakan:', e);
    }
}

function recalculateBOM() {
    const qtyInput = document.getElementById('prod-amount') || document.getElementById('prod-quantity');
    const qty = parseFloat(qtyInput?.value) || 0;
    const tableBody = document.getElementById('bom-table-body');
    const submitBtn = document.getElementById('btn-submit-production');
    const warningBox = document.getElementById('bom-warning-box');

    if (!tableBody) return;

    if (!currentProductRecipe || currentProductRecipe.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-xs text-slate-400">Resep formula bahan baku belum diatur untuk produk ini.</td></tr>`;
        if (submitBtn) submitBtn.disabled = false;
        if (warningBox) warningBox.classList.add('hidden');
        return;
    }

    let allSufficient = true;
    let html = '';

    currentProductRecipe.forEach(item => {
        const perUnit = parseFloat(item.amount_per_unit) || 0;
        const totalNeeded = perUnit * qty;
        const currentStock = parseFloat(item.raw_stock) || 0;
        const remaining = currentStock - totalNeeded;
        const isSufficient = currentStock >= totalNeeded;

        if (!isSufficient && qty > 0) {
            allSufficient = false;
        }

        html += `
            <tr class="border-b border-slate-100 text-xs">
                <td class="py-2.5 font-bold text-slate-800">
                    <div>${item.raw_name}</div>
                    <span class="text-[10px] text-slate-400 font-mono">${item.raw_code}</span>
                </td>
                <td class="py-2.5 text-center text-slate-600 font-semibold">${perUnit.toLocaleString('id-ID')} ${item.raw_unit}</td>
                <td class="py-2.5 text-center font-extrabold ${qty > 0 ? 'text-amber-700 bg-amber-50/50 rounded-lg' : 'text-slate-600'}">
                    ${totalNeeded.toLocaleString('id-ID')} ${item.raw_unit}
                </td>
                <td class="py-2.5 text-center text-slate-600">${currentStock.toLocaleString('id-ID')} ${item.raw_unit}</td>
                <td class="py-2.5 text-right">
                    ${qty === 0 ? '<span class="text-slate-400">-</span>' : (isSufficient ? 
                        `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                            Cukup (${remaining.toLocaleString('id-ID')} ${item.raw_unit})
                        </span>` : 
                        `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 animate-pulse">
                            Kurang ${(totalNeeded - currentStock).toLocaleString('id-ID')} ${item.raw_unit}
                        </span>`
                    )}
                </td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;

    if (warningBox) {
        if (!allSufficient && qty > 0) {
            warningBox.classList.remove('hidden');
        } else {
            warningBox.classList.add('hidden');
        }
    }

    if (submitBtn) {
        submitBtn.disabled = (!allSufficient && qty > 0) || qty <= 0;
        if (!allSufficient && qty > 0) {
            submitBtn.className = "px-6 py-2.5 text-xs font-bold text-white bg-slate-300 rounded-xl cursor-not-allowed shadow-none";
        } else {
            submitBtn.className = "px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth";
        }
    }
}

// Quick refresh trigger
function refreshData() {
    showToast('Menyegarkan data inventaris...');
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Quick modal open with prefilled type
function openAddModalWithType(type) {
    const typeSelect = document.getElementById('add-type-select');
    if (typeSelect) typeSelect.value = type;
    openModal('modal-add-item');
}

// ========================================================
// FITUR LOKASI & MINIMAP DISTRIBUSI BENIH BRMP DIY
// ========================================================

function initDistributionMinimap() {
    const mapContainer = document.getElementById('distribution-minimap');
    if (!mapContainer || typeof L === 'undefined') return;

    // Titik pusat D.I. Yogyakarta (-7.801389, 110.364444)
    const diyCenter = [-7.801389, 110.364444];

    distributionMap = L.map('distribution-minimap', {
        center: diyCenter,
        zoom: 10,
        zoomControl: true,
        scrollWheelZoom: false
    });

    // OpenStreetMap Standard Tile Layer (Gratis, jernih & tanpa API Key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(distributionMap);

    loadDistributionLocations();
}

async function loadDistributionLocations() {
    try {
        const res = await fetch('api.php?action=get_locations&type=produk');
        const data = await res.json();
        if (data.success && Array.isArray(data.locations)) {
            distributionLocationsData = data.locations;
            renderMinimapMarkers(distributionLocationsData);
            renderDistributionList(distributionLocationsData);
        }
    } catch (e) {
        console.error('Gagal memuat titik distribusi:', e);
    }
}

function renderMinimapMarkers(locations) {
    if (!distributionMap || !locations) return;

    // Clear existing markers
    Object.values(distributionMarkers).forEach(m => m.remove());
    distributionMarkers = {};

    const bounds = [];

    // Create Minimalist Pin Icon
    const customIcon = L.divIcon({
        className: 'minimap-pin',
        html: '<div class="minimap-pin-pulse"></div><div class="minimap-pin-inner"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -12]
    });

    locations.forEach(loc => {
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        bounds.push([lat, lng]);

        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(distributionMap);

        // Minimalist Popup Design
        const popupContent = `
            <div class="p-3.5 max-w-[260px] text-xs font-sans">
                <div class="flex items-center space-x-1.5 text-emerald-700 font-extrabold text-[11px] mb-1 uppercase tracking-wider">
                    <span>📍 ${escapeHtml(loc.location_name || 'D.I. Yogyakarta')}</span>
                </div>
                <h4 class="font-bold text-slate-900 text-sm leading-snug mb-1">${escapeHtml(loc.item_name)}</h4>
                <div class="bg-emerald-50 text-emerald-800 px-2.5 py-1 rounded-lg font-bold text-xs inline-block mb-2 border border-emerald-100">
                    Penyaluran: ${(parseFloat(loc.quantity) || 0).toLocaleString('id-ID')} ${escapeHtml(loc.unit || 'unit')}
                </div>
                <div class="space-y-1 text-slate-600 text-[11px] border-t border-slate-100 pt-2">
                    <div><span class="text-slate-400">Penerima:</span> <strong class="text-slate-800">${escapeHtml(loc.destination_or_source)}</strong></div>
                    <div><span class="text-slate-400">Tanggal:</span> ${loc.mutation_date}</div>
                    ${loc.notes ? `<div class="italic text-slate-500 mt-1 text-[10px] bg-slate-50 p-1.5 rounded">"${escapeHtml(loc.notes)}"</div>` : ''}
                </div>
            </div>
        `;

        marker.bindPopup(popupContent);
        distributionMarkers[loc.id] = marker;
    });

    // Update count badge
    const badge = document.getElementById('label-count-titik');
    if (badge) {
        badge.textContent = `${locations.length} Titik Sebaran`;
    }

    // Auto fit map bounds if we have points
    if (bounds.length > 0) {
        distributionMap.fitBounds(bounds, { padding: [35, 35], maxZoom: 12 });
    }
}

function renderDistributionList(locations) {
    const listContainer = document.getElementById('distribution-points-list');
    if (!listContainer) return;

    if (!locations || locations.length === 0) {
        listContainer.innerHTML = `
            <div class="text-center py-8 text-slate-400 text-xs">
                Belum ada titik distribusi yang tercatat.
            </div>
        `;
        return;
    }

    let html = '';
    locations.forEach(loc => {
        html += `
            <div onclick="panToLocation(${loc.id}, ${loc.latitude}, ${loc.longitude})"
                 class="p-3 bg-slate-50 hover:bg-emerald-50/80 rounded-2xl border border-slate-200/70 hover:border-emerald-300 transition-all cursor-pointer group text-xs flex items-center justify-between">
                <div class="pr-2">
                    <div class="font-bold text-slate-800 group-hover:text-emerald-800 transition-smooth line-clamp-1">
                        ${escapeHtml(loc.location_name || loc.destination_or_source)}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                        <span class="font-semibold text-emerald-700">${escapeHtml(loc.item_name)}</span> &bull; 
                        <span class="font-bold text-slate-700">${(parseFloat(loc.quantity) || 0).toLocaleString('id-ID')} ${escapeHtml(loc.unit || 'unit')}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        ${loc.mutation_date} &bull; ${escapeHtml(loc.destination_or_source)}
                    </div>
                </div>
                <div class="shrink-0 text-slate-400 group-hover:text-emerald-600 transition-smooth p-1 bg-white rounded-xl shadow-2xs border border-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </div>
        `;
    });

    listContainer.innerHTML = html;
}

function panToLocation(mutationId, lat, lng) {
    if (!distributionMap) return;

    distributionMap.flyTo([lat, lng], 13, {
        animate: true,
        duration: 1.2
    });

    const marker = distributionMarkers[mutationId];
    if (marker) {
        setTimeout(() => {
            marker.openPopup();
        }, 1300);
    }
}

function focusMinimap() {
    const mapEl = document.getElementById('container-minimap');
    if (mapEl) {
        mapEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function focusMinimapByItemId(itemId) {
    const targetLoc = distributionLocationsData.find(l => parseInt(l.item_id) === parseInt(itemId));

    const mapEl = document.getElementById('container-minimap');
    if (mapEl) {
        mapEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (targetLoc && targetLoc.latitude && targetLoc.longitude) {
        setTimeout(() => {
            panToLocation(targetLoc.id, targetLoc.latitude, targetLoc.longitude);
        }, 400);
    } else {
        showToast('Belum ada riwayat koordinat sebaran untuk produk ini.', 'error');
    }
}

function triggerMapFromDetail() {
    if (!currentDetailItem) return;
    const item = currentDetailItem;
    closeModal('modal-detail-item');
    openProductMapModal(item);
}

function resetMinimapView() {
    if (!distributionMap) return;
    if (distributionLocationsData && distributionLocationsData.length > 0) {
        const bounds = distributionLocationsData.map(l => [parseFloat(l.latitude), parseFloat(l.longitude)]).filter(pt => !isNaN(pt[0]));
        if (bounds.length > 0) {
            distributionMap.fitBounds(bounds, { padding: [35, 35], maxZoom: 12 });
            return;
        }
    }
    distributionMap.flyTo([-7.801389, 110.364444], 10);
}

// Preset Wilayah DIY di Modal Mutasi
function setupLocationPresets() {
    const presetSelect = document.getElementById('mut-preset-location');
    const latInput = document.getElementById('mut-lat');
    const lngInput = document.getElementById('mut-lng');
    const locNameInput = document.getElementById('mut-loc-name');
    const destInput = document.getElementById('mut-destination');

    if (!presetSelect) return;

    presetSelect.addEventListener('change', () => {
        const val = presetSelect.value;
        const selectedOpt = presetSelect.options[presetSelect.selectedIndex];

        if (val === 'manual') {
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (locNameInput) locNameInput.focus();
            return;
        }

        if (val && selectedOpt) {
            const lat = selectedOpt.getAttribute('data-lat');
            const lng = selectedOpt.getAttribute('data-lng');

            if (latInput) latInput.value = lat || '';
            if (lngInput) lngInput.value = lng || '';
            if (locNameInput) locNameInput.value = val;

            if (destInput && (!destInput.value || destInput.value.trim() === '')) {
                destInput.value = 'Kelompok Tani Wilayah ' + val;
            }
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ========================================================
// PETA SEBARAN KHUSUS PER PRODUK (SINGLE PRODUCT MAP)
// ========================================================

async function openProductMapModal(item) {
    if (!item) return;
    currentMapProductItem = item;

    const pmapImg = document.getElementById('pmap-img');
    if (pmapImg) pmapImg.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';

    document.getElementById('pmap-title').textContent = item.name;
    document.getElementById('pmap-code').textContent = item.code;
    document.getElementById('pmap-badge-type').textContent = item.type.toUpperCase().replace('_', ' ');

    openModal('modal-product-map');

    // Inisialisasi peta Leaflet khusus produk jika belum ada
    if (!singleProductMap && typeof L !== 'undefined') {
        singleProductMap = L.map('product-specific-map', {
            center: [-7.801389, 110.364444],
            zoom: 10,
            zoomControl: true,
            scrollWheelZoom: false
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(singleProductMap);
    }

    setTimeout(() => {
        if (singleProductMap) singleProductMap.invalidateSize();
    }, 200);

    // Ambil titik distribusi khusus produk ini
    try {
        const res = await fetch(`api.php?action=get_locations&item_id=${item.id}`);
        const data = await res.json();
        const locations = (data.success && Array.isArray(data.locations)) ? data.locations : [];

        renderSingleProductMap(item, locations);
    } catch (err) {
        console.error('Gagal mengambil titik sebaran produk:', err);
        showToast('Gagal memuat titik distribusi produk.', 'error');
    }
}

function renderSingleProductMap(item, locations) {
    if (!singleProductMap) return;

    // Bersihkan marker lama
    Object.values(singleProductMarkers).forEach(m => m.remove());
    singleProductMarkers = {};

    const contentGrid = document.getElementById('pmap-content-grid');
    const emptyState = document.getElementById('pmap-empty-state');
    const listContainer = document.getElementById('product-specific-list');

    // Hitung statistik produk
    let totalVolume = 0;
    const areas = [];

    locations.forEach(l => {
        totalVolume += (parseFloat(l.quantity) || 0);
        if (l.location_name && !areas.includes(l.location_name)) {
            areas.push(l.location_name);
        }
    });

    document.getElementById('pmap-stat-volume').textContent = `${totalVolume.toLocaleString('id-ID')} ${item.unit}`;
    document.getElementById('pmap-stat-points').textContent = `${locations.length} Titik Penyaluran`;
    document.getElementById('pmap-stat-areas').textContent = areas.length > 0 ? areas.join(', ') : 'Belum Ada Lokasi';

    if (locations.length === 0) {
        if (contentGrid) contentGrid.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (contentGrid) contentGrid.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');

    const bounds = [];

    // Custom pulse marker
    const customIcon = L.divIcon({
        className: 'minimap-pin',
        html: '<div class="minimap-pin-pulse"></div><div class="minimap-pin-inner"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -12]
    });

    let listHtml = '';

    locations.forEach(loc => {
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        bounds.push([lat, lng]);

        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(singleProductMap);

        const popupContent = `
            <div class="p-3.5 max-w-[260px] text-xs font-sans">
                <div class="flex items-center space-x-1.5 text-emerald-700 font-extrabold text-[11px] mb-1 uppercase tracking-wider">
                    <span>📍 ${escapeHtml(loc.location_name || 'D.I. Yogyakarta')}</span>
                </div>
                <h4 class="font-bold text-slate-900 text-sm leading-snug mb-1">${escapeHtml(loc.item_name)}</h4>
                <div class="bg-emerald-50 text-emerald-800 px-2.5 py-1 rounded-lg font-bold text-xs inline-block mb-2 border border-emerald-100">
                    Penyaluran: ${(parseFloat(loc.quantity) || 0).toLocaleString('id-ID')} ${escapeHtml(loc.unit || 'unit')}
                </div>
                <div class="space-y-1 text-slate-600 text-[11px] border-t border-slate-100 pt-2">
                    <div><span class="text-slate-400">Penerima:</span> <strong class="text-slate-800">${escapeHtml(loc.destination_or_source)}</strong></div>
                    <div><span class="text-slate-400">Tanggal:</span> ${loc.mutation_date}</div>
                    ${loc.notes ? `<div class="italic text-slate-500 mt-1 text-[10px] bg-slate-50 p-1.5 rounded">"${escapeHtml(loc.notes)}"</div>` : ''}
                </div>
            </div>
        `;

        marker.bindPopup(popupContent);
        singleProductMarkers[loc.id] = marker;

        listHtml += `
            <div onclick="panToSingleProductLocation(${loc.id}, ${lat}, ${lng})"
                 class="p-3 bg-slate-50 hover:bg-emerald-50/80 rounded-2xl border border-slate-200/70 hover:border-emerald-300 transition-all cursor-pointer group text-xs flex items-center justify-between">
                <div class="pr-2">
                    <div class="font-bold text-slate-800 group-hover:text-emerald-800 transition-smooth line-clamp-1">
                        ${escapeHtml(loc.location_name || loc.destination_or_source)}
                    </div>
                    <div class="text-[11px] text-emerald-700 font-bold mt-0.5">
                        ${(parseFloat(loc.quantity) || 0).toLocaleString('id-ID')} ${escapeHtml(loc.unit || 'unit')}
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        ${loc.mutation_date} &bull; ${escapeHtml(loc.destination_or_source)}
                    </div>
                </div>
                <div class="shrink-0 text-slate-400 group-hover:text-emerald-600 transition-smooth p-1 bg-white rounded-xl shadow-2xs border border-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
            </div>
        `;
    });

    if (listContainer) listContainer.innerHTML = listHtml;

    if (bounds.length > 0) {
        singleProductMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
    } else {
        singleProductMap.setView([-7.801389, 110.364444], 10);
    }
}

function panToSingleProductLocation(id, lat, lng) {
    if (!singleProductMap) return;
    singleProductMap.flyTo([lat, lng], 13, { animate: true, duration: 1.0 });

    const marker = singleProductMarkers[id];
    if (marker) {
        setTimeout(() => marker.openPopup(), 1100);
    }
}

function triggerDistributeFromProductMap() {
    if (!currentMapProductItem) return;
    const it = currentMapProductItem;
    closeModal('modal-product-map');
    openQuickMutation(it.id, it.name, it.unit, 'OUT');
}

// Filter per produk pada minimap utama
async function filterMinimapByProduct(productId) {
    if (!productId) {
        loadDistributionLocations();
        return;
    }

    try {
        const res = await fetch(`api.php?action=get_locations&item_id=${productId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.locations)) {
            renderMinimapMarkers(data.locations);
            renderDistributionList(data.locations);

            const badge = document.getElementById('label-count-titik');
            if (badge) {
                badge.textContent = `${data.locations.length} Titik Produk`;
            }
        }
    } catch (e) {
        console.error('Gagal memfilter minimap per produk:', e);
    }
}

// ========================================================
// FITUR DISTRIBUSI BENIH & TAMPILAN MINIMAP HASIL INPUT
// ========================================================

function openDistributeModal(item) {
    if (!item) return;
    currentDistributingItem = item;

    const itemIdInput = document.getElementById('dist-item-id');
    if (itemIdInput) itemIdInput.value = item.id;

    const itemNameEl = document.getElementById('dist-item-name');
    if (itemNameEl) itemNameEl.textContent = item.name;

    const itemCodeEl = document.getElementById('dist-item-code');
    if (itemCodeEl) itemCodeEl.textContent = item.code;

    const unitLabelEl = document.getElementById('dist-unit-label');
    if (unitLabelEl) unitLabelEl.textContent = item.unit || 'unit';

    const stockDisplayEl = document.getElementById('dist-item-stock') || document.getElementById('dist-stock-display');
    if (stockDisplayEl) {
        stockDisplayEl.textContent = `${(parseFloat(item.stock_current) || 0).toLocaleString('id-ID')} ${item.unit || 'unit'}`;
    }

    const qtyInput = document.getElementById('dist-amount') || document.getElementById('dist-quantity');
    if (qtyInput) {
        qtyInput.max = item.stock_current;
        qtyInput.value = '';
    }

    const imgEl = document.getElementById('dist-item-img');
    if (imgEl) {
        imgEl.src = item.image_url || 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
    }

    openModal('modal-distribute');
    setTimeout(() => {
        initDistPickerMap();
    }, 200);
}

function triggerDistributeFromDetail() {
    if (!currentDetailItem) return;
    const it = currentDetailItem;
    closeModal('modal-detail-item');
    openDistributeModal(it);
}

function initDistPickerMap() {
    const container = document.getElementById('dist-picker-map');
    if (!container || typeof L === 'undefined') return;

    if (!distPickerMap) {
        distPickerMap = L.map('dist-picker-map', {
            center: [-7.801389, 110.364444],
            zoom: 10,
            zoomControl: true,
            scrollWheelZoom: false
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(distPickerMap);

        // Event Klik pada peta untuk mengambil titik manual
        distPickerMap.on('click', (e) => {
            const lat = parseFloat(e.latlng.lat.toFixed(4));
            const lng = parseFloat(e.latlng.lng.toFixed(4));

            document.getElementById('dist-lat').value = lat;
            document.getElementById('dist-lng').value = lng;

            updatePickerMarker(lat, lng);

            const locNameInput = document.getElementById('dist-location-name');
            if (locNameInput && (!locNameInput.value || locNameInput.value.startsWith('Koordinat:'))) {
                locNameInput.value = `Titik Koordinat: ${lat}, ${lng}`;
            }
        });
    }

    setTimeout(() => {
        if (distPickerMap) distPickerMap.invalidateSize();
    }, 200);
}

function updatePickerMarker(lat, lng) {
    if (!distPickerMap) return;

    const customIcon = L.divIcon({
        className: 'minimap-pin',
        html: '<div class="minimap-pin-pulse"></div><div class="minimap-pin-inner"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -12]
    });

    if (distPickerMarker) {
        distPickerMarker.setLatLng([lat, lng]);
    } else {
        distPickerMarker = L.marker([lat, lng], { icon: customIcon }).addTo(distPickerMap);
    }

    distPickerMap.panTo([lat, lng]);
}

function setupDistributeLocationPresets() {
    const presetSelect = document.getElementById('dist-preset-select');
    if (!presetSelect) return;

    presetSelect.addEventListener('change', () => {
        const selectedOpt = presetSelect.options[presetSelect.selectedIndex];
        if (!selectedOpt || !presetSelect.value) return;

        const lat = parseFloat(selectedOpt.getAttribute('data-lat'));
        const lng = parseFloat(selectedOpt.getAttribute('data-lng'));
        const val = presetSelect.value;

        if (!isNaN(lat) && !isNaN(lng)) {
            document.getElementById('dist-lat').value = lat;
            document.getElementById('dist-lng').value = lng;
            document.getElementById('dist-location-name').value = val;

            const destInput = document.getElementById('dist-destination');
            if (destInput && (!destInput.value || destInput.value.trim() === '')) {
                destInput.value = 'Kelompok Tani Wilayah ' + val;
            }

            updatePickerMarker(lat, lng);
        }
    });
}

// Menampilkan minimap hasil distribusi setelah input manual
async function showDistributionResultMap(data, item) {
    if (!data) return;

    const itemName = data.item_name || (item ? item.name : 'Produk/Benih');
    const unit = data.item_unit || (item ? item.unit : 'Unit');

    document.getElementById('res-item-name').textContent = itemName;
    document.getElementById('res-destination').textContent = data.destination || '-';
    document.getElementById('res-location-name').textContent = data.location_name || 'D.I. Yogyakarta';
    document.getElementById('res-quantity-display').textContent = `${Number(data.quantity).toLocaleString('id-ID')} ${unit}`;
    document.getElementById('res-remaining-stock').textContent = `${Number(data.new_stock).toLocaleString('id-ID')} ${unit}`;

    openModal('modal-distribution-result-map');

    const lat = parseFloat(data.latitude);
    const lng = parseFloat(data.longitude);

    if (isNaN(lat) || isNaN(lng)) return;

    const mapContainer = document.getElementById('result-minimap');
    if (!mapContainer || typeof L === 'undefined') return;

    // Bersihkan atau buat peta
    if (!resultMinimap) {
        resultMinimap = L.map('result-minimap', {
            center: [lat, lng],
            zoom: 13,
            zoomControl: true,
            scrollWheelZoom: false
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(resultMinimap);
    } else {
        resultMinimapMarkers.forEach(m => m.remove());
        resultMinimapMarkers = [];
    }

    setTimeout(() => {
        if (resultMinimap) {
            resultMinimap.invalidateSize();
            resultMinimap.setView([lat, lng], 13);
        }
    }, 200);

    // Marker Baru (Highlight berdenyut)
    const newIcon = L.divIcon({
        className: 'minimap-pin',
        html: '<div class="minimap-pin-pulse"></div><div class="minimap-pin-inner" style="background-color:#047857; width:16px; height:16px;"></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -14]
    });

    const newMarker = L.marker([lat, lng], { icon: newIcon }).addTo(resultMinimap);
    resultMinimapMarkers.push(newMarker);

    const popupHtml = `
        <div class="p-3 text-xs font-sans">
            <div class="text-[10px] font-black tracking-wider text-emerald-700 uppercase mb-1">
                📍 Titik Penyaluran Baru
            </div>
            <h4 class="font-bold text-slate-900 text-sm leading-snug mb-1">${escapeHtml(itemName)}</h4>
            <div class="bg-emerald-50 text-emerald-800 font-bold px-2 py-0.5 rounded text-xs mb-1.5 inline-block border border-emerald-100">
                Penyaluran: ${Number(data.quantity).toLocaleString('id-ID')} ${escapeHtml(unit)}
            </div>
            <div class="text-slate-600 text-[11px] space-y-0.5 border-t border-slate-100 pt-1.5">
                <div><span class="text-slate-400">Penerima:</span> <strong>${escapeHtml(data.destination)}</strong></div>
                <div><span class="text-slate-400">Lokasi:</span> ${escapeHtml(data.location_name)}</div>
                <div><span class="text-slate-400">Tanggal:</span> ${data.mutation_date}</div>
            </div>
        </div>
    `;

    newMarker.bindPopup(popupHtml);
    setTimeout(() => {
        newMarker.openPopup();
    }, 450);

    // Ambil juga titik-titik sebelumnya dari produk ini untuk memperlihatkan sebaran lengkapnya
    if (data.item_id) {
        try {
            const res = await fetch(`api.php?action=get_locations&item_id=${data.item_id}`);
            const locData = await res.json();
            if (locData.success && Array.isArray(locData.locations)) {
                const prevIcon = L.divIcon({
                    className: 'minimap-pin',
                    html: '<div class="minimap-pin-inner" style="background-color:#059669; opacity:0.85;"></div>',
                    iconSize: [22, 22],
                    iconAnchor: [11, 11],
                    popupAnchor: [0, -10]
                });

                locData.locations.forEach(prevLoc => {
                    const pLat = parseFloat(prevLoc.latitude);
                    const pLng = parseFloat(prevLoc.longitude);
                    if (prevLoc.id != data.mutation_id && !isNaN(pLat) && !isNaN(pLng)) {
                        const m = L.marker([pLat, pLng], { icon: prevIcon }).addTo(resultMinimap);
                        m.bindPopup(`
                            <div class="p-2.5 text-xs">
                                <div class="font-bold text-slate-800">${escapeHtml(prevLoc.item_name)}</div>
                                <div class="text-emerald-700 font-semibold">${(parseFloat(prevLoc.quantity) || 0).toLocaleString('id-ID')} ${escapeHtml(prevLoc.unit || 'unit')}</div>
                                <div class="text-slate-500 text-[11px]">${escapeHtml(prevLoc.destination_or_source)} &bull; ${prevLoc.mutation_date}</div>
                            </div>
                        `);
                        resultMinimapMarkers.push(m);
                    }
                });
            }
        } catch (e) {
            console.error('Gagal mengambil titik sebelumnya:', e);
        }
    }
}

// ========================================================
// OPERASI DINAMIS & FLEKSIBEL (EDIT, HAPUS, QUICK ADJUST, RESEP)
// ========================================================

let availableRawMaterials = [];

// Buka Modal Edit Item
function openEditModal(item) {
    if (!item) return;

    document.getElementById('edit-id').value = item.id;
    document.getElementById('edit-name').value = item.name || '';
    document.getElementById('edit-code').value = item.code || '';
    document.getElementById('edit-category').value = item.category || '';
    document.getElementById('edit-unit').value = item.unit || 'kg';
    document.getElementById('edit-stock-current').value = item.stock_current || 0;
    document.getElementById('edit-stock-minimum').value = item.stock_minimum || 0;
    document.getElementById('edit-price').value = item.price_per_unit || 0;
    document.getElementById('edit-location').value = item.storage_location || '';
    document.getElementById('edit-image').value = item.image_url || '';
    document.getElementById('edit-desc').value = item.description || '';

    document.getElementById('edit-spec1-label').value = item.spec_1_label || '';
    document.getElementById('edit-spec1-val').value = item.spec_1_value || '';
    document.getElementById('edit-spec2-label').value = item.spec_2_label || '';
    document.getElementById('edit-spec2-val').value = item.spec_2_value || '';
    document.getElementById('edit-spec3-label').value = item.spec_3_label || '';
    document.getElementById('edit-spec3-val').value = item.spec_3_value || '';

    openModal('modal-edit-item');
}

function triggerEditFromDetail() {
    if (!currentDetailItem) return;
    const it = currentDetailItem;
    closeModal('modal-detail-item');
    openEditModal(it);
}

// Konfirmasi & Hapus Item
async function confirmDeleteItem(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus item "${name}" dari inventaris? Tindakan ini tidak dapat dibatalkan.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'delete_item');
        formData.append('id', id);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Gagal menghapus item.', 'error');
    }
}

function confirmDeleteItemFromEdit() {
    const id = document.getElementById('edit-id').value;
    const name = document.getElementById('edit-name').value;
    closeModal('modal-edit-item');
    confirmDeleteItem(id, name);
}

function triggerDeleteFromDetail() {
    if (!currentDetailItem) return;
    const id = currentDetailItem.id;
    const name = currentDetailItem.name;
    closeModal('modal-detail-item');
    confirmDeleteItem(id, name);
}

// Penyesuaian Cepat Stok (+ / - Langsung di Kartu)
async function quickStockAdjust(itemId, delta, reason) {
    try {
        const formData = new FormData();
        formData.append('action', 'quick_stock_adjust');
        formData.append('item_id', itemId);
        formData.append('delta', delta);
        formData.append('reason', reason);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            const stockSpan = document.getElementById(`card-stock-${itemId}`);
            if (stockSpan && typeof data.new_stock !== 'undefined') {
                const parts = stockSpan.textContent.split(' ');
                const unit = parts.slice(1).join(' ') || '';
                stockSpan.textContent = `${Number(data.new_stock).toLocaleString('id-ID')} ${unit}`;
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Gagal menyesuaikan stok.', 'error');
    }
}

// Kelola Resep Formula Pakan (BOM Dinamis)
async function openRecipeBuilder(productId, productName) {
    const idInput = document.getElementById('builder-product-id');
    const nameEl = document.getElementById('builder-product-name');
    if (idInput) idInput.value = productId;
    if (nameEl) nameEl.textContent = productName;

    openModal('modal-recipe-builder');

    const container = document.getElementById('recipe-rows-container') || document.getElementById('recipe-builder-tbody');
    if (container) {
        container.innerHTML = `<div class="py-4 text-center text-xs text-slate-400">Memuat formula bahan baku...</div>`;
    }

    try {
        // Ambil daftar bahan baku yang ada di database
        if (availableRawMaterials.length === 0) {
            const rawRes = await fetch('api.php?action=get_items&type=bahan_baku');
            const rawData = await rawRes.json();
            if (rawData.success && Array.isArray(rawData.data)) {
                availableRawMaterials = rawData.data;
            }
        }

        // Ambil resep produk saat ini
        const recRes = await fetch(`api.php?action=get_recipe&product_id=${productId}`);
        const recData = await recRes.json();
        const currentRecipe = (recData.success && Array.isArray(recData.recipe)) ? recData.recipe : [];

        if (container) container.innerHTML = '';

        if (currentRecipe.length === 0) {
            addRecipeRow();
        } else {
            currentRecipe.forEach(r => {
                addRecipeRow(r.raw_material_id, r.amount_per_unit);
            });
        }
    } catch (e) {
        console.error('Gagal memuat formula:', e);
        showToast('Gagal memuat resep formula.', 'error');
    }
}

function triggerRecipeBuilderFromDetail() {
    if (!currentDetailItem || currentDetailItem.type !== 'produk') return;
    const id = currentDetailItem.id;
    const name = currentDetailItem.name;
    closeModal('modal-detail-item');
    openRecipeBuilder(id, name);
}

function addRecipeRow(selectedRawId = null, defaultAmount = '') {
    const container = document.getElementById('recipe-rows-container') || document.getElementById('recipe-builder-tbody');
    if (!container) return;

    const rowIdx = container.children.length;
    let optionsHtml = '<option value="">-- Pilih Bahan Baku --</option>';

    availableRawMaterials.forEach(rm => {
        const isSel = (selectedRawId && parseInt(selectedRawId) === parseInt(rm.id)) ? 'selected' : '';
        optionsHtml += `<option value="${rm.id}" data-unit="${escapeHtml(rm.unit)}" data-stock="${rm.stock_current}" ${isSel}>${escapeHtml(rm.name)} (Stok: ${rm.stock_current} ${rm.unit})</option>`;
    });

    const row = document.createElement('div');
    row.className = 'recipe-item-row flex flex-col sm:flex-row sm:items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 shadow-2xs transition-smooth';
    row.innerHTML = `
        <div class="flex-1">
            <select name="ingredients[${rowIdx}][raw_material_id]" required onchange="updateRecipeRowUnit(this)" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500">
                ${optionsHtml}
            </select>
        </div>
        <div class="w-full sm:w-36 flex items-center space-x-1.5">
            <input type="number" step="0.01" min="0.01" name="ingredients[${rowIdx}][amount_per_unit]" value="${defaultAmount}" required placeholder="Takaran" class="w-full px-2 py-1.5 border border-slate-300 rounded-lg text-center font-bold text-xs focus:ring-2 focus:ring-amber-500">
            <span class="unit-label text-xs text-slate-500 font-bold shrink-0">kg</span>
        </div>
        <div class="hidden sm:block w-28 text-center text-slate-500 font-mono text-[11px] stock-label">
            -
        </div>
        <div class="flex justify-end shrink-0">
            <button type="button" onclick="removeRecipeRow(this)" class="p-1.5 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-smooth border border-rose-200 hover:border-transparent" title="Hapus Bahan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    `;

    container.appendChild(row);

    const selectEl = row.querySelector('select');
    if (selectEl && selectedRawId) {
        updateRecipeRowUnit(selectEl);
    }
}

function addRecipeIngredientRow(selectedRawId = null, defaultAmount = '') {
    addRecipeRow(selectedRawId, defaultAmount);
}

function updateRecipeRowUnit(selectEl) {
    const row = selectEl.closest('.recipe-item-row') || selectEl.closest('tr');
    if (!row) return;

    const opt = selectEl.options[selectEl.selectedIndex];
    const unit = opt ? opt.getAttribute('data-unit') : 'kg';
    const stock = opt ? opt.getAttribute('data-stock') : '-';

    const unitSpan = row.querySelector('.unit-label');
    const stockSpan = row.querySelector('.stock-label');

    if (unitSpan) unitSpan.textContent = unit || 'kg';
    if (stockSpan) stockSpan.textContent = stock !== null && stock !== '' ? `${stock} ${unit}` : '-';
}

function removeRecipeRow(btn) {
    const row = btn.closest('.recipe-item-row') || btn.closest('tr');
    if (row) row.remove();
}
