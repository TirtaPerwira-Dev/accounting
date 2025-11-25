# copilot-intrucions.md

_Sistem Akuntansi Air Minum Berbasis SAKEP_  
`v1.0.0` | 2024-06-20 | Tim Pengembang

---

## 🚰 **Tujuan Sistem**

Menyediakan solusi akuntansi **terintegrasi, otomatis, dan sesuai standar SAKEP** untuk perusahaan air minum (PDAM, BUMDes Air, dll) dengan fokus pada:

-   Pencatatan transaksi harian (penjualan air, pembelian, gaji)
-   Otomatisasi pajak (PPN, PPh, e-Faktur)
-   Rekonsiliasi bank & meter
-   Laporan keuangan sesuai PSAK & regulasi DJP/BPK/Pemda

---

## 🗂️ **Flowchart Sistem (Mermaid)**

````mermaid
flowchart TD
   A[Setup Awal] --> B[Buat Akun & Konfigurasi]
   B --> C[Atur Chart of Accounts SAKEP]
   C --> D[Input Saldo Awal]
   D --> E[Aktifkan Pajak & Periode]

   E --> F{Pencatatan Harian}
   F --> G[Penjualan Air: Debit Piutang, Kredit Pendapatan]
   F --> H[Pembelian: Debit Beban/Aset, Kredit Utang]
   F --> I[Gaji & Penyesuaian]
   G & H & I --> Z[Otomatisasi Opsional: Bank / Meter / e-Faktur]

   Z --> K[Rekonsiliasi Bank]
   K --> L[Penyesuaian Akhir Periode]
   L --> M[Catat Accrual & Deferral]
   M --> N[Hitung Depresiasi]
   N --> O[Hitung Pajak Otomatis - PPN, PPh, e-Faktur]

   O --> P[Penyusunan Laporan]
   P --> Q[Neraca]
   P --> R[Laba Rugi]
   P --> S[Arus Kas]
   P --> T[Catatan Laporan]

   Q & R & S & T --> U[Ekspor PDF/Excel]
   U --> V[Submit ke DJP / BPK / Pemda]
   V --> W[Analisis Dashboard]

   W --> X{Periode Berikutnya?}
   X -->|Ya| F
   X -->|Tidak| Y[Closing Periode & Arsip]

   style A fill:#4CAF50, color:white
   style Y fill:#F44336, color:white
   style P fill:#2196F3, color:white
   style O fill:#FF9800, color:white
   style Z fill:#9E9E9E, color:#fff

   Berikut adalah file **`copilot-intrucions.md`** yang telah disusun secara profesional, terstruktur, dan siap digunakan sebagai panduan internal untuk **GitHub Copilot** atau tim pengembang dalam memahami serta mengembangkan **Sistem Akuntansi Air Minum Berbasis SAKEP**.

---

```markdown
# copilot-intrucions.md
*Sistem Akuntansi Air Minum Berbasis SAKEP*
`v1.0.0` | 2024-06-20 | Tim Pengembang

---

## 🚰 **Tujuan Sistem**
Menyediakan solusi akuntansi **terintegrasi, otomatis, dan sesuai standar SAKEP** untuk perusahaan air minum (PDAM, BUMDes Air, dll) dengan fokus pada:
- Pencatatan transaksi harian (penjualan air, pembelian, gaji)
- Otomatisasi pajak (PPN, PPh, e-Faktur)
- Rekonsiliasi bank & meter
- Laporan keuangan sesuai PSAK & regulasi DJP/BPK/Pemda

---

## 🗂️ **Flowchart Sistem (Mermaid)**

```mermaid
flowchart TD
   A[Setup Awal] --> B[Buat Akun & Konfigurasi]
   B --> C[Atur Chart of Accounts SAKEP]
   C --> D[Input Saldo Awal]
   D --> E[Aktifkan Pajak & Periode]

   E --> F{Pencatatan Harian}
   F --> G[Penjualan Air: Debit Piutang, Kredit Pendapatan]
   F --> H[Pembelian: Debit Beban/Aset, Kredit Utang]
   F --> I[Gaji & Penyesuaian]
   G & H & I --> Z[Otomatisasi Opsional: Bank / Meter / e-Faktur]

   Z --> K[Rekonsiliasi Bank]
   K --> L[Penyesuaian Akhir Periode]
   L --> M[Catat Accrual & Deferral]
   M --> N[Hitung Depresiasi]
   N --> O[Hitung Pajak Otomatis - PPN, PPh, e-Faktur]

   O --> P[Penyusunan Laporan]
   P --> Q[Neraca]
   P --> R[Laba Rugi]
   P --> S[Arus Kas]
   P --> T[Catatan Laporan]

   Q & R & S & T --> U[Ekspor PDF/Excel]
   U --> V[Submit ke DJP / BPK / Pemda]
   V --> W[Analisis Dashboard]

   W --> X{Periode Berikutnya?}
   X -->|Ya| F
   X -->|Tidak| Y[Closing Periode & Arsip]

   style A fill:#4CAF50, color:white
   style Y fill:#F44336, color:white
   style P fill:#2196F3, color:white
   style O fill:#FF9800, color:white
   style Z fill:#9E9E9E, color:#fff
````

---

## ⚙️ **Konfigurasi Sistem**

| Komponen        | Detail                           |
| --------------- | -------------------------------- |
| **Judul**       | Sistem Akuntansi Air Minum SAKEP |
| **Bahasa**      | PHP 8.1+                         |
| **Framework**   | Laravel 10                       |
| **Database**    | PostgreSQL 14+                   |
| **Admin Panel** | Filament PHP                     |
| **Realtime**    | Livewire                         |
| **Permission**  | Spatie Permission + Shield       |

---

## 📋 **Panduan untuk Copilot / Developer**

### 1. **Chart of Accounts (SAKEP)**

-   Gunakan kode akun sesuai **Standar Akuntansi Keuangan Entitas Privat (SAKEP)**.
-   Contoh:
    ```php
    1101 - Piutang Usaha Air Minum
    4101 - Pendapatan Jasa Air Bersih
    5101 - Beban Air Baku
    2101 - Utang Supplier
    ```

### 2. **Transaksi Penjualan Air**

```php
// Jurnal Otomatis
Debit  : Piutang Usaha (1101)
Kredit : Pendapatan Air (4101)
Kredit : PPN Keluaran (2201) // jika PKP
```

### 3. **Integrasi Meter Otomatis**

```php
// Input: meter_awal, meter_akhir, tarif_per_m3
$volume = $meter_akhir - $meter_awal;
$pendapatan = $volume * $tarif;
$ppn = $pendapatan * 0.11; // jika PKP
```

### 4. **Rekonsiliasi Bank**

-   Bandingkan mutasi bank vs jurnal.
-   Gunakan `bank_statements` table + `reconciled_at`.

### 5. **Pajak Otomatis**

| Pajak    | Perhitungan                        |
| -------- | ---------------------------------- |
| PPN      | 11% × Pendapatan Kena Pajak        |
| PPh 23   | 2% × Pembelian Jasa                |
| e-Faktur | Generate XML via library resmi DJP |

### 6. **Laporan Keuangan**

-   `Neraca`, `Laba Rugi`, `Arus Kas` → otomatis dari jurnal.
-   Gunakan **Query Builder** dengan `group by account_id`.

### 7. **Closing Periode**

```php
// Pindahkan laba/rugi ke Ekuitas (3001 - Laba Ditahan)
// Kunci periode: is_closed = true
```

---

## 📊 **Fitur Dashboard**

| Widget            | Sumber Data                        |
| ----------------- | ---------------------------------- |
| Total Piutang     | SUM(debit) - SUM(credit) akun 1101 |
| Tagihan Bulan Ini | Penjualan periode berjalan         |
| PPN Terutang      | Jurnal akun 2201                   |
| Tunggakan Meter   | Pelanggan > 60 hari                |

---

## 🔐 **Hak Akses (Spatie + Shield)**

| Role          | Akses                          |
| ------------- | ------------------------------ |
| `super_admin` | Semua modul                    |
| `akuntan`     | Jurnal, Laporan, Pajak         |
| `kasir`       | Penjualan, Input Meter         |
| `direktur`    | Dashboard, Laporan (read-only) |

---

## 📁 **Struktur Database (Contoh)**

```sql
accounts (id, code, name, type, is_active)
journals (id, date, description, ref)
journal_details (journal_id, account_id, debit, credit)
customers (id, name, meter_number, address)
invoices (id, customer_id, period, volume_m3, amount, ppn)
bank_statements (id, date, description, amount, reconciled)
```

---

## 🚀 **Perintah Artisan yang Direkomendasikan**

```bash
php artisan make:journal "Penjualan Air Bulan Juni"
php artisan close:period 2024-06
php artisan generate:efaktur --month=06 --year=2024
php artisan export:report --type=neraca --format=pdf
```

---

## ⚡ **Otomatisasi (Opsional)**

-   **Bank Sync**: API Banking (BI-FAST / Flip)
-   **Meter IoT**: MQTT / REST dari device
-   **e-Faktur**: Upload otomatis ke DJP
-   **Notifikasi**: WhatsApp/Email tunggakan

---

## 📌 **Catatan Penting**

-   Semua transaksi **harus balanced** (total debit = total kredit).
-   Gunakan **soft deletes** untuk jurnal.
-   Audit log wajib untuk perubahan jurnal.
-   Backup harian database + storage.

---

_File ini digunakan oleh GitHub Copilot untuk menghasilkan kode yang konsisten, sesuai SAKEP, dan mengikuti alur bisnis PDAM._

---

**Versi**: `1.0.0`  
**Update Terakhir**: 2024-06-20  
**Author**: Tim Pengembang SAKEP

```

---
```

ternyata bagan akun itu strukturnya tidak seperti itu,
ada Nomor Kelompok, Nomor Rekenig, dan Nomor Bantu.

