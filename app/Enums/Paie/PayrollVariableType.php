<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasLabel;

enum PayrollVariableType: string implements HasLabel
{
    case StandardHour = 'std_hour';   // Heures normales
    case Overtime25 = 'overtime_25';  // Heures sup 25%
    case Overtime50 = 'overtime_50';  // Heures sup 50%
    case NightHour = 'night_hour';    // Heures de nuit
    case SundayHour = 'sunday_hour';  // Heures dimanche
    case Absence = 'absence';         // Absences (à déduire)
    case Bonus = 'bonus';             // Primes (Panier, Zone, Salissure)
    case ExpenseRefund = 'expense';   // Remboursement Note de frais
    case MealVoucher = 'meal_voucher'; // Titres restaurant
    case Transport = 'transport';     // Indemnité transport

    public function getLabel(): ?string
    {
        return match ($this) {
            self::StandardHour => 'Heures Normales',
            self::Overtime25 => 'Heures Sup 25%',
            self::Overtime50 => 'Heures Sup 50%',
            self::NightHour => 'Heures de Nuit',
            self::SundayHour => 'Heures Dimanche',
            self::Absence => 'Absence',
            self::Bonus => 'Prime / Indemnité',
            self::ExpenseRefund => 'Remboursement Frais',
            self::MealVoucher => 'Titres Restaurant',
            self::Transport => 'Indemnité Transport',
        };
    }
}
