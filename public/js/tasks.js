// Obtenemos el nodo de arranque que contiene los datos iniciales enviados por el servidor.
const boot = document.getElementById('boot'); // Nodo con data-attributes necesarios para iniciar.

// Leemos el id de usuario y filtros iniciales desde los atributos del nodo boot.
const userId = parseInt(boot.dataset.userId || '0', 10); // Convertimos el id a entero para validación.
const initialFrom = boot.dataset.initialFrom || '';       // Fecha 'desde' inicial si vino en la URL.
const initialTo = boot.dataset.initialTo || '';           // Fecha 'hasta' inicial si vino en la URL.
const initialPid = boot.dataset.initialPid || '';         // Id de proyecto inicial si vino en la URL.

// Encontramos referencias a los elementos de la interfaz que vamos a controlar.
const inputFrom = document.getElementById('from');        // Input de fecha 'desde'.
const inputTo = document.getElementById('to');            // Input de fecha 'hasta'.
const selectProject = document.getElementById('projectId'); // Select de proyecto para filtrar.
const btnApply = document.getElementById('btnApply');     // Botón para aplicar filtros.
const btnReset = document.getElementById('btnReset');     // Botón para limpiar filtros.
const btnPrev = document.getElementById('btnPrev');       // Botón de página anterior.
const btnNext = document.getElementById('btnNext');       // Botón de página siguiente.
const pageInfo = document.getElementById('pageInfo');     // Span para mostrar la página actual.
const rows = document.getElementById('rows');             // Cuerpo de la tabla donde se agregarán filas.
const statusBox = document.getElementById('status');      // Div para mostrar errores o avisos.

// Definimos el estado de la vista con página y límite por defecto.
let page = 1;                                            // Página actual iniciando en 1.
let limit = 10;                                          // Cantidad de filas por página a mostrar.

// Prellenamos los inputs de fecha con los valores iniciales si existen.
inputFrom.value = initialFrom;                           // Si había 'from' en URL, lo vemos reflejado.
inputTo.value = initialTo;                               // Si había 'to' en URL, lo vemos reflejado.

// Si teníamos un project_id inicial, lo guardamos para seleccionarlo luego.
let pendingInitialPid = initialPid;                      // Variable temporal para seleccionar el proyecto luego.

// Función auxiliar para construir la URL del endpoint con filtros y paginación.
function buildUrl() {
    // Creamos un objeto URL con la ruta base a la API del backend.
    const url = new URL(`/api/users/${userId}/tasks`, window.location.origin); // URL relativa a la app local.
    // Agregamos parámetros de query sólo si tienen valor, así mantenemos la URL limpia.
    if (inputFrom.value) url.searchParams.set('from', inputFrom.value);       // Filtro 'from' si está presente.
    if (inputTo.value) url.searchParams.set('to', inputTo.value);             // Filtro 'to' si está presente.
    if (selectProject.value) url.searchParams.set('project_id', selectProject.value); // Filtro de proyecto si aplica.
    // Agregamos paginación actual.
    url.searchParams.set('page', String(page));                               // Página actual como string.
    url.searchParams.set('limit', String(limit));                             // Límite por página como string.
    // Devolvemos la URL completa lista para usar con fetch.
    return url.toString();                                                    // Cadena completa de la URL final.
}

// Función para formatear números a 2 decimales de forma consistente.
function money(num) {
    // Usamos toFixed(2) para mostrar siempre dos decimales en tarifas e importes.
    return Number(num).toFixed(2);                                            // Convierte y fija a dos decimales.
}

