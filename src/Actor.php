<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker;

/**
 * Represents a participant in an encounter (PC, NPC, monster, etc.).
 *
 * Actors are immutable value objects that store identity, name, initiative,
 * and optional system-specific attributes (e.g., side, dexterity, passes).
 */
class Actor
{
    /**
     * Create a new Actor.
     *
     * @param string $id Unique identifier (consumer-provided)
     * @param string $name Display name
     * @param int $initiative Initiative score
     * @param array<string, mixed> $attributes Optional attributes for system-specific data
     */
    public function __construct(
        private string $id,
        private string $name,
        private int $initiative,
        private array $attributes = []
    ) {
    }

    /**
     * Get the actor's unique identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the actor's display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the actor's initiative score.
     */
    public function getInitiative(): int
    {
        return $this->initiative;
    }

    /**
     * Get a specific attribute value.
     *
     * @param string $key The attribute key
     * @return mixed|null The attribute value or null if not found
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $key The attribute key
     * @return bool True if attribute exists
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get all attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
