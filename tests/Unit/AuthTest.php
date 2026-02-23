<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\Auth;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für Auth::validatePasswordPolicy()
 *
 * Testet, ob die Passwort-Komplexitätsregeln korrekt angewandt werden:
 * - mindestens 12 Zeichen
 * - min. 1 Großbuchstabe
 * - min. 1 Kleinbuchstabe
 * - min. 1 Ziffer
 * - min. 1 Sonderzeichen
 */
class AuthTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Gültige Passwörter
    // ──────────────────────────────────────────────────────────────────────

    public function testValidPasswordReturnsTrue(): void
    {
        $result = Auth::validatePasswordPolicy('Secure@Passw0rd');
        $this->assertTrue($result, 'Ein valid password sollte true zurückgeben');
    }

    public function testValidPasswordExact12CharsReturnsTrue(): void
    {
        // Genau 12 Zeichen, alle Regeln erfüllt
        $result = Auth::validatePasswordPolicy('Aa1!Aa1!Aa1!');
        $this->assertTrue($result);
    }

    public function testValidPasswordWithUmlautSpecialChar(): void
    {
        // Sonderzeichen € gilt als nicht-alphanumerisch
        $result = Auth::validatePasswordPolicy('Passw0rd€Test');
        $this->assertTrue($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Zu kurz
    // ──────────────────────────────────────────────────────────────────────

    public function testPasswordTooShortReturnsErrorString(): void
    {
        $result = Auth::validatePasswordPolicy('Aa1!Short'); // 9 Zeichen
        $this->assertIsString($result);
        $this->assertNotTrue($result);
    }

    public function testPasswordExactly11CharsReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('Aa1!Aa1!Aa1'); // 11 Zeichen
        $this->assertIsString($result);
    }

    public function testEmptyPasswordReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('');
        $this->assertIsString($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Fehlende Zeichenklassen
    // ──────────────────────────────────────────────────────────────────────

    public function testPasswordNoUppercaseReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('secure@passw0rd'); // kein Großbuchstabe
        $this->assertIsString($result);
        $this->assertStringContainsStringIgnoringCase('groß', $result);
    }

    public function testPasswordNoLowercaseReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('SECURE@PASSW0RD'); // kein Kleinbuchstabe
        $this->assertIsString($result);
        $this->assertStringContainsStringIgnoringCase('klein', $result);
    }

    public function testPasswordNoDigitReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('Secure@Password'); // keine Ziffer
        $this->assertIsString($result);
        $this->assertStringContainsStringIgnoringCase('ziffer', $result);
    }

    public function testPasswordNoSpecialCharReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('SecurePassw0rd'); // kein Sonderzeichen
        $this->assertIsString($result);
        $this->assertStringContainsStringIgnoringCase('sonder', $result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Grenzfälle
    // ──────────────────────────────────────────────────────────────────────

    public function testPasswordOnlySpecialCharsReturnsError(): void
    {
        $result = Auth::validatePasswordPolicy('!@#$%^&*()!@#$'); // kein Klein/Groß/Ziffer
        $this->assertIsString($result);
    }

    public function testPasswordWhitespaceCountsAsSpecialChar(): void
    {
        // Leerzeichen gilt als Sonderzeichen
        $result = Auth::validatePasswordPolicy('Secure Passw0rd');
        $this->assertTrue($result);
    }

    public function testLongValidPasswordReturnsTrue(): void
    {
        $result = Auth::validatePasswordPolicy('V3ry$ecure&LongPassphrase2026!');
        $this->assertTrue($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Rückgabetyp-Konsistenz
    // ──────────────────────────────────────────────────────────────────────

    public function testReturnTypeIsEitherTrueOrString(): void
    {
        $passwords = [
            'Valid@Passw0rd'     => true,
            'tooshort'           => false,
            'NoSpecialChar123456' => false,
        ];

        foreach ($passwords as $pw => $shouldBeTrue) {
            $result = Auth::validatePasswordPolicy($pw);
            if ($shouldBeTrue) {
                $this->assertTrue($result, "'{$pw}' sollte gültig sein");
            } else {
                $this->assertIsString($result, "'{$pw}' sollte eine Fehlermeldung als String zurückgeben");
            }
        }
    }
}
