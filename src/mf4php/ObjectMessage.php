<?php
declare(strict_types=1);

namespace mf4php;

use DateTime;
use Serializable;

/**
 * @author Janos Szurovecz <szjani@szjani.hu>
 */
class ObjectMessage implements Message
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Serializable
     */
    protected $object;

    public function __construct(Serializable $object)
    {
        $this->dateTime = new DateTime();
        $this->object = $object;
    }

    public function getDateTime() : DateTime
    {
        return clone $this->dateTime;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $key => $value) {
            $this->$key = $value;
        }
    }
}
