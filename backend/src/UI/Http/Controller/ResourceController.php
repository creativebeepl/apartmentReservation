<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Resource\ListResourcesHandler;
use App\UI\Http\Presenter\ResourcePresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ResourceController
{
    public function __construct(
        private ListResourcesHandler $listResources,
        private ResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/resources', name: 'api_resources_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(['data' => $this->presenter->presentMany(($this->listResources)())]);
    }
}
