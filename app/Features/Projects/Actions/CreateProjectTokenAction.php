<?php

declare(strict_types=1);

namespace App\Features\Projects\Actions;

use App\Features\Projects\Dto\CreateProjectTokenData;
use App\Features\Projects\Dto\NewProjectToken;
use App\Features\Projects\Exceptions\ProjectDomainException;
use App\Features\Projects\Models\Project;
use App\Features\Projects\Models\ProjectToken;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;

final readonly class CreateProjectTokenAction
{
    public function __construct(
        private ConnectionInterface $db,
        #[Config('projects.tokens.max_per_project')] private int $maxTokensPerProject,
        #[Config('projects.tokens.ttl_days')] private int $ttlDays,
    ) {}

    /**
     * @throws ProjectDomainException when the project has reached the tokens limit
     */
    public function execute(Project $project, CreateProjectTokenData $data): NewProjectToken
    {
        $plainTextToken = ProjectToken::generatePlainToken();

        $token = $this->db->transaction(function () use ($project, $data, $plainTextToken): ProjectToken {
            // Lock the parent row so concurrent requests can't exceed the limit.
            Project::query()->whereKey($project->id)->lockForUpdate()->first();

            if ($project->tokens()->count() >= $this->maxTokensPerProject) {
                throw ProjectDomainException::tokenLimitExceeded($this->maxTokensPerProject);
            }

            $token = new ProjectToken;
            $token->project()->associate($project);
            $token->name = $data->name;
            $token->token_hash = ProjectToken::hash($plainTextToken);
            $token->is_active = true;
            $token->expires_at = Date::now()->addDays($this->ttlDays);
            $token->save();

            return $token;
        });

        return new NewProjectToken($token, $plainTextToken);
    }
}
