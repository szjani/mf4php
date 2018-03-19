<?php
declare(strict_types=1);

namespace mf4php;

/**
 * Queue interface. Implementations can store events.
 * Each queue is identified by its name.
 *
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface Queue
{
    /**
     * @return string
     */
    public function getName() : string;
}
