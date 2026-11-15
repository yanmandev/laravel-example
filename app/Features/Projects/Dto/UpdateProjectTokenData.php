<?php

declare(strict_types=1);

namespace App\Features\Projects\Dto;

/**
 * Partial update: `null` means "the field was not sent, leave it as is".
 */
final readonly class UpdateProjectTokenData
{
    public function __construct(
        public ?string $name = null,
        public ?bool $isActive = null,
    ) {}
}
