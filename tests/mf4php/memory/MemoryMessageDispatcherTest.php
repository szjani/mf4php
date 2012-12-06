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

namespace mf4php\memory;

use mf4php\DefaultQueue;
use PHPUnit_Framework_TestCase;

/**
 * @author Szurovecz János <szjani@szjani.hu>
 */
class MemoryMessageDispatcherTest extends PHPUnit_Framework_TestCase
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

        $listener1 = $this->getMock('mf4php\MessageListener');
        $listener2 = $this->getMock('mf4php\MessageListener');

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
        $listener1 = $this->getMock('mf4php\MessageListener');
        self::assertFalse($this->dispatcher->hasListeners($queue1));
        $this->dispatcher->addListener($queue1, $listener1);
        self::assertTrue($this->dispatcher->hasListeners($queue1));
    }

    public function testRemoveListener()
    {
        $queue = new DefaultQueue('q1');
        $listener1 = $this->getMock('mf4php\MessageListener');
        $listener2 = $this->getMock('mf4php\MessageListener');
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
        $message = $this->getMock('mf4php\Message');

        $listener1 = $this->getMock('mf4php\MessageListener');
        $listener1
            ->expects(self::once())
            ->method('onMessage')
            ->with($message);

        $listener2 = $this->getMock('mf4php\MessageListener');
        $listener2
            ->expects(self::once())
            ->method('onMessage')
            ->with($message);

        $this->dispatcher->addListener($queue, $listener1);
        $this->dispatcher->addListener($queue, $listener2);
        $this->dispatcher->send($queue, $message);
    }
}
