# Quick Start Guide: PHP Turn Tracker

**Version**: 1.0.0  
**Package**: `codryn/phpturntracker`  
**PHP**: 8.1+

## Installation

```bash
composer require codryn/phpturntracker
```

## 5-Minute Tutorial

### Step 1: Create a Timeline Profile

Choose your RPG system's turn order model:

```php
use Codryn\PhpTurnTracker\TimelineProfile;
use Codryn\PhpTurnTracker\TurnOrderType;

// D&D 5e / Pathfinder: Individual initiative, round-based
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);
```

### Step 2: Create an Encounter

```php
use Codryn\PhpTurnTracker\Encounter;

$encounter = new Encounter($profile);
```

### Step 3: Add Participants

```php
use Codryn\PhpTurnTracker\Actor;

$encounter->addActor(new Actor(
    id: 'fighter',
    name: 'Conan the Barbarian',
    initiative: 18
));

$encounter->addActor(new Actor(
    id: 'wizard',
    name: 'Gandalf the Grey',
    initiative: 15
));

$encounter->addActor(new Actor(
    id: 'orc',
    name: 'Orc Warrior',
    initiative: 12
));
```

### Step 4: Start Combat

```php
$encounter->start();
```

### Step 5: Run Turns

```php
// Get current actor
$current = $encounter->getCurrentActor();
echo "It's {$current->getName()}'s turn!\n";
// Output: "It's Conan the Barbarian's turn!"

// Advance to next turn
$encounter->advanceTurn();

$current = $encounter->getCurrentActor();
echo "It's {$current->getName()}'s turn!\n";
// Output: "It's Gandalf the Grey's turn!"
```

### Step 6: Track State

```php
// Check what round we're in
echo "Round: " . $encounter->getCurrentRound() . "\n";

// See who has acted
$acted = $encounter->getActedActors();
foreach ($acted as $actor) {
    echo "{$actor->getName()} has acted\n";
}

// See who's waiting
$waiting = $encounter->getUnactedActors();
foreach ($waiting as $actor) {
    echo "{$actor->getName()} is waiting\n";
}
```

---

## Common Scenarios

### Adding Reinforcements Mid-Combat

```php
// Combat is underway, round 2...
$encounter->addActor(new Actor(
    id: 'troll',
    name: 'Cave Troll',
    initiative: 20
));

// Troll joins turn order but won't act until next round
```

### Removing Defeated Enemies

```php
// Orc is defeated
$encounter->removeActor('orc');

// If orc was current actor, turn automatically advances to next
```

### Delaying an Action

```php
// It's the wizard's turn, but they want to delay
$encounter->delayActor('wizard', 10);  // Reduce initiative to 10

// Wizard moves later in turn order, still gets their action
```

### Changing Initiative (Spell/Buff Effect)

```php
// Fighter gets haste spell, initiative increases
$encounter->changeInitiative('fighter', 25);

// Turn order updates, but fighter doesn't get extra turn this round
```

---

## RPG System Examples

### D&D 5e

```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30,
    tieBreakerAttribute: 'dexterity'
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('pc', 'Ranger', 18, ['dexterity' => 16]));
$encounter->addActor(new Actor('npc', 'Goblin', 18, ['dexterity' => 14]));
// Ranger wins tie (higher dex)
```

### Shadowrun 4e

```php
$profile = new TimelineProfile(
    type: TurnOrderType::PASS,
    passesPerRound: 4,
    decayEnabled: true,
    decayAmount: 10
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('sam', 'Street Samurai', 28));  // 3 passes
$encounter->addActor(new Actor('mage', 'Mage', 15));           // 2 passes
$encounter->addActor(new Actor('grunt', 'Grunt', 8));          // 1 pass

$encounter->start();
// Pass 1: sam(28), mage(15), grunt(8) all act
// Pass 2: sam(18), mage(5) act (decay -10)
// Pass 3: sam(8) acts
// Round 2: All reset to original initiative
```

### Shadowrun 5e

```php
// Same as SR4, but different decay rate
$profile = new TimelineProfile(
    type: TurnOrderType::PASS,
    passesPerRound: 4,
    decayEnabled: true,
    decayAmount: 5  // SR5 uses -5 instead of -10
);
```

### Genesys / FFG Star Wars (Slot-Based)

```php
$profile = new TimelineProfile(
    type: TurnOrderType::SLOT,
    slotConfiguration: [
        ['type' => 'pc', 'count' => 2],
        ['type' => 'npc', 'count' => 3],
        ['type' => 'pc', 'count' => 1]
    ]
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('hero1', 'Luke', 0, ['side' => 'pc']));
$encounter->addActor(new Actor('hero2', 'Leia', 0, ['side' => 'pc']));
$encounter->addActor(new Actor('villain', 'Vader', 0, ['side' => 'npc']));

// Turn order: PC slot, PC slot, NPC slot, NPC slot, NPC slot, PC slot
// Any PC can fill PC slots, any NPC fills NPC slots
```

### Marvel Heroic / Popcorn Initiative

