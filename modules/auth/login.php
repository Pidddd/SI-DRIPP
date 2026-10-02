/**
 * UI MODUL AUTH - HALAMAN LOGIN
 * File: modules/auth/login.php
 * Fungsi: Menampilkan antarmuka form login.
 * Aksi Form: Mengarah ke process_login.php (Method POST)
 */

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login SI - DRIPP</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  
    <div class="login-card">

      <div class="brand">
        <span class="title">SI-DRIPP</span>
    </div>
    <p class="subtitle">Sistem Inventaris & Penjualan Grosir HORECA</p>

    <form action="process_login.php" method="POST">
      <div class="form-group">
        <label for="username">Username atau ID Pengguna</label>
        <input type="text" id="username" name="username" placeholder="Masukkan username anda ..." required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="********" required>
      </div>

      <div class="form-group">
        <label for="role">Hak Akses (Role)</label>
        <select name="role" id="role" required>
          <option value="kasir">Kasir</option>
          <option value="admin">Admin</option>
          <option value="staff">Staff Gudang</option>
        </select>
      </div>

      <button type="submit" class="btn-submit">Masuk ke Sistem</button>
    </form>

      <div class="card-footer-links">
        <a href="index.php">&larr; Halaman Utama</a>
        <a href="">Daftar Akun Baru &rarr;</a>
      </div>

      <p class="copyright">&copy; 2026 SI-DRIPP Project Based Learning</p>
</body>
</html>