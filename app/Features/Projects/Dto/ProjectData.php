<?php

declare(strict_types=1);

namespace App\Features\Projects\Dto;

/**
 * Input data for creating or renaming a project.
 */
final readonly class ProjectData
{
    public function __construct(
        public string $name,
    ) {}
}
