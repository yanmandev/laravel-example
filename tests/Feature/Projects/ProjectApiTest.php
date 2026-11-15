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

final class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function guest_cannot_access_projects(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/projects')->assertUnauthorized();
    }

    #[Test]
    public function it_lists_only_own_projects_with_pagination(): void
    {
        Project::factory()->count(3)->for($this->user)->create();
        Project::factory()->create(); // someone else's

        $this->getJson('/api/projects?perPage=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2);
    }

    #[Test]
    public function it_rejects_too_large_page_size(): void
    {
        $this->getJson('/api/projects?perPage=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('perPage');
    }

    #[Test]
    public function it_shows_own_project(): void
    {
        $project = Project::factory()->for($this->user)->create(['name' => 'Billing']);

        $this->getJson("/api/projects/{$project->public_id}")
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $project->public_id,
                'name' => 'Billing',
                'slug' => 'billing',
            ]])
            ->assertJsonMissingPath('data.user_id');
    }

    #[Test]
    public function foreign_project_looks_like_missing_one(): void
    {
        $foreign = Project::factory()->create();

        $this->getJson("/api/projects/{$foreign->public_id}")->assertNotFound();
        $this->putJson("/api/projects/{$foreign->public_id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/projects/{$foreign->public_id}")->assertNotFound();

        $this->assertModelExists($foreign);
    }

    #[Test]
    public function it_creates_a_project(): void
    {
        $response = $this->postJson('/api/projects', ['name' => '  My Shop  '])
            ->assertCreated()
            ->assertJsonPath('data.name', 'My Shop')
            ->assertJsonPath('data.slug', 'my-shop');

        $this->assertDatabaseHas('projects', [
            'public_id' => $response->json('data.id'),
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_validates_project_name(): void
    {
        $this->postJson('/api/projects', ['name' => 'ab'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_limits_projects_per_user(): void
    {
        config(['projects.max_per_user' => 2]);
        Project::factory()->count(2)->for($this->user)->create();

        $this->postJson('/api/projects', ['name' => 'One more'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame(2, $this->user->projects()->count());
    }

    #[Test]
    public function it_renames_a_project_and_refreshes_slug(): void
    {
        $project = Project::factory()->for($this->user)->create(['name' => 'Old name']);

        $this->putJson("/api/projects/{$project->public_id}", ['name' => 'New name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.slug', 'new-name');
    }

    #[Test]
    public function it_deletes_a_project_with_its_tokens(): void
    {
        $project = Project::factory()->for($this->user)->create();
        $token = ProjectToken::factory()->for($project)->create();

        $this->deleteJson("/api/projects/{$project->public_id}")->assertNoContent();

        $this->assertModelMissing($project);
        $this->assertModelMissing($token);
    }
}
