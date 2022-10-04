<?php

namespace App\ValueObject;

use InvalidArgumentException;
use JsonSerializable;

abstract class NonEmptyStringLiteral implements JsonSerializable
{
    private string $string;

    protected function __construct(string $string)
    {
        $this->guardNonEmpty($string);
        $this->string = $string;
    }

    private function guardNonEmpty(string $string): void
    {
        if (empty($string)) {
            throw new InvalidArgumentException(get_called_class().' can not be empty');
        }
    }

    public static function fromString(string $string)
    {
        return new static($string);
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
