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
use Symfony\Component\Validator\ConstraintViolationList;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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

                // We have to do some extra work to convert messenger validation violations to validation errors.
                $errors = $this->translateMessengerValidationViolationsToErrors($exception->getViolations());
                $response = new JsonResponse([
                    'message' => 'Validation failed.',
                    'errors' => $errors,
                    // We consider that messenger validation error code in always 422.
                    'code' => DomainException::$codes['UNPROCESSABLE_ENTITY'],
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
                    // For domain exception traces are not needed for development.
                    // 'traces' => $exception->getTrace(),
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
                $response->setStatusCode($exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * @param \Symfony\Component\Validator\ConstraintViolationList $violations
     * @return array
     */
    private function translateMessengerValidationViolationsToErrors(ConstraintViolationList $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $message = $violation->getRoot();
            $errors[$violation->getPropertyPath()]['property'] = $violation->getPropertyPath();
            $errors[$violation->getPropertyPath()]['errors'][] = $violation->getMessage();
            $errors[$violation->getPropertyPath()]['context'] = $message instanceof (ValidatedMessageInterface::class) ? $message->validationContext() : 'General';
        }

        return array_values($errors);
    }
}
