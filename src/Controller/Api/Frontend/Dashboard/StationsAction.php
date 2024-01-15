<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Dashboard;
use App\Entity\ApiGenerator\NowPlayingApiGenerator;
use App\Entity\Station;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Psr\Http\Message\ResponseInterface;

final class StationsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;
    use CanSortResults;
    use CanSearchResults;

    public function __construct(
        private readonly NowPlayingApiGenerator $npApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();
        $acl = $request->getAcl();

        /** @var Station[] $stations */
        $stations = array_filter(
            $this->em->getRepository(Station::class)->findAll(),
            static function ($station) use ($acl) {
                /** @var Station $station */
                return $station->getIsEnabled() &&
                    $acl->isAllowed(StationPermissions::View, $station->getId());
            }
        );

        $listenersEnabled = $this->readSettings()->isAnalyticsEnabled();

        $viewStations = [];
        foreach ($stations as $station) {
            $np = $this->npApiGenerator->currentOrEmpty($station);
            $np->resolveUrls($request->getRouter()->getBaseUrl());

            $row = new Dashboard();
            $row->fromParentObject($np);

            $row->links = [
                'public' => $router->named('public:index', ['station_id' => $station->getShortName()]),
                'manage' => $router->named('stations:index:index', ['station_id' => $station->getId()]),
            ];

            if ($listenersEnabled && $acl->isAllowed(StationPermissions::Reports, $station->getId())) {
                $row->links['listeners'] = $router->named(
                    'stations:reports:listeners',
                    ['station_id' => $station->getId()]
                );
            }

            $viewStations[] = $row;
        }

        $searchPhrase = $this->getSearchPhrase($request);
        if (null !== $searchPhrase) {
            $viewStations = array_filter(
                $viewStations,
                static function (Dashboard $row) use ($searchPhrase) {
                    return false !== mb_stripos($row->station->name, $searchPhrase);
                }
            );
        }

        $viewStations = $this->sortArray(
            $request,
            $viewStations,
            [
                'listeners' => 'listeners.current',
            ],
            'station.name'
        );

        return Paginator::fromArray($viewStations, $request)->write($response);
    }
}
