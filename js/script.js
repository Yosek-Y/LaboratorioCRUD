/**
 * ============================================================
 * ARCHIVO: script.js
 * DESCRIPCIÓN:
 * Lógica del cliente para el CRUD de productos.
 * Captura eventos del formulario, envía datos con Fetch API,
 * procesa respuestas JSON y muestra alertas con SweetAlert2.
 * Usa switch(accion), tal como se solicita en el laboratorio.
 * ============================================================
 */

let productosActuales = [];

document.addEventListener("DOMContentLoaded", () => {

    const formProducto = document.getElementById("formProducto");
    const btnCancelar = document.getElementById("btnCancelar");
    const inputBuscar = document.getElementById("buscar");
    const tablaProductos = document.getElementById("tablaProductos");

    // Cargamos los productos al abrir la página.
    ListarProductos();

    // Evento para Guardar o Modificar.
    formProducto.addEventListener("submit", (e) => {

        e.preventDefault();

        const id = document.getElementById("id").value.trim();
        const accion = id === "" ? "Guardar" : "Modificar";

        if (!validarFormulario(accion)) {
            return;
        }

        const formData = new FormData(formProducto);
        formData.set("Accion", accion);

        enviarFetch(formData, accion);

    });

    // Evento para cancelar edición.
    btnCancelar.addEventListener("click", () => {

        limpiarFormulario();

    });

    // Búsqueda automática mientras se escribe.
    let temporizadorBusqueda = null;

    inputBuscar.addEventListener("input", () => {

        clearTimeout(temporizadorBusqueda);

        temporizadorBusqueda = setTimeout(() => {

            const termino = inputBuscar.value.trim();

            if (termino === "") {
                ListarProductos();
                return;
            }

            const formData = new FormData();

            formData.set("Accion", "Buscar");
            formData.set("termino", termino);

            enviarFetch(formData, "Buscar");

        }, 350);

    });

    // Eventos delegados para Editar y Eliminar.
    tablaProductos.addEventListener("click", (e) => {

        const botonEditar = e.target.closest(".btn-editar");
        const botonEliminar = e.target.closest(".btn-eliminar");

        if (botonEditar) {

            const id = botonEditar.dataset.id;
            cargarEdicion(id);

        }

        if (botonEliminar) {

            const id = botonEliminar.dataset.id;
            confirmarEliminar(id);

        }

    });

});

/**
 * Envía datos al servidor usando Fetch API.
 */
async function enviarFetch(formData, accion) {

    try {

        const mostrarCarga = ["Guardar", "Modificar", "Eliminar"].includes(accion);

        if (mostrarCarga) {

            Swal.fire({
                title: "Procesando...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

        }

        const respuesta = await fetch("registrar.php", {
            method: "POST",
            body: formData
        });

        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const data = await respuesta.json();

        // Switch principal del frontend.
        switch (accion) {

            case "Guardar":

                if (data.success) {

                    Swal.fire({
                        icon: "success",
                        title: "Producto registrado",
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    });

                    limpiarFormulario();
                    actualizarTablaSegunBusqueda();

                } else {

                    mostrarErrores(data);

                }

                break;

            case "Modificar":

                if (data.success) {

                    Swal.fire({
                        icon: "success",
                        title: "Producto actualizado",
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    });

                    limpiarFormulario();
                    actualizarTablaSegunBusqueda();

                } else {

                    mostrarErrores(data);

                }

                break;

            case "Listar":

                if (data.success) {
                    renderizarTabla(data.datos || []);
                } else {
                    mostrarErrores(data);
                }

                break;

            case "Buscar":

                if (data.success) {
                    renderizarTabla(data.datos || []);
                } else {
                    mostrarErrores(data);
                }

                break;

            case "Eliminar":

                if (data.success) {

                    Swal.fire({
                        icon: "success",
                        title: "Producto eliminado",
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    });

                    actualizarTablaSegunBusqueda();

                } else {

                    mostrarErrores(data);

                }

                break;

            default:

                Swal.fire({
                    icon: "warning",
                    title: "Acción no reconocida",
                    text: data.message || "El servidor respondió una acción inesperada."
                });

                break;

        }

    } catch (error) {

        console.error("Error en Fetch:", error);

        Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo comunicar con el servidor. Verifica que Apache y MySQL estén activos en XAMPP."
        });

    }

}

/**
 * Solicita todos los productos al servidor.
 */
function ListarProductos() {

    const formData = new FormData();

    formData.set("Accion", "Listar");

    enviarFetch(formData, "Listar");

}

/**
 * Si hay texto en el buscador, mantiene la búsqueda.
 * Si no hay texto, lista todo.
 */
function actualizarTablaSegunBusqueda() {

    const termino = document.getElementById("buscar").value.trim();

    if (termino === "") {

        ListarProductos();
        return;

    }

    const formData = new FormData();

    formData.set("Accion", "Buscar");
    formData.set("termino", termino);

    enviarFetch(formData, "Buscar");

}

/**
 * Renderiza los productos dentro de la tabla.
 */
