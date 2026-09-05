<?php
declare(strict_types=1);

namespace Symfony\Contracts\HttpClient;

interface ResponseInterface
{
    public function getStatusCode(): int;

    public function getHeaders(bool $throw = true): array;

    public function getContent(bool $throw = true): string;

    public function toArray(bool $throw = true): array;

    public function cancel(): void;

    public function getInfo(?string $type = null): mixed;
}
