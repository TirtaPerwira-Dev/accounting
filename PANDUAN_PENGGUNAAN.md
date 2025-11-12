# PANDUAN PENGGUNAAN SISTEM AKUNTANSI PDAM

**Sistem Akuntansi Air Minum Berbasis SAKEP**

---

## 🏢 **TAHAP 1: SETUP AWAL PERUSAHAAN**

### 1.1 Login & Akses Sistem

1. Akses aplikasi melalui browser
2. Login dengan akun administrator
3. Dashboard utama akan menampilkan ringkasan keuangan

### 1.2 Setup Perusahaan

1. **Menu: Companies** → Buat profil PDAM

    - Nama: `PDAM Tirta Jaya`
    - NPWP: `12.345.678.9-012.000`
    - Alamat lengkap
    - Telepon/email
    - Standar Akuntansi: **SAKEP**

2. **Konfigurasi Pajak**
    - PPN Rate: `11%`
    - Status PKP: `Ya/Tidak`

---

## 📊 **TAHAP 2: SETUP CHART OF ACCOUNTS (COA)**

### 2.1 Verifikasi COA Template

**Menu: Chart of Accounts** → Review akun yang sudah ada:

#### ASET (1-xxxxx)

-   `1-10001` - Kas di Tangan
-   `1-10002` - Kas di Bank BRI
-   `1-11001` - Piutang Usaha Air Minum
-   `1-12000` - Persediaan Bahan Kimia
-   `1-20001` - Mesin Pompa Air
-   `1-20002` - Pipa Distribusi

#### KEWAJIBAN (2-xxxxx)

-   `2-10001` - Utang Usaha
-   `2-10002` - Utang Bank
-   `2-20000` - PPN Keluaran

#### EKUITAS (3-xxxxx)

-   `3-10000` - Modal Disetor
-   `3-10001` - Laba Ditahan

#### PENDAPATAN (4-xxxxx)

-   `4-10001` - Pendapatan Air RT A1
-   `4-10002` - Pendapatan Air RT A2
-   `4-10003` - Pendapatan Sambungan Baru

#### BEBAN (5-xxxxx)

-   `5-10001` - Beban Bahan Kimia
-   `5-10002` - Beban Listrik
-   `5-10003` - Beban Gaji Karyawan
-   `5-10004` - Beban Pemeliharaan Pipa

### 2.2 Input Saldo Awal

1. Buka setiap akun → Edit
2. Masukkan **Opening Balance**:
    - Kas: Rp 50.000.000
    - Bank: Rp 200.000.000
    - Piutang: Rp 100.000.000
    - Aset Tetap: Rp 2.000.000.000
    - dll sesuai kondisi aktual

---

## 💼 **TAHAP 3: PENCATATAN TRANSAKSI HARIAN**

### 3.1 Penjualan Air (Pendapatan)

**Menu: Journal Entries** → Create New

**Contoh Jurnal:**

```
Tanggal: 2024-11-06
Deskripsi: Penjualan air bulan November 2024

Debit:  1-11001 Piutang Usaha        Rp 150.000.000
Credit: 4-10001 Pendapatan Air RT A1  Rp 135.135.135
Credit: 2-20000 PPN Keluaran          Rp  14.864.865
```

### 3.2 Penerimaan Kas dari Pelanggan

```
Debit:  1-10002 Kas di Bank          Rp 100.000.000
Credit: 1-11001 Piutang Usaha        Rp 100.000.000
```

### 3.3 Pembelian Bahan Kimia

```
Debit:  5-10001 Beban Bahan Kimia    Rp  20.000.000
Credit: 1-10002 Kas di Bank          Rp  20.000.000
```

### 3.4 Pembayaran Gaji Karyawan

```
Debit:  5-10003 Beban Gaji Karyawan  Rp  50.000.000
Credit: 1-10002 Kas di Bank          Rp  50.000.000
```

### 3.5 Posting Jurnal

1. **Pastikan Balance**: Total Debit = Total Credit
2. **Status: Draft** → Klik **Post** untuk mem-posting
3. Jurnal yang sudah di-post **tidak bisa diedit**

---

## 📈 **TAHAP 4: REKONSILIASI & PENYESUAIAN**

### 4.1 Rekonsiliasi Bank Bulanan

1. **Menu: Bank Reconciliation** (jika ada)
2. Bandingkan mutasi bank vs jurnal
3. Input transaksi yang belum tercatat

### 4.2 Jurnal Penyesuaian Akhir Bulan

**Contoh:**

```
1. Beban Listrik yang masih harus dibayar:
Debit:  5-10002 Beban Listrik        Rp   5.000.000
Credit: 2-10001 Utang Usaha          Rp   5.000.000

2. Depresiasi Aset Tetap:
Debit:  5-10005 Beban Depresiasi     Rp  10.000.000
Credit: 1-20100 Akum. Depresiasi     Rp  10.000.000
```

---

## 📊 **TAHAP 5: LAPORAN KEUANGAN**

### 5.1 Akses Financial Reports

**Menu: Financial Reports**

### 5.2 Generate Laporan

1. **Pilih Perusahaan**: PDAM Tirta Jaya
2. **Pilih Jenis Laporan**:

    - Trial Balance
    - Neraca (Balance Sheet)
    - Laba Rugi (Income Statement)
    - Arus Kas (Cash Flow)
    - Buku Besar (General Ledger)

3. **Set Periode**:

    - Trial Balance: Per tanggal (31 Nov 2024)
    - Laba Rugi: Periode (1 Nov - 30 Nov 2024)
    - Arus Kas: Periode (1 Nov - 30 Nov 2024)

4. **Klik Generate Report**

### 5.3 Review & Export

1. **Verifikasi angka** di setiap laporan
2. **Export ke PDF/Excel** untuk submission
3. **Simpan backup** di storage

---

## 🎯 **TAHAP 6: MONITORING & ANALISIS**

### 6.1 Dashboard Monitoring

-   **Total Aset**: Pantau pertumbuhan aset
-   **Piutang**: Monitor tunggakan pelanggan
-   **Pendapatan Bulanan**: Track revenue trends
-   **Ratio Keuangan**: Likuiditas, profitabilitas

### 6.2 Analisis Laporan

-   **Neraca**: Pastikan Assets = Liabilities + Equity
-   **Laba Rugi**: Analisis margin dan efisiensi
-   **Arus Kas**: Monitor cash flow operations

---

## 🔄 **TAHAP 7: CLOSING PERIODE**

### 7.1 Tutup Buku Bulanan

1. **Pastikan semua transaksi** sudah di-record
2. **Post semua jurnal** penyesuaian
3. **Generate final reports**
4. **Backup database**

### 7.2 Tutup Buku Tahunan

1. **Jurnal penutup** pendapatan/beban ke laba ditahan
2. **Audit trail** untuk semua transaksi
3. **Archive** data tahun sebelumnya

---

## ⚠️ **TIPS & BEST PRACTICES**

1. **Backup Harian**: Database + storage files
2. **Audit Trail**: Semua perubahan tercatat
3. **Authorization**: Control user access per role
4. **Validation**: Selalu cek balanced journal
5. **Documentation**: Simpan supporting documents
6. **Regular Review**: Monthly financial analysis

---

**Sistem ini mengikuti standar SAKEP dan regulasi PDAM Indonesia**
**Version**: 1.0 | **Last Update**: Nov 2024
