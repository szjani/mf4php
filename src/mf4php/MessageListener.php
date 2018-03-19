<?php
declare(strict_types=1);

namespace mf4php;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface MessageListener
{
    public function onMessage(Message $message) : void;
}
