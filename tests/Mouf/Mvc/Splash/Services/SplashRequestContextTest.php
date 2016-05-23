<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Exception\SplashMissingParameterException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;

class SplashRequestContextTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $file = new UploadedFile('foo', 12, UPLOAD_ERR_OK);
        $request = new ServerRequest();
        $request = $request->withQueryParams(['id' => 42])
                ->withParsedBody(['post' => 'foo'])
                ->withUploadedFiles(['file' => $file]);
        $context = new SplashRequestContext($request);
        $context->addUrlParameter('url', 'bar');

        $this->assertTrue($context->hasParameter('id'));
        $this->assertTrue($context->hasParameter('post'));
        $this->assertTrue($context->hasParameter('file'));
        $this->assertTrue($context->hasParameter('url'));
        $this->assertFalse($context->hasParameter('no_exist'));

        $this->assertEquals(42, $context->getParameter('id', true));
        $this->assertEquals('foo', $context->getParameter('post', true));
        $this->assertEquals($file, $context->getParameter('file', true));
        $this->assertEquals('bar', $context->getParameter('url', true));
        $this->assertEquals('default', $context->getParameter('no_exist', false, 'default'));

        $this->expectException(SplashMissingParameterException::class);
        $this->assertEquals('bar', $context->getParameter('no_exist', true));
    }

    public function testUrlParameters()
    {
        $request = new ServerRequest();
        $context = new SplashRequestContext($request);
        $context->addUrlParameter('id', 24);
        $this->assertEquals(24, $context->getUrlParameters()['id']);
        $context->setUrlParameters([
            'id' => 42,
        ]);
        $this->assertEquals(42, $context->getUrlParameters()['id']);
    }
}
