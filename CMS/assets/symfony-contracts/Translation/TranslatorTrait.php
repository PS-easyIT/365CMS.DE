<?php
declare(strict_types=1);

namespace Symfony\Contracts\Translation;

trait TranslatorTrait
{
    protected string $locale = 'en';

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        return $parameters !== [] ? strtr($id, $parameters) : $id;
    }
}
