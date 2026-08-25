<?php

namespace App\Models;

use App\Support\OutletContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $properties = null,
    ): static {
        /** @var User|null $user */
        $user = Auth::user();
        $outletId = null;

        if ($user instanceof User) {
            $outletId = $user->isAdmin()
                ? OutletContext::currentOutletId()
                : $user->outlet_id;
        }

        // Fallback: subject outlet if user has none
        if (! $outletId && $subject && isset($subject->outlet_id)) {
            $outletId = $subject->outlet_id;
        }

        return static::create([
            'user_id' => $user?->getKey(),
            'outlet_id' => $outletId,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
