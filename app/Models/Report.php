<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'debate_id',
        'reporter_id',
        'reporter_type',
        'issue_type',
        'title',
        'description',
        'status',
        'admin_response',
        'admin_action',
        'admin_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function isReporter(User $user): bool
    {
        return $this->reporter_id === $user->id;
    }

    public function markAsUnderReview(int $adminId): bool
    {
        return $this->update([
            'status' => 'under_review',
            'admin_id' => $adminId,
        ]);
    }

    public function resolve(int $adminId, string $response = null, string $action = 'none'): bool
    {
        return $this->update([
            'status' => 'resolved',
            'admin_id' => $adminId,
            'admin_response' => $response,
            'admin_action' => $action,
            'resolved_at' => now(),
        ]);
    }

    public function dismiss(int $adminId, string $response = null): bool
    {
        return $this->update([
            'status' => 'dismissed',
            'admin_id' => $adminId,
            'admin_response' => $response,
            'resolved_at' => now(),
        ]);
    }
}