<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;

/**
 * Pass-based turn order (Shadowrun style).
 *
 * Actors with higher initiative get multiple actions (passes) per round.
 * Number of passes is determined by initiative score (e.g., initiative/10 in SR4).
 * Optional initiative decay reduces initiative by a fixed amount each pass.
 */
class PassBased implements TurnOrderInterface
{
    private array $turnOrder = [];
    private int $passesPerRound;
    private bool $decayEnabled;
    private int $decayAmount;

    public function __construct(
        int $passesPerRound = 4,
        bool $decayEnabled = false,
        int $decayAmount = 10
    ) {
        $this->passesPerRound = $passesPerRound;
        $this->decayEnabled = $decayEnabled;
        $this->decayAmount = $decayAmount;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateInitialOrder(array $actors): array
    {
        // Sort actors by initiative (descending)
        $sortedActors = $actors;

        usort($sortedActors, function (Actor $a, Actor $b): int {
            return $b->getInitiative() <=> $a->getInitiative();
        });

        // Extract and store actor IDs in turn order
        $this->turnOrder = array_map(fn (Actor $actor) => $actor->getId(), $sortedActors);

        return $this->turnOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string {
        if (empty($this->turnOrder)) {
            $this->calculateInitialOrder($actors);
        }

        if (empty($this->turnOrder)) {
            return null;
        }

        $currentActorId = $encounterState->getCurrentActorId();
        $currentPass = $encounterState->getCurrentPass() ?? 1;

        // If no current actor, return first eligible actor for this pass
        if ($currentActorId === null) {
            return $this->getFirstEligibleActor($actorStates, $currentPass);
        }

        // Find current actor's position
        $currentIndex = array_search($currentActorId, $this->turnOrder, true);

        if ($currentIndex === false) {
            return $this->getFirstEligibleActor($actorStates, $currentPass);
        }

        // Get next actor in sequence who is eligible for this pass
        $nextIndex = $currentIndex + 1;

        // Search for next eligible actor
        while ($nextIndex < count($this->turnOrder)) {
            $nextActorId = $this->turnOrder[$nextIndex];

            if ($this->isEligibleForPass($actorStates[$nextActorId], $currentPass)) {
                return $nextActorId;
            }

            $nextIndex++;
        }

        // If we've reached the end, wrap to beginning (handled by Encounter for pass/round advancement)
        return $this->getFirstEligibleActor($actorStates, $currentPass);
    }

    /**
     * Get the first actor eligible to act in the current pass.
     */
    private function getFirstEligibleActor(array $actorStates, int $currentPass): ?string
    {
        foreach ($this->turnOrder as $actorId) {
            if (isset($actorStates[$actorId]) && $this->isEligibleForPass($actorStates[$actorId], $currentPass)) {
                return $actorId;
            }
        }

        return $this->turnOrder[0] ?? null;
    }

    /**
     * Check if an actor is eligible to act in the given pass.
     *
     * Actor can act in pass N if their total passes >= N
     * Example: Actor with 2 passes can act in pass 1 and pass 2, but not pass 3
     */
    private function isEligibleForPass(ActorState $actorState, int $pass): bool
    {
        return !$actorState->hasActed() && $actorState->getPassesRemaining() >= $pass;
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void
    {
        // Recalculate entire turn order to maintain correct initiative positions
        $this->calculateInitialOrder($allActors);
    }

    /**
     * {@inheritDoc}
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void
    {
        $key = array_search($actorId, $this->turnOrder, true);

        if ($key !== false) {
            unset($this->turnOrder[$key]);
            $this->turnOrder = array_values($this->turnOrder);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function changeInitiative(
        string $actorId,
        int $newInitiative,
        array $actors,
        EncounterState $encounterState
    ): void {
        // Recalculate turn order with updated initiative
        if (isset($actors[$actorId])) {
            $this->calculateInitialOrder($actors);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool
    {
        // Round should advance when all passes are complete
        $currentPass = $encounterState->getCurrentPass() ?? 1;

        // If we're not at the last pass, don't advance round
        if ($currentPass < $this->passesPerRound) {
            return false;
        }

        // Check if all eligible actors in the last pass have acted
        foreach ($actorStates as $state) {
            if ($this->isEligibleForPass($state, $currentPass) && !$state->hasActed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvancePass(array $actorStates): bool
    {
        // Pass should advance when all eligible actors for current pass have acted
        // This is determined by Encounter based on current pass state
        // For now, return false (Encounter will handle pass logic)
        return false;
    }

    /**
     * Calculate number of passes for an actor based on initiative.
     *
     * Shadowrun 4e/5e rules: initiative / 10 (rounded up)
     * Example: 25 init = 3 passes, 18 init = 2 passes, 10 init = 1 pass
     */
    public function calculatePasses(int $initiative): int
    {
        return max(1, (int) ceil($initiative / 10.0));
    }

    /**
     * Apply initiative decay to an actor state.
     */
    public function applyDecay(ActorState $actorState): void
    {
        if ($this->decayEnabled) {
            $currentInitiative = $actorState->getCurrentInitiative();
            $newInitiative = $currentInitiative - $this->decayAmount;
            $actorState->setCurrentInitiative($newInitiative);
        }
    }
}
