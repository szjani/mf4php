mf4php
======

This is a messaging facade library. It wraps and hides messaging (event) tools thus you can whenever switch to another one.

Features
--------

There is one implementation shipped with this package, the MemoryMessageDispatcher. It is useful for synchron communications.
There is one asynchronous implementation: [mf4php/mf4php-beanstalk](https://github.com/szjani/mf4php-beanstalk)

Feel free to implement a binding for your preferred event library.

History
-------

### 1.1

There is a message dispatcher (abstract) implementation: TransactedMessageDispatcher.
It can be used with an ObservableTransactionManager coming from [trf4php](https://github.com/szjani/trf4php).
Sending messages inside a transaction will be delayed until the transaction is committed. The benefit of this feature
is that if a message sending causes error then transaction will be rolled back (buffered messages will be deleted).
If the commit is successful, then buffered messages will be delivered and inserted/updated records can be reached in message handlers
even if the messages were sent before the commit.

```php
<?php
/* @var $transactionManager ObservableTransactionManager */
$dispatcher = new TransactedMemoryMessageDispatcher($transactionManager);

/* @var $listener MessageListener */
$dispatcher->addEventListener($queue, $listener);

$transactionManager->beginTransaction();
try {
    // do anything, modify database, etc.
    $dispatcher->send($queue, $message);
    $transactionManager->commit();
    // $message will be delivered to $listener after commit
} catch (Exception $e) {
    $transactionManager->rollback();
    // $message won't be delivered
}
```

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
