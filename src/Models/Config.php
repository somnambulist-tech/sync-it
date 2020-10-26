<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Collection\MutableCollection as Collection;

/**
 * Class Config
 *
 * @package    SyncIt\Models
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

    public function __construct(Collection $common, Collection $tasks, Collection $parameters)
    {
        $this->common     = $common;
        $this->tasks      = $tasks;
        $this->parameters = $parameters;
    }

    public function getCommon(): Collection
    {
        return $this->common;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
