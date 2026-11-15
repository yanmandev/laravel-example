<?php

declare(strict_types=1);

namespace App\Features\Projects\Dto;

use App\Features\Projects\Models\ProjectToken;
use SensitiveParameter;

/**
 * Result of token creation: the model plus the plain-text value,
 * which exists only here and is never persisted.
 */
final readonly class NewProjectToken
{
    public function __construct(
        public ProjectToken $token,
        #[SensitiveParameter] public string $plainTextToken,
    ) {}
}
