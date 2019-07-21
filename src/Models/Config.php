<?php

declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Collection\MutableCollection as Collection;

/**
 * Class Config
 *
 * @package    SyncIt\Services\Config
 * @subpackage SyncIt\Models\Config
 */
class Config
{

    /**
     * @var Collection
     */
    private $common;

    /**
     * @var Collection|SyncTask[]
     */
    private $tasks;

    /**
     * @var Collection
     */
    private $parameters;

    /**
     * Constructor.
     *
     * @param Collection            $common
     * @param Collection|SyncTask[] $tasks
     * @param Collection            $parameters
     */
    public function __construct(Collection $common, $tasks, Collection $parameters)
    {
        $this->common     = $common;
        $this->tasks      = $tasks;
        $this->parameters = $parameters;
    }

    /**
     * @return Collection
     */
    public function getCommon(): Collection
    {
        return $this->common;
    }

    /**
     * @return Collection|SyncTask[]
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @return Collection
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
