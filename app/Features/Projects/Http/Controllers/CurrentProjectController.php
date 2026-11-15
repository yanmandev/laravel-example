<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Controllers;

use App\Features\Projects\Http\Middleware\AuthenticateProjectToken;
use App\Features\Projects\Http\Resources\ProjectResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * The project the request was authenticated with (by a project token, not a user session).
 *
 * The simplest consumer of {@see AuthenticateProjectToken}: lets an integration
 * check that its token works.
 */
final class CurrentProjectController extends Controller
{
    public function __invoke(Request $request): ProjectResource
    {
        return ProjectResource::make(AuthenticateProjectToken::project($request));
    }
}
