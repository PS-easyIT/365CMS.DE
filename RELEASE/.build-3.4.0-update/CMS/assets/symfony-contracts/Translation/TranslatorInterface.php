<?php
declare(strict_types=1);

namespace Symfony\Contracts\Translation;

interface TranslatorInterface extends LocaleAwareInterface
{
    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string;
}
