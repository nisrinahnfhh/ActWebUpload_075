<?php
$dir = "uploads/";

// Pastikan direktori uploads ada
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// --- 1. FITUR UNTUK MELAYANI DAFTAR BERKAS & HAPUS (UNTUK INDEX.HTML) ---
if (isset($_GET['aksi'])) {
    
    // Aksi: List semua file
    if ($_GET['aksi'] == 'list') {
        header('Content-Type: application/json; charset=utf-8');
        $fileList = [];
        
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            
            foreach ($files as $file) {
                $filePath = $dir . $file;
                
                // Skip jika bukan file
                if (!is_file($filePath)) {
                    continue;
                }
                
                $ext = strtoupper(pathinfo($filePath, PATHINFO_EXTENSION));
                $sizeBytes = filesize($filePath);
                
                // Format ukuran file
                if ($sizeBytes > 1048576) {
                    $sizeKb = round($sizeBytes / 1048576, 2) . " MB";
                } else {
                    $sizeKb = round($sizeBytes / 1024, 2) . " KB";
                }
                
                $fileList[] = [
                    "nama" => htmlspecialchars($file),
                    "tipe" => $ext,
                    "ukuran" => $sizeKb,
                    "waktu" => date("d/m/Y H:i", filemtime($filePath))
                ];
            }
            
            // Urutkan berdasarkan waktu upload terbaru
            usort($fileList, function($a, $b) {
                return strtotime($b['waktu']) - strtotime($a['waktu']);
            });
        }
        
        echo json_encode($fileList);
        exit;
    }
    
    // Aksi: Hapus file
    if ($_GET['aksi'] == 'hapus' && isset($_GET['nama'])) {
        $fileHapus = basename($_GET['nama']);
        $filePath = $dir . $fileHapus;
        
        // Validasi: pastikan file ada di direktori uploads
        if (realpath($filePath) && strpos(realpath($filePath), realpath($dir)) === 0) {
            if (file_exists($filePath) && is_file($filePath)) {
                if (unlink($filePath)) {
                    echo json_encode(["status" => "success", "message" => "File berhasil dihapus"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Gagal menghapus file"]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "File tidak ditemukan"]);
            }
        } else {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Akses ditolak"]);
        }
        exit;
    }
}

// --- 2. LOGIKA UNTUK PROSES UPLOAD BERKAS ---

// Periksa apakah ada file yang diunggah
if (!isset($_FILES["fileToUpload"]) || $_FILES["fileToUpload"]["error"] != UPLOAD_ERR_OK) {
    if (isset($_POST['submit'])) {
        echo "<script>alert('Tidak ada file yang dipilih atau terjadi kesalahan upload.'); window.location.href='index.html';</script>";
    }
    exit;
}

$fileName = basename($_FILES["fileToUpload"]["name"]);
$target_file = $dir . $fileName;
$uploadOk = 1;
$errorMessage = "";

// Periksa ekstensi file yang diizinkan
$allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc', 'txt');
$fileExtension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    $errorMessage = "Tipe file tidak diperbolehkan. Hanya: JPG, JPEG, PNG, GIF, PDF, DOCX, DOC, TXT";
    $uploadOk = 0;
}

// Periksa ukuran file (500KB)
if ($_FILES["fileToUpload"]["size"] > 500000) {
    $errorMessage = "File terlalu besar. Ukuran maksimal: 500KB";
    $uploadOk = 0;
}

// Periksa apakah file sudah ada
if (file_exists($target_file)) {
    $errorMessage = "File dengan nama yang sama sudah ada. Silakan ubah nama file.";
    $uploadOk = 0;
}

// Validasi MIME type untuk file gambar
if ($uploadOk && in_array($fileExtension, array('jpg', 'jpeg', 'png', 'gif'))) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES["fileToUpload"]["tmp_name"]);
    finfo_close($finfo);
    
    $allowedMimes = array('image/jpeg', 'image/png', 'image/gif');
    
    if (!in_array($mimeType, $allowedMimes)) {
        $errorMessage = "File yang diunggah bukan gambar yang valid.";
        $uploadOk = 0;
    }
}

// Proses upload
if ($uploadOk == 0) {
    echo "<script>alert('Gagal mengunggah: " . addslashes($errorMessage) . "'); window.location.href='index.html';</script>";
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "<script>alert('✓ File berhasil diunggah!'); window.location.href='index.html';</script>";
    } else {
        echo "<script>alert('Gagal memindahkan file. Periksa permission folder.'); window.location.href='index.html';</script>";
    }
}
?>