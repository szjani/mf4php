<?php
declare(strict_types=1);

namespace mf4php;

use PHPUnit\Framework\TestCase;
use Serializable;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class ObjectMessageTest extends TestCase
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
