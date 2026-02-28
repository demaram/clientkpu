# Skill: Clean Code Principles

Panduan penulisan kode yang bersih, mudah dibaca, dan mudah di-maintain berdasarkan pola
yang digunakan di proyek ini. Referensi utama: `CalculateLemburService`.

---

## 1. Single Responsibility Principle (SRP)

- Setiap class dan method hanya boleh memiliki **satu tanggung jawab**
- Method `calculate()` berperan sebagai **orchestrator** — hanya mengatur alur, bukan mengerjakan detail
- Pisahkan setiap logika perhitungan ke method tersendiri (`calculateHolidayPay`, `calculateWorkdayPay`, dll)
- Jika method melakukan lebih dari satu hal (query DB + validasi + hitung + format), itu tanda SRP dilanggar

---

## 2. Guard Clauses & Early Return

- Hindari nested `if-else` yang dalam
- Tangani **edge case di awal** method lalu `return` lebih awal
- Kode "happy path" tulis di bawah setelah semua guard clause
- Setiap guard clause yang ditambahkan mengurangi satu level indentasi

---

## 3. Penamaan yang Deskriptif

- Nama variabel, method, dan class harus **self-documenting** — tidak butuh komentar untuk dipahami
- Gunakan nama **lengkap**, bukan singkatan (`$currentDailyHours`, bukan `$cdh`)
- Boolean: prefix `is`, `has`, `can`, `should` → `$isHoliday`, `$hasReachedLimit`
- Method penghitung: prefix `calculate` → `calculateWorkdayPay()`
- Method pengambil data: prefix `get` → `getMasterLemburByProjectId()`
- Method validasi: prefix `validate` atau `is` → `validateOvertimeLimit()`

---

## 4. Method Extraction (Decompose Method)

- Jika method terlalu panjang atau kompleks, **pecah menjadi sub-method** yang lebih kecil
- Batas yang disarankan: **<= 30 baris** kode aktif per method
- Nama sub-method harus menjelaskan apa yang dilakukan tanpa perlu membaca isinya

---

## 5. Tipe Data Eksplisit

- Semua **parameter** dan **return type** wajib dideklarasikan di PHP 8+
- Gunakan union type jika perlu: `int|float`
- Gunakan nullable type jika parameter bisa null: `?int`, `?float`
- Tidak ada parameter atau return tanpa type hint pada method `private` / `protected`

---

## 6. Hindari Magic Numbers & Magic Strings

- Setiap angka atau string yang memiliki makna bisnis **wajib** dijadikan konstanta bernama
- Konstanta dideklarasikan di bagian atas class dengan `private const` atau `public const`
- Nama konstanta: `UPPER_SNAKE_CASE` dan harus menjelaskan makna bisnis, bukan nilainya
- Contoh: `HOLIDAY_OVERTIME_BASE_HOURS = 8` lebih baik daripada angka `8` tersebar di kode

---

## 7. Constructor Injection & Dependency Management

- Semua dependency di-instantiate di **constructor**, bukan di dalam method bisnis
- Hindari `new ClassName()` di dalam method — sulit di-test dan di-maintain
- Jika memungkinkan, gunakan dependency injection via parameter constructor

---

## 8. Konsistensi Struktur Return Value

- Return array dari service harus **selalu memiliki key yang sama** di semua kondisi
- Urutkan key secara **konsisten** dan sesuaikan alignment `=>` dalam satu kelompok
- Trailing comma wajib ada pada elemen terakhir array multi-line
- Pertimbangkan **DTO / Value Object** untuk type safety yang lebih kuat

---

## 9. Komentar Hanya untuk WHY

- Kode yang baik **tidak butuh komentar** untuk menjelaskan WHAT (apa yang dilakukan)
- Komentar hanya untuk menjelaskan **MENGAPA** — alasan bisnis, regulasi, atau keputusan non-obvious
- Komentar pada parameter boolean anonim (`true`, `false`) sangat dianjurkan untuk kejelasan
- Hapus komentar yang hanya mengulang apa yang sudah jelas terbaca dari kode

---

## 10. Method Ordering Convention

Urutkan method dalam class mengikuti pola berikut:

1. **Constants** (`private const`, `public const`)
2. **Properties** (`protected`, `private`, `public`)
3. **Constructor** (`__construct`)
4. **Public methods** — API publik class
5. **Protected methods** — dapat di-override subclass
6. **Private methods** — implementasi internal, urut dari yang paling sering dipanggil
