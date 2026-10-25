<?php

namespace Tests\Feature;

use Tests\TestCase;

class GamePageTest extends TestCase
{
    public function test_the_game_is_served_at_the_root(): void
    {
        $this->get('/')->assertOk()->assertSee('Switch on the site');
    }
}
