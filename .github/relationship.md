# Model Relationships — Core Business

---

## Client `(table: client)`
- `hasMany` **Spk** — satu client punya banyak SPK
- `hasMany` **User** `(fk: id_client)` — karyawan yang terkait client

---

## Spk `(table: spk)`
- `belongsTo` **Client**
- `belongsTo` **Pic**
- `belongsTo` **Spk** *(self, fk: header_id)* — SPK anak → SPK induk
- `hasMany` **Project**

---

## Project `(table: project)`
- `belongsTo` **Spk**
- `belongsTo` **MasterPekerjaan** `(fk: master_pekerjaan_id)`
- `belongsTo` **MasterArea** `(fk: master_area_id)`
- `hasMany` **Cost** — struktur biaya project
- `hasMany` **History** `(karyawan_project)` — riwayat penempatan karyawan
- `hasOne` **MasterLembur** `(fk: project_id)` — aturan lembur project

---

## Cost `(table: cost)`
- `belongsTo` **Project**
- `belongsTo` **Upah** — komponen gaji pokok

## Upah `(table: upah)`
- `hasMany` **Cost**

---

## Karyawan `(table: users)`
- `belongsTo` **Project** — project aktif saat ini
- `hasMany` **Absence** `(fk: id_user)` — data absensi
- `hasMany` **History** `(karyawan_project)` — riwayat penempatan di project
- `hasMany` **Cuti** `(fk: karyawan_id)`
- `belongsTo` **MasterPtkp** `(fk: ptkp_id)` — status PTKP untuk PPH 21
- `belongsToMany` **Tambahan** `(pivot: tambahan_karyawan)` — tunjangan personal
- `belongsToMany` **Potongan** `(pivot: pinjaman_karyawan)` — potongan/pinjaman personal
- `belongsToMany` **Shift** `(pivot: shift_employees)`

---

## History `(table: karyawan_project)` — Penempatan Karyawan di Project
- `belongsTo` **Karyawan**
- `belongsTo` **Project**
- `belongsTo` **MasterResign**
- `hasMany` **AbsenOs** `(fk: karyawan_project_id)`

---

## Absence `(table: absences)` — Absensi Harian
- `belongsTo` **User** `(fk: id_user)`
- `belongsTo` **Location** `(fk: id_office)`
- `belongsTo` **Shift** `(fk: id_shift)`
- `hasMany` **AbsenceLocation** — lokasi check-in/check-out

---

## LemburKaryawan `(table: lembur_karyawan_project)` — Data Lembur
- `belongsTo` **Karyawan** `(fk: user_id)`
- `belongsTo` **Project**
- `belongsTo` **Client**
- `belongsTo` **Spk**
- `hasMany` **LemburLocation** `(fk: id_lembur)`

## MasterLembur `(table: master_lembur)` — Aturan Lembur per Project
- `belongsTo` **Project**
- `belongsTo` **Client**

---

## Tambahan `(table: tambahan)` — Tunjangan
- `belongsTo` **MasterTambahan** `(fk: master_tambahan_id)`
- `belongsToMany` **Karyawan** `(pivot: tambahan_karyawan)`

## Potongan `(table: pinjaman)` — Potongan / Pinjaman
- `belongsTo` **MasterPinjaman** `(fk: master_pinjaman_id)`
- `belongsToMany` **Karyawan** `(pivot: pinjaman_karyawan)`

---

## Gaji `(table: gaji)` — Payroll Run
> Model Gaji tidak mendefinisikan relasi Eloquent secara eksplisit.
> Data payroll result diakses melalui query langsung ke tabel `gaji` dan `payroll_data`.

---

## Ringkasan Alur Utama

```
Client (1) → (n) Spk (1) → (n) Project
Project (1) → (n) Cost → (1) Upah
Project (1) → (1) MasterLembur
Project (1) → (n) History/karyawan_project (n) ← (1) Karyawan
History/karyawan_project (1) → (n) Absence
History/karyawan_project (1) → (n) LemburKaryawan
Karyawan (n) ↔ (n) Tambahan  [via tambahan_karyawan]
Karyawan (n) ↔ (n) Potongan  [via pinjaman_karyawan]
Karyawan → MasterPtkp  (untuk kalkulasi PPH 21)
```