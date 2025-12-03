# Feature Specification: RPG Turn Order Tracker Library

**Feature Branch**: `001-rpg-turn-tracker`  
**Created**: December 3, 2025  
**Status**: Draft  
**Input**: User description: "The library provided a turn order tracker for TRPG. It must support all major rpg systems by passing a timeline profile to the tracker. The tracker shall allow to start an encounter, add and remove actors, get the current actor, advance to next turn. It shall support rounds, passes and turns. Important is that it keeps track which actors already have taken their actions and which are still waiting for their turn in a round/pass. This is important to ensure the correct next actor when an actor gets it initiative reduced or increased or when it delays its action."


## User Scenarios & Testing *(mandatory)*

### User Story 1 - Basic Turn Progression (Priority: P1)

As a game master using D&D 5e, I need to run combat encounters where characters take turns in initiative order, advancing through multiple rounds until combat ends.

**Why this priority**: This is the core functionality that all RPG systems require - the ability to track who goes when in sequential order. Without this, the library has no value.

**Independent Test**: Can be fully tested by creating a tracker with 3-5 actors, starting an encounter, and advancing through turns. Delivers a working turn order system for the most common RPG scenario (round-based, individual initiative).

**Acceptance Scenarios**:

1. **Given** an encounter has not started, **When** I start the encounter with 4 actors (initiative scores: 18, 15, 12, 12), **Then** the tracker activates and the first actor (score 18) is marked as current
2. **Given** an active encounter with current actor at initiative 18, **When** I advance to next turn, **Then** the actor with initiative 15 becomes current and the previous actor (18) is marked as having acted
3. **Given** all actors have taken their turn in round 1, **When** I advance to next turn, **Then** the round increments to 2 and the first actor (initiative 18) becomes current again with all actors reset to "not acted"
4. **Given** an active encounter, **When** I query the current actor, **Then** I receive the actor whose turn it currently is
5. **Given** an active encounter in round 3, **When** I query the tracker state, **Then** I receive confirmation that it's round 3 and which actors have/haven't acted this round

---

### User Story 2 - Dynamic Actor Management (Priority: P1)

As a game master, I need to add new combatants when reinforcements arrive and remove actors when they are defeated or flee, maintaining correct turn order throughout.

**Why this priority**: Combat is dynamic - enemies join mid-battle and characters fall. This is essential for any real-world RPG session and must work correctly with the core turn progression.

**Independent Test**: Can be tested by starting an encounter with 3 actors, adding 2 new actors at different initiatives, removing 1 actor, and verifying turn order remains correct. Delivers the ability to handle the dynamic nature of combat.

**Acceptance Scenarios**:

1. **Given** an active encounter in round 2 with current actor at initiative 15, **When** I add a new actor with initiative 20, **Then** the new actor is added to the turn order but doesn't act until next round
2. **Given** an active encounter with current actor at initiative 15, **When** I add a new actor with initiative 12, **Then** the new actor is inserted in correct position and will act later this round if initiative 12 hasn't occurred yet
3. **Given** an active encounter with 5 actors, **When** I remove an actor who has already acted this round, **Then** the actor is removed and turn order continues without disruption
4. **Given** an active encounter with current actor being "Goblin A", **When** I remove "Goblin A", **Then** the turn immediately advances to the next actor in sequence
5. **Given** an active encounter, **When** I remove an actor who hasn't acted yet this round, **Then** that actor's turn is skipped and they are removed from all future turns

---

### User Story 3 - Initiative Changes and Delays (Priority: P2)

As a game master running various RPG systems, I need to handle actors delaying their actions or having their initiative scores changed mid-combat, ensuring actors who haven't acted yet this round maintain their pending status.

**Why this priority**: Many RPG systems allow initiative modification (readied actions, delay, magical effects). This requires sophisticated tracking of who has/hasn't acted to maintain fairness and game rules.

**Independent Test**: Can be tested by creating an encounter, advancing partway through round 1, then delaying an actor's turn or changing their initiative, and verifying that acted/unacted status is preserved correctly. Delivers support for tactical combat options.

**Acceptance Scenarios**:

