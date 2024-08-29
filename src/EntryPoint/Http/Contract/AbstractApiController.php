<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Contract;

use App\Module\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Module\Shared\Domain\Exception\BadRequestDomainException;
use App\Module\User\Domain\User;
use App\Module\User\Infrastructure\Security\AuthUser;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class AbstractApiController extends AbstractController
{
    protected LoggerInterface $logger;

    protected SerializerInterface $serializer;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @param string $data
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function jsonResponse(string $data = ''): JsonResponse
    {
        return JsonResponse::fromJsonString($data);
    }

    /**
     * Include current user in response.
     *
     * @param string $data
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function jsonResponseWithUser(string $data = ''): JsonResponse
    {
        $data = json_decode($data);
        $user = $this->getUser();
        if ($user instanceof AuthUser) {
            /** @var AuthUser $user */
            $user = $user->getUser();
        }

        if ($user) {
            $user = $this->serializer->normalize($user, 'json', ['groups' => ['user']]);
        }

        return $this->json(['data' => $data, 'user' => $user]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $expectedKeys
     * @param array $mandatory
     * @return array
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    public function getRequestData(Request $request, array $expectedKeys, array $mandatory = []): array
    {
        $content = json_decode($request->getContent(), true);

        if (! $content && ! empty($mandatory)) {
            throw new BadRequestDomainException('Request content is empty or not valid');
        }

        $data = [];
        foreach ($expectedKeys as $key) {
            /** @var string[]|string $key */
            if (is_array($key)) {
                // Special format: $key[0] - internal property name, $key[1] - property name from request.
                // E.g. ['passwordConfirmation', 'password_confirmation'] will result in
                // $data['passwordConfirmation'] = $content['password_confirmation']
                $data[$key[0]] = $content[$key[1]] ?? null;
            } else {
                // Normal flow: plain property name, no need for case conversion.
                $data[$key] = $content[$key] ?? null;
            }
        }

        if ($mandatory) {
            foreach ($mandatory as $key) {
                if (! isset($data[$key])) {
                    throw new BadRequestDomainException('Mandatory key '.$key.' is missing payload');
                }
            }
        }

        return $data;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $expectedKeys
     * @param array $mandatory
     * @return array
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    public function getRequestQuery(Request $request, array $expectedKeys, array $mandatory = []): array
    {
        $query = $request->query;

        $data = [];
        foreach ($expectedKeys as $key) {
            /** @var string[]|string $key */
            if (is_array($key)) {
                // Special format:
                //  $key[0] - internal property name,
                //  $key[1] - property name from request,
                //  $key[2] - default value.
                // E.g. ['perPage', 'per_page', 10] will result in
                // $data['perPage'] = $query->get('per_page', 10)
                $data[$key[0]] = $query->get($key[1]) ?? ($key[2] ?? null);
            } else {
                // Normal flow: plain property name, no need for case conversion.
                // In order to pass default values we must use array version of the key.
                $data[$key] = $query->get($key) ?? null;
            }
        }

        if ($mandatory) {
            foreach ($mandatory as $key) {
                if (! isset($data[$key])) {
                    throw new BadRequestDomainException('Mandatory key '.$key.' is missing in query');
                }
            }
        }

        return $data;
    }

    /**
     * Get currently logged-in user.
     *
     * @param bool $ensure - throw exception if user not found.
     * @return \App\Module\User\Domain\User|null
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    public function getCurrentUser(bool $ensure = false): ?User
    {
        $authUser = $this->getUser();
        if ($authUser instanceof AuthUser) {
            return $authUser->getUser();
        }

        if ($ensure) {
            throw new AccessDeniedDomainException('You must be logged in.');
        }

        return null;
    }

    /**
     * @return string
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    protected function ensureCurrentUserId(): string
    {
        $id = $this->getUser()?->getId();

        if (is_null($id)) {
            throw new AccessDeniedDomainException('You must be logged in.');
        }

        return $id;
    }
}