Perbaiki migration, model dan seedingnya seperti data ini :
NO_KEL NAMA_KEL KEL NO_KEL NO_REK NAMA_REK KODE KEL DATA NO_KEL NO_REK NO_BANTU NM_BANTU KEL KODE
10 Aktiva Lancar 1 10 1101 Kas D 1 10 1102 20 Bank BPD Capem Pasar Kota 1 D
20 Investasi Jk. Panjang 1 10 1102 Bank D 1 10 1102 30 BMT Mrebet 1 D
30 Aktiva Tetap 1 10 1201 Deposito D 1 10 1201 10 Deposito di Bank BPD 1 D
40 Aktiva Lain-lain 1 10 1202 Surat Berharga D 1 10 1201 20 Deposito di Bank BRI 1 D
41 Aktiva Lain-lain Berwujud 1 10 1301 Piutang Rekening Air D 1 10 1201 30 Deposito di Bank Mandiri 1 D
42 Aktiva Tak Berwujud 1 10 1302 Piutang Rekening Non Air D 1 10 1402 10 Piutang Pajak Penghasilan (21) 1 D
50 Kewajiban Jk. Pendek 2 10 1303 Piutang Kemitraan D 1 10 1402 20 Uang Muka Pajak Pasal 25 1 D
60 Kewajiban Jk. Panjang 2 10 1304 Piutang Rekening Air Limbah D 1 10 1402 30 Uang Muka Pajak Pasal 22 1 D
62 Kewajiban Lain-lain 2 10 1305 Piutang Ragu-ragu Air D 1 10 1402 40 Uang Muka Pajak Pasal 23 1 D
70 Modal dan Cadangan 2 10 1309 Penyisihan Piutang Usaha K 1 10 1605 10 Pajak Dimuka (PPN) 1 D
80 Pendapatan 3 10 1401 Tagihan Non Usaha D 1 10 1605 20 Pajak Pasal 23 1 D
88 Pendapatan Diluar Usaha 3 10 1402 Piutang Pajak D 1 10 1605 30 Pajak Pasal 25 1 D
91 Biaya Sumber Air 4 10 1403 Pendapatan Yang Belum Diterima D 1 50 5006 10 Utang Pajak PPN 2 K
92 Biaya pengolahan Air 4 10 1409 Rupa-rupa Piutang Lainnya D 1 50 5006 20 Utang Pajak Pasal 25 2 K
93 Biaya Transmisi dan Distribusi 4 10 1501 Persediaan Bahan Operasi Kimia D 1 50 5006 30 Utang Pajak Pasal 23 2 K
94 Biaya Air Limbah 4 10 1502 Persd. Bahan Operasi Lainnya D 1 50 5006 40 Utang Pajak Pasal 21 2 K
96 Biaya Administrasi dan Umum 5 10 1509 Persediaan Lain-lain D 1 50 5003 20 Biaya Telephon 2 K
98 Biaya Diluar Usaha 6 10 1601 Biaya Dibayar Dimuka D 1 50 5003 30 Biaya gaji ke 13 2 K
99 Kerugian Luar Biasa 6 10 1602 Uang Muka Kerja D 1 80 8101 10 Pendapatan Harga Air 3 K
45 aset program 1 10 1603 Uang Muka Pembelian D 1 80 8101 20 Pendapatan Jasa Administrasi 3 K
10 1604 Uang Muka Kepada Kontraktor D 1 80 8101 99 Penjualan Air Lainnya 3 K
10 1605 Pembayaran Dimuka Pajak D 1 80 8102 10 Pendapatan Sambungan Baru 3 K
10 1609 Rupa-rupa Pembayaran Dimuka D 1 80 8102 20 Pendapatan Sewa Instalasi 3 K
10 2001 Deposito Berjangka > 1 Tahun D 1 80 8102 30 Pendapatan Pemeriksaan Air Lab 3 K
20 2002 Penyertaan D 1 80 8102 40 Pendp. Penyambungan Kembali 3 K
20 2003 Penanaman Dlm Aktiva Berwujud D 1 80 8102 60 Pendp. Pemeriksaan Inst. Pelg. 3 K
30 3101 Tanah dan Penyepurnaan Tanah D 1 AT 80 8102 70 Pendp. Penggantian Meter Rusak 3 K
30 3102 Instalasi Sumber Air D 1 AT 80 8102 80 Pendp. Penggantian Pipa Persil 3 K
30 3103 Instalasi Pompa D 1 AT 80 8102 90 Pendapatan dari Kolam Renang 3 K
30 3104 Instalasi Pengolahan Air D 1 AT 80 8102 99 Pendapatan Non Air Lainnya 3 K
30 3105 Instalasi Transm & Distribusi D 1 AT 80 8103 10 Pendapatan Royalty 3 K
30 3106 Bangunan/Gedung D 1 AT 80 8103 20 Pembagian dari Kemitraan 3 K
30 3107 Peralatan dan Perlengkapan D 1 AT 80 8103 30 Pembg. Produksi dari Kemitraan 3 K
30 3108 Kendaraan/Alat Angkut D 1 AT 80 8103 40 Pembg. Keutungan Kemitraan 3 K
30 3109 Inventaris/Perabot Kantor D 1 AT 80 8103 50 Penerimaan Deviden 3 K
30 3190 Akumulasi Penyusutan K 1 88 8801 10 Pendapatan Bunga Deposito 3 K
40 4101 Aktiva Tetap Dlm Penyelesaian D 1 88 8801 20 Pendapatan Jasa Giro 3 K
40 4102 Bahan Instalasi D 1 88 8801 30 Penjualan Barang-Barang Bekas 3 K
40 4103 Uang Jaminan D 1 88 8801 40 Keuntungan Penj. Aktiva Tetap 3 K
40 4104 Pengeluaran Sementara D 1 88 8801 50 Keuntungan atas Valuta Asing 3 K
40 4105 Aktiva Tetap Yg Tdk Berfungsi D 1 88 8801 60 Penr. Piutang Yang disisihkan 3 K
40 4106 Dana Untuk Pembayaran Utang D 1 88 8801 99 Rupa-rupa Pendapatan Lain-lain 3 K
40 4107 Samb. Baru Yg Akan Diterima D 1 91 9101 10 Biaya Pegawai 4 D
40 4108 Pemby. Dimuka kpd Pemerintah D 1 91 9101 20 Pemakaian Bahan Bakar 4 D
42 4201 Beban Ditangguhkan D 1 91 9101 30 Biaya Listrik PLN 4 D
42 4202 Akm. Amort. Beban Ditanggukan K 1 91 9101 40 Pemakaian Bahan Pembantu 4 D
42 4203 Trade Mark D 1 91 9101 99 Rupa-rupa Biaya Operasi SA. 4 D
42 4204 Akm. Amortisasi Trade Mark K 1 91 9102 10 Peml. Bangunan & Penyemp. Tnh 4 D
42 4205 Good Will D 1 91 9102 11 Peml. Pengumpulan & Reservoir 4 D
42 4206 Akm. Amortisasi Good Will K 1 91 9102 30 Pemeliharaan Danau, Sungai 4 D
50 5001 Hutang Usaha K 2 91 9102 40 Peml. Mata Air dan Saluran 4 D
50 5002 Utang Lain-Lain K 2 91 9102 50 Pemeliharaan Sumur-sumur 4 D
50 5003 Biaya Masih Harus Dibayar K 2 91 9102 60 Pemeliharaan Pipa Induk 4 D
50 5004 Pendapatan Diterima Dimuka K 2 91 9102 70 Peml. Alat Pembangkit Tenaga 4 D
50 5005 Pinjaman Jk. Pendek K 2 91 9102 80 Peml. Alat Perpompaan 4 D
50 5006 Utang Pajak K 2 91 9102 90 Peml. Instalasi Sumber Lainnya 4 D
50 5007 Bagian Utang jk. Pjg J.T. K 2 92 9201 10 Biaya Pengawai 4 D
50 5008 Beban Bunga & Denda YMH Dibyr K 2 92 9201 20 Pemakaian Bahan Kimia 4 D
50 5009 Utang Bunga Rescheduling K 2 92 9201 30 Pemakaian Bahan Pembantu 4 D
50 5010 Jaminan Masa Pemeliharaan K 2 92 9201 40 Biaya Bahan Bakar 4 D
50 5099 Kewajiban Jk. Pendek Lainnya K 2 92 9201 50 Biaya Listrik PLN 4 D
60 6101 Pinjaman Dalam Negeri K 2 92 9201 90 Rupa-rupa Biaya Pengolahan Air 4 D
60 6102 Pinajam Luar Negeri K 2 92 9202 10 Peml. Bangunan & Penyemp Tanah 4 D
60 6103 Bunga Masa Tenggang K 2 92 9202 20 Peml. Instalasi Pengolahan Air 4 D
60 6104 Utang Leasing K 2 92 9202 30 Peml. Instalasi Pompa 4 D
62 6201 Pendapatan Yang Ditangguhkan K 2 92 9202 90 Peml. Inst. Pengolahan Lainnya 4 D
62 6202 Cadangan Dana Meter K 2 93 9301 10 Biaya Pegawai 4 D
62 6203 Cadangan Dana K 2 93 9301 20 Biaya Pemakaian Bahan/Perlengk 4 D
62 6209 Kik jangka panjang K 2 93 9301 30 Biaya Bahan Bakar 4 D
70 7001 Kekayaan Pemda Yg Dipisahkan K 2 93 9301 40 Biaya Listrik PLN 4 D
70 7002 Penyert Pemert Blm Ttp Status K 2 93 9301 50 Biaya Pemakaian Pipa Persil 4 D
70 7003 M o d a l K 2 93 9301 90 Rupa-rupa Biaya Operasi 4 D
70 7004 Modal Hibah K 2 93 9302 10 Peml. Bangunan & Penyemp Tanah 4 D
70 7005 Selisih Penilaian Kembali K 2 93 9302 20 Peml. Reservoir dan Tangki 4 D
70 7006 Cadangan K 2 93 9302 30 Peml. Pipa Transmisi 4 D
70 7011 Laba (Rugi) Ditahan Th 2009 K 2 93 9302 40 Peml. Pipa Dinas 4 D
70 7012 Laba/Rugi Bulan Berjalan K 2 93 9302 50 Peml. Instalasi Pompa 4 D
70 7013 Laba Rugi Bulan Lalu K 2 93 9302 60 Peml. Water Meter 4 D
80 8101 Pendapatan Penjualan Air K 3 93 9302 70 Peml. Hydrant 4 D
80 8102 Pendapatan Non Air K 3 93 9302 90 Peml. Trans & Distr. Lainnya 4 D
80 8103 Pendapatan Kemitraan K 3 96 9601 10 Gaji dan Honor Pengawai 5 D
80 8104 Pendapatan Air Limbah K 3 96 9601 20 Tunjangan 5 D
88 8801 Pendapatan Lain-lain K 3 96 9601 30 Iuran Pensiun 5 D
88 8901 Keuntungan Luar Biasa K 3 96 9601 40 Lembur 5 D
91 9101 Biaya Operasi Sumber Air D 4 96 9601 50 Insentive/Kesejahteraan Kary. 5 D
91 9102 Biaya Pemeliharaan Sumber Air D 4 96 9601 60 Pemb. Karyawan & Pakaian Dinas 5 D
91 9103 Biaya Air Baku D 4 96 9601 70 Bantuan dan Sumbangan 5 D
91 9109 Biaya Penyusutan Sumber Air D 4 96 9601 80 Pendidikan dan Latian 5 D
92 9201 Biaya Opr. Pengolahan Air D 4 96 9601 90 Rupa-rupa Biaya Pengawai 5 D
92 9202 Biaya Pemel. Pengolahan Air D 4 96 9602 10 Biaya Alat Tulis Kantor & FC 5 D
92 9203 Biaya Pencadangan Air Curah D 4 96 9602 20 Barang-barang Cetakan 5 D
92 9209 Biaya Peny. Pengolahan Air D 4 96 9602 30 Perlengkapan Komputer 5 D
93 9301 Biaya Opr. Transm. & Distr. D 4 96 9602 40 Biaya Telp., Telex, Telgram 5 D
93 9302 Biaya Peml. Trans. & Distr. D 4 96 9602 50 Biaya Rapat dan Tamu 5 D
93 9309 Biaya Peny. Trans. & Distr. D 4 96 9602 60 Benda Pos dan Meterai 5 D
96 9601 Biaya Pegawai D 5 96 9602 70 Biaya Listrik dan Penerangan 5 D
96 9602 Biaya Kantor D 5 96 9602 80 Biaya Cleaning Servis 5 D
96 9603 Biaya Hubungan Langganan D 5 96 9602 90 Rupa-rupa Biaya Kantor 5 D
96 9604 Biaya Penel. & Pengembangan D 5 96 9603 10 Biaya Pengawas Meter 5 D
96 9605 Biaya Keuangan D 5 96 9603 20 Biaya Pembaca Meter 5 D
96 9606 Biaya Pemeliharaan D 5 96 9603 30 Biaya Penagihan Rekening 5 D
96 9607 Biaya Penys. & Pengh. Piutang D 5 96 9603 40 Biaya Cetakan Pelanggan 5 D
96 9608 Rupa-rupa Biaya Umum D 5 96 9603 50 Biaya Cetakan Formulir Rek. 5 D
96 9691 Biaya Penyusutan D 5 96 9603 60 Biaya Pengl. & Penerbitan Rek. 5 D
96 9692 Biaya Amortisasi D 5 96 9603 70 Biaya Humas & Pembinaan Masy. 5 D
98 9801 Biaya Lain-lain D 6 96 9603 71 Biaya Iklan 5 D
99 9901 Kerugian Luar Biasa D 6 96 9603 90 Rupa-rupa Biaya Hub. Langganan 5 D
10 1306 Piutang Ragu-ragu Non Air D 1 96 9604 10 Biaya Survay dan Penelitian 5 D
10 1503 Persediaan Pipa & Accesories D 1 96 9604 20 Biaya Perencanaan Tehnik 5 D
10 1608 Pajak Masukan D 1 96 9604 30 Biaya Perenc. Bdg Usaha & Keu 5 D
10 1307 Piutang Pegawai D 1 96 9604 40 Biaya Perenc. Komputerisasi 5 D
10 1308 Piutang AMDK D 1 96 9604 90 Rupa-rupa By. Penel. & Pengemb 5 D
62 6204 Rupa-Rupa Kewajiban Lainnya K 2 62 6203 10 Cadangan Dana Produksi 2 K
70 7007 Akm L/(R) s/d Th 2008 K 2 62 6203 20 Cad.Dn Sosial Pend.& Kesj Kary 2 K
30 3110 Akm Penystn Inst Sumber K 1 62 6203 30 Cad. Dana Pensiun dan Sokongan 2 K
30 3120 Akm Penystn Inst Pompa K 1 62 6203 40 Cadangan Dana Asuransi 2 K
30 3130 Akm Penystn Inst PengolahanAir K 1 62 6203 90 Cadangan Dana Lainnya 2 K
30 3150 Akm Penystn Bangunan/Gedung K 1 62 6203 60 Bagian Laba Pemda Yg Blm dibyr 2 K
30 3140 Akm Penystn Inst Trans & Dist K 1 70 7001 10 Kekayaan Asal Anggr Bel.Daerah 2 K
30 3160 Akm Penystn Peralatan&Perlengk K 1 70 7001 20 Kekayaan Asal Dana Pemb.Daerah 2 K
30 3170 Akm Penystn Kendaraan K 1 30 3190 10 Akm. Peny. Inst. Suber Air 1 K
30 3180 Akm Penystn Inventaris Kantor K 1 30 3190 20 Akm. Peny. Inst. Pompa 1 K
96 9609 Biaya Penyusutan D 5 30 3190 30 Akm. Peny. Inst. Pengl. Air 1 K
70 7014 Laba Rugi Tahun Lalu K 2 30 3190 40 Akm. Peny. Inst. Trans & Distr 1 K
70 7015 Laba Rugi ditahan Tahun 2012 K 2 30 3190 50 Akm. Peny. Inst. Bang. Gudang 1 K
70 7016 Laba Rugi th 2013 K 2 30 3190 60 Akm. Peny. Perlt & Perlengkp 1 K
50 5011 kik jangka pendek K 2 30 3190 70 Akm. Peny. Kendaraan/Alat Angk 1 K
70 7009 pengukuran kembali kik(oci) K 2 30 3190 80 Akm. Peny. Invests/Perabot Ktr 1 K
70 7008 Rekening Tampungan Dapenma K 2 91 9101 50 Biaya ABT/APT 4 D
45 4501 aset program dapenma D 1 93 9302 80 Biaya Listrik Reservoar 4 D
70 7017 Penyertaan modal ke AMDK K 2 10 1102 21 Bank BPD Capem Bobotsari 1 D
42 4207 Dokumen Berusia 2 tahun D 1 10 1102 22 BKK/BPR Terminal Purbalingga 1 D
42 4208 Dokumen Berusia 3 tahun D 1 10 1102 31 BMT Kemangkon 1 D
42 4209 Dokumen Berusia 4 tahun D 1 10 1102 32 BMT Bukateja 1 D
42 4210 Dokumen Berusia 5 tahun D 1 10 1101 12 Kas Kecil IKK Bobotsari 1 D
42 4211 Akm.Amortisasi Dokumen 2 thn D 1 10 1101 13 Kas kecil IKK Bojongsari 1 D
42 4212 Akm.Amortisasi Dokumen 3 thn D 1 10 1101 14 Kas Kecil IKK Kemangkon 1 D
42 4213 Akm.Amortisasi Dokumen 4 thn D 1 10 1101 15 Kas Kecil IKK Bukateja 1 D
42 4214 Akm.Amortisasi Dokumen 5 thn D 1 10 1101 16 Kas Kecil IKK Kejobong 1 D
10 1101 17 Kas Kecil IKK Kutasari 1 D
10 1101 18 Kas Kecil IKK Rembang 1 D
10 1102 24 BKK/BPR Kutasari 1 D
10 1102 25 BKK/BPR Kota Purbalingga 1 D
10 1102 26 BKK/BPR Kecamatan Kalimanah 1 D
10 1102 27 BKK/BPR Kecamatan Rembang 1 D
10 1102 28 BRI Cabang Purbalingga 1 D
10 1102 29 BMT Bojongsari 1 D
10 1201 11 Deposito BKK/BPR Terminal Pbg 1 D
10 1201 12 Deposito BKK Kota Purbalingga 1 D
10 1201 13 Deposito BKK/BPR Kec.Rembang 1 D
10 1201 15 Deposito di Bank BRI 1 D
10 1402 11 Piutang Pajak PPN 1 D
10 1403 10 Pendapatan Bunga Deposito 1 D
10 1403 11 Pendapatan Bunga Giro 1 D
10 1502 10 Persed. Bahan Pembantu 1 D
10 1502 20 Persed. BBM & Minyak Pelumas 1 D
10 1502 30 Persed. Suku Cadang 1 D
10 1502 40 Persed. Pipa Persil 1 D
10 1502 50 Persed.Alat Tulis & Cetakan 1 D
10 1502 90 Rupa-Rupa Persed.Bhn Ops. Lain 1 D
10 1509 10 Persed. Dlm Perjalanan 1 D
10 1509 90 Rupa-Rupa Persed.Lainnya 1 D
10 1503 10 Pipa PVC 1 D
10 1503 11 Pipa GI 1 D
10 1503 20 Water Meter 1 D
10 1503 30 Accesoris PVC 1 D
10 1503 31 Accesoris GI 1 D
88 8801 70 Pendapatan Denda Air & Non Air 3 K
10 1102 34 BMT Kalimanah 1 D
10 1101 10 Kas Besar 1 D
80 8102 11 Pendapatan Pendaftaran SR 3 K
80 8101 11 Pendapatan Air Tangk 3 K
80 8102 12 Biaya Balik Nama 3 K
96 9605 10 Bunga Pinjaman 5 D
96 9605 20 Biaya Komitmen 5 D
96 9605 30 Denda Keterlambatan pemb. angs 5 D
96 9605 90 Rupa-rupa Bb Keuangan Lainnya 5 D
96 9606 10 Pemeliharaan Inv. Kantor 5 D
96 9606 20 Pemeliharaan Kendaraan 5 D
96 9606 30 Pemeliharaan Bangunan 5 D
96 9606 40 Pemeliharaan Instalasi 5 D
96 9606 50 Pemeliharaan Taman & Lapangan 5 D
96 9607 10 Biaya Penyisihan Piutang 5 D
96 9607 20 Biaya Penghapusan Piutang 5 D
96 9608 10 Biaya Iuran/Berlangganan 5 D
96 9608 60 Biaya Asuransi/Keamanan 5 D
96 9608 70 Biaya Pajak Pemda/Perijinan 5 D
96 9608 90 Rupa-rupa biaya Umum Lainnya 5 D
96 9608 20 Biaya Badan Pengawas 5 D
96 9608 30 Biaya Perjalanan Dinas 5 D
96 9608 40 Biaya Jasa Profesional 5 D
96 9608 50 Biaya Sewa 5 D
50 5001 01 CV. Teko Jaya Semarang 2 K
50 5001 02 CV. Sumo & Co Purwokerto 2 K
50 5001 03 Kopkar Braling Tirta PDAM Pbg 2 K
50 5001 04 CV. Multi Sejahtera 2 K
50 5001 05 PT Sinar Quality InterNusa,Jkt 2 K
50 5001 06 PT. Haneda Putra A Jakarta 2 K
50 5001 07 CV. Karya Baru,Semarang 2 K
50 5001 08 PT. Catur Adi Manunggal Smg 2 K
50 5001 09 CV. Artha Kencana,Klmnh Pbg 2 K
50 5001 10 TB. Fajar Purwokerto 2 K
50 5001 11 CV. Mitra Perwira Purbalingga 2 K
50 5001 12 CV "t" Jaya,Semarang 2 K
50 5001 13 CV. Mandala Giri Purbalingga 2 K
50 5001 14 CV. Mukti Purwokerto 2 K
50 5001 15 CV Putra Pongga,Slby,Klmnh 2 K
10 1608 01 CV. Teko Jaya Semarang 1 D
10 1608 02 CV. Sumo & Co Purwokerto 1 D
10 1608 03 Kopkar Braling Tirta PDAM Pbg 1 D
10 1608 04 CV. Multi Sejahtera Cirebon 1 D
10 1608 05 Roxander Purwokerto 1 D
10 1608 06 PT. Haneda P.A. Jakarta 1 D
10 1608 07 TB. Baskara Purwokerto 1 D
10 1608 08 PT. Catur Adi Manunggal Smg 1 D
10 1608 09 TB. Toserba Purbalingga 1 D
10 1608 10 TB. Fajar Purwokerto 1 D
10 1608 11 CV. Mitra Perwira Purbalingga 1 D
10 1608 12 TB. Asia Jaya Purbalingga 1 D
10 1608 13 CV. Mandala Giri Purbalingga 1 D
10 1608 14 CV. Mukti Purwokerto 1 D
10 1608 15 PT. Sanex Agung Motor Pwt 1 D
50 5001 16 Purwokerto Music Centre,Pwt 2 K
50 5001 17 Bengkel Las Agung 2 K
10 1409 10 Piutang AMDK 1 D
50 5001 18 KAP Ijang Soetikno 2 K
10 1605 40 Pph psl 21 1 D
10 1605 50 Pph psl 26 1 D
80 8102 13 Jaminan Langganan 3 K
80 8102 14 Jaminan Pipa Dinas 3 K
80 8102 15 Pindah Meter 3 K
80 8102 21 Restitusi Voucher 3 K
80 8102 22 Lain-lain(Pembulatan Penerimaa 3 K
50 5001 19 PT Cahaya Aqila,Gondoriyo,Smg 2 K
50 5001 20 Computama Computer Pwt 2 K
50 5001 21 Tk Mataram Pbg 2 K
50 5001 22 Cv Cahaya Citra Lestari Jkt 2 K
50 5001 23 Artha Graha Motor Pwt 2 K
10 1605 60 Pph psl 22 1 D
50 5001 24 CV Cipta Raharja Pbg 2 K
50 5001 25 PT Armada Int"l Pwt 2 K
50 5001 26 Tk Textil Daerah Pwt 2 K
50 5001 27 CV Bina Tirta Smg 2 K
10 1409 12 Piutang PEMDA 1 D
50 5001 28 CV Indra Computer Pbg 2 K
91 9101 90 Rupa2 Ops Sumber 4 D
91 9103 10 Retribusi Air Bawah Tanah RABT 4 D
96 9603 80 Biaya Rupa-rupa HUBLANG 5 D
96 9604 80 By rupa-rupa Litbang 5 D
50 5001 50 CV Dayaguna & co Pwt 2 K
10 1503 40 Barang Bantuan P2P 1 D
10 1503 50 Barang IKK Bukateja 1 D
10 1503 51 BArang IKK Bobotsari 1 D
10 1503 52 Barang IKK Kutasari 1 D
10 1503 53 BArang IKK Bojongsari 1 D
10 1503 54 Barang IKK Mrebet 1 D
10 1503 55 Barang IKK Kemangkon 1 D
10 1503 56 Barang IKK Kejobong 1 D
10 1503 57 BArang IKK Rembang 1 D
10 1503 58 Barang AMDK 1 D
10 1503 60 Persediaan APBD 1 D
10 1503 90 Persediaan ATK 1 D
50 5001 30 CV Dian Artha Buana Pwt 2 K
62 6203 11 Cadangan Dana Meter 2 K
10 1102 35 BMT Padamara 1 D
10 1102 23 Bank BPD Cabang 1 D
50 5006 50 PPN SR 2 K
80 8102 41 Pendapatan Penutupan 3 K
10 1307 01 Karsun 1 D
10 1307 02 Wagiman 1 D
10 1307 03 Rahmanto 1 D
10 1307 04 Parwono 1 D
10 1307 05 Teguh K 1 D
10 1307 06 Wildan Nurul Huda 1 D
10 1307 07 Riyanto 1 D
10 1307 08 Erma S 1 D
10 1307 09 Barip 1 D
10 1307 10 Irawan 1 D
10 1307 11 Rofid Alwan Budianto 1 D
10 1307 12 Bangun P 1 D
10 1307 13 Hani F 1 D
10 1307 14 Adik Purwo S 1 D
10 1307 15 Agus Triono 1 D
10 1307 16 Lukman H 1 D
10 1307 17 Yuni S 1 D
10 1307 18 Weby Dwi Arya Kusuma 1 D
10 1307 19 Wahyudi 1 D
10 1307 20 Rahmat W 1 D
10 1307 99 Lain-Lain (Kacamata) 1 D
10 1307 21 Minanto 1 D
10 1307 22 Sugeng 1 D
10 1307 23 Subur Winarso 1 D
88 8801 80 Pendapatan Menyewakan Truk 3 K
80 8102 42 Pendapatan Pindah Jalur 3 K
80 8102 43 Pendapatan Merusak Segel 3 K
80 8102 44 Pendapatan Pindah Golongan 3 K
80 8102 45 Merusak Segel 3 K
80 8102 46 Pendapatan Denda Memindah Mete 3 K
80 8102 47 Pendapatan Denda Pompa 3 K
80 8102 48 Pendapatan Magnet 3 K
50 5001 31 Konveksi Rasio Pwt 2 K
10 1601 10 Sewa Kantor IKK Bobotsari 1 D
10 1601 11 Sewa Tanah Sbr Serayu Larangan 1 D
10 1601 12 Sewa Kantor Loket Bojongsari 1 D
10 1601 13 Sewa Loket Bukateja 1 D
10 1601 14 Sewa Loket Kalimanah 1 D
10 1601 15 Sewa Mata Air Limbangan 1 D
10 1601 16 Sewa Tanah Kutasari 1 D
50 5001 32 Indo Computer Pwt 2 K
10 1601 17 Pembelian seragam dinas kary. 1 D
10 1601 18 bbm U/ ops kend dinas 1 D
10 1409 13 Kopkar Braling Tirta 1 D
10 1102 36 BMT Bobotsari 1 D
10 1102 37 BPR Syariah Purbalingga 1 D
10 1603 10 Dekan Jaya(altimeter digital) 1 D
50 5001 34 CV Satria Muda Purbalingga 2 K
40 4101 01 Pengemb.air bersih Kr Reja 09 1 D
50 5001 35 CV Surya Teknik Pwt 2 K
50 5002 01 RABT 2 K
50 5002 02 ASTEK 2 K
50 5002 03 LISTRIK 2 K
50 5002 04 TELEPHON 2 K
50 5002 05 DAPENMA PAMSI 2 K
50 5002 06 Pembuatan Keplek 2 K
50 5002 07 THR u/ Kolega 2 K
50 5002 08 Komputerisasi 2 K
50 5002 09 Analisa Tarip 2 K
50 5002 10 Pameran Pembangunan 2 K
50 5002 11 RMTF 2 K
50 5002 99 Lain-lain 2 K
50 5001 36 PD Barokah Cipta Persada Pbg 2 K
50 5001 37 Tk Textil Laris Purwokerto 2 K
80 8102 23 Pindah Loket 3 K
10 1201 14 BPD Capem Kota Pbg 1 D
10 1201 16 BPD Capem Bobotsari 1 D
30 3190 90 Akumulasi Penyus. Keseluruhan 1 K
50 5001 38 TB Fajar Agung Purbalingga 2 K
50 5006 60 Utang Pajak Psl 4 2 K
50 5001 39 CV Jasa Tirta Purwokerto 2 K
50 5001 40 Tk Textil Jodo Purwokerto 2 K
50 5001 41 CV Hayat Abadi Purwokerto 2 K
50 5007 10 Bagian Utang Jk.Pjg.J.T. 2 K
50 5007 20 Utang Jangka Pjg JT-Lua Neg. 2 K
50 5007 80 Utang Leasing Jatuh Tempo 2 K
50 5008 10 Beban Bunga Pinj.YMH Dibayar 2 K
50 5008 20 Beban Denda Bunga YMH Dibayar 2 K
50 5008 30 Beban Denda Pokok YMH Dibayar 2 K
50 5008 80 Utang Bunga-Leasing 2 K
60 6204 10 Resiko Piutang 2 K
10 1102 38 BKK/BPR Kecamatan Mrebet 1 D
50 5001 42 Sumber Baru Mobil Purwokerto 2 K
50 5001 43 Tk Gema Elektrik Pwt 2 K
50 5001 44 cv Wisuda Purwokerto 2 K
50 5003 03 Biaya Listrik 2 K
50 5003 04 Jasa Profesional 2 K
10 1101 11 Kas Kecil Pusat 1 D
10 1409 11 Drs. Djaenal Abidin 1 D
10 1409 14 Piutang Dharma Wanita 1 D
40 4101 02 Rehab ruang kerja ex subbag gd 1 D
10 1603 11 Tanah u/ pemb. bak ikk Kjb 1 D
40 4101 03 dist.4"-2.482,jl.bnjrnsr-kemkl 1 D
50 5001 45 Speed Computer Pwt 2 K
10 1409 15 Pencucian mobil&kend PDAM 1 D
50 5001 47 PT Tjahtiam Mutiara Sltn Clp 2 K
40 4101 04 dist 4"-1.554m,gemuruh-koptnwr 1 D
88 8801 90 Pendapatan dari unit AMDK 3 K
50 5001 48 Andika Purwokerto 2 K
50 5001 49 Bengkel Las Tansah Jaya Pbg 2 K
50 5001 51 UD Besi & Las Keluarga Pwt 2 K
10 1409 16 piutang pemda(tnh kr gambas) 1 D
50 5001 52 Perc. Sinar 12 Bkt,Pbg 2 K
10 1509 11 Tempat kml 1 D
50 5001 53 Hastuti DN,Gemuruh Pbg 2 K
50 5001 54 UD Karya Wiguna,Wirasana Pbg 2 K
10 1601 19 Sema MA Kalibodas Losari,Rmb 1 D
50 5001 46 TB Rakyat Purbalingga 2 K
10 1307 24 Eko Purnomo 1 D
50 5001 55 PTKarsamudika Andalan UtamaJkt 2 K
40 4101 05 dist 6"-1.298m,kl kbng-grecol 1 D
40 4101 06 Perenc teknis penyedia air-rmb 1 D
40 4101 07 perenc penyedia air pengadegan 1 D
40 4101 08 pemas dist galuh-bojongsari 1 D
40 4101 09 biaya pemb. kantor ikk rmb 1 D
40 4101 10 Ganti jar.dist jl Panjaitan 1 D
40 4101 11 pemas pp dist kl kjr,kl gondng 1 D
40 4101 12 180 ubin tanah belakng kantor 1 D
40 4101 13 geser jmbtn pp trans 6",pdmr 1 D
40 4101 14 pembuatn sumur dlm,losari,rmb 1 D
40 4101 15 dist 1,5" rw II&III bojanegara 1 D
40 4101 16 dist ds galuh&klkjr 6",10",12" 1 D
50 5001 56 Depo Pelita Sokaraja 2 K
40 4101 17 pemas dist 2",wilangan,klpsawt 1 D
40 4101 18 Pemas pp dist 2"-kl jebug,mewk 1 D
40 4101 19 rhb pengaman broncap bt putih 1 D
40 4101 20 pemas pp dist ds sempor,klgndg 1 D
40 4101 21 perb aspal bks galian pdm-term 1 D
40 4101 22 Pemb musholla,kmr mnd,dpr pst 1 D
40 4101 23 pemas pp dist ds brecek,kl gnd 1 D
40 4101 24 rehab kantor ikk kmk 1 D
40 4101 25 pemb gd blkng kantor 1 D
40 4101 26 Pemb broncap ma Mulang Kr Cgk 1 D
40 4101 27 dist 6"-92m,4"-1.086,jatisaba 1 D
80 8101 12 Penjualan air ke PDAM Pemalang 3 K
80 8101 13 Pendapatan HU 3 K
10 1307 25 Triana Nurlaeli 1 D
10 1307 26 Rusmadi 1 D
10 1307 27 Heri Prasetyo 1 D
10 1307 28 Triyono 1 D
10 1307 29 Suyitno 1 D
10 1307 30 Satria Adi Nugraha 1 D
10 1307 31 Widodo PP 1 D
10 1307 32 PY Wibowo 1 D
10 1307 33 Darto 1 D
10 1307 34 Sugianto 1 D
10 1307 35 Sopandi 1 D
10 1307 36 Utami B,SH 1 D
10 1307 37 Bambang PAP,BSc 1 D
10 1307 38 Endah S,SH 1 D
10 1101 19 Kas Kecil IKK Padamara 1 D
10 1101 21 Kas Keci IKK Kalimanah 1 D
10 1307 39 Diana W,SPd 1 D
40 4101 28 dist 2"-635m,1,5"-284m,penishn 1 D
62 6203 50 Cadangan Dana Umum 2 K
62 6203 70 Cadangan Dana Pembinaan 2 K
10 1307 40 Susmono Rahmat 1 D
50 5001 61 Tk Pelita Sokaraja,Skj 2 K
50 5001 59 UD Besi & Las Amanah,Pbg 2 K
50 5001 62 CV Mitra Adi Persada,Smg 2 K
10 1603 12 Toyota Avanza hitam, 3556 1 D
40 4101 29 dist 2"-1.002m,gemuruh 1 D
40 4101 30 Pengemb jar wil kec Kalimanah 1 D
40 4101 31 Pengemb jar dist Kr So,Blater 1 D
40 4101 32 Pengemb jar dist ds Kr Petir 1 D
40 4101 33 Pengdn tnh bak penampGombangan 1 D
40 4101 34 Pengdn tnh reserv ma Gombangan 1 D
40 4101 35 Pengdn tnh ma Bulakan,Pagerndg 1 D
40 4101 36 Pengdn tnh ma LimpakdauII,Mnjl 1 D
40 4101 37 Pengdn tnh bak penam ma Mulang 1 D
10 1201 17 Deposito BKK Kalimanah 1 D
10 1307 41 Gunarso 1 D
10 1307 42 Lujeng 1 D
10 1307 43 Sunano 1 D
10 1307 44 Heri Nurcahyo 1 D
10 1307 45 Saryoto 1 D
10 1307 46 Edi Subangun 1 D
10 1307 47 Lego Purbowo 1 D
10 1307 48 Rakhmanto A 1 D
10 1307 49 Bambang Subiyakto 1 D
10 1307 50 Anas Sumaryo 1 D
10 1307 51 Minanto 1 D
10 1307 52 Minanto 1 D
10 1201 31 Deposito BPR Syariah Pbg 1 D
80 8101 14 Pencurian Air 3 K
50 5001 63 PT Inti Kaliasin,Surabaya 2 K
10 1307 53 AMDK 1 D
50 5001 64 PT Nasmoco, Purwokerto 2 K
10 1307 54 Riyono 1 D
10 1601 20 um bbm solar kekeringan 1 D
40 4101 38 Pemas jar dist Perum KarsenPbg 1 D
40 4101 39 Pemas jar dist Harmoni,Bjngr 1 D
40 4101 40 Pemas jar dist GP Asri,KrSentl 1 D
40 4101 41 Pemas jar dist PT SungSim,Pbg 1 D
40 4101 42 Pemas jar dist RSUD Pbg 1 D
40 4101 43 Pemas jar dist BUMPER, Pbg 1 D
40 4101 44 Pengg.jar dist jl Raya Bajong 1 D
40 4101 45 Pemas HU Serayu,Kr Anyar,Mrbt 1 D
40 4101 46 Normaliss irigasi SryLrgn,Mrbt 1 D
40 4101 47 Normaliss irigasi BtPutih,Mrbt 1 D
50 5001 65 UD Srayan Jaya,Pbg 2 K
10 1307 55 Supandi 1 D
40 4101 48 Penamb jar kampng br ggjengkol 1 D
40 4101 49 Penamb jar perum babkan estate 1 D
40 4101 50 rehab ex r spi 1 D
40 4101 51 dist 2"-712m,1,5"-804m bojong 1 D
40 4101 52 dist 3"-788m,2"-252m munjul 1 D
40 4101 53 dist 3"-200m,2"-784m majasari 1 D
40 4101 54 Suplai debit dari MA Wds Kelir 1 D
40 4101 55 Pndh jlr pp trans Terminal Pbg 1 D
40 4101 56 Broncap darurat MA Wds Kelir 1 D
50 5001 66 CV Ambar Agung,Jl Veteran Pwt 2 K
50 5001 67 CV Mukti Jaya,Jkt 2 K
50 5001 68 CVv Sumber Teknik Pratama,Pwt 2 K
50 5001 69 PT Bintang Surya SA,Jkt 2 K
10 1501 10 kaporit 1 D
10 1501 11 liquid chlorin (cl2)99,5% 1 D
10 1307 56 Sutarman 1 D
10 1307 57 Tur Tjahyoto 1 D
10 1307 58 Subekhi 1 D
10 1307 59 Iksanto 1 D
10 1307 60 Teguh Prihatin 1 D
10 1307 61 Suwarman 1 D
10 1307 62 Triyono(B) 1 D
10 1307 63 Elvi Restu H 1 D
10 1307 64 Rasito 1 D
10 1307 65 Angkat Prasetyo 1 D
10 1307 66 Yulianto HD 1 D
10 1307 67 Susaryono 1 D
10 1307 68 Fathun 1 D
10 1307 69 Afia Widodo 1 D
10 1307 70 Yudi Haryanto 1 D
10 1307 71 Makmum 1 D
10 1307 72 Widi Haryani 1 D
10 1307 73 Ratmono 1 D
10 1307 74 Saryono 1 D
10 1307 75 Edi Harjono 1 D
10 1307 76 Riyadi 1 D
10 1307 77 Suroyo 1 D
10 1307 78 Andjar Iswanto 1 D
10 1307 79 Eko Margianto 1 D
10 1307 80 Hadi 1 D
40 4101 57 Pengemb dk Kedongkek,Toyareka 1 D
40 4101 65 Pengemb Perum Mewek 1 D
40 4101 58 Pengemb jl G.Sambeng,KdGampang 1 D
40 4101 59 Pengemb Perum Harmoni,Bjnegara 1 D
40 4101 61 Pengemb jl LetYusup/jl Lingkar 1 D
40 4101 62 Pengemb jl LetSudani,Kemb Kln 1 D
40 4101 63 Pengem jl Raya Blater,Klmnh 1 D
40 4101 64 Pengemb dk Kr Sari,Toyareja 1 D
40 4101 71 Pengemb ds Kalitinggar Lor 1 D
40 4101 68 Pengemb ds Kr Sari,Kalimanah 1 D
40 4101 67 Pengemb ds Kd Wuluh,Kalimanah 1 D
40 4101 66 Pengemb dk Kr So,Blater 1 D
40 4101 70 Pengemb ds Kalitinggar Kidul 1 D
40 4101 72 Pengemb ds Kr Petir kec Klmnh 1 D
40 4101 73 Rhb jar KrCegak(pipatrans 8"&6 1 D
40 4101 78 Rhb jar ds Rabak,Klmnh 2" 1 D
40 4101 79 Rhb jar jlKetapang,KalWetan 2" 1 D
40 4101 80 Rhb jar KrJambe-KdWringin,4"3" 1 D
40 4101 77 Rhb jar Pagutan-Patemon 3" 1 D
40 4101 82 Rhb jar Penaruban-KlKjr 6"4" 1 D
40 4101 83 Rhb jar let Kusni Jatisaba 4" 1 D
40 4101 81 Pindah jlr pp dns 18sr,GPA 1 D
40 4101 75 Rhb Tlaga-Sumingkir 6" 1 D
40 4101 74 Rhb jar Komplek Psr Pbg 1 D
40 4101 76 Rhb jar pipa ikk Bobotsari 1 D
40 4101 84 Penamb jar dist Prbys-Pdmr 1 D
40 4101 85 Rhb jl AW Sumarmo-Kompo acp10" 1 D
40 4101 97 Pemb Pagar MA Mulang 1 D
40 4101 104 Rhb kntr r.teknik & umum 1 D
40 4101 101 Pemb Pagar Reserv Kr Gambas 1 D
40 4101 96 Pemb kantor ikk Rembang 1 D
40 4101 95 Pemb gdng penyimpn brg invntrs 1 D
40 4101 99 Rhb jl & jemb MA Bedahan 1 D
40 4101 98 Rhb pagar MA Limpak Dau 1 D
40 4101 100 Rehab broncap MA Kr Pelus 1 D
40 4101 94 Pemb kantor ikk Padamara 1 D
40 4101 93 Pemb talud pengmn trans Prbys 1 D
40 4101 111 Pengadaan tnh dsCipaku(Reservr 1 D
40 4101 92 Renovasi kantor ikk Klmnh 1 D
50 5001 70 CV Siswoyo,Pwt 2 K
40 4101 86 Pemb kantor ikk Bojongsari 1 D
40 4101 87 Pemb jemb pipa ds Meri 1 D
40 4101 88 Pemb jemb pipa ds Kr Aren 1 D
40 4101 89 Pemb jemb pipa ds kr Reja 1 D
40 4101 90 Pemb jemb pipa ds Kr Reja 1 D
40 4101 91 pemb jemb pipa ds Kr Reja 1 D
50 5001 71 Kasmid, Kades Purbayasa 2 K
50 5001 72 Cv Teknik Pompa,Smg 2 K
50 5001 73 Cv Sumber Baru,Bandung 2 K
50 5001 74 Indotech Global System,Pwt 2 K
10 1307 81 Drs.Hardi W,MSi 1 D
10 1307 82 Rusiyati,SH 1 D
50 5001 75 Sakiman,UPTD wil I Pbg 2 K
50 5001 76 PT Multi Instrumentasi,Bandung 2 K
50 5001 77 Cipto Grafindo 2 K
40 4101 112 PSAB desa Banjaran,Bojongsari 1 D
50 5001 78 Aneka Teknik,Pwt 2 K
50 5001 79 Cv Surya Perdana,Kelurhn Wrsn 2 K
10 1307 83 Yuni Astuti 1 D
10 1409 17 piut pd wiwit h 1 D
40 4101 113 616/264.b/VII/08,ma sikopyah 1 D
50 5001 80 Adhimukti Wira Kencana,Pbg 2 K
50 5001 81 Cv Bangun Tirta Nusantara,Sby 2 K
40 4101 69 pengemb ds Sokawera,Pdmr 1 D
50 5001 82 CV Adika Kencana G, Tangerang 2 K
50 5001 83 CV Asia Raya,Pwt 2 K
50 5001 84 Bambang Murdwiatmoko 2 K
10 1601 21 Sewa MA Bataputih,Cipaku 1 D
50 5001 85 PT Galang Kreasi Usahatama,Jkt 2 K
10 1409 19 piutang tunggakan air(edward) 1 D
10 1307 84 Erma Susilowati 1 D
10 1409 18 PIUT REK AIRPerpamsi 1 D
10 1101 20 Kas Kecil IKK Mrebet 1 D
50 5001 86 CV. Aneka Jaya Kaligondang PBG 2 K
50 5001 87 CV Indo Mandiri,Pwt 2 K
10 1601 22 Sewa ma Situ Kajongan 1 D
10 1102 39 BKK Bobotsari 1 D
50 5006 70 Utang Pajak Pasal 29 2 K
10 1307 86 Sugeng(B) 1 D
10 1307 87 Yuliati 1 D
10 1307 88 Maun Suseno 1 D
10 1307 89 Suparno 1 D
10 1307 90 Sugihardjo,SE 1 D
10 1307 91 Hartati 1 D
10 1307 92 Yuniati Nurhayah 1 D
10 1307 93 Untung Widadi 1 D
50 5001 88 CV Eka Sukses Mandiri,Jkt 2 K
50 5001 89 Dovi Wahyu P,Griya Adhibusana 2 K
50 5001 90 Superpam, Jkt 2 K
50 5001 91 Cv Harta Karya M,BojanegaraPbg 2 K
50 5001 92 Cv Mandiri Elektrikal,Kjb Pbg 2 K
10 1101 22 Kas kecil AMDK 1 D
50 5001 93 Cv Kautsar, Pwt 2 K
10 1307 85 Suhono 1 D
10 1601 23 sewa ma tlagayasa 1 D
50 5001 94 P3M Akatirta Magelang 2 K
50 5001 95 PT Batraco,Pwt 2 K
10 1409 20 piut rek air rsu pbg/waluyo 1 D
50 5001 96 PT Armada Int"l Motor,Pwt 2 K
50 5001 97 Sinar Tirta, Pwt 2 K
10 1307 94 Endah Sri Astuti 1 D
10 1307 95 Budi Hapsari 1 D
10 1307 96 Purwaningsih 1 D
10 1307 97 Clara Budiarti 1 D
10 1307 98 Berliana DLK 1 D
10 1307 100 Didik Haryanto,SE 1 D
10 1307 101 Sri Lestari 1 D
10 1307 102 Mohamad Arifin 1 D
10 1307 103 Rakum 1 D
10 1307 104 Slamet Agil Setiawan 1 D
50 5002 12 tunjangan kesehatan 2 K
50 5001 98 PT Anugerah Tirta Sukses,Jkt 2 K
10 1101 23 Kas kecil ikk Kr Reja 1 D
88 8801 11 pendptn pot coklit rek hankam 3 K
50 5001 99 CV DAMAR JATI 2 K
50 5001 100 CV.Langgeng Bojong-Purbalingga 2 K
50 5001 101 ATMAJAYA CASTING IRON 2 K
10 1307 105 Siwan 1 D
10 1307 106 Ivan Yulianto 1 D
10 1307 107 Iwan Infantri 1 D
10 1307 108 Eli Kristiandani 1 D
50 5001 102 CV Prambanan, Smg 2 K
50 5001 103 CV Indera Cipta,Smg 2 K
10 1307 109 Ambar Pujiarto 1 D
10 1307 110 Sugi Astuti 1 D
10 1307 111 Hayati M 1 D
50 5001 104 Sambas Educomputel,Pbg 2 K
10 1509 12 Tutup galon embos PDAM 1 D
10 1509 13 Tisue pembersih 70% alkohol 1 D
10 1509 14 Segel galon logo PDAM 1 D
10 1509 15 Galon polos grade B 1 D
96 9609 10 Biaya Pnystn Inventaris Kantor 5 D
96 9609 20 Biaya Penyustn Kendaraan 5 D
96 9609 30 Biaya Penystn Gedung 5 D
96 9609 40 Biaya Penystn Peralatan&Perlen 5 D
50 5008 2 K
91 9103 20 Retribusi air permukaan 4 D
10 1603 13 isuzu / pick up lc(hitam) 1 D
50 5001 105 PT Armada Int"l Motor,Cilacap 2 K
10 1601 24 sewa ma Kalibodas,Rembang 1 D
10 1409 21 kelebihan ppn sr 2009,2010 1 D
10 1609 10 kelbhn pembyrn iuran pensiun 1 D
10 1609 11 um cv indra cipta,smg 1 D
10 1609 12 um cv prambanan,smg 1 D
50 5001 106 Corner Comp,jl mangga 1,babakn 2 K
50 5001 107 PPLH Rimba Jati,Bobotsari,Pbg 2 K
50 5001 108 Cv Armantirta Purbasindo,Ktsr 2 K
50 5001 109 PT.Cipta Sukses Bersama,JktPst 2 K
50 5001 110 Tk Sepatu Cinderella, Pwt 2 K
10 1307 112 Rino 1 D
10 1307 113 Riyadi Ktsr 1 D
10 1307 114 Siswanto 1 D
50 5001 111 cv satu titik,pbg 2 K
50 5001 112 Bp.Darmono,Amd, Jatijajar,Kbmn 2 K
50 5001 113 LPT Fak Teknik Unwiku,Pwt 2 K
10 1609 13 pembyrn dn kesejahteraan,2011 1 D
40 4101 114 pndah conex dist gembong-brobt 1 D
50 5002 13 thr 1 dir+karyawan 2 K
50 5002 14 thr 2 dir+karyawan 2 K
50 5002 15 hnr dwn pembina & pengawas 2 K
50 5002 16 Gaji 13 & Tambahan Penghasilan 2 K
50 5002 17 tunjangan perumahan direktur 2 K
50 5002 18 penghargaan masa kerja dirktr 2 K
96 9605 40 pokok pinjaman 5 D
50 5006 80 ppn amdk 2 K
50 5002 19 pengadaan kend kary 2 K
50 5001 114 King Grafika,Pwt 2 K
50 5001 115 Trans Artha Comp,Mrebet,Pbg 2 K
40 4101 115 jar dist klmnh estate klpa swt 1 D
40 4101 116 jwmbtn pipa dpn spbe bjsr 1 D
40 4101 117 dist 1,5"-gg nangka kl kabong 1 D
40 4101 118 dist jl skr kennga&teratai,gak 1 D
10 1307 115 Susi Herawati 1 D
10 1307 116 Elan Mukti Zein 1 D
10 1307 117 Sutoyo 1 D
50 5002 20 cadangan pengganti cuti 2 K
40 4101 119 dist 10" lambur,mrebet 1 D
40 4101 120 dist 6",sry krmnyr,bbs 1 D
40 4101 121 tertier 1,5",gs pajerukan 1 D
40 4101 122 rhb jembtn pp s nutug,sumampir 1 D
50 5002 21 insntp penyusunan rkap 2 K
50 5002 22 kewajiban imbalan kerja 2 K
62 6203 80 Insentif Pengambil Kebijakan 2 K
50 5002 23 penyesuaian sak etap 2 K
50 5002 24 insentip penyusunn l/k 2 K
50 5002 25 b litbang ikk baru 2 K
50 5002 26 Tambahan tkk Jan-Sept 2025 2 K
40 4101 123 konek jar dist reserv bajong 1 D
40 4101 124 tertier ponpes az zuhuriyah 1 D
10 1307 118 Sechan 1 D
50 5002 27 cad dapenma 2 K
50 5001 116 cv solusi pradana,purwokerto 2 K
10 1307 119 Margono 1 D
50 5001 117 Global Technologi Solution,Pwt 2 K
50 5001 118 CV Trias,jl Sultan Agung,Pwt 2 K
50 5001 119 PT Selaras Cipta Solusi,Jkt 2 K
50 5001 120 PT Globalindo Buana,Jkt 2 K
50 5001 121 CV Indra Kila Elektrikal,Kjb 2 K
50 5001 122 Cv Atha Anugrah,Kr Jambe,Pdmr 2 K
50 5001 123 Cv Armacon,Kr Lewas rt 4/1,pwt 2 K
50 5001 124 Cv Multi Visi Karya,Bbs,Pbg 2 K
40 4101 125 pemb.jemb.pipa pdam kl klawing 1 D
40 4101 126 ded trans 10"-6.610m,meri-gmrh 1 D
40 4101 127 ded dist buper-kr sentul,pdmr 1 D
40 4101 128 ded jar.ruas pdmr-kd wuluh 1 D
40 4101 129 dist10"-250m,6"-234m&gi10"-12m 1 D
10 1307 120 Sarwono 1 D
50 5001 125 Tk Sepatu Metro,Purwokerto 2 K
50 5001 126 KS Sport,Purwokerto 2 K
50 5001 127 Wahana Tirta,Semarang 2 K
50 5002 28 insntp keg na 2 K
40 4101 130 rhb&tambhn pp jembtn ds bajong 1 D
40 4101 131 ganti pp dist 6",serayu kranyr 1 D
10 1201 32 Deposito BKK Kemangkon 1 D
10 1102 40 BKK Kemangkon 1 D
50 5002 29 insntp penyusunan SOTK 2 K
50 5001 128 CV Damar Kumala,Semarang 2 K
40 4101 132 DED jar pp trans curug karang 1 D
50 5002 30 Gaji Ke-13 Dewas+Sekre. 2 K
50 5002 31 penghargaan ms kerja 10&20 th 2 K
50 5001 129 Toko Logam Jaya,Purwokerto 2 K
50 5001 130 PT Merdeka Indrayasa,Smg 2 K
40 4101 133 43 ubin tnh serayu kr anyar 1 D
10 1101 25 Kas besi kantor pusat 1 D
10 1101 26 Kas besi ikk Kalimanah 1 D
10 1101 27 Kas besi ikk Padamara 1 D
10 1101 28 Kas besi ikk Kutasari 1 D
10 1101 29 Kas besi ikk Bojongsari 1 D
10 1101 30 Kas besi ikk Bobotsari 1 D
50 5001 131 CV Maxima Artamedia,Pbg 2 K
50 5002 32 cadangan dplk 2 K
50 5001 132 Penjahit Dewi Shinta,Jepara 2 K
10 1609 14 um pembelian tanah 1 D
50 5001 133 Cv "KSF",Purbalingga 2 K
10 1307 121 Suwahnan 1 D
10 1307 122 Suyadi 1 D
50 5001 134 CV Tirta Agung,Ungaran,Jateng 2 K
50 5002 33 insentip keg. mbr 2 K
50 5001 135 Cv Niaga Perkasa,Smg 2 K
50 5001 136 CV Harapan Mulia Berdikari,Pbg 2 K
40 4101 134 pengaman bandul jmbtn pp 10" 1 D
10 1307 123 Ramidin 1 D
10 1307 124 Endro Purwoko 1 D
50 5001 137 CV Espro,Semarang 2 K
50 5001 138 CV Cipta Desain,Semarang 2 K
40 4101 135 ded optimaliss ikk mrb,bbs&klg 1 D
10 1307 125 Herman 1 D
50 5001 139 CV Berlian Parama 2 K
10 1509 16 lied cup pet 12-pe 18 lldpe 1 D
10 1509 17 master cetak/cilinder 1 D
10 1509 18 polly cup 220 ml 1 D
10 1509 19 straw/sedotan 1 D
10 1509 20 kartob box cup 1 D
10 1201 33 deposito bank muammalat 1 D
50 5002 34 pengadaan sepatu kary. 2 K
50 5002 35 pengadaan training 2 K
50 5002 36 pengadaan seragam perpamsi 2 K
10 1102 41 Bank Muamallat 1 D
10 1102 42 BKK Bukateja 1 D
50 5002 37 cadangan bb penagihan rekening 2 K
10 1307 126 Santosa 1 D
10 1307 127 Amin Supriyatno 1 D
10 1307 128 Purwanto 1 D
10 1307 129 Amin Sugiarto 1 D
10 1307 130 Kusdi Herwonggo 1 D
10 1101 24 Kas kecil Kaligondang 1 D
10 1601 25 sewa kantor ikk Kaligondang 1 D
88 8801 12 Pendapatan jasa adm rek air/na 3 K
10 1307 131 Sulistyono 1 D
10 1307 132 Rahmanto Bjs 1 D
10 1307 133 Moh Mansur Kholik 1 D
10 1307 134 Damami 1 D
10 1307 135 SAMSURI 1 D
10 1101 31 kas kecil cabang kota Pbg 1 D
10 1307 136 Sri Murni 1 D
10 1307 137 Adi Susana 1 D
10 1307 138 Slamet Agil Setiawan 1 D
40 4101 136 menaikan rel/gelagar j.klawing 1 D
10 1307 139 Riyanto,SE.M.Si.Ak 1 D
40 4101 137 pemas dist 4",2",1,5" kajongan 1 D
50 5002 38 insntp tim manajemen asset 2 K
50 5002 39 bingkisan lebaran 2 K
10 1307 140 SLAMET SUBEKTI 1 D
10 1601 26 thr kary 1 D
10 1601 27 thr pembina,pengawas,staf skrt 1 D
10 1601 28 bingkisan lebaran 1 D
10 1201 34 Deposito bri cab purbalingga 1 D
50 5001 140 Indo Nusa,jl durian klmnh wtn 2 K
10 1509 21 box karton gelas 220 ml 1 D
10 1509 22 box karton botol 330 ml 1 D
10 1509 23 box karton botol 600 ml 1 D
50 5002 40 gj bl juli 14 2 K
50 5002 41 insntp penyusunan rkap 2015 2 K
10 1509 24 galon great b 19 lt 1 D
10 1509 25 embos galon timbul 1 D
50 5001 141 PT Tigris Sekawan,Klaten 2 K
50 5001 142 PT Multi Karadiguna Jasa,Jkt 2 K
40 4101 138 tembok kellng,urugan tnh,gedng 1 D
50 5001 143 CV Wijaya Kusuma Pratama,Pbg 2 K
10 1307 141 Suhono Kjb 1 D
10 1307 142 Anggoro Bayu A 1 D
10 1601 29 insntp keg na 2014 1 D
50 5001 144 PT Trisakti Mustika Graphika 2 K
50 5002 42 seragam hari sabtu/batik 2 K
50 5001 146 cv sari insan,pbg 2 K
50 5001 147 sarana berlian motor,pwt 2 K
40 4101 139 pemb broncap mulang II kr cgk 1 D
50 5001 145 kap Dr.rahardja,msi,cpa,smg 2 K
50 5001 148 els computer,yogyakarta 2 K
50 5001 149 Tiza Solution,Tangerang 2 K
10 1307 143 Slamet Priyono 1 D
10 1307 144 Destara 1 D
10 1307 145 Sofan W 1 D
10 1307 146 Sulistyaningsih 1 D
10 1307 147 Bachtiar Prihono 1 D
50 5002 43 cad abt 2 K
50 5001 150 cv durian jaya,semarang 2 K
50 5001 151 cv krisma metalika,sragen 2 K
10 1409 22 piut kend a/n bahtiar prihono 1 D
50 5001 152 cv muncul sukses makmur,pbg 2 K
10 1409 23 piut kend a/n pramono hariadi 1 D
40 4101 141 tanah kejobong 1 D
50 5001 153 cv kharisma,mangunegara pbg 2 K
50 5001 154 cv karya jiesum,nangka swt,kjb 2 K
50 5001 155 cv karya ff,kelrhn bancar,pbg 2 K
40 4101 142 pemas pp 10"ketuhu-psr mandiri 1 D
40 4101 143 pemb jmbtn pp 10" jl veteran 1 D
40 4101 144 pemas dist ut 8",2.274m dawuhn 1 D
10 1307 148 Aji Purwanto 1 D
10 1307 149 Slamet Riyadi 1 D
40 4101 145 20ubin tnh kaligondang 1 D
40 4101 146 20ubin tnh kajongan 1 D
40 4101 147 dist 8"-444m,pujowiyoto,pbg 1 D
40 4101 148 tersr 2"-1.110m kr klesem 1 D
40 4101 149 dist 3"-600m,jl smu n bbs 1 D
50 5001 156 cv sahabat,bancar rt3/4 pbg 2 K
40 4101 151 dist 8"-484m,sarengat-cahyn br 1 D
40 4101 152 dist 2"-120m,perum bbkn(fauzi) 1 D
40 4101 153 pengaman slrn irigasi pajerukn 1 D
40 4101 154 dist10"-16m,8"-660m,8"-8mreptl 1 D
40 4101 155 trans 8"-930m kr banjar,munjul 1 D
40 4101 156 pemb rmh pelindung ma tuk arus 1 D
40 4101 157 pemb perb broncap ma tuk arus 1 D
40 4101 158 tnh kajongan 79,60 ubin 1 D
40 4101 159 penangkap air kr cegak mulang 1 D
50 5001 157 PT Tri Sigma,Semarang 2 K
10 1307 150 FAJAR H 1 D
10 1307 151 SODIKIN 1 D
10 1307 152 JOKO S 1 D
10 1307 153 AYUDIA K 1 D
10 1307 154 WISNU K 1 D
10 1601 30 pesangon susaryono 1 D
10 1307 155 SAMINGUN 1 D
10 1409 24 Piut a.n Joko Triwinarso 1 D
10 1601 31 sw kantor ikk bbs 1 D
10 1307 156 Januar Restu 1 D
50 5001 158 cv bintang sakti Kr.Moncol 2 K
40 4101 161 rhb gudang ikk rembang 1 D
40 4101 162 perb broncap kr pelus 1 D
10 1409 25 pendapatan rek diterima dimuka 1 D
50 5001 159 cv satrio sakti,pbg 2 K
40 4101 163 jdu 6"-1554m kl kjr-penaruban 1 D
40 4101 164 pemb kantor ikk kaligondang 1 D
40 4101 165 pemb kantor ikk karangreja 1 D
50 5001 161 cv bangun tirta nusantara,sby 2 K
10 1601 32 hnr tim reklas gol.pelanggan 1 D
10 1409 26 Piutang lainnya KIPO 1 D
50 5002 44 Pensiun BPJS 2 K
10 1307 157 Poniman 1 D
50 5001 162 cv jaya utama,bojanegara pbg 2 K
50 5001 163 tk laris manis,purwokerto 2 K
50 5002 45 thr dewas+staf sekre 2 K
50 5002 46 thr 2 dewas+staf sekre 2 K
50 5002 47 pengadaan seragam batik 2 K
50 5001 164 cv rayhan jaya,smg 2 K
10 1409 27 piut a/n muntaqo nurhadi 1 D
50 5002 48 cad iuran perpamsi 2 K
40 4101 166 dist 4",3",2",1,5" onje mrebet 1 D
40 4101 167 jembtn 8" bojong,pbg 1 D
40 4101 168 tembok kllng ma gondang,limbng 1 D
50 5001 165 cv karsa witungga,bbs pbg 2 K
50 5001 166 cv al mughni,kr manyar pbg 2 K
40 4101 169 jdu 8"peremptn mwk-pertign bjg 1 D
40 4101 171 pengemb&rehab dist&tert smngkr 1 D
40 4101 172 dist 6",4" sumingkir-kajongan 1 D
40 4101 173 rehab dist 4" jl banj sari 1 D
40 4101 174 dist 3",2" suply aliran kr lws 1 D
40 4101 175 pemb saluran air hutan kota 1 D
40 4101 176 perb tembok keliling pdam 1 D
40 4101 177 dist 2" kr kemiri(dpn smpn kmk 1 D
40 4101 178 pemb talud dusun 1 rw 10 kts 1 D
40 4101 179 pemb rabat beton dusun 1 kts 1 D
10 1601 33 pesangon parwono 1 D
10 1601 34 penghargaan ms kerja 20 th 1 D
40 4101 181 rhb dist&tert kalitinggar 1 D
40 4101 182 jdu & dist layanan slinga 1/2 1 D
10 1409 28 piut an yudia patriama(dewas) 1 D
10 1409 29 piut an imam purseto(dewas) 1 D
50 5001 167 cv aria bima cena,sby 2 K
40 4101 183 spam dk kr sengon cilapar&teja 1 D
40 4101 184 spam ds penolih,kaligondang 1 D
50 5001 168 cv dwi jaya,p abdi negara pbg 2 K
50 5001 169 toko iguana,bandung 2 K
50 5001 171 toko surabaya,bandung 2 K
88 8801 3 K
88 8801 13 pengampunan pinj depkeu(pmk805 3 K
10 1307 158 widi asmoko 1 D
40 4101 185 perb tmbk kllng gd pipa pdam 1 D
40 4101 186 rhb atap r pompa&genset kmk 1 D
40 4101 187 pemb kantor ikk bbs 1 D
40 4101 188 dist dpn smpn2 kts-bd kr lws 1 D
40 4101 189 dist penolih,kl gondang 1 D
40 4101 191 rhb dist,pelebaran jl bbs 1 D
10 1307 159 Imam Rahmanto 1 D
50 5002 50 insntp penyusunan coc 2 K
50 5002 49 sw ma limbangan 2 K
50 5001 176 cv teknik pompa,smg 2 K
50 5001 174 PT Pancatama Tirta Mukti,Jkt 2 K
50 5002 51 penghrgn ms kerja,agus triono 2 K
50 5001 172 CV Karomah, Penaruban 2 K
50 5001 173 Bp Sukaton Purtomo P,Smg 2 K
50 5001 175 CV Karya Indah Konstruksi,Rmb 2 K
50 5001 177 cv rajawali raya,baturaden bms 2 K
50 5001 178 cv mustika jaya,smg 2 K
50 5001 179 KAP Kumalahadi,Kuncara&rekan 2 K
50 5001 181 cv sarana mulia,smg 2 K
10 1307 160 HERI PURNOMO 1 D
10 1101 32 kas besi ikk kaligondang 1 D
50 5001 182 cv abadi,jl sendangsari smg 2 K
50 5001 183 cv sarana mulia,smg 2 K
50 5001 184 cv mandiri teknik,pbg 2 K
50 5001 185 rumah batik wardi,pbg 2 K
50 5001 186 cv bangun mitra utama,smg 2 K
50 5001 187 cv daya upaya mandiri,smg 2 K
50 5001 188 perc keluarga,pbg 2 K
50 5002 52 hutang gaji bl juni 2017 2 K
50 5001 189 cv anugrah agung lestari,sby 2 K
50 5001 191 kja edi s,hendra & rekan,smg 2 K
50 5002 53 insntp revisi l/k(amnesti hut) 2 K
88 8801 14 tv(hadiah)deposito dr bpr arta 3 K
88 8801 15 tv(hadiah)deposito dr bkk kota 3 K
88 8801 16 hadiah Deposito dr bprs BMP 3 K
50 5001 192 pt sarana sumber tirta,cirebon 2 K
50 5001 193 pt asia prima packaging,tegal 2 K
10 1307 162 sutaryo 1 D
10 1307 163 Buang Suripno 1 D
50 5001 194 cv waskita utama teknik,smg 2 K
50 5001 195 cv gang panca,pbg lor 2 K
40 4101 192 pemb gedung kantor teknik 1 D
50 5006 90 ut pjk pdtp,penghpsn hutdepkeu 2 K
50 5001 196 zona computer,pbg 2 K
40 4101 193 pekerj pindah gd&tmpt parkir 1 D
50 5001 197 cv brilian cipta graha,bojong 2 K
50 5002 54 cad gaji ke 13 2 K
50 5001 198 pt dasaplast nusantara,smg 2 K
10 1307 164 Karsono 1 D
50 5001 199 cv tri karya,sempor lor pbg 2 K
50 5001 201 satria media,pwt 2 K
10 1102 43 BPD TAB BIMA 1 D
10 1102 44 BNI 1 D
10 1102 45 BTN 1 D
88 8801 17 Bonus Tab BPD 3 K
50 5001 202 pt ghaitsa zahira shofa,pbg 2 K
40 4101 194 pemb gd kantor teknik pdam pbg 1 D
50 5001 203 cv cipta arta kreasi,semarang 2 K
50 5001 204 house of donatello,bandung 2 K
10 1409 30 piut a/n ichda m 1 D
10 1409 31 piut a/n sarno 1 D
50 5001 205 cv jaya niaga,pbg 2 K
50 5001 206 cv lahan jaya,pbg 2 K
40 4101 195 pemb kantor ikk mrebet 1 D
50 5001 207 cv karya berkah sejahtera,smg 2 K
50 5001 208 pt mekar jaya agung,purwokerto 2 K
88 8801 18 pendptn pph dtp a/ampunan hut 3 K
10 1409 32 Piutang lainnya Tektaya 1 D
10 1307 165 FITRI 1 D
50 5002 58 pengadaan srgm adat 2 K
50 5002 59 pengadaan srgm putih 2 K
50 5002 60 pesangon Dewas 2 K
50 5002 61 penghargaan ms kerja,suhono 2 K
50 5001 209 cv kencana jaya,cilacap 2 K
50 5001 211 cv wahana artha mulia,smg 2 K
50 5002 62 insntp penyusunan rkap 2018 2 K
50 5002 63 insntp penyusunan l/k th 2017 2 K
50 5001 212 cv dwi asih mandiri,pbg 2 K
50 5002 56 insentip penyusunan sop panda 2 K
50 5002 57 bantuan dharma wanita pdam pbg 2 K
50 5001 213 CV BINTANG SAKTI MEWEK 2 K
50 5002 65 hutang cad tantiem 2 K
50 5002 66 seragam hari selasa/perpamsi 2 K
50 5002 67 seragam batik nasional 2 K
50 5002 68 penghrgn ms kerja,subekhi 2 K
50 5001 160 CV.UTAMA KARYA-DEMAK 2 K
50 5001 170 CV.TRIA CITRAGUNA DESAIN-SMG 2 K
88 8801 21 Pendptan denda cv.sari insan 3 K
88 8801 22 pendptn aktuaria 3 K
50 5001 214 cv tria citraguna desain,smg 2 K
50 5001 215 cv utama karya,demak 2 K
40 4101 196 ded kajian bd kemusuk-bedagas 1 D
40 4101 197 fs transdist bd kemusuk-bedags 1 D
50 5002 64 hutang kesejahtrn a/pendpt dit 2 K
50 5001 216 cv.pilar muda 04 2 K
10 1601 35 kesejahteraan DEWAS 1 D
50 5001 217 Harry's Jaya-Purwokerto 2 K
50 5001 218 cv jaya nugraha,purbalingga 2 K
50 5001 219 cv.jaya nugraha pbg 2 K
88 8801 23 denda ketrlmbtn pt gaitsa z f 3 K
50 5001 221 Griya-Profesional Home Int PBG 2 K
50 5001 222 pt.tirta gesang tunggal jkt 2 K
50 5001 223 cv.nur pbg 2 K
50 5001 224 cv.gendis bojongsari pbg 2 K
50 5001 225 cv.bhakti nugroho mrebet pbg 2 K
50 5001 226 cv.Waskita Cipta Graha-pbg 2 K
50 5002 69 penghrgn ms kerja,lujeng 2 K
50 5002 70 penghrgn ms kerja,endah sri a 2 K
50 5001 227 Sugianto Sleman-Yogyakarta 2 K
50 5001 228 cv kedung agung,pbg 2 K
40 4101 198 pemb.gudang pipa PDAM PBG 1 D
10 1601 36 pengadaan sepatu karyawan 1 D
10 1601 37 pengadaan seragam PERPAMSI 1 D
10 1601 38 pengadaan training 1 D
10 1601 39 penghargaan ms kerja 10 tahun 1 D
10 1601 40 penghargaan ms kerja 30 tahun 1 D
50 5001 229 CV.Sapto Argo Purbalingga 2 K
50 5001 230 CV Berkah Gemilang-Semarang 2 K
50 5001 231 CV.Esella Wangi-Banyumas 2 K
50 5001 232 CV Yunawan Purbalingga 2 K
50 5002 55 Tunjangan Beras/Sembako 2 K
10 1601 41 Pesangon Suroyo 1 D
10 1601 42 Pesangon Lukman Haryono 1 D
10 1601 43 Pesangon Heri Nurcahyo 1 D
10 1601 44 Pesangon Sunano 1 D
50 5002 71 Penyusunan SOP 2019 2 K
50 5002 72 Pesangon Lukman Haryono,SE 2 K
50 5002 73 Pesangon Heri Nurcahyono 2 K
50 5002 74 Pesangon Sunano 2 K
50 5002 75 Pesangon Utami Budhianti,SH 2 K
50 5002 76 Program Purna Direksi 2 K
50 5002 77 Program Purna Karyawan 2 K
50 5002 78 Pesangon Hartati,S.Pd 2 K
50 5002 79 Pesangon Ratmono 2 K
50 5002 80 Pesangon Yuliati 2 K
50 5002 81 Pesangon Widodo Panca Putra 2 K
50 5002 82 Pesangon Makmum 2 K
50 5002 83 Pesangon Sulistyaningsih 2 K
50 5002 84 Pesangon Sri Murni 2 K
50 5002 85 Pesangon Nuryono 2 K
50 5002 86 Pesangon Wahyudi,SH 2 K
50 5002 87 Pesangon Hadi 2 K
50 5002 88 Pesangon Edi Harjono 2 K
50 5002 89 Pesangon Sugihardjo,SE 2 K
50 5002 90 Pesangon Sugianto 2 K
50 5002 91 Pesangon Lego Purbowo 2 K
50 5002 92 Pesangon Hayati Mulyaningsih 2 K
40 4101 199 Pngdan tanah Gombang Kjongan 1 D
40 4101 200 Pintu gerbang jembatan baru 1 D
50 5001 233 Narendra CV Bojongsari Pbg 2 K
62 6203 81 Tantiem Dir+Dewas+Bonus Kary. 2 K
40 4101 201 pengadaan tnh Ds.Sumampir Rbg 1 D
10 1601 45 gaji ke-13 1 D
10 1601 46 penghrgn ms krj 30 thn Sunano 1 D
10 1601 47 penghrgn ms krj 30 thn Teguh P 1 D
10 1601 48 penghrgn ms krj 20 thn Saryono 1 D
10 1601 49 penghrgn ms krj 10 thn Eko M. 1 D
10 1601 50 penghrgn ms krj 10 thn Sechan 1 D
10 1601 51 penghrgn ms krj 10 thn S.Agil 1 D
50 5001 234 pt.meta global s. bekasi sltn 2 K
40 4101 202 pembanguna gudang pipa pdam 1 D
50 5001 235 pt.afc berjaya Indonesia 2 K
50 5001 236 cv.ragam persada teknik 2 K
50 5001 237 cv.focus Bojongsari-Pbg 2 K
50 5001 238 cv hani jaya, purworejo 2 K
96 9603 72 By program SR MBR 5 D
50 5001 239 tata saka consultan cv 2 K
50 5002 93 Byr pajak thn.2015 2 K
50 5002 94 instp penyusn pp thn 2020 2 K
50 5002 95 Pesangon Saryono 2 K
50 5002 96 Pesangon Elvi Restu Hartini 2 K
50 5002 97 Pesangon Pudjiarto 2 K
50 5002 98 Pesangon Sugeng,ST 2 K
50 5002 100 Pesangon Yudi Haryanto 2 K
50 5002 101 Pesangon Widi Haryani 2 K
50 5002 102 Pesangon Untung Widadi 2 K
10 1409 33 piutang BMT Bukateja 1 D
10 1307 166 Agung Cahyadi 1 D
10 1601 52 thr dir+karyawan (Mei'21) 1 D
10 1601 53 thr dwn pmbn+pngws+stf(mei21) 1 D
50 5001 240 cv.Kencana Jaya-Cilacap 2 K
50 5001 241 cv.Arjuna Jaya-Kutasari Pbg 2 K
10 1601 54 Pesangon Utami Budhianti,SH 1 D
10 1601 55 Pesangon Yuni Astuti 1 D
62 6203 82 Corpt.Social Responsblty (CSR) 2 K
10 1201 35 Deposito BNI Purbalingga 1 D
10 1601 56 Pesangon Angkat Prasetyo 1 D
10 1601 57 Pesangon Hartati 1 D
50 5001 242 cv.gajah sakti Kaligondang pbg 2 K
40 4101 203 tanah ds Kajongan dkt MA Gomb. 1 D
50 5002 103 Insntif Bravet Pajak 2 K
50 5001 243 cv.sumber rejeki, Kutasari-Pbg 2 K
50 5002 104 Diklat RPAM 2 K
50 5002 105 Insentif audit Inspektorat 2 K
50 5002 106 Insentif audit Kinerja 2 K
50 5002 107 Pesangon Diana W.,S.Pd 2 K
10 1601 58 thr 1 dir+kary 1 D
10 1601 59 thr 1 dewas+staf sekre 1 D
10 1601 60 THR 2 Dir+Karyawan 1 D
10 1601 61 THR 2 Dewas+Staf Sekre 1 D
50 5001 244 CV.Archindo Media Karya-Pgdgn 2 K
50 5001 245 CV.Maju Mulia Boja-Kendal 2 K
50 5001 246 CV.Tunas Muda Wirasana-Pbg 2 K
10 1601 62 THR Dwn Pembina I&II 1 D
10 1101 33 Kas kecil Cab.Usman Djanatin 1 D
10 1101 34 Kas kecil Cab.Jend.Soedirman 1 D
10 1601 63 Tanggungan forga Magelang 1 D
50 5001 247 cv alzena-Bojongsari Pbg 2 K
50 5002 108 Peringatan HUT Kemerdekaan RI 2 K
10 1601 64 Sw kntor Cab.Jend.Sud.(2Thn) 1 D
10 1307 167 Kurnia Niken 1 D
10 1307 168 Afif Riyadi 1 D
10 1601 65 Kontrbsi Pemnfatan Sumber MA 1 D
50 5001 248 PT.Navigator Strategi Ind. 2 K
50 5002 109 Seleksi Karyawan Baru 2 K
50 5002 110 Perjanjian dgn Geologi-Bandung 2 K
50 5001 249 CV.Artha Wijaya-Kalimanah Pbg 2 K
10 1601 66 Jasa Konsultasi Kepuasan Plg 1 D
50 5001 250 CV.Dhipa Yudha Cakrawala-PWT 2 K
10 1409 34 Piutang Ilham Muchalim 1 D
50 5001 251 CV.Tunas Jaya-Purbalingga 2 K
50 5002 111 Pesangon Padi Sultoni 2 K
50 5002 112 Pesangon Haryanto 2 K
10 1601 67 JK November 2022 1 D
10 1601 68 Sewa kios AMDK (12 bln) 1 D
50 5001 252 KAP Tarmizi Achmad Semarang 2 K
10 1601 69 Kegiatan Pengeboran Air Tanah 1 D
50 5002 113 Jasa Pengabdian Dewas+Staf 2 K
10 1601 70 Pengadaan galon AMDK 1 D
10 1101 35 Kas kecil cabang Ardi Lawet 1 D
10 1409 35 Piutang cv.nur pbg 1 D
10 1409 36 Piutang cv.alzena-Bjs Pbg 1 D
10 1409 37 Amortisasi Piutang AMDK 1 D
50 5002 114 Dana titipan KIPO 2 K
10 1307 169 Soleh Alifah 1 D
10 1601 71 Kajian struktur tarif 1 D
10 1601 72 Tambahan penghasilan Feb'23 1 D
10 1601 73 Iuran Dapenma PAMSI 1 D
50 5002 115 Pesangon DIRUM 2 K
10 1601 74 TambahanPenghasilanMaret23 1 D
10 1601 75 TambahanPenghasilanApril23 1 D
50 5002 116 Seragam Olahraga 2 K
40 4101 204 Pemb.ruang adm.gudang 1 D
40 4101 205 Tanah U/Resr.KjnganSPAMBandara 1 D
50 5002 117 Pesangon Turman 2 K
10 1307 170 Eka Setiawan 1 D
50 5002 118 Tunjangan Pendidikan 2 K
10 1601 76 Halal BiHalal&Silaturahim 2023 1 D
40 4101 206 Tempat parkir dpn Mushola 1 D
10 1601 77 Sewa tanah Klp.Sawit (3 thn) 1 D
50 5001 253 CV.Putra Satria Tama-Semarang 2 K
40 4101 207 Tanah resrvoir ds.Munjul 1 D
40 4101 208 Kantong air-Klapa Sawit 1 D
40 4101 209 Rak accesories gudang 2023 1 D
10 1601 78 Termin I-AKATIRTA Magelang 1 D
40 4101 210 Pembuatan_sumurDalam 1 D
10 1307 171 Priyono 1 D
50 5001 254 CV.Nur Pratama-Gandasuli Pbg 2 K
50 5001 255 PT.Suria Multipak Indonesia 2 K
50 5001 256 PT.Golden Flexible Packaging 2 K
50 5001 257 CV.Prima Kinarya-Purbalingga 2 K
40 4101 211 Pindah jalur MA Tlagayasa 1 D
10 1601 79 Insntif pngihan Nov24-Jan25 1 D
40 4101 212 Pembuatan ruang arsip 1 D
10 1601 80 Sdtn,Lkbn beningCup220+Dus 1 D
10 1601 81 Lid Cup 4 Line Braling 1 D
10 1307 172 Setiawan 1 D
10 1601 82 Sewa tanah Ds.Sempor Lor 3thn 1 D
40 4101 213 Pembangunan terminal air 1 D
10 1601 83 Kontribusi u/desa sumber 2022 1 D
40 4101 214 Tmpt parkir kantor Bojongsari 1 D
50 5002 119 Penghargaan ms kerja Rasito 2 K
10 1601 84 Asrnsi Bang.kantor pst-EQ 1 D
10 1601 85 Asrnsi Bang.kantor pst-AlRisk 1 D
50 5001 258 CV.Rian Jaya-Bojongsari PBG 2 K
50 5001 259 CV.Garuda Jaya-Rembang Pbg 2 K
50 5001 260 Dr.Ir,Tri Joko,M.Si 2 K
10 1601 86 Termin II-AKATIRTA Magelang 1 D
10 1601 87 Sw kntr IKK Rembang (1thn) 1 D
10 1307 173 Slamet Sumber 1 D
40 4101 215 Pengecoran jln dpn kantor UJ. 1 D
50 5002 120 Dana titipan PERPAMSI 2 K
96 9602 41 ASTINET/Telkom Solution 5 D
96 9602 71 Rekening Air 5 D
96 9608 11 Iuran DPP Perpamsi 5 D
96 9608 12 Iuran DPD Perpamsi 5 D
10 1307 174 Nugro Sartono 1 D
10 1601 88 Insntif by.adm share loket 1 D
10 1601 89 SPPD pengurusan SIPPA 1 D
50 5001 261 CV.Andong Sejahtera-Mrebet PBG 2 K
50 5001 262 CV.Empat Lima-Wirasana PBG 2 K
10 1601 90 Sewa tanah kas ds.Panican KMK 1 D
40 4101 216 Tanah Ds.Kajongan u/broncap 1 D
10 1601 91 Termin III AKATIRTA-Magelang 1 D
50 5001 263 CV.Permata Wirasana-PBG 2 K
10 1601 92 Instf.Penysn LK thn buku 2023 1 D
10 1601 93 Instf.pnghn bln.Jul-Sept2023 1 D
10 1601 94 Insf.pnghn bln.Nov-Des23&Jan24 1 D
50 5001 264 CV.Bintang Raditya-Babakan Klm 2 K
50 5002 121 Instf Penghan Jul-Sept 2023 2 K
50 5002 122 Instf Penghan Nov-Des23-Jan24 2 K
10 1601 95 Instf.Penys.RKAP 2024 1 D
50 5001 265 PT.Cipta Arta Kreasi-Semarang 2 K
40 4101 217 JDU SPAM Bandara 1 D
10 1601 96 Pph THR 2024 Dir&Karyawan 1 D
96 9606 31 By Dampak Banjir April 2024 5 D
40 4101 218 Kanopi IKK Kemangkon 1 D
40 4101 219 Pembuatan taman kantor 1 D
80 8102 61 Pndptan Survey Ketrsediaan Air 3 K
50 5001 266 CV.Bilal Bintng K.Kr.Anyar-PBG 2 K
40 4101 220 Pembuatan dudukan torent 1 D
50 5002 123 Studi Banding Keuangan 2 K
40 4101 221 Bak pengaman limpsn M.Lmpk Dau 1 D
10 1101 36 Kas Besi Usman Dj.Loket Mrebet 1 D
40 4101 222 Pengadaan Tanah Kajongan II 1 D
10 1601 97 Insentif SIPPA 1 D
40 4101 223 Pasang pipa JDU Ds.Cipaku 1 D
80 8102 49 Pndptan Denda Pencurian Air 3 K
10 1101 37 Kas Besi Lkt Kr.Reja 1 D
50 5001 267 PT.Limas Adya Parama-Bekasi 2 K
50 5002 124 Tamsil November 2024 2 K
40 4101 224 Pembuatan sound sistem kantor 1 D
50 5002 125 Tamsil Desember 2024 2 K
40 4101 225 JDU SPAM Kalibodas 1 D
10 1601 98 Penyusunan Dokumen SOP 1 D
98 9801 10 Biaya Lain-Lain (Sumbangan) 6 D
98 9801 20 Biaya Admin Bank 6 D
40 4101 226 Pembtn.LntaiGudang_Peralatan 1 D
96 9608 71 Biaya Pajak Bumi Bangunan 5 D
96 9608 72 Biaya Pajak Kendaraan Bermotor 5 D
40 4101 227 Pembuatan dapur PDAM 1 D
40 4101 228 Pemb.Sertifikat Halal AMDK 1 D
40 4101 229 Pmbuatan Gazebo di MA Sikopyah 1 D
10 1102 46 Bank Syariah Indonesia PBG 1 D
10 1601 99 Sewa tanah Kr.Pinggir (5 thn) 1 D
10 1409 38 Piutang Rendy-Kr.Reja 1 D
50 5001 268 Rekatama Digdaya Solution 2 K
10 1601 100 Pengembangan Database GIS 1 D
96 9608 13 Iuran Forum BUMD 5 D
40 4101 230 Penyusunan Dokumen SOP 1 D
96 9602 31 Prog.Geograph.Inf.System/GIS 5 D
10 1409 39 Piutang Elin Elina 1 D
40 4101 231 Pemb.jln setapak MA Pajerukan 1 D
40 4101 232 Penysnan Business Plan 25-29 1 D
10 1307 175 Eko Rudi Priyanto 1 D
80 8102 50 Pendpatan REKOMTEK 3 K
88 8801 24 Pendptan Pngblian Denda Pajak 3 K
10 1101 38 Kas Besi Cab.Usman Djanatin 1 D
10 1307 176 Eko Sutikno 1 D
10 1307 177 Hari Siam 1 D
50 5001 269 CV.Rasmita Niti Karya-Kmk Pbg 2 K
10 1601 101 Sw tnh jl.prov Pbg-Bbs 1 D
10 1601 102 Sw tnh jl.prov Lingkar Brt Pbg 1 D
10 1601 103 Sw tnh Jl.prov Pbg-Bbs-Belik 1 D
10 1409 40 Piutang Adi Yuwono 1 D
40 4101 233 Perlengkapan Car Free Day 1 D
10 1307 178 Erdiono 1 D
10 1601 104 Kontribusi desa 2025 1 D
50 5002 126 Kontribusi Desa 2 K
50 5002 127 Pengadaan mantel hujan 2 K
40 4101 234 Renovasi ruang pelayanan 1 D
50 5002 128 Forga tahun 2025 2 K
40 4101 235 Pindah jalur MA Gondang Limb. 1 D
10 1409 41 Piutang Agus Al Fatah Kr.Reja 1 D
10 1307 179 Munginsidi 1 D
50 5001 270 CV.Cipta Graha Estetika-PWT 2 K
