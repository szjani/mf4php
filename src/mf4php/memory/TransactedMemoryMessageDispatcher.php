<?php
declare(strict_types=1);

namespace mf4php\memory;

use mf4php\Message;
use mf4php\MessageListener;
use mf4php\Queue;
use mf4php\TransactedMessageDispatcher;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class TransactedMemoryMessageDispatcher extends TransactedMessageDispatcher
{
    protected function immediateSend(Queue $queue, Message $message) : void
    {
        /* @var $listener MessageListener */
        foreach ($this->getListeners($queue) as $listener) {
            $listener->onMessage($message);
        }
    }
}
