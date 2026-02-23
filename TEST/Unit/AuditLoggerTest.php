<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\AuditLogger;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für AuditLogger
 *
 * Da log() eine DB-Verbindung benötigt, werden hauptsächlich
 * Singleton-Verhalten, Kategorie-Konstanten und Convenience-Methoden
 * (sollten keinen Fatal produzieren, wenn DB fehlt) getestet.
 */
class AuditLoggerTest extends TestCase
{
    private AuditLogger $logger;

    protected function setUp(): void
    {
        $this->logger = AuditLogger::instance();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = AuditLogger::instance();
        $b = AuditLogger::instance();
        $this->assertSame($a, $b);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Kategorie-Konstanten
    // ──────────────────────────────────────────────────────────────────────

    public function testCategoryConstantsAreDefinedAndNonEmpty(): void
    {
        $this->assertNotEmpty(AuditLogger::CAT_AUTH);
        $this->assertNotEmpty(AuditLogger::CAT_THEME);
        $this->assertNotEmpty(AuditLogger::CAT_PLUGIN);
        $this->assertNotEmpty(AuditLogger::CAT_USER);
        $this->assertNotEmpty(AuditLogger::CAT_SETTING);
        $this->assertNotEmpty(AuditLogger::CAT_MEDIA);
        $this->assertNotEmpty(AuditLogger::CAT_SYSTEM);
        $this->assertNotEmpty(AuditLogger::CAT_SECURITY);
    }

    public function testCategoryConstantsAreStrings(): void
    {
        $this->assertIsString(AuditLogger::CAT_AUTH);
        $this->assertIsString(AuditLogger::CAT_THEME);
        $this->assertIsString(AuditLogger::CAT_PLUGIN);
    }

    public function testAllCategoryConstantsAreUnique(): void
    {
        $cats = [
            AuditLogger::CAT_AUTH,
            AuditLogger::CAT_THEME,
            AuditLogger::CAT_PLUGIN,
            AuditLogger::CAT_USER,
            AuditLogger::CAT_SETTING,
            AuditLogger::CAT_MEDIA,
            AuditLogger::CAT_SYSTEM,
            AuditLogger::CAT_SECURITY,
        ];
        $this->assertCount(count($cats), array_unique($cats), 'Alle CAT_* Konstanten müssen unique sein');
    }

    // ──────────────────────────────────────────────────────────────────────
    // log() – darf nie eine unbehandelte Exception werfen
    // (DB-Fehler sollen intern abgefangen und nur geloggt werden)
    // ──────────────────────────────────────────────────────────────────────

    public function testLogDoesNotThrowEvenWithoutDatabase(): void
    {
        // Im Test-Kontext gibt es keine DB → log() muss den Fehler intern schlucken
        $this->expectNotToPerformAssertions();
        try {
            $this->logger->log(
                AuditLogger::CAT_AUTH,
                'test.event',
                'Unit-Test-Eintrag',
                'user',
                null,
                ['test' => true],
                'info'
            );
        } catch (\Throwable $e) {
            $this->fail('log() darf keine Exception werfen, Fehler muss intern behandelt werden: ' . $e->getMessage());
        }
    }

    public function testConvenienceMethodsDoNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            $this->logger->loginSuccess('test_user');
            $this->logger->loginFailed('attacker');
            $this->logger->themeSwitch('old-theme', 'new-theme');
            $this->logger->themeDelete('deleted-theme');
            $this->logger->pluginAction('activate', 'my-plugin');
            $this->logger->backupAction('create', 'backup_2026.zip');
        } catch (\Throwable $e) {
            $this->fail('Convenience-Methoden dürfen keine Exception werfen: ' . $e->getMessage());
        }
    }

    public function testUserRoleChangeDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            $this->logger->userRoleChange(42, 'member', 'admin');
        } catch (\Throwable $e) {
            $this->fail('userRoleChange() darf keine Exception werfen: ' . $e->getMessage());
        }
    }

    public function testThemeFileEditDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            $this->logger->themeFileEdit('my-theme', 'templates/index.php');
        } catch (\Throwable $e) {
            $this->fail('themeFileEdit() darf keine Exception werfen: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // getRecent() – ohne DB muss ein leeres Array zurückkommen
    // ──────────────────────────────────────────────────────────────────────

    public function testGetRecentReturnsArrayWithoutDatabase(): void
    {
        $result = $this->logger->getRecent(10);
        $this->assertIsArray($result);
        // Ohne DB-Verbindung = leeres Array (Exception wird intern abgefangen)
    }

    public function testGetRecentWithCategoryReturnsArray(): void
    {
        $result = $this->logger->getRecent(5, AuditLogger::CAT_AUTH);
        $this->assertIsArray($result);
    }

    public function testGetRecentWithLimitZeroReturnsArray(): void
    {
        $result = $this->logger->getRecent(0);
        $this->assertIsArray($result);
    }
}
