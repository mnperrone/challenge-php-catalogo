/**
 * Catálogo Pro - Lógica de la Aplicación (SPA)
 * Integración con la API REST de Catálogo de Productos
 */

document.addEventListener('DOMContentLoaded', () => {
    // URL base de la API (relativa, ya que compartimos el puerto/contenedor)
    const API_URL = '/productos';

    // Estado local de la aplicación
    let state = {
        productos: [],
        busqueda: '',
        editandoId: null,
        tipoCambioCalculado: null,
        eliminandoId: null
    };

    // Referencias al DOM
    const form = document.getElementById('product-form');
    const inputId = document.getElementById('product-id');
    const inputNombre = document.getElementById('product-nombre');
    const inputDescripcion = document.getElementById('product-descripcion');
    const inputPrecio = document.getElementById('product-precio');
    const btnSubmit = document.getElementById('form-submit-btn');
    const btnCancel = document.getElementById('form-cancel-btn');
    const formTitle = document.getElementById('form-title');
    const formSubtitle = document.getElementById('form-subtitle');

    const tbody = document.getElementById('products-tbody');
    const searchInput = document.getElementById('search-input');
    const statsCount = document.getElementById('stats-count');
    const statsExchange = document.getElementById('stats-exchange');
    const toastContainer = document.getElementById('toast-container');

    // Referencias del Modal de Confirmación
    const confirmModal = document.getElementById('confirm-modal');
    const modalProductName = document.getElementById('modal-product-name');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const modalConfirmBtn = document.getElementById('modal-confirm-btn');

    // Inicialización
    init();

    function init() {
        obtenerProductos();
        registrarEventos();
    }

    // --- EVENTOS ---
    function registrarEventos() {
        // Enviar formulario (Crear o Editar)
        form.addEventListener('submit', manejarSubmitFormulario);

        // Cancelar edición
        btnCancel.addEventListener('click', salirModoEdicion);

        // Búsqueda en tiempo real
        searchInput.addEventListener('input', manejarBusqueda);

        // Validar en tiempo real al escribir o perder el foco
        inputNombre.addEventListener('input', () => validarCampo(inputNombre, 'error-nombre', 'El nombre es obligatorio.'));
        inputPrecio.addEventListener('input', () => validarPrecio(inputPrecio));

        // Cerrar modal de confirmación
        modalCancelBtn.addEventListener('click', cerrarModalEliminacion);
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                cerrarModalEliminacion();
            }
        });

        // Confirmar eliminación desde el modal
        modalConfirmBtn.addEventListener('click', ejecutarEliminacionConfirmada);
    }

    // --- ACCIONES DE API ---

    // GET /productos - Obtener listado completo
    async function obtenerProductos() {
        mostrarCargandoTabla();
        try {
            const respuesta = await fetch(API_URL);
            if (!respuesta.ok) {
                throw new Error('Error al obtener los productos.');
            }
            const dataResponse = await respuesta.json();
            state.productos = dataResponse.data || [];
            
            calcularTipoCambio();
            actualizarUI();
        } catch (error) {
            mostrarToast(error.message || 'Error de conexión con la API', 'error');
            mostrarEstadoVacioTabla('Error de carga. Intenta recargar la página.');
        }
    }

    // POST /productos - Guardar producto nuevo
    async function crearProducto(producto) {
        setLoaderBoton(true);
        try {
            const respuesta = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(producto)
            });

            const dataResponse = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(dataResponse.error || 'No se pudo crear el producto.');
            }

            state.productos.unshift(dataResponse.data); // Añadir al inicio para visualización rápida
            calcularTipoCambio();
            actualizarUI();
            limpiarFormulario();
            mostrarToast('Producto creado con éxito.', 'success');
        } catch (error) {
            mostrarToast(error.message, 'error');
        } finally {
            setLoaderBoton(false);
        }
    }

    // PUT /productos/{id} - Actualizar producto existente
    async function actualizarProducto(id, producto) {
        setLoaderBoton(true);
        try {
            const respuesta = await fetch(`${API_URL}/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(producto)
            });

            const dataResponse = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(dataResponse.error || 'No se pudo actualizar el producto.');
            }

            // Actualizar en el estado local
            const index = state.productos.findIndex(p => p.id === id);
            if (index !== -1) {
                state.productos[index] = dataResponse.data;
            }

            calcularTipoCambio();
            actualizarUI();
            salirModoEdicion();
            mostrarToast('Producto actualizado con éxito.', 'success');
        } catch (error) {
            mostrarToast(error.message, 'error');
        } finally {
            setLoaderBoton(false);
        }
    }

    // DELETE /productos/{id} - Eliminar producto
    async function eliminarProducto(id) {
        console.log('Ejecutando fetch DELETE para producto ID:', id);
        try {
            const respuesta = await fetch(`${API_URL}/${id}`, {
                method: 'DELETE'
            });

            if (!respuesta.ok) {
                const dataResponse = await respuesta.json().catch(() => ({}));
                throw new Error(dataResponse.error || 'No se pudo eliminar el producto.');
            }

            // Remover del estado local
            state.productos = state.productos.filter(p => p.id !== id);
            
            // Si eliminamos el producto que se estaba editando, salimos de edición
            if (state.editandoId === id) {
                salirModoEdicion();
            }

            calcularTipoCambio();
            actualizarUI();
            mostrarToast('Producto eliminado con éxito.', 'success');
        } catch (error) {
            mostrarToast(error.message, 'error');
        }
    }

    // --- FUNCIONES DEL MODAL DE CONFIRMACIÓN ---

    function abrirModalEliminacion(producto) {
        state.eliminandoId = producto.id;
        modalProductName.textContent = producto.nombre;
        confirmModal.classList.remove('hidden');
    }

    function cerrarModalEliminacion() {
        state.eliminandoId = null;
        confirmModal.classList.add('hidden');
    }

    async function ejecutarEliminacionConfirmada() {
        if (state.eliminandoId) {
            const id = state.eliminandoId;
            cerrarModalEliminacion();
            await eliminarProducto(id);
        }
    }

    // --- MANEJADORES DE INTERFAZ ---

    function manejarSubmitFormulario(e) {
        e.preventDefault();

        const nombreValido = validarCampo(inputNombre, 'error-nombre', 'El nombre es obligatorio.');
        const precioValido = validarPrecio(inputPrecio);

        if (!nombreValido || !precioValido) {
            return;
        }

        const producto = {
            nombre: inputNombre.value.trim(),
            descripcion: inputDescripcion.value.trim() || null,
            precio: parseFloat(inputPrecio.value)
        };

        if (state.editandoId) {
            actualizarProducto(state.editandoId, producto);
        } else {
            crearProducto(producto);
        }
    }

    function manejarBusqueda(e) {
        state.busqueda = e.target.value.toLowerCase().trim();
        renderizarTabla();
    }

    function entrarModoEdicion(producto) {
        state.editandoId = producto.id;
        inputId.value = producto.id;
        inputNombre.value = producto.nombre;
        inputDescripcion.value = producto.descripcion || '';
        inputPrecio.value = producto.precio;

        // Limpiar errores previos
        limpiarErroresFormulario();

        // Actualizar textos e interfaz
        formTitle.textContent = 'Editar Producto';
        formSubtitle.textContent = `Modificando: ${producto.nombre}`;
        btnSubmit.querySelector('span').textContent = 'Guardar Cambios';
        btnCancel.classList.remove('hidden');

        // Scroll suave al formulario
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function salirModoEdicion() {
        state.editandoId = null;
        limpiarFormulario();

        formTitle.textContent = 'Agregar Producto';
        formSubtitle.textContent = 'Completa los campos para añadir un nuevo producto al catálogo.';
        btnSubmit.querySelector('span').textContent = 'Guardar Producto';
        btnCancel.classList.add('hidden');
    }

    // --- VALIDACIONES DE FORMULARIO CLIENT-SIDE ---

    function validarCampo(input, errorId, mensaje) {
        const errorSpan = document.getElementById(errorId);
        if (input.value.trim() === '') {
            errorSpan.textContent = mensaje;
            input.classList.add('invalid');
            return false;
        }
        errorSpan.textContent = '';
        input.classList.remove('invalid');
        return true;
    }

    function validarPrecio(input) {
        const errorSpan = document.getElementById('error-precio');
        const valor = input.value.trim();

        if (valor === '') {
            errorSpan.textContent = 'El precio es obligatorio.';
            input.classList.add('invalid');
            return false;
        }

        const numero = parseFloat(valor);
        if (isNaN(numero)) {
            errorSpan.textContent = 'El precio debe ser un número válido.';
            input.classList.add('invalid');
            return false;
        }

        if (numero < 0) {
            errorSpan.textContent = 'El precio no puede ser negativo.';
            input.classList.add('invalid');
            return false;
        }

        errorSpan.textContent = '';
        input.classList.remove('invalid');
        return true;
    }

    function limpiarErroresFormulario() {
        document.querySelectorAll('.error-message').forEach(span => span.textContent = '');
        document.querySelectorAll('input, textarea').forEach(el => el.classList.remove('invalid'));
    }

    function limpiarFormulario() {
        form.reset();
        inputId.value = '';
        limpiarErroresFormulario();
    }

    // --- AUXILIARES Y RENDER ---

    function calcularTipoCambio() {
        // Encontrar cualquier producto para calcular la tasa (precio_ars / precio_usd)
        if (state.productos.length > 0) {
            const primerProducto = state.productos.find(p => p.precio > 0 && p.precio_usd > 0);
            if (primerProducto) {
                state.tipoCambioCalculado = primerProducto.precio / primerProducto.precio_usd;
            }
        } else {
            state.tipoCambioCalculado = null;
        }
    }

    function actualizarUI() {
        renderizarTabla();
        renderizarEstadisticas();
    }

    function renderizarEstadisticas() {
        statsCount.textContent = state.productos.length;
        
        if (state.tipoCambioCalculado) {
            statsExchange.textContent = formatearMoneda(state.tipoCambioCalculado, 'ARS') + ' / USD';
        } else {
            statsExchange.textContent = 'Pendiente';
        }
    }

    function renderizarTabla() {
        const productosFiltrados = state.productos.filter(producto => {
            const nombreMatches = producto.nombre.toLowerCase().includes(state.busqueda);
            const descMatches = (producto.descripcion || '').toLowerCase().includes(state.busqueda);
            return nombreMatches || descMatches;
        });

        if (productosFiltrados.length === 0) {
            if (state.busqueda !== '') {
                mostrarEstadoVacioTabla('No se encontraron productos coincidentes.');
            } else {
                mostrarEstadoVacioTabla('No hay productos en el catálogo. ¡Comienza agregando uno!');
            }
            return;
        }

        tbody.innerHTML = '';
        productosFiltrados.forEach(producto => {
            const tr = document.createElement('tr');
            
            // Celda Nombre
            const tdNombre = document.createElement('td');
            tdNombre.className = 'product-name-cell';
            tdNombre.textContent = producto.nombre;
            tr.appendChild(tdNombre);

            // Celda Descripción
            const tdDesc = document.createElement('td');
            if (producto.descripcion) {
                tdDesc.className = 'product-desc-cell';
                tdDesc.textContent = producto.descripcion;
                tdDesc.title = producto.descripcion; // Tooltip con texto completo
            } else {
                const spanEmpty = document.createElement('span');
                spanEmpty.className = 'product-desc-empty';
                spanEmpty.textContent = 'Sin descripción';
                tdDesc.appendChild(spanEmpty);
            }
            tr.appendChild(tdDesc);

            // Celda Precio ARS
            const tdPrecioArs = document.createElement('td');
            tdPrecioArs.className = 'text-right';
            const spanArs = document.createElement('span');
            spanArs.className = 'price-badge price-badge-ars';
            spanArs.textContent = formatearMoneda(producto.precio, 'ARS');
            tdPrecioArs.appendChild(spanArs);
            tr.appendChild(tdPrecioArs);

            // Celda Precio USD
            const tdPrecioUsd = document.createElement('td');
            tdPrecioUsd.className = 'text-right';
            const spanUsd = document.createElement('span');
            spanUsd.className = 'price-badge price-badge-usd';
            spanUsd.textContent = formatearMoneda(producto.precio_usd, 'USD');
            tdPrecioUsd.appendChild(spanUsd);
            tr.appendChild(tdPrecioUsd);

            // Celda Acciones
            const tdAcciones = document.createElement('td');
            tdAcciones.className = 'text-center';
            
            const divActions = document.createElement('div');
            divActions.className = 'action-buttons';

            // Botón Editar
            const btnEdit = document.createElement('button');
            btnEdit.className = 'action-btn action-btn-edit';
            btnEdit.title = 'Editar';
            btnEdit.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`;
            btnEdit.addEventListener('click', () => entrarModoEdicion(producto));
            divActions.appendChild(btnEdit);

            // Botón Eliminar
            const btnDel = document.createElement('button');
            btnDel.className = 'action-btn action-btn-delete';
            btnDel.title = 'Eliminar';
            btnDel.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>`;
            btnDel.addEventListener('click', (e) => {
                e.stopPropagation();
                abrirModalEliminacion(producto);
            });
            divActions.appendChild(btnDel);

            tdAcciones.appendChild(divActions);
            tr.appendChild(tdAcciones);

            tbody.appendChild(tr);
        });
    }

    function mostrarCargandoTabla() {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center table-loader-cell">
                    <div class="loader-wrapper">
                        <div class="spinner"></div>
                        <p>Cargando productos...</p>
                    </div>
                </td>
            </tr>
        `;
    }

    function mostrarEstadoVacioTabla(mensaje) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <p>${mensaje}</p>
                    </div>
                </td>
            </tr>
        `;
    }

    function setLoaderBoton(cargando) {
        const spinner = btnSubmit.querySelector('.btn-spinner');
        const spanTexto = btnSubmit.querySelector('span');

        if (cargando) {
            btnSubmit.disabled = true;
            spinner.classList.remove('hidden');
            spanTexto.textContent = state.editandoId ? 'Guardando...' : 'Creando...';
        } else {
            btnSubmit.disabled = false;
            spinner.classList.add('hidden');
            spanTexto.textContent = state.editandoId ? 'Guardar Cambios' : 'Guardar Producto';
        }
    }

    function formatearMoneda(valor, divisa) {
        const opciones = {
            style: 'currency',
            currency: divisa === 'ARS' ? 'ARS' : 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        };
        
        const formateador = new Intl.NumberFormat('es-AR', opciones);
        let resultado = formateador.format(valor);
        
        // Ajustar formato del símbolo para que quede legible y premium
        if (divisa === 'USD') {
            resultado = resultado.replace('USD', '').trim() + ' USD';
        }
        return resultado;
    }

    // --- SISTEMA DE TOASTS NOTIFICATION ---

    function mostrarToast(mensaje, tipo = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;

        const iconSvg = tipo === 'success' 
            ? `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`
            : `<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

        toast.innerHTML = `
            ${iconSvg}
            <span class="toast-message">${mensaje}</span>
        `;

        toastContainer.appendChild(toast);

        // Desaparecer después de 5 segundos
        setTimeout(() => {
            toast.classList.add('toast-fade-out');
            // Asegurar la remoción del DOM después de que termine la animación
            setTimeout(() => {
                toast.remove();
            }, 500);
        }, 5000);
    }
});
