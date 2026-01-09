# Phase 1: Data Model

**Feature**: RPG Turn Order Tracker Library  
**Date**: 2026-01-08  
**Status**: Complete

## Entity Definitions

### Actor

Represents a participant in an encounter (player character, NPC, monster, etc.).

**Properties**:
```php
class Actor
{
    public function __construct(
        private string $id,              // Unique identifier (consumer-provided)
        private string $name,            // Display name
        private int $initiative,         // Initiative score
        private array $attributes = []   // Optional attributes (dexterity, side, etc.)
    ) {}
    
    public function getId(): string
    public function getName(): string
    public function getInitiative(): int
    public function getAttribute(string $key): mixed
    public function hasAttribute(string $key): bool
    public function getAttributes(): array
}
```

**Relationships**:
- Belongs to an Encounter (many-to-one)
- Has one ActorState per encounter (one-to-one with state)

**Validation Rules**:
- `id` must be unique within an encounter
- `initiative` must be within TimelineProfile bounds (if configured)
- `attributes` are freeform key-value pairs for system-specific data

**State Transitions**: Immutable once created; initiative changes return new Actor instance or mutate via Encounter

---

### TimelineProfile

Defines the turn order model and rules for an encounter.

**Properties**:
```php
class TimelineProfile
{
    public function __construct(
        private TurnOrderType $type,              // round-individual, round-side, pass, slot, popcorn
        private ?int $minInitiative = null,       // Optional minimum initiative
        private ?int $maxInitiative = null,       // Optional maximum initiative
        private ?string $tieBreakerAttribute = null,  // Attribute for tie-breaking
        private ?callable $tieBreakerCallback = null, // Custom tie-breaker
        
        // Pass-based specific:
        private ?int $passesPerRound = null,      // Number of passes per round
        private bool $decayEnabled = false,       // Enable initiative decay
        private int $decayAmount = 10,            // Decay per pass
        
        // Popcorn specific:
        private bool $allowRepeatPopcorn = false, // Allow re-designating acted actors
        
        // Slot-based specific:
        private ?array $slotConfiguration = null  // Slot types and counts
    ) {}
    
    public function getType(): TurnOrderType
    public function getMinInitiative(): ?int
    public function getMaxInitiative(): ?int
    public function getTieBreakerAttribute(): ?string
    public function getTieBreakerCallback(): ?callable
    public function getPassesPerRound(): ?int
    public function isDecayEnabled(): bool
    public function getDecayAmount(): int
    public function allowsRepeatPopcorn(): bool
    public function getSlotConfiguration(): ?array
    public function validate(): void  // Throws InvalidTimelineProfileException
}
```

**Relationships**:
- Belongs to one Encounter (one-to-one)
- Determines which TurnOrderStrategy is used (factory pattern)

**Validation Rules**:
- `type` must be one of: round-individual, round-side, pass, slot, popcorn
- If `type` is pass: `passesPerRound` must be > 0
- If `type` is slot: `slotConfiguration` must be provided
- `minInitiative` must be < `maxInitiative` (if both specified)
- `decayAmount` must be > 0 if `decayEnabled` is true
- Pass-specific properties only valid when `type` is pass
- Popcorn-specific properties only valid when `type` is popcorn

**State Transitions**: Immutable after encounter starts

---

### TurnOrderType

Enumeration-style class for turn order models (PHP 8.1 compatible, no enums).

**Constants**:
```php
class TurnOrderType
{
    public const ROUND_INDIVIDUAL = 'round-individual';  // D&D, Pathfinder
    public const ROUND_SIDE = 'round-side';              // OSR, AD&D
    public const PASS = 'pass';                          // Shadowrun
    public const SLOT = 'slot';                          // Genesys, FFG Star Wars
    public const POPCORN = 'popcorn';                    // Marvel Heroic, Cortex
    
    public static function isValid(string $type): bool
    {
        return in_array($type, [
            self::ROUND_INDIVIDUAL,
            self::ROUND_SIDE,
            self::PASS,
            self::SLOT,
            self::POPCORN,
        ], true);
    }
}
```

