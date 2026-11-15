<?php

declare(strict_types=1);

namespace App\Features\Projects\Dto;

final readonly class CreateProjectTokenData
{
    public function __construct(
        public string $name,
    ) {}
}
