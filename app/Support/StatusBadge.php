<?php

namespace App\Support;

class StatusBadge
{
    public static function className(?string $status): string
    {
        return self::classes($status)['badge'];
    }

    public static function backgroundClass(?string $status): string
    {
        return self::classes($status)['background'];
    }

    public static function borderClass(?string $status): string
    {
        return self::classes($status)['border'];
    }

    public static function label(?string $status): string
    {
        return match ($status ?? 'pending') {
            'pending' => 'Εκκρεμεί',
            'draft' => 'Πρόχειρο',
            'submitted' => 'Αναμονή ελέγχου',
            'processing' => 'Σε επεξεργασία',
            'approved' => 'Εγκρίθηκε',
            'completed' => 'Ολοκληρώθηκε',
            'active' => 'Ενεργό',
            'rejected' => 'Απορρίφθηκε',
            'failed' => 'Απέτυχε',
            'cancelled' => 'Ακυρώθηκε',
            'not_required' => 'Δεν απαιτείται',
            default => $status,
        };
    }

    private static function classes(?string $status): array
    {
        return match ($status ?? 'pending') {
            'approved', 'completed' => ['badge' => 'text-bg-success', 'background' => 'bg-success-subtle', 'border' => 'border-success'],
            'active' => [
                'badge' => 'text-bg-primary',
                'background' => 'bg-primary-subtle',
                'border' => 'border-primary'],
            'submitted' => [
                'badge' => 'text-bg-warning',
                'background' => 'bg-warning-subtle',
                'border' => 'border-warning',
            ],
            'processing' => ['badge' => 'text-bg-info', 'background' => 'bg-info-subtle', 'border' => 'border-info'],
            'rejected', 'failed', 'cancelled' => ['badge' => 'text-bg-danger', 'background' => 'bg-danger-subtle', 'border' => 'border-danger'],
            'not_required' => [
                'badge' => 'text-bg-info',
                'background' => 'bg-info-subtle',
                'border' => 'border-info',
            ],

            default => ['badge' => 'text-bg-dark', 'background' => 'bg-secondary-subtle', 'border' => 'border-secondary'],

        };
    }
}
