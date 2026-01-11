<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit;

use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

class TurnOrderTypeTest extends TestCase
{
    public function testAllConstantsAreDefined(): void
    {
        $this->assertSame('round_individual', TurnOrderType::ROUND_INDIVIDUAL);
        $this->assertSame('round_side', TurnOrderType::ROUND_SIDE);
        $this->assertSame('pass', TurnOrderType::PASS);
        $this->assertSame('slot', TurnOrderType::SLOT);
        $this->assertSame('popcorn', TurnOrderType::POPCORN);
    }

    public function testGetAllReturnsAllTypes(): void
    {
        $types = TurnOrderType::getAll();

        $this->assertCount(5, $types);
        $this->assertContains(TurnOrderType::ROUND_INDIVIDUAL, $types);
        $this->assertContains(TurnOrderType::ROUND_SIDE, $types);
        $this->assertContains(TurnOrderType::PASS, $types);
        $this->assertContains(TurnOrderType::SLOT, $types);
        $this->assertContains(TurnOrderType::POPCORN, $types);
    }

    public function testIsValidReturnsTrueForValidTypes(): void
    {
        $this->assertTrue(TurnOrderType::isValid(TurnOrderType::ROUND_INDIVIDUAL));
        $this->assertTrue(TurnOrderType::isValid(TurnOrderType::ROUND_SIDE));
        $this->assertTrue(TurnOrderType::isValid(TurnOrderType::PASS));
        $this->assertTrue(TurnOrderType::isValid(TurnOrderType::SLOT));
        $this->assertTrue(TurnOrderType::isValid(TurnOrderType::POPCORN));
    }

    public function testIsValidReturnsFalseForInvalidTypes(): void
    {
        $this->assertFalse(TurnOrderType::isValid('invalid'));
        $this->assertFalse(TurnOrderType::isValid(''));
        $this->assertFalse(TurnOrderType::isValid('round'));
        $this->assertFalse(TurnOrderType::isValid('ROUND_INDIVIDUAL'));
    }

    public function testIsValidIsCaseSensitive(): void
    {
        $this->assertTrue(TurnOrderType::isValid('round_individual'));
        $this->assertFalse(TurnOrderType::isValid('ROUND_INDIVIDUAL'));
        $this->assertFalse(TurnOrderType::isValid('Round_Individual'));
    }
}
