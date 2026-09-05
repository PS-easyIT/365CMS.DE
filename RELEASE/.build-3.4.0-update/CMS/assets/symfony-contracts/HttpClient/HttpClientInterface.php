<?php
declare(strict_types=1);

namespace Symfony\Contracts\HttpClient;

interface HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): ResponseInterface;

    public function stream(iterable $responses, ?float $timeout = null): iterable;

    public function withOptions(array $options): static;
}
