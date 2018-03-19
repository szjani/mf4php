<?php
declare(strict_types=1);

namespace mf4php;

use mf4php\Message;
use mf4php\Queue;
use trf4php\ObservableTransactionManager;
use trf4php\TransactionManagerObserver;

/**
 * Delayed message sending if it is used inside a transaction.
 * It stores messages until a transaction is committed. If there is a transaction error,
 * collected messages won't be sent.
 *
 * You can reach persisted data in event handlers even if you send your messages
 * before committing the transaction.
 *
 * You have to use the same ObservableTransactionManager object
 * for transaction handling as is passed to the constructor!
 *
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
abstract class TransactedMessageDispatcher extends AbstractMessageDispatcher implements TransactionManagerObserver
{
    private $buffered = false;

    private $queueBuffer = array();

    private $messageBuffer = array();

    /**
     * @param ObservableTransactionManager $transactionManager
     */
    public function __construct(ObservableTransactionManager $transactionManager)
    {
        $transactionManager->attach($this);
    }

    /**
     * This method is called to send buffered messages
     * or to send messages if buffering is turned off.
     */
    abstract protected function immediateSend(Queue $queue, Message $message) : void;

    /**
     * @return boolean
     */
    public function isBuffered() : bool
    {
        return $this->buffered;
    }

    protected function startBuffer() : void
    {
        $this->buffered = true;
    }

    protected function stopBuffer() : void
    {
        $this->buffered = false;
        $this->queueBuffer = array();
        $this->messageBuffer = array();
    }

    /**
     * Send message which may will be buffered
     * depending on the current transaction status.
     *
     * @param Queue $queue
     * @param Message $message
     */
    final public function send(Queue $queue, Message $message) : void
    {
        if (!$this->isBuffered()) {
            $this->immediateSend($queue, $message);
        } else {
            $this->queueBuffer[$queue->getName()] = $queue;
            $this->messageBuffer[$queue->getName()][] = $message;
        }
    }

    /**
     * Send all buffered messages out.
     * Message listeners can also send messages, so the member variables
     * must be reset before sending the messages.
     * Otherwise it would cause infinite loop.
     */
    protected function flush() : void
    {
        $queueBuffer = $this->queueBuffer;
        $messageBuffer = $this->messageBuffer;
        $this->stopBuffer();
        foreach ($queueBuffer as $queue) {
            foreach ($messageBuffer[$queue->getName()] as $message) {
                $this->immediateSend($queue, $message);
            }
        }
    }

    /**
     * Observes transaction manager.
     *
     * @param ObservableTransactionManager $manager
     * @param string $event
     */
    public function update(ObservableTransactionManager $manager, $event) : void
    {
        switch ($event) {
            case ObservableTransactionManager::POST_BEGIN_TRANSACTION:
            case ObservableTransactionManager::PRE_TRANSACTIONAL:
                $this->startBuffer();
                break;
            case ObservableTransactionManager::POST_COMMIT:
            case ObservableTransactionManager::POST_TRANSACTIONAL:
                $this->flush();
                break;
            case ObservableTransactionManager::POST_ROLLBACK:
            case ObservableTransactionManager::ERROR_COMMIT:
            case ObservableTransactionManager::ERROR_ROLLBACK:
            case ObservableTransactionManager::ERROR_TRANSACTIONAL:
                $this->stopBuffer();
                break;
        }
    }
}
