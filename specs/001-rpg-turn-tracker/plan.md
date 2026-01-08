# Implementation Plan: RPG Turn Order Tracker Library

**Branch**: `001-rpg-turn-tracker` | **Date**: 2026-01-08 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-rpg-turn-tracker/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

A PHP 8.0+ Composer library (`codryn/phpturntracker`) that provides flexible turn order tracking for tabletop RPG combat systems. The library accepts configurable timeline profiles to support multiple turn order models (round-based individual/side initiative, pass-based with decay, slot-based, and popcorn initiative), tracks actor state (acted/unacted), handles dynamic actor management (add/remove mid-encounter), and maintains accurate turn progression through initiative changes and delays. Development follows strict TDD with PHPUnit, PSR-12 compliance, and comprehensive documentation.

## Technical Context

**Language/Version**: PHP 8.3 (development), PHP 8.0+ (compatibility requirement)  
**Primary Dependencies**: None (zero-dependency design for maximum portability)  
**Storage**: N/A (in-memory state management, consumers handle persistence if needed)  
**Testing**: PHPUnit 10+ with 90% minimum coverage requirement  
**Target Platform**: Composer package - reusable library for integration into RPG applications  
**Project Type**: single (Composer package - PHP library structure)  
**Performance Goals**: 
- Encounter with 20 actors: complete 10 rounds in <100ms
- State query operations: <1ms response time
- Support for concurrent independent encounters without performance degradation

**Constraints**: 
- Zero external dependencies (stdlib only) for maximum portability
- Immutable timeline profiles (configured before encounter, cannot change mid-encounter)
- Thread-safe design not required (PHP single-threaded model)
- No persistent storage layer (consumers manage serialization/persistence)

**Scale/Scope**: 
- Support 5-6 different turn order models via timeline profile configuration
- Handle encounters with 50+ actors without performance issues
- Enable multiple concurrent encounters operating independently
- Support all major RPG systems (D&D all editions, Pathfinder, Shadowrun, GURPS, Savage Worlds, Genesys)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Composer Package Standards
- [x] Feature design compatible with Composer package structure (PSR-4 autoloading)
- [x] Dependencies identified and version constraints defined (zero dependencies)
- [x] No changes that break semantic versioning contract (new library, v1.0.0 target)

### PSR-12 Coding Standards
- [x] Code style enforcement configured (php-cs-fixer required)
- [x] All new code will follow PSR-12 specification
- [x] `.php-cs-fixer.php` to be created in Phase 2

### Test-Driven Development
- [x] Test scenarios defined BEFORE implementation (comprehensive acceptance scenarios in spec.md)
- [x] Red-Green-Refactor cycle planned (tasks.md will define per-story TDD cycles)
- [x] Stakeholder approval obtained for acceptance criteria (clarification session completed 2026-01-08)

### PHPUnit Testing Coverage
- [x] PHPUnit test strategy defined (unit tests for all classes, integration tests for workflow scenarios)
- [x] Tests executable via `composer test` (standard composer.json script configuration)
- [x] Coverage target: 90% minimum for new code (enforced via phpunit.xml)
- [x] `phpunit.xml` configuration to be created in Phase 2

### Complete Documentation
- [x] README.md updates planned (complete library documentation with installation, quickstart, examples)
- [x] PHPDoc comments required for all public methods (enforced in code review)
- [x] Usage examples included in feature documentation (quickstart.md in Phase 1)
- [x] CHANGELOG.md entry planned for v1.0.0 release

