# Ip Validator

`Laminas\Validator\Ip` allows you to validate if a given value is an IP address. It
supports the IPv4, IPv6, and IPvFuture definitions.

## Supported Options

The following options are supported for `Laminas\Validator\Ip`:

- `allowipv4`: Defines if the validator allows IPv4 addresses. This option
  defaults to `true`.
- `allowipv6`: Defines if the validator allows IPv6 addresses. This option
  defaults to `true`.
- `allowipvfuture`: Defines if the validator allows IPvFuture addresses. This
  option defaults to `false`.
- `allowliteral`: Defines if the validator allows IPv6 or IPvFuture with URI
  literal style (the IP surrounded by brackets). This option defaults to `true`.

## Basic Usage

```php
$validator = new Laminas\Validator\Ip();

if ($validator->isValid($ip)) {
    // ip appears to be valid
} else {
    // ip is invalid; print the reasons
}
```

> ### Invalid IP Addresses
>
> Keep in mind that `Laminas\Validator\Ip` only validates IP addresses. Addresses
> like '`mydomain.com`' or '`192.168.50.1/index.html`' are not valid IP
> addresses. They are either hostnames or valid URLs but not IP addresses.

> ### IPv6/IPvFuture Validation
>
> `Laminas\Validator\Ip` validates IPv6/IPvFuture addresses using a regex. The
> reason is that the filters and methods from PHP itself don't follow the RFC.
> Many other available classes also don't follow it.

## Validate IPv4 or IPV6 Alone

Sometimes it's useful to validate only one of the supported formats; e.g., when
your network only supports IPv4. In this case it would be useless to allow IPv6
within this validator.

To limit `Laminas\Validator\Ip` to one protocol, you can set the options `allowipv4`
or `allowipv6` to `false`. You can do this either by giving the option to the
constructor or by using `setOptions()` afterwards.

```php
$validator = new Laminas\Validator\Ip(['allowipv6' => false]);

if ($validator->isValid($ip)) {
    // ip appears to be valid ipv4 address
} else {
    // ip is not an ipv4 address
}
```

> ### Default Behaviour
>
> The default behaviour which `Laminas\Validator\Ip` follows is to allow both
> standards.
