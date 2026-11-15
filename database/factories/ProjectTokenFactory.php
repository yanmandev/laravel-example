<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Features\Projects\Models\Project;
use App\Features\Projects\Models\ProjectToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectToken>
 */
final class ProjectTokenFactory extends Factory
{
    protected $model = ProjectToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'token_hash' => ProjectToken::hash(ProjectToken::generatePlainToken()),
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ];
    }

    /**
     * A token whose plain-text value is known to the test.
     */
    public function withPlainToken(string $plainToken): self
    {
        return $this->state(['token_hash' => ProjectToken::hash($plainToken)]);
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}
