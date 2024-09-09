<?php
declare(strict_types=1);

namespace App\Module\Shared\Infrastructure\Bus;

use App\Module\Shared\Domain\Bus\Contract\QueryResponse;
use App\Module\Shared\Domain\Bus\Query\Query;
use App\Module\Shared\Domain\Bus\Query\QueryBus;
use InvalidArgumentException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerQueryBus implements QueryBus
{
    use HandleTrait {
        handle as handleQuery;
    }

    /**
     * @param \Symfony\Component\Messenger\MessageBusInterface $queryBus
     */
    public function __construct(private readonly MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    /**
     * @param Query $query
     * @return QueryResponse
     * @throws \Throwable
     *
     * Queries are always executed synchronously and should return data.
     */
    public function ask(Query $query): QueryResponse
    {
        try {
            return $this->handleQuery($query);
        } catch (NoHandlerForMessageException $e) {
            throw new InvalidArgumentException(sprintf('The command has not a valid handler: %s', $query::class));
        } catch (HandlerFailedException $e) {
            // Try to get previous exception that should be normally the real issue.
            throw $e->getPrevious() ?? $e;
        }
    }
}
