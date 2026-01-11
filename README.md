# PHPTurnTracker

[![PHP Version](https://img.shields.io/badge/PHP-8.1--8.5-blue.svg)](https://www.php.net/)
[![PHPStan Level 10](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg)](https://phpstan.org/)
[![CI](https://github.com/codryn/phpturntracker/workflows/CI/badge.svg)](https://github.com/codryn/phpturntracker/actions)
[![Latest Stable Version](https://poser.pugx.org/codryn/phpturntracker/v/stable)](https://packagist.org/packages/codryn/phpturntracker)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> Flexible turn order tracking library for tabletop RPG combat systems

**PHPTurnTracker** is a zero-dependency PHP library that provides robust turn order management for tabletop RPG combat encounters. It supports all major RPG systems through configurable timeline profiles, handling everything from D&D's individual initiative to Shadowrun's pass-based combat.

## Features

- **🎲 Universal RPG Support**: D&D all editions, Pathfinder, Shadowrun, GURPS, Savage Worlds, Genesys, Marvel Heroic, OSR, and more
- **🔄 Multiple Turn Order Models**: Round-based individual/side, pass-based with decay, slot-based, popcorn initiative
- **📊 State Tracking**: Automatic tracking of acted/unacted actors per round/pass
- **🎯 Dynamic Management**: Add reinforcements, remove defeated actors, change initiative mid-combat
- **❗ Error Handling**: Clear, specific error messages with location information
- **🔒 Type Safe**: Full PHP 8.1+ type declarations and strict mode
- **⚡ Zero Dependencies**: Pure PHP 8.1+ implementation using only stdlib and json extension
- **🧪 Well Tested**: Unit tests with comprehensive coverage
- **📦 PSR-12 Compliant**: Modern PHP coding standards

## Requirements

- PHP 8.1 or higher
- json PHP extension enabled

## Installation

```bash
composer require codryn/phpturntracker
```

## Quick Start and Usage

### Basic D&D-Style Combat

```php
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

// 1. Create a timeline profile for your RPG system
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30
);

// 2. Create an encounter
$encounter = new Encounter($profile);

// 3. Add participants
$encounter->addActor(new Actor('fighter', 'Conan', 18));
$encounter->addActor(new Actor('wizard', 'Gandalf', 15));
$encounter->addActor(new Actor('orc', 'Orc Warrior', 12));

// 4. Start combat
$encounter->start();

// 5. Run turns
while ($encounter->isActive()) {
    $current = $encounter->getCurrentActor();
    echo "It's {$current->getName()}'s turn! (Round {$encounter->getCurrentRound()})\n";
    
    // ... resolve actor's actions ...
    
    $encounter->advanceTurn();
}
```

### Dynamic Combat Management

```php
// Add reinforcements mid-combat
$encounter->addActor(new Actor('troll', 'Cave Troll', 20));

// Remove defeated enemies
$encounter->removeActor('orc');

// Handle delays
$encounter->delayActor('wizard', 10);  // Wizard delays to initiative 10

// Apply initiative modifiers
$encounter->changeInitiative('fighter', 25);  // Haste spell!
```

## Supported RPG Systems

| System | Turn Order Type | Timeline Profile |
|--------|----------------|------------------|
| **D&D 5e / Pathfinder** | Individual initiative, round-based | `ROUND_INDIVIDUAL` |
| **Shadowrun 4e** | Pass-based with 10-point decay | `PASS` (decay: 10) |
| **Shadowrun 5e/6e** | Pass-based with 5-point decay | `PASS` (decay: 5) |
| **Genesys / Star Wars FFG** | Slot-based initiative | `SLOT` |
| **Marvel Heroic / Popcorn** | Narrative designation | `POPCORN` |
| **B/X D&D / OSR** | Side-based initiative | `ROUND_SIDE` |

## Configuration Examples

### D&D 5e with Dexterity Tie-Breaker

```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_INDIVIDUAL,
    minInitiative: 1,
    maxInitiative: 30,
    tieBreakerAttribute: 'dexterity'
);

$encounter->addActor(new Actor('ranger', 'Legolas', 18, ['dexterity' => 16]));
$encounter->addActor(new Actor('rogue', 'Garrett', 18, ['dexterity' => 14]));
// Legolas acts first (higher dexterity)
```

### Shadowrun 4e Multi-Pass Combat

```php
$profile = new TimelineProfile(
    type: TurnOrderType::PASS,
    passesPerRound: 4,
    decayEnabled: true,
    decayAmount: 10
);

$encounter->addActor(new Actor('sam', 'Street Samurai', 28));  // 3 passes
$encounter->addActor(new Actor('mage', 'Mage', 15));           // 2 passes
$encounter->addActor(new Actor('grunt', 'Grunt', 8));          // 1 pass

// Pass 1: sam(28), mage(15), grunt(8) all act
// Pass 2: sam(18), mage(5) act (decay -10)
// Pass 3: sam(8) acts
// Round 2: All reset to original initiative
```

### Genesys Slot-Based Initiative

```php
$profile = new TimelineProfile(
    type: TurnOrderType::SLOT,
    slotConfiguration: [
        ['type' => 'PC', 'initiative' => 3],
        ['type' => 'NPC', 'initiative' => 2],
        ['type' => 'PC', 'initiative' => 2],
        ['type' => 'NPC', 'initiative' => 1]
    ]
);

// Any PC can fill PC slots, any NPC can fill NPC slots
// Players choose which character acts in each slot
```

### Popcorn Initiative (Marvel Heroic)

```php
$profile = new TimelineProfile(
    type: TurnOrderType::POPCORN,
    allowRepeatPopcorn: false  // Can't designate someone who already acted
);

$encounter->start();
// Current actor designates who goes next
$encounter->designateNext('hero2');
```

### OSR Side-Based Initiative

```php
$profile = new TimelineProfile(
    type: TurnOrderType::ROUND_SIDE
);

$encounter->addActor(new Actor('fighter', 'Fighter', 15, ['side' => 'players']));
$encounter->addActor(new Actor('cleric', 'Cleric', 12, ['side' => 'players']));
$encounter->addActor(new Actor('goblin1', 'Goblin', 10, ['side' => 'monsters']));

// All players act, then all monsters act
```

## State Tracking

```php
// Check current round/pass
$round = $encounter->getCurrentRound();
$pass = $encounter->getCurrentPass();  // For pass-based systems

// Query actor status
$acted = $encounter->getActedActors();
$waiting = $encounter->getUnactedActors();

// Check if encounter is active
if ($encounter->isActive()) {
    // Combat ongoing
}
```

## State Save and Restore

Save and restore complete encounter state for persistence, UI synchronization, or undo/rewind features:

```php
use Codryn\PHPTurnTracker\State\EncounterSnapshot;

// Capture current state
$snapshot = $encounter->getState();

// Serialize to JSON for storage
$json = $snapshot->toJson();
file_put_contents('encounter_state.json', $json);

// Later: Load and restore state
$json = file_get_contents('encounter_state.json');
$snapshot = EncounterSnapshot::fromJsonString($json);

$newEncounter = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
$newEncounter->restoreState($snapshot);

// Continue from exact same state
$encounter->advanceTurn();
```

The state snapshot includes:
- **Timeline configuration** (profile, turn order type, all settings)
- **All actors** (IDs, names, initiatives, attributes)
- **Actor states** (acted/unacted, passes remaining, temporary initiative changes)
- **Encounter state** (active status, current round/pass, current actor)

Use cases:
- **Persistence**: Save state between sessions
- **UI Synchronization**: Ensure UI always reflects exact game state
- **Rewind/Undo**: Implement advanced undo features using state snapshots
- **State Transfer**: Move encounters between different systems or implementations

## Documentation

- **[Examples](examples/)** - Complete code examples for each RPG system

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

## Architecture

The library uses the **Strategy Pattern** for turn order calculation:

- `Encounter` - Main coordinator class
- `Actor` - Represents a participant in combat
- `TimelineProfile` - Configuration for turn order rules
- `TurnOrderInterface` - Strategy interface for turn order models
- Strategies: `RoundBasedIndividual`, `RoundBasedSide`, `PassBased`, `SlotBased`, `Popcorn`

## Performance

- **Encounters with 20 actors**: Complete 10 rounds in <100ms
- **State queries**: <1ms response time
- **Concurrent encounters**: Multiple independent encounters without performance degradation

## Quality Standards

- ✅ **PHPStan Level 10**: Strictest static analysis level from PHPSTan 2.1
- ✅ **PSR-12**: PHP coding standards compliance
- ✅ **Strict Types**: `declare(strict_types=1)` in all files
- ✅ **TDD**: Test-driven development methodology
- ✅ **Type Hints**: Full type declarations on all methods
- ✅ **PHPDoc**: Complete documentation blocks

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Development workflow
- Coding standards
- Testing requirements
- Pull request process

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and migration guides.

## Support

- 📖 [Documentation](docs/)
- 🐛 [Issue Tracker](https://github.com/codryn/phpturntracker/issues)
- 💬 [Discussions](https://github.com/codryn/phpturntracker/discussions)
- 📧 [Email](mailto:info@codryn.com)

## Credits

Created and maintained by Marco for [Codryn](https://codryn.com).

Special thanks to:
- The PHP community
- PHPUnit, PHPStan, and PHP-CS-Fixer maintainers

### Game Systems Copyright

This library implements initiative and turn order mechanics from various tabletop RPG systems for non-commercial use. All game system names, mechanics, and related intellectual property remain the property of their respective copyright holders. See [GAME_SYSTEMS_COPYRIGHT.md](GAME_SYSTEMS_COPYRIGHT.md) for detailed copyright notices and attributions.

---

Built for the tabletop RPG community 🎲
