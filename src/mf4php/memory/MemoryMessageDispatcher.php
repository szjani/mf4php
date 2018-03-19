<?php
declare(strict_types=1);

namespace mf4php\memory;

use mf4php\AbstractMessageDispatcher;
use mf4php\Message;
use mf4php\MessageListener;
use mf4php\Queue;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class MemoryMessageDispatcher extends AbstractMessageDispatcher
{
    public function send(Queue $queue, Message $message) : void
    {
        /* @var $listener MessageListener */
        foreach ($this->getListeners($queue) as $listener) {
            $listener->onMessage($message);
        }
    }
}
