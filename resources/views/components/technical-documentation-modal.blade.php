<x-filament::modal id="technical-documentation" width="6xl">
    <x-slot name="trigger">
        <span class="hidden"></span>
    </x-slot>

    <x-slot name="heading">
        Technical Documentation - Developer Guide
    </x-slot>

    <div class="prose dark:prose-invert max-w-none">
        <h2>🔧 Technical Documentation</h2>

        <h3>System Architecture</h3>
        <ul>
            <li><strong>Framework:</strong> Laravel 11.x</li>
            <li><strong>Admin Panel:</strong> Filament PHP 3.x</li>
            <li><strong>Database:</strong> PostgreSQL 14+</li>
            <li><strong>Authentication:</strong> Laravel Sanctum + Spatie Permission</li>
            <li><strong>UI:</strong> Livewire + Alpine.js + Tailwind CSS</li>
        </ul>

        <h3>Database Structure</h3>
        <h4>Main Tables:</h4>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded">
accounts (id, no_kel, no_rek, no_bantu, nama, kode, kel)
journal_entries (id, date, ref, description, created_by, deleted_at)
journal_details (id, journal_id, account_id, debit, credit)
users (id, name, email, password, is_verified)
roles (id, name, guard_name)
permissions (id, name, guard_name)
        </pre>

        <h3>Code Structure</h3>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded">
app/
  ├── Filament/
  │   ├── Admin/        # Admin Panel Resources
  │   ├── Accounting/   # Accounting Panel Resources
  │   └── Widgets/      # Dashboard Widgets
  ├── Models/           # Eloquent Models
  ├── Policies/         # Authorization Policies
  ├── Services/         # Business Logic
  └── Traits/           # Reusable Traits
        </pre>

        <h3>Important Models</h3>
        <h4>JurnalRekeningAir.php</h4>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
use HasFactory, SoftDeletes, LogsActivity;

protected $fillable = [
    'tanggal', 'no_bukti', 'keterangan', 'total_debit',
    'total_kredit', 'created_by', 'status'
];

protected static function boot() {
    parent::boot();
    static::creating(function ($model) {
        $model->created_by = auth()->id();
    });
}
        </pre>

        <h3>Authorization Flow</h3>
        <ol>
            <li><strong>Registration:</strong> User registers → Admin verifies → Role assigned</li>
            <li><strong>Roles:</strong> super_admin, akuntan, kasir, direktur</li>
            <li><strong>Permissions:</strong> Managed via Spatie Permission package</li>
            <li><strong>Policies:</strong> Each resource has policy for CRUD operations</li>
        </ol>

        <h3>Key Features Implementation</h3>

        <h4>1. Soft Deletes</h4>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
// In model
use SoftDeletes;

// In migration
$table->softDeletes();

// Usage
$jurnal->delete();           // Soft delete
$jurnal->forceDelete();      // Permanent delete
$jurnal->restore();          // Restore
        </pre>

        <h4>2. Activity Logs</h4>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
use LogsActivity;

protected static $logAttributes = ['*'];
protected static $logOnlyDirty = true;

// Auto logs: created, updated, deleted
        </pre>

        <h4>3. Auto created_by</h4>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
static::creating(function ($model) {
    if (auth()->check()) {
        $model->created_by = auth()->id();
    }
});
        </pre>

        <h3>API Endpoints (if needed)</h3>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
POST   /api/journals          # Create journal
GET    /api/journals          # List journals
GET    /api/journals/{id}     # Show journal
PUT    /api/journals/{id}     # Update journal
DELETE /api/journals/{id}     # Delete journal
GET    /api/reports/balance   # Balance sheet
GET    /api/reports/income    # Income statement
        </pre>

        <h3>Error Handling</h3>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
try {
    DB::transaction(function () use ($data) {
        $journal = JurnalRekeningAir::create($data);
        // Process details...
    });

    Notification::make()
        ->success()
        ->title('Berhasil')
        ->body('Jurnal berhasil disimpan')
        ->send();

} catch (\Exception $e) {
    Log::error('Journal creation failed: ' . $e->getMessage());

    Notification::make()
        ->danger()
        ->title('Error')
        ->body('Terjadi kesalahan: ' . $e->getMessage())
        ->send();
}
        </pre>

        <h3>Deployment</h3>
        <ol>
            <li>Run migrations: <code>php artisan migrate</code></li>
            <li>Seed initial data: <code>php artisan db:seed</code></li>
            <li>Clear cache: <code>php artisan optimize:clear</code></li>
            <li>Run queue worker: <code>php artisan queue:work</code></li>
            <li>Schedule cron: <code>* * * * * cd /path && php artisan schedule:run</code></li>
        </ol>

        <h3>Testing</h3>
        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded text-sm">
# Run all tests
php artisan test

# Run specific test
php artisan test --filter JournalTest

# With coverage
php artisan test --coverage
        </pre>

        <h3>Performance Optimization</h3>
        <ul>
            <li>Use eager loading: <code>with(['details', 'account'])</code></li>
            <li>Cache queries: <code>Cache::remember('accounts', 3600, ...)</code></li>
            <li>Index database columns for faster queries</li>
            <li>Use queue for heavy operations</li>
            <li>Optimize images and assets</li>
        </ul>

        <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg mt-6">
            <p class="text-sm"><strong>📚 Further Reading:</strong> Check <code>SECURITY_AUDIT.md</code>, <code>DEPLOYMENT_GUIDE.md</code>, and <code>IMPLEMENTATION_SUMMARY.md</code> for more details.</p>
        </div>
    </div>
</x-filament::modal>
