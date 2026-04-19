<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireAdmin();

$pdo = getDB();
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sp   = trim($_POST['spesialisasi'] ?? '');
        $jadwal = trim($_POST['jadwal'] ?? '');
        if ($name === '' || $sp === '') { $error = 'Nama dan spesialisasi wajib diisi.'; }
        else {
            $pdo->prepare("INSERT INTO dokter (name, spesialisasi, jadwal) VALUES (?,?,?)")->execute([$name, $sp, $jadwal]);
            $msg = 'Dokter berhasil ditambahkan.';
        }
    } elseif ($act === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sp   = trim($_POST['spesialisasi'] ?? '');
        $jadwal = trim($_POST['jadwal'] ?? '');
        $aktif = (int)($_POST['aktif'] ?? 1);
        if ($name === '' || $sp === '') { $error = 'Nama dan spesialisasi wajib diisi.'; }
        else {
            $pdo->prepare("UPDATE dokter SET name=?, spesialisasi=?, jadwal=?, aktif=? WHERE id=?")->execute([$name, $sp, $jadwal, $aktif, $id]);
            $msg = 'Data dokter diperbarui.';
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE dokter SET aktif = 0 WHERE id = ?")->execute([$id]);
        $msg = 'Dokter dinonaktifkan.';
    }
}

$dokterList = $pdo->query("SELECT * FROM dokter ORDER BY aktif DESC, name")->fetchAll();
$editDokter = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM dokter WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editDokter = $stmt->fetch();
}

$pageTitle = 'Kelola Dokter — KliniKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Dokter</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header"><?= $editDokter ? 'Edit Dokter' : 'Tambah Dokter' ?></div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr <?= $editDokter ? '100px' : '' ?> auto;gap:.75rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editDokter ? 'edit' : 'add' ?>">
                <?php if ($editDokter): ?><input type="hidden" name="id" value="<?= $editDokter['id'] ?>"><?php endif; ?>
                <div class="form-group" style="margin:0;"><label>Nama Dokter</label><input type="text" name="name" value="<?= htmlspecialchars($editDokter['name'] ?? '') ?>" required></div>
                <div class="form-group" style="margin:0;"><label>Spesialisasi</label><input type="text" name="spesialisasi" value="<?= htmlspecialchars($editDokter['spesialisasi'] ?? '') ?>" required></div>
                <div class="form-group" style="margin:0;"><label>Jadwal</label><input type="text" name="jadwal" value="<?= htmlspecialchars($editDokter['jadwal'] ?? '') ?>"></div>
                <?php if ($editDokter): ?>
                <div class="form-group" style="margin:0;"><label>Status</label>
                    <select name="aktif"><option value="1" <?= $editDokter['aktif'] ? 'selected' : '' ?>>Aktif</option><option value="0" <?= !$editDokter['aktif'] ? 'selected' : '' ?>>Nonaktif</option></select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-<?= $editDokter ? 'primary' : 'success' ?>"><?= $editDokter ? 'Update' : 'Tambah' ?></button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Dokter</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Nama</th><th>Spesialisasi</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($dokterList as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['spesialisasi']) ?></td>
                    <td><?= htmlspecialchars($d['jadwal'] ?? '-') ?></td>
                    <td><span class="badge <?= $d['aktif'] ? 'badge-success' : 'badge-secondary' ?>"><?= $d['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="admin_dokter.php?edit=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <?php if ($d['aktif']): ?>
                        <form method="post" onsubmit="return confirm('Nonaktifkan dokter ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Nonaktifkan</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
