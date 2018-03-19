<?php
declare(strict_types=1);

namespace mf4php\memory;

use mf4php\DefaultQueue;
use mf4php\Message;
use mf4php\MessageListener;
use PHPUnit\Framework\TestCase;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class MemoryMessageDispatcherTest extends TestCase
{
    private $dispatcher;

    public function setUp()
    {
        $this->dispatcher = new MemoryMessageDispatcher();
    }

    public function testAddListener()
    {
        $queue1 = new DefaultQueue('q1');
        $queue2 = new DefaultQueue('q2');

        $listener1 = $this->getMockBuilder(MessageListener::class)->getMock();
        $listener2 = $this->getMockBuilder(MessageListener::class)->getMock();

        $this->dispatcher->addListener($queue1, $listener1);
        $this->dispatcher->addListener($queue2, $listener2);

        $queue1Listeners = $this->dispatcher->getListeners($queue1);
        self::assertContains($listener1, $queue1Listeners);
        self::assertEquals(1, count($queue1Listeners));

        $queue2Listeners = $this->dispatcher->getListeners($queue2);
        self::assertContains($listener2, $queue2Listeners);
        self::assertEquals(1, count($queue2Listeners));
    }

    public function testHasListener()
    {
        $queue1 = new DefaultQueue('q1');
        $listener1 = $this->getMockBuilder(MessageListener::class)->getMock();
        self::assertFalse($this->dispatcher->hasListeners($queue1));
        $this->dispatcher->addListener($queue1, $listener1);
        self::assertTrue($this->dispatcher->hasListeners($queue1));
    }

    public function testRemoveListener()
    {
        $queue = new DefaultQueue('q1');
        $listener1 = $this->getMockBuilder(MessageListener::class)->getMock();
        $listener2 = $this->getMockBuilder(MessageListener::class)->getMock();
        self::assertFalse($this->dispatcher->hasListeners($queue));

        $this->dispatcher->addListener($queue, $listener1);
        $this->dispatcher->addListener($queue, $listener2);
        self::assertTrue($this->dispatcher->hasListeners($queue));
        self::assertEquals(2, count($this->dispatcher->getListeners($queue)));

        $this->dispatcher->removeListener($queue, $listener1);
        self::assertTrue($this->dispatcher->hasListeners($queue));
        $listeners = $this->dispatcher->getListeners($queue);
        self::assertEquals(1, count($listeners));
        self::assertSame($listener2, reset($listeners));
    }

    public function testSend()
    {
        $queue = new DefaultQueue('q1');
        $message = $this->getMockBuilder(Message::class)->getMock();

        $listener1 = $this->getMockBuilder(MessageListener::class)->getMock();
        $listener1
            ->expects(self::once())
            ->method('onMessage')
            ->with($message);

        $listener2 = $this->getMockBuilder(MessageListener::class)->getMock();
        $listener2
            ->expects(self::once())
            ->method('onMessage')
            ->with($message);

        $this->dispatcher->addListener($queue, $listener1);
        $this->dispatcher->addListener($queue, $listener2);
        $this->dispatcher->send($queue, $message);
    }
}
