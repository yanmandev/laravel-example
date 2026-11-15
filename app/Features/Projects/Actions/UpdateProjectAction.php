<?php

declare(strict_types=1);

namespace App\Features\Projects\Actions;

use App\Features\Projects\Dto\ProjectData;
use App\Features\Projects\Models\Project;

final readonly class UpdateProjectAction
{
    public function execute(Project $project, ProjectData $data): Project
    {
        $project->update(['name' => $data->name]);

        return $project;
    }
}
