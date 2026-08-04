<?php

namespace App\Filament\Accounting\Pages;

use App\Models\JurnalBayarKasBankDetail;
use App\Models\JurnalMemorial;
use App\Models\JurnalMemorialDetail;
use App\Models\JurnalPembelianDetail;
use App\Models\JurnalPemakaianBahanDetail;
use App\Models\JurnalPenerimaanKasDetail;
use App\Models\JurnalRekeningAirDetail;
use App\Models\KodeProyek;
use App\Models\NomorBantu;
use App\Models\Rekening;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class JurnalKoreksi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static string $view = 'filament.accounting.pages.jurnal-koreksi';

    protected static ?string $navigationLabel = 'Jurnal Koreksi';

    protected static ?string $navigationGroup = 'Jurnal';

    protected static ?int $navigationSort = 7;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tanggal' => now()->toDateString(),
            'bukti' => 'KOR-' . now()->format('Ymd-His'),
            'sumber_jurnal' => 'memorial',
            'search_by' => 'nomor_rekening',
            'source_search_results' => [],
            'jumlah_koreksi' => 0,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Section 1: Cari Item Sumber')
                    ->description('Pilih sumber jurnal dan kata kunci rekening, lalu klik tombol Cari.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('sumber_jurnal')
                                    ->label('Sumber Jurnal')
                                    ->options([
                                        'all' => 'Semua Sumber (Rekening / Nomor Bantu)',
                                        'memorial' => 'Jurnal Memorial',
                                        'rekening_air' => 'Jurnal Rekening Air',
                                        'penerimaan_kas' => 'Jurnal Penerimaan Kas',
                                        'bayar_kas_bank' => 'Jurnal Bayar Kas/Bank',
                                        'pembelian' => 'Jurnal Pembelian',
                                        'pemakaian_bahan' => 'Jurnal Pemakaian Bahan',
                                    ])
                                    ->default('memorial')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set): void {
                                        $set('item_sumber', null);
                                        $set('source_search_results', []);
                                        $set('source_kelompok_id', null);
                                        $set('source_rekening_id', null);
                                        $set('source_nomor_bantu_id', null);
                                        $set('source_kode_proyek_id', null);
                                        $set('source_posisi', null);
                                        $set('source_jumlah', null);
                                    }),

                                Forms\Components\Select::make('search_by')
                                    ->label('Cari Berdasarkan')
                                    ->options([
                                        'nomor_rekening' => 'Nomor Rekening',
                                        'nomor_bantu' => 'Nomor Bantu',
                                        'nama_akun' => 'Nama Akun',
                                        'nomor_voucher' => 'Nomor Voucher / No Bukti',
                                        'nomor_invoice' => 'Nomor Invoice / Dokumen',
                                    ])
                                    ->default('nomor_rekening')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set): void {
                                        $set('item_sumber', null);
                                        $set('source_search_results', []);
                                    }),

                                Forms\Components\TextInput::make('search_kode_akun')
                                    ->label('Kata Kunci Pencarian')
                                    ->placeholder('Contoh: 1101, 020, kas, VCR-001, INV-001')
                                    ->helperText('Gunakan pilihan "Cari Berdasarkan" agar pencarian sesuai field jurnal.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set): void {
                                        $set('item_sumber', null);
                                        $set('source_search_results', []);
                                    }),
                            ]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('search_source_items')
                                ->label('Cari')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('info')
                                ->action(function (Forms\Get $get, Forms\Set $set): void {
                                    $type = $get('sumber_jurnal');
                                    $keyword = trim((string) ($get('search_kode_akun') ?? ''));
                                    $searchBy = (string) ($get('search_by') ?? 'nomor_rekening');

                                    if (!$type || $keyword === '') {
                                        Notification::make()
                                            ->title('Filter belum lengkap')
                                            ->body('Pilih sumber jurnal dan isi kata kunci pencarian terlebih dahulu.')
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $options = $type === 'all'
                                        ? $this->getAllSourceItemOptions($keyword, $searchBy)
                                        : $this->getSourceItemOptions($type, $keyword, $searchBy, null);

                                    $set('source_search_results', $options);
                                    $set('item_sumber', null);

                                    if (empty($options)) {
                                        Notification::make()
                                            ->title('Data tidak ditemukan')
                                            ->body('Tidak ada item posted yang sesuai filter pencarian.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    Notification::make()
                                        ->title('Pencarian berhasil')
                                        ->body(count($options) . ' item ditemukan. Lanjutkan pilih item di Section 2.')
                                        ->success()
                                        ->send();
                                }),
                        ])->alignment('start'),
                    ]),

                Forms\Components\Section::make('Section 2: Hasil Pencarian Item Sumber')
                    ->description('Data hasil pencarian dari section pertama ditampilkan di sini untuk dipilih.')
                    ->schema([

                        Forms\Components\Select::make('item_sumber')
                            ->label('Item Sumber yang Dikoreksi')
                            ->required()
                            ->searchable()
                            ->options(fn(Forms\Get $get): array => $get('source_search_results') ?? [])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                $source = $this->resolveSelectedSource($state);

                                $set('source_kelompok_id', $source['kelompok_id'] ?? null);
                                $set('source_rekening_id', $source['rekening_id'] ?? null);
                                $set('source_nomor_bantu_id', $source['nomor_bantu_id'] ?? null);
                                $set('source_kode_proyek_id', $source['kode_proyek_id'] ?? null);
                                $set('source_posisi', $source['posisi'] ?? null);
                                $set('source_jumlah', $source['jumlah'] ?? null);

                                if (!empty($source['jumlah'])) {
                                    $set('jumlah_koreksi', (float) $source['jumlah']);
                                }
                            })
                            ->helperText('Menampilkan maksimal 100 data terbaru sesuai filter.'),

                        Forms\Components\Placeholder::make('source_preview')
                            ->label('Preview Item Sumber')
                            ->content(function (Forms\Get $get): string {
                                $source = $this->resolveSelectedSource($get('item_sumber'));
                                if (!$source) {
                                    return 'Belum ada item sumber yang dipilih.';
                                }

                                $kode = $source['kode_akun'] ?? '-';
                                $nama = $source['nama_akun'] ?? '-';
                                $posisi = $source['posisi'] ?? '-';
                                $jumlah = number_format((float) ($source['jumlah'] ?? 0), 0, ',', '.');

                                return "Akun: {$kode} {$nama} | Posisi: {$posisi} | Jumlah: Rp {$jumlah}";
                            }),

                        Forms\Components\Placeholder::make('jurnal_t_preview')
                            ->label('Simulasi Jurnal T (Debit | Kredit)')
                            ->content(function (Forms\Get $get): string {
                                $source = $this->resolveSelectedSource($get('item_sumber'));
                                if (!$source) {
                                    return 'Belum ada item sumber yang dipilih.';
                                }

                                $sourcePosisi = strtoupper((string) ($source['posisi'] ?? '-'));
                                $koreksiPosisi = $sourcePosisi === 'D' ? 'K' : 'D';
                                $jumlah = number_format((float) ($get('jumlah_koreksi') ?? $source['jumlah'] ?? 0), 0, ',', '.');

                                return "Sumber ({$sourcePosisi}) Rp {$jumlah} | Koreksi ({$koreksiPosisi}) Rp {$jumlah}";
                            }),
                    ])
                    ->visible(fn(Forms\Get $get): bool => !empty($get('source_search_results'))),

                Forms\Components\Section::make('Section 3: Input Koreksi')
                    ->description('Pilih akun tujuan koreksi. Sistem akan membuat jurnal memorial koreksi (reversal + akun koreksi).')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal Koreksi')
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('bukti')
                                    ->label('No. Bukti Koreksi')
                                    ->required()
                                    ->maxLength(100),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('koreksi_rekening_id')
                                    ->label('Rekening Koreksi')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Rekening::query()
                                            ->with('kelompok')
                                            ->where(function ($query) use ($search) {
                                                $query->where('no_rek', 'like', "%{$search}%")
                                                    ->orWhere('nama_rek', 'like', "%{$search}%")
                                                    ->orWhereHas('kelompok', fn($q) => $q->where('no_kel', 'like', "%{$search}%"));
                                            })
                                            ->orderBy('no_rek')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function (Rekening $rekening) {
                                                $label = sprintf(
                                                    '[%02d-%04d] %s',
                                                    (int) ($rekening->kelompok?->no_kel ?? 0),
                                                    (int) $rekening->no_rek,
                                                    $rekening->nama_rek
                                                );

                                                return [$rekening->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        if (!$value) {
                                            return null;
                                        }

                                        $rekening = Rekening::with('kelompok')->find($value);
                                        if (!$rekening) {
                                            return null;
                                        }

                                        return sprintf(
                                            '[%02d-%04d] %s',
                                            (int) ($rekening->kelompok?->no_kel ?? 0),
                                            (int) $rekening->no_rek,
                                            $rekening->nama_rek
                                        );
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set): void {
                                        $set('koreksi_nomor_bantu_id', null);
                                    }),

                                Forms\Components\Select::make('koreksi_nomor_bantu_id')
                                    ->label('Nomor Bantu Koreksi')
                                    ->searchable()
                                    ->options(function (Forms\Get $get): array {
                                        $rekeningId = $get('koreksi_rekening_id');
                                        if (!$rekeningId) {
                                            return [];
                                        }

                                        return NomorBantu::query()
                                            ->where('rekening_id', $rekeningId)
                                            ->orderBy('no_bantu')
                                            ->limit(100)
                                            ->get()
                                            ->mapWithKeys(fn(NomorBantu $nb) => [
                                                $nb->id => "[{$nb->no_bantu}] {$nb->nm_bantu}",
                                            ])
                                            ->toArray();
                                    }),

                                Forms\Components\Select::make('koreksi_kode_proyek_id')
                                    ->label('Kode Proyek')
                                    ->searchable()
                                    ->options(fn(): array => KodeProyek::query()->pluck('name', 'id')->toArray())
                                    ->placeholder('Opsional'),
                            ]),

                        Forms\Components\TextInput::make('jumlah_koreksi')
                            ->label('Jumlah Koreksi')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Koreksi')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Koreksi salah pilih akun biaya operasional'),

                        Forms\Components\Hidden::make('source_kelompok_id'),
                        Forms\Components\Hidden::make('source_rekening_id'),
                        Forms\Components\Hidden::make('source_nomor_bantu_id'),
                        Forms\Components\Hidden::make('source_kode_proyek_id'),
                        Forms\Components\Hidden::make('source_posisi'),
                        Forms\Components\Hidden::make('source_jumlah'),
                    ]),
            ])
            ->statePath('data');
    }

    public function createKoreksi(): void
    {
        $data = $this->form->getState();

        validator($data, [
            'tanggal' => ['required', 'date'],
            'bukti' => ['required', 'string', 'max:100'],
            'keterangan' => ['required', 'string'],
            'item_sumber' => ['required', 'string'],
            'koreksi_rekening_id' => ['required', 'integer', 'exists:rekenings,id'],
            'koreksi_nomor_bantu_id' => ['nullable', 'integer', 'exists:nomor_bantus,id'],
            'koreksi_kode_proyek_id' => ['nullable', 'integer', 'exists:kode_proyeks,id'],
            'jumlah_koreksi' => ['required', 'numeric', 'min:1'],
            'source_posisi' => ['required', 'in:D,K'],
            'source_kelompok_id' => ['required', 'integer', 'exists:kelompoks,id'],
            'source_rekening_id' => ['required', 'integer', 'exists:rekenings,id'],
        ], [
            'item_sumber.required' => 'Pilih item sumber jurnal yang ingin dikoreksi.',
            'source_posisi.required' => 'Posisi sumber tidak terbaca. Pilih ulang item sumber.',
        ])->validate();

        $rekeningKoreksi = Rekening::with('kelompok')->findOrFail($data['koreksi_rekening_id']);
        $jumlah = (float) $data['jumlah_koreksi'];
        $sourcePosisi = strtoupper((string) $data['source_posisi']);

        // Header memorial menjadi reversal dari akun sumber.
        $kodeHeader = $sourcePosisi === 'D' ? 'K' : 'D';

        DB::transaction(function () use ($data, $rekeningKoreksi, $jumlah, $sourcePosisi, $kodeHeader): void {
            $header = JurnalMemorial::create([
                'tanggal' => $data['tanggal'],
                'bukti' => $data['bukti'],
                'kelompok_id' => $data['source_kelompok_id'],
                'rekening_id' => $data['source_rekening_id'],
                'nomor_bantu_id' => $data['source_nomor_bantu_id'] ?? null,
                'rp' => $jumlah,
                'kode' => $kodeHeader,
                'keterangan' => '[KOREKSI] ' . $data['keterangan'],
                'ref' => '6',
                'kode_proyek_id' => $data['source_kode_proyek_id'] ?? null,
                'company_id' => 1,
                'created_by' => auth()->id(),
                'is_confirmed' => false,
            ]);

            JurnalMemorialDetail::create([
                'jurnal_memorial_id' => $header->id,
                'bukti' => $data['bukti'],
                'keterangan' => '[KOREKSI] ' . $data['keterangan'],
                'jumlah' => $jumlah,
                'posisi' => $sourcePosisi,
                'kelompok_id' => $rekeningKoreksi->kelompok_id,
                'rekening_id' => $rekeningKoreksi->id,
                'nomor_bantu_id' => $data['koreksi_nomor_bantu_id'] ?? null,
                'kode_proyek_id' => $data['koreksi_kode_proyek_id'] ?? ($data['source_kode_proyek_id'] ?? null),
            ]);
        });

        Notification::make()
            ->title('Jurnal koreksi berhasil dibuat')
            ->body('Koreksi disimpan sebagai Jurnal Memorial dan siap dikonfirmasi.')
            ->success()
            ->send();

        $this->form->fill([
            'tanggal' => now()->toDateString(),
            'bukti' => 'KOR-' . now()->format('Ymd-His'),
            'sumber_jurnal' => $data['sumber_jurnal'] ?? 'memorial',
            'search_by' => $data['search_by'] ?? 'nomor_rekening',
            'search_kode_akun' => $data['search_kode_akun'] ?? null,
            'source_search_results' => [],
            'jumlah_koreksi' => 0,
        ]);
    }

    private function getAllSourceItemOptions(?string $keyword, ?string $searchBy): array
    {
        if (!$keyword) {
            return [];
        }

        return array_slice(array_merge(
            $this->getMemorialSourceOptions($keyword, $searchBy),
            $this->getRekeningAirSourceOptions($keyword, $searchBy),
            $this->getPenerimaanKasSourceOptions($keyword, $searchBy),
            $this->getBayarKasBankSourceOptions($keyword, $searchBy),
            $this->getPembelianSourceOptions($keyword, $searchBy),
            $this->getPemakaianBahanSourceOptions($keyword, null, $searchBy),
        ), 0, 100, true);
    }

    private function getSourceItemOptions(string $type, ?string $keyword, ?string $searchBy, ?string $side): array
    {
        return match ($type) {
            'memorial' => $this->getMemorialSourceOptions($keyword, $searchBy),
            'rekening_air' => $this->getRekeningAirSourceOptions($keyword, $searchBy),
            'penerimaan_kas' => $this->getPenerimaanKasSourceOptions($keyword, $searchBy),
            'bayar_kas_bank' => $this->getBayarKasBankSourceOptions($keyword, $searchBy),
            'pembelian' => $this->getPembelianSourceOptions($keyword, $searchBy),
            'pemakaian_bahan' => $this->getPemakaianBahanSourceOptions($keyword, $side, $searchBy),
            default => [],
        };
    }

    private function getMemorialSourceOptions(?string $keyword, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);

        return JurnalMemorialDetail::query()
            ->with(['rekening.kelompok', 'nomorBantu', 'jurnalMemorial'])
            ->whereHas('jurnalMemorial', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                match ($searchBy) {
                    'nomor_rekening' => $query->whereHas('rekening', fn($q) => $q->where('no_rek', 'like', "%{$digits}%")),
                    'nomor_bantu' => $query->whereHas('nomorBantu', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%")),
                    'nama_akun' => $query->whereHas('rekening', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%")),
                    'nomor_voucher', 'nomor_invoice' => $query
                        ->where('bukti', 'like', "%{$keyword}%")
                        ->orWhereHas('jurnalMemorial', fn($q) => $q->where('bukti', 'like', "%{$keyword}%")),
                    default => $query->whereHas('rekening', function ($q) use ($keyword, $digits) {
                        $q->where('no_rek', 'like', "%{$digits}%")
                            ->orWhere('nama_rek', 'like', "%{$keyword}%");
                    }),
                };
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(function (JurnalMemorialDetail $item) {
                $posisi = strtoupper((string) $item->posisi);
                $kode = $this->formatKodeAkun($item->rekening, $item->nomorBantu);
                $tanggal = $item->jurnalMemorial?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->bukti ?: ($item->jurnalMemorial?->bukti ?? '-');
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                $value = "memorial|{$item->id}|{$posisi}";
                $label = "{$tanggal} | {$bukti} | {$kode} | {$posisi} | Rp {$jumlah}";

                return [$value => $label];
            })
            ->toArray();
    }

    private function getRekeningAirSourceOptions(?string $keyword, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);

        return JurnalRekeningAirDetail::query()
            ->with(['rekening.kelompok', 'nomorBantu', 'jurnalRekeningAir'])
            ->whereHas('jurnalRekeningAir', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                match ($searchBy) {
                    'nomor_rekening' => $query->whereHas('rekening', fn($q) => $q->where('no_rek', 'like', "%{$digits}%")),
                    'nomor_bantu' => $query->whereHas('nomorBantu', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%")),
                    'nama_akun' => $query->whereHas('rekening', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%")),
                    'nomor_voucher', 'nomor_invoice' => $query->whereHas('jurnalRekeningAir', fn($q) => $q->where('bukti', 'like', "%{$keyword}%")),
                    default => $query->whereHas('rekening', function ($q) use ($keyword, $digits) {
                        $q->where('no_rek', 'like', "%{$digits}%")
                            ->orWhere('nama_rek', 'like', "%{$keyword}%");
                    }),
                };
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(function (JurnalRekeningAirDetail $item) {
                $posisi = strtolower((string) $item->position) === 'kredit' ? 'K' : 'D';
                $kode = $this->formatKodeAkun($item->rekening, $item->nomorBantu);
                $tanggal = $item->jurnalRekeningAir?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->jurnalRekeningAir?->bukti ?? '-';
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                $value = "rekening_air|{$item->id}|{$posisi}";
                $label = "{$tanggal} | {$bukti} | {$kode} | {$posisi} | Rp {$jumlah}";

                return [$value => $label];
            })
            ->toArray();
    }

    private function getPenerimaanKasSourceOptions(?string $keyword, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);

        return JurnalPenerimaanKasDetail::query()
            ->with(['rekening.kelompok', 'nomorBantu', 'jurnalPenerimaanKas'])
            ->whereHas('jurnalPenerimaanKas', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                match ($searchBy) {
                    'nomor_rekening' => $query->whereHas('rekening', fn($q) => $q->where('no_rek', 'like', "%{$digits}%")),
                    'nomor_bantu' => $query->whereHas('nomorBantu', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%")),
                    'nama_akun' => $query->whereHas('rekening', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%")),
                    'nomor_voucher', 'nomor_invoice' => $query
                        ->where('nomor_bukti', 'like', "%{$keyword}%")
                        ->orWhereHas('jurnalPenerimaanKas', fn($q) => $q->where('nomor_bukti', 'like', "%{$keyword}%")),
                    default => $query->whereHas('rekening', function ($q) use ($keyword, $digits) {
                        $q->where('no_rek', 'like', "%{$digits}%")
                            ->orWhere('nama_rek', 'like', "%{$keyword}%");
                    }),
                };
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(function (JurnalPenerimaanKasDetail $item) {
                $posisi = 'K';
                $kode = $this->formatKodeAkun($item->rekening, $item->nomorBantu);
                $tanggal = $item->jurnalPenerimaanKas?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->nomor_bukti ?: ($item->jurnalPenerimaanKas?->nomor_bukti ?? '-');
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                $value = "penerimaan_kas|{$item->id}|{$posisi}";
                $label = "{$tanggal} | {$bukti} | {$kode} | {$posisi} | Rp {$jumlah}";

                return [$value => $label];
            })
            ->toArray();
    }

    private function getBayarKasBankSourceOptions(?string $keyword, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);

        return JurnalBayarKasBankDetail::query()
            ->with(['rekening.kelompok', 'nomorBantu', 'jurnalBayarKasBank'])
            ->whereHas('jurnalBayarKasBank', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                match ($searchBy) {
                    'nomor_rekening' => $query->whereHas('rekening', fn($q) => $q->where('no_rek', 'like', "%{$digits}%")),
                    'nomor_bantu' => $query->whereHas('nomorBantu', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%")),
                    'nama_akun' => $query->whereHas('rekening', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%")),
                    'nomor_voucher', 'nomor_invoice' => $query
                        ->where('no_voucher', 'like', "%{$keyword}%")
                        ->orWhereHas('jurnalBayarKasBank', function ($q) use ($keyword) {
                            $q->where('no_voucher', 'like', "%{$keyword}%")
                                ->orWhere('bukti', 'like', "%{$keyword}%");
                        }),
                    default => $query->whereHas('rekening', function ($q) use ($keyword, $digits) {
                        $q->where('no_rek', 'like', "%{$digits}%")
                            ->orWhere('nama_rek', 'like', "%{$keyword}%");
                    }),
                };
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(function (JurnalBayarKasBankDetail $item) {
                $posisi = 'D';
                $kode = $this->formatKodeAkun($item->rekening, $item->nomorBantu);
                $tanggal = $item->jurnalBayarKasBank?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->no_voucher ?: ($item->jurnalBayarKasBank?->bukti ?? '-');
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                $value = "bayar_kas_bank|{$item->id}|{$posisi}";
                $label = "{$tanggal} | {$bukti} | {$kode} | {$posisi} | Rp {$jumlah}";

                return [$value => $label];
            })
            ->toArray();
    }

    private function getPembelianSourceOptions(?string $keyword, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);

        return JurnalPembelianDetail::query()
            ->with(['rekeningDebit.kelompok', 'nomorBantuDebit', 'jurnalPembelian'])
            ->whereHas('jurnalPembelian', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                match ($searchBy) {
                    'nomor_rekening' => $query->whereHas('rekeningDebit', fn($q) => $q->where('no_rek', 'like', "%{$digits}%")),
                    'nomor_bantu' => $query->whereHas('nomorBantuDebit', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%")),
                    'nama_akun' => $query->whereHas('rekeningDebit', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%")),
                    'nomor_voucher', 'nomor_invoice' => $query
                        ->where('bukti', 'like', "%{$keyword}%")
                        ->orWhereHas('jurnalPembelian', fn($q) => $q->where('bukti', 'like', "%{$keyword}%")),
                    default => $query->whereHas('rekeningDebit', function ($q) use ($keyword, $digits) {
                        $q->where('no_rek', 'like', "%{$digits}%")
                            ->orWhere('nama_rek', 'like', "%{$keyword}%");
                    }),
                };
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(function (JurnalPembelianDetail $item) {
                $posisi = 'D';
                $kode = $this->formatKodeAkun($item->rekeningDebit, $item->nomorBantuDebit);
                $tanggal = $item->jurnalPembelian?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->bukti ?: ($item->jurnalPembelian?->bukti ?? '-');
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                $value = "pembelian|{$item->id}|{$posisi}";
                $label = "{$tanggal} | {$bukti} | {$kode} | {$posisi} | Rp {$jumlah}";

                return [$value => $label];
            })
            ->toArray();
    }

    private function getPemakaianBahanSourceOptions(?string $keyword, ?string $side, ?string $searchBy): array
    {
        $keyword = trim((string) $keyword);
        $side = in_array($side, ['debit', 'kredit'], true) ? $side : 'auto';

        return JurnalPemakaianBahanDetail::query()
            ->with([
                'rekeningDebit.kelompok',
                'nomorBantuDebit',
                'rekeningKredit.kelompok',
                'nomorBantuKredit',
                'jurnalPemakaianBahan',
            ])
            ->whereHas('jurnalPemakaianBahan', fn($query) => $query->where('is_posted', true))
            ->where(function ($query) use ($keyword, $searchBy) {
                if ($keyword === '') {
                    return;
                }

                $digits = preg_replace('/\D/', '', $keyword);

                if ($searchBy === 'nomor_rekening') {
                    $query->whereHas('rekeningDebit', fn($q) => $q->where('no_rek', 'like', "%{$digits}%"))
                        ->orWhereHas('rekeningKredit', fn($q) => $q->where('no_rek', 'like', "%{$digits}%"));
                    return;
                }

                if ($searchBy === 'nomor_bantu') {
                    $query->whereHas('nomorBantuDebit', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%"))
                        ->orWhereHas('nomorBantuKredit', fn($q) => $q->where('no_bantu', 'like', "%{$digits}%"));
                    return;
                }

                if ($searchBy === 'nama_akun') {
                    $query->whereHas('rekeningDebit', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%"))
                        ->orWhereHas('rekeningKredit', fn($q) => $q->where('nama_rek', 'like', "%{$keyword}%"));
                    return;
                }

                if (in_array($searchBy, ['nomor_voucher', 'nomor_invoice'], true)) {
                    $query->where('bukti', 'like', "%{$keyword}%")
                        ->orWhereHas('jurnalPemakaianBahan', fn($q) => $q->where('bukti', 'like', "%{$keyword}%"));
                    return;
                }

                $query->whereHas('rekeningDebit', function ($q) use ($keyword, $digits) {
                    $q->where('no_rek', 'like', "%{$digits}%")
                        ->orWhere('nama_rek', 'like', "%{$keyword}%");
                })->orWhereHas('rekeningKredit', function ($q) use ($keyword, $digits) {
                    $q->where('no_rek', 'like', "%{$digits}%")
                        ->orWhere('nama_rek', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->flatMap(function (JurnalPemakaianBahanDetail $item) use ($side) {
                $rows = [];
                $tanggal = $item->jurnalPemakaianBahan?->tanggal?->format('d/m/Y') ?? '-';
                $bukti = $item->bukti ?: ($item->jurnalPemakaianBahan?->bukti ?? '-');
                $jumlah = number_format((float) $item->jumlah, 0, ',', '.');

                if ($side === 'auto' || $side === 'debit') {
                    $kodeDebit = $this->formatKodeAkun($item->rekeningDebit, $item->nomorBantuDebit);
                    $valueDebit = "pemakaian_bahan|{$item->id}|D";
                    $rows[$valueDebit] = "{$tanggal} | {$bukti} | {$kodeDebit} | D | Rp {$jumlah}";
                }

                if ($side === 'auto' || $side === 'kredit') {
                    $kodeKredit = $this->formatKodeAkun($item->rekeningKredit, $item->nomorBantuKredit);
                    $valueKredit = "pemakaian_bahan|{$item->id}|K";
                    $rows[$valueKredit] = "{$tanggal} | {$bukti} | {$kodeKredit} | K | Rp {$jumlah}";
                }

                return $rows;
            })
            ->toArray();
    }

    private function resolveSelectedSource(?string $selected): ?array
    {
        if (!$selected) {
            return null;
        }

        [$type, $id, $side] = array_pad(explode('|', $selected), 3, null);
        if (!$type || !$id) {
            return null;
        }

        return match ($type) {
            'memorial' => $this->resolveMemorialSource((int) $id),
            'rekening_air' => $this->resolveRekeningAirSource((int) $id),
            'penerimaan_kas' => $this->resolvePenerimaanKasSource((int) $id),
            'bayar_kas_bank' => $this->resolveBayarKasBankSource((int) $id),
            'pembelian' => $this->resolvePembelianSource((int) $id),
            'pemakaian_bahan' => $this->resolvePemakaianBahanSource((int) $id, strtoupper((string) $side)),
            default => null,
        };
    }

    private function resolveMemorialSource(int $id): ?array
    {
        $item = JurnalMemorialDetail::with(['rekening.kelompok', 'nomorBantu'])->find($id);
        if (!$item) {
            return null;
        }

        return [
            'kelompok_id' => $item->kelompok_id,
            'rekening_id' => $item->rekening_id,
            'nomor_bantu_id' => $item->nomor_bantu_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => strtoupper((string) $item->posisi),
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekening, $item->nomorBantu),
            'nama_akun' => $item->rekening?->nama_rek,
        ];
    }

    private function resolveRekeningAirSource(int $id): ?array
    {
        $item = JurnalRekeningAirDetail::with(['rekening.kelompok', 'nomorBantu'])->find($id);
        if (!$item) {
            return null;
        }

        return [
            'kelompok_id' => $item->kelompok_id,
            'rekening_id' => $item->rekening_id,
            'nomor_bantu_id' => $item->nomor_bantu_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => strtolower((string) $item->position) === 'kredit' ? 'K' : 'D',
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekening, $item->nomorBantu),
            'nama_akun' => $item->rekening?->nama_rek,
        ];
    }

    private function resolvePenerimaanKasSource(int $id): ?array
    {
        $item = JurnalPenerimaanKasDetail::with(['rekening.kelompok', 'nomorBantu'])->find($id);
        if (!$item) {
            return null;
        }

        return [
            'kelompok_id' => $item->kelompok_id,
            'rekening_id' => $item->rekening_id,
            'nomor_bantu_id' => $item->nomor_bantu_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => 'K',
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekening, $item->nomorBantu),
            'nama_akun' => $item->rekening?->nama_rek,
        ];
    }

    private function resolveBayarKasBankSource(int $id): ?array
    {
        $item = JurnalBayarKasBankDetail::with(['rekening.kelompok', 'nomorBantu'])->find($id);
        if (!$item) {
            return null;
        }

        return [
            'kelompok_id' => $item->kelompok_id,
            'rekening_id' => $item->rekening_id,
            'nomor_bantu_id' => $item->nomor_bantu_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => 'D',
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekening, $item->nomorBantu),
            'nama_akun' => $item->rekening?->nama_rek,
        ];
    }

    private function resolvePembelianSource(int $id): ?array
    {
        $item = JurnalPembelianDetail::with(['rekeningDebit.kelompok', 'nomorBantuDebit'])->find($id);
        if (!$item) {
            return null;
        }

        return [
            'kelompok_id' => $item->kelompok_debit_id,
            'rekening_id' => $item->rekening_debit_id,
            'nomor_bantu_id' => $item->nomor_bantu_debit_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => 'D',
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekeningDebit, $item->nomorBantuDebit),
            'nama_akun' => $item->rekeningDebit?->nama_rek,
        ];
    }

    private function resolvePemakaianBahanSource(int $id, string $side): ?array
    {
        $item = JurnalPemakaianBahanDetail::with([
            'rekeningDebit.kelompok',
            'nomorBantuDebit',
            'rekeningKredit.kelompok',
            'nomorBantuKredit',
        ])->find($id);

        if (!$item) {
            return null;
        }

        if ($side === 'K') {
            return [
                'kelompok_id' => $item->kelompok_kredit_id,
                'rekening_id' => $item->rekening_kredit_id,
                'nomor_bantu_id' => $item->nomor_bantu_kredit_id,
                'kode_proyek_id' => $item->kode_proyek_id,
                'posisi' => 'K',
                'jumlah' => (float) $item->jumlah,
                'kode_akun' => $this->formatKodeAkun($item->rekeningKredit, $item->nomorBantuKredit),
                'nama_akun' => $item->rekeningKredit?->nama_rek,
            ];
        }

        return [
            'kelompok_id' => $item->kelompok_debit_id,
            'rekening_id' => $item->rekening_debit_id,
            'nomor_bantu_id' => $item->nomor_bantu_debit_id,
            'kode_proyek_id' => $item->kode_proyek_id,
            'posisi' => 'D',
            'jumlah' => (float) $item->jumlah,
            'kode_akun' => $this->formatKodeAkun($item->rekeningDebit, $item->nomorBantuDebit),
            'nama_akun' => $item->rekeningDebit?->nama_rek,
        ];
    }

    private function formatKodeAkun(?Rekening $rekening, ?NomorBantu $nomorBantu): string
    {
        if (!$rekening) {
            return '-';
        }

        $noKel = (int) ($rekening->kelompok?->no_kel ?? 0);
        $noRek = (int) $rekening->no_rek;

        if ($nomorBantu) {
            return sprintf('%02d-%04d-%s', $noKel, $noRek, $nomorBantu->no_bantu);
        }

        return sprintf('%02d-%04d', $noKel, $noRek);
    }
}
