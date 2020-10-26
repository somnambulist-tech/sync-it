<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Collection\MutableCollection as Collection;

/**
 * Class SyncTask
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\SyncTask
 */
class SyncTask
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $target;

    /**
     * @var bool
     */
    private $useCommon;

    /**
     * @var Collection
     */
    private $options;

    /**
     * @var Collection
     */
    private $ignore;

    /**
     * @var Collection
     */
    private $groups;

    /**
     * @var MutagenSession|null
     */
    private $session;

    public function __construct(string $label, string $source, string $target, bool $useCommon, Collection $options, Collection $ignore, Collection $groups)
    {
        $this->label     = $label;
        $this->source    = $source;
        $this->target    = $target;
        $this->useCommon = $useCommon;
        $this->options   = $options;
        $this->ignore    = $ignore;
        $this->groups    = $groups;
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
