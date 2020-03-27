<?php

declare(strict_types=1);

namespace Prometee\PayumStripeCheckoutSession\Request\Api;

use Payum\Core\Request\Convert;
use Payum\Core\Security\TokenInterface;
use Prometee\PayumStripeCheckoutSession\Wrapper\EventWrapperInterface;
use Stripe\Event;

final class ResolveWebhookEvent extends Convert
{
    /**
     * @param TokenInterface|null $token
     */
    public function __construct(TokenInterface $token = null)
    {
        parent::__construct(null, Event::class, $token);
    }

    /**
     * @return EventWrapperInterface|null
     */
    public function getEventWrapper(): ?EventWrapperInterface
    {
        if ($this->getResult() instanceof EventWrapperInterface) {
            return $this->getResult();
        }

        return null;
    }

    /**
     * @param EventWrapperInterface $eventWrapper
     */
    public function setEventWrapper(EventWrapperInterface $eventWrapper): void
    {
        $this->setResult($eventWrapper);
    }
}