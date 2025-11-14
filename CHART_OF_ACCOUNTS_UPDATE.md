# Chart of Accounts Structure Update

## 📊 Struktur Bagan Akun SAKEP

Sistem telah diperbarui untuk menggunakan struktur bagan akun sesuai standar SAKEP dengan hierarki:

### Hierarki Akun:
1. **Kelompok** (NO_KEL) - Kategori utama
2. **Rekening** (NO_REK) - Sub-kategori dalam kelompok
3. **Nomor Bantu** (NO_BANTU) - Detail akun spesifik

### Contoh Struktur:
```
10 - Aktiva Lancar
  ├── 1101 - Kas
  │   ├── 10 - Kas Besar
  │   ├── 11 - Kas Kecil Pusat
  │   └── 12 - Kas Kecil IKK Bobotsari
  └── 1102 - Bank
      ├── 20 - Bank BPD Capem Pasar Kota
      ├── 30 - BMT Mrebet
      └── 21 - Bank BPD Capem Bobotsari
```

## 🔧 Perubahan Database

### Model yang Diperbarui:
- `Kelompok.php` - Model untuk kelompok akun utama
- `Rekening.php` - Model untuk sub-kategori rekening
- `NomorBantu.php` - Model untuk detail akun spesifik

### Relasi Database:
```php
Kelompok hasMany Rekening
Rekening hasMany NomorBantu
NomorBantu belongsTo Rekening
Rekening belongsTo Kelompok
```

## 👥 Sistem Role & Permission

### Hierarki Role:
1. **Super Admin** - Akses penuh semua fitur
2. **Direktur Utama** - Akses penuh kecuali sistem & audit
3. **Direktur Umum** - Akses laporan dan aktivitas
4. **Kepala Bagian** - Manajemen akun, jurnal, approval
5. **Kasub Verifikasi Pembukuan** - Kelola CoA, approval pengeluaran
6. **Kasub Anggaran Pendapatan** - Kelola CoA, approval penerimaan
7. **Staff Verifikasi Pembukuan** - Input jurnal pengeluaran (draft)
8. **Staff Anggaran Pendapatan** - Input jurnal penerimaan (draft)
9. **Staff** - Akses terbatas sesuai kebutuhan

### Permission Matrix:

#### 📈 Staff Level:
- **Staff Anggaran Pendapatan**: 
  - View chart of accounts
  - Create/edit jurnal penerimaan (draft only)
  - View dashboard, profile

- **Staff Verifikasi Pembukuan**:
  - View chart of accounts
  - Create/edit jurnal pengeluaran (draft only)
  - View dashboard, profile

#### 👨‍💼 Supervisor Level:
- **Kasub Anggaran Pendapatan**:
  - Full CRUD chart of accounts
  - Approval jurnal penerimaan (dapat posting)
  - Input saldo awal
  - Akses laporan keuangan

- **Kasub Verifikasi Pembukuan**:
  - Full CRUD chart of accounts
  - Approval jurnal pengeluaran & umum (dapat posting)
  - Input saldo awal
  - Akses laporan keuangan

#### 🎯 Management Level:
- **Kepala Bagian**:
  - Manajemen user (create/update)
  - Full CRUD chart of accounts
  - Approval semua jenis jurnal
  - Company settings
  - Role management (terbatas)
  - Laporan keuangan

## 🔄 Alur Kerja Akuntansi

### 1. Setup Awal:
- Super Admin/Direktur setup company
- Setup chart of accounts (Kelompok → Rekening → Nomor Bantu)
- Input saldo awal

### 2. Operasional Harian:
- **Staff** input jurnal sesuai divisi (status: draft)
- **Kasub** review dan approve jurnal (status: posted)
- **Kabag** supervisi keseluruhan proses

### 3. Pelaporan:
- Semua level supervisor+ dapat akses laporan keuangan
- PDF report dengan filter tanggal dan status
- Print per transaksi jurnal

## ⚠️ Business Rules

### Separation of Duties:
- Staff pendapatan hanya handle jurnal penerimaan
- Staff pembukuan hanya handle jurnal pengeluaran & umum
- Kasub hanya bisa approve jurnal sesuai divisinya
- Kabag bisa approve semua jenis jurnal

### Approval Workflow:
- Staff: Create (draft) → Kasub: Review/Approve → Posted
- Hanya jurnal status "posted" yang masuk laporan keuangan final
- Audit trail lengkap untuk semua perubahan

## 📋 Next Steps

1. ✅ Database structure updated
2. ✅ Models & relationships configured
3. ✅ Role-based permissions implemented
4. ✅ Filament resources with proper access control
5. ✅ PDF reporting system
6. ⏳ Testing workflow dengan berbagai role
7. ⏳ User training documentation

---

**Version**: 1.1.0  
**Last Updated**: January 2025  
**Compliance**: SAKEP, PSAK, DJP Standards