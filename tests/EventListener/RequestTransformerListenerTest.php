<?php

namespace SymfonyBundles\JsonRequestBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use SymfonyBundles\JsonRequestBundle\EventListener\RequestTransformerListener;

class RequestTransformerListenerTest extends TestCase
{
    private static string $getContentTypeFormat = 'getContentTypeFormat';

    private RequestTransformerListener $listener;

    public static function setUpBeforeClass(): void
    {
        if (!method_exists(Request::class, 'getContentTypeFormat')) {
            self::$getContentTypeFormat = 'getContentType';
        }
    }

    protected function setUp(): void
    {
        $this->listener = new RequestTransformerListener(['json']);
    }

    public function testOnKernelRequestWithInvalidJson(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"test": "val}');
        $request->method(self::$getContentTypeFormat)->willReturn("json");

        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent->method('getRequest')->willReturn($request);

        $requestEvent->expects($this->once())->method('setResponse')->willReturnCallback(function ($resp) {
            $this->assertInstanceOf(JsonResponse::class, $resp);
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $resp->getStatusCode());
            $this->assertIsString($resp->getContent());
            $this->assertJson($resp->getContent());
        });

        $this->listener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestWithValidJson(): void
    {
        $inputBag = new InputBag();

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"test": "val"}');
        $request->method(self::$getContentTypeFormat)->willReturn('json');
        $request->request = $inputBag;

        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent->method('getRequest')->willReturn($request);

        $requestEvent->expects($this->never())->method('setResponse');

        $this->listener->onKernelRequest($requestEvent);

        $this->assertEquals(['test' => 'val'], $inputBag->all());
    }
}
