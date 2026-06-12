# Intentional failure snippets

These snippets are documentation only and are not scanned by the pipeline.

## Test failure

Change a correct assertion to an incorrect expected value:

```php
self::assertSame(151.0, $resultPrice);
```

## Pint failure

Remove required spaces:

```php
if($price < 0.0){
```

## Larastan failure

Call a method that does not exist:

```php
$discountCalculator->unknownMethod();
```
