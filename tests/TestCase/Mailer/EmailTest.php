<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\TestCase\Mailer;

use BEdita\Core\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Core\Mailer\Email
 */
class EmailTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        TransportFactory::setConfig('test', [
            'className' => 'Debug',
        ]);

        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();

        TransportFactory::drop('test');
    }

    /**
     * Data provider for `testSendRaw` test case.
     *
     * @return array
     */
    public function sendRawProvider()
    {
        return [
            'simple' => [
                [
                    'headers' => [
                        'From: Gustavo <gustavo.supporto@example.org>',
                        'To: Evermannella <evermannella@example.org>',
                        'Subject: Re: Have you installed the latest version of Synapse?',
                    ],
                    'message' => [
                        'Not yet. Please write a story on our Scrum board.',
                        '',
                        'Regards,',
                        'Evermannella @ ChiaLab srl',
                    ],
                ],
                [
                    'from' => ['gustavo.supporto@example.org' => 'Gustavo'],
                    'to' => ['evermannella@example.org' => 'Evermannella'],
                    'subject' => 'Re: Have you installed the latest version of Synapse?',
                    'message' => [
                        'Not yet. Please write a story on our Scrum board.',
                        '',
                        'Regards,',
                        'Evermannella @ ChiaLab srl',
                    ],
                ],
            ],
            'empty from' => [
                new \LogicException('From is not specified.'),
                [],
            ],
            'empty to' => [
                new \LogicException('You need specify one destination on to, cc or bcc.'),
                [
                    'from' => ['gustavo.supporto@example.org' => 'Gustavo'],
                ],
            ],
            'wrong transport' => [
                new \LogicException('Cannot send email, transport was not defined. Did you call transport() or define a transport in the set profile?'),
                [
                    'from' => ['gustavo.supporto@example.org' => 'Gustavo'],
                    'to' => ['evermannella@example.org' => 'Evermannella'],
                ],
                false,
            ],
        ];
    }

    /**
     * Test `sendRaw()` method
     *
     * @param mixed $expected Expected result.
     * @param array $config Email configuration.
     * @param bool $setTransport Should email transport be set?
     * @return void
     * @covers ::sendRaw()
     * @dataProvider sendRawProvider()
     */
    public function testRun($expected, array $config, $setTransport = true)
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionCode($expected->getCode());
            $this->expectExceptionMessage($expected->getMessage());
        }

        $email = (new Email())
            ->reset()
            ->createFromArray($config);
        if ($setTransport) {
            $email = $email->setTransport('test');
        }

        $result = $email->sendRaw();
        array_walk($result, function (&$val) {
            $val = explode("\r\n", $val);
        });

        static::assertArrayHasKey('headers', $result);
        foreach ($expected['headers'] as $header) {
            static::assertContains($header, $result['headers']);
        }
        static::assertArrayHasKey('message', $result);
        static::assertSame($expected['message'], $result['message']);
    }

    /**
     * Test getter for boundary.
     *
     * @return void
     * @covers ::getBoundary()
     */
    // public function testGetBoundary()
    // {
    //     $email = new Email();
    //     $email->setTo('evermannella@example.org');
    //     $email->setBodyText('This is the message');
    //     $email->addAttachments([
    //         'test.txt' => [
    //             'data' => 'Some text attachment',
    //             'mimetype' => 'text/plain',
    //         ],
    //     ]);
    //     $message = $email->getMessage()->getBody();
    //     $boundary = Email::getBoundary($email);

    //     static::assertNotNull($boundary);
    //     static::assertAttributeSame($boundary, '_boundary', $email);
    // }
}
