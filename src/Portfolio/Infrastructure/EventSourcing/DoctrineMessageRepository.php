<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\EventSourcing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use Generator;
use InvalidArgumentException;

use const JSON_THROW_ON_ERROR;

use Throwable;

// Append-only event store; the unique (aggregate_root_id, version) index gives
// optimistic concurrency.
final class DoctrineMessageRepository implements MessageRepository
{
    private const string TABLE = 'portfolio_event_store';

    public function __construct(
        private readonly Connection $connection,
        private readonly MessageSerializer $serializer,
    ) {
    }

    public function persist(Message ...$messages): void
    {
        try {
            foreach ($messages as $message) {
                $serialized = $this->serializer->serializeMessage($message);

                $aggregateRootId = $message->aggregateRootId();
                \assert($aggregateRootId instanceof AggregateRootId);

                $headers = $serialized['headers'] ?? [];
                \assert(\is_array($headers));
                $eventType = $headers[Header::EVENT_TYPE] ?? null;
                \assert(\is_string($eventType));

                $this->connection->insert(self::TABLE, [
                    'aggregate_root_id' => $aggregateRootId->toString(),
                    'aggregate_root_version' => $message->aggregateVersion(),
                    'event_type' => $eventType,
                    'payload' => json_encode($serialized, JSON_THROW_ON_ERROR),
                    'recorded_at' => $message->timeOfRecording()->format('Y-m-d H:i:s.uP'),
                ]);
            }
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo($exception->getMessage(), $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        return yield from $this->yieldMessages(
            'SELECT payload FROM '.self::TABLE.' WHERE aggregate_root_id = :id ORDER BY aggregate_root_version ASC',
            ['id' => $id->toString()],
        );
    }

    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        return yield from $this->yieldMessages(
            'SELECT payload FROM '.self::TABLE.' WHERE aggregate_root_id = :id AND aggregate_root_version > :version ORDER BY aggregate_root_version ASC',
            ['id' => $id->toString(), 'version' => $aggregateRootVersion],
        );
    }

    public function paginate(PaginationCursor $cursor): Generator
    {
        if (!$cursor instanceof OffsetCursor) {
            throw new InvalidArgumentException('Cursor must be an '.OffsetCursor::class.'.');
        }

        $payloads = $this->connection->fetchFirstColumn(
            'SELECT payload FROM '.self::TABLE.' ORDER BY no ASC LIMIT :limit OFFSET :offset',
            ['limit' => $cursor->limit(), 'offset' => $cursor->offset()],
            ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        );

        $yielded = 0;
        foreach ($payloads as $payload) {
            \assert(\is_string($payload));
            yield $this->deserialize($payload);
            ++$yielded;
        }

        return $cursor->plusOffset($yielded);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return Generator<Message>
     */
    private function yieldMessages(string $sql, array $parameters): Generator
    {
        $version = 0;
        $result = $this->connection->executeQuery($sql, $parameters);

        while (false !== ($payload = $result->fetchOne())) {
            \assert(\is_string($payload));
            $message = $this->deserialize($payload);
            $version = $message->aggregateVersion();
            yield $message;
        }

        return $version;
    }

    private function deserialize(string $payload): Message
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return $this->serializer->unserializePayload($decoded);
    }
}
