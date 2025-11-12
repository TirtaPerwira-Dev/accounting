# 📖 PANDUAN LENGKAP SISTEM AKUNTANSI PDAM

**Untuk Pemula yang Awam Akuntansi**

---

## 🎯 **OVERVIEW SISTEM**

Sistem ini membantu PDAM mengelola keuangan dengan 3 langkah utama:

1. **Setup Awal** → Input kondisi keuangan saat mulai pakai sistem
2. **Transaksi Harian** → Catat aktivitas keuangan sehari-hari
3. **Laporan Otomatis** → Sistem buat laporan keuangan sendiri

---

## 🚀 **ALUR LENGKAP PENGGUNAAN**

### **📋 PERSIAPAN (Diabaikan untuk panduan ini)**

-   ✅ Profil Perusahaan (sudah diatur saat instalasi)
-   ✅ Pengguna & Hak Akses (sudah diatur saat instalasi)

---

## **🔧 LANGKAH 1: SETUP BAGAN AKUN**

### **📍 Menu: "2. Master Data" → "Bagan Akun"**

**Apa yang dilakukan:**

-   Melihat daftar semua akun keuangan yang sudah disiapkan
-   Memastikan semua akun yang dibutuhkan sudah ada
-   **JANGAN** input saldo di sini!

**Langkah detail:**

1. Buka menu **"2. Master Data"** di sidebar kiri
2. Klik **"Bagan Akun"**
3. Anda akan melihat daftar akun seperti:

    ```
    💰 ASET
    ├── 1-10001 - Kas di Kasir
    ├── 1-10002 - Bank BRI
    ├── 1-11001 - Piutang Pelanggan

    💳 HUTANG
    ├── 2-10001 - Utang Supplier
    ├── 2-10002 - Utang Gaji

    🏛️ MODAL
    ├── 3-10001 - Modal Awal PDAM
    ```

4. **Cek kolom "Info Saldo"**:
    - ✅ **Hijau** = Sudah ada saldo awal
    - ⚠️ **Kuning** = Belum ada saldo awal

**Catatan Penting:**

-   **JANGAN** edit atau tambah saldo di halaman ini
-   Jika ada akun yang kurang, bisa tambah dengan tombol **"+ Buat Akun"**
-   Fokus pastikan semua akun yang dibutuhkan sudah ada

---

## **💰 LANGKAH 2: INPUT SALDO AWAL**

### **📍 Menu: "1. Setup Sistem" → "Saldo Awal"**

**Apa yang dilakukan:**

-   Input kondisi keuangan PDAM saat pertama kali pakai sistem
-   Seperti "foto" keuangan di tanggal tertentu (biasanya 1 Januari)

**Langkah detail:**

### **2.1 Buka Menu Saldo Awal**

1. Klik **"1. Setup Sistem"** di sidebar
2. Klik **"Saldo Awal"**
3. Klik tombol **"+ Buat Saldo Awal"**

### **2.2 Input Data Saldo**

**Contoh 1: Kas di Bank**

1. **Tanggal Saldo Awal**: Pilih 1 Januari 2025 (atau tanggal mulai pakai sistem)
2. **Pilih Akun**: "💰 ASET | 1-10002 - Bank BRI"
3. **Jenis Saldo**: Otomatis terpilih "📈 DEBIT" (karena kas = aset)
4. **Jumlah Saldo**: Masukkan 50000000 (Rp 50,000,000)
5. **Keterangan**: "Saldo kas di Bank BRI per 1 Januari 2025"
6. Klik **"Simpan"**

**Contoh 2: Piutang Pelanggan**

1. **Tanggal Saldo Awal**: 1 Januari 2025
2. **Pilih Akun**: "💰 ASET | 1-11001 - Piutang Pelanggan"
3. **Jenis Saldo**: "📈 DEBIT" (karena piutang = aset)
4. **Jumlah Saldo**: 25000000 (Rp 25,000,000)
5. **Keterangan**: "Tagihan pelanggan yang belum dibayar per 31 Des 2024"
6. Klik **"Simpan"**

**Contoh 3: Utang Supplier**

