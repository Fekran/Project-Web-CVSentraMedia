<?php
// db_backup_restore.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Restricted to Admin
check_role(['Admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'backup') {
    try {
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql_dump = "-- Database Backup\n";
        $sql_dump .= "-- Toko Bangunan Inventory System\n";
        $sql_dump .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop table query
            $sql_dump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            
            // Show Create Table
            $create_stmt = $pdo->query("SHOW CREATE TABLE `" . $table . "`");
            $create_row = $create_stmt->fetch(PDO::FETCH_NUM);
            $sql_dump .= $create_row[1] . ";\n\n";

            // Get Table Data
            $data_stmt = $pdo->query("SELECT * FROM `" . $table . "`");
            $column_count = $data_stmt->columnCount();

            while ($row = $data_stmt->fetch(PDO::FETCH_NUM)) {
                $sql_dump .= "INSERT INTO `" . $table . "` VALUES(";
                for ($j = 0; $j < $column_count; $j++) {
                    if (isset($row[$j])) {
                        // Escape quotes
                        $val = str_replace("\n", "\\n", addslashes($row[$j]));
                        $sql_dump .= '"' . $val . '"';
                    } else {
                        $sql_dump .= 'NULL';
                    }
                    if ($j < ($column_count - 1)) {
                        $sql_dump .= ',';
                    }
                }
                $sql_dump .= ");\n";
            }
            $sql_dump .= "\n\n";
        }
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Download Headers
        $filename = "backup_db_inventory_" . date('Ymd_His') . ".sql";
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=$filename");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $sql_dump;
        exit;
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal membackup database: ' . $e->getMessage());
        header("Location: ../pages/pengaturan.php");
        exit;
    }
} 

elseif ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/pengaturan.php");
        exit;
    }

    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('error', 'Gagal mengupload file backup.');
        header("Location: ../pages/pengaturan.php");
        exit;
    }

    $file_path = $_FILES['backup_file']['tmp_name'];
    $sql_queries = file_get_contents($file_path);

    try {
        // We will execute the query block
        $pdo->beginTransaction();
        
        // Disable foreign keys check
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Execute backup contents
        $pdo->exec($sql_queries);
        
        // Enable foreign keys check
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        $pdo->commit();
        set_flash_message('success', 'Database berhasil di-restore!');
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal merestore database: ' . $e->getMessage());
    }

    header("Location: ../pages/pengaturan.php");
    exit;
} else {
    header("Location: ../pages/pengaturan.php");
    exit;
}
?>
