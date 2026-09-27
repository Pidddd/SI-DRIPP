# 📌 Standar Operasional Prosedur (SOP) Git Workflow - Tim 404

Dokumen ini adalah panduan wajib bagi seluruh anggota pengembang SI-DRIPP (Kelompok 404) dalam melakukan manajemen kode menggunakan Git dan GitHub.

## 🚨 Aturan Mutlak
1. **DILARANG KERAS** melakukan `commit` dan `push` langsung ke branch `main`.
2. **DILARANG KERAS** melakukan `commit` dan `push` langsung ke branch `dev`.
3. Branch `main` hanya digunakan untuk rilis final / demo dosen.
4. Branch `dev` hanya digunakan sebagai tempat penggabungan akhir dari seluruh fitur.

Semua anggota tim wajib mengikuti 4 tahapan di bawah ini setiap kali mengerjakan tugas/fitur:

---

## Tahap 1: Awal Buka Terminal (Persiapan)
Sebelum mulai menulis kode, kamu wajib mengambil versi kode terbaru dari server agar kodemu tidak tertinggal atau menabrak pekerjaan teman yang lain.

Buka terminal di VS Code dan jalankan perintah berurutan ini:
```bash
git checkout dev                  # (Untuk pindah ke branch gabungan tim)
git pull origin dev               # (Untuk menarik/mengunduh kodingan terbaru dari GitHub agar tidak bentrok)
git checkout -b nama-branch       # (Untuk membuat branch/kamar baru khusus tugasmu dan langsung pindah ke sana)
```
*(Catatan: Ganti `nama-branch` dengan fitur yang sedang kamu kerjakan. Contoh: `git checkout -b fitur-login` atau `git checkout -b ui-kasir`).*

---

## Tahap 2: Proses Pengerjaan (Koding)
Silakan menulis kode, mengedit file, atau merancang UI sesuai pembagian tugas di branch kamu sendiri.

---

## Tahap 3: Akhir Koding (Penyimpanan & Push)
Jika fiturmu sudah selesai atau kamu ingin menyimpan progres untuk dilanjutkan besok, kirim kodemu dari terminal ke GitHub.

Jalankan perintah berurutan ini:
```bash
git add .                         # (Untuk memasukkan semua perubahan kodemu ke keranjang sementara)
git commit -m "Pesan kamu"        # (Untuk menyegel dan menyimpan kodemu beserta pesan penjelasannya)
git push origin nama-branch       # (Untuk mengunggah kodemu dari laptop ke server GitHub)
```
*(⚠️ **PENTING:** Pada baris push, ujungnya wajib menggunakan nama branch-mu sendiri, JANGAN `dev` atau `main`! Contoh: `git push origin ui-kasir`).*

---

## Tahap 4: Pengajuan Penggabungan (Pull Request)
Setelah berhasil di-push melalui terminal, tugas terakhirmu ada di web GitHub:

1. Buka halaman repositori SI-DRIPP di GitHub.
2. Klik tombol hijau **"Compare & pull request"** yang otomatis muncul.
3. Pastikan target penggabungannya mengarah ke **`dev`** (`base: dev` <- `compare: nama-tugasmu`).
4. Klik tombol **Create Pull Request**.
5. Segera konfirmasi di grup WhatsApp: *"Kodinganku udah di-push, tolong di-acc (Merge) ke dev ya."*

> **Project Manager (Rafid)** akan mereview kodemu. Jika tidak ada error atau bentrok (conflict), kodemu akan di-Merge ke dalam branch `dev` dan resmi menjadi bagian dari sistem.