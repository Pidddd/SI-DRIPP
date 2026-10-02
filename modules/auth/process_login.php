<?php
/**
 * LOGIKA PEMROSESAN AUTH
 * File: modules/auth/process_login.php
 * Fungsi: Mengecek kredensial login (Username & Password) terhadap tabel User.
 * Jika valid: Set $_SESSION (ID_User, Role, dll) lalu alihkan ke dashboard.
 * Jika invalid: Kembalikan ke login.php dengan pesan error.
 * [CRITICAL]: Gunakan sanitasi string prosedural!
 */