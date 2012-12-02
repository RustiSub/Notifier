<?php
/**
 * This file is part of the Notifier package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Notifier;

class Notifier
{
    const TYPE_ALL = 'Notifier.all';

    /**
     * @var Handler\HandlerInterface[]
     */
    protected $handlers = array();

    protected $processors = array();

    /**
     * Pushes a handler on to the stack.
     *
     * @param Handler\HandlerInterface $handler
     */
    public function pushHandler(Handler\HandlerInterface $handler)
    {
        array_unshift($this->handlers, $handler);
    }

    /**
     * Pops a handler from the stack.
     *
     * @return Handler\HandlerInterface
     * @throws \LogicException
     */
    public function popHandler()
    {
        if (!count($this->handlers)) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }
        return array_shift($this->handlers);
    }

    /**
     * Send the message.
     *
     * @param Message\MessageInterface $message
     * @return bool
     */
    public function sendMessage(Message\MessageInterface $message)
    {
        if (!$this->handlers) {
            //$this->pushHandler(new StreamHandler('php://stderr', self::DEBUG));
            // @todo add a default handler
        }
        // check if any handler will handle this message
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($message)) {
                $handlerKey = $key;
                break;
            }
        }
        // none found
        if (null === $handlerKey) {
            return false;
        }
        // found at least one, process message and dispatch it
        foreach ($this->processors as $processor) {
            $message = call_user_func($processor, $message);
        }
        while (isset($this->handlers[$handlerKey]) && false === $this->handlers[$handlerKey]->handle($message)) {
            $handlerKey++;
        }

        return true;
    }
}
