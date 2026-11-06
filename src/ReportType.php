<?php
declare(strict_types=1);

namespace App;

enum ReportType: string
{
    case In = 'in';
    case Out = 'out';
    case Country = 'country';
    case Region = 'region';
    case City = 'city';
    case Os = 'os';
    case Browser = 'browser';
    case Mount = 'mount';
    case Protocol = 'protocol';
    case Port = 'port';
    case IpVersion = 'ipversion';

    public function isInOut(): bool
    {
        return match ($this) {
            self::In, self::Out => true,
            default => false,
        };
    }

    public function pageUrl(): string
    {
        return $this->value . '.php';
    }

    public function iconClass(): string
    {
        return match ($this) {
            self::In => 'bi-box-arrow-in-right',
            self::Out => 'bi-box-arrow-right',
            self::Country => 'bi-globe-americas',
            self::Region => 'bi-map',
            self::City => 'bi-buildings',
            self::Os => 'bi-pc-display',
            self::Browser => 'bi-browser-chrome',
            self::Mount => 'bi-hdd-network',
            self::Protocol => 'bi-shield-lock',
            self::Port => 'bi-plug',
            self::IpVersion => 'bi-router',
        };
    }

    public function faqLinkType(): ?string
    {
        return match ($this) {
            self::In, self::Out => null,
            self::Browser => 'agent',
            default => $this->value,
        };
    }

    public function defaultScale(): string
    {
        return match ($this) {
            self::In, self::Out, self::IpVersion, self::Protocol, self::Port => 'linear',
            default => 'logarithmic',
        };
    }

    public function labelMsgId(): string
    {
        return match ($this) {
            self::In => 'Incoming',
            self::Out => 'Outgoing',
            self::Country => 'Country',
            self::Region => 'Region',
            self::City => 'City',
            self::Os => 'Operating System',
            self::Browser => 'Browser',
            self::Mount => 'Mountpoint',
            self::Protocol => 'Protocol',
            self::Port => 'Port',
            self::IpVersion => 'IP Version',
        };
    }

    public function descriptionMsgId(): string
    {
        return match ($this) {
            self::In => 'These are users who tuned in to a station in the network within the last 30 minutes.',
            self::Out => 'These are users who tuned out of a station in the network within the last 30 minutes.',
            self::Country => 'Shows user countries for today, yesterday, the last 7, and the last 30 days.',
            self::Region => 'Shows user regions for today, yesterday, the last 7, and the last 30 days.',
            self::City => 'Shows user cities for today, yesterday, the last 7, and the last 30 days.',
            self::Os => 'Shows user operating systems for today, yesterday, the last 7, and the last 30 days.',
            self::Browser => 'Shows used browsers for today, yesterday, the last 7, and the last 30 days.',
            self::Mount => 'Shows user mountpoints for today, yesterday, the last 7, and the last 30 days.',
            self::Protocol => 'Shows user activity protocols for today, yesterday, the last 7, and the last 30 days.',
            self::Port => 'Shows used ports for today, yesterday, the last 7, and the last 30 days.',
            self::IpVersion => 'Shows used IP versions for today, yesterday, the last 7, and the last 30 days.',
        };
    }

    public function propertyId(string $inboundId, string $outboundId, string $generalId): string
    {
        return match ($this) {
            self::In => $inboundId,
            self::Out => $outboundId,
            default => $generalId,
        };
    }

    /**
     * @return list<self>
     */
    public static function streamCases(): array
    {
        return [self::In, self::Out];
    }

    /**
     * @return list<self>
     */
    public static function dimensionCases(): array
    {
        return [
            self::Country,
            self::Region,
            self::City,
            self::Os,
            self::Browser,
            self::Mount,
            self::Protocol,
            self::Port,
            self::IpVersion,
        ];
    }
}

