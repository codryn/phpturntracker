# Tasks: RPG Turn Order Tracker Library

**Input**: Design documents from `/specs/001-rpg-turn-tracker/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Tests**: Included - This feature follows strict TDD (Test-Driven Development) per constitution

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 Create composer.json with package metadata: name=codryn/phpturntracker, PHP ^8.0 requirement, PSR-4 autoloading (Codryn\\PhpTurnTracker), PHPUnit dev dependency
- [ ] T002 Create phpunit.xml with test suite configuration, 90% coverage requirement, bootstrap, strict mode
- [ ] T003 Create .php-cs-fixer.php with PSR-12 rules configuration
- [ ] T004 [P] Create .gitignore with vendor/, .phpunit.cache/, coverage/, .php-cs-fixer.cache
- [ ] T005 [P] Create LICENSE file (MIT recommended for libraries)
- [ ] T006 [P] Create CHANGELOG.md with v1.0.0 section placeholder
- [ ] T007 [P] Create directory structure: src/, src/TurnOrder/, src/State/, src/Validators/, src/Exceptions/, tests/Unit/, tests/Integration/, tests/Fixtures/
- [ ] T008 Run composer install to verify setup

**Checkpoint**: Project structure ready, composer autoloading works

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T009 [P] Create base exception PhpTurnTrackerException in src/Exceptions/PhpTurnTrackerException.php
- [ ] T010 [P] Create TurnOrderType class with constants (ROUND_INDIVIDUAL, ROUND_SIDE, PASS, SLOT, POPCORN) in src/TurnOrderType.php
- [ ] T011 [P] Create test for TurnOrderType in tests/Unit/TurnOrderTypeTest.php (validate all constants, isValid() method)
- [ ] T012 Create Actor entity in src/Actor.php with constructor promotion (id, name, initiative, attributes), getters, PHPDoc
- [ ] T013 Create test for Actor in tests/Unit/ActorTest.php (construction, getters, attributes)
- [ ] T014 Create ActorState in src/State/ActorState.php with hasActed tracking, passesRemaining for pass-based
- [ ] T015 Create test for ActorState in tests/Unit/State/ActorStateTest.php (state transitions)
- [ ] T016 Create EncounterState in src/State/EncounterState.php with round, pass, currentActorId, isActive tracking
- [ ] T017 Create test for EncounterState in tests/Unit/State/EncounterStateTest.php (state mutations, round/pass increments)
- [ ] T018 [P] Create all exception classes in src/Exceptions/: InvalidTimelineProfileException, InvalidInitiativeException, EncounterNotActiveException, EncounterAlreadyActiveException, ActorNotFoundException, DuplicateActorException, NoActorsException, ActorAlreadyActedException, InvalidDelayException, InvalidDesignationException, InvalidTurnOrderTypeException
- [ ] T019 Create TurnOrderInterface in src/TurnOrder/TurnOrderInterface.php with strategy contract methods (calculateInitialOrder, getNextActor, addActor, removeActor, changeInitiative, shouldAdvanceRound, shouldAdvancePass)
- [ ] T020 Create TimelineProfile in src/TimelineProfile.php with all configuration properties (type, min/max initiative, tie-breaker, pass config, popcorn config, slot config), getters, validate() method
- [ ] T021 Create test for TimelineProfile in tests/Unit/TimelineProfileTest.php (construction, validation of invalid configs)
- [ ] T022 Create InitiativeValidator in src/Validators/InitiativeValidator.php with bounds checking logic
- [ ] T023 Create test for InitiativeValidator in tests/Unit/Validators/InitiativeValidatorTest.php (boundary cases, unbounded)
- [ ] T024 Create TimelineProfileValidator in src/Validators/TimelineProfileValidator.php with profile validation logic
- [ ] T025 Create test for TimelineProfileValidator in tests/Unit/Validators/TimelineProfileValidatorTest.php (all validation rules)
- [ ] T026 Create ActorFactory fixture in tests/Fixtures/ActorFactory.php for test data generation
- [ ] T027 Create TimelineProfiles fixture in tests/Fixtures/TimelineProfiles.php with D&D5e, Shadowrun4, Shadowrun5, OSR, Genesys, Popcorn profiles

**Checkpoint**: Foundation ready - all core entities, state objects, validators tested. User story implementation can now begin in parallel.

---

## Phase 3: User Story 1 - Basic Turn Progression (Priority: P1) 🎯 MVP

**Goal**: Enable D&D-style combat with individual initiative, round-based turn order, and acted/unacted tracking

**Independent Test**: Create encounter with 4 actors, start, advance through multiple rounds, verify correct turn order and round increments

### Tests for User Story 1 ✅ RED PHASE

> **TDD: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T028 [P] [US1] Integration test for US1 Scenario 1 in tests/Integration/BasicTurnProgressionTest.php: start encounter, verify first actor current
- [ ] T029 [P] [US1] Integration test for US1 Scenario 2: advance turn, verify next actor current and previous marked acted
- [ ] T030 [P] [US1] Integration test for US1 Scenario 3: complete round, verify round increment and acted status reset
- [ ] T031 [P] [US1] Integration test for US1 Scenario 4: getCurrentActor() returns correct actor
- [ ] T032 [P] [US1] Integration test for US1 Scenario 5: query tracker state (round number, acted/unacted lists)

### Implementation for User Story 1 ✅ GREEN PHASE

- [ ] T033 [US1] Implement RoundBasedIndividual strategy in src/TurnOrder/RoundBasedIndividual.php: calculateInitialOrder (sort by initiative desc, stable order for ties), getNextActor (cycle through sorted list), shouldAdvanceRound
- [ ] T034 [US1] Create unit test for RoundBasedIndividual in tests/Unit/TurnOrder/RoundBasedIndividualTest.php (sorting, cycling, tie-breaking)
- [ ] T035 [US1] Implement Encounter class in src/Encounter.php: constructor with TimelineProfile, start() method, state tracking, strategy delegation
- [ ] T036 [US1] Implement Encounter::addActor() with validation, state creation
- [ ] T037 [US1] Implement Encounter::getCurrentActor() returning current Actor or null
- [ ] T038 [US1] Implement Encounter::advanceTurn() with actor state updates, next actor calculation, round advancement logic
- [ ] T039 [US1] Implement Encounter::getCurrentRound(), isActive(), getActedActors(), getUnactedActors() query methods
- [ ] T040 [US1] Run integration tests - should now PASS ✅

### Refactor for User Story 1 ✅ REFACTOR PHASE

- [ ] T041 [US1] Add PHPDoc comments to all Encounter public methods
- [ ] T042 [US1] Extract tie-breaker logic to private method in RoundBasedIndividual
- [ ] T043 [US1] Run php-cs-fixer to enforce PSR-12 compliance

**Checkpoint**: User Story 1 complete - D&D-style combat works end-to-end, independently testable

---

## Phase 4: User Story 2 - Dynamic Actor Management (Priority: P1)

**Goal**: Support adding reinforcements mid-combat and removing defeated actors while maintaining turn order

**Independent Test**: Start encounter with 3 actors, add 2 actors mid-combat, remove 1 actor, verify turn order remains correct

### Tests for User Story 2 ✅ RED PHASE

- [ ] T044 [P] [US2] Integration test for US2 Scenario 1 in tests/Integration/DynamicActorManagementTest.php: add actor mid-round with high initiative, verify won't act until next round
- [ ] T045 [P] [US2] Integration test for US2 Scenario 2: add actor mid-round with low initiative, verify acts later this round if position not passed
- [ ] T046 [P] [US2] Integration test for US2 Scenario 3: remove actor who already acted, verify no disruption
- [ ] T047 [P] [US2] Integration test for US2 Scenario 4: remove current actor, verify turn advances immediately
- [ ] T048 [P] [US2] Integration test for US2 Scenario 5: remove unacted actor, verify turn skipped and removed from future turns

### Implementation for User Story 2 ✅ GREEN PHASE

- [ ] T049 [US2] Implement RoundBasedIndividual::addActor() with mid-round position calculation
- [ ] T050 [US2] Implement RoundBasedIndividual::removeActor() with current actor handling
- [ ] T051 [US2] Implement Encounter::removeActor() with validation and current actor advance logic
- [ ] T052 [US2] Add duplicate actor ID validation to Encounter::addActor()
- [ ] T053 [US2] Handle ActorNotFoundException in removeActor
- [ ] T054 [US2] Run integration tests - should now PASS ✅

### Refactor for User Story 2 ✅ REFACTOR PHASE

- [ ] T055 [US2] Extract actor position calculation logic to helper method
- [ ] T056 [US2] Add comprehensive unit tests for edge cases (remove last actor, add to empty encounter)
- [ ] T057 [US2] Run php-cs-fixer

**Checkpoint**: User Stories 1 AND 2 both work independently - dynamic combat fully functional

---

## Phase 5: User Story 3 - Initiative Changes and Delays (Priority: P2)

**Goal**: Handle tactical options like delayed actions and initiative modifications while preserving acted/unacted fairness

**Independent Test**: Create encounter, advance partway through round, delay actor's initiative, change another actor's initiative, verify acted status preserved

### Tests for User Story 3 ✅ RED PHASE

- [ ] T058 [P] [US3] Integration test for US3 Scenario 1 in tests/Integration/InitiativeChangesTest.php: delay actor to lower initiative, verify moved position but remains unacted, turn advances to next
- [ ] T059 [P] [US3] Integration test for US3 Scenario 2: reduce acted actor's initiative, verify position changes but remains acted this round
- [ ] T060 [P] [US3] Integration test for US3 Scenario 3: delayed actor's status resets properly in next round
- [ ] T061 [P] [US3] Integration test for US3 Scenario 4: increase unacted actor's initiative past current position, verify skipped this round

### Implementation for User Story 3 ✅ GREEN PHASE

- [ ] T062 [US3] Implement RoundBasedIndividual::changeInitiative() with re-sorting, acted status preservation
- [ ] T063 [US3] Implement Encounter::changeInitiative() with validation and strategy delegation
- [ ] T064 [US3] Implement Encounter::delayActor() with preconditions (must not have acted, initiative must decrease)
- [ ] T065 [US3] Add InvalidDelayException throwing for invalid delay attempts
- [ ] T066 [US3] Add ActorAlreadyActedException for delayed actor who already acted
- [ ] T067 [US3] Run integration tests - should now PASS ✅

### Refactor for User Story 3 ✅ REFACTOR PHASE

- [ ] T068 [US3] Extract initiative change validation to InitiativeValidator
- [ ] T069 [US3] Add unit tests for initiative change edge cases
- [ ] T070 [US3] Run php-cs-fixer

**Checkpoint**: User Stories 1, 2, AND 3 all work independently - tactical combat options functional

---

## Phase 6: User Story 4 - Multi-Pass System Support (Priority: P2)

**Goal**: Enable Shadowrun-style combat with multiple passes per round, initiative decay, and per-pass actor eligibility

**Independent Test**: Configure 3-pass timeline, create actors with different pass counts, advance through full round with decay, verify correct pass progression

### Tests for User Story 4 ✅ RED PHASE

- [ ] T071 [P] [US4] Integration test for US4 Scenario 1 in tests/Integration/MultiPassSystemTest.php: start pass-based encounter, verify all actors available in pass 1
- [ ] T072 [P] [US4] Integration test for US4 Scenario 2: advance to pass 2, verify only actors with 2+ passes available
- [ ] T073 [P] [US4] Integration test for US4 Scenario 3: advance to pass 3, verify only actors with 3 passes available
- [ ] T074 [P] [US4] Integration test for US4 Scenario 4: complete all passes, verify round increment and reset
- [ ] T075 [P] [US4] Integration test for US4 Scenario 5: verify initiative decay applied correctly each pass

### Implementation for User Story 4 ✅ GREEN PHASE

- [ ] T076 [US4] Implement PassBased strategy in src/TurnOrder/PassBased.php: calculateInitialOrder with pass calculation, getNextActor with pass eligibility, shouldAdvancePass, initiative decay logic
- [ ] T077 [US4] Create unit test for PassBased in tests/Unit/TurnOrder/PassBasedTest.php (pass tracking, decay, eligibility)
- [ ] T078 [US4] Add pass-specific properties to TimelineProfile (passesPerRound, decayEnabled, decayAmount)
- [ ] T079 [US4] Update TimelineProfileValidator for pass-based validation rules
- [ ] T080 [US4] Add getCurrentPass() method to Encounter for pass-based encounters
- [ ] T081 [US4] Update Encounter::advanceTurn() to handle pass advancement and decay application
- [ ] T082 [US4] Update ActorState to track passesRemaining for pass-based systems
- [ ] T083 [US4] Run integration tests - should now PASS ✅

### Refactor for User Story 4 ✅ REFACTOR PHASE

- [ ] T084 [US4] Extract pass eligibility calculation to helper method
- [ ] T085 [US4] Add unit tests for decay edge cases (decay to negative, decay with bounds)
- [ ] T086 [US4] Run php-cs-fixer

**Checkpoint**: User Stories 1-4 all work independently - Shadowrun-style combat functional

---

## Phase 7: User Story 5 - Alternative Turn Order Systems (Priority: P3)

**Goal**: Support Genesys slot-based and Marvel Heroic popcorn initiative for narrative-focused systems

**Independent Test**: Test slot-based with 2 PC slots and 3 NPC slots; test popcorn with designation mechanics

### Tests for User Story 5 ✅ RED PHASE

- [ ] T087 [P] [US5] Integration test for US5 Scenario 1 in tests/Integration/AlternativeTurnOrderTest.php: slot-based encounter with typed slots, verify any actor of matching type can fill slot
- [ ] T088 [P] [US5] Integration test for US5 Scenario 2: popcorn encounter, verify current actor can designate next
- [ ] T089 [P] [US5] Integration test for US5 Scenario 3: slot-based with actor chosen for slot, verify marked acted
- [ ] T090 [P] [US5] Integration test for US5 Scenario 4: popcorn with 4/5 acted, verify last unacted automatically current
- [ ] T091 [P] [US5] Integration test for US5 Scenario 5: popcorn or slot-based round completion, verify reset
- [ ] T092 [P] [US5] Integration test for US5 Scenario 6: popcorn with allowRepeatPopcorn=true, verify can re-designate acted actor

### Implementation for User Story 5 ✅ GREEN PHASE

- [ ] T093 [P] [US5] Implement SlotBased strategy in src/TurnOrder/SlotBased.php: slot generation from initiative, actor-to-slot matching, slot filling logic
- [ ] T094 [P] [US5] Create unit test for SlotBased in tests/Unit/TurnOrder/SlotBasedTest.php
- [ ] T095 [P] [US5] Implement Popcorn strategy in src/TurnOrder/Popcorn.php: designation tracking, automatic last-actor selection, repeat designation handling
- [ ] T096 [P] [US5] Create unit test for Popcorn in tests/Unit/TurnOrder/PopcornTest.php
- [ ] T097 [US5] Add slotConfiguration property to TimelineProfile for slot-based
- [ ] T098 [US5] Add allowRepeatPopcorn property to TimelineProfile for popcorn
- [ ] T099 [US5] Implement Encounter::designateNext() for popcorn initiative
- [ ] T100 [US5] Add InvalidDesignationException for invalid popcorn designations
- [ ] T101 [US5] Update TimelineProfileValidator for slot and popcorn validation rules
- [ ] T102 [US5] Run integration tests - should now PASS ✅

### Refactor for User Story 5 ✅ REFACTOR PHASE

- [ ] T103 [US5] Extract slot matching logic to helper method
- [ ] T104 [US5] Add unit tests for popcorn edge cases (designate self, designate nonexistent)
- [ ] T105 [US5] Run php-cs-fixer

**Checkpoint**: User Stories 1-5 all work independently - Narrative system support functional

---

## Phase 8: User Story 6 - Side-Based Initiative (Priority: P3)

**Goal**: Support B/X D&D and OSR side-based initiative where entire teams act together

**Independent Test**: Configure side-based profile, create actors with side attributes, verify all side members act before other side

### Tests for User Story 6 ✅ RED PHASE

- [ ] T106 [P] [US6] Integration test for US6 Scenario 1 in tests/Integration/SideBasedInitiativeTest.php: side-based encounter, verify entire first side available
- [ ] T107 [P] [US6] Integration test for US6 Scenario 2: all first side acted, verify second side becomes current
- [ ] T108 [P] [US6] Integration test for US6 Scenario 3: second side completes, verify round increment and reset
- [ ] T109 [P] [US6] Integration test for US6 Scenario 4: add actor to active side mid-round, verify can act immediately

### Implementation for User Story 6 ✅ GREEN PHASE

- [ ] T110 [US6] Implement RoundBasedSide strategy in src/TurnOrder/RoundBasedSide.php: group actors by side attribute, side-level turn tracking
- [ ] T111 [US6] Create unit test for RoundBasedSide in tests/Unit/TurnOrder/RoundBasedSideTest.php
- [ ] T112 [US6] Update Encounter to handle side-based current state (current side vs current actor)
- [ ] T113 [US6] Update getCurrentActor() to return null or any unacted actor of current side for side-based
- [ ] T114 [US6] Run integration tests - should now PASS ✅

### Refactor for User Story 6 ✅ REFACTOR PHASE

- [ ] T115 [US6] Add unit tests for side-based edge cases (single side, no side attribute)
- [ ] T116 [US6] Run php-cs-fixer

**Checkpoint**: All 6 user stories complete and independently functional - Full RPG system coverage achieved

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories and production readiness

- [ ] T117 [P] Create README.md with: installation instructions, quick start example, supported systems table, link to quickstart.md
- [ ] T118 [P] Create examples/ directory with complete code examples for each RPG system (D&D5e, SR4, SR5, Genesys, Popcorn, OSR)
- [ ] T119 [P] Update CHANGELOG.md with v1.0.0 features, breaking changes (none), migration guide
- [ ] T120 [P] Add GitHub Actions workflow .github/workflows/ci.yml for PHP 8.0, 8.1, 8.2, 8.3 matrix testing
- [ ] T121 [P] Add PHPStan configuration phpstan.neon at level max
- [ ] T122 Run PHPStan analysis, fix any issues
- [ ] T123 Run PHPUnit with coverage report, verify ≥90% coverage
- [ ] T124 Run php-cs-fixer on entire codebase
- [ ] T125 Review all PHPDoc comments for completeness and accuracy
- [ ] T126 Manual test: run through quickstart.md examples step-by-step, verify all work
- [ ] T127 [P] Add composer scripts: "test" (phpunit), "coverage" (phpunit with coverage), "cs-fix" (php-cs-fixer), "analyze" (phpstan)
- [ ] T128 Final review: verify all acceptance scenarios from spec.md pass

**Checkpoint**: Production ready - all quality gates passed, documentation complete

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately ✅
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories ⚠️
- **User Stories (Phase 3-8)**: All depend on Foundational phase completion
  - Can proceed in parallel if multiple developers available
  - Or sequentially in priority order: US1 → US2 → US3 → US4 → US5 → US6
- **Polish (Phase 9)**: Depends on all desired user stories being complete

### User Story Dependencies

- **US1 (Basic Turn Progression)**: Independent - Core functionality, no dependencies on other stories ✅
- **US2 (Dynamic Actors)**: Independent - Extends US1 but testable separately ✅
- **US3 (Initiative Changes)**: Independent - Uses US1 actors but tests initiative mechanics separately ✅
- **US4 (Multi-Pass)**: Independent - Different strategy, testable separately ✅
- **US5 (Alternative Systems)**: Independent - New strategies, testable separately ✅
- **US6 (Side-Based)**: Independent - New strategy, testable separately ✅

**Key Insight**: All user stories are independently deliverable after Foundational phase completes!

### TDD Cycle Within Each User Story

1. **RED**: Write integration tests for acceptance scenarios - tests MUST fail ❌
2. **GREEN**: Implement minimal code to make tests pass - tests now pass ✅
3. **REFACTOR**: Clean up code, add unit tests, enforce PSR-12 ♻️
4. **VERIFY**: Run full test suite, confirm user story works independently ✅

### Parallel Opportunities

**Setup Phase**: T001-T008 can all run in parallel (different files)

**Foundational Phase**: 
- T009-T011 (TurnOrderType) || T012-T013 (Actor) || T014-T015 (ActorState) can run in parallel
- T016-T017 (EncounterState) || T018 (Exceptions) can run in parallel
- T022-T023 (InitiativeValidator) || T024-T025 (TimelineProfileValidator) can run in parallel
- T026-T027 (Fixtures) can run in parallel

**User Story Tests**: All test tasks within a story marked [P] can run in parallel (e.g., T028-T032 for US1)

**User Story Implementations**: Multiple developers can work on different user stories simultaneously after Foundational completes

**Polish Phase**: T117-T121 can all run in parallel (documentation, examples, CI config)

---

## Parallel Example: User Story 1

```bash
# RED PHASE - Launch all integration tests together (will fail initially):
$ # T028: Test start encounter
$ # T029: Test advance turn
$ # T030: Test round completion
$ # T031: Test getCurrentActor
$ # T032: Test state queries
# All 5 tests can be written in parallel by different devs

