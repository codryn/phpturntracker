<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit\Validators;

use Codryn\PHPTurnTracker\Exceptions\InvalidTimelineProfileException;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;
use Codryn\PHPTurnTracker\Validators\TimelineProfileValidator;
use PHPUnit\Framework\TestCase;

class TimelineProfileValidatorTest extends TestCase
{
    public function testValidateSucceedsForValidProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        TimelineProfileValidator::validate($profile);
        $this->assertTrue(true); // No exception means success
    }

    public function testValidateThrowsForInvalidType(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('Invalid turn order type: invalid');

        $profile = new TimelineProfile(type: 'invalid');
        TimelineProfileValidator::validate($profile);
    }

    public function testValidateThrowsForInvalidBounds(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('minInitiative (20) must be less than maxInitiative (10)');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 20,
            maxInitiative: 10
        );
        TimelineProfileValidator::validate($profile);
    }

    public function testValidateThrowsForPassBasedWithoutPassesPerRound(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('passesPerRound must be greater than 0');

        $profile = new TimelineProfile(type: TurnOrderType::PASS);
        TimelineProfileValidator::validate($profile);
    }

    public function testValidateThrowsForSlotBasedWithoutSlotConfiguration(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('slotConfiguration must be provided');

        $profile = new TimelineProfile(type: TurnOrderType::SLOT);
        TimelineProfileValidator::validate($profile);
    }

    public function testIsValidReturnsTrueForValidProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        $this->assertTrue(TimelineProfileValidator::isValid($profile));
    }

    public function testIsValidReturnsFalseForInvalidType(): void
    {
        $profile = new TimelineProfile(type: 'invalid');

        $this->assertFalse(TimelineProfileValidator::isValid($profile));
    }

    public function testIsValidReturnsFalseForInvalidBounds(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 20,
            maxInitiative: 10
        );

        $this->assertFalse(TimelineProfileValidator::isValid($profile));
    }

    public function testIsValidReturnsFalseForPassBasedWithoutPassesPerRound(): void
    {
        $profile = new TimelineProfile(type: TurnOrderType::PASS);

        $this->assertFalse(TimelineProfileValidator::isValid($profile));
    }

    public function testIsValidReturnsFalseForSlotBasedWithoutSlotConfiguration(): void
    {
        $profile = new TimelineProfile(type: TurnOrderType::SLOT);

        $this->assertFalse(TimelineProfileValidator::isValid($profile));
    }

    public function testValidateSucceedsForValidPassBasedProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 10
        );

        TimelineProfileValidator::validate($profile);
        $this->assertTrue(true);
    }

    public function testValidateSucceedsForValidSlotBasedProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: ['slots' => [1, 2, 3, 4, 5]]
        );

        TimelineProfileValidator::validate($profile);
        $this->assertTrue(true);
    }

    public function testValidateSucceedsForValidPopcornProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN,
            allowRepeatPopcorn: true
        );

        TimelineProfileValidator::validate($profile);
        $this->assertTrue(true);
    }

    public function testValidateSucceedsForValidRoundSideProfile(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_SIDE,
            tieBreakerAttribute: 'dexterity'
        );

        TimelineProfileValidator::validate($profile);
        $this->assertTrue(true);
    }
}
