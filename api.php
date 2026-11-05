<?php
// api.php - REST API handler for Rumah Produksi BRMP DIY
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Proteksi hak akses CRUD khusus admin
$mutationActions = [
    'add_item', 'update_item', 'delete_item', 'quick_adjust', 
    'add_mutation', 'record_production', 'save_recipe', 'distribute_item'
];

if (in_array($action, $mutationActions, true)) {
    if (!is_admin_logged_in()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Akses ditolak: Operasi ini hanya dapat dilakukan oleh Administrator. Silakan login terlebih dahulu.'
        ]);
        exit;
    }
}

try {
    switch ($action) {
        case 'get_items':
            $type = $_GET['type'] ?? '';
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $category = $_GET['category'] ?? '';

            $sql = "SELECT * FROM inventory_items WHERE 1=1";
            $params = [];

            if (!empty($type)) {
                $sql .= " AND type = :type";
                $params[':type'] = $type;
            }

            if (!empty($category)) {
                $sql .= " AND category = :cat";
                $params[':cat'] = $category;
            }

            if (!empty($search)) {
                $sql .= " AND (name LIKE :search OR code LIKE :search OR category LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if (!empty($status)) {
                if ($status === 'critical') {
                    $sql .= " AND stock_current <= stock_minimum";
                } elseif ($status === 'safe') {
                    $sql .= " AND stock_current > stock_minimum";
                }
            }

            $sql .= " ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $items]);
            break;

        case 'get_recipe':
            $productId = (int)($_GET['product_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT r.*, rm.name as raw_name, rm.code as raw_code, rm.unit as raw_unit, rm.stock_current as raw_stock
                FROM product_recipes r
                JOIN inventory_items rm ON r.raw_material_id = rm.id
                WHERE r.product_id = :pid
                ORDER BY r.amount_per_unit DESC
            ");
            $stmt->execute([':pid' => $productId]);
            $recipe = $stmt->fetchAll();
            echo json_encode(['success' => true, 'recipe' => $recipe]);
            break;

        case 'get_locations':
            $itemId = $_GET['item_id'] ?? null;
            $type = $_GET['type'] ?? null;
            $sql = "
                SELECT m.*, i.name as item_name, i.code as item_code, i.unit, i.type as item_type, i.image_url
                FROM inventory_mutations m
                JOIN inventory_items i ON m.item_id = i.id
                WHERE m.latitude IS NOT NULL AND m.longitude IS NOT NULL
            ";
            $params = [];
            if (!empty($itemId)) {
                $sql .= " AND m.item_id = :item_id";
                $params[':item_id'] = $itemId;
            }
            if (!empty($type)) {
                $sql .= " AND i.type = :itype";
                $params[':itype'] = $type;
            }
            $sql .= " ORDER BY m.mutation_date DESC, m.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $locations = $stmt->fetchAll();
            echo json_encode(['success' => true, 'locations' => $locations]);
            break;

        case 'add_item':
            $type = trim($_POST['type'] ?? 'bahan_baku');
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $targetStage = trim($_POST['target_stage'] ?? '');
            $unit = trim($_POST['unit'] ?? 'kg');
            $stockCurrent = (float)($_POST['stock_current'] ?? 0);
            $stockMinimum = (float)($_POST['stock_minimum'] ?? 10);
            $location = trim($_POST['storage_location'] ?? 'Gudang Utama BRMP DIY');
            $price = (int)($_POST['price_per_unit'] ?? 0);
            $imageUrl = trim($_POST['image_url'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $spec1Label = trim($_POST['spec_1_label'] ?? '');
            $spec1Value = trim($_POST['spec_1_value'] ?? '');
            $spec2Label = trim($_POST['spec_2_label'] ?? '');
            $spec2Value = trim($_POST['spec_2_value'] ?? '');
            $spec3Label = trim($_POST['spec_3_label'] ?? '');
            $spec3Value = trim($_POST['spec_3_value'] ?? '');

            if (empty($code) || empty($name) || empty($category)) {
                echo json_encode(['success' => false, 'message' => 'Kode, Nama, dan Kategori wajib diisi!']);
                exit;
            }

            // Check duplicate code
            $chk = $pdo->prepare("SELECT id FROM inventory_items WHERE code = :code");
            $chk->execute([':code' => $code]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Kode barang sudah digunakan!']);
                exit;
            }

            if (empty($imageUrl)) {
                if ($type === 'bahan_baku') {
                    $imageUrl = 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=500&auto=format&fit=crop&q=80';
                } elseif ($type === 'produk') {
                    $imageUrl = 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
                } else {
                    $imageUrl = 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=80';
                }
            }

            $insert = $pdo->prepare("
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
            $insert->execute([
                ':type' => $type,
                ':code' => $code,
                ':name' => $name,
                ':category' => $category,
                ':target_stage' => $targetStage,
                ':unit' => $unit,
                ':stock_current' => $stockCurrent,
                ':stock_minimum' => $stockMinimum,
                ':storage_location' => $location,
                ':price_per_unit' => $price,
                ':image_url' => $imageUrl,
                ':spec_1_label' => $spec1Label,
                ':spec_1_value' => $spec1Value,
                ':spec_2_label' => $spec2Label,
                ':spec_2_value' => $spec2Value,
                ':spec_3_label' => $spec3Label,
                ':spec_3_value' => $spec3Value,
                ':description' => $description
            ]);

            echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan ke inventaris BRMP DIY!']);
            break;

        case 'add_mutation':
            $pdo->beginTransaction();

            $itemId = (int)($_POST['item_id'] ?? $_POST['product_id'] ?? 0);
            $type = strtoupper(trim($_POST['type'] ?? ''));
            $quantity = (float)($_POST['quantity'] ?? $_POST['amount'] ?? 0);
            $date = trim($_POST['mutation_date'] ?? date('Y-m-d'));
            $officer = trim($_POST['officer_name'] ?? 'Petugas BRMP DIY');
            $destination = trim($_POST['destination_or_source'] ?? $_POST['recipient_location'] ?? '-');
            $notes = trim($_POST['notes'] ?? '');

            $latitude = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? (float)$_POST['latitude'] : null;
            $longitude = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? (float)$_POST['longitude'] : null;
            $locationName = trim($_POST['location_name'] ?? $_POST['recipient_location'] ?? '');
            if (empty($locationName) && !empty($destination) && $destination !== '-') {
                $locationName = $destination;
            }
            
            // Auto deduct flag (default true for product production)
            $autoDeduct = isset($_POST['auto_deduct_raw']) ? ($_POST['auto_deduct_raw'] === '1' || $_POST['auto_deduct_raw'] === 'true' || $_POST['auto_deduct_raw'] === 'on') : true;

            if (!$itemId || !in_array($type, ['IN', 'OUT']) || $quantity <= 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Data mutasi tidak valid!']);
                exit;
            }

            // Fetch current item
            $stmt = $pdo->prepare("SELECT id, name, type, stock_current, unit FROM inventory_items WHERE id = :id");
            $stmt->execute([':id' => $itemId]);
            $item = $stmt->fetch();

            if (!$item) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan!']);
                exit;
            }

            $currentStock = (float)$item['stock_current'];
            if ($type === 'OUT' && $quantity > $currentStock) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Sisa stok {$item['name']}: {$currentStock} {$item['unit']}"]);
                exit;
            }

            $deductedSummary = [];

            // ========================================================
            // LOGIC UTAMA: PRODUKSI PRODUK -> BAHAN BAKU OTOMATIS BERKURANG
            // ========================================================
            if ($type === 'IN' && $item['type'] === 'produk' && $autoDeduct) {
                // Fetch recipes/formula for this product
                $rStmt = $pdo->prepare("
                    SELECT r.*, rm.name as raw_name, rm.stock_current as raw_stock, rm.unit as raw_unit
                    FROM product_recipes r
                    JOIN inventory_items rm ON r.raw_material_id = rm.id
                    WHERE r.product_id = :pid
                ");
                $rStmt->execute([':pid' => $itemId]);
                $ingredients = $rStmt->fetchAll();

                if (!empty($ingredients)) {
                    // Check availability of each ingredient
                    $insufficient = [];
                    foreach ($ingredients as $ing) {
                        $needed = (float)$ing['amount_per_unit'] * $quantity;
                        if ((float)$ing['raw_stock'] < $needed) {
                            $insufficient[] = "{$ing['raw_name']} (dibutuhkan: {$needed} {$ing['raw_unit']}, tersedia: {$ing['raw_stock']} {$ing['raw_unit']})";
                        }
                    }

                    if (!empty($insufficient)) {
                        $pdo->rollBack();
                        echo json_encode([
                            'success' => false,
                            'message' => "Stok bahan baku tidak mencukupi untuk memproduksi {$quantity} {$item['unit']} {$item['name']}!\n\nKekurangan:\n• " . implode("\n• ", $insufficient)
                        ]);
                        exit;
                    }

                    // Deduct each raw material & record log
                    $deductStmt = $pdo->prepare("UPDATE inventory_items SET stock_current = stock_current - :needed, updated_at = CURRENT_TIMESTAMP WHERE id = :raw_id");
                    $rawMutStmt = $pdo->prepare("
                        INSERT INTO inventory_mutations (item_id, type, quantity, mutation_date, officer_name, destination_or_source, notes)
                        VALUES (:item_id, 'OUT', :quantity, :mutation_date, :officer_name, :destination, :notes)
                    ");

                    foreach ($ingredients as $ing) {
                        $needed = (float)$ing['amount_per_unit'] * $quantity;
                        $deductStmt->execute([':needed' => $needed, ':raw_id' => $ing['raw_material_id']]);
                        $rawMutStmt->execute([
                            ':item_id' => $ing['raw_material_id'],
                            ':quantity' => $needed,
                            ':mutation_date' => $date,
                            ':officer_name' => $officer,
                            ':destination' => "Unit Produksi Pakan BRMP DIY",
                            ':notes' => "Digunakan untuk produksi {$quantity} {$item['unit']} {$item['name']}"
                        ]);
                        $deductedSummary[] = "{$ing['raw_name']}: -{$needed} {$ing['raw_unit']}";
                    }
                }
            }

            // Update product/item stock
            $newStock = ($type === 'IN') ? ($currentStock + $quantity) : ($currentStock - $quantity);

            $mutStmt = $pdo->prepare("
                INSERT INTO inventory_mutations (item_id, type, quantity, mutation_date, officer_name, destination_or_source, notes, latitude, longitude, location_name)
                VALUES (:item_id, :type, :quantity, :mutation_date, :officer_name, :destination_or_source, :notes, :latitude, :longitude, :location_name)
            ");
            $mutStmt->execute([
                ':item_id' => $itemId,
                ':type' => $type,
                ':quantity' => $quantity,
                ':mutation_date' => $date,
                ':officer_name' => $officer,
                ':destination_or_source' => $destination,
                ':notes' => $notes,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':location_name' => $locationName ?: null
            ]);

            $upStmt = $pdo->prepare("UPDATE inventory_items SET stock_current = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upStmt->execute([':new_stock' => $newStock, ':id' => $itemId]);

            $pdo->commit();

            $newId = (int)$pdo->lastInsertId();

            if ($type === 'IN' && $item['type'] === 'produk' && !empty($deductedSummary)) {
                $msg = "Produksi Berhasil!\n\n+{$quantity} {$item['unit']} {$item['name']} (Stok baru: " . number_format($newStock, 0, ',', '.') . " {$item['unit']}).\n\nBahan Baku Terpotong Otomatis:\n• " . implode("\n• ", $deductedSummary);
            } else {
                $label = ($type === 'IN') ? 'Stok masuk berhasil dicatat' : 'Penyaluran / pemakaian stok berhasil dicatat';
                $msg = "$label! Sisa stok {$item['name']}: " . number_format($newStock, 0, ',', '.') . " {$item['unit']}.";
            }

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'mutation_id' => $newId,
                'item_id' => $itemId,
                'item_name' => $item['name'],
                'item_unit' => $item['unit'],
                'new_stock' => $newStock,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_name' => $locationName,
                'quantity' => $quantity,
                'destination' => $destination,
                'mutation_date' => $date
            ]);
            break;

        case 'delete_item':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            $del = $pdo->prepare("DELETE FROM inventory_items WHERE id = :id");
            $del->execute([':id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Item berhasil dihapus dari sistem!']);
            break;

        case 'update_item':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID item tidak valid']);
                exit;
            }

            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $targetStage = trim($_POST['target_stage'] ?? '');
            $unit = trim($_POST['unit'] ?? 'kg');
            $stockCurrent = (float)($_POST['stock_current'] ?? 0);
            $stockMinimum = (float)($_POST['stock_minimum'] ?? 10);
            $location = trim($_POST['storage_location'] ?? 'Gudang Utama BRMP DIY');
            $price = (int)($_POST['price_per_unit'] ?? 0);
            $imageUrl = trim($_POST['image_url'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $spec1Label = trim($_POST['spec_1_label'] ?? '');
            $spec1Value = trim($_POST['spec_1_value'] ?? '');
            $spec2Label = trim($_POST['spec_2_label'] ?? '');
            $spec2Value = trim($_POST['spec_2_value'] ?? '');
            $spec3Label = trim($_POST['spec_3_label'] ?? '');
            $spec3Value = trim($_POST['spec_3_value'] ?? '');

            if (empty($name) || empty($category)) {
                echo json_encode(['success' => false, 'message' => 'Nama dan Kategori wajib diisi!']);
                exit;
            }

            // Check duplicate code if changed
            if (!empty($code)) {
                $chk = $pdo->prepare("SELECT id FROM inventory_items WHERE code = :code AND id != :id");
                $chk->execute([':code' => $code, ':id' => $id]);
                if ($chk->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Kode barang sudah digunakan oleh item lain!']);
                    exit;
                }
            }

            $upd = $pdo->prepare("
                UPDATE inventory_items SET
                    name = :name,
                    code = :code,
                    category = :category,
                    target_stage = :target_stage,
                    unit = :unit,
                    stock_current = :stock_current,
                    stock_minimum = :stock_minimum,
                    storage_location = :storage_location,
                    price_per_unit = :price_per_unit,
                    image_url = :image_url,
                    spec_1_label = :spec_1_label,
                    spec_1_value = :spec_1_value,
                    spec_2_label = :spec_2_label,
                    spec_2_value = :spec_2_value,
                    spec_3_label = :spec_3_label,
                    spec_3_value = :spec_3_value,
                    description = :description,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $upd->execute([
                ':id' => $id,
                ':name' => $name,
                ':code' => $code,
                ':category' => $category,
                ':target_stage' => $targetStage,
                ':unit' => $unit,
                ':stock_current' => $stockCurrent,
                ':stock_minimum' => $stockMinimum,
                ':storage_location' => $location,
                ':price_per_unit' => $price,
                ':image_url' => $imageUrl,
                ':spec_1_label' => $spec1Label,
                ':spec_1_value' => $spec1Value,
                ':spec_2_label' => $spec2Label,
                ':spec_2_value' => $spec2Value,
                ':spec_3_label' => $spec3Label,
                ':spec_3_value' => $spec3Value,
                ':description' => $description
            ]);

            echo json_encode(['success' => true, 'message' => 'Data item berhasil diperbarui!']);
            break;

        case 'save_recipe':
            $productId = (int)($_POST['product_id'] ?? 0);
            $ingredients = $_POST['ingredients'] ?? []; // Array of [raw_material_id => amount]

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Produk tidak valid!']);
                exit;
            }

            $pdo->beginTransaction();
            // Delete old recipe
            $del = $pdo->prepare("DELETE FROM product_recipes WHERE product_id = :pid");
            $del->execute([':pid' => $productId]);

            // Insert new recipe lines
            $ins = $pdo->prepare("INSERT INTO product_recipes (product_id, raw_material_id, amount_per_unit) VALUES (:pid, :raw_id, :amount)");
            
            if (is_array($ingredients)) {
                foreach ($ingredients as $ing) {
                    $rawId = (int)($ing['raw_material_id'] ?? 0);
                    $amount = (float)($ing['amount_per_unit'] ?? 0);
                    if ($rawId > 0 && $amount > 0) {
                        $ins->execute([':pid' => $productId, ':raw_id' => $rawId, ':amount' => $amount]);
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Resep formula produksi berhasil disimpan!']);
            break;

        case 'quick_stock_adjust':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $delta = (float)($_POST['delta'] ?? 0); // Positive or negative
            $reason = trim($_POST['reason'] ?? 'Penyesuaian stok cepat');

            if (!$itemId || $delta == 0) {
                echo json_encode(['success' => false, 'message' => 'Data penyesuaian tidak valid!']);
                exit;
            }

            $item = $pdo->prepare("SELECT id, name, unit, stock_current FROM inventory_items WHERE id = :id");
            $item->execute([':id' => $itemId]);
            $it = $item->fetch();

            if (!$it) {
                echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan!']);
                exit;
            }

            $newStock = (float)$it['stock_current'] + $delta;
            if ($newStock < 0) {
                echo json_encode(['success' => false, 'message' => "Stok tidak boleh bernilai negatif! (Sisa: {$it['stock_current']} {$it['unit']})"]);
                exit;
            }

            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE inventory_items SET stock_current = :new_stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $up->execute([':new_stock' => $newStock, ':id' => $itemId]);

            $type = ($delta > 0) ? 'IN' : 'OUT';
            $qty = abs($delta);
            $mut = $pdo->prepare("
                INSERT INTO inventory_mutations (item_id, type, quantity, mutation_date, officer_name, destination_or_source, notes)
                VALUES (:item_id, :type, :quantity, :mdate, 'Petugas BRMP DIY', :dest, :notes)
            ");
            $mut->execute([
                ':item_id' => $itemId,
                ':type' => $type,
                ':quantity' => $qty,
                ':mdate' => date('Y-m-d'),
                ':dest' => 'Penyesuaian Inventaris Cepat',
                ':notes' => $reason
            ]);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => "Stok {$it['name']} berhasil disesuaikan! Stok baru: " . number_format($newStock, 0, ',', '.') . " {$it['unit']}",
                'new_stock' => $newStock
            ]);
            break;

        case 'get_stats':
            $statsBahanBaku = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN stock_current <= stock_minimum THEN 1 ELSE 0 END) as critical FROM inventory_items WHERE type = 'bahan_baku'")->fetch();
            $statsProduk = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN stock_current <= stock_minimum THEN 1 ELSE 0 END) as critical FROM inventory_items WHERE type = 'produk'")->fetch();

            echo json_encode([
                'success' => true,
                'bahan_baku' => $statsBahanBaku,
                'produk' => $statsProduk
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
