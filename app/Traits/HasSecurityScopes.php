<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasSecurityScopes
{
    /**
     * Scope to filter results based on user permissions
     * Override this in each model as needed
     */
    public function scopeAllowedForUser(Builder $query, $user = null): Builder
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return $query->whereRaw('1 = 0'); // Return no results for unauthenticated users
        }

        // Super admin can see everything
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $query;
        }

        // Default behavior - override in specific models
        return $query;
    }

    /**
     * Scope to filter by user's company if applicable
     */
    public function scopeForUserCompany(Builder $query, $user = null): Builder
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user || !isset($user->company_id)) {
            return $query->whereRaw('1 = 0');
        }

        // If the model has company_id column, filter by it
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'company_id')) {
            return $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Check if user can access this model instance
     */
    public function isAccessibleByUser($user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        // Company-specific access
        if (isset($this->company_id) && isset($user->company_id)) {
            return $this->company_id === $user->company_id;
        }

        return true; // Default allow - override in specific models
    }

    /**
     * Sanitize input data before mass assignment
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Only allow fillable attributes
            if (in_array($key, $this->getFillable())) {
                // Basic sanitization
                if (is_string($value)) {
                    $value = trim($value);
                    $value = strip_tags($value);
                }

                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Safe fill method with input sanitization
     */
    public function safeFill(array $attributes): static
    {
        return $this->fill($this->sanitizeInput($attributes));
    }

    /**
     * Generate secure random code
     */
    public static function generateSecureCode(string $prefix = '', int $length = 10): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $prefix . $code;
    }

    /**
     * Validate model data before saving
     */
    public function validateBeforeSave(): array
    {
        $errors = [];

        // Override this method in specific models to add validation rules
        return $errors;
    }

    /**
     * Safe save method with validation
     */
    public function safeSave(array $options = []): bool
    {
        $errors = $this->validateBeforeSave();

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        return $this->save($options);
    }
}
