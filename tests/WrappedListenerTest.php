<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Events\Tests;

use ArgumentCountError;
use BiuradPHP\Events\LazyEventDispatcher;
use BiuradPHP\Events\TraceableEventDispatcher;
use BiuradPHP\Events\WrappedListener;
use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class WrappedListenerTest extends TestCase
{
    /**
     * @dataProvider provideListenersToDescribe
     */
    public function testListenerDescription($listener, $expected): void
    {
        $wrappedListener = new WrappedListener(
            $listener,
            null,
            $this->getMockBuilder(EventDispatcherInterface::class)->getMock()
        );

        $this->assertStringMatchesFormat($expected, $wrappedListener->getPretty());
    }

    public function testInvokeWithLazyDispatcherAndStoppedPropagation(): void
    {
        $wrappedListener = new WrappedListener(
            function (Event $event, $eventName, $dispatcher, stdClass $object): void {
                $event->stopPropagation();
            },
            'hello'
        );
        $wrappedListener(new Event(), 'hello', new TraceableEventDispatcher(new LazyEventDispatcher()));

        $this->assertTrue($wrappedListener->wasCalled());
        $this->assertTrue($wrappedListener->stoppedPropagation());
    }

    public function testExceptionOnInvoke(): void
    {
        $wrappedListener = new WrappedListener(
            function (Event $event, $eventName, $dispatcher, $hello): void {
            },
            null
        );

        $this->expectException(ArgumentCountError::class);
        $wrappedListener(new Event(), 'hello', new EventDispatcher());
    }

    /**
     * @return Generator
     */
    public function provideListenersToDescribe(): Generator
    {
        yield 'Test Pretty Callable String' => ['var_dump', 'var_dump'];

        yield 'Test Pretty Invalid String' => ['nothing', 'nothing'];

        yield 'Test Pretty Invoke Object' => [
            new Fixtures\FooListener(),
            'BiuradPHP\Events\Tests\Fixtures\FooListener::__invoke',
        ];

        yield 'Test Pretty Callable' => [
            [new Fixtures\FooListener(), 'listen'],
            'BiuradPHP\Events\Tests\Fixtures\FooListener::listen',
        ];

        yield 'Test Pretty Static Callable' => [
            [Fixtures\FooListener::class, 'listenStatic'],
            'BiuradPHP\Events\Tests\Fixtures\FooListener::listenStatic',
        ];

        yield 'Test Pretty Array' => [
            [Fixtures\FooListener::class, 'invalidMethod'],
            'BiuradPHP\Events\Tests\Fixtures\FooListener::invalidMethod',
        ];

        yield 'Test Pretty Closure Cast' => [
            Closure::fromCallable([new Fixtures\FooListener(), 'listen']),
            'BiuradPHP\Events\Tests\Fixtures\FooListener::listen',
        ];

        yield 'Test Pretty Closure Static Cast' => [
            Closure::fromCallable([Fixtures\FooListener::class, 'listenStatic']),
            'BiuradPHP\Events\Tests\Fixtures\FooListener::listenStatic',
        ];

        yield 'Test Pretty Closure' => [
            function (): string {
                return 'something';
            },
            'closure',
        ];

        yield 'Test Pretty Cast Closure' => [
            Closure::fromCallable(
                function (): void {
                }
            ),
            'closure',
        ];
    }
}
