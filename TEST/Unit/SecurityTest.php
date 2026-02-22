<?php
/**
 * Security Unit Tests
 *
 * Testet Security::sanitize(), validateEmail(), hashPassword(), verifyPassword(), escape().
 * Diese Methoden sind static → kein DB-Zugriff, vollständig isolierbar.
 *
 * @package CMS\Tests\Unit
 */

declare(strict_types=1);

namespace CMS\Tests\Unit;

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
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $hash = Security::hashPassword('MyP@ssword!1');
        $this->assertTrue(Security::verifyPassword('MyP@ssword!1', $hash));
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
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
    }
}
