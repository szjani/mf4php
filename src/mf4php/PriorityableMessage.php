<?php
declare(strict_types=1);

namespace mf4php;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface PriorityableMessage extends Message
{
    /**
     * @return int
     */
    public function getPriority() : int;
}