1. **Given** round 1 with actors at initiatives [20, 18, 15, 12] where actor at 20 has acted and current is 18, **When** actor at 18 delays their action to initiative 10, **Then** actor moves to initiative 10 but remains marked as "not acted" and turn advances to actor at 15
2. **Given** round 2 with actor at initiative 18 marked as "has acted", **When** a magical effect reduces their initiative to 12, **Then** their position in turn order changes but they remain marked as "has acted" for this round
3. **Given** an actor delays to initiative 10 and hasn't acted yet, **When** the round advances to round 2, **Then** the delayed actor's "not acted" status resets like all other actors
4. **Given** round 1 with current actor at initiative 15, **When** I increase an upcoming actor's initiative from 12 to 22, **Then** that actor's turn is skipped this round (since initiative 22 already passed) but they go first in round 2

---

### User Story 4 - Multi-Pass System Support (Priority: P2)

As a game master running Shadowrun, I need combat to progress through multiple passes per round, where actors with higher initiative get multiple actions while tracking which actors have acted in each pass.

**Why this priority**: Critical for Shadowrun and similar systems that use pass-based combat. This is a distinct combat model that requires tracking passes within rounds and per-pass action status.

**Independent Test**: Can be tested by configuring a Shadowrun-style timeline profile with 3 passes per round, creating actors with different initiative scores that grant 1-3 passes, and advancing through a complete round to verify correct pass progression and actor availability.

**Acceptance Scenarios**:

1. **Given** a Shadowrun encounter with 3 passes per round and actors with initiatives 25 (3 passes), 18 (2 passes), 10 (1 pass), **When** I start the encounter, **Then** round 1 pass 1 begins with all three actors available
2. **Given** pass 1 with all actors having acted, **When** I advance to next turn, **Then** pass 2 begins with only the actors who have 2+ passes available (25 and 18), and actor with 10 is not available
3. **Given** pass 2 with actors 25 and 18 having acted, **When** I advance to next turn, **Then** pass 3 begins with only actor 25 available
4. **Given** pass 3 completed, **When** I advance to next turn, **Then** round 2 pass 1 begins with all actors reset and available again
5. **Given** Shadowrun encounter with initiative decay enabled, **When** pass 2 begins, **Then** all actor initiative scores are reduced by 10 points (per Shadowrun rules) while maintaining their relative order

---

### User Story 5 - Alternative Turn Order Systems (Priority: P3)

As a game master running Genesys or narrative RPG systems, I need support for non-traditional turn order models like slot-based initiative (fill slots with any actor) or popcorn initiative (current actor chooses next).

**Why this priority**: Enables support for modern narrative-focused RPG systems. While less common than traditional initiative, these systems represent growing market segments and demonstrate library flexibility.

**Independent Test**: Can be tested by configuring a slot-based timeline profile, creating initiative slots for "2 player slots, 3 enemy slots", and allowing any player character to fill player slots in any order. Separately test popcorn mode where current actor designates the next actor.

**Acceptance Scenarios**:

1. **Given** a Genesys encounter with 2 PC slots and 3 NPC slots generated, **When** I start the encounter, **Then** the first slot (type: PC or NPC based on roll results) is current and any actor of that type can be chosen to act
2. **Given** a popcorn initiative encounter with 5 actors, **When** current actor completes their turn, **Then** they can designate any other actor who hasn't acted this round as the next actor
3. **Given** a slot-based encounter with current slot designated "NPC", **When** an NPC actor is chosen to fill that slot, **Then** that actor is marked as acted and the next slot becomes current
4. **Given** a popcorn encounter where 4 of 5 actors have acted, **When** current actor completes their turn, **Then** the only remaining actor who hasn't acted automatically becomes current
5. **Given** all slots filled or all actors having acted in popcorn mode, **When** next turn is requested, **Then** round increments and all slots/actors reset for a new round

---

### User Story 6 - Side-Based Initiative (Priority: P3)

As a game master running older D&D editions or war games, I need support for side-based initiative where entire teams (players vs. monsters) take turns as a group rather than individually.

**Why this priority**: Required for B/X D&D, AD&D, and tactical war games. Less common in modern RPGs but essential for retro-clone and OSR communities.

