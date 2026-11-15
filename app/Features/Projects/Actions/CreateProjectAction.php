<?php

declare(strict_types=1);

namespace App\Features\Projects\Actions;

use App\Features\Projects\Dto\ProjectData;
use App\Features\Projects\Exceptions\ProjectDomainException;
use App\Features\Projects\Models\Project;
use App\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\ConnectionInterface;

final readonly class CreateProjectAction
{
    public function __construct(
        private ConnectionInterface $db,
        #[Config('projects.max_per_user')] private int $maxProjectsPerUser,
    ) {}

    /**
     * @throws ProjectDomainException when the user has reached the projects limit
     */
    public function execute(User $owner, ProjectData $data): Project
    {
        return $this->db->transaction(function () use ($owner, $data): Project {
            // Serialize concurrent requests of the same user, otherwise two parallel
            // requests could both pass the "count < limit" check.
            User::query()->whereKey($owner->id)->lockForUpdate()->first();

            if ($owner->projects()->count() >= $this->maxProjectsPerUser) {
                throw ProjectDomainException::projectLimitExceeded($this->maxProjectsPerUser);
            }

            return $owner->projects()->create(['name' => $data->name]);
        });
    }
}
