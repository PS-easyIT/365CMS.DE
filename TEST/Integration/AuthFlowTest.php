<?php

declare(strict_types=1);

namespace CMS\Tests\Integration;

use CMS\Auth;
use CMS\Security;
use PHPUnit\Framework\TestCase;

/**
 * Integrations-Tests für den Auth-Flow
 *
 * Voraussetzung: CMS_MODE=test (gesetzt in bootstrap.php)
 * Diese Tests laufen ohne echte DB – sie prüfen den stateless Teil des Auth-Systems.
 * DB-abhängige Tests (login, register) sind mit @group db markiert und werden in CI
 * übersprungen, wenn MYSQL_AVAILABLE=false.
 *
 * @group integration
 */
class AuthFlowTest extends TestCase
{
    private Auth $auth;

    protected function setUp(): void
    {
        $this->auth = Auth::instance();
        // Keine bestehende Session im Test-Kontext
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // isLoggedIn – ohne Session
    // ──────────────────────────────────────────────────────────────────────

    public function testIsLoggedInReturnsFalseWithoutSession(): void
    {
        $this->assertFalse(Auth::isLoggedIn(), 'Ohne Session darf isLoggedIn() nicht true zurückgeben');
    }

    public function testIsAdminReturnsFalseWithoutSession(): void
    {
        $this->assertFalse(Auth::isAdmin());
    }

    public function testHasRoleReturnsFalseWithoutSession(): void
    {
        $this->assertFalse(Auth::hasRole('admin'));
        $this->assertFalse(Auth::hasRole('member'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // currentUser / getCurrentUser
    // ──────────────────────────────────────────────────────────────────────

    public function testCurrentUserReturnsNullWithoutSession(): void
    {
        $user = $this->auth->currentUser();
        $this->assertNull($user, 'currentUser() ohne Session muss null zurückgeben');
    }

    public function testGetCurrentUserReturnsNullWithoutSession(): void
    {
        $user = Auth::getCurrentUser();
        $this->assertNull($user);
    }

    // ──────────────────────────────────────────────────────────────────────
    // login – Fehlerfälle ohne Datenbank
    // ──────────────────────────────────────────────────────────────────────

    public function testLoginWithEmptyCredentialsReturnsError(): void
    {
        $result = $this->auth->login('', '');
        $this->assertNotTrue($result, 'Login mit leeren Zugangsdaten darf nicht erfolgreich sein');
        $this->assertIsString($result);
    }

    public function testLoginWithInvalidCredentialsReturnsErrorWithoutDatabase(): void
    {
        // Ohne DB-Verbindung muss login() einen Fehler-String zurückgeben (kein Exception)
        $result = $this->auth->login('not_existing_user_xyz', 'SomePass@1234');
        $this->assertNotTrue($result, 'Login ohne DB-Config darf nicht true zurückgeben');
    }

    // ──────────────────────────────────────────────────────────────────────
    // register – Validierung
    // ──────────────────────────────────────────────────────────────────────

    public function testRegisterWithMissingFieldsReturnsError(): void
    {
        $result = $this->auth->register([]);
        $this->assertIsString($result, 'register() mit leerem Array muss Fehler-String zurückgeben');
    }

    public function testRegisterWithInvalidEmailReturnsError(): void
    {
        $result = $this->auth->register([
            'username' => 'testuser',
            'email'    => 'not-a-valid-email',
            'password' => 'Test@Passw0rd!',
        ]);
        $this->assertIsString($result, 'Ungültige E-Mail muss als Fehler zurückgegeben werden');
    }

    public function testRegisterWithWeakPasswordReturnsError(): void
    {
        $result = $this->auth->register([
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => 'weak',
        ]);
        $this->assertIsString($result, 'Schwaches Passwort muss als Fehler zurückgegeben werden');
    }

    // ──────────────────────────────────────────────────────────────────────
    // hasCapability – ohne Session
    // ──────────────────────────────────────────────────────────────────────

    public function testHasCapabilityReturnsFalseWithoutSession(): void
    {
        $this->assertFalse($this->auth->hasCapability('manage_users'));
        $this->assertFalse($this->auth->hasCapability('edit_content'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // logout – darf nie eine Exception werfen
    // ──────────────────────────────────────────────────────────────────────

    public function testLogoutDoesNotThrowWithoutActiveSession(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            $this->auth->logout();
        } catch (\Throwable $e) {
            $this->fail('logout() ohne aktive Session darf keine Exception werfen: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // MFA – Basics
    // ──────────────────────────────────────────────────────────────────────

    public function testIsMfaEnabledReturnsFalseForNonExistentUser(): void
    {
        $result = $this->auth->isMfaEnabled(999999);
        $this->assertFalse($result, 'isMfaEnabled() für nicht vorhandenen User muss false zurückgeben');
    }

    public function testVerifyMfaCodeReturnsFalseForInvalidCode(): void
    {
        $result = $this->auth->verifyMfaCode(999999, '000000');
        $this->assertFalse($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CSRF-Integration: Auth + Security zusammen
    // ──────────────────────────────────────────────────────────────────────

    public function testCsrfTokenVerificationIntegration(): void
    {
        $sec = Security::instance();
        $token = $sec->generateToken('login_form');

        // Token muss sofort valid sein
        $this->assertTrue($sec->verifyToken($token, 'login_form'));

        // Gleicher Token mit anderer Action: invalid
        $this->assertFalse($sec->verifyToken($token, 'other_form'));

        // Veränderter Token: invalid
        $tampered = substr($token, 0, -4) . '0000';
        $this->assertFalse($sec->verifyToken($tampered, 'login_form'));
    }
}