function renderizarTabla(productos) {

    productosActuales = productos;

    const tbody = document.getElementById("tablaProductos");

    tbody.innerHTML = "";

    if (productos.length === 0) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    No hay productos para mostrar.
                </td>
            </tr>
        `;

        return;

    }

    productos.forEach((p) => {

        const fila = document.createElement("tr");

        fila.innerHTML = `
            <td>${escapeHTML(p.id)}</td>
            <td>${escapeHTML(p.codigo)}</td>
            <td>${escapeHTML(p.producto)}</td>
            <td>$${Number(p.precio).toFixed(2)}</td>
            <td>${escapeHTML(p.cantidad)}</td>
            <td>
                <button
                    type="button"
                    class="btn btn-warning btn-sm btn-editar me-1"
                    data-id="${escapeHTML(p.id)}">
                    <i class="bi bi-pencil-square"></i> Editar
                </button>

                <button
                    type="button"
                    class="btn btn-danger btn-sm btn-eliminar"
                    data-id="${escapeHTML(p.id)}">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </td>
        `;

        tbody.appendChild(fila);

    });

}

/**
 * Consulta un producto por ID y carga sus datos en el formulario.
 * Esta función usa registrar.php con Accion = "Obtener",
 * evitando crear un archivo editar.php separado.
 */
async function cargarEdicion(id) {

    try {

        const formData = new FormData();

        formData.set("Accion", "Obtener");
        formData.set("id", id);

        Swal.fire({
            title: "Cargando producto...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const respuesta = await fetch("registrar.php", {
            method: "POST",
            body: formData
        });

        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const data = await respuesta.json();

        Swal.close();

        if (!data.success) {

            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message || "No se pudo obtener el producto."
            });

            return;
        }

        const producto = data.producto;

        document.getElementById("id").value = producto.id;
        document.getElementById("codigo").value = producto.codigo;
        document.getElementById("producto").value = producto.producto;
        document.getElementById("precio").value = producto.precio;
        document.getElementById("cantidad").value = producto.cantidad;

        // En edición se permite cantidad 0 porque el producto puede estar agotado.
        document.getElementById("cantidad").min = "0";

        const btnGuardar = document.getElementById("btnGuardar");
        const btnCancelar = document.getElementById("btnCancelar");

        btnGuardar.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar';
        btnGuardar.classList.remove("btn-primary");
        btnGuardar.classList.add("btn-warning");

        btnCancelar.classList.remove("d-none");

        document.getElementById("formProducto").scrollIntoView({
            behavior: "smooth"
        });

    } catch (error) {

        console.error("Error al cargar edición:", error);

        Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo consultar el producto. Verifica que Apache y MySQL estén activos en XAMPP."
        });

    }

}

/**
 * Limpia el formulario y regresa al modo Registrar.
 */
function limpiarFormulario() {

    const formProducto = document.getElementById("formProducto");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelar");

    formProducto.reset();

    document.getElementById("id").value = "";

    // En registro nuevo exigimos mínimo 1 unidad.
    document.getElementById("cantidad").min = "1";

    btnGuardar.innerHTML = '<i class="bi bi-save"></i> Registrar';
    btnGuardar.classList.remove("btn-warning");
    btnGuardar.classList.add("btn-primary");

    btnCancelar.classList.add("d-none");

}

/**
 * Valida los datos antes de enviarlos al servidor.
 */
function validarFormulario(accion) {

    const codigo = document.getElementById("codigo").value.trim();
    const producto = document.getElementById("producto").value.trim();
    const precioTexto = document.getElementById("precio").value.trim();
    const cantidadTexto = document.getElementById("cantidad").value.trim();

    const precio = Number(precioTexto);
    const cantidad = Number(cantidadTexto);

    const errores = [];

    if (codigo === "") {
        errores.push("El código es obligatorio.");
    }

    if (codigo.length > 20) {
        errores.push("El código no puede tener más de 20 caracteres.");
    }

    if (producto === "") {
        errores.push("El nombre del producto es obligatorio.");
    }

    if (producto.length > 100) {
        errores.push("El nombre del producto no puede tener más de 100 caracteres.");
    }

    if (precioTexto === "" || !Number.isFinite(precio) || precio <= 0) {
        errores.push("El precio debe ser mayor que cero.");
    }

    if (cantidadTexto === "" || !Number.isInteger(cantidad)) {
        errores.push("La cantidad debe ser un número entero.");
    }

    if (accion === "Guardar" && cantidad < 1) {
        errores.push("Al registrar un producto debe existir al menos una unidad.");
    }

    if (accion === "Modificar" && cantidad < 0) {
        errores.push("La cantidad no puede ser negativa.");
    }

    if (errores.length > 0) {

        Swal.fire({
            icon: "warning",
            title: "Revisa el formulario",
            html: crearListaErrores(errores)
        });

        return false;

    }

    return true;

}

/**
 * Confirma la eliminación antes de enviar la solicitud.
 */
function confirmarEliminar(id) {

    Swal.fire({
        icon: "warning",
        title: "¿Eliminar producto?",
        text: "Esta acción no se puede deshacer.",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#b91c1c",
        cancelButtonColor: "#6b7280"
    }).then((resultado) => {

        if (resultado.isConfirmed) {

            const formData = new FormData();

            formData.set("Accion", "Eliminar");
            formData.set("id", id);

            enviarFetch(formData, "Eliminar");

        }

    });

}

/**
 * Muestra errores enviados por el servidor.
 */
function mostrarErrores(data) {

    let html = escapeHTML(data.message || "Ocurrió un error.");

    if (data.errors && data.errors.length > 0) {
        html += crearListaErrores(data.errors);
    }

    Swal.fire({
        icon: "error",
        title: "Error",
        html: html
    });

}

/**
 * Convierte un arreglo de errores en una lista HTML.
 */
function crearListaErrores(errores) {

    return `
        <ul class="text-start mt-2">
            ${errores.map((error) => `<li>${escapeHTML(error)}</li>`).join("")}
        </ul>
    `;

}

/**
 * Evita insertar HTML directamente en la tabla o alertas.
 */
function escapeHTML(texto) {

    const div = document.createElement("div");

    div.textContent = String(texto ?? "");

    return div.innerHTML;

}