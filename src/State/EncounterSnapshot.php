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
        if (!is_array($profileData)) {
            throw new \InvalidArgumentException('Invalid profile data: expected array');
        }

        // Validate turn order type
        if (!isset($profileData['type']) || !is_string($profileData['type']) || !\Codryn\PhpTurnTracker\TurnOrderType::isValid($profileData['type'])) {
            throw new \InvalidArgumentException('Invalid turn order type in snapshot data');
        }
        $profile = new TimelineProfile(
            type: $profileData['type'],
            minInitiative: isset($profileData['minInitiative']) && is_int($profileData['minInitiative']) ? $profileData['minInitiative'] : 1,
            maxInitiative: isset($profileData['maxInitiative']) && is_int($profileData['maxInitiative']) ? $profileData['maxInitiative'] : 30,
            tieBreakerAttribute: isset($profileData['tieBreakerAttribute']) && is_string($profileData['tieBreakerAttribute']) ? $profileData['tieBreakerAttribute'] : null,
            passesPerRound: isset($profileData['passesPerRound']) && is_int($profileData['passesPerRound']) ? $profileData['passesPerRound'] : null,
            decayEnabled: isset($profileData['decayEnabled']) && is_bool($profileData['decayEnabled']) ? $profileData['decayEnabled'] : false,
            decayAmount: isset($profileData['decayAmount']) && is_int($profileData['decayAmount']) ? $profileData['decayAmount'] : 0,
            slotConfiguration: isset($profileData['slotConfiguration']) && is_array($profileData['slotConfiguration']) ? $profileData['slotConfiguration'] : null,
            allowRepeatPopcorn: isset($profileData['allowRepeatPopcorn']) && is_bool($profileData['allowRepeatPopcorn']) ? $profileData['allowRepeatPopcorn'] : false
        );

        // Reconstruct actors
        $actors = [];
        $actorsData = $data['actors'];
        if (!is_array($actorsData)) {
            throw new \InvalidArgumentException('Invalid actors data: expected array');
        }
        foreach ($actorsData as $id => $actorData) {
            if (!is_array($actorData)) {
                throw new \InvalidArgumentException('Invalid actor data: expected array');
            }
            if (!isset($actorData['id']) || !is_string($actorData['id'])) {
                throw new \InvalidArgumentException('Invalid actor id: expected string');
            }
            if (!isset($actorData['name']) || !is_string($actorData['name'])) {
                throw new \InvalidArgumentException('Invalid actor name: expected string');
            }
            if (!isset($actorData['initiative']) || !is_int($actorData['initiative'])) {
                throw new \InvalidArgumentException('Invalid actor initiative: expected int');
            }
            $actors[$id] = new Actor(
                $actorData['id'],
                $actorData['name'],
                $actorData['initiative'],
                isset($actorData['attributes']) && is_array($actorData['attributes']) ? $actorData['attributes'] : []
            );
        }

        // Reconstruct actor states
        $actorStates = [];
        $actorStatesData = $data['actorStates'];
        if (!is_array($actorStatesData)) {
            throw new \InvalidArgumentException('Invalid actor states data: expected array');
        }
        foreach ($actorStatesData as $id => $stateData) {
            if (!is_array($stateData)) {
                throw new \InvalidArgumentException('Invalid actor state data: expected array');
            }
            if (!isset($stateData['actorId']) || !is_string($stateData['actorId'])) {
                throw new \InvalidArgumentException('Invalid actor state actorId: expected string');
            }
            $actorStates[$id] = new ActorState(
                $stateData['actorId'],
                isset($stateData['hasActed']) && is_bool($stateData['hasActed']) ? $stateData['hasActed'] : false,
                isset($stateData['passesRemaining']) && is_int($stateData['passesRemaining']) ? $stateData['passesRemaining'] : 0,
                isset($stateData['currentInitiative']) && is_int($stateData['currentInitiative']) ? $stateData['currentInitiative'] : 0,
                isset($stateData['addedInRound']) && is_int($stateData['addedInRound']) ? $stateData['addedInRound'] : null
            );
        }

        // Reconstruct encounter state
        $encounterStateData = $data['encounterState'];
        if (!is_array($encounterStateData)) {
            throw new \InvalidArgumentException('Invalid encounter state data: expected array');
        }
        $encounterState = new EncounterState();
        $encounterState->setActive(isset($encounterStateData['isActive']) && is_bool($encounterStateData['isActive']) ? $encounterStateData['isActive'] : false);
        $encounterState->setCurrentRound(isset($encounterStateData['currentRound']) && is_int($encounterStateData['currentRound']) ? $encounterStateData['currentRound'] : 0);
        $encounterState->setCurrentPass(isset($encounterStateData['currentPass']) && is_int($encounterStateData['currentPass']) ? $encounterStateData['currentPass'] : null);
        $encounterState->setCurrentActorId(isset($encounterStateData['currentActorId']) && is_string($encounterStateData['currentActorId']) ? $encounterStateData['currentActorId'] : null);
        $encounterState->setPreviousActorId(isset($encounterStateData['previousActorId']) && is_string($encounterStateData['previousActorId']) ? $encounterStateData['previousActorId'] : null);

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
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON: expected object/array');
        }
        return self::fromJson($data);
    }
}
