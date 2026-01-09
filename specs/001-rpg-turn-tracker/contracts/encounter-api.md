# API Contract: Encounter Management

**Version**: 1.0.0  
**Date**: 2026-01-08

## Public API

### Encounter Creation

```php
// Create a new encounter with a timeline profile
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);

$encounter = new Encounter($profile);
```

**Parameters**:
- `profile`: TimelineProfile - Configuration for turn order model

**Returns**: Encounter instance

**Exceptions**:
- `InvalidTimelineProfileException` - Profile validation failed

---

### Start Encounter

```php
$encounter->start();
```

**Preconditions**:
- Encounter must not already be active
- At least one actor should be added (not enforced, but practical)

**Effects**:
- Sets encounter state to active
- Sets current round to 1
- Sets current pass to 1 (if pass-based)
- Calculates initial turn order
- Sets first actor as current

**Exceptions**:
- `EncounterAlreadyActiveException` - Encounter is already started

---

### Add Actor

```php
$actor = new Actor(
    id: 'pc-1',
    name: 'Gandalf',
    initiative: 18,
    attributes: ['dexterity' => 12, 'side' => 'heroes']
);

$encounter->addActor($actor);
```

**Parameters**:
- `actor`: Actor - Participant to add to encounter

**Effects**:
- Adds actor to encounter
- Creates ActorState for tracking
- If encounter active: inserts actor into turn order at appropriate position
- If encounter active: actor won't act until next round (or current round if initiative allows)

**Exceptions**:
- `DuplicateActorException` - Actor with same ID already exists
- `InvalidInitiativeException` - Initiative outside profile bounds

---

### Remove Actor

```php
$encounter->removeActor('pc-1');
```

**Parameters**:
- `actorId`: string - ID of actor to remove

**Effects**:
- Removes actor from encounter
- Removes actor's state
- If removing current actor: immediately advances to next actor
- Turn order recalculated without removed actor

**Exceptions**:
- `ActorNotFoundException` - Actor with given ID doesn't exist

---

### Advance Turn

```php
$encounter->advanceTurn();
```

**Preconditions**:
- Encounter must be active
- At least one actor must be present

**Effects**:
- Marks current actor as having acted
- Determines next actor in sequence
- Updates current actor to next
- If last actor of round: increments round, resets acted status
- If pass-based and last actor of pass: increments pass, applies decay if enabled

**Exceptions**:
- `EncounterNotActiveException` - Encounter not started
- `NoActorsException` - No actors in encounter

---

### Change Initiative

```php
$encounter->changeInitiative('pc-1', 22);
```

**Parameters**:
- `actorId`: string - ID of actor to modify
- `newInitiative`: int - New initiative score

**Effects**:
- Updates actor's initiative score
- Recalculates turn order with new initiative
- **Preserves** acted/unacted status for current round
- If initiative increases past current position: actor doesn't get another turn this round

**Exceptions**:
- `ActorNotFoundException` - Actor doesn't exist
- `InvalidInitiativeException` - New initiative outside bounds
- `EncounterNotActiveException` - Encounter not started

---

### Delay Actor

```php
$encounter->delayActor('pc-1', 10);
```

**Parameters**:
- `actorId`: string - ID of actor delaying action
- `newInitiative`: int - Initiative to delay to (must be lower than current)

**Preconditions**:
- Actor must not have acted yet this round
- New initiative must be lower than current initiative

**Effects**:
- Reduces actor's initiative to new value
- Recalculates turn order (actor moves later)
- **Preserves** unacted status (actor still gets their turn)
- Immediately advances to next actor if delaying actor was current

**Exceptions**:
- `ActorNotFoundException` - Actor doesn't exist
- `ActorAlreadyActedException` - Actor has already acted this round
- `InvalidDelayException` - New initiative not lower than current
- `InvalidInitiativeException` - New initiative outside bounds

---

### Get Current Actor

```php
$currentActor = $encounter->getCurrentActor();
```

**Returns**: Actor|null - Current actor whose turn it is, or null if no actors

---

### Query State

