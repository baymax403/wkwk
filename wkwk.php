<?php
// Set memory limit lebih tinggi jika diizinkan
ini_set('memory_limit', '2048M');

// Fungsi untuk format brand name
function formatBrandHyphenated($brand) {
    return strtolower(str_replace(' ', '-', trim($brand)));
}

// Fungsi untuk mendapatkan URL saat ini
function urlPath() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $domain = $_SERVER['HTTP_HOST'];
    $requestUri = $_SERVER['REQUEST_URI'];
    // Menghapus query string jika ada
    $path = strtok($requestUri, '?');
    return $protocol . '://' . $domain . $path;
}

// Fungsi untuk generate angka konsisten
function generateConsistentIndex($target_string, $max = 999999) {
    return mt_rand(30000, $max);
}

function generateConsistentNumber2($target_string) {
    return (abs(crc32($target_string)) % 20) + 1;
}

function generateConsistentNumber3($target_string) {
    return (abs(crc32($target_string)) % 91) + 1;
}

function generateConsistentNumber4($target_string) {
    return (abs(crc32($target_string)) % 26) + 1;
}

// Fungsi untuk mengganti NEOPHAGUS dan UNITY
function replaceNeophagusAndUnity($content, $brandName, $slug) {
    $uppercaseBrand = strtoupper($brandName);
    $baseUrl = urlPath();
    $fullUrl = $baseUrl . $slug . '/';
    return str_replace(['NEOPHAGUS', 'UNITY'], [$uppercaseBrand, $fullUrl], $content);
}

