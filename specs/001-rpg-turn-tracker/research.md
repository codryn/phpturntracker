# Phase 0: Research & Decisions

**Feature**: RPG Turn Order Tracker Library  
**Date**: 2026-01-08  
**Status**: Complete

## Research Tasks Completed

### 1. Turn Order Model Classification

**Decision**: Implement 5 distinct turn order strategies, excluding tick-based (deferred to V2)

**Rationale**: 
- The 5 core models (round-based individual, round-based side, pass-based, slot-based, popcorn) cover ~95% of major RPG systems in active use
- Each model has fundamentally different state management requirements:
  - **Round-based individual**: Simple sorted list with round counter
  - **Round-based side**: Group actors by faction, single initiative per side
  - **Pass-based**: Multi-dimensional tracking (round + pass + per-pass eligibility)
  - **Slot-based**: Initiative determines slot types, not specific actors
  - **Popcorn**: Current actor designates next, requires designation API
- Tick-based systems (GURPS, Exalted) use fundamentally different time models (continuous timeline vs discrete turns) and were explicitly deferred per clarification session
- Strategy pattern provides clean abstraction for these different behaviors

**Alternatives considered**:
- Single unified algorithm: Rejected - too complex, mixing incompatible concerns
- Plugin/extension system: Rejected - over-engineering for known, finite set of models
- Include tick-based now: Rejected - significantly increases scope and complexity

**Implementation approach**: `TurnOrderInterface` with 5 concrete strategies, selected via `TimelineProfile->type` property

---

### 2. PHP 8.0+ Compatibility Strategy

**Decision**: Develop on PHP 8.3, test against PHP 8.0, 8.1, 8.2, 8.3 in CI

**Rationale**:
- PHP 8.0 introduced union types, named arguments, constructor property promotion - modern features that improve code quality
- PHP 8.0 EOL was November 2023, but many enterprise environments still use it
- Target compatibility window balances modern features vs market reach
- Avoid PHP 8.1+ features: enums (8.1), readonly properties (8.1), DNF types (8.2)

**Compatibility constraints**:
- ❌ No enums (use constants or class-based enumerations)
- ❌ No readonly keyword (use private properties with public getters)
- ❌ No true standalone type (8.2)
- ✅ Can use union types, mixed type, nullsafe operator, constructor promotion
- ✅ Can use attributes (but not required for core functionality)

**Testing approach**: 
- GitHub Actions matrix testing: PHP 8.0, 8.1, 8.2, 8.3
- Composer require: `"php": "^8.0"`
- Development environment: PHP 8.3

---

### 3. Initiative Change & Delay Mechanics

**Decision**: Preserve acted/unacted status during initiative changes; treat delays as initiative reductions to new position

**Rationale**:
- Fairness principle: An actor who has acted shouldn't get another turn just because their initiative changed
- Delay = "I go later" = reduce initiative to new value, preserve unacted status
- Initiative increase/decrease from external effects (spells, conditions) should only affect turn order, not grant extra actions

**Technical implementation**:
```php
// When initiative changes mid-round:
// 1. Update actor's initiative score
// 2. Resort turn order
// 3. Preserve actor's acted/unacted flag for current round
// 4. If initiative increase moves actor past current position, they've "missed" their turn this round

// Delay action:
// 1. Reduce initiative to new value (validated against bounds)
// 2. Resort turn order (actor moves later in sequence)
// 3. Keep acted status = false (they haven't acted yet)
// 4. Advance to next actor in sequence
```

**Edge cases handled**:
- Initiative increase past current position: Actor doesn't act again this round
- Delay to initiative already passed: Actor moved to that position but doesn't act until next round
- Delay during last actor's turn: Round doesn't advance until delayed actor acts or round ends

---

### 4. Pass-Based Initiative Decay

**Decision**: Configurable decay amount (parameter: `decayAmount`, default: 10) applied at start of each pass

**Rationale**:
- Different Shadowrun editions use different decay values:
  - SR4: -10 per pass
  - SR5: -5 per pass  
  - SR6: Variable based on actions taken
