<?php

declare(strict_types=1);

/**
 * Manual Quickstart Test
 * Tests all examples from quickstart.md to ensure they work correctly.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\Encounter;
use Codryn\PhpTurnTracker\TimelineProfile;
use Codryn\PhpTurnTracker\TurnOrderType;

echo "=== Quickstart Manual Test ===\n\n";

// Test 1: Basic D&D-style combat
echo "Test 1: Basic D&D-style Combat\n";
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);

$encounter = new Encounter($profile);

$encounter->addActor(new Actor('fighter', 'Conan', 18));
$encounter->addActor(new Actor('wizard', 'Gandalf', 15));
$encounter->addActor(new Actor('orc', 'Orc Warrior', 12));

$encounter->start();

$current = $encounter->getCurrentActor();
assert($current !== null, 'Current actor should not be null');
assert($current->getName() === 'Conan', 'First actor should be Conan');
echo "  ✓ Encounter starts correctly\n";

$encounter->advanceTurn();
$current = $encounter->getCurrentActor();
assert($current->getName() === 'Gandalf', 'Second actor should be Gandalf');
echo "  ✓ Turn advancement works\n";

assert($encounter->getCurrentRound() === 1, 'Should be round 1');
echo "  ✓ Round tracking works\n\n";

// Test 2: Adding reinforcements
echo "Test 2: Adding Reinforcements Mid-Combat\n";
$encounter->addActor(new Actor('troll', 'Cave Troll', 20));
$unacted = $encounter->getUnactedActors();
$foundTroll = false;
foreach ($unacted as $actor) {
    if ($actor->getId() === 'troll') {
        $foundTroll = true;
        break;
    }
}
assert($foundTroll, 'Troll should be in unacted list');
echo "  ✓ Reinforcements can be added\n\n";

// Test 3: Removing defeated enemies
echo "Test 3: Removing Defeated Enemies\n";
$encounter->removeActor('orc');
$allActors = array_merge($encounter->getActedActors(), $encounter->getUnactedActors());
$foundOrc = false;
foreach ($allActors as $actor) {
    if ($actor->getId() === 'orc') {
        $foundOrc = true;
        break;
    }
}
assert(!$foundOrc, 'Orc should be removed');
echo "  ✓ Actors can be removed\n\n";

// Test 4: Changing initiative
echo "Test 4: Changing Initiative (Buffs)\n";
$encounter->changeInitiative('fighter', 25);
$fighter = null;
foreach (array_merge($encounter->getActedActors(), $encounter->getUnactedActors()) as $actor) {
    if ($actor->getId() === 'fighter') {
        $fighter = $actor;
        break;
    }
}
assert($fighter !== null, 'Fighter should exist');
assert($fighter->getInitiative() === 25, 'Fighter should have initiative 25');
echo "  ✓ Initiative can be changed\n\n";

// Test 5: D&D 5e with tie-breaker
echo "Test 5: D&D 5e with Dexterity Tie-Breaker\n";
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30,
    tieBreakerAttribute: 'dexterity'
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('ranger', 'Legolas', 18, ['dexterity' => 16]));
$encounter->addActor(new Actor('rogue', 'Garrett', 18, ['dexterity' => 14]));
$encounter->start();

$current = $encounter->getCurrentActor();
assert($current->getName() === 'Legolas', 'Legolas should go first (higher dex)');
echo "  ✓ Tie-breaker works correctly\n\n";

// Test 6: State tracking
echo "Test 6: State Tracking\n";
$acted = $encounter->getActedActors();
$unacted = $encounter->getUnactedActors();
assert(count($acted) === 0, 'No actors should have acted yet');
assert(count($unacted) === 2, 'Two actors should be unacted');
assert($encounter->getCurrentRound() === 1, 'Should be round 1');
assert($encounter->isActive(), 'Encounter should be active');
echo "  ✓ State tracking works correctly\n\n";

echo "=== All Quickstart Tests PASSED! ===\n";
