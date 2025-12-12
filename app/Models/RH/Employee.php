<?php

namespace App\Models\RH;

use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use App\Models\Fleets\FleetAssignment;
use App\Models\User;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable; // Import the Notifiable trait

class Employee extends Model
{
    use HasFactory, BelongsToCompany, Notifiable; // Add Notifiable trait

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'employee_team');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function fleetAssignments(): MorphMany
    {
        return $this->morphMany(FleetAssignment::class, 'assignable');
    }

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'exit_date' => 'date',
            'hourly_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Helper : Nom complet
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->user->email; // Assuming each employee has a linked user with an email
    }
}
