<?php

declare(strict_types=1);

namespace App\Features\Projects\Models;

use App\Features\Projects\Policies\ProjectPolicy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A project is an external application that talks to the service using its own API tokens.
 *
 * The internal auto-increment `id` never leaves the service:
 * the API exposes a project by its UUID (`public_id`).
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|Carbon|null $created_at
 * @property CarbonImmutable|Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['name'])]
#[UseFactory(ProjectFactory::class)]
#[UsePolicy(ProjectPolicy::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuids;

    /**
     * Only `public_id` is generated as a UUID; the primary key stays an integer.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ProjectToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(ProjectToken::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    protected static function booted(): void
    {
        // The slug always follows the name, renames included.
        static::saving(function (Project $project): void {
            if ($project->isDirty('name') || blank($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }
}