**GATE STATUS**: ✅ PASS - All constitutional requirements satisfied, no violations to justify

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
/workspaces/phpturntracker/
├── src/
│   ├── Encounter.php                    # Main encounter coordinator
│   ├── Actor.php                        # Participant in encounter
│   ├── TimelineProfile.php              # Turn order configuration
│   ├── TurnOrder/
│   │   ├── TurnOrderInterface.php       # Strategy interface
│   │   ├── RoundBasedIndividual.php     # D&D-style initiative
│   │   ├── RoundBasedSide.php           # OSR side-based initiative
│   │   ├── PassBased.php                # Shadowrun pass system
│   │   ├── SlotBased.php                # Genesys slot initiative
│   │   └── Popcorn.php                  # Narrative popcorn initiative
│   ├── State/
│   │   ├── EncounterState.php           # Current round/pass/actor tracking
│   │   └── ActorState.php               # Acted/unacted status per actor
│   ├── Validators/
│   │   ├── TimelineProfileValidator.php  # Profile configuration validation
│   │   └── InitiativeValidator.php       # Initiative bounds checking
│   └── Exceptions/
│       ├── InvalidTimelineProfileException.php
│       ├── InvalidInitiativeException.php
│       ├── EncounterNotActiveException.php
│       └── ActorNotFoundException.php
├── tests/
│   ├── Unit/
│   │   ├── ActorTest.php
│   │   ├── TimelineProfileTest.php
│   │   ├── TurnOrder/
│   │   │   ├── RoundBasedIndividualTest.php
│   │   │   ├── RoundBasedSideTest.php
│   │   │   ├── PassBasedTest.php
│   │   │   ├── SlotBasedTest.php
│   │   │   └── PopcornTest.php
│   │   └── Validators/
│   │       ├── TimelineProfileValidatorTest.php
│   │       └── InitiativeValidatorTest.php
│   ├── Integration/
│   │   ├── BasicTurnProgressionTest.php      # User Story 1
│   │   ├── DynamicActorManagementTest.php    # User Story 2
│   │   ├── InitiativeChangesTest.php         # User Story 3
│   │   ├── MultiPassSystemTest.php           # User Story 4
│   │   ├── AlternativeTurnOrderTest.php      # User Story 5
│   │   └── SideBasedInitiativeTest.php       # User Story 6
│   └── Fixtures/
│       ├── TimelineProfiles.php               # Predefined profiles for testing
│       └── ActorFactory.php                   # Test data builders
├── composer.json
├── phpunit.xml
├── .php-cs-fixer.php
├── README.md
├── CHANGELOG.md
└── LICENSE
```

**Structure Decision**: Single Composer package following PSR-4 autoloading with `Codryn\\PhpTurnTracker` namespace. Strategy pattern for turn order implementations allows clean separation of different RPG system behaviors while maintaining consistent Encounter API. State objects manage temporal data (round/pass/acted status) separately from entity models (Actor, TimelineProfile).

## Complexity Tracking

> **No violations - section not applicable**

All constitutional requirements are satisfied without exceptions. The library design aligns with standard Composer package patterns, follows established PHP testing practices, and requires no architectural compromises.

---

## Phase Execution Summary

### Phase 0: Outline & Research ✅ COMPLETE

**Objective**: Resolve all "NEEDS CLARIFICATION" items from Technical Context

**Deliverables**:
- ✅ `research.md` - Comprehensive research document covering:
  - Turn order model classification (5 strategies, tick-based deferred)
  - PHP 8.0+ compatibility strategy and constraints
  - Initiative change & delay mechanics
  - Pass-based initiative decay configuration
  - Tie-breaking strategy (default + custom)
  - Initiative bounds validation (optional min/max)
  - Popcorn initiative repeat designation handling
  - State management architecture (immutable entities, mutable state)
  - Zero-dependency design rationale
  - Testing strategy (unit + integration)

**Key Decisions Made**:
1. Implement 5 turn order strategies via Strategy pattern
2. Develop on PHP 8.3, maintain 8.0+ compatibility
3. Preserve acted/unacted status during initiative changes
4. Configurable decay amount for pass-based systems
5. Default tie-breaker: stable insertion order
6. Optional initiative bounds in TimelineProfile
7. Optional allowRepeatPopcorn flag for narrative flexibility
8. Separate mutable state from immutable entities
9. Zero external dependencies for maximum portability
10. Unit tests per class + Integration tests per user story

**Research Quality**: All unknowns from Technical Context resolved with clear rationale and implementation approach

---

### Phase 1: Design & Contracts ✅ COMPLETE

**Objective**: Generate data model, API contracts, and quickstart guide

**Deliverables**:
- ✅ `data-model.md` - Complete entity definitions:
  - Actor (immutable participant)
  - TimelineProfile (immutable configuration)
  - TurnOrderType (enumeration-style constants)
  - Encounter (main coordinator)
  - EncounterState (mutable temporal state)
  - ActorState (mutable per-actor state)
  - TurnOrderInterface (strategy contract)
  - Validation rules, state transitions, relationships
  
- ✅ `contracts/encounter-api.md` - Public API specification:
  - All public methods with parameters, returns, exceptions
  - 5 complete usage examples (D&D, Shadowrun, Delay, Popcorn, Side-based)
  - Error handling hierarchy
  - Thread safety guarantees
  
- ✅ `quickstart.md` - User-friendly tutorial:
  - 5-minute getting started guide
  - Common scenarios (reinforcements, delays, initiative changes)
  - RPG system examples (D&D, SR4, SR5, Genesys, Marvel, OSR)
  - Best practices and troubleshooting
  - Integration patterns (web, database, logging)

- ✅ Agent context updated:
  - GitHub Copilot instructions updated with PHP 8.0-8.3, zero-dependency, library pattern

**Design Quality**: 
- Clear separation of concerns (entities vs state)
- Strategy pattern for extensible turn order models
- Immutable configuration prevents mid-encounter rule changes
- Exception hierarchy provides actionable error messages
- API examples cover all 6 user stories from spec

---

### Phase 2: Implementation Planning (NEXT STEP)

**Objective**: Generate tasks.md with prioritized, testable work items

**Command**: `/speckit.tasks` (separate command, not part of /speckit.plan)

**Planned Approach**:
- Break down each user story into TDD cycles (Red-Green-Refactor)
- Priority 1: User Stories 1-2 (Basic turn progression, dynamic actors)
- Priority 2: User Stories 3-4 (Initiative changes, pass-based)
- Priority 3: User Stories 5-6 (Alternative systems, side-based)
- Each task: Write test → Implement minimal code → Refactor
- Infrastructure tasks: composer.json, phpunit.xml, .php-cs-fixer.php

---

## Post-Design Constitution Re-Check ✅ PASS

All checkboxes from initial constitution check remain valid:
- Design maintains Composer package structure
- Zero dependencies confirmed in research
- TDD approach fully planned with test → implement → refactor cycles
- PHPUnit strategy covers unit + integration testing
- Documentation complete (README planned, quickstart delivered, API documented)

**No new violations introduced during design phase**

---

## Artifacts Generated

| File | Purpose | Status |
|------|---------|--------|
| `plan.md` | This file - overall implementation plan | ✅ Complete |
| `research.md` | Phase 0 decisions and rationale | ✅ Complete |
| `data-model.md` | Entity definitions and relationships | ✅ Complete |
| `contracts/encounter-api.md` | Public API specification | ✅ Complete |
| `quickstart.md` | User tutorial and examples | ✅ Complete |
| `.github/agents/copilot-instructions.md` | AI agent context | ✅ Updated |
| `tasks.md` | Implementation tasks (Phase 2) | ⏳ Pending /speckit.tasks |

---

## Ready for Implementation

**Prerequisites Met**:
- ✅ All clarifications resolved (5 Q&As documented in spec.md)
- ✅ Technical approach researched and decided
- ✅ Data model designed with clear entity boundaries
- ✅ Public API specified with examples
- ✅ User documentation drafted
- ✅ Constitutional compliance verified twice (pre/post design)

**Next Command**: `/speckit.tasks` to generate prioritized implementation tasks with TDD cycles

**Estimated Effort**:
- Core entities & state (Actor, TimelineProfile, State objects): 2-3 days
- Strategy implementations (5 turn order types): 3-4 days
- Encounter coordinator & API: 2-3 days
- Validation & error handling: 1-2 days
- Integration tests (6 user stories): 2-3 days
- Documentation & polish: 1-2 days
- **Total**: ~12-17 development days

**Package Publication Checklist** (post-implementation):
- [ ] composer.json configured with `codryn/phpturntracker`
- [ ] README.md with installation and usage
- [ ] LICENSE file (MIT/BSD recommended for libraries)
- [ ] CHANGELOG.md for v1.0.0
- [ ] GitHub repository created
- [ ] Packagist.org registration
- [ ] GitHub Actions CI for PHP 8.0, 8.1, 8.2, 8.3
- [ ] PHPStan/Psalm static analysis passing
- [ ] PHPUnit coverage ≥90%
- [ ] PSR-12 compliance verified
- [ ] Tag v1.0.0 release

