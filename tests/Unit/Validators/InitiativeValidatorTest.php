<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit\Validators;

use Codryn\PhpTurnTracker\Exceptions\InvalidInitiativeException;
use Codryn\PhpTurnTracker\TimelineProfile;
use Codryn\PhpTurnTracker\TurnOrderType;
use Codryn\PhpTurnTracker\Validators\InitiativeValidator;
use PHPUnit\Framework\TestCase;

class InitiativeValidatorTest extends TestCase
{
    public function testValidateSucceedsForUnboundedProfile(): void
    {
        $profile = new TimelineProfile(type: TurnOrderType::ROUND_INDIVIDUAL);

        InitiativeValidator::validate(-100, $profile);
        InitiativeValidator::validate(0, $profile);
        InitiativeValidator::validate(100, $profile);
        InitiativeValidator::validate(9999, $profile);

        $this->assertTrue(true); // No exceptions means success
    }

    public function testValidateSucceedsWhenWithinBounds(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        InitiativeValidator::validate(1, $profile);
        InitiativeValidator::validate(10, $profile);
        InitiativeValidator::validate(20, $profile);

        $this->assertTrue(true);
    }

    public function testValidateThrowsWhenBelowMinimum(): void
    {
        $this->expectException(InvalidInitiativeException::class);
        $this->expectExceptionMessage('Initiative 0 is below minimum 1');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        InitiativeValidator::validate(0, $profile);
    }

    public function testValidateThrowsWhenAboveMaximum(): void
    {
        $this->expectException(InvalidInitiativeException::class);
        $this->expectExceptionMessage('Initiative 21 exceeds maximum 20');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        InitiativeValidator::validate(21, $profile);
    }

    public function testValidateWithOnlyMinimumBound(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1
        );

        InitiativeValidator::validate(1, $profile);
        InitiativeValidator::validate(100, $profile);
        InitiativeValidator::validate(9999, $profile);

        $this->assertTrue(true);
    }

    public function testValidateThrowsWithOnlyMinimumBound(): void
    {
        $this->expectException(InvalidInitiativeException::class);
        $this->expectExceptionMessage('Initiative 0 is below minimum 1');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1
        );

        InitiativeValidator::validate(0, $profile);
    }

    public function testValidateWithOnlyMaximumBound(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            maxInitiative: 20
        );

        InitiativeValidator::validate(-100, $profile);
        InitiativeValidator::validate(0, $profile);
        InitiativeValidator::validate(20, $profile);

        $this->assertTrue(true);
    }

    public function testValidateThrowsWithOnlyMaximumBound(): void
    {
        $this->expectException(InvalidInitiativeException::class);
        $this->expectExceptionMessage('Initiative 21 exceeds maximum 20');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            maxInitiative: 20
        );

        InitiativeValidator::validate(21, $profile);
    }

    public function testIsValidReturnsTrueWhenValid(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        $this->assertTrue(InitiativeValidator::isValid(1, $profile));
        $this->assertTrue(InitiativeValidator::isValid(10, $profile));
        $this->assertTrue(InitiativeValidator::isValid(20, $profile));
    }

    public function testIsValidReturnsFalseWhenBelowMinimum(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        $this->assertFalse(InitiativeValidator::isValid(0, $profile));
        $this->assertFalse(InitiativeValidator::isValid(-10, $profile));
    }

    public function testIsValidReturnsFalseWhenAboveMaximum(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        $this->assertFalse(InitiativeValidator::isValid(21, $profile));
        $this->assertFalse(InitiativeValidator::isValid(100, $profile));
    }

    public function testIsValidReturnsTrueForUnboundedProfile(): void
    {
        $profile = new TimelineProfile(type: TurnOrderType::ROUND_INDIVIDUAL);

        $this->assertTrue(InitiativeValidator::isValid(-100, $profile));
        $this->assertTrue(InitiativeValidator::isValid(0, $profile));
        $this->assertTrue(InitiativeValidator::isValid(100, $profile));
        $this->assertTrue(InitiativeValidator::isValid(9999, $profile));
    }

    public function testNegativeInitiativeWithinBounds(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: -10,
            maxInitiative: 10
        );

        InitiativeValidator::validate(-10, $profile);
        InitiativeValidator::validate(-5, $profile);
        InitiativeValidator::validate(0, $profile);
        InitiativeValidator::validate(5, $profile);
        InitiativeValidator::validate(10, $profile);

        $this->assertTrue(true);
    }
}
