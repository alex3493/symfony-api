<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/app/dashboard', name: 'app-dashboard', methods: ['GET'])]
    #[Route('/web/dashboard', name: 'web-dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        return $this->jsonResponseWithUser(json_encode([
            'message' => 'Welcome to dashboard. You are logged in.',
        ]));
    }
}
