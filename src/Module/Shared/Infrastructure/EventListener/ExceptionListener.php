<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\EventListener;

use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;
use App\Module\Shared\Domain\Exception\DomainException;
use App\Module\Shared\Domain\Exception\FormValidationException;
use App\Module\Shared\Domain\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

readonly class ExceptionListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
     * @return void
     * @throws \ReflectionException
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only act if we have an API request (should always be the case).
        if ('application/json' === $request->headers->get('Accept')) {
            if ($exception instanceof ValidationException) {
                // Symfony Validator exception.
                $this->logger->debug('ExceptionListener :: Symfony Validator exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors(),
                    'code' => $exception->getCode(),
                ]);
            } elseif ($exception instanceof ValidationFailedException) {
                // Messenger middleware validation exception.
                $this->logger->debug('ExceptionListener :: Messenger validation failed exception',
                    ['exception' => $exception]);

                $exception = $this->validationExceptionFromMessenger($exception);

                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors(),
                    'code' => $exception->getCode(),
                ]);
            } elseif ($exception instanceof FormValidationException) {
                // Custom form validation exception.
                $this->logger->debug('ExceptionListener :: Form validation exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors(),
                    'code' => $exception->getCode(),
                ]);
            } elseif ($exception instanceof DomainException) {
                // Other Domain exceptions.
                $this->logger->debug('ExceptionListener :: Generic domain exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]);
            } else {
                // All other exceptions.
                // Customize your response object to display the exception details.
                $this->logger->debug('ExceptionListener :: Misc exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'traces' => $exception->getTrace(),
                ]);
            }

            // HttpExceptionInterface is a special type of exception that
            // holds status code and header details.
            if ($exception instanceof HttpExceptionInterface) {
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
            } else {
                $code = $exception->getCode();
                // Make sure we set a valid status code.
                if (! array_key_exists($code, Response::$statusTexts)) {
                    $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                }
                $response->setStatusCode($code);
            }

            // Send the modified response object to the event.
            $event->setResponse($response);
        } else {
            $this->logger->debug('ExceptionListener :: Server error', [
                'exception' => $exception,
                'Content-Type header' => $request->headers->get('Content-Type'),
                'Accept header' => $request->headers->get('Accept'),
            ]);
        }
    }

    /**
     * @param \Symfony\Component\Messenger\Exception\ValidationFailedException $e
     * @return \App\Module\Shared\Domain\Exception\ValidationException
     * @throws \ReflectionException
     */
    private function validationExceptionFromMessenger(ValidationFailedException $e): ValidationException
    {
        $context = 'Global';

        foreach ($e->getViolations() as $violation) {
            // If any of violation messages implements ValidatedMessageInterface we read context from message.
            if ($violation->getRoot() instanceof ValidatedMessageInterface) {
                $context = $violation->getRoot()->validationContext();
                break;
            }
        }

        return new ValidationException($e->getViolations(), $context);
    }
}
