<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\Event\EventType;
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * Base class for an aggregate root
 */
abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    /**
     * @var string
     * @Flow\Transient
     */
    protected $aggregateIdentifier;

    /**
     * @var string
     * @Flow\Transient
     */
    protected $aggregateName;

    /**
     * @var EventTransport[]
     * @Flow\Transient
     */
    protected $events = [];

    /**
     * @param string $identifier
     * @return void
     */
    protected function setAggregateIdentifier($identifier)
    {
        $this->aggregateIdentifier = $identifier;
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier(): string
    {
        return $this->aggregateIdentifier;
    }

    /**
     * Apply an event to the current aggregate root
     *
     * If the event aggregate identifier and name is not set the event
     * if automatically updated with the current aggregate identifier
     * and name.
     *
     * @param  EventInterface $event
     * @param  array $metadata
     * @return void
     * @api
     */
    public function recordThat(EventInterface $event, array $metadata = [])
    {
        $messageMetadata = new MessageMetadata($metadata);

        $this->apply($event);

        $this->events[] = new EventTransport($event, $messageMetadata);
    }

    /**
     * Returns the events which have been recorded since the last call of this method.
     *
     * This method is used internally by the persistence layer (for example, the Event Store).
     *
     * @return array
     */
    public function pullUncommittedEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    /**
     * Apply the given event to this aggregate root.
     *
     * @param  EventInterface $event
     * @return void
     */
    protected function apply(EventInterface $event)
    {
        $name = EventType::get($event);

        $nameParts = Arrays::trimExplode('\\', $name);
        $className = array_pop($nameParts);

        $method = sprintf('when%s', ucfirst($className));

        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }
}
