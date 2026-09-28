<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Akun</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="'register-container">
  <div class="register-header">
    <h2>Registration</h2>
    <p>Buat akses baru ke platfrom SI-DRIPP</p>
    </div>
  <form action="process_login.php" method="POST">
    <div class="form-group">
      <label for="nama_lengkap">Nama Lengkap</label>
      <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required>
    </div>

    <div class="form-group">
      <label for="jenis_kelamin">Jenis Kelamin</label>
      <select id="jenis_kelamin" name="jenis_kelamin" class="form-control" required>
        <option value="" disabled selected>Pilih Jenis Kelamin</option>
        <option value="Laki-laki">Laki-laki</option>
        <option value="Perempuan">Perempuan</option>
      </select>
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" class="form-control" required>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" class="form-control" placeholder="********" required>
    </div>

    <div class="form-group">
      <label for="role">Hak Akses (Role)</label>
      <select id="role" name="role" class="form-control" required>
        <option value="kasir">Kasir</option>
        <option value="admin">Admin</option>
        <option value="staff gudang">Staff Gudang</option>
      </select>
    </div>

    <button type="submit" class="btn-submit">Daftar Sekarang</button>
    </form>

    <div class="register-footer">
      <a href="login.php">Sudah punya akun? Masuk di sini</a>
    </div>
    </div>
</body>
</html>