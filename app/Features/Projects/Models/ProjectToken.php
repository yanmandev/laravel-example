<?php

declare(strict_types=1);

namespace App\Features\Projects\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProjectTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * API token of a project.
 *
 * Only a SHA-256 hash is stored; the plain-text value is shown to the client once, on creation.
 * A fast hash (rather than bcrypt) is enough because the token is a long random string,
 * and it lets us find the token by a unique index in a single query.
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $token_hash
 * @property bool $is_active
 * @property CarbonImmutable|Carbon|null $last_used_at
 * @property CarbonImmutable|Carbon|null $expires_at
 * @property CarbonImmutable|Carbon|null $created_at
 * @property CarbonImmutable|Carbon|null $updated_at
 * @property-read Project $project
 */
#[UseFactory(ProjectTokenFactory::class)]
#[Hidden(['token_hash'])]
class ProjectToken extends Model
{
    /** @use HasFactory<ProjectTokenFactory> */
    use HasFactory;

    private const int PLAIN_TOKEN_LENGTH = 48;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function generatePlainToken(): string
    {
        return Str::random(self::PLAIN_TOKEN_LENGTH);
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function findByPlainToken(string $plainToken): ?self
    {
        return self::query()
            ->with('project')
            ->where('token_hash', self::hash($plainToken))
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
