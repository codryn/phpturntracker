<?php

declare(strict_types=1);

/**
 * Shadowrun 4e Combat Example
 *
 * Demonstrates:
 * - Pass-based combat system
 * - Initiative decay (-10 per pass)
 * - Multiple actions per round for high-initiative characters
 * - Pass progression and round resets
 *
 * Copyright Notice:
 * Shadowrun is a registered trademark of The Topps Company, Inc.
 * The mechanics of the Shadowrun 4th Edition initiative system are used in this example
 * solely for non-commercial purposes to assist game masters in tracking combat
 * initiative. This library is not affiliated with, endorsed by, or sponsored by
 * The Topps Company, Inc. or Catalyst Game Labs. See GAME_SYSTEMS_COPYRIGHT.md for full details.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create Shadowrun 4e timeline profile
// - Pass-based system (up to 4 passes per round)
// - Initiative decays by 10 each pass
// - Actors act in each pass while initiative > 0
$profile = new TimelineProfile(
    type: TurnOrderType::PASS,
    passesPerRound: 4,
    decayEnabled: true,
    decayAmount: 10
);

// Create encounter
$encounter = new Encounter($profile);

// Add runners with varying initiative
// High initiative = more actions per round
$encounter->addActor(new Actor(
    id: 'sam',
    name: 'Street Samurai (wired reflexes)',
    initiative: 28  // Will act in 3 passes (28, 18, 8)
));

$encounter->addActor(new Actor(
    id: 'adept',
    name: 'Physical Adept',
    initiative: 24  // Will act in 3 passes (24, 14, 4)
));

$encounter->addActor(new Actor(
    id: 'mage',
    name: 'Combat Mage',
    initiative: 15  // Will act in 2 passes (15, 5)
));

$encounter->addActor(new Actor(
    id: 'decker',
    name: 'Decker',
    initiative: 12  // Will act in 2 passes (12, 2)
));

// Add opposition
$encounter->addActor(new Actor(
    id: 'guard1',
    name: 'Corp Guard',
    initiative: 8   // Will act in 1 pass only
));

$encounter->addActor(new Actor(
    id: 'guard2',
    name: 'Corp Guard',
    initiative: 9   // Will act in 1 pass only
));

// Start combat!
$encounter->start();

echo "=== Shadowrun 4e Combat Encounter ===\n\n";
echo "Initiative determines number of passes per round:\n";
echo "  - 10+ = 1 action\n";
echo "  - 20+ = 2 actions\n";
echo "  - 30+ = 3 actions\n\n";

// Simulate 2 rounds of combat
for ($round = 1; $round <= 2; $round++) {
    echo "--- ROUND {$round} ---\n\n";

    // Each round has up to 4 passes
    for ($pass = 1; $pass <= 4; $pass++) {
        $unacted = $encounter->getUnactedActors();

        if (empty($unacted)) {
            echo "Pass {$pass}: No actors remaining, advancing to next round\n\n";
            break;
        }

        echo "Pass {$pass}:\n";

        foreach ($unacted as $actor) {
            $current = $encounter->getCurrentActor();

            echo "  - {$current->getName()}: Init {$current->getInitiative()}, takes action\n";

            $encounter->advanceTurn();
        }

        echo "\n";
    }
}

echo "=== Combat Complete ===\n";
