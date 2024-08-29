<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\User\Application\ResetPassword\PerformResetPassword\PerformResetPasswordCommand;
use App\Module\User\Application\ResetPassword\RequestResetPassword\RequestResetPasswordMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class PasswordResetController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/forgot-password', name: 'forgot-password', methods: ['POST'])]
    public function requestResetPassword(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
        ], [
            'email',
        ]);

        $command = new RequestResetPasswordMessage($jsonData['email']);

        $commandBus->dispatch($command);

        return $this->json(['message' => 'Reset link sent to '.$jsonData['email']]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/reset-password', name: 'reset-password', methods: ['POST'])]
    public function resetPassword(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            ['resetToken', 'reset_token'],
            'password',
            ['passwordConfirmation', 'password_confirmation'],
        ], [
            'email',
            'resetToken',
            'password',
            'passwordConfirmation',
        ]);

        $command = new PerformResetPasswordCommand($jsonData['email'], $jsonData['resetToken'], $jsonData['password'],
            $jsonData['passwordConfirmation']);

        /** @var \App\Module\Shared\Application\UserResponse $response */
        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }
}
