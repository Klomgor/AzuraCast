<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/mount/{id}/intro',
    description: 'Removes the intro track for a mount point.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Mount Points'],
    parameters: [
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Mount Point ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
        ),
        new OA\Response(
            response: 404,
            description: 'Record not found',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
        new OA\Response(
            response: 403,
            description: 'Access denied',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
    ]
)]
class DeleteIntroAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMountRepository $mountRepo,
        int $id
    ): ResponseInterface {
        $station = $request->getStation();
        $mount = $mountRepo->find($station, $id);

        if (null === $mount) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $mountRepo->clearIntro($mount);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
