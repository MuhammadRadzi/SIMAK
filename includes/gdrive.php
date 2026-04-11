<?php
if (!defined("GDRIVE_CREDENTIALS")) require_once __DIR__ . "/../config/config.php";
if (!defined("GDRIVE_CREDENTIALS")) require_once __DIR__ . "/../config/google-drive.php";
// ============================================================
//  SIMAK — Google Drive Helper
//  Membutuhkan: composer require google/apiclient
// ============================================================

function getGDriveClient(): Google\Client {
    $client = new Google\Client();
    $client->setAuthConfig(GDRIVE_CREDENTIALS);
    $client->addScope(Google\Service\Drive::DRIVE);
    $client->setApplicationName(APP_NAME);
    return $client;
}

function getGDriveService(): Google\Service\Drive {
    return new Google\Service\Drive(getGDriveClient());
}

/**
 * Ambil ID folder dari settings DB
 */
function getGDriveFolderId(string $modul): string {
    $db  = getDB();
    $key = 'gdrive_folder_' . $modul;
    $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['value'] ?? '';
}

/**
 * Upload file ke Google Drive
 * @return array ['file_id' => string, 'link' => string] | ['error' => string]
 */
function uploadToGDrive(string $localPath, string $fileName, string $modul): array {
    if (!file_exists(GDRIVE_CREDENTIALS)) {
        return ['error' => 'File kredensial Google Drive tidak ditemukan.'];
    }

    $folderId = getGDriveFolderId($modul);
    if (empty($folderId)) {
        return ['error' => 'Folder Google Drive untuk modul ' . $modul . ' belum dikonfigurasi. Hubungi Super Admin.'];
    }

    try {
        require_once BASE_PATH . '/vendor/autoload.php';

        $service = getGDriveService();

        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name'    => $fileName,
            'parents' => [$folderId],
        ]);

        $content = file_get_contents($localPath);
        $file    = $service->files->create($fileMetadata, [
            'data'              => $content,
            'mimeType'          => 'application/pdf',
            'uploadType'        => 'multipart',
            'fields'            => 'id, webViewLink',
            'supportsAllDrives' => true,
        ]);

        // Set permission agar bisa dibuka via link
        // Untuk Shared Drive, permission diinherit dari folder — error diabaikan
        try {
            $permission = new Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $service->permissions->create($file->id, $permission, [
                'supportsAllDrives' => true,
            ]);
        } catch (Exception $permErr) {
            error_log('[SIMAK GDrive Permission] ' . $permErr->getMessage());
        }

        return [
            'file_id' => $file->id,
            'link'    => $file->webViewLink,
        ];

    } catch (Exception $e) {
        error_log('[SIMAK GDrive] ' . $e->getMessage());
        return ['error' => 'Upload ke Google Drive gagal: ' . $e->getMessage()];
    }
}

/**
 * Hapus file dari Google Drive
 */
function deleteFromGDrive(string $fileId): bool {
    if (empty($fileId) || !file_exists(GDRIVE_CREDENTIALS)) return false;
    try {
        require_once BASE_PATH . '/vendor/autoload.php';
        getGDriveService()->files->delete($fileId);
        return true;
    } catch (Exception $e) {
        error_log('[SIMAK GDrive Delete] ' . $e->getMessage());
        return false;
    }
}

/**
 * Handle upload PDF: validasi → simpan temp → upload Drive → return result
 */
function handlePdfUpload(string $inputName, string $modul, string $prefix = ''): array {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['skipped' => true];
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal upload file. Kode error: ' . $file['error']];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['error' => 'Ukuran file melebihi batas maksimal (10 MB).'];
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        return ['error' => 'Hanya file PDF yang diperbolehkan.'];
    }

    $safeName  = $prefix . '_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $localPath = UPLOAD_DIR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $localPath)) {
        return ['error' => 'Gagal menyimpan file sementara.'];
    }

    $result = uploadToGDrive($localPath, $safeName, $modul);

    // Hapus file temp
    if (file_exists($localPath)) unlink($localPath);

    if (isset($result['error'])) return $result;

    return [
        'nama_file'      => $file['name'],
        'gdrive_file_id' => $result['file_id'],
        'gdrive_link'    => $result['link'],
        'ukuran_file'    => $file['size'],
    ];
}
