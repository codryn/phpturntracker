<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit\State;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\State\ActorState;
use Codryn\PHPTurnTracker\State\EncounterSnapshot;
use Codryn\PHPTurnTracker\State\EncounterState;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

class EncounterSnapshotTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $actors = [
            'actor1' => new Actor('actor1', 'Fighter', 18),
            'actor2' => new Actor('actor2', 'Wizard', 15),
        ];
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: false, currentInitiative: 18),
            'actor2' => new ActorState('actor2', hasActed: true, currentInitiative: 15),
        ];
        $encounterState = new EncounterState();
        $encounterState->setActive(true);
        $encounterState->setCurrentRound(2);
        $encounterState->setCurrentActorId('actor1');

        $snapshot = new EncounterSnapshot($profile, $actors, $actorStates, $encounterState);

        $this->assertSame($profile, $snapshot->getProfile());
        $this->assertSame($actors, $snapshot->getActors());
        $this->assertSame($actorStates, $snapshot->getActorStates());
        $this->assertSame($encounterState, $snapshot->getEncounterState());
    }

    public function testJsonSerialize(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 30
        );
        $actors = [
            'actor1' => new Actor('actor1', 'Fighter', 18, ['dexterity' => 16]),
        ];
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: false, currentInitiative: 18),
        ];
        $encounterState = new EncounterState();
        $encounterState->setActive(true);
        $encounterState->setCurrentRound(1);
        $encounterState->setCurrentActorId('actor1');

        $snapshot = new EncounterSnapshot($profile, $actors, $actorStates, $encounterState);
        $data = $snapshot->jsonSerialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('actors', $data);
        $this->assertArrayHasKey('actorStates', $data);
        $this->assertArrayHasKey('encounterState', $data);

        $this->assertSame(TurnOrderType::ROUND_INDIVIDUAL, $data['profile']['type']);
        $this->assertSame(1, $data['profile']['minInitiative']);
        $this->assertSame(30, $data['profile']['maxInitiative']);

        $this->assertArrayHasKey('actor1', $data['actors']);
        $this->assertSame('Fighter', $data['actors']['actor1']['name']);
        $this->assertSame(18, $data['actors']['actor1']['initiative']);
        $this->assertSame(['dexterity' => 16], $data['actors']['actor1']['attributes']);

        $this->assertTrue($data['encounterState']['isActive']);
        $this->assertSame(1, $data['encounterState']['currentRound']);
        $this->assertSame('actor1', $data['encounterState']['currentActorId']);
    }

    public function testToJsonAndFromJsonString(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $actors = [
            'actor1' => new Actor('actor1', 'Fighter', 18),
            'actor2' => new Actor('actor2', 'Wizard', 15),
        ];
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: false, currentInitiative: 18),
            'actor2' => new ActorState('actor2', hasActed: true, currentInitiative: 15),
        ];
        $encounterState = new EncounterState();
        $encounterState->setActive(true);
        $encounterState->setCurrentRound(2);
        $encounterState->setCurrentActorId('actor1');

        $original = new EncounterSnapshot($profile, $actors, $actorStates, $encounterState);
        $json = $original->toJson();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);

        $restored = EncounterSnapshot::fromJsonString($json);

        $this->assertEquals($original->getProfile()->getType(), $restored->getProfile()->getType());
        $this->assertCount(2, $restored->getActors());
        $this->assertCount(2, $restored->getActorStates());
        $this->assertTrue($restored->getEncounterState()->isActive());
        $this->assertSame(2, $restored->getEncounterState()->getCurrentRound());
        $this->assertSame('actor1', $restored->getEncounterState()->getCurrentActorId());
    }

    public function testFromJsonWithPassBasedProfile(): void
    {
        $data = [
            'profile' => [
                'type' => TurnOrderType::PASS,
                'minInitiative' => 1,
                'maxInitiative' => 40,
                'tieBreakerAttribute' => null,
                'passesPerRound' => 4,
                'decayEnabled' => true,
                'decayAmount' => 10,
                'slotConfiguration' => null,
                'allowRepeatPopcorn' => false,
            ],
            'actors' => [
                'sam' => [
                    'id' => 'sam',
                    'name' => 'Street Samurai',
                    'initiative' => 28,
                    'attributes' => [],
                ],
            ],
            'actorStates' => [
                'sam' => [
                    'actorId' => 'sam',
                    'hasActed' => false,
                    'passesRemaining' => 3,
                    'currentInitiative' => 28,
                    'addedInRound' => null,
                ],
            ],
            'encounterState' => [
                'isActive' => true,
                'currentRound' => 1,
                'currentPass' => 1,
                'currentActorId' => 'sam',
                'previousActorId' => null,
            ],
        ];

        $snapshot = EncounterSnapshot::fromJson($data);

        $this->assertSame(TurnOrderType::PASS, $snapshot->getProfile()->getType());
        $this->assertTrue($snapshot->getProfile()->isDecayEnabled());
        $this->assertSame(10, $snapshot->getProfile()->getDecayAmount());
        $this->assertSame(4, $snapshot->getProfile()->getPassesPerRound());

        $actors = $snapshot->getActors();
        $this->assertCount(1, $actors);
        $this->assertArrayHasKey('sam', $actors);
        $this->assertSame('Street Samurai', $actors['sam']->getName());

        $actorStates = $snapshot->getActorStates();
        $this->assertCount(1, $actorStates);
        $this->assertSame(3, $actorStates['sam']->getPassesRemaining());

        $encounterState = $snapshot->getEncounterState();
        $this->assertTrue($encounterState->isActive());
        $this->assertSame(1, $encounterState->getCurrentPass());
    }

    public function testFromJsonWithInactiveEncounter(): void
    {
        $data = [
            'profile' => [
                'type' => TurnOrderType::ROUND_INDIVIDUAL,
                'minInitiative' => 1,
                'maxInitiative' => 30,
                'tieBreakerAttribute' => null,
                'passesPerRound' => null,
                'decayEnabled' => false,
                'decayAmount' => 0,
                'slotConfiguration' => null,
                'allowRepeatPopcorn' => false,
            ],
            'actors' => [],
            'actorStates' => [],
            'encounterState' => [
                'isActive' => false,
                'currentRound' => 0,
                'currentPass' => null,
                'currentActorId' => null,
                'previousActorId' => null,
            ],
        ];

        $snapshot = EncounterSnapshot::fromJson($data);

        $this->assertFalse($snapshot->getEncounterState()->isActive());
        $this->assertSame(0, $snapshot->getEncounterState()->getCurrentRound());
        $this->assertEmpty($snapshot->getActors());
        $this->assertEmpty($snapshot->getActorStates());
    }

    public function testFromJsonThrowsExceptionForMissingProfile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required fields');

        EncounterSnapshot::fromJson([
            'actors' => [],
            'actorStates' => [],
            'encounterState' => [],
        ]);
    }

    public function testFromJsonThrowsExceptionForMissingActors(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required fields');

        EncounterSnapshot::fromJson([
            'profile' => ['type' => TurnOrderType::ROUND_INDIVIDUAL],
            'actorStates' => [],
            'encounterState' => [],
        ]);
    }

    public function testFromJsonStringThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        EncounterSnapshot::fromJsonString('not valid json{');
    }

    public function testJsonSerializeWithAllProfileTypes(): void
    {
        $types = [
            TurnOrderType::ROUND_INDIVIDUAL,
            TurnOrderType::ROUND_SIDE,
            TurnOrderType::PASS,
            TurnOrderType::SLOT,
            TurnOrderType::POPCORN,
        ];

        foreach ($types as $type) {
            $profile = new TimelineProfile($type);
            $snapshot = new EncounterSnapshot(
                $profile,
                [],
                [],
                new EncounterState()
            );

            $json = $snapshot->toJson();
            $restored = EncounterSnapshot::fromJsonString($json);

            $this->assertSame($type, $restored->getProfile()->getType());
        }
    }

    public function testJsonSerializePreservesActorAttributes(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $actors = [
            'actor1' => new Actor('actor1', 'Fighter', 18, [
                'dexterity' => 16,
                'side' => 'heroes',
                'level' => 5,
            ]),
        ];
        $actorStates = [
            'actor1' => new ActorState('actor1', hasActed: false, currentInitiative: 18),
        ];
        $encounterState = new EncounterState();

        $snapshot = new EncounterSnapshot($profile, $actors, $actorStates, $encounterState);
        $json = $snapshot->toJson();
        $restored = EncounterSnapshot::fromJsonString($json);

        $restoredActors = $restored->getActors();
        $this->assertArrayHasKey('actor1', $restoredActors);
        $this->assertSame(16, $restoredActors['actor1']->getAttribute('dexterity'));
        $this->assertSame('heroes', $restoredActors['actor1']->getAttribute('side'));
        $this->assertSame(5, $restoredActors['actor1']->getAttribute('level'));
    }

    public function testJsonSerializePreservesActorStateDetails(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $actors = ['actor1' => new Actor('actor1', 'Fighter', 18)];
        $actorStates = [
            'actor1' => new ActorState(
                actorId: 'actor1',
                hasActed: true,
                passesRemaining: 2,
                currentInitiative: 15,
                addedInRound: 3
            ),
        ];
        $encounterState = new EncounterState();

        $snapshot = new EncounterSnapshot($profile, $actors, $actorStates, $encounterState);
        $json = $snapshot->toJson();
        $restored = EncounterSnapshot::fromJsonString($json);

        $restoredStates = $restored->getActorStates();
        $state = $restoredStates['actor1'];
        $this->assertTrue($state->hasActed());
        $this->assertSame(2, $state->getPassesRemaining());
        $this->assertSame(15, $state->getCurrentInitiative());
        $this->assertSame(3, $state->getAddedInRound());
    }
}
