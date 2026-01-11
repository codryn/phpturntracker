<?php

declare(strict_types=1);

/**
 * OSR Side-Based Initiative Example (B/X D&D style)
 *
 * Demonstrates:
 * - Side-based initiative (entire teams act together)
 * - Party vs Monsters turn order
 * - All members of a side can act before other side
 * - Round-based progression
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create OSR side-based timeline profile
// - Entire sides act together
// - Initiative rolled per side, not per character
// - Simple and fast resolution
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_SIDE
);

// Create encounter
$encounter = new Encounter($profile);

// Add adventuring party
$encounter->addActor(new Actor(
    id: 'fighter',
    name: 'Fighter',
    initiative: 4,  // Party rolled a 4
    attributes: ['side' => 'adventurers']
));

$encounter->addActor(new Actor(
    id: 'cleric',
    name: 'Cleric',
    initiative: 4,  // Same initiative as side
    attributes: ['side' => 'adventurers']
));

$encounter->addActor(new Actor(
    id: 'thief',
    name: 'Thief',
    initiative: 4,
    attributes: ['side' => 'adventurers']
));

$encounter->addActor(new Actor(
    id: 'magicuser',
    name: 'Magic-User',
    initiative: 4,
    attributes: ['side' => 'adventurers']
));

// Add monsters
$encounter->addActor(new Actor(
    id: 'orc1',
    name: 'Orc Warrior 1',
    initiative: 2,  // Monsters rolled a 2
    attributes: ['side' => 'monsters']
));

$encounter->addActor(new Actor(
    id: 'orc2',
    name: 'Orc Warrior 2',
    initiative: 2,
    attributes: ['side' => 'monsters']
));

$encounter->addActor(new Actor(
    id: 'orc_chief',
    name: 'Orc Chieftain',
    initiative: 2,
    attributes: ['side' => 'monsters']
));

// Start combat!
$encounter->start();

echo "=== OSR Side-Based Combat (B/X D&D) ===\n\n";
echo "Initiative: Adventurers (4) vs Monsters (2)\n";
echo "Adventurers won initiative!\n\n";

// Simulate 2 rounds
for ($round = 1; $round <= 2; $round++) {
    echo "--- ROUND {$round} ---\n\n";

    echo "Adventurers' Turn:\n";
    $adventurers = array_filter(
        $encounter->getUnactedActors(),
        fn($a) => $a->getAttributes()['side'] === 'adventurers'
    );

    foreach ($adventurers as $actor) {
        echo "  - {$actor->getName()} takes their action\n";
        // In real game, resolve action here
        $encounter->advanceTurn();
    }

    echo "\nMonsters' Turn:\n";
    $monsters = $encounter->getUnactedActors();

    foreach ($monsters as $actor) {
        echo "  - {$actor->getName()} takes their action\n";
        // In real game, resolve action here
        $encounter->advanceTurn();
    }

    echo "\n";
}

echo "=== Combat Complete ===\n";
