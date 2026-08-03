<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Jurnal Koreksi" description="Gunakan halaman ini untuk membuat jurnal koreksi (reversal + akun koreksi) dari item jurnal yang sudah diinput.">
            <form wire:submit="createKoreksi" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                    <x-filament::button type="submit" icon="heroicon-o-check-circle" size="lg">
                        Simpan Jurnal Koreksi
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
