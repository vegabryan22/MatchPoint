<?php

namespace Tests\Unit;

use App\Services\Groups\RoundRobinScheduleService;
use PHPUnit\Framework\TestCase;

class RoundRobinScheduleServiceTest extends TestCase
{
    public function test_even_schedule_contains_every_pair_once(): void
    {
        $schedule = (new RoundRobinScheduleService)->generate([1, 2, 3, 4]);
        $pairs = collect($schedule)->flatten(1)->map(fn (array $pair): string => min($pair).'-'.max($pair));

        $this->assertCount(3, $schedule);
        $this->assertCount(6, $pairs);
        $this->assertCount(6, $pairs->unique());
    }

    public function test_odd_schedule_uses_byes_without_creating_fake_matches(): void
    {
        $schedule = (new RoundRobinScheduleService)->generate([1, 2, 3, 4, 5]);
        $pairs = collect($schedule)->flatten(1)->map(fn (array $pair): string => min($pair).'-'.max($pair));

        $this->assertCount(5, $schedule);
        $this->assertCount(10, $pairs);
        $this->assertCount(10, $pairs->unique());
        $this->assertFalse($pairs->contains(fn (string $pair): bool => str_contains($pair, 'null')));
    }
}
