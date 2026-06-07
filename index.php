<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';

$pdo = getDB();
$today = date('Y-m-d');

// Handle POST: update status atau hapus antrian
if (!empty($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'update_status';
    $tgl    = $_POST['tanggal_filter'] ?? $today;

    if ($action === 'hapus' && $id > 0) {
        // Hanya boleh hapus antrian yang sudah selesai atau batal
        $cek = $pdo->prepare("SELECT status FROM antrian WHERE id = ?");
        $cek->execute([$id]);
        $row = $cek->fetch();
        if ($row && in_array($row['status'], ['selesai', 'batal'])) {
            $pdo->prepare("DELETE FROM antrian WHERE id = ?")->execute([$id]);
        }
    } else {
        // Update status
        $status  = $_POST['status'] ?? '';
        $allowed = ['menunggu', 'dipanggil', 'selesai', 'batal'];
        if ($id > 0 && in_array($status, $allowed)) {
            $pdo->prepare("UPDATE antrian SET status = ? WHERE id = ?")->execute([$status, $id]);
        }
    }

    header('Location: index.php?tanggal=' . urlencode($tgl));
    exit;
}

// Filter tanggal: default hari ini, bisa dipilih via GET
$tanggalFilter = $_GET['tanggal'] ?? $today;
// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalFilter)) {
    $tanggalFilter = $today;
}

// Ambil antrian sesuai tanggal yang dipilih
$stmt = $pdo->prepare("SELECT a.*, d.name AS dokter_name FROM antrian a LEFT JOIN dokter d ON a.dokter_id = d.id WHERE a.tanggal = ? ORDER BY a.nomor_antrian");
$stmt->execute([$tanggalFilter]);
$antrianList = $stmt->fetchAll();

$stats = ['menunggu' => 0, 'dipanggil' => 0, 'selesai' => 0, 'batal' => 0];
foreach ($antrianList as $a) { $stats[$a['status']] = ($stats[$a['status']] ?? 0) + 1; }

$isToday = ($tanggalFilter === $today);
$judulTanggal = $isToday ? 'Hari Ini — ' . date('d/m/Y') : date('d/m/Y', strtotime($tanggalFilter));
$pageTitle = 'Antrian ' . ($isToday ? 'Hari Ini' : $judulTanggal) . ' — KliniKu';
include __DIR__ . '/php/header.php';
$statusLabel = [
    'menunggu'  => ['label' => 'Menunggu',  'class' => 'badge-warning'],
    'dipanggil' => ['label' => 'Dipanggil', 'class' => 'badge-info'],
    'selesai'   => ['label' => 'Selesai',   'class' => 'badge-success'],
    'batal'     => ['label' => 'Batal',     'class' => 'badge-danger'],
];
?>
<div class="container">
    <div class="page-header">
        <h1>🏥 Antrian <?= htmlspecialchars($judulTanggal) ?></h1>
        <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="daftar_antrian.php" class="btn btn-primary">+ Daftar Pasien</a>
        <?php else: ?>
        <a href="login.php" class="btn btn-secondary">Masuk sebagai Petugas</a>
        <?php endif; ?>
    </div>

    <!-- Filter Tanggal -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;padding:.75rem 1rem;">
            <span style="font-weight:600;color:#495057;">📅 Pilih Tanggal:</span>
            <form method="get" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggalFilter) ?>"
                    max="<?= $today ?>"
                    style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.9rem;">
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                <?php if (!$isToday): ?>
                <a href="index.php" class="btn btn-secondary btn-sm">⟵ Kembali ke Hari Ini</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-label">Menunggu</div><div class="stat-value" style="color:#856404"><?= $stats['menunggu'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Dipanggil</div><div class="stat-value" style="color:#0d6efd"><?= $stats['dipanggil'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value" style="color:#198754"><?= $stats['selesai'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= count($antrianList) ?></div></div>
    </div>

    <?php if (empty($antrianList)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:3rem;color:#6c757d;">
            Tidak ada antrian untuk tanggal <strong><?= date('d/m/Y', strtotime($tanggalFilter)) ?></strong>.
        </div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>No</th><th>Nama Pasien</th><th>Keluhan</th><th>Dokter</th><th>Tanggal</th><th>Status</th>
                <?php if (!empty($_SESSION['user_id'])): ?><th>Update</th><th>Aksi</th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($antrianList as $a): ?>
                <?php $sl = $statusLabel[$a['status']] ?? ['label'=>$a['status'],'class'=>'badge-secondary']; ?>
                <tr>
                    <td><strong style="font-size:1.2rem;">#<?= $a['nomor_antrian'] ?></strong></td>
                    <td><?= htmlspecialchars($a['nama_pasien']) ?></td>
                    <td><?= htmlspecialchars($a['keluhan']) ?></td>
                    <td><?= htmlspecialchars($a['dokter_name'] ?? 'Umum') ?></td>
                    <td><?= $a['tanggal'] ?></td>
                    <td><span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span></td>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                    <td>
                        <!-- Form update status -->
                        <form method="post" style="display:flex;gap:.3rem;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="tanggal_filter" value="<?= htmlspecialchars($tanggalFilter) ?>">
                            <select name="status" style="padding:.3rem;border:1px solid #dee2e6;border-radius:4px;font-size:.85rem;">
                                <?php foreach ($statusLabel as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $a['status'] === $val ? 'selected' : '' ?>><?= $lbl['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">OK</button>
                        </form>
                    </td>
                    <td>
                        <!-- Tombol Hapus: hanya muncul jika status selesai atau batal -->
                        <?php if (in_array($a['status'], ['selesai', 'batal'])): ?>
                        <form method="post" onsubmit="return confirm('Hapus antrian #<?= $a['nomor_antrian'] ?> (<?= htmlspecialchars($a['nama_pasien']) ?>)?')">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="tanggal_filter" value="<?= htmlspecialchars($tanggalFilter) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#adb5bd;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