- Making decay configurable supports all editions without special-casing
- Decay applied at pass start (not pass end) matches SR4/SR5 rules
- Decay affects initiative score but not pass eligibility (determined at round start)

**Implementation details**:
```php
// TimelineProfile properties for pass-based:
- passesPerRound: int (e.g., 3 for Shadowrun)
- decayEnabled: bool (default: false)
- decayAmount: int (default: 10)

// Pass advancement logic:
1. Mark current pass complete
2. If decayEnabled, subtract decayAmount from all actor initiatives
3. Increment pass counter
4. Filter actors: only those with enough initiative for this pass
5. Reset acted status for eligible actors
```

---

### 5. Tie-Breaking Strategy

**Decision**: Default to stable insertion order; support optional tie-breaker callbacks

**Rationale**:
- Stable insertion order is simplest, most predictable, requires no extra data
- Matches common table-top convention: "whoever declared initiative first"
- Deterministic behavior critical for fairness and reproducibility
- Advanced users can provide custom comparator for attribute-based tie-breaking (e.g., Dexterity)

**API design**:
```php
// TimelineProfile properties:
- tieBreakerAttribute: ?string (e.g., 'dexterity', null for insertion order)
- tieBreakerCallback: ?callable (custom comparison function)

// Resolution order:
1. If tieBreakerCallback provided: use callback(actor1, actor2)
2. Else if tieBreakerAttribute provided: compare actor attributes
3. Else: stable insertion order (first added wins)
```

---

### 6. Initiative Bounds Validation

**Decision**: Optional min/max bounds in `TimelineProfile`; reject out-of-bounds changes when configured

**Rationale**:
- Some systems have natural bounds (D&D: typically 1-30), others don't (Shadowrun: unbounded)
- Optional bounds provide safety net without restricting systems that don't need limits
- Validation at add/change time prevents invalid state
- Clear error messages guide consumers to valid values

**Validation logic**:
```php
// TimelineProfile properties:
- minInitiative: ?int (null = unbounded)
- maxInitiative: ?int (null = unbounded)

// Validation triggers:
- Actor::__construct($initiative) - validate on creation
- Encounter::addActor($actor) - validate on addition
- Encounter::changeInitiative($actorId, $newInit) - validate on change

// Error handling:
throw new InvalidInitiativeException(
    "Initiative {$value} outside configured bounds [{$min}, {$max}]"
);
```

---

### 7. Popcorn Initiative Repeat Designation

**Decision**: Optional `allowRepeatPopcorn` flag (default: false) in `TimelineProfile`

**Rationale**:
- Default behavior (false): Each actor acts once per round - maintains fairness
- Optional behavior (true): Narrative-focused games might allow flexible repeat actions
- Configuration-driven rather than hardcoded policy
- When false and no unacted actors remain, round auto-advances

**Implementation**:
```php
// TimelineProfile property:
- allowRepeatPopcorn: bool (default: false)

// Popcorn::designateNext($actorId) logic:
if (!allowRepeatPopcorn && $actor->hasActedThisRound()) {
    throw new InvalidDesignationException(
        "Actor {$actorId} has already acted this round"
    );
}

// Auto-advance when all acted:
if (count($unactedActors) === 0) {
    if (allowRepeatPopcorn) {
        // Continue round, allow any designation
    } else {
        // Advance to next round
        $this->advanceRound();
    }
}
```

---

### 8. State Management Architecture

**Decision**: Separate mutable state (EncounterState, ActorState) from immutable entities (Actor, TimelineProfile)

**Rationale**:
- Clean separation of concerns: entities are data, state is temporal
- Enables state serialization/restoration without coupling to entity lifecycle
- Simplifies testing: can manipulate state independently
- Supports potential future features: undo/redo, state snapshots, time travel debugging

