<?php

declare(strict_types=1);

namespace App\Features\Projects\Policies;

use App\Features\Projects\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Only the owner may access a project.
 *
 * A foreign project yields 404 rather than 403: the client must not be able
 * to tell whether a project with the given UUID exists at all.
 */
final class ProjectPolicy
{
    public function view(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function update(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function delete(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    public function manageTokens(User $user, Project $project): Response
    {
        return $this->ownerOnly($user, $project);
    }

    private function ownerOnly(User $user, Project $project): Response
    {
        return $project->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
