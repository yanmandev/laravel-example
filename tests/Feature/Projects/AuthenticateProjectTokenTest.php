<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Features\Projects\Models\ProjectToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthenticateProjectTokenTest extends TestCase
{
    use RefreshDatabase;

    private const string PLAIN_TOKEN = 'test-plain-token-value';

    #[Test]
    public function it_requires_a_token(): void
    {
        $this->getJson('/api/project')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertJsonPath('message', 'Project token is missing.');
    }

    #[Test]
    public function it_rejects_unknown_token(): void
    {
        $this->withToken('unknown')
            ->getJson('/api/project')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid project token.');
    }

    #[Test]
    public function it_rejects_inactive_token(): void
    {
        ProjectToken::factory()->withPlainToken(self::PLAIN_TOKEN)->inactive()->create();

        $this->withToken(self::PLAIN_TOKEN)
            ->getJson('/api/project')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid project token.');
    }

    #[Test]
    public function it_rejects_expired_token(): void
    {
        ProjectToken::factory()->withPlainToken(self::PLAIN_TOKEN)->expired()->create();

        $this->withToken(self::PLAIN_TOKEN)
            ->getJson('/api/project')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Project token has expired.');
    }

    #[Test]
    public function it_authenticates_project_and_records_usage(): void
    {
        $this->freezeSecond();
        $token = ProjectToken::factory()->withPlainToken(self::PLAIN_TOKEN)->create();

        $this->withToken(self::PLAIN_TOKEN)
            ->getJson('/api/project')
            ->assertOk()
            ->assertJsonPath('data.id', $token->project->public_id);

        $token->refresh();
        $this->assertEquals(now(), $token->last_used_at);
    }

    #[Test]
    public function usage_is_recorded_at_most_once_per_throttle_interval(): void
    {
        config(['projects.tokens.last_used_throttle_seconds' => 60]);
        $this->freezeSecond();
        $token = ProjectToken::factory()->withPlainToken(self::PLAIN_TOKEN)->create();
        $firstUse = now()->toImmutable();

        $this->withToken(self::PLAIN_TOKEN)->getJson('/api/project')->assertOk();

        $this->travel(30)->seconds();
        $this->withToken(self::PLAIN_TOKEN)->getJson('/api/project')->assertOk();
        $this->assertEquals($firstUse, $token->refresh()->last_used_at);

        $this->travel(31)->seconds();
        $this->withToken(self::PLAIN_TOKEN)->getJson('/api/project')->assertOk();
        $this->assertEquals(now(), $token->refresh()->last_used_at);
    }
}
