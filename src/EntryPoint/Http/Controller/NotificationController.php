<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Module\Shared\Domain\Message\MercureUpdateMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class NotificationController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\Mercure\HubInterface $hub
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/web/mercure-auth', name: 'web-mercure-auth', methods: ['GET'])]
    #[Route('/app/mercure-auth', name: 'app-mercure-auth', methods: ['GET'])]
    public function getMercureSubscriptionToken(HubInterface $hub): JsonResponse
    {
        $token = $hub->getProvider()->getJwt();

        return $this->json(['token' => $token]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/web/test-mercure', name: 'web-test-mercure', methods: ['POST'])]
    #[Route('/app/test-mercure', name: 'app-test-mercure', methods: ['POST'])]
    public function testMercure(
        Request $request, MessageBusInterface $bus
    ): JsonResponse {
        $jsonData = $this->getRequestData($request, [
            'topic',
            'payload',
        ], [
            'topic',
            'payload',
        ]);

        $bus->dispatch(new MercureUpdateMessage($jsonData['topic'], $jsonData['payload']));

        return $this->json([
            'message_dispatched' => 'OK',
        ]);
    }
}
