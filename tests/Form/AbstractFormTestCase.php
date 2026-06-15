<?php

namespace App\Tests\Form;

use App\Tests\Integration\AbstractDoctrineTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class AbstractFormTestCase extends AbstractDoctrineTestCase
{
    protected FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/');
        $request->setSession($session);
        static::getContainer()->get(RequestStack::class)->push($request);
    }
}
