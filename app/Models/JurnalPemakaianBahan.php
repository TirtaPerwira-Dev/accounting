<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPemakaianBahan extends Model
{
    protected $fillable = [
        'no_reff', 'tanggal', 'bukti', 'beban_bagian', 'dibayar', 'no_check',
        'kelompok_debit_id', 'rekening_debit_id', 'nomor_bantu_debit_id', 'data_debit',
        'kelompok_kredit_id', 'rekening_kredit_id', 'nomor_bantu_kredit_id', 'data_kredit',
        'rp', 'keterangan', 'keterangan_1', 'keterangan_2', 'keterangan_3', 'keterangan_4',
        'ref', 'kode_proyek_id', 'group_transaksi', 'item_sequence',
        'company_id', 'is_confirmed', 'confirmed_by', 'confirmed_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rp' => 'decimal:2',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function kelompokDebit(): BelongsTo { return $this->belongsTo(Kelompok::class, 'kelompok_debit_id'); }
    public function rekeningDebit(): BelongsTo { return $this->belongsTo(Rekening::class, 'rekening_debit_id'); }
    public function nomorBantuDebit(): BelongsTo { return $this->belongsTo(NomorBantu::class, 'nomor_bantu_debit_id'); }
    public function kelompokKredit(): BelongsTo { return $this->belongsTo(Kelompok::class, 'kelompok_kredit_id'); }
    public function rekeningKredit(): BelongsTo { return $this->belongsTo(Rekening::class, 'rekening_kredit_id'); }
    public function nomorBantuKredit(): BelongsTo { return $this->belongsTo(NomorBantu::class, 'nomor_bantu_kredit_id'); }
    public function kodeProyek(): BelongsTo { return $this->belongsTo(KodeProyek::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