```php
// Get current round number
$round = $encounter->getCurrentRound();  // Returns: int

// Get current pass (pass-based only)
$pass = $encounter->getCurrentPass();    // Returns: int|null

// Check if specific actor has acted
$hasActed = $encounter->hasActorActed('pc-1');  // Returns: bool

// Get all actors who have acted this round
$actedActors = $encounter->getActedActors();    // Returns: Actor[]

// Get all actors who haven't acted this round
$unactedActors = $encounter->getUnactedActors(); // Returns: Actor[]

// Check if encounter is active
$isActive = $encounter->isActive();      // Returns: bool
```

---

### Designate Next Actor (Popcorn Initiative Only)

```php
$encounter->designateNext('pc-2');
```

**Parameters**:
- `actorId`: string - ID of actor to act next

**Preconditions**:
- Timeline profile must be popcorn type
- Designated actor must exist
- If allowRepeatPopcorn=false: designated actor must not have acted yet

**Effects**:
- Marks current actor as having acted
- Sets designated actor as current
- If all actors have acted: advances round (if allowRepeatPopcorn=false)

**Exceptions**:
- `InvalidTurnOrderTypeException` - Not a popcorn initiative encounter
- `ActorNotFoundException` - Designated actor doesn't exist
- `InvalidDesignationException` - Actor already acted and repeats not allowed

---

## Usage Examples

### Example 1: Basic D&D 5e Combat

```php
// Setup D&D 5e encounter
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30,
    tieBreakerAttribute: 'dexterity'
);

$encounter = new Encounter($profile);

// Add participants
$encounter->addActor(new Actor('fighter', 'Conan', 18, ['dexterity' => 14]));
$encounter->addActor(new Actor('wizard', 'Gandalf', 15, ['dexterity' => 12]));
$encounter->addActor(new Actor('goblin-1', 'Goblin', 12, ['dexterity' => 14]));
$encounter->addActor(new Actor('goblin-2', 'Goblin', 12, ['dexterity' => 11]));

// Start combat
$encounter->start();

// Round 1
echo $encounter->getCurrentActor()->getName(); // "Conan"
$encounter->advanceTurn();

echo $encounter->getCurrentActor()->getName(); // "Gandalf"
$encounter->advanceTurn();

// Tie: both goblins have init 12, higher dex goes first
echo $encounter->getCurrentActor()->getName(); // "Goblin" (goblin-1, dex 14)
$encounter->advanceTurn();

echo $encounter->getCurrentActor()->getName(); // "Goblin" (goblin-2, dex 11)
$encounter->advanceTurn();

// Round 2
echo $encounter->getCurrentRound(); // 2
echo $encounter->getCurrentActor()->getName(); // "Conan"
```

---

### Example 2: Shadowrun Pass-Based Combat

```php
// Setup Shadowrun 4e encounter
$profile = new TimelineProfile(
    type: TurnOrderType::PASS,
    passesPerRound: 4,
    decayEnabled: true,
    decayAmount: 10
);

$encounter = new Encounter($profile);

// Add participants (initiative determines number of passes)
$encounter->addActor(new Actor('sam', 'Street Samurai', 25));  // 3 passes
$encounter->addActor(new Actor('mage', 'Mage', 18));           // 2 passes
$encounter->addActor(new Actor('decker', 'Decker', 10));       // 1 pass
$encounter->addActor(new Actor('goon', 'Goon', 8));            // 1 pass

$encounter->start();

// Pass 1: All act
echo $encounter->getCurrentPass(); // 1
// Sam (25) → Mage (18) → Decker (10) → Goon (8)

// Pass 2: Initiatives decay by 10, only Sam(15) and Mage(8) have passes
// Sam (15) → Mage (8)

// Pass 3: Only Sam(5) has a third pass
// Sam (5)

// Pass 4: No one has initiative for 4th pass
// Round advances to 2, all initiatives reset
```

---

### Example 3: Actor Management Mid-Combat

