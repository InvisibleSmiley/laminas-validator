# StringLength Validator

This validator allows you to validate if a given string is between a defined
length.

<!-- markdownlint-disable-next-line MD001 -->
> ### Supports only string validation
>
> `Laminas\Validator\StringLength` supports only the validation of strings.
> Integers, floats, dates or objects can not be validated with this validator.

## Supported Options

The following options are supported for `Laminas\Validator\StringLength`:

- `encoding`: Sets the expected encoding of the input string. _(Default `'utf-8'`)_
- `min`: Sets the minimum allowed length for a string. _(Default `0`)_
- `max`: Sets the maximum allowed length for a string. _(Default `null`)_

## Default Behaviour

By default, this validator checks if a value is between `min` and `max` using a
default `min` value of `0` and default `max` value of `NULL` (meaning unlimited).

As such, without any options, the validator only checks that the input is a
string.

## Limiting the Maximum String Length

To limit the maximum allowed length of a string you need to set the `max`
property. It accepts an integer value as input.

```php
$validator = new Laminas\Validator\StringLength(['max' => 6]);

$validator->isValid("Test"); // returns true
$validator->isValid("Testing"); // returns false
```

## Limiting the Minimum String Length

To limit the minimal required string length, set the `min`
property using an integer value:

```php
$validator = new Laminas\Validator\StringLength(['min' => 5]);

$validator->isValid("Test"); // returns false
$validator->isValid("Testing"); // returns true
```

## Limiting Both Minimum and Maximum String Length

Sometimes you will need to set both a minimum and a maximum string length;
as an example, in a username input, you may want to limit the name to a maximum
of 30 characters, but require at least three characters:

```php
$validator = new Laminas\Validator\StringLength(['min' => 3, 'max' => 30]);

$validator->isValid("."); // returns false
$validator->isValid("Test"); // returns true
$validator->isValid("Testing"); // returns true
```

## Limiting to a Strict Length

If you need a strict length, then set the `min` and `max` properties to the same
value:

```php
$validator = new Laminas\Validator\StringLength(['min' => 4, 'max' => 4]);

$validator->isValid('Tes'); // returns false
$validator->isValid('Test'); // returns true
$validator->isValid('Testi'); // returns false
```

> ### Setting a Maximum Lower than the Minimum
>
> When you try to set a lower maximum value than the specified minimum value, or
> a higher minimum value as the actual maximum value, the validator will raise
> an exception.

## Encoding of Values

Strings are always using an encoding. Even when you don't set the encoding
explicitly, PHP uses one. When your application is using a different encoding
than PHP itself, you should set an encoding manually.

You can set an encoding at instantiation with the `encoding` option. Assuming that your installation and application uses UTF-8 encoding, you will see the below behaviour.

```php
$validator = new Laminas\Validator\StringLength(['min' => 6]);
$validator->isValid("Ärger"); // returns true

$validator = new Laminas\Validator\StringLength(['min' => 6, 'encoding' => 'ascii']);
$validator->isValid("Ärger"); // returns false
```

When your installation and your application are using different encodings, then
you should always set an encoding manually.

NOTE: **Default Encoding**
By default, the expected input encoding is `UTF-8`

## Validation Messages

Using the setMessage() method you can set another message to be returned in case of the specified failure.

```php
$validator = new Laminas\Validator\StringLength(['min' => 3, 'max' => 30]);
$validator->setMessage('Your string is too long. You typed '%length%' chars.', Laminas\Validator\StringLength::TOO_LONG);
```