---

### Encounter

Main coordinator for turn tracking. Manages actors, state, and delegates to turn order strategy.

**Properties**:
```php
class Encounter
{
    public function __construct(
        private TimelineProfile $profile,
        private TurnOrderInterface $turnOrder,    // Injected strategy
        private EncounterState $state,
        private array $actors = [],               // Actor[]
        private array $actorStates = []           // ActorState[] keyed by actor ID
    ) {}
    
    // Lifecycle
    public function start(): void
    public function isActive(): bool
    
    // Actor management
    public function addActor(Actor $actor): void
    public function removeActor(string $actorId): void
    public function getActor(string $actorId): Actor
    public function getActors(): array
    
    // Turn progression
    public function getCurrentActor(): ?Actor
    public function advanceTurn(): void
    public function changeInitiative(string $actorId, int $newInitiative): void
    public function delayActor(string $actorId, int $newInitiative): void
    
    // State queries
    public function getCurrentRound(): int
    public function getCurrentPass(): ?int
    public function hasActorActed(string $actorId): bool
    public function getActedActors(): array
    public function getUnactedActors(): array
    public function getState(): EncounterState
    
    // Popcorn-specific
    public function designateNext(string $actorId): void
}
```

**Relationships**:
- Has one TimelineProfile (one-to-one, immutable)
- Has many Actors (one-to-many, dynamic)
- Has one EncounterState (one-to-one, mutable)
- Has many ActorStates (one-to-many, mutable)
- Uses one TurnOrderStrategy (one-to-one, based on profile type)

**Validation Rules**:
- Cannot start if already active
- Cannot add actor with duplicate ID
- Cannot remove actor that doesn't exist
- Cannot advance turn if not active or no actors
- Initiative changes must respect profile bounds

**State Transitions**:
```
[Inactive] --start()--> [Active, Round 1, Pass 1 if applicable]
[Active] --addActor()--> [Active, actor added to turn order]
[Active] --removeActor()--> [Active, actor removed, current may advance]
[Active] --advanceTurn()--> [Active, next actor/pass/round]
```

---

### EncounterState

Tracks temporal state of an encounter.

**Properties**:
```php
class EncounterState
{
    private bool $isActive = false;
    private int $currentRound = 0;
    private ?int $currentPass = null;
    private ?string $currentActorId = null;
    
    public function isActive(): bool
    public function setActive(bool $active): void
    
    public function getCurrentRound(): int
    public function setCurrentRound(int $round): void
    public function incrementRound(): void
    
    public function getCurrentPass(): ?int
    public function setCurrentPass(?int $pass): void
    public function incrementPass(): void
    
    public function getCurrentActorId(): ?string
    public function setCurrentActorId(?string $actorId): void
}
```

**Relationships**:
- Belongs to one Encounter (one-to-one)

**State Transitions**:
- Round increments when all actors in last pass/turn have acted
- Pass increments when all eligible actors in current pass have acted (pass-based only)
- Current actor changes on each advanceTurn()

---

### ActorState

Tracks per-actor state within an encounter.

**Properties**:
```php
class ActorState
{
    public function __construct(
        private string $actorId,
        private bool $hasActed = false,
        private ?int $passesRemaining = null   // For pass-based systems
    ) {}
    
    public function getActorId(): string
    public function hasActed(): bool
    public function setHasActed(bool $acted): void
    public function resetActed(): void
    
    public function getPassesRemaining(): ?int
    public function setPassesRemaining(?int $passes): void
    public function decrementPassesRemaining(): void
}
```

**Relationships**:
- Belongs to one Actor (many-to-one)
- Belongs to one Encounter (many-to-one)

**State Transitions**:
- `hasActed` set to true when actor takes turn
- `hasActed` reset to false when round/pass advances
- `passesRemaining` decrements each pass (pass-based only)

---

### TurnOrderInterface (Strategy Pattern)

Defines contract for turn order implementations.

