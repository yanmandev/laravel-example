<?php

declare(strict_types=1);

namespace App\Features\Projects\Http\Middleware;

use App\Features\Projects\Models\Project;
use App\Features\Projects\Models\ProjectToken;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates machine-to-machine requests by a project token: `Authorization: Bearer <token>`.
 *
 * On success puts the project and the token into request attributes,
 * see {@see self::project()}.
 */
final readonly class AuthenticateProjectToken
{
    private const string PROJECT_ATTRIBUTE = 'project';

    private const string TOKEN_ATTRIBUTE = 'projectToken';

    public function __construct(
        #[Config('projects.tokens.last_used_throttle_seconds')] private int $lastUsedThrottleSeconds,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (blank($plainToken)) {
            return $this->unauthorized('Project token is missing.');
        }

        $token = ProjectToken::findByPlainToken($plainToken);

        // Don't tell "not found" apart from "deactivated": less information for brute force.
        if ($token === null || ! $token->is_active) {
            return $this->unauthorized('Invalid project token.');
        }

        if ($token->isExpired()) {
            return $this->unauthorized('Project token has expired.');
        }

        $this->touchLastUsed($token);

        $request->attributes->set(self::PROJECT_ATTRIBUTE, $token->project);
        $request->attributes->set(self::TOKEN_ATTRIBUTE, $token);

        return $next($request);
    }

    /**
     * The project of the current request. Available only behind this middleware.
     */
    public static function project(Request $request): Project
    {
        $project = $request->attributes->get(self::PROJECT_ATTRIBUTE);

        if (! $project instanceof Project) {
            throw new LogicException(self::class.' middleware is not applied to this route.');
        }

        return $project;
    }

    private function touchLastUsed(ProjectToken $token): void
    {
        if ($token->last_used_at?->gt(now()->subSeconds($this->lastUsedThrottleSeconds))) {
            return;
        }

        // A technical field: the token wasn't "edited", so `updated_at` stays untouched.
        ProjectToken::withoutTimestamps(
            fn () => $token->forceFill(['last_used_at' => now()])->saveQuietly(),
        );
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse(
            ['message' => $message],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
