<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\State;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\TimelineProfile;

/**
 * Immutable snapshot of complete encounter state.
 *
 * Captures all actors, their states, encounter state, and configuration.
 * Can be serialized to/from JSON for state persistence and UI synchronization.
 */
class EncounterSnapshot implements \JsonSerializable
{
    /**
     * Create a new encounter snapshot.
     *
     * @param TimelineProfile $profile Timeline configuration
     * @param array<string, Actor> $actors Actors keyed by ID
     * @param array<string, ActorState> $actorStates Actor states keyed by ID
     * @param EncounterState $encounterState Current encounter state
     */
    public function __construct(
        private TimelineProfile $profile,
        private array $actors,
        private array $actorStates,
        private EncounterState $encounterState
    ) {
    }

    /**
     * Get the timeline profile.
     */
    public function getProfile(): TimelineProfile
    {
        return $this->profile;
    }

    /**
     * Get all actors.
     *
     * @return array<string, Actor>
     */
    public function getActors(): array
    {
        return $this->actors;
    }

    /**
     * Get all actor states.
     *
     * @return array<string, ActorState>
     */
    public function getActorStates(): array
    {
        return $this->actorStates;
    }

    /**
     * Get encounter state.
     */
    public function getEncounterState(): EncounterState
    {
        return $this->encounterState;
    }

    /**
     * Serialize to JSON-compatible format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $actorsData = [];
        foreach ($this->actors as $id => $actor) {
            $actorsData[$id] = [
                'id' => $actor->getId(),
                'name' => $actor->getName(),
                'initiative' => $actor->getInitiative(),
                'attributes' => $actor->getAttributes(),
            ];
        }

        $actorStatesData = [];
        foreach ($this->actorStates as $id => $state) {
            $actorStatesData[$id] = [
                'actorId' => $state->getActorId(),
                'hasActed' => $state->hasActed(),
                'passesRemaining' => $state->getPassesRemaining(),
                'currentInitiative' => $state->getCurrentInitiative(),
                'addedInRound' => $state->getAddedInRound(),
            ];
        }

        return [
            'profile' => [
                'type' => $this->profile->getType(),
                'minInitiative' => $this->profile->getMinInitiative(),
                'maxInitiative' => $this->profile->getMaxInitiative(),
                'tieBreakerAttribute' => $this->profile->getTieBreakerAttribute(),
                'passesPerRound' => $this->profile->getPassesPerRound(),
                'decayEnabled' => $this->profile->isDecayEnabled(),
                'decayAmount' => $this->profile->getDecayAmount(),
                'slotConfiguration' => $this->profile->getSlotConfiguration(),
                'allowRepeatPopcorn' => $this->profile->allowsRepeatPopcorn(),
            ],
            'actors' => $actorsData,
            'actorStates' => $actorStatesData,
            'encounterState' => [
                'isActive' => $this->encounterState->isActive(),
                'currentRound' => $this->encounterState->getCurrentRound(),
                'currentPass' => $this->encounterState->getCurrentPass(),
                'currentActorId' => $this->encounterState->getCurrentActorId(),
                'previousActorId' => $this->encounterState->getPreviousActorId(),
            ],
        ];
    }

    /**
     * Create snapshot from JSON data.
     *
     * @param array<string, mixed> $data JSON-decoded data
     * @return self
     * @throws \InvalidArgumentException If data is invalid
     */
    public static function fromJson(array $data): self
    {
        if (!isset($data['profile'], $data['actors'], $data['actorStates'], $data['encounterState'])) {
            throw new \InvalidArgumentException('Invalid snapshot data: missing required fields');
        }

        // Reconstruct profile
        $profileData = $data['profile'];

        // Validate turn order type
        if (!isset($profileData['type']) || !\Codryn\PhpTurnTracker\TurnOrderType::isValid($profileData['type'])) {
            throw new \InvalidArgumentException('Invalid turn order type in snapshot data');
        }
        $profile = new TimelineProfile(
            type: $profileData['type'],
            minInitiative: $profileData['minInitiative'] ?? 1,
            maxInitiative: $profileData['maxInitiative'] ?? 30,
            tieBreakerAttribute: $profileData['tieBreakerAttribute'] ?? null,
            passesPerRound: $profileData['passesPerRound'] ?? null,
            decayEnabled: $profileData['decayEnabled'] ?? false,
            decayAmount: $profileData['decayAmount'] ?? 0,
            slotConfiguration: $profileData['slotConfiguration'] ?? null,
            allowRepeatPopcorn: $profileData['allowRepeatPopcorn'] ?? false
        );

        // Reconstruct actors
        $actors = [];
        foreach ($data['actors'] as $id => $actorData) {
            $actors[$id] = new Actor(
                $actorData['id'],
                $actorData['name'],
                $actorData['initiative'],
                $actorData['attributes'] ?? []
            );
        }

        // Reconstruct actor states
        $actorStates = [];
        foreach ($data['actorStates'] as $id => $stateData) {
            $actorStates[$id] = new ActorState(
                $stateData['actorId'],
                $stateData['hasActed'] ?? false,
                $stateData['passesRemaining'] ?? 0,
                $stateData['currentInitiative'] ?? 0,
                $stateData['addedInRound'] ?? null
            );
        }

        // Reconstruct encounter state
        $encounterStateData = $data['encounterState'];
        $encounterState = new EncounterState();
        $encounterState->setActive($encounterStateData['isActive'] ?? false);
        $encounterState->setCurrentRound($encounterStateData['currentRound'] ?? 0);
        $encounterState->setCurrentPass($encounterStateData['currentPass'] ?? null);
        $encounterState->setCurrentActorId($encounterStateData['currentActorId'] ?? null);
        $encounterState->setPreviousActorId($encounterStateData['previousActorId'] ?? null);

        return new self($profile, $actors, $actorStates, $encounterState);
    }

    /**
     * Serialize snapshot to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * Create snapshot from JSON string.
     *
     * @throws \JsonException If JSON is invalid
     * @throws \InvalidArgumentException If data structure is invalid
     */
    public static function fromJsonString(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromJson($data);
    }
}
