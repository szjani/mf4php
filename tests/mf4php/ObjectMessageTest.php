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
use Serializable;

/**
 * @author Szurovecz János <szjani@szjani.hu>
 */
class ObjectMessageTest extends PHPUnit_Framework_TestCase
{
    public function testCreation()
    {
        $obj = new SampleObject('test@host.com');
        $message = new ObjectMessage($obj);
        self::assertInstanceOf('\DateTime', $message->getDateTime());
        self::assertSame($obj, $message->getObject());
    }

    public function testSerialization()
    {
        $obj = new SampleObject('test@host.com');

        $ser = serialize($obj);
        $obj2 = unserialize($ser);
        self::assertEquals($obj->getEmail(), $obj2->getEmail());

        $message = new ObjectMessage($obj);
        $serialized = serialize($message);
        $deserMsg = unserialize($serialized);
        self::assertEquals($obj->getEmail(), $deserMsg->getObject()->getEmail());
        self::assertEquals($message->getDateTime(), $deserMsg->getDateTime());
    }
}

class SampleObject implements Serializable
{
    private $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function serialize()
    {
        return serialize($this->email);
    }

    public function unserialize($serialized)
    {
        $this->email = unserialize($serialized);
    }
}
