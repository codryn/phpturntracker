<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit\TurnOrder;

use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;
use Codryn\PhpTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PhpTurnTracker\TurnOrder\RoundBasedIndividual;
use PHPUnit\Framework\TestCase;

class RoundBasedIndividualTest extends TestCase
{
    public function testCalculateInitialOrderSortsByInitiativeDescending(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Slow', 10),
            ActorFactory::create('actor2', 'Fast', 20),
            ActorFactory::create('actor3', 'Medium', 15),
        ];

        $strategy = new RoundBasedIndividual();
        $order = $strategy->calculateInitialOrder($actors);

        $this->assertSame(['actor2', 'actor3', 'actor1'], $order);
    }

    public function testCalculateInitialOrderMaintainsStableOrderForTies(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'First', 15),
            ActorFactory::create('actor2', 'Second', 15),
            ActorFactory::create('actor3', 'Third', 15),
        ];

        $strategy = new RoundBasedIndividual();
        $order = $strategy->calculateInitialOrder($actors);

        // Stable sort should maintain original order for ties
        $this->assertSame(['actor1', 'actor2', 'actor3'], $order);
    }

    public function testCalculateInitialOrderHandlesNegativeInitiative(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Negative', -5),
            ActorFactory::create('actor2', 'Zero', 0),
            ActorFactory::create('actor3', 'Positive', 10),
        ];

        $strategy = new RoundBasedIndividual();
        $order = $strategy->calculateInitialOrder($actors);

        $this->assertSame(['actor3', 'actor2', 'actor1'], $order);
    }

    public function testGetNextActorReturnsFirstWhenNoCurrentActor(): void
    {
        $actors = ActorFactory::createMultiple(3);
        $actorStates = [];
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();
        $strategy->calculateInitialOrder($actors);

        $nextId = $strategy->getNextActor($actors, $actorStates, $encounterState);

        // actor3 has highest initiative (12), so it goes first
        $this->assertSame('actor3', $nextId);
    }

    public function testGetNextActorAdvancesThroughSequence(): void
    {
        $actors = ActorFactory::createMultiple(3);
        $actorStates = [];
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();
        $strategy->calculateInitialOrder($actors);

        // Turn order is: actor3 (12), actor2 (11), actor1 (10)

        // Start with actor3
        $encounterState->setCurrentActorId('actor3');
        $nextId = $strategy->getNextActor($actors, $actorStates, $encounterState);
        $this->assertSame('actor2', $nextId);

        // Advance to actor2
        $encounterState->setCurrentActorId('actor2');
        $nextId = $strategy->getNextActor($actors, $actorStates, $encounterState);
        $this->assertSame('actor1', $nextId);

        // Advance to actor1
        $encounterState->setCurrentActorId('actor1');
        $nextId = $strategy->getNextActor($actors, $actorStates, $encounterState);
        $this->assertSame('actor3', $nextId); // Wraps to beginning
    }

    public function testGetNextActorReturnsNullWhenNoActors(): void
    {
        $actors = [];
        $actorStates = [];
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();

        $nextId = $strategy->getNextActor($actors, $actorStates, $encounterState);

        $this->assertNull($nextId);
    }

    public function testAddActorInsertsIntoTurnOrder(): void
    {
        $actors = ActorFactory::createMultiple(2);
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();
        $strategy->calculateInitialOrder($actors);

        $newActor = ActorFactory::create('actor3', 'New Actor', 15);

        // Build array of all actors including the new one (keyed by ID)
        $allActors = [];
        foreach ($actors as $actor) {
            $allActors[$actor->getId()] = $actor;
        }
        $allActors[$newActor->getId()] = $newActor;

        $strategy->addActor($newActor, $allActors, $encounterState);

        $actorStates = [];

        // Turn order is recalculated with actor3 inserted at correct position
        $encounterState->setCurrentActorId('actor1');
        $nextId = $strategy->getNextActor($allActors, $actorStates, $encounterState);
        $this->assertSame('actor3', $nextId);
    }

    public function testRemoveActorRemovesFromTurnOrder(): void
    {
        $actors = ActorFactory::createMultiple(3);
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();
        $strategy->calculateInitialOrder($actors);

        $strategy->removeActor('actor2', $encounterState);

        // Verify turn order is now actor1 -> actor3
        $encounterState->setCurrentActorId('actor1');
        $nextId = $strategy->getNextActor($actors, $actorStates = [], $encounterState);
        $this->assertSame('actor3', $nextId);
    }

    public function testChangeInitiativeRecalculatesOrder(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'First', 10),
            ActorFactory::create('actor2', 'Second', 20),
            ActorFactory::create('actor3', 'Third', 15),
        ];
        $encounterState = new EncounterState();

        $strategy = new RoundBasedIndividual();
        $order = $strategy->calculateInitialOrder($actors);

        $this->assertSame(['actor2', 'actor3', 'actor1'], $order);

        // Change actor1's initiative to 25 (should become first)
        $actors[0] = ActorFactory::create('actor1', 'First', 25);
        $actorsKeyed = array_combine(
            array_map(fn ($a) => $a->getId(), $actors),
            $actors
        );

        $strategy->changeInitiative('actor1', 25, $actorsKeyed, $encounterState);

        // Verify new order
        $encounterState->setCurrentActorId(null);
        $nextId = $strategy->getNextActor($actorsKeyed, [], $encounterState);
        $this->assertSame('actor1', $nextId);
    }

    public function testShouldAdvanceRoundReturnsTrueWhenAllActed(): void
    {
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: true),
            'actor2' => new ActorState('actor2', hasActed: true),
            'actor3' => new ActorState('actor3', hasActed: true),
        ];

        $strategy = new RoundBasedIndividual();
        $encounterState = new EncounterState();
        $encounterState->setActive(true);
        $encounterState->setCurrentRound(1);

        $this->assertTrue($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    public function testShouldAdvanceRoundReturnsFalseWhenSomeUnacted(): void
    {
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: true),
            'actor2' => new ActorState('actor2', hasActed: false),
            'actor3' => new ActorState('actor3', hasActed: true),
        ];

        $strategy = new RoundBasedIndividual();
        $encounterState = new EncounterState();
        $encounterState->setActive(true);
        $encounterState->setCurrentRound(1);

        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    public function testShouldAdvancePassReturnsFalse(): void
    {
        $strategy = new RoundBasedIndividual();

        // Pass advancement not applicable for round-based systems
        $this->assertFalse($strategy->shouldAdvancePass([]));
    }
}
