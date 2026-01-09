# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

---

## [1.0.0] - 2026-01-09

Initial release of PHP Turn Tracker - a flexible turn order tracking library for tabletop RPG combat systems.

### Added

#### Core Features
- **Multiple Turn Order Systems**:
  - Round-based individual initiative (D&D 5e, Pathfinder, d20 systems)
  - Round-based side initiative (B/X D&D, OSR, war games)
  - Pass-based with initiative decay (Shadowrun 4e, 5e, 6e)
  - Slot-based initiative (Genesys, FFG Star Wars)
  - Popcorn initiative (Marvel Heroic, Cortex, narrative systems)

- **Actor Management**:
  - Add actors with initiative scores and custom attributes
  - Remove actors mid-encounter (defeated, fled)
  - Dynamic reinforcements joining combat
  - Automatic turn advancement when current actor removed

- **Initiative Mechanics**:
  - Change initiative scores mid-combat (buffs, debuffs, spells)
  - Delay actions to lower initiative
  - Preserve acted/unacted status through initiative changes
  - Configurable tie-breaking (attribute-based, stable insertion order)
  - Optional min/max initiative bounds validation

- **State Tracking**:
  - Current round and pass tracking
  - Acted/unacted actor lists per round/pass
  - Encounter active/inactive status
  - Current actor determination

#### Configuration
- **Timeline Profiles**: Configurable turn order rules per RPG system
- **Validation**: Built-in validation for profiles and initiative values
- **Flexible Attributes**: Custom actor attributes for tie-breaking and side tracking

#### Quality Assurance
- Comprehensive PHPUnit test suite (90%+ coverage)
- Integration tests for all 6 user stories
- Unit tests for all strategies and components
- PSR-12 code style compliance (enforced via php-cs-fixer)
- PHP 8.0+ type safety with strict types

#### Documentation
- Complete README with installation and usage examples
- Quick Start Guide with 5-minute tutorial
- 6 complete code examples for different RPG systems:
  - D&D 5e with dexterity tie-breaker
  - Shadowrun 4e multi-pass combat
  - Genesys slot-based initiative
  - Popcorn initiative (Marvel Heroic)
  - OSR side-based combat
  - Dynamic actor management demo
- Inline PHPDoc comments on all public APIs
- API documentation structure

#### Developer Tools
- Composer scripts for testing, coverage, code style
- PHPUnit configuration with strict mode
- php-cs-fixer configuration for PSR-12
- Zero external dependencies (production)

### Technical Details

- **Language**: PHP 8.0+ with constructor promotion, named arguments, strict types
- **Architecture**: Strategy pattern for turn order implementations
- **Performance**: Handles 20+ actors, 100+ rounds without performance degradation
- **Dependencies**: None (zero-dependency design for maximum portability)
- **Package**: `codryn/phpturntracker` (Packagist)
- **License**: MIT

### Supported RPG Systems

| System | Edition | Turn Order Type | Features |
|--------|---------|----------------|----------|
| D&D / Pathfinder | All | Round-based individual | Tie-breakers, delays, initiative changes |
| Shadowrun | 4e | Pass-based | 4 passes, -10 decay |
| Shadowrun | 5e/6e | Pass-based | 4 passes, -5 decay |
| Genesys | All | Slot-based | PC/NPC slots, flexible order |
| Star Wars FFG | All | Slot-based | PC/NPC slots |
| Marvel Heroic | All | Popcorn | Narrative designation |
| B/X D&D | All | Side-based | Party vs monsters |
| OSR Systems | Various | Side-based | Team initiative |

### Migration Guide

This is the initial release. No migration needed.

### Breaking Changes

None (initial release).

### Known Limitations

- Timeline profiles are immutable after encounter creation
- No built-in persistence layer (consumers handle serialization)
- Single-threaded design (PHP default, no thread-safety needed)
- No GUI components (library only)

---

## Version History

### [1.0.0] - 2026-01-09
Initial release with complete feature set for 6 major RPG turn order systems.

Initial release of PHP Turn Tracker library supporting all major RPG combat systems.

[Unreleased]: https://github.com/codryn/phpturntracker/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/codryn/phpturntracker/releases/tag/v1.0.0
