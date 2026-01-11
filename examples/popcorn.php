<?php

declare(strict_types=1);

/**
 * Popcorn Initiative Example (Marvel Heroic RPG style)
 *
 * Demonstrates:
 * - Narrative turn order
 * - Current actor designates next actor
 * - Tracking who has/hasn't acted
 * - Automatic last-actor selection
 *
 * Copyright Notice:
 * Marvel Heroic Roleplaying is a trademark of Margaret Weis Productions and Marvel
 * Characters, Inc. The mechanics of the popcorn initiative system are used in this example
 * solely for non-commercial purposes to assist game masters in tracking combat initiative.
 * This library is not affiliated with, endorsed by, or sponsored by Margaret Weis Productions
 * or Marvel Characters, Inc. See GAME_SYSTEMS_COPYRIGHT.md for full details.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create popcorn initiative timeline profile
// - No fixed turn order
// - Current actor chooses who goes next
// - Can't choose someone who already acted (unless allowRepeatPopcorn=true)
$profile = new TimelineProfile(
    type: TurnOrderType::POPCORN,
    allowRepeatPopcorn: false  // Standard popcorn rules
);

// Create encounter
$encounter = new Encounter($profile);

// Add heroes
$encounter->addActor(new Actor(
    id: 'spider',
    name: 'Spider-Man',
    initiative: 0  // Initiative not used in popcorn
));

$encounter->addActor(new Actor(
    id: 'iron',
    name: 'Iron Man',
    initiative: 0
));

$encounter->addActor(new Actor(
    id: 'cap',
    name: 'Captain America',
    initiative: 0
));

// Add villains
$encounter->addActor(new Actor(
    id: 'doom',
    name: 'Doctor Doom',
    initiative: 0
));

$encounter->addActor(new Actor(
    id: 'hydra1',
    name: 'Hydra Agent',
    initiative: 0
));

// Start combat!
$encounter->start();

echo "=== Popcorn Initiative Combat (Marvel Heroic) ===\n\n";
echo "Turn order is narrative-driven:\n";
echo "  - Current actor designates who goes next\n";
echo "  - Can't designate someone who already acted\n";
echo "  - Last remaining actor goes automatically\n\n";

echo "--- ROUND 1 ---\n\n";

// First actor (random or player choice in real game)
$current = $encounter->getCurrentActor();
echo "First actor: {$current->getName()}\n";
echo "  - Takes action...\n";
echo "  - Designates Iron Man to go next\n\n";

// Designate next actors
$encounter->designateNext('iron');

$current = $encounter->getCurrentActor();
echo "Current: {$current->getName()}\n";
echo "  - Takes action...\n";
echo "  - Designates Doctor Doom to go next\n\n";

$encounter->designateNext('doom');

$current = $encounter->getCurrentActor();
echo "Current: {$current->getName()}\n";
echo "  - Takes action...\n";
echo "  - Designates Captain America to go next\n\n";

$encounter->designateNext('cap');

$current = $encounter->getCurrentActor();
echo "Current: {$current->getName()}\n";
echo "  - Takes action...\n";

// Check remaining actors
$unacted = $encounter->getUnactedActors();
echo "  - Only 1 actor remaining: {$unacted[0]->getName()}\n";
echo "  - That actor automatically goes next\n\n";

$encounter->advanceTurn();

$current = $encounter->getCurrentActor();
echo "Current (automatic): {$current->getName()}\n";
echo "  - Takes action...\n\n";

echo "All actors have acted, round complete!\n";
echo "Next round begins with new designations\n";

// Advance to next round
$encounter->advanceTurn();

echo "\n--- ROUND 2 ---\n\n";
$current = $encounter->getCurrentActor();
echo "First actor: {$current->getName()}\n";
echo "Turn order starts fresh...\n";
