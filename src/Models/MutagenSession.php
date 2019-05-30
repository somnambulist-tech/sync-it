<?php

declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\ValueObjects\AbstractValueObject;

/**
 * Class MutagenSession
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\MutagenSession
 */
class MutagenSession extends AbstractValueObject
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string|null
     */
    private $connectionState;

    /**
     * @var string|null
     */
    private $status;

    /**
     * Constructor.
     *
     * @param string      $id
     * @param string      $source
     * @param string      $target
     * @param string|null $connectionState
     * @param string|null $status
     */
    public function __construct(string $id, string $source, string $target, ?string $connectionState = null, ?string $status = null)
    {
        $this->id              = $id;
        $this->source          = $source;
        $this->target          = $target;
        $this->connectionState = $connectionState;
        $this->status          = $status;
    }

    public function toString(): string
    {
        return (string)$this->id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return string|null
     */
    public function getConnectionState(): ?string
    {
        return $this->connectionState;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function equals($object): bool
    {
        if (!$object instanceof SyncTask) {
            return false;
        }

        return $this->source === $object->getSource() && $this->target === $object->getTarget();
    }
}
