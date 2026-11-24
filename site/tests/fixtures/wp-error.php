<?php

declare(strict_types=1);

namespace EspacioSutil\Tests\Fixtures;

/** Error returned by the in-memory WordPress boundary, without a WP bootstrap. */
final class FixtureError
{
    public function __construct(private string $code = '', private string $message = '', private array $data = []) {}

    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data(): array { return $this->data; }
}

// Only the isolated PHP process needs the WordPress class name.
class_alias(FixtureError::class, 'WP_Error');
