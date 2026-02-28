# Skill: Performance Optimization

Panduan untuk menulis kode yang efisien dan berperforma tinggi di proyek Laravel payroll ini.
Referensi utama: pola query optimization di `CalculateLemburService` dan `LemburRepository`.

---

- Ambil semua data yang dibutuhkan dalam **satu query** daripada banyak query kecil.
- Selalu gunakan `with()` ketika membutuhkan relasi dari koleksi model.
- Jangan gunakan n+1 query dengan mengakses relasi di dalam loop tanpa eager loading.
- Jangan `SELECT *` jika tidak semua kolom dibutuhkan.
- Tulis query yang memanfaatkan **index** yang sudah ada.
- Gunakan `chunk()` atau `cursor()` untuk memproses data dalam jumlah besar.
- Cache data master yang tidak sering berubah untuk mengurangi query berulang.
- Lakukan agregasi (SUM, COUNT, AVG) di **database**, bukan di PHP.
- Simpan hasil kalkulasi berat ke variabel, jangan hitung ulang.
- Gunakan **Laravel Queue** untuk operasi payroll yang berat agar tidak block request.
- Gunakan Carbon instance yang sudah ada, jangan parse ulang dari string yang sama. hindari penggunaan fungsi date() atau strtotime() berulang kali.
- Terapkan **conditional short-circuit** untuk menghindari komputasi yang tidak perlu.
- Pindahkan query keluar dari loop, atau gunakan query tunggal dengan `whereIn`.