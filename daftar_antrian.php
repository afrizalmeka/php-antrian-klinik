<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();
$error = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_pasien'] ?? '');
    $tglLahir = trim($_POST['tanggal_lahir'] ?? '');
    $telp     = trim($_POST['no_telp'] ?? '');
    $keluhan  = trim($_POST['keluhan'] ?? '');
    $dokterId = (int)($_POST['dokter_id'] ?? 0);

    // Validasi 1: field wajib tidak boleh kosong
    if ($nama === '') {
        $error = 'Nama pasien wajib diisi.';
    } elseif ($keluhan === '') {
        $error = 'Keluhan wajib diisi.';
    }
    // Validasi 2: nama hanya boleh huruf, spasi, titik, dan tanda hubung
    elseif (!preg_match('/^[\p{L}\s.\'-]+$/u', $nama)) {
        $error = 'Nama pasien tidak boleh mengandung karakter khusus (angka atau simbol).';
    }
    // Validasi 3: keluhan hanya boleh huruf, angka, spasi, dan tanda baca umum
    elseif (!preg_match('/^[\p{L}\p{N}\s.,\-()]+$/u', $keluhan)) {
        $error = 'Keluhan tidak boleh mengandung karakter khusus seperti @, #, $, !, dll.';
    }
    // Validasi 4: nomor telepon hanya boleh angka, +, -, spasi
    elseif ($telp !== '' && !preg_match('/^[\d\s\+\-]+$/', $telp)) {
        $error = 'No. telepon hanya boleh berisi angka, +, atau -.';
    }

    if ($error === '') {
        // Nomor antrian reset per hari
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(nomor_antrian), 0) + 1 FROM antrian WHERE tanggal = ?");
        $stmt->execute([$today]);
        $nomorAntrian = (int)$stmt->fetchColumn();

        $pdo->prepare("INSERT INTO antrian (nomor_antrian, nama_pasien, tanggal_lahir, no_telp, keluhan, dokter_id, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$nomorAntrian, $nama, $tglLahir ?: null, $telp ?: null, $keluhan, $dokterId ?: null, $today]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Pasien didaftarkan. Nomor antrian: #$nomorAntrian"];
        header('Location: index.php');
        exit;
    }
}

$dokterList = $pdo->query("SELECT * FROM dokter WHERE aktif = 1 ORDER BY name")->fetchAll();

$pageTitle = 'Daftar Pasien — KliniKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>📋 Daftar Pasien Baru</h1><a href="index.php" class="btn btn-secondary">← Kembali</a></div>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card" style="max-width:600px;">
        <div class="card-header">Form Pendaftaran Pasien</div>
        <div class="card-body">
            <form method="post">
                <div class="form-group"><label>Nama Pasien <span style="color:red">*</span></label>
                    <input type="text" name="nama_pasien"
                        value="<?= htmlspecialchars($_POST['nama_pasien'] ?? '') ?>"
                        placeholder="Contoh: Budi Santoso"
                        pattern="[\p{L}\s.\'\-]+" required></div>

                <div class="form-group"><label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>"></div>

                <div class="form-group"><label>No. Telepon</label>
                    <input type="text" name="no_telp"
                        value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>"
                        placeholder="Contoh: 08123456789"
                        pattern="[\d\s\+\-]+"></div>

                <div class="form-group"><label>Keluhan <span style="color:red">*</span></label>
                    <textarea name="keluhan" rows="3"
                        placeholder="Contoh: Demam, batuk, sakit kepala"
                        required><?= htmlspecialchars($_POST['keluhan'] ?? '') ?></textarea></div>

                <div class="form-group"><label>Pilih Dokter</label>
                    <select name="dokter_id">
                        <option value="">-- Dokter Umum --</option>
                        <?php foreach ($dokterList as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int)($_POST['dokter_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['spesialisasi']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="font-size:.8rem;color:#6c757d;margin-bottom:1rem;">
                    <strong>Catatan:</strong> Nama dan keluhan tidak boleh mengandung karakter khusus (@, #, $, !, dll).
                </div>

                <button type="submit" class="btn btn-primary">Daftarkan Pasien</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
