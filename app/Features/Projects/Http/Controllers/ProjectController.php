<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Controllers;

use App\Features\Projects\Actions\CreateProjectAction;
use App\Features\Projects\Actions\ListProjectsAction;
use App\Features\Projects\Actions\UpdateProjectAction;
use App\Features\Projects\Http\Requests\ListProjectsRequest;
use App\Features\Projects\Http\Requests\StoreProjectRequest;
use App\Features\Projects\Http\Requests\UpdateProjectRequest;
use App\Features\Projects\Http\Resources\ProjectResource;
use App\Features\Projects\Models\Project;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Management of the authenticated user's projects.
 *
 * The controller is thin: it maps HTTP to DTO, calls an action and wraps the result
 * in a resource. Validation and authorization live in FormRequests and the policy,
 * business rules in actions.
 */
final class ProjectController extends Controller
{
    public function index(
        ListProjectsRequest $request,
        #[CurrentUser] User $user,
        ListProjectsAction $listProjects,
    ): AnonymousResourceCollection {
        return ProjectResource::collection(
            $listProjects->execute($user, $request->perPage()),
        );
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return ProjectResource::make($project);
    }

    public function store(
        StoreProjectRequest $request,
        #[CurrentUser] User $user,
        CreateProjectAction $createProject,
    ): JsonResponse {
        $project = $createProject->execute($user, $request->toData());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateProjectRequest $request,
        Project $project,
        UpdateProjectAction $updateProject,
    ): ProjectResource {
        return ProjectResource::make(
            $updateProject->execute($project, $request->toData()),
        );
    }

    public function destroy(Project $project): Response
    {
        Gate::authorize('delete', $project);

        // Tokens are removed by the FK `ON DELETE CASCADE`.
        $project->delete();

        return response()->noContent();
    }
}
