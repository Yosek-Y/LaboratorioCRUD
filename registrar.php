<?php
/**
 * ============================================================
 * ARCHIVO: registrar.php
 * DESCRIPCIÓN:
 * Controlador principal del CRUD de productos.
 * Recibe las solicitudes mediante Fetch API y responde
 * exclusivamente en formato JSON.
 * ============================================================
 */

// Indicamos que todas las respuestas de este archivo seran JSON.
// Esto permite que fetch() pueda leer la respuesta con respuesta.json().
header("Content-Type: application/json; charset=utf-8");

// Cargamos la conexion a la base de datos y el modelo Producto.
// require_once evita que el mismo archivo se cargue mas de una vez.
require_once __DIR__ . "/Modelo/conexion.php";
require_once __DIR__ . "/Modelo/Productos.php";

/**
 * Envía una respuesta en formato JSON
 * y finaliza la ejecución del script.
 */
function responder(bool $success, string $message, array $extra = []): void
{
    // array_merge une la respuesta basica con datos extra.
    // Ejemplo: errores de validacion, lista de productos o ID insertado.
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));

    // Detiene el script para que no se ejecute mas codigo despues de responder.
    exit;
}

// Este archivo solo debe recibir solicitudes POST enviadas desde JavaScript.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.");
}

// Obtenemos la acción enviada desde Fetch
// El operador ?? evita errores si el campo Accion no llega en la peticion.
$accion = trim($_POST["Accion"] ?? "");

// Verificamos que se haya recibido una acción válida
if ($accion === "") {
    responder(false, "No se recibió ninguna acción.");
}

// Segun la accion recibida, se ejecuta una operacion diferente del CRUD.
switch ($accion) {

    //=========================================================
    // GUARDAR
    //=========================================================
    case "Guardar":

        // Creamos un objeto Producto para cargar los datos del formulario.
        $producto = new Producto();

        // Asignamos los valores recibidos por POST.
        // Los setters limpian textos o convierten numeros segun corresponda.
        $producto->setCodigo($_POST["codigo"] ?? "");
        $producto->setProducto($_POST["producto"] ?? "");
        $producto->setPrecio($_POST["precio"] ?? 0);
        $producto->setCantidad($_POST["cantidad"] ?? 0);

        // Validamos reglas como codigo obligatorio, precio mayor a cero y cantidad minima.
        if (!$producto->validar()) {

            responder(false, "Errores de validación.", [
                "errors" => $producto->getErrores(),
                "accion" => "Guardar"
            ]);

        }

        // Antes de guardar, comprobamos que no exista otro producto con el mismo codigo.
        if ($producto->codigoExiste()) {

            responder(false, "Ya existe un producto con ese código.", [
                "accion" => "Guardar"
            ]);

        }

        // Si las validaciones pasan, insertamos el producto en la base de datos.
        $id = $producto->guardar();

        // Si MySQL devuelve un ID mayor que cero, el registro fue exitoso.
        if ($id > 0) {
            responder(true, "Producto registrado correctamente.", [
                "id" => $id,
                "accion" => "Guardar"
            ]);
        }

        // Respuesta de respaldo si no se pudo insertar el producto.
        responder(false, "No fue posible registrar el producto.", [
            "accion" => "Guardar"
        ]);
    break;
    
    //=========================================================
    // MODIFICAR
    //=========================================================
    case "Modificar":

        // Creamos el objeto Producto que se va a actualizar.
        $producto = new Producto();

        // Cargamos el ID y verificamos que sea válido
        $producto->setId($_POST["id"] ?? 0);
        if ($producto->getId() <= 0) {
            responder(false, "ID de producto inválido.");
        }

        // Cargamos los nuevos datos enviados desde el formulario.
        $producto->setCodigo($_POST["codigo"] ?? "");
        $producto->setProducto($_POST["producto"] ?? "");
        $producto->setPrecio($_POST["precio"] ?? 0);
        $producto->setCantidad($_POST["cantidad"] ?? 0);

        // El parametro true indica que estamos editando.
        // En edicion se permite cantidad 0 para productos agotados.
        if (!$producto->validar(true)) {

            responder(false, "Errores de validación.", [
                "errors" => $producto->getErrores(),
                "accion" => "Modificar"
            ]);

        }

        // Revisamos que el codigo no este asignado a otro producto diferente.
        if ($producto->codigoExiste()) {

            responder(false, "Ya existe otro producto con ese código.", [
                "accion" => "Modificar"
            ]);

        }

        // Ejecutamos el UPDATE y recibimos cuantas filas fueron modificadas.
        $filas = $producto->editar();

        // Si no hubo filas afectadas, puede ser porque los datos eran iguales.
        responder(
            $filas > 0,
            $filas > 0
                ? "Producto actualizado correctamente."
                : "No hubo cambios para actualizar.",
            [
                "accion" => "Modificar"
            ]
        );

    break;

    //=========================================================
    // LISTAR
    //=========================================================
    case "Listar":

        // Consultamos todos los productos y los enviamos al frontend.
        responder(true, "Productos obtenidos correctamente.", [
            "datos" => Producto::listarTodos(),
            "accion" => "Listar"
        ]);

    break;

    //=========================================================
    // BUSCAR
    //=========================================================
    case "Buscar":

        // Recibimos el texto digitado en el buscador.
        $texto = trim($_POST["termino"] ?? "");

        // Buscamos coincidencias por codigo o nombre del producto.
        $datos = Producto::buscar($texto);

        // Devolvemos los resultados y el total encontrado.

        responder(true, "Búsqueda realizada correctamente.", [
            "datos" => $datos,
            "total" => count($datos),
            "accion" => "Buscar"
        ]);

    break;

    //=========================================================
    // ELIMINAR
    //=========================================================
    case "Eliminar":

        // Creamos un objeto Producto solo para asignarle el ID que se eliminara.
        $producto = new Producto();

        // Cargamos el ID del producto a eliminar
        $producto->setId($_POST["id"] ?? 0);
        if ($producto->getId() <= 0) {
            responder(false, "ID inválido.");
        }

        // Ejecutamos el DELETE y recibimos cuantas filas fueron eliminadas.
        $filas = $producto->eliminar();

        // Respondemos segun si realmente se elimino algun registro.
        responder(
            $filas > 0,
            $filas > 0
                ? "Producto eliminado correctamente."
                : "No se encontró el producto.",
            [
                "accion" => "Eliminar"
            ]
        );

    break;

    //=========================================================
    // OBTENER
    //=========================================================
    case "Obtener":

        // Convertimos el ID recibido a entero para consultarlo de forma segura.
        $id = intval($_POST["id"] ?? 0);

        // Si el ID no es valido, no se hace la consulta.
        if ($id <= 0) {
            responder(false, "ID inválido para consultar.", [
                "accion" => "Obtener"
            ]);
        }

        // Consultamos el producto por ID para enviarlo al formulario de edicion.
        $producto = Producto::obtenerPorId($id);

        // Si existe, se devuelve el arreglo con sus datos.
        if ($producto) {

            responder(true, "Producto encontrado.", [
                "producto" => $producto,
                "accion" => "Obtener"
            ]);

        }
        // Si no existe un producto con ese ID, enviamos un error controlado.

        responder(false, "No se encontró el producto solicitado.", [
            "accion" => "Obtener"
        ]);

    break;

    //=========================================================
    // ACCIÓN DESCONOCIDA
    //=========================================================
    default:

        // Si llega una accion diferente a las permitidas, no ejecutamos ninguna operacion.

        responder(false, "Acción no reconocida.");

}
