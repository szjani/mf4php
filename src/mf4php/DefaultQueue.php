<?php
declare(strict_types=1);

namespace mf4php;

use InvalidArgumentException;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class DefaultQueue implements Queue
{
    private $name;

    public function __construct($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Queue name must be string!');
        }
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }
}
