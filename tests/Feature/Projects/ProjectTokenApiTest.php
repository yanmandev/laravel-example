<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Features\Projects\Models\Project;
use App\Features\Projects\Models\ProjectToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTokenApiTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->project = Project::factory()->for($user)->create();
        Sanctum::actingAs($user);
    }

    #[Test]
    public function it_lists_project_tokens_without_secrets(): void
    {
        ProjectToken::factory()->count(2)->for($this->project)->create();
        ProjectToken::factory()->create(); // token of another project

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.token_hash');
    }

    #[Test]
    public function it_creates_a_token_and_returns_plain_value_once(): void
    {
        $this->freezeTime();

        $response = $this->postJson($this->url(), ['name' => 'Production'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Production')
            ->assertJsonPath('data.isActive', true)
            ->assertJsonPath('data.expiresAt', now()->addDays(365)->toIso8601String());

        $plainToken = $response->json('meta.plainTextToken');
        $this->assertIsString($plainToken);

        // Only the hash is stored in the database.
        $this->assertDatabaseHas('project_tokens', [
            'id' => $response->json('data.id'),
            'token_hash' => ProjectToken::hash($plainToken),
        ]);
        $this->assertDatabaseMissing('project_tokens', ['token_hash' => $plainToken]);

        // The value is not exposed anywhere afterwards.
        $this->getJson($this->url())->assertDontSee($plainToken);
    }

    #[Test]
    public function it_limits_tokens_per_project(): void
    {
        config(['projects.tokens.max_per_project' => 1]);
        ProjectToken::factory()->for($this->project)->create();

        $this->postJson($this->url(), ['name' => 'Second'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_updates_name_and_status(): void
    {
        $token = ProjectToken::factory()->for($this->project)->create();

        $this->patchJson($this->url($token), ['name' => 'Renamed', 'isActive' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.isActive', false);
    }

    #[Test]
    public function partial_update_keeps_other_fields(): void
    {
        $token = ProjectToken::factory()->for($this->project)->inactive()->create(['name' => 'Staging']);

        $this->patchJson($this->url($token), ['name' => 'Staging EU'])
            ->assertOk()
            ->assertJsonPath('data.isActive', false);
    }

    #[Test]
    public function expired_token_cannot_be_activated(): void
    {
        $token = ProjectToken::factory()->for($this->project)->inactive()->expired()->create();

        $this->patchJson($this->url($token), ['isActive' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isActive');

        $this->assertFalse($token->fresh()?->is_active);
    }

    #[Test]
    public function it_deletes_a_token(): void
    {
        $token = ProjectToken::factory()->for($this->project)->create();

        $this->deleteJson($this->url($token))->assertNoContent();

        $this->assertModelMissing($token);
    }

    #[Test]
    public function token_of_another_project_is_not_reachable_through_own_project(): void
    {
        $foreignToken = ProjectToken::factory()->create();

        $this->patchJson($this->url($foreignToken), ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson($this->url($foreignToken))->assertNotFound();

        $this->assertModelExists($foreignToken);
    }

    #[Test]
    public function tokens_of_foreign_project_are_not_accessible(): void
    {
        $foreignProject = Project::factory()->create();
        $foreignToken = ProjectToken::factory()->for($foreignProject)->create();
        $base = "/api/projects/{$foreignProject->public_id}/tokens";

        $this->getJson($base)->assertNotFound();
        $this->postJson($base, ['name' => 'Backdoor'])->assertNotFound();
        $this->patchJson("{$base}/{$foreignToken->id}", ['isActive' => false])->assertNotFound();
        $this->deleteJson("{$base}/{$foreignToken->id}")->assertNotFound();

        $this->assertSame(1, $foreignProject->tokens()->count());
    }

    private function url(?ProjectToken $token = null): string
    {
        $url = "/api/projects/{$this->project->public_id}/tokens";

        return $token === null ? $url : "{$url}/{$token->id}";
    }
}
