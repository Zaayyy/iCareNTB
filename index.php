<?php
// index.php - Rumah Produksi BRMP DIY
// 3 Fitur Utama: 1. Bahan Baku, 2. Produk, 3. Stok Perbenihan
require_once __DIR__ . '/db.php';

// Fetch items for each feature
$bahanBakuList = $pdo->query("SELECT * FROM inventory_items WHERE type = 'bahan_baku' ORDER BY id ASC")->fetchAll();
$produkList = $pdo->query("SELECT * FROM inventory_items WHERE type = 'produk' ORDER BY id ASC")->fetchAll();
$perbenihanList = $pdo->query("SELECT * FROM inventory_items WHERE type = 'perbenihan' ORDER BY id ASC")->fetchAll();

// Categories for pills
$catBahanBaku = array_unique(array_column($bahanBakuList, 'category'));
$catProduk = array_unique(array_column($produkList, 'category'));
$catPerbenihan = array_unique(array_column($perbenihanList, 'category'));

// All items for mutation dropdown
$allItems = $pdo->query("SELECT id, name, code, unit, stock_current, type FROM inventory_items ORDER BY type, name")->fetchAll();

// Mutations log
$mutations = $pdo->query("
    SELECT m.*, i.name as item_name, i.code as item_code, i.unit, i.type as item_type
    FROM inventory_mutations m
    JOIN inventory_items i ON m.item_id = i.id
    ORDER BY m.mutation_date DESC, m.id DESC
    LIMIT 15
")->fetchAll();

// Metrics per feature
function getMetrics($list) {
    $total = count($list);
    $critical = 0;
    foreach ($list as $item) {
        if ($item['stock_current'] <= $item['stock_minimum']) $critical++;
    }
    return ['total' => $total, 'critical' => $critical];
}

$metricsBB = getMetrics($bahanBakuList);
$metricsPRD = getMetrics($produkList);
$metricsBNH = getMetrics($perbenihanList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rumah Produksi &bull; BRMP D.I. Yogyakarta</title>
    <!-- Favicon Logo Resmi BRMP DIY -->
    <link rel="icon" type="image/png" href="assets/img/logo-brmp.png">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Leaflet Minimalist Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brmp: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        },
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex flex-col font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#065f46] text-white shadow-xl sticky top-0 z-40 border-b border-emerald-600/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Official BRMP DIY Branding -->
                <div class="flex items-center space-x-3.5">
                    <a href="index.php" class="relative group block shrink-0" title="BRMP D.I. Yogyakarta">
                        <div class="w-13 h-13 p-1 bg-white/10 hover:bg-white/20 rounded-2xl border border-white/25 shadow-lg backdrop-blur-md transition-smooth flex items-center justify-center">
                            <img src="assets/img/logo-brmp.png" alt="Logo Resmi BRMP DIY" class="w-11 h-11 object-contain drop-shadow-md transform group-hover:scale-105 transition-smooth">
                        </div>
                    </a>
                    <div class="flex items-center space-x-2.5">
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white uppercase drop-shadow-sm">Rumah Produksi</h1>
                        <span class="bg-amber-400 text-slate-900 text-xs px-2.5 py-0.5 rounded-full font-black tracking-wider uppercase shadow-xs">BRMP DIY</span>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div id="quick-actions" class="flex items-center space-x-2 sm:space-x-2.5">
                    <!-- Produksi Pakan -->
                    <button onclick="openProductionModal()" class="inline-flex items-center space-x-1.5 px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 rounded-full shadow-md hover:shadow-lg transition-smooth border border-amber-300/60">
                        <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                        <span>🏭 Catat Produksi</span>
                    </button>
                    <!-- Catat Mutasi -->
                    <button onclick="openModal('modal-mutation')" class="inline-flex items-center space-x-1.5 px-3.5 py-2 text-xs sm:text-sm font-bold text-emerald-900 bg-white hover:bg-emerald-50 rounded-full shadow-md transition-smooth">
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span class="hidden sm:inline">Catat Mutasi</span>
                        <span class="sm:hidden">Mutasi</span>
                    </button>
                    <!-- Tambah Data -->
                    <button onclick="openModal('modal-add-item')" class="inline-flex items-center space-x-1.5 px-4 py-2 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-full shadow-md transition-smooth border border-emerald-400/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden sm:inline">Tambah Data</span>
                        <span class="sm:hidden">+ Data</span>
                    </button>
                    <!-- Cetak Laporan -->
                    <button onclick="window.print()" class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-smooth" title="Cetak Rekap Laporan">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1 w-full space-y-6">

        <!-- 3 MAIN FEATURES NAVIGATION BAR (Segmented Floating Pill) -->
        <nav class="bg-white/90 backdrop-blur-md p-1.5 rounded-full border border-slate-200/80 shadow-[0_2px_15px_rgba(0,0,0,0.04)] flex flex-wrap items-center justify-between gap-1.5 no-print" aria-label="3 Fitur Utama">
            <div class="flex flex-wrap items-center gap-1.5">
                <!-- Fitur 1 -->
                <button data-target="tab-bahan-baku" class="feature-tab-btn px-5 py-2.5 rounded-full font-bold text-xs sm:text-sm bg-transparent text-slate-600 hover:text-emerald-800 hover:bg-slate-100/70 flex items-center space-x-2 transition-smooth">
                    <span>🌾 1. Bahan Baku</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold"><?= $metricsBB['total'] ?></span>
                </button>

                <!-- Fitur 2 -->
                <button data-target="tab-produk" class="feature-tab-btn px-5 py-2.5 rounded-full font-bold text-xs sm:text-sm bg-transparent text-slate-600 hover:text-emerald-800 hover:bg-slate-100/70 flex items-center space-x-2 transition-smooth">
                    <span>📦 2. Produk</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold"><?= $metricsPRD['total'] ?></span>
                </button>

                <!-- Fitur 3 (Default Active) -->
                <button data-target="tab-perbenihan" class="feature-tab-btn px-5 py-2.5 rounded-full font-bold text-xs sm:text-sm bg-[#059669] text-white shadow-md flex items-center space-x-2 transition-smooth">
                    <span>🌱 3. Stok Perbenihan</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-white text-emerald-800 font-bold"><?= $metricsBNH['total'] ?></span>
                </button>
            </div>

            <!-- Quick Info Badge on the Right -->
            <div class="hidden sm:flex items-center space-x-2 pr-3 text-xs text-slate-500 font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Sistem BRMP DIY Terkoneksi</span>
            </div>
        </nav>

        <!-- ======================================================== -->
        <!-- FITUR 1: BAHAN BAKU -->
        <!-- ======================================================== -->
        <section id="section-bahan-baku" class="hidden space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-1">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Monitoring Stok Bahan Baku</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Inventarisasi dan ketersediaan bahan baku pakan bermutu untuk operasional Rumah Produksi BRMP D.I. Yogyakarta.
                    </p>
                </div>
                <div class="flex items-center space-x-3 shrink-0">
                    <button onclick="refreshData()" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Segarkan Data</span>
                    </button>
                    <button onclick="openAddModalWithType('bahan_baku')" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <span>+ Tambah Bahan Baku</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Filter Pills & Search -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-category="" class="cat-pill-bahan-baku px-4 py-2 rounded-full text-xs font-bold border border-emerald-600 bg-emerald-600 text-white shadow-xs transition-smooth">
                        Semua Kategori (<?= count($bahanBakuList) ?>)
                    </button>
                    <?php foreach ($catBahanBaku as $cat): ?>
                    <button type="button" data-category="<?= htmlspecialchars($cat) ?>" class="cat-pill-bahan-baku px-4 py-2 rounded-full text-xs font-bold border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-smooth">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="search-bahan-baku" placeholder="Cari bahan baku..." class="w-full pl-9 pr-3 py-2 rounded-full text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <select id="status-bahan-baku" class="px-3 py-2 text-xs rounded-full border border-slate-200 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="safe">Tersedia</option>
                        <option value="critical">Stok Kritis</option>
                    </select>
                </div>
            </div>

            <!-- Cards Grid (4 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 pt-2">
                <?php foreach ($bahanBakuList as $item): 
                    $isCritical = ($item['stock_current'] <= $item['stock_minimum']);
                ?>
                <div class="item-card-bahan-baku bg-white rounded-3xl p-5 border border-slate-100/90 shadow-[0_4px_25px_rgba(0,0,0,0.03)] item-card-hover flex flex-col justify-between group"
                     data-name="<?= htmlspecialchars($item['name']) ?>"
                     data-code="<?= htmlspecialchars($item['code']) ?>"
                     data-category="<?= htmlspecialchars($item['category']) ?>"
                     data-critical="<?= $isCritical ? '1' : '0' ?>">
                    <div>
                        <!-- Product Image Area with Quick Action Badges -->
                        <div class="h-44 w-full bg-slate-50/70 rounded-2xl flex items-center justify-center p-3 mb-4 overflow-hidden relative border border-slate-100">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-105"
                                 loading="lazy">
                            <span class="absolute top-2.5 right-2.5 bg-black/50 backdrop-blur-xs text-[10px] font-mono text-white px-2 py-0.5 rounded-full font-bold">
                                <?= htmlspecialchars($item['code']) ?>
                            </span>
                            <!-- Quick Edit & Delete Icons -->
                            <div class="absolute top-2.5 left-2.5 flex items-center space-x-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                <button type="button" onclick='event.stopPropagation(); openEditModal(<?= json_encode($item) ?>)' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-blue-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Edit Item">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button type="button" onclick='event.stopPropagation(); confirmDeleteItem(<?= $item['id'] ?>, "<?= htmlspecialchars(addslashes($item['name'])) ?>")' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-rose-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Hapus Item">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Status Badge (Matching Reference) -->
                        <div>
                            <?php if ($isCritical): ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>Stok Kritis</span>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Tersedia</span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title -->
                        <h3 class="text-base font-extrabold text-slate-900 mt-2 line-clamp-1 group-hover:text-emerald-700 transition-smooth" title="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </h3>

                        <!-- Stock Line with Quick Adjust (+/-) -->
                        <div class="flex items-center justify-between text-xs mt-1.5">
                            <span class="text-slate-500 font-medium">
                                Stok: <span id="card-stock-<?= $item['id'] ?>" class="font-black text-[#059669] text-sm"><?= number_format($item['stock_current'], 0, ',', '.') ?> <?= htmlspecialchars($item['unit']) ?></span>
                            </span>
                            <div class="flex items-center space-x-1" onclick="event.stopPropagation()">
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, -10, 'Pengurangan 10 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 font-bold text-xs transition-smooth" title="Kurang 10">-</button>
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, 10, 'Penambahan 10 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 font-bold text-xs transition-smooth" title="Tambah 10">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Action Link -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold">
                        <button onclick='openQuickMutation(<?= $item['id'] ?>, "<?= htmlspecialchars(addslashes($item['name'])) ?>", "<?= htmlspecialchars($item['unit']) ?>", "IN")' class="inline-flex items-center space-x-1 px-3 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-smooth border border-emerald-200/60 shadow-xs">
                            <span>+ Tambah Stok</span>
                        </button>
                        <div onclick='openDetailModal(<?= json_encode($item) ?>)' class="flex items-center space-x-1 text-slate-500 hover:text-emerald-700 cursor-pointer group/link pl-1 font-bold">
                            <span>Detail Spesifikasi</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover/link:translate-x-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search State -->
            <div id="no-results-bahan-baku" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-bold text-slate-800">Tidak ada bahan baku yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        </section>


        <!-- ======================================================== -->
        <!-- FITUR 2: PRODUK -->
        <!-- ======================================================== -->
        <section id="section-produk" class="hidden space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-1">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Katalog Produk Pakan Olahan</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Katalog resmi produk formulasi pakan bermutu yang diproduksi oleh Rumah Produksi BRMP D.I. Yogyakarta.
                    </p>
                </div>
                <div class="flex items-center space-x-3 shrink-0">
                    <button onclick="openProductionModal()" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-full bg-amber-500 hover:bg-amber-400 text-white text-xs sm:text-sm font-bold shadow-xs transition-smooth">
                        <span>🏭 Catat Produksi</span>
                    </button>
                    <button onclick="refreshData()" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Segarkan Data</span>
                    </button>
                    <button onclick="openAddModalWithType('produk')" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <span>+ Tambah Produk</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Informational Banner for Automatic Raw Material Deduction -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 p-4 rounded-2xl flex items-start space-x-3.5 shadow-xs no-print">
                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xs sm:text-sm font-bold text-amber-900">Otomatisasi Formula Produksi (Bill of Materials)</h3>
                    <p class="text-[11px] sm:text-xs text-amber-800/90 mt-0.5 leading-relaxed">
                        Setiap kali Anda mencatat <strong>Produksi Pakan</strong>, sistem akan <strong>otomatis mengurangi stok bahan baku</strong> (seperti Jagung, Bungkil Kedelai, Tepung Ikan, Dedak, dan Premix) sesuai formula resmi BRMP DIY serta memvalidasi ketersediaannya agar stok tidak minus.
                    </p>
                </div>
                <button onclick="openProductionModal()" class="shrink-0 px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-200/60 hover:bg-amber-200 rounded-xl transition-smooth">
                    + Catat Produksi
                </button>
            </div>

            <!-- Filter Pills & Search -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-category="" class="cat-pill-produk px-4 py-2 rounded-full text-xs font-bold border border-emerald-600 bg-emerald-600 text-white shadow-xs transition-smooth">
                        Semua Kategori (<?= count($produkList) ?>)
                    </button>
                    <?php foreach ($catProduk as $cat): ?>
                    <button type="button" data-category="<?= htmlspecialchars($cat) ?>" class="cat-pill-produk px-4 py-2 rounded-full text-xs font-bold border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-smooth">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="search-produk" placeholder="Cari produk pakan..." class="w-full pl-9 pr-3 py-2 rounded-full text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <select id="status-produk" class="px-3 py-2 text-xs rounded-full border border-slate-200 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="safe">Tersedia</option>
                        <option value="critical">Stok Kritis</option>
                    </select>
                </div>
            </div>

            <!-- Cards Grid (4 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 pt-2">
                <?php foreach ($produkList as $item): 
                    $isCritical = ($item['stock_current'] <= $item['stock_minimum']);
                ?>
                <div class="item-card-produk bg-white rounded-3xl p-5 border border-slate-100/90 shadow-[0_4px_25px_rgba(0,0,0,0.03)] item-card-hover flex flex-col justify-between group"
                     data-name="<?= htmlspecialchars($item['name']) ?>"
                     data-code="<?= htmlspecialchars($item['code']) ?>"
                     data-category="<?= htmlspecialchars($item['category']) ?>"
                     data-critical="<?= $isCritical ? '1' : '0' ?>">
                    <div>
                        <!-- Product Image Area with Quick Action Badges -->
                        <div class="h-44 w-full bg-slate-50/70 rounded-2xl flex items-center justify-center p-3 mb-4 overflow-hidden relative border border-slate-100">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-105"
                                 loading="lazy">
                            <span class="absolute top-2.5 right-2.5 bg-black/50 backdrop-blur-xs text-[10px] font-mono text-white px-2 py-0.5 rounded-full font-bold">
                                <?= htmlspecialchars($item['code']) ?>
                            </span>
                            <!-- Quick Edit & Delete Icons -->
                            <div class="absolute top-2.5 left-2.5 flex items-center space-x-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                <button type="button" onclick='event.stopPropagation(); openEditModal(<?= json_encode($item) ?>)' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-blue-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Edit Data Produk">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button type="button" onclick='event.stopPropagation(); confirmDeleteItem(<?= $item['id'] ?>, "<?= htmlspecialchars(addslashes($item['name'])) ?>")' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-rose-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Hapus Produk">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Status Badge (Matching Reference) -->
                        <div>
                            <?php if ($isCritical): ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>Stok Kritis</span>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Tersedia</span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title -->
                        <h3 class="text-base font-extrabold text-slate-900 mt-2 line-clamp-1 group-hover:text-emerald-700 transition-smooth" title="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </h3>

                        <!-- Stock Line with Quick Adjust (+/-) -->
                        <div class="flex items-center justify-between text-xs mt-1.5">
                            <span class="text-slate-500 font-medium">
                                Stok: <span id="card-stock-<?= $item['id'] ?>" class="font-black text-[#059669] text-sm"><?= number_format($item['stock_current'], 0, ',', '.') ?> <?= htmlspecialchars($item['unit']) ?></span>
                            </span>
                            <div class="flex items-center space-x-1" onclick="event.stopPropagation()">
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, -1, 'Pengurangan 1 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 font-bold text-xs transition-smooth" title="Kurang 1">-</button>
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, 1, 'Penambahan 1 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 font-bold text-xs transition-smooth" title="Tambah 1">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Action Link with Direct Produksi & Distribusi & Formula Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs font-semibold gap-1.5">
                            <div class="flex items-center space-x-1">
                                <button onclick="openProductionModal(<?= $item['id'] ?>)" class="inline-flex items-center space-x-1 px-2.5 py-1 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 rounded-lg transition-smooth border border-amber-300/60 shadow-2xs" title="Catat Produksi Pakan">
                                    <span>🏭 Produksi</span>
                                </button>
                                <button onclick='openRecipeBuilder(<?= $item['id'] ?>, "<?= htmlspecialchars(addslashes($item['name'])) ?>")' class="inline-flex items-center space-x-1 px-2 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-smooth border border-slate-200" title="Atur Formula Komposisi Bahan Baku">
                                    <span>🧪 Resep</span>
                                </button>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button onclick='openDistributeModal(<?= json_encode($item) ?>)' class="inline-flex items-center space-x-1 px-2.5 py-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-smooth border border-emerald-200/60 shadow-2xs" title="Salurkan Produk">
                                    <span>Salurkan</span>
                                </button>
                                <button onclick='openProductMapModal(<?= json_encode($item) ?>)' class="p-1 text-slate-500 hover:text-emerald-700 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white transition-smooth" title="Peta Distribusi">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div onclick='openDetailModal(<?= json_encode($item) ?>)' class="flex items-center justify-between text-xs font-bold text-slate-500 hover:text-emerald-700 cursor-pointer pt-1 group/link">
                            <span>Lihat Detail Produk</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover/link:translate-x-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search State -->
            <div id="no-results-produk" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-bold text-slate-800">Tidak ada produk pakan yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        </section>


        <!-- ======================================================== -->
        <!-- FITUR 3: STOK PERBENIHAN (Persis Sesuai Gambar Referensi) -->
        <!-- ======================================================== -->
        <section id="section-perbenihan" class="space-y-6">
            <!-- Header Section matching exact screenshot layout -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-1">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Ketersediaan Benih Bersertifikasi</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Katalog resmi varietas benih unggul berstandar nasional yang diproduksi dan diawasi oleh BRMP D.I. Yogyakarta.
                    </p>
                </div>
                <div class="flex items-center space-x-3 shrink-0">
                    <button onclick="focusMinimap()" class="inline-flex items-center space-x-1.5 px-4 py-2.5 rounded-full border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs sm:text-sm font-bold shadow-xs transition-smooth">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Lihat Peta Sebaran</span>
                    </button>
                    <button onclick="refreshData()" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Segarkan Data</span>
                    </button>
                    <button onclick="openAddModalWithType('perbenihan')" class="inline-flex items-center space-x-1.5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition-smooth">
                        <span>+ Tambah Varietas Benih</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- MINIMAP MINIMALIS: SEBARAN DISTRIBUSI BENIH -->
            <!-- ============================================== -->
            <div id="container-minimap" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_25px_rgba(0,0,0,0.03)] transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/80 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-base font-extrabold text-slate-800">Minimap Sebaran Distribusi Benih BRMP DIY</h3>
                                <span class="text-[10px] uppercase font-bold tracking-wider bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">D.I. Yogyakarta</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Pantau titik lokasi penyaluran stok benih ke kelompok tani binaan & titik tanam di seluruh kabupaten/kota</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Dropdown Filter Map Per Produk -->
                        <div class="relative">
                            <select id="minimap-product-filter" onchange="filterMinimapByProduct(this.value)" class="text-xs font-bold bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-full px-3.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-smooth">
                                <option value="">🌐 Semua Produk & Benih (Peta Gabungan)</option>
                                <optgroup label="Stok Perbenihan (Benih & Bibit)">
                                    <?php foreach ($perbenihanList as $pb): ?>
                                    <option value="<?= $pb['id'] ?>">🌱 <?= htmlspecialchars($pb['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Produk Pakan Olahan BRMP">
                                    <?php foreach ($produkList as $pr): ?>
                                    <option value="<?= $pr['id'] ?>">📦 <?= htmlspecialchars($pr['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <span id="badge-total-titik" class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping inline-block"></span>
                            <span id="label-count-titik">Memuat Titik...</span>
                        </span>
                        <button onclick="resetMinimapView()" class="px-3 py-1 text-xs font-semibold text-slate-600 hover:text-emerald-700 bg-slate-50 hover:bg-slate-100 rounded-full border border-slate-200 transition-smooth">
                            Reset Posisi
                        </button>
                    </div>
                </div>

                <!-- Grid Peta & List Lokasi -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 pt-4">
                    <!-- Peta Leaflet (Colspan 2) -->
                    <div class="lg:col-span-2 relative">
                        <div id="distribution-minimap" class="w-full h-[320px] sm:h-[360px] rounded-2xl overflow-hidden border border-slate-200 z-10 shadow-inner"></div>
                        <div class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-slate-200/90 text-[11px] text-slate-600 shadow-sm flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span class="font-medium">Peta Sebaran Minimalis &bull; Klik titik untuk info rincian</span>
                        </div>
                    </div>

                    <!-- Sidebar Daftar Titik Distribusi (Colspan 1) -->
                    <div class="flex flex-col h-[320px] sm:h-[360px]">
                        <div class="text-xs font-bold text-slate-700 mb-2 flex items-center justify-between">
                            <span>Titik Distribusi Terakhir</span>
                            <span class="text-[10px] text-slate-400 font-normal">Klik untuk zoom lokasi</span>
                        </div>
                        <div id="distribution-points-list" class="overflow-y-auto space-y-2 pr-1 flex-1 custom-scroll">
                            <div class="text-xs text-slate-400 py-6 text-center">Memuat daftar lokasi...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Pills Bar & Search -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-category="" class="cat-pill-perbenihan px-4 py-2 rounded-full text-xs font-bold border border-emerald-600 bg-emerald-600 text-white shadow-xs transition-smooth">
                        Semua Kategori (<?= count($perbenihanList) ?>)
                    </button>
                    <?php foreach ($catPerbenihan as $cat): ?>
                    <button type="button" data-category="<?= htmlspecialchars($cat) ?>" class="cat-pill-perbenihan px-4 py-2 rounded-full text-xs font-bold border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-smooth">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="search-perbenihan" placeholder="Cari varietas benih..." class="w-full pl-9 pr-3 py-2 rounded-full text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <select id="status-perbenihan" class="px-3 py-2 text-xs rounded-full border border-slate-200 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Status</option>
                        <option value="safe">Tersedia</option>
                        <option value="critical">Stok Kritis</option>
                    </select>
                </div>
            </div>

            <!-- Cards Grid (4 Columns, Exactly matching reference image) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 pt-2">
                <?php foreach ($perbenihanList as $item): 
                    $isCritical = ($item['stock_current'] <= $item['stock_minimum']);
                ?>
                <div class="item-card-perbenihan bg-white rounded-3xl p-5 border border-slate-100/90 shadow-[0_4px_25px_rgba(0,0,0,0.03)] item-card-hover flex flex-col justify-between group"
                     data-name="<?= htmlspecialchars($item['name']) ?>"
                     data-code="<?= htmlspecialchars($item['code']) ?>"
                     data-category="<?= htmlspecialchars($item['category']) ?>"
                     data-critical="<?= $isCritical ? '1' : '0' ?>">
                    <div>
                        <!-- Product Image Area with Quick Action Badges -->
                        <div class="h-44 w-full bg-slate-50/70 rounded-2xl flex items-center justify-center p-3 mb-4 overflow-hidden relative border border-slate-100">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="max-h-full max-w-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-105"
                                 loading="lazy">
                            <span class="absolute top-2.5 right-2.5 bg-black/50 backdrop-blur-xs text-[10px] font-mono text-white px-2 py-0.5 rounded-full font-bold">
                                <?= htmlspecialchars($item['code']) ?>
                            </span>
                            <!-- Quick Edit & Delete Icons -->
                            <div class="absolute top-2.5 left-2.5 flex items-center space-x-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                <button type="button" onclick='event.stopPropagation(); openEditModal(<?= json_encode($item) ?>)' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-blue-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Edit Data Varietas">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button type="button" onclick='event.stopPropagation(); confirmDeleteItem(<?= $item['id'] ?>, "<?= htmlspecialchars(addslashes($item['name'])) ?>")' class="p-1.5 bg-white/95 hover:bg-white text-slate-700 hover:text-rose-600 rounded-lg shadow-xs backdrop-blur-xs transition-smooth" title="Hapus Varietas">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Status Badge (Tersedia - matching image reference exactly) -->
                        <div>
                            <?php if ($isCritical): ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>Stok Kritis</span>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Tersedia</span>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title (e.g. Kedelai Varietas Dega-1) -->
                        <h3 class="text-base font-extrabold text-slate-900 mt-2 line-clamp-1 group-hover:text-emerald-700 transition-smooth" title="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </h3>

                        <!-- Stock Line with Quick Adjust (+/-) -->
                        <div class="flex items-center justify-between text-xs mt-1.5">
                            <span class="text-slate-500 font-medium">
                                Stok: <span id="card-stock-<?= $item['id'] ?>" class="font-black text-[#059669] text-sm"><?= number_format($item['stock_current'], 0, ',', '.') ?> <?= htmlspecialchars($item['unit']) ?></span>
                            </span>
                            <div class="flex items-center space-x-1" onclick="event.stopPropagation()">
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, -50, 'Pengurangan 50 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-700 font-bold text-xs transition-smooth" title="Kurang 50">-</button>
                                <button onclick="quickStockAdjust(<?= $item['id'] ?>, 50, 'Penambahan 50 <?= $item['unit'] ?>')" class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 font-bold text-xs transition-smooth" title="Tambah 50">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Action Link (Distribusi & Detail - Identik Referensi) -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-1.5">
                            <button type="button" onclick='openDistributeModal(<?= json_encode($item) ?>)' class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition-smooth" title="Distribusikan Benih & Tandai Lokasi">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <span>Distribusikan</span>
                            </button>
                            <button type="button" onclick='openProductMapModal(<?= json_encode($item) ?>)' class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-slate-50 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 rounded-xl text-[11px] font-bold border border-slate-200 transition-smooth" title="Lihat Titik Sebaran di Minimap">
                                <span>📍 Minimap</span>
                            </button>
                        </div>
                        <!-- Link "Lihat Detail Varietas >" persis seperti di gambar referensi -->
                        <div onclick='openDetailModal(<?= json_encode($item) ?>)' class="flex items-center justify-between text-xs font-bold text-emerald-700 hover:text-emerald-800 cursor-pointer pt-1 group/link">
                            <span>Lihat Detail Varietas</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover/link:translate-x-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search State -->
            <div id="no-results-perbenihan" class="hidden text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-bold text-slate-800">Tidak ada benih yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau pilih kategori lain.</p>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-8 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center space-x-3">
                <img src="assets/img/logo-brmp.png" alt="Logo Resmi BRMP DIY" class="w-10 h-10 object-contain">
                <div class="text-left">
                    <p class="font-extrabold text-slate-800 text-sm">Rumah Produksi &bull; BRMP D.I. Yogyakarta</p>
                    <p class="text-[11px] text-slate-500">Sistem Pengawasan Mutu Pakan, Bahan Baku & Stok Perbenihan Bersertifikasi</p>
                </div>
            </div>
            <div class="text-center sm:text-right text-[11px] text-slate-400">
                <p>&copy; <?= date('Y') ?> Balai Penerapan Standar Instrumen Pertanian / BRMP D.I. Yogyakarta.</p>
                <p class="mt-0.5">Seluruh data inventaris & distribusi terintegrasi secara dinamis.</p>
            </div>
        </div>
    </footer>

    <!-- ========================================== -->
    <!-- MODAL DIALOGS (HTML5 Dialog Standard) -->
    <!-- ========================================== -->

    <!-- Modal 1: Detail Spesifikasi Item -->
    <dialog id="modal-detail-item" class="w-full max-w-md bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <span id="detail-code" class="text-xs font-mono font-bold text-emerald-600">CODE</span>
                <h3 id="detail-name" class="text-lg font-black text-slate-800 leading-tight">Nama Item</h3>
            </div>
            <button onclick="closeModal('modal-detail-item')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-4 mt-4">
            <!-- Product Image -->
            <div class="h-40 w-full bg-slate-50 rounded-2xl flex items-center justify-center p-3 overflow-hidden border border-slate-100">
                <img id="detail-img" src="" alt="Foto Item" class="max-h-full max-w-full object-contain rounded-xl">
            </div>

            <!-- General Info -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block">Tipe Fitur:</span>
                    <span id="detail-type" class="font-bold text-slate-800">PERBENIHAN</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block">Kategori:</span>
                    <span id="detail-category" class="font-bold text-slate-800">-</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block">Stok Tersedia:</span>
                    <span id="detail-stock" class="font-bold text-emerald-700">0</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block">Batas Minimum:</span>
                    <span id="detail-min" class="font-bold text-slate-800">0</span>
                </div>
            </div>

            <!-- Parameter Mutu / Spesifikasi Laboratorium -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2.5">Parameter Mutu & Sertifikasi BRMP</h4>
                
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-200/60">
                        <span id="detail-spec1-label" class="text-slate-600 font-medium">Spesifikasi 1</span>
                        <span id="detail-spec1-val" class="font-bold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200/60">
                        <span id="detail-spec2-label" class="text-slate-600 font-medium">Spesifikasi 2</span>
                        <span id="detail-spec2-val" class="font-bold text-slate-800">-</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span id="detail-spec3-label" class="text-slate-600 font-medium">Spesifikasi 3</span>
                        <span id="detail-spec3-val" class="font-bold text-slate-800">-</span>
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-500 space-y-1">
                <div>Lokasi Gudang: <span id="detail-location" class="font-semibold text-slate-700">Gudang Utama</span></div>
                <div>Standar Harga: <span id="detail-price" class="font-semibold text-slate-700">Rp 0</span></div>
                <div class="pt-1 text-[11px] text-slate-400" id="detail-desc">Deskripsi singkat</div>
            </div>

            <!-- Footer Modal with Quick Mutasi, Distribusi, Edit & Hapus -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                    <button id="btn-produce-from-detail" type="button" onclick="triggerProductionFromDetail()" class="hidden px-3 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 rounded-xl shadow-xs transition-smooth">
                        🏭 Produksi
                    </button>
                    <button id="btn-recipe-from-detail" type="button" onclick="triggerRecipeBuilderFromDetail()" class="hidden px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-xl shadow-xs transition-smooth">
                        🧪 Resep Formula
                    </button>
                    <button id="btn-distribute-from-detail" type="button" onclick="triggerDistributeFromDetail()" class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-xs transition-smooth">
                        📦 Distribusikan
                    </button>
                    <button id="btn-map-from-detail" type="button" onclick="triggerMapFromDetail()" class="hidden px-3 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 rounded-xl shadow-xs transition-smooth">
                        📍 Peta
                    </button>
                    <button type="button" onclick="triggerEditFromDetail()" class="px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition-smooth">
                        ✏️ Edit Data
                    </button>
                    <button type="button" onclick="triggerDeleteFromDetail()" class="px-2.5 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition-smooth" title="Hapus Item">
                        🗑️
                    </button>
                </div>
                <button type="button" onclick="closeModal('modal-detail-item')" class="w-full sm:w-auto px-5 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-smooth">
                    Tutup
                </button>
            </div>
        </div>
    </dialog>

    <!-- Modal Khusus: Peta Sebaran Distribusi Per Produk -->
    <dialog id="modal-product-map" class="w-full max-w-4xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 p-1 border border-emerald-100 flex items-center justify-center overflow-hidden shrink-0">
                    <img id="pmap-img" src="" alt="Foto Produk" class="max-h-full max-w-full object-contain rounded-xl">
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span id="pmap-code" class="text-xs font-mono font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-md">CODE</span>
                        <span id="pmap-badge-type" class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">PRODUK</span>
                    </div>
                    <h3 id="pmap-title" class="text-lg font-black text-slate-800 leading-tight mt-0.5">Peta Distribusi Produk</h3>
                </div>
            </div>
            <button onclick="closeModal('modal-product-map')" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-smooth">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-4 mt-4">
            <!-- Statistik Ringkas Produk Terdistribusi -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 bg-emerald-50/70 rounded-2xl border border-emerald-100/80">
                    <span class="text-[11px] font-bold text-emerald-800 block">Total Volume Tersalurkan</span>
                    <span id="pmap-stat-volume" class="text-lg font-black text-emerald-700">0 Unit</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block">Jumlah Titik Penyaluran</span>
                    <span id="pmap-stat-points" class="text-lg font-black text-slate-800">0 Titik Lokasi</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block">Cakupan Wilayah DIY</span>
                    <span id="pmap-stat-areas" class="text-xs font-bold text-slate-700 mt-1 block truncate">Sleman, Bantul, dll</span>
                </div>
            </div>

            <!-- Grid Peta Leaflet & Daftar Titik Lokasi -->
            <div id="pmap-content-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Peta Leaflet Khusus Produk (Colspan 2) -->
                <div class="lg:col-span-2 relative">
                    <div id="product-specific-map" class="w-full h-[320px] sm:h-[350px] rounded-2xl overflow-hidden border border-slate-200 z-10 shadow-inner"></div>
                    <div class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-slate-200 text-[11px] text-slate-600 shadow-sm flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="font-medium">Peta Sebaran Produk Ini &bull; Klik titik untuk rincian</span>
                    </div>
                </div>

                <!-- Daftar Titik Distribusi Produk Ini (Colspan 1) -->
                <div class="flex flex-col h-[320px] sm:h-[350px]">
                    <div class="text-xs font-bold text-slate-700 mb-2 flex items-center justify-between">
                        <span>Riwayat Penyaluran</span>
                        <span class="text-[10px] text-slate-400 font-normal">Klik untuk zoom</span>
                    </div>
                    <div id="product-specific-list" class="overflow-y-auto space-y-2 pr-1 flex-1 custom-scroll">
                        <!-- Populated dynamically by JS -->
                    </div>
                </div>
            </div>

            <!-- State Belum Ada Lokasi -->
            <div id="pmap-empty-state" class="hidden p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-200/70 text-slate-500 flex items-center justify-center mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h4 class="text-xs font-bold text-slate-700">Belum Ada Titik Lokasi Distribusi</h4>
                <p class="text-[11px] text-slate-500 mt-1">Produk ini belum memiliki catatan penyaluran keluar dengan koordinat lokasi GPS di wilayah D.I. Yogyakarta.</p>
            </div>

            <!-- Action Buttons Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="triggerDistributeFromProductMap()" class="inline-flex items-center space-x-1.5 px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-sm transition-smooth">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Catat Penyaluran Baru Produk Ini</span>
                </button>
                <button type="button" onclick="closeModal('modal-product-map')" class="px-5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                    Tutup Peta
                </button>
            </div>
        </div>
    </dialog>

    <!-- Modal Form: Input Distribusi Benih & Penentuan Lokasi Manual / Peta -->
    <dialog id="modal-distribute" class="w-full max-w-xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center p-1 border border-emerald-100 overflow-hidden shrink-0">
                    <img id="dist-item-img" src="" alt="Foto Benih" class="max-h-full max-w-full object-contain rounded-xl">
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span id="dist-item-code" class="text-[11px] font-mono font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-md">CODE</span>
                        <span class="text-[10px] uppercase font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">Distribusi Keluar</span>
                    </div>
                    <h3 id="dist-item-name" class="text-base font-black text-slate-800 leading-tight mt-0.5">Distribusikan Benih</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Stok Tersedia: <span id="dist-stock-display" class="font-extrabold text-emerald-700">0 kg</span></p>
                </div>
            </div>
            <button onclick="closeModal('modal-distribute')" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-smooth">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-distribute-seed" class="space-y-3.5 mt-4">
            <input type="hidden" id="dist-item-id" name="item_id" value="">
            <input type="hidden" name="type" value="OUT">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Distribusi (<span id="dist-unit-label">kg</span>) *</label>
                    <input type="number" step="0.1" id="dist-quantity" name="quantity" required placeholder="0" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl font-black text-slate-800 focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Distribusi</label>
                    <input type="date" name="mutation_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Penerima / Kelompok Tani *</label>
                    <input type="text" id="dist-destination" name="destination_or_source" placeholder="Contoh: Gapoktan Tani Makmur" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Petugas BRMP DIY</label>
                    <input type="text" name="officer_name" value="Petugas BRMP DIY" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <!-- Bagian Penentuan Lokasi (Input Manual + Peta Interaktif) -->
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-slate-800 flex items-center space-x-1.5">
                        <span>📍</span>
                        <span>Lokasi Distribusi (Input Manual / Tandai Peta)</span>
                    </span>
                    <span class="text-[10px] text-emerald-700 font-bold bg-emerald-100/70 px-2 py-0.5 rounded-full">Peta DIY</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Tempat / Alamat Distribusi *</label>
                    <input type="text" id="dist-location-name" name="location_name" placeholder="Contoh: Desa Wijirejo, Kec. Pandak, Kab. Bantul" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500 font-medium">
                </div>

                <!-- Pilihan Cepat Preset DIY -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-500 mb-1">Pilih Wilayah Cepat (D.I. Yogyakarta):</label>
                    <select id="dist-preset-select" class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-xl bg-white text-slate-700 font-medium">
                        <option value="">-- Pilih Wilayah DIY atau Klik Peta --</option>
                        <option value="Kec. Pandak, Kab. Bantul" data-lat="-7.9015" data-lng="110.3015">Bantul - Kec. Pandak (-7.9015, 110.3015)</option>
                        <option value="Kec. Imogiri, Kab. Bantul" data-lat="-7.9250" data-lng="110.3800">Bantul - Kec. Imogiri (-7.9250, 110.3800)</option>
                        <option value="Kec. Nanggulan, Kab. Kulon Progo" data-lat="-7.7654" data-lng="110.2154">Kulon Progo - Kec. Nanggulan (-7.7654, 110.2154)</option>
                        <option value="Kec. Sentolo, Kab. Kulon Progo" data-lat="-7.8341" data-lng="110.2215">Kulon Progo - Kec. Sentolo (-7.8341, 110.2215)</option>
                        <option value="Kec. Kalasan, Kab. Sleman" data-lat="-7.7680" data-lng="110.4680">Sleman - Kec. Kalasan (-7.7680, 110.4680)</option>
                        <option value="Kec. Pakem, Kab. Sleman" data-lat="-7.6650" data-lng="110.4180">Sleman - Kec. Pakem (-7.6650, 110.4180)</option>
                        <option value="Kec. Tempel, Kab. Sleman" data-lat="-7.6492" data-lng="110.3340">Sleman - Kec. Tempel (-7.6492, 110.3340)</option>
                        <option value="Kec. Semin, Kab. Gunungkidul" data-lat="-7.8650" data-lng="110.7420">Gunungkidul - Kec. Semin (-7.8650, 110.7420)</option>
                        <option value="Kec. Playen, Kab. Gunungkidul" data-lat="-7.9542" data-lng="110.5512">Gunungkidul - Kec. Playen (-7.9542, 110.5512)</option>
                        <option value="Kota Yogyakarta" data-lat="-7.8014" data-lng="110.3644">Kota Yogyakarta (-7.8014, 110.3644)</option>
                    </select>
                </div>

                <!-- Minimap Picker Interaktif -->
                <div class="relative">
                    <div id="dist-picker-map" class="w-full h-[170px] rounded-xl overflow-hidden border border-slate-200 shadow-inner"></div>
                    <div class="absolute bottom-2 left-2 z-[400] bg-white/95 backdrop-blur-xs px-2.5 py-1 rounded-lg border border-slate-200 text-[10px] text-slate-600 shadow-xs flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Klik peta untuk menentukan titik koordinat</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Latitude</label>
                        <input type="number" step="0.0001" id="dist-lat" name="latitude" required placeholder="-7.8014" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Longitude</label>
                        <input type="number" step="0.0001" id="dist-lng" name="longitude" required placeholder="110.3644" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                <input type="text" name="notes" placeholder="Nomor surat jalan, varietas panen, dll..." class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-distribute')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                    Batal
                </button>
                <button type="submit" id="btn-submit-distribute" class="inline-flex items-center space-x-1.5 px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <span>Simpan & Tampilkan di Minimap</span>
                </button>
            </div>
        </form>
    </dialog>

    <!-- Modal: Minimap Hasil Distribusi (Langsung Tampil Setelah Input) -->
    <dialog id="modal-distribution-result-map" class="w-full max-w-3xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800 leading-tight">Distribusi Berhasil Dicatat!</h3>
                    <p class="text-xs text-slate-500">Titik lokasi distribusi berhasil ditandai pada minimap sebaran BRMP DIY</p>
                </div>
            </div>
            <button onclick="closeModal('modal-distribution-result-map')" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 transition-smooth">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-4 mt-4">
            <!-- Banner Rincian Pengiriman yang Baru Saja Diinput -->
            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Item Terdistribusi:</span>
                    <h4 id="res-item-name" class="text-base font-black text-emerald-950">Nama Benih</h4>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        Tujuan: <strong id="res-destination" class="text-emerald-900">Kelompok Tani</strong> &bull; 
                        Lokasi: <span id="res-location-name" class="font-bold text-emerald-900">Alamat Tempat</span>
                    </p>
                </div>
                <div class="sm:text-right shrink-0">
                    <span class="text-[11px] text-emerald-700 block">Kuantitas Penyaluran</span>
                    <span id="res-quantity-display" class="text-xl font-black text-emerald-800">500 kg</span>
                </div>
            </div>

            <!-- Minimap Hasil Penyaluran -->
            <div class="relative">
                <div id="result-minimap" class="w-full h-[320px] rounded-2xl overflow-hidden border border-slate-200 z-10 shadow-inner"></div>
                <div class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-slate-200 text-[11px] text-slate-600 shadow-sm flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping inline-block"></span>
                    <span class="font-medium">Titik lokasi baru &bull; Klik penanda untuk rincian</span>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-slate-500">Sisa stok terkini: <strong id="res-remaining-stock" class="text-slate-800">0</strong></span>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModal('modal-distribution-result-map')" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Modal 2: Tambah Data Baru -->
    <dialog id="modal-add-item" class="w-full max-w-xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-black text-slate-800">Tambah Data Inventaris Baru</h3>
            </div>
            <button onclick="closeModal('modal-add-item')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-add-item" class="space-y-4 mt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Fitur / Jenis *</label>
                    <select id="add-type-select" name="type" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 font-bold">
                        <option value="bahan_baku">1. Bahan Baku</option>
                        <option value="produk">2. Produk Pakan Jadi</option>
                        <option value="perbenihan">3. Stok Perbenihan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Item *</label>
                    <input type="text" name="code" placeholder="Contoh: BNH-KDL-03" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Item / Varietas *</label>
                <input type="text" name="name" placeholder="Nama lengkap varietas atau produk" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori / Klasifikasi *</label>
                    <input type="text" name="category" placeholder="Contoh: Benih Palawija Bersertifikasi" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Satuan Kemasan / Unit *</label>
                    <input type="text" name="unit" placeholder="kg / Sak / Ekor / Drum" required value="kg" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <!-- Parameter Mutu -->
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                <span class="block text-xs font-bold text-slate-700">Parameter Mutu / Spesifikasi Singkat</span>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <input type="text" name="spec_1_label" placeholder="Label 1 (Daya Berkecambah)" value="Kadar Air" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" name="spec_1_value" placeholder="Nilai (e.g. >85%)" value="11.5%" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                    <div>
                        <input type="text" name="spec_2_label" placeholder="Label 2 (Kemurnian)" value="Kemurnian" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" name="spec_2_value" placeholder="Nilai (e.g. 99%)" value="99.5%" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                    <div>
                        <input type="text" name="spec_3_label" placeholder="Label 3 (Ketahanan)" value="Standar SNI" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" name="spec_3_value" placeholder="Nilai" value="Lulus Uji BRMP" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Stok & Harga -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal</label>
                    <input type="number" step="0.1" name="stock_current" value="1000" class="w-full px-3 py-2 text-sm border rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Batas Minimum</label>
                    <input type="number" step="0.1" name="stock_minimum" value="200" class="w-full px-3 py-2 text-sm border rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Harga Satuan (Rp)</label>
                    <input type="number" step="100" name="price_per_unit" value="15000" class="w-full px-3 py-2 text-sm border rounded-xl">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">URL Foto Produk (Opsional)</label>
                <input type="url" name="image_url" placeholder="https://..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-item')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                    Simpan Data
                </button>
            </div>
        </form>
    </dialog>

    <!-- Modal: Edit Item Inventaris (Ubah / Kurang / Hapus) -->
    <dialog id="modal-edit-item" class="w-full max-w-xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <div class="p-2 bg-amber-100 text-amber-700 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-800">Edit Data Inventaris</h3>
                    <p class="text-xs text-slate-500">Ubah detail spesifikasi, batas stok, harga, atau hapus item ini</p>
                </div>
            </div>
            <button onclick="closeModal('modal-edit-item')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-edit-item" class="space-y-4 mt-4">
            <input type="hidden" id="edit-id" name="id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Item / Varietas *</label>
                    <input type="text" id="edit-name" name="name" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Item *</label>
                    <input type="text" id="edit-code" name="code" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori / Klasifikasi *</label>
                    <input type="text" id="edit-category" name="category" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Satuan Kemasan / Unit *</label>
                    <input type="text" id="edit-unit" name="unit" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl font-bold">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Stok Saat Ini</label>
                    <input type="number" step="0.1" id="edit-stock-current" name="stock_current" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl font-black text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Batas Minimum</label>
                    <input type="number" step="0.1" id="edit-stock-minimum" name="stock_minimum" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Harga Satuan (Rp)</label>
                    <input type="number" step="100" id="edit-price" name="price_per_unit" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <!-- Parameter Mutu -->
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                <span class="block text-xs font-bold text-slate-700">Parameter Mutu / Spesifikasi Laboratorium</span>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <input type="text" id="edit-spec1-label" name="spec_1_label" placeholder="Label 1" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" id="edit-spec1-val" name="spec_1_value" placeholder="Nilai" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                    <div>
                        <input type="text" id="edit-spec2-label" name="spec_2_label" placeholder="Label 2" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" id="edit-spec2-val" name="spec_2_value" placeholder="Nilai" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                    <div>
                        <input type="text" id="edit-spec3-label" name="spec_3_label" placeholder="Label 3" class="w-full px-2 py-1 text-xs border rounded-lg mb-1">
                        <input type="text" id="edit-spec3-val" name="spec_3_value" placeholder="Nilai" class="w-full px-2 py-1 text-xs border rounded-lg">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Penyimpanan di Gudang</label>
                    <input type="text" id="edit-location" name="storage_location" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">URL Foto Produk</label>
                    <input type="url" id="edit-image" name="image_url" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Tambahan</label>
                <textarea id="edit-desc" name="description" rows="2" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl"></textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" onclick="confirmDeleteItemFromEdit()" class="inline-flex items-center space-x-1.5 px-4 py-2 text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 rounded-xl border border-rose-200 transition-smooth">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus Item Ini</span>
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModal('modal-edit-item')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- Modal: Kelola Resep Formula Pakan (BOM Dinamis) -->
    <dialog id="modal-recipe-builder" class="w-full max-w-2xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-amber-100 text-amber-700 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-800">Pengaturan Resep Formula Produksi</h3>
                    <p class="text-xs text-slate-500">Atur takaran bahan baku yang terpotong otomatis per 1 unit <span id="builder-product-name" class="font-bold text-slate-700">Produk</span></p>
                </div>
            </div>
            <button onclick="closeModal('modal-recipe-builder')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-recipe-builder" class="space-y-4 mt-4">
            <input type="hidden" id="builder-product-id" name="product_id">

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-700">Daftar Bahan Baku Komposisi:</span>
                    <button type="button" onclick="addRecipeIngredientRow()" class="inline-flex items-center space-x-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-smooth">
                        <span>+ Tambah Bahan</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-200 text-[10px] uppercase font-bold">
                                <th class="py-2">Bahan Baku</th>
                                <th class="py-2 text-center">Takaran per 1 Unit Produk</th>
                                <th class="py-2 text-center">Stok Saat Ini</th>
                                <th class="py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="recipe-builder-tbody" class="divide-y divide-slate-100">
                            <!-- Populated dynamically by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <span class="text-[11px] text-slate-400">Setiap produksi pakan ini akan memotong stok bahan baku di atas secara proporsional.</span>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModal('modal-recipe-builder')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                        Simpan Resep Formula
                    </button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- Modal 3: Catat Mutasi -->
    <dialog id="modal-mutation" class="w-full max-w-lg bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-black text-slate-800">Pencatatan Mutasi Stok</h3>
            </div>
            <button onclick="closeModal('modal-mutation')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-mutation" class="space-y-4 mt-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Item Inventaris *</label>
                <select id="mut-item-id" name="item_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                    <?php foreach ($allItems as $it): ?>
                    <option value="<?= $it['id'] ?>">
                        [<?= strtoupper($it['type']) ?>] <?= htmlspecialchars($it['name']) ?> (Stok: <?= number_format($it['stock_current'], 0, ',', '.') ?> <?= $it['unit'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Transaksi *</label>
                    <select id="mut-type" name="type" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl font-bold">
                        <option value="IN" class="text-emerald-700">+ Stok Masuk (Penerimaan / Panen)</option>
                        <option value="OUT" class="text-rose-700">- Stok Keluar (Penyaluran / Pemakaian)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Volume (<span id="mut-unit-label">Unit</span>) *</label>
                    <input type="number" step="0.1" name="quantity" required placeholder="0" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Transaksi</label>
                    <input type="date" name="mutation_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Petugas BRMP DIY</label>
                    <input type="text" name="officer_name" value="Petugas BRMP DIY" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Sumber / Tujuan Penyaluran *</label>
                <input type="text" id="mut-destination" name="destination_or_source" placeholder="Contoh: Kelompok Tani Sedyo Makmur Kulon Progo" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
            </div>

            <!-- Bagian Lokasi Distribusi (Minimap Geotagging) -->
            <div id="mut-location-section" class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm">📍</span>
                        <span class="text-xs font-bold text-emerald-900">Lokasi Titik Sebaran (Tampil di Minimap)</span>
                    </div>
                    <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-100 px-2 py-0.5 rounded-full">GPS DIY</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Pilih Wilayah Distribusi Cepat (D.I. Yogyakarta):</label>
                    <select id="mut-preset-location" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl bg-white font-medium text-slate-700">
                        <option value="">-- Pilih Wilayah DIY / Titik Panen --</option>
                        <option value="Kec. Nanggulan, Kab. Kulon Progo" data-lat="-7.7654" data-lng="110.2154">Kulon Progo - Kec. Nanggulan (-7.7654, 110.2154)</option>
                        <option value="Kec. Imogiri, Kab. Bantul" data-lat="-7.9250" data-lng="110.3800">Bantul - Kec. Imogiri (-7.9250, 110.3800)</option>
                        <option value="Kec. Semin, Kab. Gunungkidul" data-lat="-7.8650" data-lng="110.7420">Gunungkidul - Kec. Semin (-7.8650, 110.7420)</option>
                        <option value="Kec. Kalasan, Kab. Sleman" data-lat="-7.7680" data-lng="110.4680">Sleman - Kec. Kalasan (-7.7680, 110.4680)</option>
                        <option value="Kec. Pakem, Kab. Sleman" data-lat="-7.6650" data-lng="110.4180">Sleman - Kec. Pakem (-7.6650, 110.4180)</option>
                        <option value="Kota Yogyakarta" data-lat="-7.8014" data-lng="110.3644">Kota Yogyakarta - Pusat (-7.8014, 110.3644)</option>
                        <option value="manual">Manual / Input Koordinat Sendiri</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Nama Tempat / Lokasi</label>
                        <input type="text" id="mut-loc-name" name="location_name" placeholder="Nama Tempat/Desa" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Koordinat GPS (Lat, Lng)</label>
                        <div class="flex items-center space-x-1">
                            <input type="number" step="0.0001" id="mut-lat" name="latitude" placeholder="Latitude" class="w-1/2 px-2 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                            <input type="number" step="0.0001" id="mut-lng" name="longitude" placeholder="Longitude" class="w-1/2 px-2 py-1.5 border border-slate-300 rounded-lg bg-white text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                <textarea name="notes" rows="2" placeholder="Nomor surat jalan, kondisi kemasan, atau catatan batch..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-mutation')" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                    Simpan Mutasi
                </button>
            </div>
        </form>
    </dialog>

    <!-- Modal 4: Catat Produksi Pakan (Auto Kurangi Bahan Baku) -->
    <dialog id="modal-production" class="w-full max-w-2xl bg-white p-6 rounded-3xl shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="p-2.5 bg-amber-100 text-amber-700 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-800">Catat Produksi Pakan Olahan</h3>
                    <p class="text-xs text-slate-500">Stok produk bertambah, dan bahan baku yang bersangkutan otomatis berkurang.</p>
                </div>
            </div>
            <button onclick="closeModal('modal-production')" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="form-production" class="space-y-4 mt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Produk yang Diproduksi *</label>
                    <select id="prod-product-id" name="item_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 font-bold text-slate-800">
                        <?php foreach ($produkList as $pr): ?>
                        <option value="<?= $pr['id'] ?>" data-unit="<?= htmlspecialchars($pr['unit']) ?>">
                            <?= htmlspecialchars($pr['name']) ?> (Stok: <?= number_format($pr['stock_current'], 0, ',', '.') ?> <?= $pr['unit'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Rencana Produksi (<span id="prod-unit-label">Sak</span>) *</label>
                    <input type="number" id="prod-quantity" name="quantity" required min="0.1" step="0.1" value="5" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 font-black text-slate-800 text-lg">
                </div>
            </div>

            <!-- Resep Formula & Simulasi Potong Bahan Baku -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-xs font-bold text-slate-700">Resep Formula Bahan Baku yang Akan Terpotong:</span>
                    </div>
                    <span class="text-[11px] text-emerald-700 font-semibold bg-emerald-100 px-2.5 py-0.5 rounded-full">
                        Otomatis Berkurang
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-500 border-b border-slate-200 uppercase text-[10px]">
                                <th class="py-2 font-bold">Bahan Baku</th>
                                <th class="py-2 text-center font-bold">Resep / Unit</th>
                                <th class="py-2 text-center font-bold text-amber-800">Total Dipotong</th>
                                <th class="py-2 text-center font-bold">Stok Gudang</th>
                                <th class="py-2 text-right font-bold">Status Ketersediaan</th>
                            </tr>
                        </thead>
                        <tbody id="bom-table-body" class="divide-y divide-slate-100">
                            <!-- Populated dynamically by JS -->
                        </tbody>
                    </table>
                </div>

                <div id="bom-warning-box" class="hidden mt-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-center space-x-2">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>Perhatian:</strong> Salah satu bahan baku tidak mencukupi untuk jumlah produksi ini. Silakan kurangi kuantitas produksi atau tambah stok bahan baku terlebih dahulu.</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Produksi</label>
                    <input type="date" name="mutation_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Petugas Produksi BRMP</label>
                    <input type="text" name="officer_name" value="Tim Produksi BRMP DIY" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Batch Produksi</label>
                <input type="text" name="notes" placeholder="Nomor batch, mesin mixer, atau hasil uji organoleptik..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-production')" class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-smooth">
                    Batal
                </button>
                <button type="submit" id="btn-submit-production" class="px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md transition-smooth">
                    Mulai Produksi & Kurangi Bahan Baku
                </button>
            </div>
        </form>
    </dialog>

    <!-- Toast Notification -->
    <div id="toast-notification" class="fixed bottom-5 right-5 z-50 flex items-center p-4 text-white bg-emerald-600 rounded-xl shadow-2xl transition-all duration-300 transform translate-y-10 opacity-0 pointer-events-none">
        <svg id="toast-icon" class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span id="toast-message" class="text-sm font-semibold">Operasi berhasil</span>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
