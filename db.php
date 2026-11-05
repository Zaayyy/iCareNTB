<?php
// db.php - Database connection & auto initialization for Rumah Produksi BRMP DIY
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
$dbFile = $dataDir . '/pakan.db';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL CHECK(type IN ('bahan_baku', 'produk')),
            code TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            target_stage TEXT,
            unit TEXT NOT NULL DEFAULT 'kg',
            stock_current REAL NOT NULL DEFAULT 0,
            stock_minimum REAL NOT NULL DEFAULT 10,
            storage_location TEXT,
            price_per_unit INTEGER NOT NULL DEFAULT 0,
            image_url TEXT,
            spec_1_label TEXT,
            spec_1_value TEXT,
            spec_2_label TEXT,
            spec_2_value TEXT,
            spec_3_label TEXT,
            spec_3_value TEXT,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS inventory_mutations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('IN', 'OUT')),
            quantity REAL NOT NULL,
            mutation_date DATE NOT NULL,
            officer_name TEXT NOT NULL,
            destination_or_source TEXT NOT NULL,
            latitude REAL,
            longitude REAL,
            location_name TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS product_recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            raw_material_id INTEGER NOT NULL,
            amount_per_unit REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
            FOREIGN KEY (raw_material_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        );
    ");

    // Check & Migrate columns in inventory_mutations
    $cols = $pdo->query("PRAGMA table_info(inventory_mutations)")->fetchAll();
    $hasLat = false;
    foreach ($cols as $c) {
        if ($c['name'] === 'latitude') { $hasLat = true; break; }
    }
    if (!$hasLat) {
        $pdo->exec("ALTER TABLE inventory_mutations ADD COLUMN latitude REAL");
        $pdo->exec("ALTER TABLE inventory_mutations ADD COLUMN longitude REAL");
        $pdo->exec("ALTER TABLE inventory_mutations ADD COLUMN location_name TEXT");
    }

    // Check if seeding is needed
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_items");
    $itemCount = $stmt->fetch()['count'];

    if ($itemCount == 0) {
        $seedItems = [
            // ==========================================
            // 1. FITUR BAHAN BAKU (Raw Materials)
            // ==========================================
            [
                'type' => 'bahan_baku',
                'code' => 'BB-JAG-01',
                'name' => 'Jagung Pipil Kering Super',
                'category' => 'Sumber Energi',
                'target_stage' => 'Kadar Air Maks 14%',
                'unit' => 'kg',
                'stock_current' => 4500,
                'stock_minimum' => 1000,
                'storage_location' => 'Silo Bahan Baku A1 BRMP',
                'price_per_unit' => 6200,
                'image_url' => 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Kadar Air',
                'spec_1_value' => '13.2%',
                'spec_2_label' => 'Protein Kasar',
                'spec_2_value' => '8.6%',
                'spec_3_label' => 'Kadar Jamur / Aflatoksin',
                'spec_3_value' => '< 20 ppb (Aman SNI)',
                'description' => 'Jagung pipil lokal DIY berkualitas tinggi hasil panen mitra tani binaan BRMP DIY.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-KDL-01',
                'name' => 'Bungkil Kedelai Non-GMO',
                'category' => 'Protein Nabati',
                'target_stage' => 'Protein Tinggi 44%',
                'unit' => 'kg',
                'stock_current' => 2800,
                'stock_minimum' => 500,
                'storage_location' => 'Gudang Bahan Kering B2',
                'price_per_unit' => 9800,
                'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '44.5%',
                'spec_2_label' => 'Lemak Kasar',
                'spec_2_value' => '1.8%',
                'spec_3_label' => 'Serat Kasar',
                'spec_3_value' => '5.2%',
                'description' => 'Bungkil kedelai murni kaya asam amino esensial untuk formulasi pelet ikan dan unggas.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-DDK-01',
                'name' => 'Dedak Padi Halus Super',
                'category' => 'Sumber Energi & Serat',
                'target_stage' => 'Dedak Selepan Murni',
                'unit' => 'kg',
                'stock_current' => 3200,
                'stock_minimum' => 800,
                'storage_location' => 'Gudang Bahan Kering B1',
                'price_per_unit' => 4500,
                'image_url' => 'https://images.unsplash.com/photo-1509358271058-acd22cc93898?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Kadar Air',
                'spec_1_value' => '11.8%',
                'spec_2_label' => 'Protein Kasar',
                'spec_2_value' => '11.5%',
                'spec_3_label' => 'Kandungan Sekam',
                'spec_3_value' => '< 5% Bebas Campuran',
                'description' => 'Dedak padi halus hasil penggilingan padi berkualitas di Sleman dan Bantul.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-IKN-01',
                'name' => 'Tepung Ikan Murni 60%',
                'category' => 'Protein Hewani',
                'target_stage' => 'Protein Hewani Laut',
                'unit' => 'kg',
                'stock_current' => 1250,
                'stock_minimum' => 300,
                'storage_location' => 'Ruang Bahan Dingin C1',
                'price_per_unit' => 17500,
                'image_url' => 'https://images.unsplash.com/photo-1530595467537-0b5996c41f2d?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '60.5%',
                'spec_2_label' => 'Lemak Kasar',
                'spec_2_value' => '8.2%',
                'spec_3_label' => 'Kadar Garam',
                'spec_3_value' => '< 2.5%',
                'description' => 'Tepung ikan laut murni tanpa campuran pasir dan kimia pengawet berbahaya.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-MGT-01',
                'name' => 'Tepung Maggot BSF Kering',
                'category' => 'Protein Alternatif',
                'target_stage' => 'Biokonversi Organik',
                'unit' => 'kg',
                'stock_current' => 650,
                'stock_minimum' => 200,
                'storage_location' => 'Unit Biokonversi BSF BRMP',
                'price_per_unit' => 35000,
                'image_url' => 'https://images.unsplash.com/photo-1574314833076-a059535359a3?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '42.0%',
                'spec_2_label' => 'Asam Laurat',
                'spec_2_value' => 'Tinggi (Antibakteri)',
                'spec_3_label' => 'Kadar Lemak',
                'spec_3_value' => '28.0%',
                'description' => 'Produksi mandiri biokonversi larva lalat Black Soldier Fly binaan BRMP DIY.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-PMX-01',
                'name' => 'Premix Vitamin & Mineral Ternak',
                'category' => 'Suplemen Mikro',
                'target_stage' => 'Mikronutrisi Lengkap',
                'unit' => 'kg',
                'stock_current' => 65, // Kritis (min 100)
                'stock_minimum' => 100,
                'storage_location' => 'Gudang Suplemen BRMP',
                'price_per_unit' => 45000,
                'image_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Komposisi Vitamin',
                'spec_1_value' => 'Vit A, D3, E, K3, B-Kompleks',
                'spec_2_label' => 'Mineral Mikro',
                'spec_2_value' => 'Zn, Fe, Cu, Mn, Se, I',
                'spec_3_label' => 'Dosis Rekomendasi',
                'spec_3_value' => '1 - 2 kg per Ton Pakan',
                'description' => 'Premix lengkap untuk memastikan daya tahan tubuh dan produktivitas hewan.'
            ],
            [
                'type' => 'bahan_baku',
                'code' => 'BB-MLS-01',
                'name' => 'Molase Tetes Tebu Fermentasi',
                'category' => 'Bahan Fermentasi & Binder',
                'target_stage' => 'Perekat & Sumber Sukrosa',
                'unit' => 'kg',
                'stock_current' => 450,
                'stock_minimum' => 150,
                'storage_location' => 'Tangki Cairan C2',
                'price_per_unit' => 3800,
                'image_url' => 'https://images.unsplash.com/photo-1546554137-f86b9593a222?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Brix',
                'spec_1_value' => '79.5%',
                'spec_2_label' => 'Kadar Gula Reduksi',
                'spec_2_value' => '48.0%',
                'spec_3_label' => 'Kegunaan',
                'spec_3_value' => 'Pellet Binder & Aktivator Probiotik',
                'description' => 'Tetes tebu murni dari pabrik gula lokal Madukismo Yogyakarta.'
            ],

            // ==========================================
            // 2. FITUR PRODUK (Finished Feed Products)
            // ==========================================
            [
                'type' => 'produk',
                'code' => 'PRD-NIL-01',
                'name' => 'Pelet Starter Ikan Nila Pro-32',
                'category' => 'Pakan Ikan',
                'target_stage' => 'Fase Awal (0 - 30 Hari)',
                'unit' => 'Sak (25 kg)',
                'stock_current' => 120,
                'stock_minimum' => 25,
                'storage_location' => 'Silo Produk A1 BRMP',
                'price_per_unit' => 285000,
                'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '32.5%',
                'spec_2_label' => 'Lemak Kasar',
                'spec_2_value' => '6.0%',
                'spec_3_label' => 'Rasio FCR',
                'spec_3_value' => '1.1 - 1.2',
                'description' => 'Pelet terapung ukuran 1 mm berprotein tinggi untuk memacu pertumbuhan benih ikan nila.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-NIL-02',
                'name' => 'Pelet Apung Grower 2',
                'category' => 'Pakan Ikan',
                'target_stage' => 'Fase Pertumbuhan (30 - 60 Hari)',
                'unit' => 'Sak (30 kg)',
                'stock_current' => 185,
                'stock_minimum' => 30,
                'storage_location' => 'Silo Produk A2 BRMP',
                'price_per_unit' => 310000,
                'image_url' => 'https://images.unsplash.com/photo-1574314833076-a059535359a3?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '28.0%',
                'spec_2_label' => 'Kestabilan Air',
                'spec_2_value' => '> 2 Jam Terapung',
                'spec_3_label' => 'Serat Kasar',
                'spec_3_value' => '5.0%',
                'description' => 'Formulasi pakan pembesaran ikan air tawar nila, mas, dan patin dengan efisiensi pakan tinggi.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-GUR-01',
                'name' => 'Finisher Pelet Terapung Super',
                'category' => 'Pakan Ikan',
                'target_stage' => 'Fase Akhir / Penggemukan',
                'unit' => 'Sak (30 kg)',
                'stock_current' => 90,
                'stock_minimum' => 20,
                'storage_location' => 'Silo Produk A3 BRMP',
                'price_per_unit' => 325000,
                'image_url' => 'https://images.unsplash.com/photo-1530595467537-0b5996c41f2d?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '25.5%',
                'spec_2_label' => 'Lemak Kasar',
                'spec_2_value' => '6.2%',
                'spec_3_label' => 'Diameter Pelet',
                'spec_3_value' => '4 - 5 mm',
                'description' => 'Pakan finisher untuk ikan konsumsi siap panen menghasilkan tekstur daging padat dan gurih.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-LLE-01',
                'name' => 'Pelet Benih Lele D-1',
                'category' => 'Pakan Benih Ikan',
                'target_stage' => 'Benih Lele D-1 s/d D-3',
                'unit' => 'kg',
                'stock_current' => 18, // Kritis (min 25)
                'stock_minimum' => 25,
                'storage_location' => 'Ruang Benih & Hatchery',
                'price_per_unit' => 24000,
                'image_url' => 'https://images.unsplash.com/photo-1509358271058-acd22cc93898?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '38.0%',
                'spec_2_label' => 'Ukuran Butir',
                'spec_2_value' => '0.5 - 0.7 mm',
                'spec_3_label' => 'Tingkat Kelangsungan (SR)',
                'spec_3_value' => '> 90%',
                'description' => 'Pakan mikro butiran khusus larva lele dan gurami pada masa pendederan awal.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-AYM-01',
                'name' => 'Crumbles Ayam Broiler Starter',
                'category' => 'Pakan Unggas',
                'target_stage' => 'Broiler Umur 0 - 21 Hari',
                'unit' => 'Sak (50 kg)',
                'stock_current' => 65,
                'stock_minimum' => 20,
                'storage_location' => 'Gudang Utama Produk Unggas',
                'price_per_unit' => 495000,
                'image_url' => 'https://images.unsplash.com/photo-1595246140625-573b715d11dc?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '22.8%',
                'spec_2_label' => 'Energi Metabolis (ME)',
                'spec_2_value' => '3.050 kkal/kg',
                'spec_3_label' => 'Kalsium & Fosfor',
                'spec_3_value' => 'Seimbang SNI',
                'description' => 'Pakan bentuk crumbles renyah yang merangsang nafsu makan dan mempercepat bobot ayam.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-DMB-01',
                'name' => 'Konsentrat Pertumbuhan Domba',
                'category' => 'Pakan Ruminansia',
                'target_stage' => 'Domba & Kambing Penggemukan',
                'unit' => 'Sak (40 kg)',
                'stock_current' => 40,
                'stock_minimum' => 15,
                'storage_location' => 'Hanggar Ruminansia BRMP',
                'price_per_unit' => 230000,
                'image_url' => 'https://images.unsplash.com/photo-1484557052118-f32bd25b45b5?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Protein Kasar',
                'spec_1_value' => '16.5%',
                'spec_2_label' => 'TDN',
                'spec_2_value' => '68.0%',
                'spec_3_label' => 'Pertambahan Bobot (ADG)',
                'spec_3_value' => '150 - 200 gram/hari',
                'description' => 'Pakan konsentrat fermentatif untuk ternak ruminansia kecil di sentra peternakan DIY.'
            ],
            [
                'type' => 'produk',
                'code' => 'PRD-SLS-01',
                'name' => 'Silase Tebon Jagung Fermentasi EM4',
                'category' => 'Pakan Hijauan Awetan',
                'target_stage' => 'Pakan Ternak Musim Kemarau',
                'unit' => 'Drum (100 kg)',
                'stock_current' => 12, // Kritis (min 15)
                'stock_minimum' => 15,
                'storage_location' => 'Silo Fermentasi Hijauan',
                'price_per_unit' => 180000,
                'image_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=500&auto=format&fit=crop&q=80',
                'spec_1_label' => 'Kadar Air',
                'spec_1_value' => '65 - 70%',
                'spec_2_label' => 'Aroma & pH',
                'spec_2_value' => 'Asam Segar (pH 3.8 - 4.2)',
                'spec_3_label' => 'Daya Simpan',
                'spec_3_value' => '6 Bulan Kedap Udara',
                'description' => 'Silase batang & tongkol jagung muda difermentasi dengan bakteri asam laktat unggul.'
            ]
        ];

        $insertStmt = $pdo->prepare("
            INSERT INTO inventory_items (
                type, code, name, category, target_stage, unit, stock_current, stock_minimum,
                storage_location, price_per_unit, image_url, spec_1_label, spec_1_value,
                spec_2_label, spec_2_value, spec_3_label, spec_3_value, description
            ) VALUES (
                :type, :code, :name, :category, :target_stage, :unit, :stock_current, :stock_minimum,
                :storage_location, :price_per_unit, :image_url, :spec_1_label, :spec_1_value,
                :spec_2_label, :spec_2_value, :spec_3_label, :spec_3_value, :description
            )
        ");

        foreach ($seedItems as $item) {
            $insertStmt->execute($item);
        }

        // Seed Mutations
        $mutations = [
            [
                'item_id' => 1,
                'type' => 'IN',
                'quantity' => 2000,
                'mutation_date' => date('Y-m-d', strtotime('-5 days')),
                'officer_name' => 'Bambang S. (BRMP DIY)',
                'destination_or_source' => 'Penerimaan Panen Kelompok Tani Sleman',
                'notes' => 'Pasokan jagung kering kadar air 13%'
            ],
            [
                'item_id' => 8,
                'type' => 'OUT',
                'quantity' => 15,
                'mutation_date' => date('Y-m-d', strtotime('-3 days')),
                'officer_name' => 'Siti Nurhaliza (BRMP DIY)',
                'destination_or_source' => 'Kelompok Pembudidaya Mina Lestari Bantul',
                'notes' => 'Distribusi bantuan pakan program ketahanan pangan'
            ],
            [
                'item_id' => 15,
                'type' => 'OUT',
                'quantity' => 500,
                'mutation_date' => date('Y-m-d', strtotime('-2 days')),
                'officer_name' => 'Agus Prayitno (BRMP DIY)',
                'destination_or_source' => 'Kelompok Tani Sedyo Makmur Kulon Progo',
                'notes' => 'Penyaluran bantuan benih kedelai varietas Dega-1'
            ],
            [
                'item_id' => 18,
                'type' => 'IN',
                'quantity' => 5000,
                'mutation_date' => date('Y-m-d', strtotime('-1 days')),
                'officer_name' => 'Eko Prasetyo (BRMP DIY)',
                'destination_or_source' => 'Unit Sertifikasi Benih Padi DIY',
                'notes' => 'Sertifikasi lulus uji benih pokok Inpari 31'
            ]
        ];

        $insertMut = $pdo->prepare("
            INSERT INTO inventory_mutations (item_id, type, quantity, mutation_date, officer_name, destination_or_source, notes)
            VALUES (:item_id, :type, :quantity, :mutation_date, :officer_name, :destination_or_source, :notes)
        ");

        foreach ($mutations as $m) {
            $insertMut->execute($m);
        }
    }

    // Check & Seed product_recipes
    $recipeCount = $pdo->query("SELECT COUNT(*) FROM product_recipes")->fetchColumn();
    if ($recipeCount == 0) {
        // Find product and raw material IDs by code for resilient seeding
        $getIdByCode = function($code) use ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM inventory_items WHERE code = :code");
            $stmt->execute([':code' => $code]);
            return $stmt->fetchColumn();
        };

        $idJagung = $getIdByCode('BB-JAG-01');
        $idKedelai = $getIdByCode('BB-KDL-01');
        $idDedak = $getIdByCode('BB-DDK-01');
        $idTepungIkan = $getIdByCode('BB-IKN-01');
        $idMaggot = $getIdByCode('BB-MGT-01');
        $idPremix = $getIdByCode('BB-PMX-01');
        $idMolase = $getIdByCode('BB-MLS-01');

        $idPeletStarter = $getIdByCode('PRD-NIL-01');
        $idPeletGrower = $getIdByCode('PRD-NIL-02');
        $idPeletFinisher = $getIdByCode('PRD-GUR-01');
        $idPeletLele = $getIdByCode('PRD-LLE-01');
        $idCrumblesAyam = $getIdByCode('PRD-AYM-01');
        $idKonsentratDomba = $getIdByCode('PRD-DMB-01');
        $idSilase = $getIdByCode('PRD-SLS-01');

        $recipes = [
            // Pelet Starter (25 kg / Sak)
            ['product_id' => $idPeletStarter, 'raw_material_id' => $idJagung, 'amount_per_unit' => 6.0],
            ['product_id' => $idPeletStarter, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 8.5],
            ['product_id' => $idPeletStarter, 'raw_material_id' => $idTepungIkan, 'amount_per_unit' => 6.5],
            ['product_id' => $idPeletStarter, 'raw_material_id' => $idDedak, 'amount_per_unit' => 3.5],
            ['product_id' => $idPeletStarter, 'raw_material_id' => $idPremix, 'amount_per_unit' => 0.5],

            // Pelet Grower (30 kg / Sak)
            ['product_id' => $idPeletGrower, 'raw_material_id' => $idJagung, 'amount_per_unit' => 9.0],
            ['product_id' => $idPeletGrower, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 9.0],
            ['product_id' => $idPeletGrower, 'raw_material_id' => $idDedak, 'amount_per_unit' => 7.0],
            ['product_id' => $idPeletGrower, 'raw_material_id' => $idTepungIkan, 'amount_per_unit' => 4.0],
            ['product_id' => $idPeletGrower, 'raw_material_id' => $idMaggot, 'amount_per_unit' => 1.0],

            // Pelet Finisher (30 kg / Sak)
            ['product_id' => $idPeletFinisher, 'raw_material_id' => $idJagung, 'amount_per_unit' => 11.0],
            ['product_id' => $idPeletFinisher, 'raw_material_id' => $idDedak, 'amount_per_unit' => 9.0],
            ['product_id' => $idPeletFinisher, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 6.0],
            ['product_id' => $idPeletFinisher, 'raw_material_id' => $idTepungIkan, 'amount_per_unit' => 3.5],
            ['product_id' => $idPeletFinisher, 'raw_material_id' => $idMolase, 'amount_per_unit' => 0.5],

            // Pelet Lele D-1 (per kg)
            ['product_id' => $idPeletLele, 'raw_material_id' => $idTepungIkan, 'amount_per_unit' => 0.45],
            ['product_id' => $idPeletLele, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 0.30],
            ['product_id' => $idPeletLele, 'raw_material_id' => $idMaggot, 'amount_per_unit' => 0.15],
            ['product_id' => $idPeletLele, 'raw_material_id' => $idPremix, 'amount_per_unit' => 0.05],
            ['product_id' => $idPeletLele, 'raw_material_id' => $idDedak, 'amount_per_unit' => 0.05],

            // Crumbles Ayam (50 kg / Sak)
            ['product_id' => $idCrumblesAyam, 'raw_material_id' => $idJagung, 'amount_per_unit' => 27.5],
            ['product_id' => $idCrumblesAyam, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 15.0],
            ['product_id' => $idCrumblesAyam, 'raw_material_id' => $idTepungIkan, 'amount_per_unit' => 4.5],
            ['product_id' => $idCrumblesAyam, 'raw_material_id' => $idDedak, 'amount_per_unit' => 2.0],
            ['product_id' => $idCrumblesAyam, 'raw_material_id' => $idPremix, 'amount_per_unit' => 1.0],

            // Konsentrat Domba (40 kg / Sak)
            ['product_id' => $idKonsentratDomba, 'raw_material_id' => $idDedak, 'amount_per_unit' => 18.0],
            ['product_id' => $idKonsentratDomba, 'raw_material_id' => $idJagung, 'amount_per_unit' => 10.0],
            ['product_id' => $idKonsentratDomba, 'raw_material_id' => $idKedelai, 'amount_per_unit' => 8.0],
            ['product_id' => $idKonsentratDomba, 'raw_material_id' => $idMolase, 'amount_per_unit' => 3.0],
            ['product_id' => $idKonsentratDomba, 'raw_material_id' => $idPremix, 'amount_per_unit' => 1.0],

            // Silase Tebon Jagung (100 kg / Drum)
            ['product_id' => $idSilase, 'raw_material_id' => $idJagung, 'amount_per_unit' => 92.0],
            ['product_id' => $idSilase, 'raw_material_id' => $idMolase, 'amount_per_unit' => 5.0],
            ['product_id' => $idSilase, 'raw_material_id' => $idDedak, 'amount_per_unit' => 3.0],
        ];

        $insRecipe = $pdo->prepare("INSERT INTO product_recipes (product_id, raw_material_id, amount_per_unit) VALUES (:product_id, :raw_material_id, :amount_per_unit)");
        foreach ($recipes as $r) {
            if ($r['product_id'] && $r['raw_material_id']) {
                $insRecipe->execute($r);
            }
        }
    }

    // Seed sample product distribution points if not exist
    $distCount = $pdo->query("SELECT COUNT(*) FROM inventory_mutations WHERE latitude IS NOT NULL")->fetchColumn();
    if ($distCount == 0) {
        $getId = function($code) use ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM inventory_items WHERE code = :c");
            $stmt->execute([':c' => $code]);
            return $stmt->fetchColumn();
        };

        $sampleDist = [
            [
                'item_id' => $getId('PRD-NIL-01'),
                'type' => 'OUT',
                'quantity' => 15,
                'mutation_date' => date('Y-m-d', strtotime('-4 days')),
                'officer_name' => 'Agus Prayitno (BRMP DIY)',
                'destination_or_source' => 'Kelompok Pembudidaya Mina Lestari, Bantul',
                'latitude' => -7.9250,
                'longitude' => 110.3800,
                'location_name' => 'Kec. Imogiri, Kab. Bantul',
                'notes' => 'Penyaluran bantuan Pelet Starter Ikan Nila Pro-32 untuk kelompok binaan'
            ],
            [
                'item_id' => $getId('PRD-AYM-01'),
                'type' => 'OUT',
                'quantity' => 20,
                'mutation_date' => date('Y-m-d', strtotime('-3 days')),
                'officer_name' => 'Siti Nurhaliza (BRMP DIY)',
                'destination_or_source' => 'Kelompok Peternak Ayam Makmur, Sleman',
                'latitude' => -7.6650,
                'longitude' => 110.4180,
                'location_name' => 'Kec. Pakem, Kab. Sleman',
                'notes' => 'Distribusi Crumbles Ayam Broiler Starter program ketahanan pakan'
            ],
            [
                'item_id' => $getId('PRD-DMB-01'),
                'type' => 'OUT',
                'quantity' => 10,
                'mutation_date' => date('Y-m-d', strtotime('-2 days')),
                'officer_name' => 'Eko Prasetyo (BRMP DIY)',
                'destination_or_source' => 'Kelompok Ternak Domba Berkah, Gunungkidul',
                'latitude' => -7.8650,
                'longitude' => 110.7420,
                'location_name' => 'Kec. Semin, Kab. Gunungkidul',
                'notes' => 'Penyaluran Konsentrat Pertumbuhan Domba sentra peternakan rakyat'
            ],
            [
                'item_id' => $getId('PRD-NIL-02'),
                'type' => 'OUT',
                'quantity' => 25,
                'mutation_date' => date('Y-m-d', strtotime('-1 days')),
                'officer_name' => 'Bambang S. (BRMP DIY)',
                'destination_or_source' => 'Kelompok Pembudidaya Mina Kalasan, Sleman',
                'latitude' => -7.7680,
                'longitude' => 110.4680,
                'location_name' => 'Kec. Kalasan, Kab. Sleman',
                'notes' => 'Penyaluran Pelet Apung Grower 2 untuk pembudidaya mina DIY'
            ]
        ];

        $ins = $pdo->prepare("
            INSERT INTO inventory_mutations (item_id, type, quantity, mutation_date, officer_name, destination_or_source, latitude, longitude, location_name, notes)
            VALUES (:item_id, :type, :quantity, :mutation_date, :officer_name, :destination_or_source, :latitude, :longitude, :location_name, :notes)
        ");
        foreach ($sampleDist as $d) {
            if (!empty($d['item_id'])) {
                $ins->execute($d);
            }
        }
    }
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
