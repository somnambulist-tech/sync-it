<?php

declare(strict_types=1);

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
     * @var MutagenSession|null
     */
    private $session;

    /**
     * Constructor.
     *
     * @param string     $label
     * @param string     $source
     * @param string     $target
     * @param bool       $useCommon
     * @param Collection $options
     * @param Collection $ignore
     */
    public function __construct(string $label, string $source, string $target, bool $useCommon, Collection $options, Collection $ignore)
    {
        $this->label     = $label;
        $this->source    = $source;
        $this->target    = $target;
        $this->useCommon = $useCommon;
        $this->options   = $options;
        $this->ignore    = $ignore;
    }

    /**
     * @param MutagenSession $session
     */
    public function attachSession(MutagenSession $session): void
    {
        $this->session = $session;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->session instanceof MutagenSession && $this->session->getId();
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
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
     * @return bool
     */
    public function shouldUseCommon(): bool
    {
        return $this->useCommon;
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @return Collection
     */
    public function getIgnore(): Collection
    {
        return $this->ignore;
    }

    /**
     * @return MutagenSession|null
     */
    public function getSession(): ?MutagenSession
    {
        return $this->session;
    }
}
