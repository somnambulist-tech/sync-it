<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Components\Collection\MutableCollection as Collection;

/**
 * Class SyncTask
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\SyncTask
 */
class SyncTask
{
    private ?MutagenSession $session = null;

    public function __construct(
        private string $label,
        private string $source,
        private string $target,
        private bool $useCommon,
        private Collection $options,
        private Collection $ignore,
        private Collection $groups
    ) {
    }

    public function attachSession(MutagenSession $session): void
    {
        $this->session = $session;
    }

    public function isRunning(): bool
    {
        return $this->session instanceof MutagenSession && $this->session->getId();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function shouldUseCommon(): bool
    {
        return $this->useCommon;
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function getIgnore(): Collection
    {
        return $this->ignore;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function getSession(): ?MutagenSession
    {
        return $this->session;
    }
}
