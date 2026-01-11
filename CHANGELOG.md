# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned for v1.0.0
- Production-ready release
- Additional documentation and examples
- Performance optimizations
- Extended test coverage to 90%+

---

## [0.2.0] - 2026-01-09

**Alpha Release** - Enhanced turn management with delay mechanics and encounter control.

### Added

#### State Persistence
Save and restore complete encounter state
  - `getState()`: Capture immutable snapshot of entire encounter
  - `restoreState()`: Restore encounter from snapshot
  - `EncounterSnapshot`: Immutable state container with JSON serialization
  - Supports persistence, UI synchronization, and save/load features
  - Complete test coverage with 314 integration tests and 317 unit tests

#### New Turn Management Features
- **`delayActor()`**: Temporarily delay an actor's turn to a lower initiative for the current round only
  - Actor maintains "unacted" status when delayed
  - Initiative automatically restores to original value in next round
  - Useful for "Ready Action" mechanics and tactical positioning
  - Validates that new initiative is lower than current

- **`rewindTurn()`**: Undo the last turn advancement
  - Restores previous actor as current
  - Unmarks previous actor's "acted" status
  - Automatically undoes temporary delays and restores original initiative
  - Useful for correcting mistakes or implementing "take-back" mechanics
  - Throws exception if no previous turn exists

#### Enhanced Encounter Control
- **`restart()`**: Restart encounter from round 1 with same actors
  - Preserves all actors and their current initiatives
  - Resets all acted/unacted states
  - Clears temporary delays from previous rounds
  - Encounter remains active (no need to call `start()` again)
  - Useful for practice rounds or replay scenarios

- **`reset()`**: Complete encounter reset to initial state
  - Removes all actors
  - Clears all internal state
  - Sets encounter to inactive
  - Returns encounter to "as-constructed" state
  - Allows starting fresh with new actors

### Changed

#### CI Improvements
Enhanced GitHub Actions workflow
  - Aligned configuration with other package projects
  - Fixed script permissions
  - Improved code style enforcement

#### Internal Improvements
- **Turn Order Strategy Interface**: Added `actorStates` parameter to `changeInitiative()` method
  - Enables turn order strategies to use temporary initiative values from actor states
  - Required for proper delay functionality
  - All turn order implementations updated accordingly

- **Actor State Tracking**: Enhanced state management for temporary vs permanent initiative changes
  - `ActorState` now properly tracks `currentInitiative` separately from base initiative
  - `delayActor()` modifies current initiative only (temporary)
  - `changeInitiative()` modifies base initiative (permanent)
  - Round advancement automatically restores original initiatives

- **Previous Actor Tracking**: Added `previousActorId` to `EncounterState`
  - Enables `rewindTurn()` functionality
  - Automatically tracked during `advanceTurn()` and `delayActor()`
  - Cleared after successful rewind

### Quality Improvements
- **Test Coverage**: Increased from 87% to 88.30% line coverage
  - Added 12 new integration tests for encounter management
  - Total: 179 tests with 503 assertions (up from 167 tests, 450 assertions)
  - New test file: `EncounterManagementTest.php`
  - Comprehensive coverage of reset, restart, and rewindTurn functionality

- **Static Analysis**: PHPStan level MAX with zero errors
  - Fixed null coalescing operator warnings
  - All code passes strict type checking

### Fixed
- **Code Style**: Fixed trailing whitespace and formatting issues
- **State Serialization**: Fixed serialization and deserialization edge cases

### Technical Details
- **PHP Version**: 8.1+ with strict types
- **Dependencies**: Zero (production), PHPUnit 10 (dev)
- **Performance**: Maintains 167,000+ turns/second
- **API Changes**: Interface change is backward compatible with new optional parameter

### Migration Notes

#### For Users of v0.1.0

**API Changes (Non-Breaking)**:
- If you've implemented custom turn order strategies, you'll need to add the `actorStates` parameter to your `changeInitiative()` implementation
- Existing encounters will continue to work without modification
- New methods are additions and don't affect existing functionality

**Example Update for Custom Strategies**:
```php
// Old signature
public function changeInitiative(
    string $actorId,
    int $newInitiative,
    array $actors,
    EncounterState $encounterState
): void

// New signature
public function changeInitiative(
    string $actorId,
    int $newInitiative,
    array $actors,
    array $actorStates,  // New parameter
    EncounterState $encounterState
): void
```

---

## [0.1.0] - 2026-01-09

**Alpha Release** - Not recommended for production use. This is an early preview release for testing and feedback.

Initial alpha release of PHP Turn Tracker - a flexible turn order tracking library for tabletop RPG combat systems.

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
  PHPUnit test suite with 87% coverage (167 tests, 450 assertions)
- Integration tests for all 6 user stories
- Unit tests for all strategies and components
- PSR-12 code style compliance (enforced via php-cs-fixer)
- PHPStan level MAX static analysis
- **Flexible Attributes**: Custom actor attributes for tie-breaking and side tracking

#### Quality Assurance
- Comprehensive PHPUnit test suite (90%+ coverage)
- Integration tests for all 6 user stories
- Unit tests for all strategies and components
- PSR-12 code style compliance (enforced via php-cs-fixer)
- PHP 8.1+ type safety with strict types

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
0+ with constructor promotion, named arguments, strict types
- **Architecture**: Strategy pattern for turn order implementations
- **Performance**: 167,000+ turns/second, handles 20+ actors through 100+ rounds
- **Dependencies**: None (zero-dependency design for maximum portability)
- **Package**: `codryn/phpturntracker`or maximum portability)
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
Known Issues & Limitations

⚠️ **Alpha Release Warnings**:
- Not production-ready - API may change before v1.0.0
- Test coverage at 87% (target: 90%+ for v1.0.0)
- Documentation is complete but examples may need refinement
- Performance tested but not yet battle-tested in production environments

**Technical Limitations**:
- Timeline profiles are immutable after encounter creation
- No built-in persistence layer (consumers handle serialization)
- Single-threaded design (PHP default, no thread-safety needed)
- No GUI components (library only)

### Migration Guide

This is the initial alpha release. No migration needed.

### Feedback Welcome

This is an alpha release for early adopters and testing. Please report issues, suggestions, and use cases:
- GitHub Issues: [Report bugs or request features]
- Feedback on API design welcome before v1.0.0 stabilization

---

## Version History

### [0.2.0] - 2026-01-09
Enhanced turn management with delay mechanics, rewind functionality, and encounter control (restart/reset).

### [0.1.0] - 2026-01-09
Alpha release with complete feature set for 6 major RPG turn order systems. Not recommended for production.

[Unreleased]: https://github.com/codryn/phpturntracker/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/codryn/phpturntracker/releases/tag/v0.2.0
[0.1.0]: https://github.com/codryn/phpturntracker/releases/tag/v0.1.0
