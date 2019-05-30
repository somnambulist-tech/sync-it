<?php

declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Collection\Collection;

/**
 * Class Sessions
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\Sessions
 */
class Sessions implements \IteratorAggregate, \Countable
{

    /**
     * @var Collection
     */
    private $items;

    /**
     * Constructor.
     *
     * @param Collection|MutagenSession[] $items
     */
    public function __construct(Collection $items)
    {
        $this->items = $items;
    }

    public function getIterator()
    {
        return $this->items;
    }

    public function count()
    {
        return $this->items->count();
    }

    public function all(): Collection
    {
        return $this->items;
    }

    /**
     * Maps any available sessions to the appropriate task
     *
     * @param Collection $tasks
     */
    public function map(Collection $tasks): void
    {
        $tasks->each(function (SyncTask $task) {
            if ($this->hasSessionFor($task)) {
                $task->attachSession($this->getSessionFor($task));
            }

            return true;
        });
    }

    /**
     * Attempts to get the first matching session for the task
     *
     * @param SyncTask $task
     *
     * @return MutagenSession|null
     */
    public function getSessionFor(SyncTask $task): ?MutagenSession
    {
        $session = $this->items->filter(function (MutagenSession $session) use ($task) {
            return $session->equals($task);
        });

        if ($session->count() > 1) {
            throw new \RuntimeException(
                sprintf('Task "%s" matched more than one session; use "mutagen list" instead', $task->getLabel())
            );
        }

        return $session->first() ?: null;
    }

    public function hasSessionFor(SyncTask $task): bool
    {
        return null !== $this->getSessionFor($task);
    }
}
