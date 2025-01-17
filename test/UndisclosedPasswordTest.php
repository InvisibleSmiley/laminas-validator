<?php

declare(strict_types=1);

namespace LaminasTest\Validator;

use Exception;
use Laminas\Validator\UndisclosedPassword;
use LaminasTest\Validator\TestAsset\HttpClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use stdClass;

use function random_int;
use function sha1;
use function sprintf;
use function strtoupper;
use function substr;

final class UndisclosedPasswordTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $httpRequest;
    private ResponseInterface&MockObject $httpResponse;
    private UndisclosedPassword $validator;
    private StreamInterface&MockObject $stream;

    /** {@inheritDoc} */
    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient   = $this->createMock(ClientInterface::class);
        $this->httpRequest  = $this->createMock(RequestFactoryInterface::class);
        $this->httpResponse = $this->createMock(ResponseInterface::class);
        $this->validator    = new UndisclosedPassword($this->httpClient, $this->httpRequest);
        $this->stream       = $this->createMock(StreamInterface::class);
    }

    /**
     * @param non-empty-string $constant
     * @param class-string|object $classOrInstance
     * @return mixed
     */
    public function getConstant(string $constant, string|object $classOrInstance)
    {
        return (new ReflectionClass($classOrInstance))
            ->getConstant($constant);
    }

    /**
     * Data provider returning good, strong and unseen
     * passwords to be used in the validator.
     *
     * @psalm-return array<array{string}>
     */
    public static function goodPasswordProvider(): array
    {
        return [
            ['ABi$B47es.Pfg3n9PjPi'],
            ['potence tipple would frisk shoofly'],
        ];
    }

    /**
     * Data provider for most common used passwords
     *
     * @see https://en.wikipedia.org/wiki/List_of_the_most_common_passwords
     *
     * @psalm-return array<array{string}>
     */
    public static function seenPasswordProvider(): array
    {
        return [
            ['123456'],
            ['password'],
            ['123456789'],
            ['12345678'],
            ['12345'],
        ];
    }

    /**
     * Testing that we reject invalid password types
     *
     * @todo Can be replaced by a \TypeError being thrown in PHP 7.0 or up
     */
    public function testValidationFailsForInvalidInput(): void
    {
        self::assertFalse($this->validator->isValid(true));
        self::assertFalse($this->validator->isValid(new stdClass()));
        self::assertFalse($this->validator->isValid(['foo']));
    }

    /**
     * Test that a given password was not found in the HIBP
     * API service.
     */
    #[DataProvider('goodPasswordProvider')]
    public function testStrongUnseenPasswordsPassValidation(string $password): void
    {
        $this->httpResponse
            ->expects(self::once())
            ->method('getBody')
            ->willReturnCallback(function (): StreamInterface {
                $hash = sha1('laminas-validator');

                $constant = $this->getConstant(
                    'HIBP_K_ANONYMITY_HASH_RANGE_LENGTH',
                    UndisclosedPassword::class
                );
                self::assertIsInt($constant);

                $this->stream->method('__toString')
                    ->willReturn(sprintf(
                        '%s:%d',
                        strtoupper(substr($hash, $constant)),
                        random_int(0, 100000)
                    ));

                return $this->stream;
            });

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturn($this->httpResponse);

        self::assertTrue($this->validator->isValid($password));
    }

    /**
     * Test that a given password was already seen in the HIBP
     * AP service.
     */
    #[DataProvider('seenPasswordProvider')]
    public function testBreachedPasswordsDoNotPassValidation(string $password): void
    {
        $this->httpResponse
            ->expects(self::once())
            ->method('getBody')
            ->willReturnCallback(function () use ($password): StreamInterface {
                $hash = sha1($password);

                $constant = $this->getConstant(
                    'HIBP_K_ANONYMITY_HASH_RANGE_LENGTH',
                    UndisclosedPassword::class
                );

                self::assertIsInt($constant);

                $this->stream->method('__toString')
                    ->willReturn(sprintf(
                        '%s:%d',
                        strtoupper(substr($hash, $constant)),
                        random_int(0, 100000)
                    ));

                return $this->stream;
            });

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturn($this->httpResponse);

        self::assertFalse($this->validator->isValid($password));
    }

    /**
     * Testing we are setting error messages when a password was found
     * in the breach database.
     */
    #[DataProvider('seenPasswordProvider')]
    #[Depends('testBreachedPasswordsDoNotPassValidation')]
    public function testBreachedPasswordReturnErrorMessages(string $password): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willThrowException(new Exception('foo'));

        $this->expectException(Exception::class);

        $this->validator->isValid($password);

        self::fail('Expected exception was not thrown');
    }

    /**
     * Testing that we capture any failures when trying to connect with
     * the HIBP web service.
     */
    #[DataProvider('seenPasswordProvider')]
    #[Depends('testBreachedPasswordsDoNotPassValidation')]
    public function testValidationDegradesGracefullyWhenNoConnectionCanBeMade(string $password): void
    {
        $clientException = $this->createMock(HttpClientException::class);

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($clientException);

        $this->expectException(ClientExceptionInterface::class);

        $this->validator->isValid($password);

        self::fail('Expected ClientException was not thrown');
    }
}
