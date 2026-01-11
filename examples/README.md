# Examples

This directory contains complete, runnable examples demonstrating PHPTurnTracker usage with different RPG systems.

## Game Systems Copyright Notice

All examples in this directory demonstrate initiative mechanics from various tabletop RPG systems. These mechanics are used solely for non-commercial purposes to assist game masters in tracking combat initiative. All game system names, mechanics, and related intellectual property remain the property of their respective copyright holders. See [GAME_SYSTEMS_COPYRIGHT.md](../GAME_SYSTEMS_COPYRIGHT.md) for detailed copyright notices and attributions.

## Running Examples

All examples require the library to be installed:

```bash
composer install
php examples/dnd5e.php
```

## Available Examples

### [dnd5e.php](dnd5e.php)
**System**: D&D 5e / Pathfinder  
**Turn Order**: Round-based individual initiative  
**Demonstrates**:
- Individual initiative with dexterity tie-breaker
- Basic turn progression through multiple rounds
- Actor state tracking (acted/unacted)

### [shadowrun4e.php](shadowrun4e.php)
**System**: Shadowrun 4th Edition  
**Turn Order**: Pass-based with 10-point decay  
**Demonstrates**:
- Multi-pass combat system
- Initiative decay between passes
- High-initiative characters getting multiple actions per round

### [genesys-slot.php](genesys-slot.php)
**System**: Genesys / Star Wars FFG  
**Turn Order**: Slot-based initiative  
**Demonstrates**:
- PC and NPC slots determined by dice rolls
- Players choosing which character fills each slot
- Flexible turn order within slot types

### [popcorn.php](popcorn.php)
**System**: Marvel Heroic RPG  
**Turn Order**: Popcorn (narrative designation)  
**Demonstrates**:
- Current actor designates next actor
- Tracking acted/unacted status
- Automatic last-actor selection

### [osr-side.php](osr-side.php)
**System**: B/X D&D / OSR  
**Turn Order**: Side-based initiative  
**Demonstrates**:
- Entire teams acting together
- Party vs Monsters turn order
- Simple old-school combat flow

### [dynamic-actors.php](dynamic-actors.php)
**System**: D&D-style with modifications  
**Demonstrates**:
- Adding reinforcements mid-combat
- Removing defeated actors
- Changing initiative (buffs/debuffs)
- Delaying actions
- Maintaining turn order through changes

## Quick Reference

### Timeline Types

```php
TurnOrderType::ROUND_INDIVIDUAL  // D&D, Pathfinder, most modern RPGs
TurnOrderType::PASS              // Shadowrun multi-pass system
TurnOrderType::SLOT              // Genesys slot-based
TurnOrderType::POPCORN           // Narrative designation
TurnOrderType::ROUND_SIDE        // OSR side-based
```

### Common Patterns

**Start Combat**:
```php
$encounter = new Encounter($profile);
$encounter->addActor(new Actor('id', 'Name', 15));
$encounter->start();
```

**Run Turns**:
```php
$current = $encounter->getCurrentActor();
// ... resolve action ...
$encounter->advanceTurn();
```

**Dynamic Changes**:
```php
$encounter->addActor($newActor);           // Add reinforcement
$encounter->removeActor('id');             // Remove defeated
$encounter->changeInitiative('id', 20);    // Buff/debuff
$encounter->delayActor('id', 10);          // Delay action
```

## Need More Help?

- [Quick Start Guide](../specs/001-rpg-turn-tracker/quickstart.md) - Full tutorial
- [README](../README.md) - Library overview and installation
- [API Documentation](../docs/) - Complete API reference