# GREEN PHASE - Sequential implementation:
$ # T033: Implement RoundBasedIndividual strategy (core logic)
$ # T034: Unit test strategy
$ # T035-T039: Implement Encounter methods (depends on T033)
$ # T040: Run integration tests - should pass now ✅

# REFACTOR PHASE - Parallel cleanup:
$ # T041: PHPDoc || T042: Extract helper || T043: PSR-12
```

---

## Parallel Example: Multiple User Stories

```bash
# After Foundational Phase (T001-T027) completes:

# Developer A focuses on US1 (T028-T043):
$ cd tests/Integration && touch BasicTurnProgressionTest.php
$ # Implements all US1 tasks sequentially

# Developer B focuses on US2 (T044-T057):
$ cd tests/Integration && touch DynamicActorManagementTest.php
$ # Implements all US2 tasks sequentially

# Developer C focuses on US4 (T071-T086):
$ cd src/TurnOrder && touch PassBased.php
$ # Implements all US4 tasks sequentially

# All three user stories can progress in parallel without conflicts!
```

---

## Implementation Strategy

### MVP First (User Stories 1-2 Only)

**Target**: Basic D&D-style combat with dynamic actors

1. Complete Phase 1: Setup (T001-T008) - ~1 day
2. Complete Phase 2: Foundational (T009-T027) - ~3-4 days
3. Complete Phase 3: User Story 1 (T028-T043) - ~2-3 days
4. Complete Phase 4: User Story 2 (T044-T057) - ~1-2 days
5. **STOP and VALIDATE**: Test US1+US2 work together
6. Complete Phase 9: Polish essentials (README, basic docs) - ~1 day
7. **Deploy MVP v0.1.0** - Covers 90% of D&D/Pathfinder use cases

**Total MVP Effort**: ~8-12 days

### Incremental Delivery

1. **v0.1.0 (MVP)**: US1 + US2 → Basic individual initiative ✅
2. **v0.2.0**: Add US3 → Tactical options (delays, initiative changes) ✅
3. **v0.3.0**: Add US4 → Shadowrun support ✅
4. **v0.4.0**: Add US5 → Narrative systems (Genesys, Marvel) ✅
5. **v0.5.0**: Add US6 → OSR support ✅
6. **v1.0.0**: Polish + comprehensive docs + production hardening ✅

Each version adds value without breaking previous functionality!

### Parallel Team Strategy

With 3 developers after Foundational (T027) completes:

- **Dev A**: US1 (T028-T043) → US3 (T058-T070) → Documentation
- **Dev B**: US2 (T044-T057) → US5 (T087-T105) → Examples
- **Dev C**: US4 (T071-T086) → US6 (T106-T116) → CI/Testing

**Result**: All user stories complete in ~6-8 days instead of 12-17 days sequential

---

## Quality Gates

### Per User Story

- [ ] All integration tests pass for acceptance scenarios ✅
- [ ] Unit tests cover strategy implementation ✅
- [ ] PHPDoc comments on all public methods ✅
- [ ] PSR-12 compliance verified ✅
- [ ] Story works independently without other stories ✅

### Final (Before v1.0.0)

- [ ] PHPUnit coverage ≥90% across entire codebase ✅
- [ ] PHPStan level max passes with no errors ✅
- [ ] CI pipeline passes on PHP 8.0, 8.1, 8.2, 8.3 ✅
- [ ] All 6 user stories work independently ✅
- [ ] All edge cases from spec.md handled ✅
- [ ] README.md complete with examples ✅
- [ ] quickstart.md examples verified working ✅
- [ ] No TODOs or FIXMEs in production code ✅

---

## Notes

- **[P] markers**: Tasks marked [P] touch different files and have no dependencies, can run in parallel
- **[Story] labels**: Map tasks to user stories for traceability and independent validation
- **TDD mandatory**: Per constitution, tests must be written before implementation
- **Independent stories**: Each user story must be completable and testable without depending on other stories
- **Commit frequently**: Commit after each task or logical group for easy rollback
- **Validate at checkpoints**: Stop at each checkpoint to verify story works independently
- **Zero dependencies**: Remember - stdlib only, no external packages except dev dependencies (PHPUnit, php-cs-fixer)

---

## Success Metrics

**MVP (US1+US2)**: Functional D&D combat tracker ready for publication as v0.1.0

**Full Feature Set (US1-US6)**: Supports all major RPG systems (D&D, Pathfinder, Shadowrun, GURPS, Savage Worlds, Feng Shui, Genesys, Marvel Heroic, OSR)

**Quality**: 90%+ test coverage, PSR-12 compliant, PHP 8.0-8.3 compatible, zero dependencies, production-ready documentation

**Timeline**: 8-12 days for MVP, 12-17 days for full v1.0.0 (sequential), 6-10 days (parallel with 3 devs)
