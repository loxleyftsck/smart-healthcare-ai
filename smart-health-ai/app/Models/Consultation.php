<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToTenant;

class Consultation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'patient_id',
        'session_id',
        'message',
        'intent',
        'response',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function triageLogs(): HasMany
    {
        return $this->hasMany(TriageLog::class);
    }
}
