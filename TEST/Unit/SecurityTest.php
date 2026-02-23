<?php
<<<<<<< HEAD
=======
/**
 * Security Unit Tests
 *
 * Testet Security::sanitize(), validateEmail(), hashPassword(), verifyPassword(), escape().
 * Diese Methoden sind static → kein DB-Zugriff, vollständig isolierbar.
 *
 * @package CMS\Tests\Unit
 */
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a

declare(strict_types=1);

namespace CMS\Tests\Unit;

<<<<<<< HEAD
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
=======
use PHPUnit\Framework\TestCase;
use CMS\Security;

class SecurityTest extends TestCase
{
    // ── sanitize (text) ───────────────────────────────────────────────────────

    public function testSanitizeTextStripsHtmlTags(): void
    {
        $result = Security::sanitize('<script>alert("xss")</script>Hello', 'text');
        $this->assertSame('Hello', $result);
    }

    public function testSanitizeTextTrimsWhitespace(): void
    {
        $result = Security::sanitize('  hello world  ');
        $this->assertSame('hello world', $result);
    }

    public function testSanitizeDefaultIsText(): void
    {
        $result = Security::sanitize('<b>bold</b>');
        $this->assertSame('bold', $result);
    }

    // ── sanitize (email) ──────────────────────────────────────────────────────

    public function testSanitizeEmailRemovesInvalidChars(): void
    {
        $result = Security::sanitize('user<tag>@example.com', 'email');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testSanitizeEmailPreservesValidEmail(): void
    {
        $result = Security::sanitize('user@example.com', 'email');
        $this->assertStringContainsString('@', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    // ── sanitize (username) ───────────────────────────────────────────────────

    public function testSanitizeUsernameAllowsAlphanumericAndUnderscore(): void
    {
        $result = Security::sanitize('user_123', 'username');
        $this->assertSame('user_123', $result);
    }

    public function testSanitizeUsernameRemovesSpecialChars(): void
    {
        $result = Security::sanitize('user@name!#$', 'username');
        $this->assertSame('username', $result);
    }

    public function testSanitizeUsernameRemovesSpaces(): void
    {
        $result = Security::sanitize('my user', 'username');
        $this->assertSame('myuser', $result);
    }

    // ── sanitize (html) ───────────────────────────────────────────────────────

    public function testSanitizeHtmlEscapesSpecialChars(): void
    {
        $result = Security::sanitize('<b>Test & "value"</b>', 'html');
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    // ── sanitize (int) ────────────────────────────────────────────────────────

    public function testSanitizeIntRemovesNonDigits(): void
    {
        $result = Security::sanitize('abc123def', 'int');
        $this->assertSame('123', $result);
    }

    public function testSanitizeIntPreservesNegativeSign(): void
    {
        $result = Security::sanitize('-42abc', 'int');
        $this->assertStringContainsString('-', $result);
        $this->assertStringContainsString('42', $result);
    }

    // ── validateEmail ─────────────────────────────────────────────────────────

    public function testValidateEmailAcceptsValidEmail(): void
    {
        $this->assertTrue(Security::validateEmail('admin@example.com'));
        $this->assertTrue(Security::validateEmail('user.name+tag@sub.domain.org'));
    }

    public function testValidateEmailRejectsInvalidEmail(): void
    {
        $this->assertFalse(Security::validateEmail('notanemail'));
        $this->assertFalse(Security::validateEmail('missing@'));
        $this->assertFalse(Security::validateEmail('@nodomain'));
        $this->assertFalse(Security::validateEmail(''));
    }

    // ── hashPassword / verifyPassword ─────────────────────────────────────────

    public function testHashPasswordReturnsNonEmptyString(): void
    {
        $hash = Security::hashPassword('SecureP@ss123');
        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
    }

    public function testHashPasswordCreatesUniqueHashes(): void
    {
        $hash1 = Security::hashPassword('same_password');
        $hash2 = Security::hashPassword('same_password');
        // bcrypt erzeugt zwingend unterschiedliche Hashes (Salting)
        $this->assertNotSame($hash1, $hash2);
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
<<<<<<< HEAD
        $hash = Security::hashPassword('MySecure@Pass1!');
        $this->assertTrue(Security::verifyPassword('MySecure@Pass1!', $hash));
=======
        $hash = Security::hashPassword('MyP@ssword!1');
        $this->assertTrue(Security::verifyPassword('MyP@ssword!1', $hash));
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
<<<<<<< HEAD
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
=======
        $hash = Security::hashPassword('correct_password');
        $this->assertFalse(Security::verifyPassword('wrong_password', $hash));
    }

    public function testVerifyPasswordReturnsFalseForEmpty(): void
    {
        $hash = Security::hashPassword('real_password');
        $this->assertFalse(Security::verifyPassword('', $hash));
    }

    // ── escape ────────────────────────────────────────────────────────────────

    public function testEscapeConvertsSpecialChars(): void
    {
        $result = Security::escape('<script>alert(1)</script>');
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
    }

    public function testEscapeHandlesAmpersand(): void
    {
        $result = Security::escape('AT&T is a company');
        $this->assertStringContainsString('&amp;', $result);
    }

    public function testEscapeHandlesQuotes(): void
    {
        $result = Security::escape('"double" and \'single\'');
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }
}