**Architecture**:
```php
// Immutable entities:
- Actor (id, name, initiative, attributes) - data about participant
- TimelineProfile (type, rules, configuration) - data about turn model

// Mutable state:
- EncounterState (round, pass, currentActorId, isActive)
- ActorState (actorId, hasActed, passesRemaining)

// Encounter coordinates:
- Holds immutable TimelineProfile
- Holds collection of immutable Actors
- Holds mutable EncounterState
- Delegates turn order logic to TurnOrderStrategy
```

---

### 9. Zero-Dependency Design

**Decision**: No external dependencies, stdlib only

**Rationale**:
- Maximum portability - works in any PHP 8.0+ environment
- No version conflict risks with consumer applications
- Smaller install footprint
- Faster composer install
- Core turn order logic doesn't require external libraries

**What we're NOT using**:
- ❌ symfony/event-dispatcher (could add hooks, but adds complexity)
- ❌ doctrine/collections (PHP arrays sufficient for actor management)
- ❌ psr/log (consumers can wrap Encounter if logging needed)
- ❌ ramsey/uuid (consumers can use any ID strategy)

**Standard library sufficient for**:
- ✅ Array manipulation (usort, array_filter, array_map)
- ✅ Validation (simple conditionals and exceptions)
- ✅ State management (object properties and arrays)

---

### 10. Testing Strategy

**Decision**: Unit tests for all classes + Integration tests mapping to user stories

**Rationale**:
- Unit tests verify individual component behavior in isolation
- Integration tests verify user story acceptance criteria end-to-end
- User story → integration test mapping provides traceability
- 90% coverage target catches edge cases and error paths

**Test structure**:
```
tests/Unit/
- Test each class in isolation with mocks
- Test each TurnOrder strategy independently
- Test validators with boundary cases
- Test state objects with various configurations

tests/Integration/
- BasicTurnProgressionTest → User Story 1 scenarios
- DynamicActorManagementTest → User Story 2 scenarios
- InitiativeChangesTest → User Story 3 scenarios
- MultiPassSystemTest → User Story 4 scenarios
- AlternativeTurnOrderTest → User Story 5 scenarios
- SideBasedInitiativeTest → User Story 6 scenarios

tests/Fixtures/
- TimelineProfiles: D&D5e, Shadowrun4, Shadowrun5, OSR, Genesys
- ActorFactory: Builder pattern for test actors
```

---

## Summary of Key Decisions

| Question | Decision | Impact |
|----------|----------|--------|
| Tick-based systems | Deferred to V2 | Reduces scope, focuses on 5 core models |
| PHP compatibility | 8.0+ (test on 8.0-8.3) | Modern features, broad compatibility |
| Initiative changes | Preserve acted status | Fairness, prevents double-turns |
| Pass decay | Configurable amount (default 10) | Supports SR4, SR5, SR6, custom systems |
| Tie-breaking | Stable insertion order default | Simple, predictable, extensible |
| Initiative bounds | Optional min/max validation | Safety for bounded systems, flexibility for unbounded |
| Popcorn repeats | Optional flag (default: no repeats) | Fairness default, narrative flexibility option |
| State architecture | Separate state from entities | Clean design, testable, extensible |
| Dependencies | Zero (stdlib only) | Maximum portability, no conflicts |
| Testing | Unit + Integration (90% coverage) | Quality assurance, user story traceability |

---

## Open Questions / Future Considerations

1. **Serialization format**: Should library provide built-in serialization (JSON/array export)? 
   - **Deferred**: Consumers can serialize Actor/EncounterState as needed
   
2. **Event system**: Should encounter emit events (turn started, round advanced)?
   - **Deferred**: Adds complexity, consumers can wrap if needed

3. **Undo/redo**: Should library support undoing turn advancement?
   - **Deferred to V2**: State snapshots could enable this later

4. **Performance optimization**: Pre-sort vs sort-on-demand for large actor counts?
   - **Decision**: Profile after V1 implementation, optimize if needed

5. **Concurrent modification**: Should library detect/prevent mid-turn actor list changes?
   - **Decision**: Allow changes (per requirements), document consumer responsibility