1. **Tanggal Saldo Awal**: 1 Januari 2025
2. **Pilih Akun**: "💳 HUTANG | 2-10001 - Utang Supplier"
3. **Jenis Saldo**: Otomatis terpilih "📉 KREDIT" (karena utang = kewajiban)
4. **Jumlah Saldo**: 15000000 (Rp 15,000,000)
5. **Keterangan**: "Hutang ke supplier bahan kimia per 31 Des 2024"
6. Klik **"Simpan"**

**Contoh 4: Modal Awal**

1. **Tanggal Saldo Awal**: 1 Januari 2025
2. **Pilih Akun**: "🏛️ MODAL | 3-10001 - Modal Awal PDAM"
3. **Jenis Saldo**: "📉 KREDIT" (karena modal = ekuitas)
4. **Jumlah Saldo**: 160000000 (Rp 160,000,000)
5. **Keterangan**: "Modal awal pendirian PDAM"
6. Klik **"Simpan"**

### **2.3 Pastikan Balanced**

**PENTING**: Total DEBIT harus sama dengan Total KREDIT!

```
DEBIT:                    KREDIT:
Bank BRI: 50,000,000     Utang Supplier: 15,000,000
Piutang: 25,000,000      Modal Awal: 160,000,000
Peralatan: 100,000,000
----------------         ----------------
Total: 175,000,000       Total: 175,000,000 ✓
```

### **2.4 Konfirmasi Saldo Awal**

1. Setelah semua saldo diinput, klik tombol **"Konfirmasi"** di setiap baris
2. Status akan berubah dari ⏳ **Pending** menjadi ✅ **Confirmed**
3. Saldo yang sudah dikonfirmasi tidak bisa diubah lagi

---

## **📝 LANGKAH 3: INPUT TRANSAKSI HARIAN**

### **📍 Menu: "3. Transaksi Harian" → "Jurnal Umum"**

**Apa yang dilakukan:**

-   Catat semua aktivitas keuangan sehari-hari
-   Setiap transaksi harus **balanced** (Total Debit = Total Kredit)

**Langkah detail:**

### **3.1 Buka Menu Jurnal**

1. Klik **"3. Transaksi Harian"** di sidebar
2. Klik **"Jurnal Umum"**
3. Klik tombol **"+ Buat Jurnal"**

### **3.2 Contoh Transaksi 1: Pelanggan Bayar Tagihan**

**Input Header:**

1. **Tanggal Transaksi**: Pilih tanggal hari ini
2. **Keterangan**: "Pelanggan bayar tagihan air bulan November"

**Input Detail:**

1. **Baris 1 (DEBIT)**:

    - **Akun**: "1-10002 - Bank BRI"
    - **Debit**: 1500000 (Rp 1,500,000)
    - **Kredit**: 0
    - **Keterangan**: "Pembayaran dari pelanggan"

2. **Baris 2 (KREDIT)**:

    - **Akun**: "1-11001 - Piutang Pelanggan"
    - **Debit**: 0
    - **Kredit**: 1500000 (Rp 1,500,000)
    - **Keterangan**: "Pengurangan piutang pelanggan"

3. **Cek Balance**: Pastikan tampil ✅ **Seimbang**
4. Klik **"Simpan"**

### **3.3 Contoh Transaksi 2: Bayar Gaji Karyawan**

**Input Header:**

1. **Tanggal Transaksi**: Pilih tanggal pembayaran
2. **Keterangan**: "Pembayaran gaji karyawan bulan November"

**Input Detail:**

1. **Baris 1 (DEBIT)**:

    - **Akun**: "5-10001 - Beban Gaji Karyawan"
    - **Debit**: 5000000 (Rp 5,000,000)
    - **Kredit**: 0
    - **Keterangan**: "Gaji karyawan November"

2. **Baris 2 (KREDIT)**:

    - **Akun**: "1-10002 - Bank BRI"
    - **Debit**: 0
    - **Kredit**: 5000000 (Rp 5,000,000)
    - **Keterangan**: "Transfer gaji ke rekening karyawan"

3. **Cek Balance**: Pastikan tampil ✅ **Seimbang**
4. Klik **"Simpan"**

### **3.4 Post Jurnal**

1. Setelah jurnal disimpan, status akan **Draft**
2. Klik tombol **"Post"** untuk memposting jurnal
3. Jurnal yang sudah di-post tidak bisa diedit lagi
4. Hanya jurnal yang di-post yang mempengaruhi laporan keuangan

---

