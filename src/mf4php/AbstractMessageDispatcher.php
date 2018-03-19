<?php
declare(strict_types=1);

namespace mf4php;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
abstract class AbstractMessageDispatcher implements MessageDispatcher
{
    private $listeners = array();

    public function addListener(Queue $queue, MessageListener $messageListener) : void
    {
        $this->listeners[$queue->getName()][] = $messageListener;
    }

    public function removeListener(Queue $queue, MessageListener $messageListener) : void
    {
        if (!array_key_exists($queue->getName(), $this->listeners)) {
            return;
        }
        $key = array_search($messageListener, $this->listeners[$queue->getName()], true);
        if ($key !== false) {
            unset($this->listeners[$queue->getName()][$key]);
        }
    }

    public function getListeners(Queue $queue) : array
    {
        return array_key_exists($queue->getName(), $this->listeners)
            ? $this->listeners[$queue->getName()]
            : null;
    }

    public function hasListeners(Queue $queue) : bool
    {
        if (!array_key_exists($queue->getName(), $this->listeners)) {
            return false;
        }
        return 0 < count($this->listeners[$queue->getName()]);
    }
}
