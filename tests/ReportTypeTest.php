<?php
declare(strict_types=1);

namespace Tests;

use App\ReportType;

class ReportTypeTest extends TestCase
{
    public function testAllElevenViewsAreRegistered(): void
    {
        $cases = ReportType::cases();
        $this->assertCount(11, $cases);
        $this->assertCount(2, ReportType::streamCases());
        $this->assertCount(9, ReportType::dimensionCases());
    }

    public function testStreamVsDimensionClassification(): void
    {
        $this->assertTrue(ReportType::In->isInOut());
        $this->assertTrue(ReportType::Out->isInOut());
        $this->assertFalse(ReportType::Country->isInOut());
        $this->assertFalse(ReportType::Browser->isInOut());
    }

    public function testPageUrlsAndFaqMapping(): void
    {
        $this->assertSame('in.php', ReportType::In->pageUrl());
        $this->assertSame('country.php', ReportType::Country->pageUrl());
        $this->assertSame('ipversion.php', ReportType::IpVersion->pageUrl());

        $this->assertNull(ReportType::In->faqLinkType());
        $this->assertSame('agent', ReportType::Browser->faqLinkType());
        $this->assertSame('country', ReportType::Country->faqLinkType());
    }

    public function testPropertyIdRoutingAndScaleDefaults(): void
    {
        $this->assertSame('prop/in', ReportType::In->propertyId('prop/in', 'prop/out', 'prop/gen'));
        $this->assertSame('prop/out', ReportType::Out->propertyId('prop/in', 'prop/out', 'prop/gen'));
        $this->assertSame('prop/gen', ReportType::City->propertyId('prop/in', 'prop/out', 'prop/gen'));

        $this->assertSame('linear', ReportType::IpVersion->defaultScale());
        $this->assertSame('logarithmic', ReportType::Country->defaultScale());
    }
}

