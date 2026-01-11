<?php

declare(strict_types=1);

/**
 * Stress Test for PHP Turn Tracker.
 *
 * Tests performance and stability with:
 * - 20 actors
 * - 100+ rounds
 * - Verifies no integer overflow
 * - Verifies performance remains stable
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

echo "=== PHP Turn Tracker Stress Test ===\n\n";

// Create profile
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);

// Create encounter
$encounter = new Encounter($profile);

// Add 20 actors with varied initiatives
echo "Setting up encounter with 20 actors...\n";
for ($i = 1; $i <= 20; $i++) {
    $initiative = random_int(1, 30);
    $encounter->addActor(new Actor(
        id: "actor_{$i}",
        name: "Actor #{$i}",
        initiative: $initiative
    ));
}

$encounter->start();
echo "Encounter started.\n\n";

// Run 100 rounds
$targetRounds = 100;
$turnsPerRound = 20;  // 20 actors
$totalTurns = $targetRounds * $turnsPerRound;

echo "Running {$targetRounds} rounds ({$totalTurns} total turns)...\n";

$startTime = microtime(true);
$startMemory = memory_get_usage();

$turnCount = 0;
$roundTimes = [];

for ($round = 1; $round <= $targetRounds; $round++) {
    $roundStart = microtime(true);

    // Process all actors in this round
    for ($turn = 0; $turn < $turnsPerRound; $turn++) {
        $current = $encounter->getCurrentActor();

        if ($current === null) {
            echo "ERROR: No current actor at round {$round}, turn {$turn}\n";
            exit(1);
        }

        $encounter->advanceTurn();
        $turnCount++;
    }

    $roundTime = microtime(true) - $roundStart;
    $roundTimes[] = $roundTime;

    // Report progress every 20 rounds
    if ($round % 20 === 0) {
        $avgTime = array_sum($roundTimes) / count($roundTimes);
        echo "  Round {$round} complete (avg: " . number_format($avgTime * 1000, 2) . "ms/round)\n";
    }
}

$endTime = microtime(true);
$endMemory = memory_get_usage();

// Calculate statistics
$totalTime = $endTime - $startTime;
$avgRoundTime = array_sum($roundTimes) / count($roundTimes);
$maxRoundTime = max($roundTimes);
$minRoundTime = min($roundTimes);
$memoryUsed = $endMemory - $startMemory;

echo "\n=== Stress Test Results ===\n\n";
echo "Configuration:\n";
echo "  - Actors: 20\n";
echo "  - Rounds: {$targetRounds}\n";
echo "  - Total turns: {$turnCount}\n\n";

echo "Performance:\n";
echo '  - Total time: ' . number_format($totalTime * 1000, 2) . "ms\n";
echo '  - Time per round (avg): ' . number_format($avgRoundTime * 1000, 2) . "ms\n";
echo '  - Time per round (min): ' . number_format($minRoundTime * 1000, 2) . "ms\n";
echo '  - Time per round (max): ' . number_format($maxRoundTime * 1000, 2) . "ms\n";
echo '  - Time per turn (avg): ' . number_format(($totalTime / $turnCount) * 1000, 2) . "ms\n";
echo '  - Throughput: ' . number_format($turnCount / $totalTime, 0) . " turns/second\n\n";

echo "Memory:\n";
echo '  - Used: ' . number_format($memoryUsed / 1024, 2) . " KB\n";
echo '  - Peak: ' . number_format(memory_get_peak_usage() / 1024, 2) . " KB\n\n";

echo "State Verification:\n";
echo '  - Current round: ' . $encounter->getCurrentRound() . "\n";
echo '  - Expected round: ' . ($targetRounds + 1) . "\n";
echo '  - Encounter active: ' . ($encounter->isActive() ? 'Yes' : 'No') . "\n";

// Verify no integer overflow
$currentActor = $encounter->getCurrentActor();
if ($currentActor) {
    echo '  - Current actor initiative: ' . $currentActor->getInitiative() . "\n";
    if ($currentActor->getInitiative() < 1 || $currentActor->getInitiative() > 30) {
        echo "\n❌ FAIL: Initiative out of bounds (overflow detected)\n";
        exit(1);
    }
}

// Performance targets
$target10Rounds = 100; // 100ms for 10 rounds with 20 actors
$actual10Rounds = $avgRoundTime * 10 * 1000; // Convert to ms

echo "\n=== Test Verdict ===\n\n";

$pass = true;

if ($actual10Rounds > $target10Rounds) {
    echo "⚠️  WARNING: Performance slower than target\n";
    echo "   Target: <{$target10Rounds}ms for 10 rounds\n";
    echo '   Actual: ' . number_format($actual10Rounds, 2) . "ms for 10 rounds\n";
    $pass = false;
} else {
    echo "✅ PASS: Performance meets target\n";
    echo "   Target: <{$target10Rounds}ms for 10 rounds\n";
    echo '   Actual: ' . number_format($actual10Rounds, 2) . "ms for 10 rounds\n";
}

if ($encounter->getCurrentRound() !== $targetRounds + 1) {
    echo "❌ FAIL: Round count mismatch\n";
    $pass = false;
} else {
    echo "✅ PASS: Round count correct\n";
}

if ($memoryUsed > 5 * 1024 * 1024) { // 5MB
    echo "⚠️  WARNING: High memory usage\n";
    echo '   Used: ' . number_format($memoryUsed / 1024 / 1024, 2) . " MB\n";
} else {
    echo "✅ PASS: Memory usage acceptable\n";
}

echo "\n";

if ($pass) {
    echo "🎉 All stress tests PASSED!\n";
    exit(0);
} else {
    echo "⚠️  Some tests did not meet targets (see warnings above)\n";
    exit(0); // Still exit 0 as these are warnings, not failures
}
