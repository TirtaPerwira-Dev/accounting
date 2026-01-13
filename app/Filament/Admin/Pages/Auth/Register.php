<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Notifications\Notification;

class Register extends BaseRegister
{
    protected function getRedirectUrl(): string
    {
        // Redirect to login page after successful registration
        return '/admin/login';
    }

    protected function afterRegister(): void
    {
        // Show notification after registration
        Notification::make()
            ->title('Registrasi Berhasil')
            ->body('Akun Anda telah dibuat. Silakan tunggu verifikasi dari Super Admin untuk dapat mengakses sistem.')
            ->success()
            ->send();
    }
}
