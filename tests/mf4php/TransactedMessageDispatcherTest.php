<?php
declare(strict_types=1);

namespace mf4php;

use PHPUnit\Framework\TestCase;
use trf4php\AbstractObservableTransactionManager;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class TransactedMessageDispatcherTest extends TestCase
{
    /**
     * @var \trf4php\ObservableTransactionManager
     */
    private $tm;

    /**
     * @var TransactedMessageDispatcher
     */
    private $dispatcher;

    /**
     * @var MessageListener
     */
    private $listener;

    private $queue;

    public function setUp()
    {
        $this->queue = new DefaultQueue('q1');
        $this->tm = $this->getMockBuilder(AbstractObservableTransactionManager::class)
            ->setMethods(['commitInner', 'beginTransactionInner', 'rollbackInner', 'transactionalInner'])
            ->getMock();
        $this->dispatcher = $this->getMockBuilder(TransactedMessageDispatcher::class)
            ->setMethods(['immediateSend'])
            ->setConstructorArgs([$this->tm])
            ->getMock();
        $this->listener = $this->getMockBuilder(MessageListener::class)->getMock();
        $this->dispatcher->addListener($this->queue, $this->listener);
    }

    public function testObserverAttached()
    {
        self::assertTrue($this->tm->contains($this->dispatcher));
    }

    public function testWithoutTransaction()
    {
        $message = $this->getMockBuilder(Message::class)->getMock();
        $this->dispatcher
            ->expects(self::once())
            ->method('immediateSend')
            ->with($this->queue, $message);
        $this->dispatcher->send($this->queue, $message);
    }

    public function testIsBuffered()
    {
        self::assertFalse($this->dispatcher->isBuffered());
        $this->tm->beginTransaction();
        self::assertTrue($this->dispatcher->isBuffered());

        $this->tm->commit();
        self::assertFalse($this->dispatcher->isBuffered());

        $this->tm->beginTransaction();
        self::assertTrue($this->dispatcher->isBuffered());

        $this->tm->rollback();
        self::assertFalse($this->dispatcher->isBuffered());

        $this->tm
            ->expects(self::once())
            ->method('transactionalInner')
            ->will(
                self::returnCallback(
                    function ($closure) {
                        call_user_func($closure);
                    }
                )
            );
        $dispatcher = $this->dispatcher;
        $this->tm->transactional(
            function () use ($dispatcher) {
                TransactedMessageDispatcherTest::assertTrue($dispatcher->isBuffered());
            }
        );
        self::assertFalse($dispatcher->isBuffered());

    }

    public function testIsBufferedInCaseOfExceptions()
    {
        $dispatcher = $this->dispatcher;
        $this->tm->beginTransaction();
        self::assertTrue($dispatcher->isBuffered());
        $this->tm
            ->expects(self::once())
            ->method('commitInner')
            ->will(self::throwException(new \Exception()));
        try {
            $this->tm->commit();
        } catch (\Exception $e) {
        }
        self::assertFalse($dispatcher->isBuffered());

        $this->tm->beginTransaction();
        self::assertTrue($dispatcher->isBuffered());
        $this->tm
            ->expects(self::once())
            ->method('rollbackInner')
            ->will(self::throwException(new \Exception()));
        try {
            $this->tm->rollback();
        } catch (\Exception $e) {
        }
        self::assertFalse($dispatcher->isBuffered());

        $this->tm
            ->expects(self::once())
            ->method('transactionalInner')
            ->will(self::throwException(new \Exception()));
        try {
            $this->tm->transactional(
                function () {
                }
            );
        } catch (\Exception $e) {
        }
        self::assertFalse($dispatcher->isBuffered());
    }

    public function testWithTransactionRollback()
    {
        $message = $this->getMockBuilder(Message::class)->getMock();
        $this->dispatcher
            ->expects(self::never())
            ->method('immediateSend');
        $this->tm->beginTransaction();
        $this->dispatcher->send($this->queue, $message);
        $this->tm->rollback();
    }

    public function testDelayedSendInTransaction()
    {
        $message = $this->getMockBuilder(Message::class)->getMock();
        $message2 = $this->getMockBuilder(Message::class)->getMock();
        $queue2 = new DefaultQueue('q2');
        $this->dispatcher
            ->expects(self::at(0))
            ->method('immediateSend')
            ->with($this->queue, $message);
        $this->dispatcher
            ->expects(self::at(1))
            ->method('immediateSend')
            ->with($this->queue, $message2);
        $this->dispatcher
            ->expects(self::at(2))
            ->method('immediateSend')
            ->with($queue2, $message);
        $this->dispatcher
            ->expects(self::at(3))
            ->method('immediateSend')
            ->with($queue2, $message2);

        $this->tm->beginTransaction();
        self::assertTrue($this->dispatcher->isBuffered());
        $this->dispatcher->send($this->queue, $message);
        $this->dispatcher->send($this->queue, $message2);
        $this->dispatcher->send($queue2, $message);
        $this->tm->commit();
        self::assertFalse($this->dispatcher->isBuffered());

        $this->tm->beginTransaction();
        self::assertTrue($this->dispatcher->isBuffered());
        $this->dispatcher->send($queue2, $message2);
        $this->tm->commit();
        self::assertFalse($this->dispatcher->isBuffered());
    }

    public function testTwoTransactionIfFirstIsFailed()
    {
        $message = $this->getMockBuilder(Message::class)->getMock();
        $this->dispatcher
            ->expects(self::once())
            ->method('immediateSend')
            ->with($this->queue, $message);

        $this->tm->beginTransaction();
        $this->dispatcher->send($this->queue, $message);
        $this->tm->rollback();

        $this->tm->beginTransaction();
        $this->dispatcher->send($this->queue, $message);
        $this->tm->commit();
    }
}
