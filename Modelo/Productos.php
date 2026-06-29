<?php

require_once __DIR__ . "/conexion.php";

/**
 * Modelo Producto.
 *
 * Representa la logica de negocio del CRUD de productos:
 * recibe los datos, los normaliza, valida reglas basicas y
 * ejecuta las operaciones contra la base de datos usando la clase DB.
 */
class Producto
{
    // ===============================
    // Propiedades
    // ===============================

    private int $id = 0;
    private string $codigo = "";
    private string $producto = "";
    private float $precio = 0;
    private int $cantidad = 0;

    private array $errores = [];

    // ===============================
    // Getters
    // ===============================

    /**
     * Devuelve el identificador interno del producto.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Devuelve el codigo unico del producto.
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Devuelve el nombre del producto.
     */
    public function getProducto(): string
    {
        return $this->producto;
    }

    /**
     * Devuelve el precio del producto.
     */
    public function getPrecio(): float
    {
        return $this->precio;
    }

    /**
     * Devuelve la cantidad disponible.
     */
    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    /**
     * Devuelve los mensajes generados durante la validacion.
     */
    public function getErrores(): array
    {
        return $this->errores;
    }
    // ===============================
    // Setters
    // ===============================

    /**
     * Asigna el ID del producto. Se usa principalmente al editar o eliminar.
     */
    public function setId($id): void
    {
        $this->id = (int)$id;
    }

    /**
     * Asigna el codigo del producto limpiando espacios y caracteres HTML.
     */
    public function setCodigo($codigo): void
    {
        $this->codigo = trim(htmlspecialchars($codigo));
    }

    /**
     * Asigna el nombre del producto limpiando espacios y caracteres HTML.
     */
    public function setProducto($producto): void
    {
        $this->producto = trim(htmlspecialchars($producto));
    }

    /**
     * Asigna el precio convirtiendo el valor recibido a decimal.
     */
    public function setPrecio($precio): void
    {
        $this->precio = (float)$precio;
    }

    /**
     * Asigna la cantidad convirtiendo el valor recibido a entero.
     */
    public function setCantidad($cantidad): void
    {
        $this->cantidad = (int)$cantidad;
    }

    // ===============================
    // Validaciones
    // ===============================

    /**
     * Valida las reglas principales antes de guardar o modificar.
     *
     * En registro nuevo la cantidad debe ser mayor o igual a 1.
     * En edicion se permite cantidad 0 para manejar productos agotados.
     */
    public function validar(bool $editar = false): bool
    {
        $this->errores = [];

        if ($this->codigo === "") {
            $this->errores[] = "Debe ingresar el código.";
        }

        if (strlen($this->codigo) > 20) {
            $this->errores[] = "El código no puede tener más de 20 caracteres.";
        }

        if ($this->producto === "") {
            $this->errores[] = "Debe ingresar el nombre del producto.";
        }

        if (strlen($this->producto) > 100) {
            $this->errores[] = "El nombre del producto es demasiado largo.";
        }

        if (!is_numeric($this->precio) || $this->precio <= 0) {
            $this->errores[] = "El precio debe ser mayor que cero.";
        }

        if (!is_numeric($this->cantidad)) {
            $this->errores[] = "La cantidad debe ser numérica.";
        }

        if (!$editar && $this->cantidad < 1) {
            $this->errores[] = "Al registrar un producto debe existir al menos una unidad.";
        }

        if ($editar && $this->cantidad < 0) {
            $this->errores[] = "La cantidad no puede ser negativa.";
        }

        return empty($this->errores);
    }

    // ===============================
    // Verificar código duplicado
    // ===============================

    /**
     * Verifica si el codigo ya esta registrado en otro producto.
     *
     * Cuando se edita, permite conservar el mismo codigo del producto actual.
     */
    public function codigoExiste(): bool
    {
        $sql = "SELECT id
                FROM productos
                WHERE codigo = :codigo";

        $datos = DB::query($sql, [

            ":codigo" => $this->codigo

        ]);

        foreach ($datos as $fila) {

            if ((int)$fila["id"] !== $this->id) {
                return true;
            }
        }

        return false;
    }

    // ===============================
    // Guardar
    // ===============================

    /**
     * Inserta un nuevo producto y devuelve el ID generado por MySQL.
     */
    public function guardar(): int
    {
        $sql = "INSERT INTO productos
                (codigo, producto, precio, cantidad)

                VALUES

                (:codigo,:producto,:precio,:cantidad)";

        return DB::insertSeguro($sql, [

            ":codigo" => $this->codigo,
            ":producto" => $this->producto,
            ":precio" => $this->precio,
            ":cantidad" => $this->cantidad

        ]);
    }

    // ===============================
    // Editar
    // ===============================

    /**
     * Actualiza los datos del producto actual y devuelve filas afectadas.
     */
    public function editar(): int
    {
        $sql = "UPDATE productos

                SET

                codigo=:codigo,
                producto=:producto,
                precio=:precio,
                cantidad=:cantidad

                WHERE id=:id";

        return DB::updateSeguro($sql, [

            ":codigo" => $this->codigo,
            ":producto" => $this->producto,
            ":precio" => $this->precio,
            ":cantidad" => $this->cantidad,
            ":id" => $this->id

        ]);
    }

    // ===============================
    // Eliminar
    // ===============================

    /**
     * Elimina el producto actual usando su ID.
     */
    public function eliminar(): int
    {
        $sql = "DELETE FROM productos
                WHERE id=:id";

        return DB::deleteSeguro($sql, [

            ":id" => $this->id

        ]);
    }

    // ===============================
    // Obtener producto por ID
    // ===============================

    /**
     * Consulta un producto especifico por ID.
     */
    public static function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT *
                FROM productos
                WHERE id = :id
                LIMIT 1";

        $datos = DB::query($sql, [
            ":id" => $id
        ]);

        return $datos[0] ?? null;
    }

    // ===============================
    // Buscar
    // ===============================

    /**
     * Busca productos por coincidencia parcial en codigo o nombre.
     */
    public static function buscar(string $texto): array
    {
        $buscar = "%" . trim($texto) . "%";

        $sql = "SELECT *

                FROM productos
                WHERE codigo LIKE :codigo
                OR producto LIKE :producto
                ORDER BY id DESC";

        return DB::query($sql, [

            ":codigo" => $buscar,
            ":producto" => $buscar

        ]);
    }

    // ===============================
    // Listar
    // ===============================

    /**
     * Lista todos los productos, mostrando primero los mas recientes.
     */
    public static function listarTodos(): array
    {
        return DB::query(
            "SELECT *
             FROM productos
             ORDER BY id DESC"
        );
    }
}