**Independent Test**: Can be tested by configuring a side-based timeline profile, creating two sides (Players and Monsters), rolling initiative for each side, and advancing through turns where all members of a side can act before the other side's turn.

**Acceptance Scenarios**:

1. **Given** a side-based encounter with "Players" (initiative 15) and "Monsters" (initiative 12), **When** I start the encounter, **Then** the Players side is current and all player actors are available to act
2. **Given** Players side is current with 3 player actors, **When** all player actors have completed their actions, **Then** the Monsters side becomes current
3. **Given** Monsters side completes all actions, **When** I advance turn, **Then** round 2 begins and Players side becomes current again with all actors reset
4. **Given** round 1 with Players side current and 1 of 3 players has acted, **When** a new player actor is added, **Then** the new actor can act this round as part of the Players side

---

### Edge Cases

- **Empty encounter**: What happens when trying to advance turns with no actors in the encounter?
  - Expected: System should reject the advance request or remain in waiting state until actors are added
  
- **Single actor**: What happens when only one actor is in the encounter?
  - Expected: That actor is always current; advancing turn increments the round and returns to the same actor
  
- **Initiative ties**: What happens when multiple actors have identical initiative scores?
  - Expected: System uses configured tie-breaker rules (e.g., dexterity score, random, or user-specified order) to establish consistent turn order
  
- **Remove all actors mid-encounter**: What happens when all actors are removed during an active encounter?
  - Expected: Encounter remains active but in waiting state; adding new actors resumes the encounter in the current round
  
- **Delay to same initiative as another actor**: What happens when an actor delays to an initiative score occupied by another actor?
  - Expected: Delayed actor is placed after existing actors at that initiative (or before, based on configuration)
  
- **Change initiative beyond valid range**: What happens when trying to set an actor's initiative to a negative number or above maximum?
  - Expected: System validates against configured min/max initiative values and rejects invalid changes
  
- **Multi-pass with initiative increase**: What happens when an actor's initiative increases mid-round in a pass-based system, granting them an additional pass?
  - Expected: Actor gains the additional pass starting next round; current round's pass availability doesn't change mid-round
  
- **Popcorn mode with last actor**: What happens in popcorn mode when the last actor to act has no one else to designate?
  - Expected: System automatically ends the round and starts next round, or allows actor to re-designate someone who already acted (based on configuration)
  
- **Slot-based with insufficient actors**: What happens when there are 3 NPC slots but only 2 NPC actors available?
  - Expected: The 2 NPCs fill 2 slots; the third slot is skipped/auto-filled by one NPC acting twice, or configuration determines behavior
  
- **Round overflow**: What happens when a combat reaches round 100 or other extreme values?
  - Expected: System continues tracking rounds without limit (or up to configured maximum) and maintains performance

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST accept a timeline profile configuration that defines the turn order model (round-based individual, round-based side, pass-based, slot-based, popcorn, or tick-based)

- **FR-002**: System MUST allow starting an encounter, which activates turn tracking and establishes the first actor/slot as current

- **FR-003**: System MUST allow adding actors to an encounter with their initiative score and any additional attributes required by the timeline profile (e.g., side affiliation, number of passes, dexterity for tie-breaking)

- **FR-004**: System MUST allow removing actors from an encounter at any time, properly handling removal of the current actor by advancing to the next actor

- **FR-005**: System MUST provide the current actor (whose turn it is now) or current slot (in slot-based systems)

- **FR-006**: System MUST advance to the next turn, updating the current actor, tracking round/pass progression, and managing acted/unacted status

- **FR-007**: System MUST track which actors have taken their action in the current round/pass and which actors are still waiting for their turn

- **FR-008**: System MUST reset all actors to "not acted" status when a new round begins in round-based systems

- **FR-009**: System MUST reset actors appropriately when a new pass begins in pass-based systems, making only actors with remaining passes available

- **FR-010**: System MUST support changing an actor's initiative score mid-encounter while preserving their acted/unacted status for the current round/pass

- **FR-011**: System MUST support actors delaying their action to a lower initiative, moving them to the new position in turn order while maintaining their "not acted" status

