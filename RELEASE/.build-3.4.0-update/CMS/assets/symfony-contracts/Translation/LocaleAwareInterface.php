<?php
declare(strict_types=1);

namespace Symfony\Contracts\Translation;

interface LocaleAwareInterface
{
    public function setLocale(string $locale): void;

    public function getLocale(): string;
}