## **📊 LANGKAH 4: LIHAT LAPORAN KEUANGAN**

### **📍 Menu: "4. Laporan Keuangan"**

**Apa yang dilakukan:**

-   Sistem otomatis menghitung laporan dari saldo awal + transaksi
-   Tidak perlu input manual

**Laporan yang tersedia:**

### **4.1 Neraca (Balance Sheet)**

-   Menampilkan posisi keuangan (Aset, Hutang, Modal)
-   Formula: **Aset = Hutang + Modal**

### **4.2 Laba Rugi (Income Statement)**

-   Menampilkan pendapatan vs beban
-   Formula: **Laba = Pendapatan - Beban**

### **4.3 Arus Kas (Cash Flow)**

-   Menampilkan keluar masuk uang
-   Berguna untuk monitor likuiditas

---

## **🔄 SIKLUS HARIAN**

Setelah setup awal selesai, rutinitas harian:

### **Pagi Hari:**

1. Buka **"Jurnal Umum"**
2. Input semua transaksi kemarin yang belum dicatat

### **Transaksi Umum PDAM:**

-   💧 **Pelanggan bayar tagihan** → Debit: Bank, Kredit: Piutang
-   🧪 **Beli bahan kimia** → Debit: Persediaan, Kredit: Utang Supplier
-   ⚡ **Bayar listrik** → Debit: Beban Listrik, Kredit: Bank
-   💰 **Bayar gaji** → Debit: Beban Gaji, Kredit: Bank
-   🔧 **Beli peralatan** → Debit: Aset Tetap, Kredit: Bank

### **Akhir Hari:**

1. **Post** semua jurnal yang sudah dicek
2. Lihat **Dashboard** untuk monitoring cepat

### **Akhir Bulan:**

1. Buat **Laporan Keuangan** lengkap
2. Export ke PDF untuk arsip
3. Submit ke atasan/BPK/DJP sesuai kebutuhan

---

## **💡 TIPS UNTUK PEMULA**

### **🔍 Cara Mudah Ingat Debit vs Kredit:**

**DEBIT = DAPAT (Yang kita dapat/punya)**

-   Kas bertambah → DEBIT
-   Piutang bertambah → DEBIT
-   Aset bertambah → DEBIT
-   Beban/pengeluaran → DEBIT

**KREDIT = KELUAR (Yang keluar/hutang)**

-   Kas berkurang → KREDIT
-   Utang bertambah → KREDIT
-   Pendapatan masuk → KREDIT
-   Modal bertambah → KREDIT

### **⚠️ Kesalahan yang Sering Terjadi:**

1. **Input saldo di Chart of Accounts** → ❌ Salah tempat
2. **Jurnal tidak balanced** → ❌ Total Debit ≠ Total Kredit
3. **Lupa post jurnal** → ❌ Jurnal draft tidak masuk laporan
4. **Input langsung di laporan** → ❌ Laporan otomatis dari jurnal

### **✅ Checklist Harian:**

-   [ ] Semua transaksi sudah dijurnal?
-   [ ] Semua jurnal sudah balanced?
-   [ ] Semua jurnal sudah di-post?
-   [ ] Saldo kas sesuai dengan kenyataan?

---

## **🆘 TROUBLESHOOTING**

### **Problem: Tombol "Post" tidak muncul**

**Solusi**: Pastikan jurnal balanced (Total Debit = Total Kredit)

### **Problem: Saldo tidak sesuai kenyataan**

**Solusi**:

1. Cek semua transaksi sudah dijurnal
2. Cek jurnal sudah di-post
3. Cek saldo awal sudah benar

### **Problem: Laporan kosong**

**Solusi**:

1. Pastikan saldo awal sudah dikonfirmasi
2. Pastikan jurnal sudah di-post (bukan draft)

### **Problem: Bingung akun mana yang dipilih**

**Solusi**: Gunakan template yang sudah disediakan di form jurnal

---

## **📞 SUPPORT**

Jika masih bingung:

1. Lihat **Tips untuk Pemula** di setiap form
2. Gunakan **Template Jurnal** yang sudah disediakan
3. Hubungi tim IT untuk bantuan teknis

---

**🎉 Selamat! Anda sudah siap menggunakan sistem akuntansi PDAM!**

_File ini bisa di-print atau disimpan sebagai referensi harian._
