<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Controllers;

use App\Features\Projects\Actions\CreateProjectTokenAction;
use App\Features\Projects\Actions\UpdateProjectTokenAction;
use App\Features\Projects\Http\Requests\StoreProjectTokenRequest;
use App\Features\Projects\Http\Requests\UpdateProjectTokenRequest;
use App\Features\Projects\Http\Resources\ProjectTokenResource;
use App\Features\Projects\Models\Project;
use App\Features\Projects\Models\ProjectToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * API tokens of a project.
 *
 * Routes use scoped bindings: `{token}` is resolved only among `$project->tokens()`,
 * so a token of another project yields 404 without any manual checks.
 */
final class ProjectTokenController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        Gate::authorize('manageTokens', $project);

        return ProjectTokenResource::collection(
            $project->tokens()->latest('id')->get(),
        );
    }

    public function store(
        StoreProjectTokenRequest $request,
        Project $project,
        CreateProjectTokenAction $createToken,
    ): JsonResponse {
        $newToken = $createToken->execute($project, $request->toData());

        // The plain-text token is returned exactly once — it can't be recovered later.
        return ProjectTokenResource::make($newToken->token)
            ->additional(['meta' => ['plainTextToken' => $newToken->plainTextToken]])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateProjectTokenRequest $request,
        Project $project,
        ProjectToken $token,
        UpdateProjectTokenAction $updateToken,
    ): ProjectTokenResource {
        return ProjectTokenResource::make(
            $updateToken->execute($token, $request->toData()),
        );
    }

    public function destroy(Project $project, ProjectToken $token): Response
    {
        Gate::authorize('manageTokens', $project);

        $token->delete();

        return response()->noContent();
    }
}
