<?php
/**
 * Service Container (DI-Container)
 *
 * Einfacher Dependency-Injection-Container für 365CMS.
 * Ermöglicht:
 * - Explizite Registrierung von Services (Bindings)
 * - Lazy-Auflösung (Closure wird nur beim ersten Aufruf ausgeführt)
 * - Singletons (einmalige Instanziierung, dann gecacht)
 * - Schrittweise Migration der bestehenden Singleton-Klassen
 *
 * Verwendung:
 *   $container = Container::instance();
 *   $container->singleton('db', fn() => Database::instance());
 *   $db = $container->make('db');   // gibt stets dieselbe Instanz zurück
 *
 *   // Bereits vorhandene Instanz einbinden:
 *   $container->bindInstance('db', $existingDb);
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Container
{
    private static ?self $instance = null;

    /** @var array<string, \Closure> Registrierte Factories */
    private array $bindings = [];

    /** @var array<string, mixed> Gecachte Singleton-Instanzen */
    private array $resolved = [];

    /** @var array<string, bool> Welche Bindings sind Singletons */
    private array $singletons = [];

    // -------------------------------------------------------------------------
    // Singleton-Zugang
    // -------------------------------------------------------------------------

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // -------------------------------------------------------------------------
    // Registrierung
    // -------------------------------------------------------------------------

    /**
     * Registriert eine transiente Factory (jeder make()-Aufruf erzeugt neue Instanz).
     *
     * @param string   $abstract Schlüssel (z. B. Klassenname oder Interface-Name)
     * @param \Closure $factory  Callback, der die Instanz erzeugt
     */
    public function bind(string $abstract, \Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        unset($this->resolved[$abstract]);
    }

    /**
     * Registriert eine Singleton-Factory (Instanz wird beim ersten make() erzeugt
     * und danach immer wiederverwendet).
     */
    public function singleton(string $abstract, \Closure $factory): void
    {
        $this->bind($abstract, $factory);
        $this->singletons[$abstract] = true;
    }

    /**
     * Registriert eine bereits vorhandene Instanz direkt als Singleton.
     * (Alias: bindInstance, um Konflikt mit static::instance() zu vermeiden)
     */
    public function bindInstance(string $abstract, mixed $resolved): void
    {
        $this->resolved[$abstract]   = $resolved;
        $this->singletons[$abstract] = true;
    }

    // -------------------------------------------------------------------------
    // Auflösung
    // -------------------------------------------------------------------------

    /**
     * Löst einen registrierten Service auf.
     *
     * @throws \RuntimeException wenn $abstract nicht registriert ist
     */
    public function make(string $abstract): mixed
    {
        // Bereits aufgelöste Singleton-Instanz zurückgeben
        if (isset($this->resolved[$abstract])) {
            return $this->resolved[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException(
                "Container: '{$abstract}' ist nicht registriert. " .
                "Bitte zuerst bind() oder singleton() aufrufen."
            );
        }

        $instance = ($this->bindings[$abstract])($this);

        if ($this->singletons[$abstract] ?? false) {
            $this->resolved[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Alias für make() – kompatibel mit Laravel-Konvention.
     */
    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    /**
     * Prüft ob ein Abstract registriert oder bereits aufgelöst ist.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->resolved[$abstract]);
    }

    /**
     * Gibt alle registrierten Abstract-Schlüssel zurück.
     *
     * @return string[]
     */
    public function registered(): array
    {
        return array_unique(
            array_merge(array_keys($this->bindings), array_keys($this->resolved))
        );
    }

    /**
     * Entfernt einen Service aus dem Container (zurücksetzen einer Singleton-Instanz).
     */
    public function forget(string $abstract): void
    {
        unset($this->resolved[$abstract]);
    }

    /**
     * Leert alle Registrierungen (v. a. für Tests).
     */
    public function flush(): void
    {
        $this->bindings  = [];
        $this->resolved  = [];
        $this->singletons = [];
    }
}