```php
$profile = new TimelineProfile(
    type: TurnOrderType::POPCORN,
    allowRepeatPopcorn: false
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('hero', 'Spider-Man', 15));
$encounter->addActor(new Actor('villain', 'Green Goblin', 12));

$encounter->start();
// Highest initiative starts
$current = $encounter->getCurrentActor(); // Spider-Man

// Spider-Man chooses who goes next
$encounter->designateNext('villain');
$current = $encounter->getCurrentActor(); // Green Goblin
```

### Old-School D&D (Side-Based)

```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_SIDE
);

$encounter = new Encounter($profile);
$encounter->addActor(new Actor('pc1', 'Fighter', 15, ['side' => 'heroes']));
$encounter->addActor(new Actor('pc2', 'Cleric', 15, ['side' => 'heroes']));
$encounter->addActor(new Actor('orc1', 'Orc', 12, ['side' => 'monsters']));
$encounter->addActor(new Actor('orc2', 'Orc', 12, ['side' => 'monsters']));

$encounter->start();
// Heroes side goes first (all heroes can act in any order)
// Then monsters side (all monsters can act in any order)
```

---

## Best Practices

### 1. Use Descriptive Actor IDs

```php
// Good
$encounter->addActor(new Actor('pc-gandalf', 'Gandalf', 15));
$encounter->addActor(new Actor('enemy-balrog', 'Balrog', 20));

// Avoid
$encounter->addActor(new Actor('1', 'Gandalf', 15));
$encounter->addActor(new Actor('2', 'Balrog', 20));
```

### 2. Store Timeline Profile as Configuration

```php
class D&D5eProfile {
    public static function create(): TimelineProfile {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 30,
            tieBreakerAttribute: 'dexterity'
        );
    }
}

$encounter = new Encounter(D&D5eProfile::create());
```

### 3. Validate Before Operations

```php
try {
    $encounter->addActor($actor);
} catch (InvalidInitiativeException $e) {
    echo "Invalid initiative: " . $e->getMessage();
} catch (DuplicateActorException $e) {
    echo "Actor already exists: " . $e->getMessage();
}
```

### 4. Query State Frequently

```php
// Always check before acting on current actor
if ($encounter->isActive()) {
    $current = $encounter->getCurrentActor();
    if ($current !== null) {
        // Process turn
    }
}
```

### 5. Handle Edge Cases

```php
// Check if actor exists before removing
$actor = $encounter->getActor('orc-1');
if ($actor !== null) {
    $encounter->removeActor('orc-1');
}

// Check if actor hasn't acted before delaying
if (!$encounter->hasActorActed('wizard')) {
    $encounter->delayActor('wizard', 10);
}
```

---

## Integration Patterns

### Web Application

```php
// Store encounter state in session
$_SESSION['encounter'] = serialize($encounter);

// Restore later
$encounter = unserialize($_SESSION['encounter']);
```

### Database Persistence

```php
// Export state as array
$state = [
    'profile' => $encounter->getProfile(),
    'actors' => $encounter->getActors(),
    'state' => $encounter->getState(),
    'round' => $encounter->getCurrentRound()
];

// Save to database
$db->save('encounters', $encounterId, json_encode($state));

// Restore from database
$data = json_decode($db->load('encounters', $encounterId), true);
$encounter = Encounter::fromArray($data);
```

### Event Logging

```php
// Wrap encounter methods to log events
class LoggedEncounter extends Encounter {
    public function advanceTurn(): void {
        $before = $this->getCurrentActor();
        parent::advanceTurn();
        $after = $this->getCurrentActor();
        
        $this->logger->info("Turn advanced from {$before->getName()} to {$after->getName()}");
    }
}
```

---

## Troubleshooting

### "Initiative X outside configured bounds [Y, Z]"

**Cause**: Trying to set initiative outside TimelineProfile limits

**Solution**: Either adjust bounds in profile or adjust initiative value
```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: -10,  // Allow negative
    maxInitiative: 50    // Higher max
);
```

### "Actor with ID 'X' already exists"

**Cause**: Duplicate actor ID

**Solution**: Use unique IDs per encounter
```php
$encounter->addActor(new Actor('orc-1', 'Orc', 12));
$encounter->addActor(new Actor('orc-2', 'Orc', 12));  // Different IDs
```

### "Encounter not active"

**Cause**: Forgot to call `start()`

**Solution**: Always start encounter before advancing turns
```php
$encounter = new Encounter($profile);
$encounter->addActor($actor);
$encounter->start();  // Don't forget!
$encounter->advanceTurn();
```

### Turn order seems wrong

**Cause**: Initiative ties with no tie-breaker

**Solution**: Add tie-breaker attribute or callback
```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    tieBreakerAttribute: 'dexterity'
);

// Or custom logic
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    tieBreakerCallback: fn($a, $b) => $a->getAttribute('name') <=> $b->getAttribute('name')
);
```

---

## Next Steps

- Read full [API Documentation](./contracts/encounter-api.md)
- Review [Data Model](./data-model.md) for advanced usage
- Check [examples/](../examples/) for complete implementations
- See [CHANGELOG.md](../../CHANGELOG.md) for version history

---

## Support

- **Issues**: GitHub Issues at `github.com/codryn/phpturntracker`
- **Documentation**: README.md in package root
- **License**: See LICENSE file
