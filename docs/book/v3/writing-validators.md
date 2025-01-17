# Writing Validators

laminas-validator supplies a set of commonly needed validators, but many
applications have needs for custom validators. The component allows this via
implementations of `Laminas\Validator\ValidatorInterface`.

`Laminas\Validator\ValidatorInterface` defines two methods: `isValid()` and
`getMessages()`. An object that implements the interface may be added to a
validator chain using `Laminas\Validator\ValidatorChain::addValidator()`. Such
objects may also be used with
[laminas-inputfilter](https://docs.laminas.dev/laminas-inputfilter).

Validators will return a boolean value from `isValid()`, and report information
regarding **why** a value failed validation via `getMessages()`. The
availability of the reasons for validation failures may be valuable to an
application for various purposes, such as providing statistics for usability
analysis.

Basic validation failure message functionality is implemented in
`Laminas\Validator\AbstractValidator`, which you may extend for your custom
validators.  Extending class you would implement the `isValid()` method logic
and define the message variables and message templates that correspond to the
types of validation failures that can occur. If a value fails your validation
tests, then `isValid()` should return `false`. If the value passes your
validation tests, then `isValid()` should return `true`.

In general, the `isValid()` method should not throw any exceptions, except where
it is impossible to determine whether or not the input value is valid. A few
examples of reasonable cases for throwing an exception might be if a file cannot
be opened, an LDAP server could not be contacted, or a database connection is
unavailable, where such a thing may be required for validation success or
failure to be determined.

## Creating a Validation Class

The following example demonstrates how a custom validator might be written. In
this case, the validator tests that a value is a floating point value.

```php
namespace MyValid;

use Laminas\Validator\AbstractValidator;

final class Float extends AbstractValidator
{
    public const ERR_NOT_FLOAT = 'float';

    protected array $messageTemplates = [
        self::ERR_NOT_FLOAT => "'%value%' is not a floating point value",
    ];

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        if (! is_float($value)) {
            $this->error(self::ERR_NOT_FLOAT);
            return false;
        }

        return true;
    }
}
```

The class defines a template for its single validation failure message, which
includes the built-in magic parameter, `%value%`. The call to `setValue()`
prepares the object to insert the tested value into the failure message
automatically, should the value fail validation. The call to `error()` tracks a
reason for validation failure. Since this class only defines one failure
message, it is not necessary to provide `error()` with the name of the failure
message template.

## Writing a Validation Class Having Dependent Conditions

The following example demonstrates a more complex set of validation rules:

- The input must be numeric.
- The input must fall within a range of boundary values.

An input value would fail validation for exactly one of the following reasons:

- The input value is not numeric.
- The input value is less than the minimum allowed value.
- The input value is more than the maximum allowed value.

These validation failure reasons are then translated to definitions in the
class:

```php
namespace MyValid;

use Laminas\Validator\AbstractValidator;

/**
 * @psalm-type Options = array{
 *     minimum: positive-int,
 *     maximum: positive-int,
 * }
 */
final class NumericBetween extends AbstractValidator
{
    public const ERR_NOT_NUMERIC = 'msgNumeric';
    public const ERR_NOT_MINIMUM = 'msgMinimum';
    public const ERR_NOT_MAXIMUM = 'msgMaximum';

    protected readonly $minimum;
    protected readonly $maximum;

    protected array $messageVariables = [
        'min' => 'minimum',
        'max' => 'maximum',
    ];

    protected array $messageTemplates = [
        self::ERR_NOT_NUMERIC => "'%value%' is not numeric",
        self::ERR_NOT_MINIMUM => "'%value%' must be at least '%min%'",
        self::ERR_NOT_MAXIMUM => "'%value%' must be no more than '%max%'",
    ];
    
    /** @param Options $options */
    public function __construct(array $options) {
        $this->minimum = $options['minimum'];
        $this->maximum = $options['maximum'];
        
        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        if (! is_numeric($value)) {
            $this->error(self::ERR_NOT_NUMERIC);
            return false;
        }

        if ($value < $this->minimum) {
            $this->error(self::ERR_NOT_MINIMUM);
            return false;
        }

        if ($value > $this->maximum) {
            $this->error(self::ERR_NOT_MAXIMUM);
            return false;
        }

        return true;
    }
}
```

The protected properties `$minimum` and `$maximum` have been established to provide
the minimum and maximum boundaries, respectively, for a value to successfully
validate. The class also defines two message variables that correspond to the
public properties and allow `min` and `max` to be used in message templates as
magic parameters, just as with `value`.

Note that if any one of the validation checks in `isValid()` fails, an
appropriate failure message is prepared, and the method immediately returns
`false`. These validation rules are therefore sequentially dependent; that is,
if one test should fail, there is no need to test any subsequent validation
rules. This need not be the case, however. The following example illustrates how
to write a class having independent validation rules, where the validation
object may return multiple reasons why a particular validation attempt failed.

## Validation with Independent Conditions, Multiple Reasons for Failure

Consider writing a validation class for password strength enforcement - when a
user is required to choose a password that meets certain criteria for helping
secure user accounts. Let us assume that the password security criteria enforce
that the password:

- is at least 8 characters in length,
- contains at least one uppercase letter,
- contains at least one lowercase letter,
- and contains at least one digit character.

The following class implements these validation criteria:

```php
namespace MyValid;

use Laminas\Validator\AbstractValidator;

final class PasswordStrength extends AbstractValidator
{
    public const ERR_LENGTH = 'length';
    public const ERR_UPPER  = 'upper';
    public const ERR_LOWER  = 'lower';
    public const ERR_DIGIT  = 'digit';

    protected array $messageTemplates = [
        self::ERR_LENGTH => "'%value%' must be at least 8 characters in length",
        self::ERR_UPPER  => "'%value%' must contain at least one uppercase letter",
        self::ERR_LOWER  => "'%value%' must contain at least one lowercase letter",
        self::ERR_DIGIT  => "'%value%' must contain at least one digit character",
    ];

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        $isValid = true;

        if (strlen($value) < 8) {
            $this->error(self::ERR_LENGTH);
            $isValid = false;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $this->error(self::ERR_UPPER);
            $isValid = false;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $this->error(self::ERR_LOWER);
            $isValid = false;
        }

        if (! preg_match('/\d/', $value)) {
            $this->error(self::ERR_DIGIT);
            $isValid = false;
        }

        return $isValid;
    }
}
```

Note that the four criteria tests in `isValid()` do not immediately return
`false`. This allows the validation class to provide **all** the reasons that
the input password failed to meet the validation requirements. If, for example,
a user were to input the string `#$%` as a password, `isValid()` would cause
all four validation failure messages to be returned by a subsequent call to
`getMessages()`.

## Access to the Wider Validation Context

Typically, `laminas-validator` is used via `laminas-inputfilter` which is often, in turn, used via `laminas-form`.
When validators are used in these contexts, validators are provided with a second argument to the `isValid()` method - an array that represents the entire payload _(Typically `$_POST`)_ in an unfiltered and un-validated state.

Your custom validator can use this context to perform conditional validation by amending the signature of your `isValid` method to:

```php
public function isValid(mixed $value, ?array $context = null): bool
{
    // ... validation logic
}
```

## Best Practices When Inheriting From `AbstractValidator`

### Constructor Signature

Define your constructor to accept a normal associative array of options with a signature such as:

```php
public function __construct(array $options) { /** ... **/}
```

Additionally, call `parent::__construct($options)` within the constructor.

The reason for this is to ensure that when users provide the `messages` option, the array of error messages override the defaults you have defined in the class.

`AbstractValidator` also accepts:

- The `translator` option which can be a specific implementation of `Laminas\Translator\TranslatorInterface`
- The `translatorEnabled` option which is a boolean indicating whether translation should be enabled or not
- The `translatorTextDomain` option - A string defining the text domain the translator should use
- The `valueObscured` option - A boolean that indicates whether the validated value should be replaced with '****' when interpolated into error messages

The additional benefit of defining your constructor to accept an array of options is improved compatibility with the `ValidatorPluginManager`.
The plugin manager always creates a new instance, providing options to the constructor, meaning fewer specialised factories to write.

When your validator has runtime dependencies on services, consider allowing an options array in the constructor so that the `AbstractValidator` options can be provided if required:

```php
namespace MyValid;

use Laminas\Validator\AbstractValidator;
use Psr\Container\ContainerInterface;

final class FlightNumber extends AbstractValidator {
    
    public const ERR_INVALID_FLIGHT_NUMBER = 'invalidFlightNumber';
    
    public function __construct(private readonly FlightNumberValidationService $service, array $options = []) {
        parent::__construct($options);
    }
    
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::ERR_INVALID_FLIGHT_NUMBER);
            
            return false;
        }
        
        if (! $this->service->isValidFlightNumber($value)) {
            $this->error(self::ERR_INVALID_FLIGHT_NUMBER);
            
            return false;
        }
        
        return true;
    }
}

final class FlightNumberFactory
{
    public function __invoke(ContainerInterface $container, string $name, array $options = []): FlightNumber
    {
        return new FlightNumber(
            $container->get(FlightNumberValidationService::class),
            $options,
        );
    }
}
```

### Set Option Values Once and Make Them `readonly`

By resolving validator options in the constructor to `private readonly` properties, and removing methods such as `getMyOption` and `setMyOption` you are forced to test how your validator behaviour varies based on its options, and, you can be sure that options simply cannot change once the validator has been constructed.
