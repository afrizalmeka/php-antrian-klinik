<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';

$pdo = getDB();
$today = date('Y-m-d');

if (!empty($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['menunggu', 'dipanggil', 'selesai', 'batal'];
    if ($id > 0 && in_array($status, $allowed)) {
        $pdo->prepare("UPDATE antrian SET status = ? WHERE id = ?")->execute([$status, $id]);
        // BUG 3: Flash message tidak diset — tidak ada feedback sukses ke user
    }
    header('Location: index.php');
    exit;
}

// BUG 4: Query tidak difilter per tanggal — menampilkan semua antrian dari semua hari
$antrian = $pdo->query("SELECT a.*, d.name AS dokter_name FROM antrian a LEFT JOIN dokter d ON a.dokter_id = d.id ORDER BY a.nomor_antrian");
$antrianList = $antrian->fetchAll();

$stats = ['menunggu' => 0, 'dipanggil' => 0, 'selesai' => 0, 'batal' => 0];
foreach ($antrianList as $a) { $stats[$a['status']] = ($stats[$a['status']] ?? 0) + 1; }

$pageTitle = 'Antrian Hari Ini — KliniKu';
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
        <h1>🏥 Antrian Hari Ini — <?= date('d/m/Y') ?></h1>
        <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="daftar_antrian.php" class="btn btn-primary">+ Daftar Pasien</a>
        <?php else: ?>
        <a href="login.php" class="btn btn-secondary">Masuk sebagai Petugas</a>
        <?php endif; ?>
    </div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-label">Menunggu</div><div class="stat-value" style="color:#856404"><?= $stats['menunggu'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Dipanggil</div><div class="stat-value" style="color:#0d6efd"><?= $stats['dipanggil'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value" style="color:#198754"><?= $stats['selesai'] ?></div></div>
        <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= count($antrianList) ?></div></div>
    </div>

    <?php if (empty($antrianList)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:3rem;">Belum ada antrian.</div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>No</th><th>Nama Pasien</th><th>Keluhan</th><th>Dokter</th><th>Tanggal</th><th>Status</th>
                <?php if (!empty($_SESSION['user_id'])): ?><th>Update</th><?php endif; ?>
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
                        <form method="post" style="display:flex;gap:.3rem;">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <select name="status" style="padding:.3rem;border:1px solid #dee2e6;border-radius:4px;font-size:.85rem;">
                                <?php foreach ($statusLabel as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $a['status'] === $val ? 'selected' : '' ?>><?= $lbl['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">OK</button>
                        </form>
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
