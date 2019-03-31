<?php declare(strict_types=1);

namespace Parable\Rights\Tests;

use Parable\Mail\Exception;
use Parable\Mail\Mailer;
use Parable\Mail\Sender\FakeSender;
use Parable\Mail\Sender\NullSender;
use Parable\Mail\Sender\SenderInterface;

class MailerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mailer
     */
    protected $mailer;

    protected $requiredHeaders = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=UTF-8",
    ];

    public function setUp()
    {
        parent::setUp();

        $this->mailer = new Mailer(new FakeSender());
    }

    public function testSenderIsSetAppropriately()
    {
        self::assertInstanceOf(SenderInterface::class, $this->mailer->getSender());
        self::assertInstanceOf(FakeSender::class, $this->mailer->getSender());
    }

    public function testSetFromThrowsOnInvalidMail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email provided is invalid: nope');

        $this->mailer->setFrom('nope');
    }

    public function testSetFromWorksWithBothJustEmailAndBothEmailAndName()
    {
        self::assertNull($this->mailer->getAddressesStringForType('from'));

        $this->mailer->setFrom('me@here.dv');

        self::assertSame('me@here.dv', $this->mailer->getAddressesStringForType('from'));

        $this->mailer->setFrom('me@here.dv', 'Me!');

        self::assertSame('Me! <me@here.dv>', $this->mailer->getAddressesStringForType('from'));
    }

    public function testAddToThrowsOnInvalidMail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email provided is invalid: nope');

        $this->mailer->addTo('nope');
    }

    public function testAddToAddsAppropriately()
    {
        $this->mailer->addTo('me@here.dv', 'Me!');

        self::assertSame('Me! <me@here.dv>', $this->mailer->getAddressesStringForType('to'));

        $this->mailer->addTo('you@there.dv', 'You!');

        self::assertSame('Me! <me@here.dv>, You! <you@there.dv>', $this->mailer->getAddressesStringForType('to'));
    }

    public function testAddCcAddsAppropriately()
    {
        $this->mailer->addCc('me@here.dv', 'Me!');

        self::assertSame('Me! <me@here.dv>', $this->mailer->getAddressesStringForType('cc'));

        $this->mailer->addCc('you@there.dv', 'You!');

        self::assertSame('Me! <me@here.dv>, You! <you@there.dv>', $this->mailer->getAddressesStringForType('cc'));
    }

    public function testAddBccAddsAppropriately()
    {
        $this->mailer->addBcc('me@here.dv', 'Me!');

        self::assertSame('Me! <me@here.dv>', $this->mailer->getAddressesStringForType('bcc'));

        $this->mailer->addBcc('you@there.dv', 'You!');

        self::assertSame('Me! <me@here.dv>, You! <you@there.dv>', $this->mailer->getAddressesStringForType('bcc'));
    }

    public function testGetAddressesReturnsAll()
    {
        $this->mailer->setFrom('from@here.dv');
        $this->mailer->addTo('to@here.dv');
        $this->mailer->addCc('cc@here.dv');
        $this->mailer->addBcc('bcc@here.dv');

        self::assertSame(
            [
                'from' => [
                    ['email' => 'from@here.dv', 'name' => null],
                ],
                'to' => [
                    ['email' => 'to@here.dv', 'name' => null],
                ],
                'cc' => [
                    ['email' => 'cc@here.dv', 'name' => null],
                ],
                'bcc' => [
                    ['email' => 'bcc@here.dv', 'name' => null],
                ],
            ],
            $this->mailer->getAddresses()
        );
    }

    /**
     * @dataProvider dpValidAddressStrings
     */
    public function testGetAddressesStringForTypeWorks(string $type, string $expected)
    {
        $this->mailer->setFrom('from@here.dv');

        $this->mailer->addTo('to1@here.dv');
        $this->mailer->addTo('to2@here.dv');

        $this->mailer->addCc('cc1@here.dv');
        $this->mailer->addCc('cc2@here.dv');

        $this->mailer->addBcc('bcc1@here.dv');
        $this->mailer->addBcc('bcc2@here.dv');

        self::assertSame(
            $expected,
            $this->mailer->getAddressesStringForType($type)
        );
    }

    public function dpValidAddressStrings(): array
    {
        return [
            ['from', 'from@here.dv'],
            ['to', 'to1@here.dv, to2@here.dv'],
            ['cc', 'cc1@here.dv, cc2@here.dv'],
            ['bcc', 'bcc1@here.dv, bcc2@here.dv'],
        ];
    }

    public function testGetAddressesStringForTypeReturnsNullIfNone()
    {
        self::assertNull($this->mailer->getAddressesStringForType('from'));
        self::assertNull($this->mailer->getAddressesStringForType('to'));
        self::assertNull($this->mailer->getAddressesStringForType('cc'));
        self::assertNull($this->mailer->getAddressesStringForType('bcc'));
    }

    public function testGetAddressesStringForTypeThrowsOnInvalidType()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only to, from, cc, bcc addresses are allowed.');

        $this->mailer->getAddressesStringForType('yolo');
    }

    /**
     * @dataProvider dpInvalidData
     */
    public function testMailerThrowsIfSendingWithoutRequiredInformation(array $data, string $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        if ($data['to'] !== null) {
            $this->mailer->addTo($data['to']);
        }
        if ($data['from'] !== null) {
            $this->mailer->setFrom($data['from']);
        }
        if ($data['subject'] !== null) {
            $this->mailer->setSubject($data['subject']);
        }
        if ($data['body'] !== null) {
            $this->mailer->setBody($data['body']);
        }

        $this->mailer->send();
    }

    public function dpInvalidData(): array
    {
        return [
            [
                [
                    'to' => null,
                    'from' => 'me@here.dv',
                    'subject' => 'subject',
                    'body' => 'body',
                ],
                'No to addresses provided.',
            ],
            [
                [
                    'to' => 'you@there.dv',
                    'from' => null,
                    'subject' => 'subject',
                    'body' => 'body',
                ],
                'No from address provided.',
            ],
            [
                [
                    'to' => 'you@there.dv',
                    'from' => 'me@here.dv',
                    'subject' => null,
                    'body' => 'body',
                ],
                'No subject provided.',
            ],
            [
                [
                    'to' => 'you@there.dv',
                    'from' => 'me@here.dv',
                    'subject' => 'subject',
                    'body' => null,
                ],
                'No body provided.',
            ],
        ];
    }

    public function testSetAndGetBody()
    {
        self::assertNull($this->mailer->getBody());

        $this->mailer->setBody('BODY');

        self::assertSame('BODY', $this->mailer->getBody());
    }

    public function testSetAndGetSubject()
    {
        self::assertNull($this->mailer->getSubject());

        $this->mailer->setSubject('SUBJECT');

        self::assertSame('SUBJECT', $this->mailer->getSubject());
    }

    public function testRequiredHeadersAreSet()
    {
        self::assertSame(
            $this->requiredHeaders,
            $this->mailer->getRequiredHeaders()
        );
    }

    public function testAddRequiredHeaderWorks()
    {
        $this->mailer->addRequiredHeader('Testing: now');

        self::assertSame(
            array_merge($this->requiredHeaders, ['Testing: now']),
            $this->mailer->getRequiredHeaders()
        );
    }

    public function testAddCustomHeaderWorks()
    {
        self::assertSame(
            [],
            $this->mailer->getCustomHeaders()
        );

        $this->mailer->addCustomHeader('Testing: now');

        self::assertSame(
            ['Testing: now'],
            $this->mailer->getCustomHeaders()
        );
    }

    public function testGetAllHeadersReturnsCustomAndRequiredHeaders()
    {
        self::assertSame(
            $this->requiredHeaders,
            $this->mailer->getAllHeaders()
        );

        $this->mailer->addCustomHeader('Testing: now');

        self::assertSame(
            array_merge($this->requiredHeaders, ['Testing: now']),
            $this->mailer->getAllHeaders()
        );
    }

    public function testSendUsesSender()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        self::assertTrue($mailer->send());

        /** @var FakeSender $sender */
        $sender = $mailer->getSender();

        $expectedHeaders = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'test: yay',
            'Cc: him@there.dv',
            'Bcc: her@there.dv',
            'From: me@here.dv',
        ]);

        self::assertCount(1, $sender->getSentMails());
        self::assertSame(
            ['you@there.dv', 'subject', 'body', $expectedHeaders],
            $sender->getSentMails()[0]
        );
    }

    public function testResetMailDataClearsNoMoreThanThat()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        $mailer->resetMailData();

        self::assertNull($mailer->getSubject());
        self::assertNull($mailer->getBody());
        self::assertEmpty($mailer->getCustomHeaders());

        self::assertNotNull($mailer->getAddressesStringForType('from'));
        self::assertNotNull($mailer->getAddressesStringForType('to'));
        self::assertNotNull($mailer->getAddressesStringForType('cc'));
        self::assertNotNull($mailer->getAddressesStringForType('bcc'));
    }

    public function testResetFromClearsOnlyFrom()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        $mailer->resetFrom();

        self::assertNull($mailer->getAddressesStringForType('from'));

        self::assertNotNull($mailer->getAddressesStringForType('to'));
        self::assertNotNull($mailer->getAddressesStringForType('cc'));
        self::assertNotNull($mailer->getAddressesStringForType('bcc'));
        self::assertNotNull($mailer->getSubject());
        self::assertNotNull($mailer->getBody());
        self::assertNotEmpty($mailer->getCustomHeaders());
    }

    public function testResetToCcBccClearsOnlyThose()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        $mailer->resetToCcBcc();

        self::assertNull($mailer->getAddressesStringForType('to'));
        self::assertNull($mailer->getAddressesStringForType('cc'));
        self::assertNull($mailer->getAddressesStringForType('bcc'));

        self::assertNotNull($mailer->getAddressesStringForType('from'));
        self::assertNotNull($mailer->getSubject());
        self::assertNotNull($mailer->getBody());
        self::assertNotEmpty($mailer->getCustomHeaders());
    }

    public function testResetClearsEverythingButFrom()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        $mailer->reset();

        self::assertNull($mailer->getAddressesStringForType('to'));
        self::assertNull($mailer->getAddressesStringForType('cc'));
        self::assertNull($mailer->getAddressesStringForType('bcc'));
        self::assertNull($mailer->getSubject());
        self::assertNull($mailer->getBody());
        self::assertEmpty($mailer->getCustomHeaders());

        self::assertNotNull($mailer->getAddressesStringForType('from'));
    }

    public function testResetAllClearsEverything()
    {
        $mailer = $this->createFullyLoadedMailerWithFakeSender();

        $mailer->resetAll();

        self::assertNull($mailer->getAddressesStringForType('from'));
        self::assertNull($mailer->getAddressesStringForType('to'));
        self::assertNull($mailer->getAddressesStringForType('cc'));
        self::assertNull($mailer->getAddressesStringForType('bcc'));
        self::assertNull($mailer->getSubject());
        self::assertNull($mailer->getBody());
        self::assertEmpty($mailer->getCustomHeaders());
    }

    protected function createFullyLoadedMailerWithFakeSender(): Mailer
    {
        $mailer = new Mailer(new FakeSender());

        $mailer->setFrom('me@here.dv');

        $mailer->addTo('you@there.dv');
        $mailer->addCc('him@there.dv');
        $mailer->addBcc('her@there.dv');
        $mailer->setSubject('subject');
        $mailer->setBody('body');
        $mailer->addCustomHeader('test: yay');

        return $mailer;
    }
}
