<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit;

use Codryn\PhpTurnTracker\Exceptions\InvalidTimelineProfileException;
use Codryn\PhpTurnTracker\TimelineProfile;
use Codryn\PhpTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

class TimelineProfileTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20,
            tieBreakerAttribute: 'dexterity'
        );

        $this->assertSame(TurnOrderType::ROUND_INDIVIDUAL, $profile->getType());
        $this->assertSame(1, $profile->getMinInitiative());
        $this->assertSame(20, $profile->getMaxInitiative());
        $this->assertSame('dexterity', $profile->getTieBreakerAttribute());
        $this->assertNull($profile->getTieBreakerCallback());
        $this->assertNull($profile->getPassesPerRound());
        $this->assertFalse($profile->isDecayEnabled());
        $this->assertSame(10, $profile->getDecayAmount());
        $this->assertFalse($profile->allowsRepeatPopcorn());
        $this->assertNull($profile->getSlotConfiguration());
    }

    public function testValidateSucceedsForValidRoundIndividual(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20
        );

        $profile->validate();
        $this->assertTrue(true); // No exception means success
    }

    public function testValidateThrowsForInvalidType(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('Invalid turn order type: invalid');

        $profile = new TimelineProfile(type: 'invalid');
        $profile->validate();
    }

    public function testValidateThrowsWhenMinInitiativeEqualsMax(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('minInitiative (10) must be less than maxInitiative (10)');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 10,
            maxInitiative: 10
        );
        $profile->validate();
    }

    public function testValidateThrowsWhenMinInitiativeGreaterThanMax(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('minInitiative (20) must be less than maxInitiative (10)');

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 20,
            maxInitiative: 10
        );
        $profile->validate();
    }

    public function testValidateThrowsForPassBasedWithoutPassesPerRound(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('passesPerRound must be greater than 0');

        $profile = new TimelineProfile(type: TurnOrderType::PASS);
        $profile->validate();
    }

    public function testValidateThrowsForPassBasedWithZeroPassesPerRound(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('passesPerRound must be greater than 0');

        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 0
        );
        $profile->validate();
    }

    public function testValidateSucceedsForValidPassBased(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 10
        );

        $profile->validate();
        $this->assertTrue(true);
    }

    public function testValidateThrowsForPassBasedWithDecayEnabledButZeroAmount(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('decayAmount must be greater than 0');

        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 0
        );
        $profile->validate();
    }

    public function testValidateThrowsForSlotBasedWithoutSlotConfiguration(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('slotConfiguration must be provided');

        $profile = new TimelineProfile(type: TurnOrderType::SLOT);
        $profile->validate();
    }

    public function testValidateThrowsForSlotBasedWithEmptySlotConfiguration(): void
    {
        $this->expectException(InvalidTimelineProfileException::class);
        $this->expectExceptionMessage('slotConfiguration must be provided');

        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: []
        );
        $profile->validate();
    }

    public function testValidateSucceedsForValidSlotBased(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: ['slots' => [1, 2, 3, 4, 5]]
        );

        $profile->validate();
        $this->assertTrue(true);
    }

    public function testTieBreakerCallback(): void
    {
        $callback = fn (array $a, array $b) => $a['dex'] <=> $b['dex'];

        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            tieBreakerCallback: $callback
        );

        $this->assertSame($callback, $profile->getTieBreakerCallback());
    }

    public function testPopcornConfiguration(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN,
            allowRepeatPopcorn: true
        );

        $this->assertTrue($profile->allowsRepeatPopcorn());
    }

    public function testPassBasedDecayConfiguration(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 15
        );

        $this->assertTrue($profile->isDecayEnabled());
        $this->assertSame(15, $profile->getDecayAmount());
    }

    public function testUnboundedInitiative(): void
    {
        $profile = new TimelineProfile(type: TurnOrderType::ROUND_INDIVIDUAL);

        $profile->validate();

        $this->assertNull($profile->getMinInitiative());
        $this->assertNull($profile->getMaxInitiative());
    }

    public function testOnlyMinInitiativeSet(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1
        );

        $profile->validate();

        $this->assertSame(1, $profile->getMinInitiative());
        $this->assertNull($profile->getMaxInitiative());
    }

    public function testOnlyMaxInitiativeSet(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            maxInitiative: 20
        );

        $profile->validate();

        $this->assertNull($profile->getMinInitiative());
        $this->assertSame(20, $profile->getMaxInitiative());
    }
}
