<?php

namespace Tests\Prometee\PayumStripeCheckoutSession\Action\Api;

use LogicException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\GetHttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prometee\PayumStripeCheckoutSession\Action\Api\ResolveWebhookEventAction;
use Prometee\PayumStripeCheckoutSession\Api\KeysInterface;
use Prometee\PayumStripeCheckoutSession\Request\Api\ConstructEvent;
use Prometee\PayumStripeCheckoutSession\Request\Api\ResolveWebhookEvent;
use Prometee\PayumStripeCheckoutSession\Wrapper\EventWrapper;
use Prometee\PayumStripeCheckoutSession\Wrapper\EventWrapperInterface;
use Stripe\Event;

class ResolveWebhookEventActionTest extends TestCase
{
    use ApiAwareActionTrait;

    /**
     * @test
     */
    public function shouldImplements()
    {
        $action = new ResolveWebhookEventAction();

        $this->assertInstanceOf(ActionInterface::class, $action);
        $this->assertInstanceOf(ApiAwareInterface::class, $action);
        $this->assertInstanceOf(GatewayAwareInterface::class, $action);
    }

    /**
     * @test
     */
    public function shouldThrowLogicExceptionWhenNoStripeSignatureIsFound()
    {
        $action = new ResolveWebhookEventAction();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class));

        $apiMock = $this->createApiMock(false);

        $action->setApiClass(KeysInterface::class);
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        $request = new ResolveWebhookEvent();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A Stripe signature is required !');

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldResolveWebhookEventWithSymfonyRequestBridge()
    {
        $action = new ResolveWebhookEventAction();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->headers = [
                    'stripe-signature' => ['stripeSignature']
                ];
                $request->content = 'stripeContent';
            }));
        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ConstructEvent::class))
            ->will($this->returnCallback(function (ConstructEvent $request) {
                $this->assertEquals('stripeContent', $request->getPayload());
                $this->assertEquals('stripeSignature', $request->getSigHeader());
                $this->assertEquals('whsec_test', $request->getWebhookSecretKey());
                $request->setEventWrapper(new EventWrapper(
                    $request->getWebhookSecretKey(),
                    new Event()
                ));
            }));

        $apiMock = $this->createApiMock(false);
        $apiMock
            ->expects($this->once())
            ->method('getWebhookSecretKeys')
            ->willReturn(['whsec_test'])
        ;

        $action->setApiClass(KeysInterface::class);
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        $request = new ResolveWebhookEvent();

        $action->execute($request);

        $this->assertInstanceOf(EventWrapperInterface::class, $request->getEventWrapper());
        $this->assertInstanceOf(EventWrapperInterface::class, $request->getResult());
        $this->assertEquals($request->getEventWrapper(), $request->getResult());
    }

    /**
     * @test
     */
    public function shouldResolveWebhookEventWithPlainPHP()
    {
        $action = new ResolveWebhookEventAction();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $_SERVER['HTTP_STRIPE_SIGNATURE'] = 'stripeSignature';
                $request->content = 'stripeContent';
            }));
        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ConstructEvent::class))
            ->will($this->returnCallback(function (ConstructEvent $request) {
                $this->assertEquals('stripeContent', $request->getPayload());
                $this->assertEquals('stripeSignature', $request->getSigHeader());
                $this->assertEquals('whsec_test', $request->getWebhookSecretKey());
                $request->setEventWrapper(new EventWrapper(
                    $request->getWebhookSecretKey(),
                    new Event()
                ));
            }));

        $apiMock = $this->createApiMock(false);
        $apiMock
            ->expects($this->once())
            ->method('getWebhookSecretKeys')
            ->willReturn(['whsec_test'])
        ;

        $action->setApiClass(KeysInterface::class);
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        $request = new ResolveWebhookEvent();

        $action->execute($request);

        $this->assertInstanceOf(EventWrapperInterface::class, $request->getEventWrapper());
        $this->assertInstanceOf(EventWrapperInterface::class, $request->getResult());
        $this->assertEquals($request->getEventWrapper(), $request->getResult());
    }

    /**
     * @test
     */
    public function shouldRequestNotSupportedExceptionWhenTheWebhookCanNotBeResolved()
    {
        $action = new ResolveWebhookEventAction();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $_SERVER['HTTP_STRIPE_SIGNATURE'] = 'stripeSignature';
                $request->content = 'stripeContent';
            }));
        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(ConstructEvent::class))
            ->will($this->returnCallback(function (ConstructEvent $request) {
                $this->assertEquals('stripeContent', $request->getPayload());
                $this->assertEquals('stripeSignature', $request->getSigHeader());
                $this->assertEquals('whsec_test', $request->getWebhookSecretKey());
                $request->setEventWrapper(null);
            }));

        $apiMock = $this->createApiMock(false);
        $apiMock
            ->expects($this->once())
            ->method('getWebhookSecretKeys')
            ->willReturn(['whsec_test'])
        ;

        $action->setApiClass(KeysInterface::class);
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);

        $request = new ResolveWebhookEvent();

        $this->expectException(RequestNotSupportedException::class);

        $action->execute($request);
    }

    /**
     * @return MockObject&GatewayInterface
     */
    protected function createGatewayMock(): GatewayInterface
    {
        return $this->createMock(GatewayInterface::class);
    }
}