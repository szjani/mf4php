<?php
declare(strict_types=1);

namespace mf4php\memory;

use mf4php\DefaultQueue;
use mf4php\Message;
use mf4php\MessageListener;
use PHPUnit\Framework\TestCase;
use trf4php\AbstractObservableTransactionManager;

class TransactedMemoryMessageDispatcherTest extends TestCase
{
    /**
     * @var TransactedMemoryMessageDispatcher
     */
    private $dispatcher;

    private $tm;

    /**
     * @var DefaultQueue
     */
    private $queue;

    public function setUp()
    {
        $this->queue = new DefaultQueue(__CLASS__);
        $this->tm = $this->getMockBuilder(AbstractObservableTransactionManager::class)
            ->setMethods(['beginTransactionInner', 'commitInner', 'rollbackInner', 'transactionalInner'])
            ->getMock();
        $this->dispatcher = new TransactedMemoryMessageDispatcher($this->tm);
    }

    public function testLoop()
    {
        $tm = $this->tm;
        $message = $this->getMockBuilder(Message::class)->getMock();
        $listener = $this->getMockBuilder(MessageListener::class)->getMock();
        $listener
            ->expects(self::once())
            ->method('onMessage')
            ->with($message)
            ->will(
                self::returnCallback(
                    function (Message $message) use ($tm) {
                        $tm->beginTransaction();
                        $tm->commit();
                    }
                )
            );
        $this->dispatcher->addListener($this->queue, $listener);

        $this->tm->beginTransaction();
        $this->dispatcher->send($this->queue, $message);
        $this->tm->commit();
    }
}