// Función principal que trae datos desde la API y actualiza la tabla.
async function load() {
    try {
        // Limpiamos el estado y avisos antes de empezar la carga.
        statusBox.textContent = '';                                             // Quitamos mensajes previos.
        rows.innerHTML = '';                                                    // Vaciamos la tabla para repintar.

        // Construimos la URL final con los filtros actuales.
        const url = buildUrl();                                                 // Obtenemos la URL lista para fetch.

        // Hacemos la petición GET al endpoint de la API.
        const res = await fetch(url);                                           // Llamada a la API con fetch nativo.
        // Si la respuesta no es 200-299, mostramos el error y salimos.
        if (!res.ok) {                                                          // Validamos el código de estado.
            const errText = await res.text();                                     // Leemos el cuerpo para mostrar algo útil.
            statusBox.textContent = `Error ${res.status}: ${errText}`;            // Mostramos el error en pantalla.
            return;                                                               // No seguimos si la API respondió con error.
        }

        // Parseamos el JSON que envía el backend con user, meta e items.
        const data = await res.json();                                          // Convertimos la respuesta a objeto.
        // Extraemos partes útiles para la vista.
        const items = data.items || [];                                         // Arreglo de tareas o vacío.
        const meta = data.meta || {};                                           // Información de paginación y filtros.

        // Si no hay items, indicamos que no hubo resultados.
        if (items.length === 0) {                                               // Verificamos si la lista vino vacía.
            rows.innerHTML = `<tr><td colspan="6">Sin resultados para los filtros aplicados.</td></tr>`;     // Mostramos una fila con mensaje.
        } else {
            // Si hay items, pintamos cada uno como una fila de la tabla.
            for (const it of items) {                                             // Recorremos cada tarea.
                // Creamos una fila de tabla para insertar en el DOM.
                const tr = document.createElement('tr');                            // Nueva fila de tabla.
                // Construimos las celdas con los campos relevantes.
                tr.innerHTML = `
          <td>${it.date}</td>
          <td>${it.task_title}</td>
          <td>${it.project_name}</td>
          <td>${it.duration_minutes}</td>
          <td>${money(it.applied_rate)}</td>
          <td>${money(it.amount)}</td>
        `;
                // Añadimos la fila ya completa al cuerpo de la tabla.
                rows.appendChild(tr);                                               // Insertamos la fila en la tabla.
            }
        }

        // Actualizamos el indicador de página usando los valores calculados en el backend.
        const currentPage = meta.page || page;                                  // Tomamos la página actual.
        const totalPages = meta.total_pages || 1;                                // Tomamos el total de páginas.
        pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;        // Rendereamos la info de paginación.

        // Habilitamos o deshabilitamos los botones según dónde estemos.
        btnPrev.disabled = currentPage <= 1;                                    // Deshabilitamos 'Anterior' en página 1.
        btnNext.disabled = currentPage >= totalPages;                           // Deshabilitamos 'Siguiente' en última.

        // Si es la primera carga, y aún no se llenó el select de proyectos, lo llenamos.
        if (selectProject.options.length <= 1) {                                 // Revisamos si solo está "Todos".
            // Obtenemos nombres únicos de proyectos a partir de los items cargados.
            const seen = new Map();                                               // Mapa para evitar duplicados.
            for (const it of items) {                                             // Recorremos items actuales.
                if (!seen.has(it.project_id)) {                                     // Si no hemos agregado el proyecto aún...
                    seen.set(it.project_id, it.project_name);                         // Guardamos el par id-nombre.
                }
            }
            // Creamos las opciones en el select usando lo visto.
            for (const [pid, name] of seen.entries()) {                           // Iteramos proyectos recolectados.
                const opt = document.createElement('option');                       // Nueva opción del select.
                opt.value = String(pid);                                            // Asignamos el valor con el id del proyecto.
                opt.textContent = name;                                             // Texto visible con el nombre del proyecto.
                selectProject.appendChild(opt);                                     // Insertamos la opción en el select.
            }
            // Si venía un project_id inicial, intentamos seleccionarlo ahora.
            if (pendingInitialPid) {                                              // Verificamos si había un pid pendiente.
                selectProject.value = pendingInitialPid;                            // Seleccionamos el valor indicado.
                pendingInitialPid = '';                                             // Limpiamos la variable temporal.
            }
        }
    } catch (e) {
        // Si ocurrió un error inesperado de red o parseo, lo mostramos en pantalla.
        statusBox.textContent = `Error inesperado: ${e}`;                       // Mostramos mensaje de error genérico.
    }
}

// Manejador del botón "Aplicar filtros" para recargar desde la página 1.
btnApply.addEventListener('click', () => {
    // Siempre que aplicamos filtros, reseteamos la paginación a la primera página.
    page = 1;                                                                 // Reset de página.
    // Cargamos con los nuevos valores de filtros que estén en los inputs actuales.
    load();                                                                    // Ejecutamos la carga nuevamente.
});

// Manejador del botón "Reset" para limpiar filtros y volver a primera página.
btnReset.addEventListener('click', () => {
    // Limpiamos el contenido de los inputs y del select.
    inputFrom.value = '';                                                     // Quitamos 'from'.
    inputTo.value = '';                                                       // Quitamos 'to'.
    selectProject.value = '';                                                 // Seleccionamos "Todos".
    // Regresamos a la primera página antes de cargar.
    page = 1;                                                                 // Reset de página.
    // Ejecutamos una nueva carga con los filtros limpios.
    load();                                                                    // Recargamos la tabla.
});

// Manejadores de paginación para retroceder o avanzar páginas.
btnPrev.addEventListener('click', () => {
    // Si ya estamos en la primera, no hacemos nada.
    if (page <= 1) return;                                                    // Protección mínima.
    // Reducimos la página en uno y recargamos.
    page -= 1;                                                                // Vamos a la página anterior.
    load();                                                                    // Recargamos los datos.
});

btnNext.addEventListener('click', () => {
    // Avanzamos una página y recargamos; el botón se desactiva si no hay más páginas.
    page += 1;                                                                // Incrementamos página.
    load();                                                                    // Volvemos a pedir datos a la API.
});

// Lanzamos la primera carga automáticamente al abrir la página.
load();                                                                      // Primera ejecución al cargar el script.