- **FR-012**: System MUST handle initiative ties using configurable tie-breaking rules (secondary attribute comparison, random determination, or explicit ordering)

- **FR-013**: System MUST support pass-based systems with configurable number of passes per round and actor-specific pass availability based on initiative scores

- **FR-014**: System MUST support optional initiative decay in pass-based systems, reducing initiative scores by a configured amount each pass

- **FR-015**: System MUST support side-based initiative where actors are grouped into sides and all members of a side act before the opposing side

- **FR-016**: System MUST support slot-based initiative where initiative determines slots (e.g., "PC slot", "NPC slot") and any actor of the matching type can fill the slot

- **FR-017**: System MUST support popcorn initiative where the current actor designates which actor acts next from those who haven't acted this round

- **FR-018**: System MUST track the current round number, incrementing it when all actors/passes/slots complete their turns

- **FR-019**: System MUST provide encounter state information including current round, current pass (if applicable), current actor or slot, and list of actors who have/haven't acted

- **FR-020**: System MUST validate timeline profile configurations to ensure all required parameters are provided and valid for the selected turn order model

- **FR-021**: System MUST handle edge cases including empty encounters, single-actor encounters, and mid-encounter actor list changes without corrupting turn order

- **FR-022**: System MUST support multiple concurrent encounters operating independently without interfering with each other

### Key Entities

- **Encounter**: Represents an active combat or turn-tracked scenario. Contains all actors, current state (round, pass, current actor), and tracks which actors have acted. Lifecycle: created inactive, becomes active when started, continues until explicitly ended.

- **Actor**: Represents a participant in an encounter. Contains initiative score, unique identifier, acted/unacted status, side affiliation (if applicable), number of passes (if applicable), and tie-breaker attributes. Can be added/removed dynamically and have their initiative modified.

- **Timeline Profile**: Defines the turn order model and rules for an encounter. Contains turn order type (round/pass/slot/popcorn/tick), number of passes (if applicable), tie-breaking rules, initiative decay settings, valid initiative range, and any system-specific parameters. Configured before encounter starts and remains immutable during encounter.

- **Turn Order**: The calculated sequence of actors or slots determining who acts when. Derived from actors' initiative scores and timeline profile rules. Updates dynamically when actors are added/removed or initiative changes.

- **Round**: A complete cycle where all eligible actors have had opportunity to act. Increments when the last actor/pass/slot completes.

- **Pass**: A subdivision of a round in pass-based systems. Determines which actors are eligible to act based on their remaining pass count. Increments when all eligible actors in current pass have acted.

- **Slot**: In slot-based systems, a position in turn order with a type designation (e.g., "PC" or "NPC") that can be filled by any actor matching that type.

- **Side**: In side-based initiative, a group/faction of actors that share the same initiative and act collectively.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Library supports all specified RPG systems (D&D all editions, Pathfinder, Shadowrun 4/5, GURPS, Savage Worlds, Feng Shui, Hackmaster, Cortex, Marvel Heroic, Genesys/FFG Star Wars) through appropriate timeline profile configurations without requiring system-specific code

- **SC-002**: Turn order remains mathematically correct when actors are added, removed, or have initiative changed mid-encounter, with 100% accuracy in regression tests covering these scenarios

- **SC-003**: Acted/unacted status is preserved correctly during initiative changes and delays in 100% of test cases, ensuring fairness in turn order

- **SC-004**: System correctly handles pass-based progression for Shadowrun-style systems with initiative decay, accurately tracking which actors get multiple actions per round

- **SC-005**: An encounter with 20 actors can complete 10 rounds of turn advancement in under 100 milliseconds, demonstrating adequate performance for typical RPG combat

- **SC-006**: Library provides clear state information at any point (current round, current actor, who has/hasn't acted) that can be queried in under 1 millisecond

- **SC-007**: Timeline profile validation catches 100% of invalid configurations (missing required parameters, conflicting settings) before encounter starts

- **SC-008**: Documentation and examples enable a developer unfamiliar with the library to implement support for a new RPG system in under 30 minutes by configuring an appropriate timeline profile

