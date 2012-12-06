mf4php
======

This is a messaging facade library. It wraps and hides messaging (event) tools thus you can whenever switch to another one.

Features
--------

There is one implementation shipped with this package, the MemoryMessageDispatcher. It is useful for synchron communications.
The future plan is to implement this interface for queue systems (gearman, beanstalk, etc.).

Feel free to implement a binding for your preferred event library.

Configuration
-------------

```php
<?php
$dispatcher = new MemoryMessageDispatcher();
$queue = new DefaultQueue('queue');

/* @var $listener MessageListener */
$dispatcher->addEventListener($queue, $listener);
```

Send events
-----------
```php
<?php
/* @var $object Serializable */
$message = new ObjectMessage($object);
$dispatcher->send($queue, $message);
// onMessage method in $listener is called
```
