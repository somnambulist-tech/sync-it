<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Components\Collection\MutableCollection as Collection;

/**
 * Class Config
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\Config
 */
class Config
{
    public function __construct(
        private Collection $common,
        private Collection $tasks,
        private Collection $parameters
    ) {
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
