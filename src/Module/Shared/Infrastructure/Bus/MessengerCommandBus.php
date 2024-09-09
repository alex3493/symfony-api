<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Bus;

use App\Module\Shared\Domain\Bus\Command\AsyncCommand;
use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\Shared\Domain\Bus\Contract\CommandResponse;
use InvalidArgumentException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class MessengerCommandBus implements CommandBus
{
    use HandleTrait {
        handle as handleCommand;
    }

    /**
     * @param \Symfony\Component\Messenger\MessageBusInterface $commandBus
     */
    public function __construct(private MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    /**
     * @param Command|AsyncCommand $command
     * @return CommandResponse|null
     * @throws \Throwable
     *
     * We support both commands that return data and those that do not return anything,
     * so we are prepared to use async queue with commands.
     */
    public function dispatch(Command|AsyncCommand $command): ?CommandResponse
    {
        if ($command instanceof AsyncCommand) {
            // The command is async, just dispatch it to message bus.
            $this->messageBus->dispatch($command);

            return null;
        } else {
            // We expect command response.
            try {
                return $this->handleCommand($command);
            } catch (NoHandlerForMessageException $e) {
                throw new InvalidArgumentException(sprintf('The command has not a valid handler: %s', $command::class));
            } catch (HandlerFailedException $e) {
                // Try to get previous exception that should be normally the real issue.
                throw $e->getPrevious() ?? $e;
            }
        }
    }
}
