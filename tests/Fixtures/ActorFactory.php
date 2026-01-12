<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Fixtures;

use Codryn\PHPTurnTracker\Actor;

/**
 * Factory for creating test Actor instances.
 */
class ActorFactory
{
    /**
     * Create a basic actor with minimal configuration.
     *
     * @param string $id Actor ID (default: 'actor1')
     * @param string $name Actor name (default: 'Test Actor')
     * @param int $initiative Initiative score (default: 10)
     * @param array<string, mixed> $attributes Optional attributes
     * @return Actor
     */
    public static function create(
        string $id = 'actor1',
        string $name = 'Test Actor',
        int $initiative = 10,
        array $attributes = []
    ): Actor {
        return new Actor($id, $name, $initiative, $attributes);
    }

    /**
     * Create multiple actors with sequential IDs and varying initiatives.
     *
     * @param int $count Number of actors to create
     * @param int $baseInitiative Starting initiative (default: 10)
     * @return Actor[]
     */
    public static function createMultiple(int $count, int $baseInitiative = 10): array
    {
        $actors = [];
        for ($i = 1; $i <= $count; $i++) {
            $actors[] = new Actor(
                id: "actor{$i}",
                name: "Actor {$i}",
                initiative: $baseInitiative + ($i - 1)
            );
        }
        return $actors;
    }

    /**
     * Create a party of D&D-style adventurers.
     *
     * @return Actor[]
     */
    public static function createDndParty(): array
    {
        return [
            new Actor('fighter', 'Thorin the Fighter', 15, ['dexterity' => 14]),
            new Actor('wizard', 'Gandalf the Wizard', 12, ['dexterity' => 16]),
            new Actor('rogue', 'Bilbo the Rogue', 18, ['dexterity' => 18]),
            new Actor('cleric', 'Frodo the Cleric', 10, ['dexterity' => 12]),
        ];
    }

    /**
     * Create actors with tied initiatives for tie-breaker testing.
     *
     * @return Actor[]
     */
    public static function createTiedActors(): array
    {
        return [
            new Actor('actor1', 'Actor 1', 15, ['dexterity' => 14]),
            new Actor('actor2', 'Actor 2', 15, ['dexterity' => 16]),
            new Actor('actor3', 'Actor 3', 15, ['dexterity' => 12]),
        ];
    }

    /**
     * Create actors for side-based combat testing.
     *
     * @return Actor[]
     */
    public static function createSideBasedActors(): array
    {
        return [
            new Actor('pc1', 'Fighter', 15, ['side' => 'heroes']),
            new Actor('pc2', 'Wizard', 12, ['side' => 'heroes']),
            new Actor('npc1', 'Orc', 8, ['side' => 'monsters']),
            new Actor('npc2', 'Goblin', 10, ['side' => 'monsters']),
        ];
    }

    /**
     * Create actors for pass-based (Shadowrun-style) testing.
     *
     * @return Actor[]
     */
    public static function createPassBasedActors(): array
    {
        return [
            new Actor('runner1', 'Fast Runner', 30, ['passes' => 4]),
            new Actor('runner2', 'Average Runner', 20, ['passes' => 3]),
            new Actor('runner3', 'Slow Runner', 15, ['passes' => 2]),
            new Actor('runner4', 'Slow Runner', 10, ['passes' => 1]),
        ];
    }

    /**
     * Create actors with specific initiatives for slot-based testing.
     *
     * @return Actor[]
     */
    public static function createSlotBasedActors(): array
    {
        return [
            new Actor('actor1', 'Fast Actor', 5),
            new Actor('actor2', 'Medium Actor', 3),
            new Actor('actor3', 'Slow Actor', 1),
        ];
    }

    /**
     * Create actors with extreme initiative values.
     *
     * @return Actor[]
     */
    public static function createExtremeInitiativeActors(): array
    {
        return [
            new Actor('very_fast', 'Lightning', 100),
            new Actor('fast', 'Cheetah', 50),
            new Actor('slow', 'Snail', -10),
            new Actor('very_slow', 'Statue', -50),
        ];
    }
}
