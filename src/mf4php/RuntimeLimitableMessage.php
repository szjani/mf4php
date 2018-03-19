<?php
declare(strict_types=1);

namespace mf4php;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface RuntimeLimitableMessage extends Message
{
    /**
     * @return int seconds
     */
    public function getRuntimeLimit() : int;
}
