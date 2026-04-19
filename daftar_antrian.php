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
    $nama    = trim($_POST['nama_pasien'] ?? '');
    $tglLahir = trim($_POST['tanggal_lahir'] ?? '');
    $telp    = trim($_POST['no_telp'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $dokterId = (int)($_POST['dokter_id'] ?? 0);

    // BUG 5: Validasi nama dan keluhan hilang — pasien bisa didaftarkan dengan nama kosong
    // BUG 6: Nomor antrian dihitung dari SEMUA tanggal, bukan hanya hari ini
    // sehingga nomor antrian hari ini selalu meneruskan dari hari sebelumnya
    $stmt = $pdo->query("SELECT COALESCE(MAX(nomor_antrian), 0) + 1 FROM antrian");
    $nomorAntrian = (int)$stmt->fetchColumn();

    $pdo->prepare("INSERT INTO antrian (nomor_antrian, nama_pasien, tanggal_lahir, no_telp, keluhan, dokter_id, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$nomorAntrian, $nama, $tglLahir ?: null, $telp ?: null, $keluhan, $dokterId ?: null, $today]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => "Pasien didaftarkan. Nomor antrian: #$nomorAntrian"];
    header('Location: index.php');
    exit;
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
                    <input type="text" name="nama_pasien" value="<?= htmlspecialchars($_POST['nama_pasien'] ?? '') ?>" required></div>
                <div class="form-group"><label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>"></div>
                <div class="form-group"><label>No. Telepon</label>
                    <input type="text" name="no_telp" value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>"></div>
                <div class="form-group"><label>Keluhan <span style="color:red">*</span></label>
                    <textarea name="keluhan" rows="3" required><?= htmlspecialchars($_POST['keluhan'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Pilih Dokter</label>
                    <select name="dokter_id">
                        <option value="">-- Dokter Umum --</option>
                        <?php foreach ($dokterList as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['spesialisasi']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Daftarkan Pasien</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
