<?php
/**
 * ============================================================
 * Clase DB
 * Maneja la conexión a MySQL mediante PDO.
 * Implementa el patrón Singleton para reutilizar una única
 * conexión durante toda la petición.
 * ============================================================
 */

class DB
{
    private static string $host = "localhost";
    private static string $dbname = "productosdb";
    private static string $user = "root";
    private static string $pass = "";

    private static ?PDO $conexion = null;

    private function __construct() {}

    /**
     * Obtiene la conexión PDO.
     */
    public static function getConexion(): PDO
    {
        if (self::$conexion === null) {

            try {

                $dsn = "mysql:host=" . self::$host .
                       ";dbname=" . self::$dbname .
                       ";charset=utf8";

                self::$conexion = new PDO(
                    $dsn,
                    self::$user,
                    self::$pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

            } catch (PDOException $e) {

                die("Error de conexión: " . $e->getMessage());

            }

        }

        return self::$conexion;
    }

    /**
     * Ejecuta un SELECT.
     */
    public static function query(string $sql, array $parametros = []): array
    {
        $stmt = self::getConexion()->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Ejecuta INSERT.
     * Devuelve el ID insertado.
     */
    public static function insertSeguro(string $sql, array $parametros = []): int
    {
        $stmt = self::getConexion()->prepare($sql);

        $stmt->execute($parametros);

        return (int) self::getConexion()->lastInsertId();
    }

    /**
     * Ejecuta UPDATE.
     * Devuelve las filas afectadas.
     */
    public static function updateSeguro(string $sql, array $parametros = []): int
    {
        $stmt = self::getConexion()->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->rowCount();
    }

    /**
     * Ejecuta DELETE.
     * Devuelve las filas eliminadas.
     */
    public static function deleteSeguro(string $sql, array $parametros = []): int
    {
        $stmt = self::getConexion()->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->rowCount();
    }
}