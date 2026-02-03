<?php

declare(strict_types=1);

namespace Cases\MinorUnits;

use AmoCRM\Models\LeadModel;
use PHPUnit\Framework\TestCase;

class LeadMinorUnitsTest extends TestCase
{
    public function testSetPriceInt(): void
    {
        $lead = new LeadModel();
        $lead->setPrice(100);

        $this->assertEquals(100, $lead->getPrice());
        $this->assertEquals(100.0, $lead->getPriceWithMinorUnits());
    }

    public function testSetPriceWithMinorUnits(): void
    {
        $lead = new LeadModel();
        $lead->setPriceWithMinorUnits(100.50);

        $this->assertEquals(100, $lead->getPrice());
        $this->assertEquals(100.50, $lead->getPriceWithMinorUnits());
    }

    public function testToApiWithMinorUnits(): void
    {
        $lead = new LeadModel();
        $lead->setPriceWithMinorUnits(100.50);
        $api = $lead->toApi();

        $this->assertEquals(100.50, $api['price']);
        $this->assertArrayNotHasKey('price_with_minor_units', $api);
    }

    public function testToApiWithIntPrice(): void
    {
        $lead = new LeadModel();
        $lead->setPrice(100);
        $api = $lead->toApi();

        $this->assertEquals(100.0, $api['price']);
    }

    public function testFromArrayWithMinorUnits(): void
    {
        $lead = LeadModel::fromArray([
            'id' => 1,
            'price_with_minor_units' => 100.50,
        ]);

        $this->assertEquals(100.50, $lead->getPriceWithMinorUnits());
        $this->assertEquals(100, $lead->getPrice());
    }

    public function testFromArrayWithoutMinorUnitsBehavesAsExpected(): void
    {
        $lead = LeadModel::fromArray([
            'id' => 1,
            'price' => 200,
        ]);

        $this->assertEquals(0, $lead->getPriceWithMinorUnits());
    }

    public function testOverridePriceWithMinor(): void
    {
        $lead = new LeadModel();
        $lead->setPrice(100);
        $lead->setPriceWithMinorUnits(100.99);

        $this->assertEquals(100.99, $lead->getPriceWithMinorUnits());
        $this->assertEquals(100, $lead->getPrice());
    }

    public function testOverrideMinorWithPrice(): void
    {
        $lead = new LeadModel();
        $lead->setPriceWithMinorUnits(100.99);
        $lead->setPrice(200);

        $this->assertEquals(200.0, $lead->getPriceWithMinorUnits());
        $this->assertEquals(200, $lead->getPrice());
    }

    public function testNullPrice(): void
    {
        $lead = new LeadModel();
        $lead->setPrice(null);

        $this->assertNull($lead->getPrice());
        $this->assertEquals(0.0, $lead->getPriceWithMinorUnits());
    }

    public function testNullPriceWithMinorUnits(): void
    {
        $lead = new LeadModel();
        $lead->setPriceWithMinorUnits(null);

        $this->assertNull($lead->getPrice());
        $this->assertEquals(0.0, $lead->getPriceWithMinorUnits());
    }

    public function testHighPrecisionFloat(): void
    {
        $lead = new LeadModel();
        $lead->setPriceWithMinorUnits(1234.56789);

        $this->assertEquals(1234.56789, $lead->getPriceWithMinorUnits());
        $this->assertEquals(1234, $lead->getPrice());

        $api = $lead->toApi();
        $this->assertEquals(1234.56789, $api['price']);
    }
}
