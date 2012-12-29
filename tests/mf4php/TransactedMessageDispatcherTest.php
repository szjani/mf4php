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

use PHPUnit_Framework_TestCase;

/**
 * @author Szurovecz János <szjani@szjani.hu>
 */
class TransactedMessageDispatcherTest extends PHPUnit_Framework_TestCase
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
        $this->tm = $this->getMock(
            'trf4php\AbstractObservableTransactionManager',
            array(
                'commitInner',
                'beginTransactionInner',
                'rollbackInner',
                'transactionalInner'
            )
        );
        $this->dispatcher = $this->getMock(__NAMESPACE__ . '\TransactedMessageDispatcher', array('immediateSend'), array($this->tm));
        $this->listener = $this->getMock(__NAMESPACE__ . '\MessageListener');
        $this->dispatcher->addListener($this->queue, $this->listener);
    }

    public function testObserverAttached()
    {
        self::assertTrue($this->tm->contains($this->dispatcher));
    }

    public function testWithoutTransaction()
    {
        $message = $this->getMock(__NAMESPACE__ . '\Message');
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
        $message = $this->getMock(__NAMESPACE__ . '\Message');
        $this->dispatcher
            ->expects(self::never())
            ->method('immediateSend');
        $this->tm->beginTransaction();
        $this->dispatcher->send($this->queue, $message);
        $this->tm->rollback();
    }

    public function testDelayedSendInTransaction()
    {
        $message = $this->getMock(__NAMESPACE__ . '\Message');
        $message2 = $this->getMock(__NAMESPACE__ . '\Message');
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
        $message = $this->getMock(__NAMESPACE__ . '\Message');
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
