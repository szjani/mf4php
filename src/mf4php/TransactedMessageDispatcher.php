<?php
/*
 * Copyright (c) 2012 Szurovecz János
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
 * @author Szurovecz János <szjani@szjani.hu>
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
    abstract protected function immediateSend(Queue $queue, Message $message);

    /**
     * @return boolean
     */
    public function isBuffered()
    {
        return $this->buffered;
    }

    protected function startBuffer()
    {
        $this->buffered = true;
    }

    protected function stopBuffer()
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
    final public function send(Queue $queue, Message $message)
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
    protected function flush()
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
    public function update(ObservableTransactionManager $manager, $event)
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
