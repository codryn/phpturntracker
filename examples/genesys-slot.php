<?php

declare(strict_types=1);

/**
 * Genesys / Star Wars FFG Slot-Based Initiative Example
 *
 * Demonstrates:
 * - Slot-based initiative system
 * - PC and NPC slots determined by rolls
 * - Players choose which character fills each PC slot
 * - Flexible turn order within slot types
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// Create Genesys slot-based timeline profile
// Slots are determined by initiative rolls:
// - Success symbols determine number of slots
// - Advantage/Triumph determine PC vs NPC slots
$profile = new TimelineProfile(
    type: TurnOrderType::SLOT,
    slotConfiguration: [
        ['type' => 'PC', 'initiative' => 3],   // Best PC roll: 3 successes
        ['type' => 'NPC', 'initiative' => 2],  // Best NPC roll: 2 successes
        ['type' => 'PC', 'initiative' => 2],   // Second PC roll: 2 successes
        ['type' => 'NPC', 'initiative' => 2],  // Second NPC roll: 2 successes
        ['type' => 'PC', 'initiative' => 1],   // Third PC roll: 1 success
        ['type' => 'NPC', 'initiative' => 1],  // Third NPC roll: 1 success
    ]
);

// Create encounter
$encounter = new Encounter($profile);

// Add player characters
$encounter->addActor(new Actor(
    id: 'jedi',
    name: 'Jedi Knight',
    initiative: 0,  // Initiative not used in slot-based
    attributes: ['type' => 'PC']
));

$encounter->addActor(new Actor(
    id: 'smuggler',
    name: 'Smuggler',
    initiative: 0,
    attributes: ['type' => 'PC']
));

$encounter->addActor(new Actor(
    id: 'mechanic',
    name: 'Mechanic',
    initiative: 0,
    attributes: ['type' => 'PC']
));

// Add NPCs
$encounter->addActor(new Actor(
    id: 'stormtrooper1',
    name: 'Stormtrooper Squad Leader',
    initiative: 0,
    attributes: ['type' => 'NPC']
));

$encounter->addActor(new Actor(
    id: 'stormtrooper2',
    name: 'Stormtrooper',
    initiative: 0,
    attributes: ['type' => 'NPC']
));

$encounter->addActor(new Actor(
    id: 'officer',
    name: 'Imperial Officer',
    initiative: 0,
    attributes: ['type' => 'NPC']
));

// Start combat!
$encounter->start();

echo "=== Genesys Slot-Based Combat ===\n\n";
echo "Initiative slots (determined by dice rolls):\n";
foreach ($profile->getSlotConfiguration() as $index => $slot) {
    echo "  " . ($index + 1) . ". {$slot['type']} slot (initiative: {$slot['initiative']})\n";
}
echo "\n";

echo "Players choose which PC fills each PC slot\n";
echo "GM chooses which NPC fills each NPC slot\n\n";

// Simulate 1 round of combat
echo "--- ROUND 1 ---\n\n";

$slotNumber = 1;
while (!empty($encounter->getUnactedActors())) {
    $currentSlot = $encounter->getCurrentSlot();
    $current = $encounter->getCurrentActor();

    if ($current === null) {
        echo "Slot {$slotNumber} ({$currentSlot['type']}): No available actors of this type\n\n";
        $encounter->advanceTurn();
        $slotNumber++;
        continue;
    }

    echo "Slot {$slotNumber} ({$currentSlot['type']}): ";
    echo "{$current->getName()} chosen to act\n";
    echo "  - Takes their action...\n";

    $encounter->advanceTurn();
    $slotNumber++;
    echo "\n";
}

echo "=== Round Complete ===\n";
echo "All slots filled, round resets for next round of combat\n";
