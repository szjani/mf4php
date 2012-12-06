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

use DateTime;
use Serializable;

/**
 * @author Szurovecz János <szjani@szjani.hu>
 */
class ObjectMessage implements Message
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Serializable
     */
    private $object;

    public function __construct(Serializable $object)
    {
        $this->dateTime = new DateTime();
        $this->object = $object;
    }

    public function getDateTime()
    {
        return clone $this->dateTime;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function serialize()
    {
        return serialize(
            array(
                'dateTime' => $this->dateTime,
                'object' => $this->object
            )
        );
    }

    public function unserialize($serialized)
    {
        $array = unserialize($serialized);
        $this->dateTime = $array['dateTime'];
        $this->object = $array['object'];
    }
}