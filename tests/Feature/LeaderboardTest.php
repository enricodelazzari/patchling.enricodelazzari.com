<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function stats(array $overrides = []): array
    {
        return [
            'outcome' => 'completed',
            'months' => 36,
            'revenue' => 300,
            'features' => 9,
            'squashed' => 12,
            'breaches' => 0,
            'health' => 80,
            'happy' => 70,
            'debt' => 30,
            'age' => 2.5,
            'name' => null,
            ...$overrides,
        ];
    }

    public function test_a_game_can_be_started(): void
    {
        $this->postJson('/api/games')->assertCreated()->assertJsonStructure(['id']);

        $this->assertDatabaseCount('games', 1);
    }

    public function test_the_server_computes_the_score_and_verdict(): void
    {
        $game = Game::factory()->played()->create();

        $response = $this->postJson("/api/games/{$game->id}/result", $this->stats(['score' => 999999]));

        // 300×10 + 36×10 + (80+70)×5 + 500 healthy bonus
        $response->assertCreated()
            ->assertJsonPath('data.score', 4610)
            ->assertJsonPath('data.verdict', 'healthy')
            ->assertJsonPath('data.rank', 1)
            ->assertJsonStructure(['claim_token', 'data' => ['share_url']]);
    }

    public function test_attacks_and_an_early_crash_lower_the_score(): void
    {
        $game = Game::factory()->played()->create();

        $this->postJson("/api/games/{$game->id}/result", $this->stats([
            'outcome' => 'crash', 'months' => 20, 'revenue' => 100, 'health' => 0, 'breaches' => 2,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.verdict', 'crashed')
            ->assertJsonPath('data.score', 100 * 10 + 20 * 10 - 2 * 150);
    }

    public function test_a_game_can_only_have_one_result(): void
    {
        $game = Game::factory()->played()->create();

        $this->postJson("/api/games/{$game->id}/result", $this->stats())->assertCreated();
        $this->postJson("/api/games/{$game->id}/result", $this->stats())->assertConflict();
    }

    public function test_a_game_cannot_finish_faster_than_real_time(): void
    {
        $game = Game::factory()->create(['created_at' => now()->subSeconds(30)]);

        $this->postJson("/api/games/{$game->id}/result", $this->stats())->assertJsonValidationErrors('months');
    }

    public function test_a_completed_game_lasts_every_month(): void
    {
        $game = Game::factory()->played()->create();

        $this->postJson("/api/games/{$game->id}/result", $this->stats(['months' => 12]))->assertJsonValidationErrors('months');
    }

    public function test_implausible_stats_are_rejected(): void
    {
        $game = Game::factory()->played()->create();

        $this->postJson("/api/games/{$game->id}/result", $this->stats(['revenue' => 50000, 'health' => 140]))
            ->assertJsonValidationErrors(['revenue', 'health']);
    }

    public function test_names_are_validated(): void
    {
        $game = Game::factory()->played()->create();

        $this->postJson("/api/games/{$game->id}/result", $this->stats(['name' => '<script>']))
            ->assertJsonValidationErrors('name');
    }

    public function test_the_player_can_name_an_anonymous_result_once(): void
    {
        $result = Result::factory()->anonymous()->create();

        $this->patchJson("/api/results/{$result->id}", ['name' => 'Ada', 'claim_token' => 'secret'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ada');

        $this->patchJson("/api/results/{$result->id}", ['name' => 'Grace', 'claim_token' => 'secret'])
            ->assertConflict();
    }

    public function test_only_the_claim_token_holder_can_name_a_result(): void
    {
        $result = Result::factory()->anonymous()->create();

        $this->patchJson("/api/results/{$result->id}", ['name' => 'Mallory', 'claim_token' => 'guess'])
            ->assertForbidden();

        $this->assertNull($result->fresh()->name);
    }

    public function test_the_leaderboard_lists_named_results_by_score(): void
    {
        Result::factory()->create(['name' => 'Low', 'score' => 100]);
        Result::factory()->create(['name' => 'High', 'score' => 900]);
        Result::factory()->anonymous()->create(['score' => 5000]);

        $this->getJson('/api/leaderboard')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'High')
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.1.name', 'Low')
            ->assertJsonPath('data.1.rank', 2)
            ->assertJsonMissingPath('data.0.claim_token');
    }

    public function test_the_shared_page_has_link_preview_tags(): void
    {
        $result = Result::factory()->create(['name' => 'Ada', 'score' => 4321]);

        $this->get("/r/{$result->id}")
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.route('results.image', ['result' => $result, 'v' => $result->updated_at->timestamp]), false)
            ->assertSee('Survived · 4,321 points in Patchling');
    }

    public function test_the_share_image_is_a_png(): void
    {
        $result = Result::factory()->create();

        $response = $this->get("/r/{$result->id}/card.png")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertSame([1200, 630], array_slice(getimagesizefromstring($response->getContent()), 0, 2));
    }

    public function test_abandoned_games_are_pruned(): void
    {
        $abandoned = Game::factory()->create(['created_at' => now()->subDays(2)]);
        $finished = Game::factory()->create(['created_at' => now()->subDays(2)]);
        Result::factory()->for($finished)->create();

        $this->artisan('model:prune', ['--model' => Game::class]);

        $this->assertModelMissing($abandoned);
        $this->assertModelExists($finished);
    }
}