**Methods**:
```php
interface TurnOrderInterface
{
    // Calculate initial turn order when encounter starts
    public function calculateInitialOrder(array $actors): array;
    
    // Get next actor ID in sequence
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string;
    
    // Handle actor addition mid-encounter
    public function addActor(Actor $actor, EncounterState $encounterState): void;
    
    // Handle actor removal mid-encounter
    public function removeActor(string $actorId, EncounterState $encounterState): void;
    
    // Handle initiative change
    public function changeInitiative(
        string $actorId,
        int $newInitiative,
        array $actors,
        EncounterState $encounterState
    ): void;
    
    // Check if round/pass should advance
    public function shouldAdvanceRound(array $actorStates): bool;
    public function shouldAdvancePass(array $actorStates): bool;
}
```

**Implementations**:
- `RoundBasedIndividual`: Sorts actors by initiative, cycles through in order
- `RoundBasedSide`: Groups actors by side attribute, cycles through sides
- `PassBased`: Multi-pass tracking with initiative decay
- `SlotBased`: Generates slots from initiative, any matching actor fills slot
- `Popcorn`: Current actor designates next actor

---

## Validation Rules Summary

### Initiative Validation
- Must be integer
- Must be within TimelineProfile min/max bounds (if configured)
- Validated on: Actor creation, addActor(), changeInitiative(), delayActor()

### TimelineProfile Validation
- Type must be valid TurnOrderType constant
- Pass-based: passesPerRound > 0
- Slot-based: slotConfiguration must be array with valid structure
- Bounds: minInitiative < maxInitiative (if both set)
- Decay: decayAmount > 0 if decayEnabled
- Validated on: TimelineProfile construction, Encounter start

### Encounter Validation
- Cannot start if already active
- Cannot add actor with duplicate ID
- Cannot operate on inactive encounter (advanceTurn, etc.)
- Cannot remove nonexistent actor
- Actor IDs must be non-empty strings

---

## State Transition Diagrams

### Encounter Lifecycle
```
[Created] 
   |
   | start()
   ↓
[Active: Round 1, Current Actor = first by initiative]
   |
   | advanceTurn() (mid-round)
   ↓
[Active: Round 1, Current Actor = next in order]
   |
   | advanceTurn() (last actor of round)
   ↓
[Active: Round 2, Current Actor = first by initiative]
```

### Pass-Based Round Progression
```
[Round N, Pass 1]
   | all pass-1 actors acted
   | advanceTurn()
   ↓
[Round N, Pass 2] (decay applied if enabled)
   | all pass-2 actors acted
   | advanceTurn()
   ↓
[Round N, Pass 3]
   | all pass-3 actors acted
   | advanceTurn()
   ↓
[Round N+1, Pass 1] (all actors reset)
```

### Actor State Per Round
```
[Added to Encounter]
   |
   | hasActed = false
   ↓
[Waiting for Turn]
   |
   | actor's turn comes
   | advanceTurn()
   ↓
[Has Acted This Round]
   | hasActed = true
   ↓
   | round advances
   ↓
[Waiting for Turn] (hasActed reset to false)
```

---

## Relationships Diagram

```
TimelineProfile (1) ------- (1) Encounter
                                  |
                                  | creates
                                  ↓
                            TurnOrderStrategy (1)
                            (selected by profile type)
                                  
Encounter (1) ----------- (many) Actor
   |                               |
   | manages state                 | has state
   ↓                               ↓
EncounterState (1)      ActorState (many)
```

---

## Key Design Decisions

1. **Immutability**: Actor and TimelineProfile are immutable value objects; state is mutable
2. **Strategy Pattern**: Turn order logic varies by system, strategy pattern provides clean abstraction
3. **State Separation**: Encounter entity vs EncounterState allows clear temporal tracking
4. **ID Strategy**: Consumer provides actor IDs (strings), library doesn't enforce UUID or auto-increment
5. **Validation Placement**: TimelineProfile validates on construction, Encounter validates operations
6. **No Persistence**: Library doesn't handle storage; consumers serialize/deserialize as needed