// Fungsi untuk menulis log
function writeLog($message) {
    file_put_contents('process.log', date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

// Fungsi untuk mendapatkan file yang belum diproses
function getNextUnprocessedFile() {
    // Logika untuk mengambil file yang belum diproses
    return 'file.json'; // Contoh placeholder, ganti dengan implementasi sesuai kebutuhan Anda
}

// Fungsi untuk memproses file JSON
function processJsonFile($file) {
    // Logika untuk memproses file JSON
    return true; // Contoh, return true jika berhasil memproses
}

// Fungsi untuk memperbarui status file
function updateFileStatus($file, $status) {
    // Logika untuk memperbarui status file
    writeLog("Status file $file diubah menjadi $status");
}

// Fungsi untuk mengecek apakah semua file telah diproses
function areAllFilesProcessed() {
    // Logika untuk mengecek apakah semua file telah diproses
    return false; // Placeholder, ganti dengan logika pengecekan yang sesuai
}

// Cek argumen baris perintah untuk mode background
if (isset($argv[1]) && $argv[1] === 'background') {
    // Proses background
    while ($nextFile = getNextUnprocessedFile()) {
        updateFileStatus($nextFile, 'processing');
        
        if (processJsonFile($nextFile)) {
            updateFileStatus($nextFile, 'completed');
        } else {
            updateFileStatus($nextFile, 'failed');
        }
        
        // Delay antar proses file (opsional)
        sleep(1);
    }
    
    writeLog("Semua file JSON telah diproses");
    exit(0);
}

// Handler untuk request HTTP
if (!areAllFilesProcessed()) {
    $lockFile = 'import.lock';
    
    // Cek apakah proses lain sudah berjalan
    if (!file_exists($lockFile)) {
        // Buat lock file agar tidak ada proses duplikat
        file_put_contents($lockFile, getmypid());
        
        // Menjalankan proses background menggunakan exec
        $cmd = "php " . __FILE__ . " background >> background_process.log 2>&1 &";
        exec($cmd);
        
        echo json_encode([
            'status' => 'started',
            'message' => 'Memulai proses impor file JSON'
        ]);
    } else {
        echo json_encode([
            'status' => 'running',
            'message' => 'Proses impor sedang berjalan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'completed',
        'message' => 'Semua file JSON telah diproses'
    ]);
}

// Mengambil data brands dari file JSON dan template HTML
$brands = json_decode(file_get_contents('brands.json'), true);
if (!$brands) {
    echo "Error: Gagal membaca file brands.json!\n";
    exit;
}

$template = file_get_contents('landingpage.html');
if (!$template) {
    echo "Error: Template file landingpage.html tidak ditemukan!\n";
    exit;
}

// Memproses setiap brand untuk membuat folder dan file index.php
foreach ($brands as $row) {
    $slug = $row['slug'];
    
    // Cek apakah folder sudah ada
    if (file_exists($slug)) {
        echo "Folder $slug sudah ada, melanjutkan ke folder berikutnya...\n";
        continue; // Skip folder yang sudah ada
    }

    // Membuat folder berdasarkan slug
    if (!mkdir($slug, 0777, true)) {
        echo "Error: Gagal membuat folder untuk slug: $slug\n";
        exit;
    }
    echo "Folder dibuat: $slug\n";

    // Memproses template dengan data dari JSON
    $content = $template;

    // Mengganti NEOPHAGUS dan UNITY di setiap nilai sebelum replacement
    $processedRow = array_map(function($value) use ($row) {
        return replaceNeophagusAndUnity($value, $row['brand'], $row['slug']);
    }, $row);

    // Mengganti placeholder dengan data yang sudah diproses
    $replacements = [
        '{{titleContent}}' => $processedRow['title'],
        '{{titleContent2}}' => $processedRow['title_content2'],
        '{{titleContent3}}' => $processedRow['title_content3'],
        '{{titleContent4}}' => $processedRow['title_content4'],
        '{{metadesContent}}' => $processedRow['meta_description'],
        '{{paragraf2Content}}' => $processedRow['meta_description2'],
        '{{paragraf3Content}}' => $processedRow['meta_description3'],
        '{{paragraf4Content}}' => $processedRow['meta_description4'],
        '{{paragraf5Content}}' => $processedRow['meta_description5'],
        '{{paragraf6Content}}' => $processedRow['meta_description6'],
        '{{paragraf7Content}}' => $processedRow['meta_description7'],
        '{{BRAND1}}' => $processedRow['brand'],
        '{{randomUrl1}}' => $processedRow['internalLink'],
        '{{randomUrl2}}' => $processedRow['internalLink2'],
        '{{randomUrl3}}' => $processedRow['internalLink3'],
        '{{randomUrl4}}' => $processedRow['internalLink4'],
        '{{randomUrl5}}' => $processedRow['internalLink5'],
        '{{randomUrl6}}' => $processedRow['internalLink6'],
        '{{randomUrl7}}' => $processedRow['internalLink7'],
        '{{randomKeyword}}' => $processedRow['internalLinkBrand'],
        '{{randomKeyword2}}' => $processedRow['internalLinkBrand2'],
        '{{randomKeyword3}}' => $processedRow['internalLinkBrand3'],
        '{{randomKeyword4}}' => $processedRow['internalLinkBrand4'],
        '{{randomKeyword5}}' => $processedRow['internalLinkBrand5'],
        '{{randomKeyword6}}' => $processedRow['internalLinkBrand6'],
        '{{randomKeyword7}}' => $processedRow['internalLinkBrand7'],
        '{{randomNumber}}' => generateConsistentIndex($row['slug']),
        '{{randomNumber2}}' => generateConsistentNumber2($row['slug']),
        '{{randomNumber3}}' => generateConsistentNumber3($row['slug']),
        '{{randomNumber4}}' => generateConsistentNumber4($row['slug']),
        '{{urlPath}}' => urlPath(),
        '{{brandHyphenated}}' => formatBrandHyphenated($row['brand'])
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    // Menyimpan file index.php
    $filename = $slug . '/index.php';
    if (file_put_contents($filename, $content) === false) {
        echo "Error: Gagal menulis file: $filename\n";
        exit;
    }
    echo "File dibuat: $filename\n";

    // Membersihkan variabel untuk menghemat memori
    unset($content);
    unset($replacements);
    unset($processedRow);
}

writeLog("Proses selesai!\n");
?>
