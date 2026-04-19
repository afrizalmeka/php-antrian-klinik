<?php
function initDatabase(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'petugas',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS dokter (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        spesialisasi TEXT NOT NULL,
        jadwal TEXT,
        aktif INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS antrian (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nomor_antrian INTEGER NOT NULL,
        nama_pasien TEXT NOT NULL,
        tanggal_lahir DATE,
        no_telp TEXT,
        keluhan TEXT NOT NULL,
        dokter_id INTEGER,
        status TEXT NOT NULL DEFAULT 'menunggu',
        tanggal DATE NOT NULL DEFAULT (DATE('now')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dokter_id) REFERENCES dokter(id)
    )");

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Dr. Admin', 'admin@kliniku.com', '$pass', 'admin')");
        $petugas = password_hash('petugas123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Petugas Satu', 'petugas@kliniku.com', '$petugas', 'petugas')");

        $pdo->exec("INSERT INTO dokter (name, spesialisasi, jadwal) VALUES ('Dr. Andi Pratama', 'Umum', 'Senin-Jumat 08:00-14:00')");
        $pdo->exec("INSERT INTO dokter (name, spesialisasi, jadwal) VALUES ('Dr. Siti Rahayu', 'Anak', 'Selasa-Sabtu 09:00-15:00')");
        $pdo->exec("INSERT INTO dokter (name, spesialisasi, jadwal) VALUES ('Dr. Budi Santoso', 'Gigi', 'Senin-Rabu 10:00-16:00')");

        $today = date('Y-m-d');
        $pdo->exec("INSERT INTO antrian (nomor_antrian, nama_pasien, keluhan, dokter_id, status, tanggal) VALUES (1, 'Ahmad Fauzi', 'Demam dan batuk', 1, 'selesai', '$today')");
        $pdo->exec("INSERT INTO antrian (nomor_antrian, nama_pasien, keluhan, dokter_id, status, tanggal) VALUES (2, 'Rina Wati', 'Sakit kepala pusing', 1, 'dipanggil', '$today')");
        $pdo->exec("INSERT INTO antrian (nomor_antrian, nama_pasien, keluhan, dokter_id, status, tanggal) VALUES (3, 'Doni Prasetyo', 'Perut mual', 1, 'menunggu', '$today')");
    }
}
