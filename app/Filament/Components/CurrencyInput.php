<?php

namespace App\Filament\Components;

use Filament\Forms\Components\TextInput;

class CurrencyInput extends TextInput
{
    protected string $view = 'filament.components.currency-input';

    public function setUp(): void
    {
        parent::setUp();

        $this->prefix('Rp')
            ->placeholder('0')
            ->live()
            ->step(1)
            ->minValue(0)
            ->formatStateUsing(function ($state) {
                if (!$state) return '';

                // Remove any existing formatting
                $clean = preg_replace('/[^\d]/', '', $state);

                // Add thousand separators
                return number_format((float)$clean, 0, '', '.');
            })
            ->dehydrateStateUsing(function ($state) {
                if (!$state) return 0;

                // Clean and convert to float
                $clean = preg_replace('/[^\d]/', '', $state);
                return (float)$clean;
            })
            ->extraAttributes([
                'x-data' => '{
                    formatInput() {
                        let value = this.$el.value;
                        if (!value) return;

                        // Remove non-digits
                        let clean = value.replace(/[^\d]/g, "");

                        // Format with thousand separators
                        let formatted = clean.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                        this.$el.value = formatted;
                    }
                }',
                'x-on:input' => 'formatInput()',
                'x-on:paste' => '$nextTick(() => formatInput())',
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*'
            ]);
    }
}
