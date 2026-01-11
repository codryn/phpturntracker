<?php

declare(strict_types=1);

/**
 * Dynamic Actor Management Example
 *
 * Demonstrates:
 * - Adding reinforcements mid-combat
 * - Removing defeated actors
 * - Changing initiative (buffs/debuffs)
 * - Delaying actions
 * - Maintaining correct turn order through changes
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create standard D&D profile
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);

// Create encounter
$encounter = new Encounter($profile);

// Initial combatants
$encounter->addActor(new Actor('fighter', 'Fighter', 18));
$encounter->addActor(new Actor('wizard', 'Wizard', 15));
$encounter->addActor(new Actor('goblin1', 'Goblin 1', 12));
$encounter->addActor(new Actor('goblin2', 'Goblin 2', 10));

// Start combat!
$encounter->start();

echo "=== Dynamic Actor Management Demo ===\n\n";
echo "--- ROUND 1 ---\n\n";

// Turn 1: Fighter
$current = $encounter->getCurrentActor();
echo "Turn 1: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Fighter attacks Goblin 1 and defeats it!\n";

// Remove defeated enemy
$encounter->removeActor('goblin1');
echo "  - Goblin 1 removed from combat\n";

$encounter->advanceTurn();
echo "\n";

// Turn 2: Wizard
$current = $encounter->getCurrentActor();
echo "Turn 2: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Wizard casts Haste on Fighter!\n";

// Buff changes initiative
$encounter->changeInitiative('fighter', 25);
echo "  - Fighter's initiative increased to 25\n";
echo "  - Fighter won't get extra turn this round (already acted)\n";

$encounter->advanceTurn();
echo "\n";

// Turn 3: Goblin 2
$current = $encounter->getCurrentActor();
echo "Turn 3: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Goblin 2 calls for reinforcements!\n";

// Add reinforcement mid-combat
$encounter->addActor(new Actor('hobgoblin', 'Hobgoblin', 20));
echo "  - Hobgoblin arrives (Init 20)\n";
echo "  - Hobgoblin won't act until next round\n";

$encounter->advanceTurn();
echo "\n";

echo "Round 1 complete!\n\n";

// Round 2
echo "--- ROUND 2 ---\n\n";

// Turn 1: Fighter (now at Init 25 due to Haste)
$current = $encounter->getCurrentActor();
echo "Turn 1: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Fighter goes first due to Haste spell\n";
echo "  - Fighter delays action to coordinate with Wizard\n";

// Delay to later initiative
$encounter->delayActor('fighter', 16);
echo "  - Fighter delays to initiative 16\n";

$encounter->advanceTurn();
echo "\n";

// Turn 2: Hobgoblin (new reinforcement)
$current = $encounter->getCurrentActor();
echo "Turn 2: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Hobgoblin takes its first action\n";

$encounter->advanceTurn();
echo "\n";

// Turn 3: Fighter (delayed)
$current = $encounter->getCurrentActor();
echo "Turn 3: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Fighter acts at delayed initiative\n";
echo "  - Coordinates attack with Wizard's spell!\n";

$encounter->advanceTurn();
echo "\n";

// Turn 4: Wizard
$current = $encounter->getCurrentActor();
echo "Turn 4: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Wizard casts Fireball, defeats Hobgoblin!\n";

$encounter->removeActor('hobgoblin');
echo "  - Hobgoblin removed from combat\n";

$encounter->advanceTurn();
echo "\n";

// Turn 5: Goblin 2
$current = $encounter->getCurrentActor();
echo "Turn 5: {$current->getName()} (Init {$current->getInitiative()})\n";
echo "  - Goblin 2 flees in terror!\n";

$encounter->removeActor('goblin2');
echo "  - Goblin 2 removed from combat\n";

echo "\n=== Combat Complete ===\n";
echo "All enemies defeated or fled!\n";
