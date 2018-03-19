<?php
declare(strict_types=1);

namespace mf4php;

use mf4php\Message;
use mf4php\MessageListener;
use mf4php\Queue;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
interface MessageDispatcher
{
    /**
     * @param Queue $queue
     * @param Message $message
     * @throws MessageException
     */
    public function send(Queue $queue, Message $message) : void;

    /**
     * @param Queue $queue
     * @param MessageListener $messageListener
     */
    public function addListener(Queue $queue, MessageListener $messageListener) : void;

    /**
     * @param Queue $queue
     * @param MessageListener $messageListener
     */
    public function removeListener(Queue $queue, MessageListener $messageListener) : void;

    /**
     * @param Queue $queue
     * @return array
     */
    public function getListeners(Queue $queue) : array;

    /**
     * @param Queue $queue
     * @return boolean
     */
    public function hasListeners(Queue $queue) : bool;
}
