<?php
declare(strict_types=1);

namespace mf4php;

use DateTime;
use Serializable;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface Message extends Serializable
{
    /**
     * @return DateTime
     */
    public function getDateTime() : DateTime;
}
