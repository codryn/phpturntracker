<?php

declare(strict_types=1);

/**
 * D&D 5e Combat Example
 *
 * Demonstrates:
 * - Individual initiative with round-based tracking
 * - Dexterity tie-breaker for initiative ties
 * - Basic turn progression through multiple rounds
 * - Actor state tracking (acted/unacted)
 *
 * Copyright Notice:
 * Dungeons & Dragons and D&D are trademarks of Wizards of the Coast LLC.
 * The mechanics of the D&D 5th Edition initiative system are used in this example
 * solely for non-commercial purposes to assist game masters in tracking combat
 * initiative. This library is not affiliated with, endorsed by, or sponsored by
 * Wizards of the Coast LLC. See GAME_SYSTEMS_COPYRIGHT.md for full details.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create D&D 5e timeline profile
// - Individual initiative (each character acts separately)
// - Initiative range: 1-30
// - Dexterity as tie-breaker
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30,
    tieBreakerAttribute: 'dexterity'
);

// Create encounter
$encounter = new Encounter($profile);

// Add party members
$encounter->addActor(new Actor(
    id: 'fighter',
    name: 'Bruenor Battlehammer',
    initiative: 15,
    attributes: ['dexterity' => 14]
));

$encounter->addActor(new Actor(
    id: 'wizard',
    name: 'Elminster',
    initiative: 18,
    attributes: ['dexterity' => 16]
));

$encounter->addActor(new Actor(
    id: 'rogue',
    name: 'Artemis Entreri',
    initiative: 22,
    attributes: ['dexterity' => 20]
));

// Add enemies
$encounter->addActor(new Actor(
    id: 'goblin1',
    name: 'Goblin Archer',
    initiative: 18,
    attributes: ['dexterity' => 14]  // Same initiative as wizard, but lower dex
));

$encounter->addActor(new Actor(
    id: 'goblin2',
    name: 'Goblin Warrior',
    initiative: 12,
    attributes: ['dexterity' => 12]
));

// Start combat!
$encounter->start();

echo "=== D&D 5e Combat Encounter ===\n\n";

// Simulate 2 rounds of combat
for ($round = 1; $round <= 2; $round++) {
    echo "--- ROUND {$round} ---\n\n";

    // Get all actors for this round
    $unacted = $encounter->getUnactedActors();

    foreach ($unacted as $actor) {
        $current = $encounter->getCurrentActor();

        echo "Turn: {$current->getName()} (Initiative: {$current->getInitiative()})\n";
        echo "  - Takes their action...\n";

        // Show who has acted and who's waiting
        $acted = $encounter->getActedActors();
        $waiting = $encounter->getUnactedActors();

        echo "  - Acted this round: " . count($acted) . "\n";
        echo "  - Waiting: " . count($waiting) - 1 . "\n";  // -1 for current actor

        $encounter->advanceTurn();
        echo "\n";
    }
}

echo "=== Combat Complete ===\n";
