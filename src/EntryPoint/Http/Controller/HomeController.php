<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class HomeController extends AbstractApiController
{
    #[Route('/', name: 'welcome', methods: ['GET'])]
    public function welcome(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to the home page.',
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Mercure\Discovery $discovery
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/app/dashboard', name: 'app-dashboard', methods: ['GET'])]
    #[Route('/web/dashboard', name: 'web-dashboard', methods: ['GET'])]
    public function dashboard(Request $request, Discovery $discovery): JsonResponse
    {
        // Add Mercure Hub discovery link to response.
        $discovery->addLink($request);

        return $this->jsonResponseWithUser(json_encode([
            'message' => 'Welcome to dashboard. You are logged in.',
        ]));
    }
}