```php
$profile = new TimelineProfile(type: TurnOrderType::ROUND_INDIVIDUAL);
$encounter = new Encounter($profile);

$encounter->addActor(new Actor('pc-1', 'Hero', 18));
$encounter->addActor(new Actor('enemy-1', 'Orc', 12));
$encounter->start();

// Round 1, Turn 1: Hero acts
echo $encounter->getCurrentActor()->getName(); // "Hero"
$encounter->advanceTurn();

// Round 1, Turn 2: Orc acts
echo $encounter->getCurrentActor()->getName(); // "Orc"

// Reinforcements arrive mid-turn!
$encounter->addActor(new Actor('enemy-2', 'Troll', 20));

// Troll has higher initiative, but won't act until Round 2
$encounter->advanceTurn();

// Round 2: Troll goes first (initiative 20)
echo $encounter->getCurrentRound(); // 2
echo $encounter->getCurrentActor()->getName(); // "Troll"
```

---

### Example 4: Initiative Change & Delay

```php
$profile = new TimelineProfile(type: TurnOrderType::ROUND_INDIVIDUAL);
$encounter = new Encounter($profile);

$encounter->addActor(new Actor('fighter', 'Fighter', 18));
$encounter->addActor(new Actor('rogue', 'Rogue', 15));
$encounter->addActor(new Actor('cleric', 'Cleric', 12));
$encounter->start();

// Fighter's turn
echo $encounter->getCurrentActor()->getName(); // "Fighter"
$encounter->advanceTurn();

// Rogue's turn - decides to delay action to 10
$encounter->delayActor('rogue', 10);

// Rogue moved, Cleric is now current
echo $encounter->getCurrentActor()->getName(); // "Cleric"
$encounter->advanceTurn();

// Rogue's delayed turn (init 10, after Cleric's 12)
echo $encounter->getCurrentActor()->getName(); // "Rogue"
$encounter->advanceTurn();

// Round 2: Back to original order (Fighter 18, Rogue 15, Cleric 12)
// Delay was temporary, doesn't persist
```

---

### Example 5: Popcorn Initiative

```php
$profile = new TimelineProfile(
    type: TurnOrderType::POPCORN,
    allowRepeatPopcorn: false
);

$encounter = new Encounter($profile);

// Initiative doesn't determine order in popcorn, just who starts
$encounter->addActor(new Actor('hero-1', 'Captain', 18));
$encounter->addActor(new Actor('hero-2', 'Wizard', 15));
$encounter->addActor(new Actor('villain-1', 'Boss', 12));

$encounter->start();

// Highest initiative starts
echo $encounter->getCurrentActor()->getName(); // "Captain"

// Captain chooses Wizard to go next
$encounter->designateNext('hero-2');
echo $encounter->getCurrentActor()->getName(); // "Wizard"

// Wizard chooses Boss
$encounter->designateNext('villain-1');
echo $encounter->getCurrentActor()->getName(); // "Boss"

// All acted, round advances automatically
$encounter->advanceTurn();
echo $encounter->getCurrentRound(); // 2
```

---

## Error Handling

All exceptions extend `PhpTurnTrackerException` base class.

### Exception Hierarchy

```
PhpTurnTrackerException (base)
├── InvalidTimelineProfileException
├── InvalidInitiativeException
├── EncounterNotActiveException
├── EncounterAlreadyActiveException
├── ActorNotFoundException
├── DuplicateActorException
├── NoActorsException
├── ActorAlreadyActedException
├── InvalidDelayException
├── InvalidDesignationException
└── InvalidTurnOrderTypeException
```

### Exception Messages

All exceptions provide clear, actionable messages:

```php
try {
    $encounter->addActor($duplicateActor);
} catch (DuplicateActorException $e) {
    echo $e->getMessage();
    // "Actor with ID 'pc-1' already exists in encounter"
}

try {
    $encounter->changeInitiative('pc-1', 50);
} catch (InvalidInitiativeException $e) {
    echo $e->getMessage();
    // "Initiative 50 outside configured bounds [1, 30]"
}
```

---

## Thread Safety & Concurrency

**Guarantee**: Multiple Encounter instances are completely independent and thread-safe relative to each other.

**Limitation**: A single Encounter instance is NOT thread-safe for concurrent modifications. Consumers must synchronize access if needed.

**Rationale**: PHP's typical single-threaded execution model makes complex locking unnecessary. For async/concurrent PHP (Swoole, ReactPHP), consumers can wrap Encounter with appropriate synchronization.
