<?php
/*
 * Copyright (c) 2013 Szurovecz János
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
use mf4php\Message;
use PHPUnit_Framework_TestCase;
use trf4php\AbstractObservableTransactionManager;

class TransactedMemoryMessageDispatcherTest extends PHPUnit_Framework_TestCase
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
        $this->tm = $this->getMock(
            'trf4php\AbstractObservableTransactionManager',
            array('beginTransactionInner', 'commitInner', 'rollbackInner', 'transactionalInner')
        );
        $this->dispatcher = new TransactedMemoryMessageDispatcher($this->tm);
    }

    public function testLoop()
    {
        $tm = $this->tm;
        $message = $this->getMock('mf4php\Message');
        $listener = $this->getMock('\mf4php\MessageListener');
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
