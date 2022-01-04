<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Components\Domain\Entities\AbstractValueObject;

/**
 * Class MutagenSession
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\MutagenSession
 */
class MutagenSession extends AbstractValueObject
{
    public function __construct(
        private string $id,
        private string $source,
        private string $target,
        private ?string $connectionState = null,
        private ?string $status = null
    ) {
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getConnectionState(): ?string
    {
        return $this->connectionState;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function equals(object $object): bool
    {
        if (!$object instanceof SyncTask) {
            return false;
        }

        return $this->source === $object->getSource() && $this->target === $object->getTarget();
    }
}
