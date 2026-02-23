<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\Security;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Security-Klasse
 *
 * Testet CSRF-Token-Generierung/-Verifikation, Sanitierung,
 * Passwort-Hashing, E-Mail-/URL-Validierung sowie getClientIp().
 */
class SecurityTest extends TestCase
{
    private Security $sec;

    protected function setUp(): void
    {
        $this->sec = Security::instance();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = Security::instance();
        $b = Security::instance();
        $this->assertSame($a, $b, 'Security::instance() muss denselben Singleton zurückgeben');
    }

    // ──────────────────────────────────────────────────────────────────────
    // CSRF / Token
    // ──────────────────────────────────────────────────────────────────────

    public function testGenerateTokenReturnsNonEmptyString(): void
    {
        $token = $this->sec->generateToken('test_action');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateTokenIsDifferentForDifferentActions(): void
    {
        $t1 = $this->sec->generateToken('action_a');
        $t2 = $this->sec->generateToken('action_b');
        $this->assertNotEquals($t1, $t2, 'Tokens für verschiedene Actions müssen unterschiedlich sein');
    }

    public function testVerifyTokenReturnsTrueForValidToken(): void
    {
        $token = $this->sec->generateToken('save_items');
        $this->assertTrue($this->sec->verifyToken($token, 'save_items'));
    }

    public function testVerifyTokenReturnsFalseForWrongAction(): void
    {
        $token = $this->sec->generateToken('action_x');
        $this->assertFalse($this->sec->verifyToken($token, 'action_y'));
    }

    public function testVerifyTokenReturnsFalseForTamperedToken(): void
    {
        $token = $this->sec->generateToken('edit_page');
        $tampered = substr($token, 0, -4) . 'dead';
        $this->assertFalse($this->sec->verifyToken($tampered, 'edit_page'));
    }

    public function testVerifyTokenReturnsFalseForEmptyToken(): void
    {
        $this->assertFalse($this->sec->verifyToken('', 'any_action'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Sanitize
    // ──────────────────────────────────────────────────────────────────────

    public function testSanitizeTextStripsHtmlTags(): void
    {
        $result = Security::sanitize('<script>alert(1)</script>Hello', 'text');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testSanitizeEmailNormalizesEmail(): void
    {
        $result = Security::sanitize('user@example.com', 'email');
        $this->assertEquals('user@example.com', $result);
    }

    public function testSanitizeEmailRejectsInvalidEmail(): void
    {
        $result = Security::sanitize('not-an-email', 'email');
        $this->assertEmpty($result);
    }

    public function testSanitizeIntReturnsDigitsOnly(): void
    {
        $result = Security::sanitize('42abc!', 'int');
        $this->assertEquals('42', $result);
    }

    public function testSanitizeUrlRejectsJavascriptScheme(): void
    {
        $result = Security::sanitize('javascript:alert(1)', 'url');
        $this->assertEmpty($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Escape
    // ──────────────────────────────────────────────────────────────────────

    public function testEscapeConvertsSpecialChars(): void
    {
        $escaped = Security::escape('<b>Test & "Hello"</b>');
        $this->assertStringContainsString('&lt;', $escaped);
        $this->assertStringContainsString('&amp;', $escaped);
        $this->assertStringContainsString('&quot;', $escaped);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Validierung
    // ──────────────────────────────────────────────────────────────────────

    public function testValidateEmailReturnsTrueForValidEmail(): void
    {
        $this->assertTrue(Security::validateEmail('admin@example.com'));
    }

    public function testValidateEmailReturnsFalseForInvalidEmail(): void
    {
        $this->assertFalse(Security::validateEmail('no-at-sign'));
        $this->assertFalse(Security::validateEmail(''));
    }

    public function testValidateUrlReturnsTrueForHttps(): void
    {
        $this->assertTrue(Security::validateUrl('https://example.com/path?foo=bar'));
    }

    public function testValidateUrlReturnsFalseForPlainText(): void
    {
        $this->assertFalse(Security::validateUrl('not a url'));
        $this->assertFalse(Security::validateUrl('ftp://example.com'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Passwort-Hashing
    // ──────────────────────────────────────────────────────────────────────

    public function testHashPasswordReturnsBcryptHash(): void
    {
        $hash = Security::hashPassword('Test@Password1');
        $this->assertStringStartsWith('$2y$', $hash, 'Muss ein bcrypt-Hash sein');
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $hash = Security::hashPassword('MySecure@Pass1!');
        $this->assertTrue(Security::verifyPassword('MySecure@Pass1!', $hash));
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
        $hash = Security::hashPassword('CorrectPassword1!');
        $this->assertFalse(Security::verifyPassword('WrongPassword1!', $hash));
    }

    public function testHashesAreDifferentForSamePassword(): void
    {
        $h1 = Security::hashPassword('Same@Pass1!');
        $h2 = Security::hashPassword('Same@Pass1!');
        // bcrypt erzeugt per Salt immer verschiedene Hashes
        $this->assertNotEquals($h1, $h2);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Client-IP
    // ──────────────────────────────────────────────────────────────────────

    public function testGetClientIpReturnsFallbackInCliContext(): void
    {
        $ip = Security::getClientIp();
        // Im CLI/Test-Kontext: kein REMOTE_ADDR → Fallback '127.0.0.1' oder '0.0.0.0'
        $this->assertMatchesRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $ip);
    }
}
