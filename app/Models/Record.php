<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Record extends Model
{
    use HasFactory, MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'issue_id',
        'type',
        'payload',
        'fingerprint',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Query Scopes
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLast24Hours($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    public function scopeForPeriod($query, ?string $period, ?string $from = null, ?string $to = null)
    {
        if ($period === 'custom' && $from && $to) {
            return $query->whereBetween('created_at', [$from, $to]);
        }

        return match ($period) {
            '1h' => $query->where('created_at', '>=', now()->subHour()),
            '24h' => $query->where('created_at', '>=', now()->subDay()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '14d' => $query->where('created_at', '>=', now()->subDays(14)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            default => $query,
        };
    }

    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('payload->status', 'failed')
                ->orWhere('payload->status_code', '>=', 400);
        });
    }

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('projects')
                ->whereColumn('projects.id', 'records.project_id')
                ->where('projects.retention_days', '>', 0)
                ->whereRaw('records.created_at < DATE_SUB(NOW(), INTERVAL projects.retention_days DAY)');
        });
    }
}
