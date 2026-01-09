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

### [0.1.0] - 2026-01-09
Alpha release with complete feature set for 6 major RPG turn order systems. Not recommended for production.

[Unreleased]: https://github.com/codryn/phpturntracker/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/codryn/phpturntracker/releases/tag/v0.1.0...HEAD
[1.0.0]: https://github.com/codryn/phpturntracker/releases/tag/v1.0.0
