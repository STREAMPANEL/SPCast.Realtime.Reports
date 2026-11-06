<?php
declare(strict_types=1);

namespace App;

use Closure;
use Throwable;

class ApiController
{
    private readonly Closure $translator;

    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly Logger $logger,
        private readonly string $inboundPropertyId,
        private readonly string $outboundPropertyId,
        private readonly string $generalPropertyId,
        private readonly string $cacheDir = 'cache/',
        ?callable $translator = null
    ) {
        $this->translator = $translator !== null
            ? Closure::fromCallable($translator)
            : static fn (string $text): string => $text;
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array{status: int, body: array<string, mixed>}
     */
    public function handle(array $queryParams, string $requestMethod = 'GET'): array
    {
        $method = $requestMethod |> trim(...) |> strtoupper(...);
        if ($method !== 'GET') {
            return [
                'status' => 405,
                'body' => [
                    'ok' => false,
                    'error' => ($this->translator)('Method not allowed.'),
                ],
            ];
        }

        $rawAction = $queryParams['action'] ?? (isset($queryParams['type']) ? 'report' : 'pulse');
        if (!is_string($rawAction)) {
            $this->logger->error('ApiController rejected non-string action parameter.');
            return [
                'status' => 400,
                'body' => [
                    'ok' => false,
                    'error' => ($this->translator)('Invalid request parameter.'),
                ],
            ];
        }

        $action = $rawAction |> trim(...) |> strtolower(...);

        try {
            return match ($action) {
                'pulse' => $this->handlePulse(),
                'report' => $this->handleReport($queryParams),
                default => [
                    'status' => 400,
                    'body' => [
                        'ok' => false,
                        'error' => ($this->translator)('Unsupported API action.'),
                    ],
                ],
            };
        } catch (Throwable $e) {
            $this->logger->error('ApiController unhandled error: ' . $e->getMessage());
            return [
                'status' => 500,
                'body' => [
                    'ok' => false,
                    'error' => ($this->translator)('Unable to load statistics at the moment.'),
                ],
            ];
        }
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function handlePulse(): array
    {
        $pulse = $this->analyticsService->getLivePulseSummary(
            $this->inboundPropertyId,
            $this->outboundPropertyId,
            $this->cacheDir,
            45
        );

        return [
            'status' => 200,
            'body' => [
                'ok' => true,
                'action' => 'pulse',
                'pulse' => $pulse,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array{status: int, body: array<string, mixed>}
     */
    private function handleReport(array $queryParams): array
    {
        $rawType = $queryParams['type'] ?? null;
        if (!is_string($rawType)) {
            return [
                'status' => 400,
                'body' => [
                    'ok' => false,
                    'error' => ($this->translator)('Missing or invalid report type.'),
                ],
            ];
        }

        $normalizedType = $rawType |> trim(...) |> strtolower(...);
        $reportType = ReportType::tryFrom($normalizedType);

        if ($reportType === null) {
            $this->logger->error('ApiController rejected unknown report type: ' . substr($normalizedType, 0, 40));
            return [
                'status' => 400,
                'body' => [
                    'ok' => false,
                    'error' => ($this->translator)('Unknown report type requested.'),
                ],
            ];
        }

        $propertyId = $reportType->propertyId(
            $this->inboundPropertyId,
            $this->outboundPropertyId,
            $this->generalPropertyId
        );

        $tr = $this->translator;

        if ($reportType->isInOut()) {
            $inOutData = $this->analyticsService->getInOutReportData(
                $reportType->value,
                $propertyId,
                $this->cacheDir,
                45
            );

            $isIn = $reportType === ReportType::In;
            $realtimeLabel = $isIn
                ? $tr('Tuned in per 30 minutes')
                : $tr('Tuned out per 30 minutes');
            $weeklyLabel = $isIn
                ? $tr('Tuned in over the last 7 days')
                : $tr('Tuned out over the last 7 days');
            $monthlyLabel = $isIn
                ? $tr('Tuned in over the last 30 days')
                : $tr('Tuned out over the last 30 days');

            return [
                'status' => 200,
                'body' => [
                    'ok' => true,
                    'action' => 'report',
                    'type' => $reportType->value,
                    'kind' => 'inout',
                    'title' => $tr($reportType->labelMsgId()),
                    'description' => $tr($reportType->descriptionMsgId()),
                    'pageUrl' => $reportType->pageUrl(),
                    'icon' => $reportType->iconClass(),
                    'defaultScale' => $reportType->defaultScale(),
                    'activeUsers' => $inOutData['activeUsers'],
                    'timestamp' => $inOutData['timestamp'],
                    'periods' => [
                        'realtime' => [
                            'label' => $tr('Today'),
                            'datasetLabel' => $realtimeLabel,
                            'xAxisLabel' => $tr('Hour of the day'),
                            'yAxisLabel' => $realtimeLabel,
                            'labels' => $inOutData['hourly']['labels'],
                            'values' => $inOutData['hourly']['values'],
                        ],
                        'minutes' => [
                            'label' => $tr('Last 30 Minutes'),
                            'datasetLabel' => $realtimeLabel,
                            'xAxisLabel' => $tr('Minutes ago'),
                            'yAxisLabel' => $tr('Users'),
                            'labels' => $inOutData['minutes']['labels'],
                            'values' => $inOutData['minutes']['values'],
                        ],
                        'weekly' => [
                            'label' => $tr('Last 7 Days'),
                            'datasetLabel' => $weeklyLabel,
                            'xAxisLabel' => $tr('Date'),
                            'yAxisLabel' => $tr('Users'),
                            'labels' => $inOutData['weekly']['labels'],
                            'values' => $inOutData['weekly']['values'],
                        ],
                        'monthly' => [
                            'label' => $tr('Last 30 Days'),
                            'datasetLabel' => $monthlyLabel,
                            'xAxisLabel' => $tr('Date'),
                            'yAxisLabel' => $tr('Users'),
                            'labels' => $inOutData['monthly']['labels'],
                            'values' => $inOutData['monthly']['values'],
                        ],
                    ],
                ],
            ];
        }

        $reportData = $this->analyticsService->getStandardReportData(
            $reportType->value,
            $propertyId,
            45,
            $this->cacheDir
        );

        $summaries = [];
        foreach ($reportData as $periodKey => $items) {
            $summaries[$periodKey] = $this->analyticsService->summarizePeriodRanking($items);
        }

        return [
            'status' => 200,
            'body' => [
                'ok' => true,
                'action' => 'report',
                'type' => $reportType->value,
                'kind' => 'dimension',
                'title' => $tr($reportType->labelMsgId()),
                'description' => $tr($reportType->descriptionMsgId()),
                'axisLabel' => $tr($reportType->labelMsgId()),
                'faqLinkType' => $reportType->faqLinkType(),
                'pageUrl' => $reportType->pageUrl(),
                'icon' => $reportType->iconClass(),
                'defaultScale' => $reportType->defaultScale(),
                'timestamp' => time(),
                'periods' => $reportData,
                'summaries' => $summaries,
            ],
        ];
    }
}

