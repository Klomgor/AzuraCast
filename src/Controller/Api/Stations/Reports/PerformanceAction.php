<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Service\CsvWriterTempFile;
use App\Sync\Task\RunAutomatedAssignmentTask;
use Psr\Http\Message\ResponseInterface;

final class PerformanceAction
{
    public function __construct(
        private readonly RunAutomatedAssignmentTask $automationTask
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $station = $request->getStation();

        $automationConfig = (array)$station->getAutomationSettings();
        $thresholdDays = (int)($automationConfig['threshold_days']
            ?? RunAutomatedAssignmentTask::DEFAULT_THRESHOLD_DAYS);

        $reportData = $this->automationTask->generateReport($station, $thresholdDays);

        // Do not show songs that are not in playlists.
        $reportData = array_filter(
            $reportData,
            static function ($media) {
                return !(empty($media['playlists']));
            }
        );

        $params = $request->getQueryParams();
        $format = $params['format'] ?? 'json';

        if ($format === 'csv') {
            return $this->exportReportAsCsv(
                $response,
                $reportData,
                $station->getShortName() . '_media_' . date('Ymd') . '.csv'
            );
        }

        return Paginator::fromArray($reportData, $request)->write($response);
    }

    /**
     * @param Response $response
     * @param mixed[] $reportData
     * @param string $filename
     */
    private function exportReportAsCsv(
        Response $response,
        array $reportData,
        string $filename
    ): ResponseInterface {
        $tempFile = new CsvWriterTempFile();
        $csv = $tempFile->getWriter();

        $csv->insertOne(
            [
                'Song Title',
                'Song Artist',
                'Filename',
                'Length',
                'Current Playlist',
                'Delta Joins',
                'Delta Losses',
                'Delta Total',
                'Play Count',
                'Play Percentage',
                'Weighted Ratio',
            ]
        );

        foreach ($reportData as $row) {
            $csv->insertOne([
                $row['title'],
                $row['artist'],
                $row['path'],
                $row['length'],
                implode('/', $row['playlists']),
                $row['delta_positive'],
                $row['delta_negative'],
                $row['delta_total'],
                $row['num_plays'],
                $row['percent_plays'] . '%',
                $row['ratio'],
            ]);
        }

        return $response->withFileDownload($tempFile->getTempPath(), $filename, 'text/csv');
    }
}
