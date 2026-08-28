<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

import {
    Head,
    Link,
    usePage,
    useForm
} from '@inertiajs/vue3';

import {
    FolderOpen,
    Plus,
    Search,
    FileText,
    Edit2,
    CheckCircle2,
    Archive,
    Eye,
    Download,
    X,
    RefreshCw
} from 'lucide-vue-next';

import {
    computed,
    ref
} from 'vue';

const props = defineProps({
    expedientes: {
        type: Array,
        default: () => []
    },

    asesores: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

const usuario = computed(() =>
    page.props.auth.usuario
);

const rol = computed(() =>
    usuario.value?.rol
);

const isSecretario = computed(() =>
    rol.value === 'secretario'
);

const puedeExportar = computed(() =>
    ['administrador', 'secretario'].includes(rol.value)
);

const puedeVerDetalle = computed(() =>
    ['administrador', 'secretario', 'asesor', 'practicante'].includes(rol.value)
);

const buscar = ref('');
const filtroTipo = ref('');
const filtroEstado = ref('');
const filtroAsesor = ref('');

const expedientesFiltrados = computed(() => {
    return props.expedientes.filter((exp) => {
        const texto = buscar.value
            .toLowerCase()
            .trim();

        const coincideBusqueda =
            !texto ||
            exp.numero_expediente
                ?.toLowerCase()
                .includes(texto) ||
            exp.cliente
                ?.toLowerCase()
                .includes(texto);

        const coincideTipo =
            !filtroTipo.value ||
            exp.tipo_proceso === filtroTipo.value;

        const coincideEstado =
            !filtroEstado.value ||
            exp.estado === filtroEstado.value;

        const coincideAsesor =
            !filtroAsesor.value ||
            String(exp.asesor_id) ===
            String(filtroAsesor.value);

        return (
            coincideBusqueda &&
            coincideTipo &&
            coincideEstado &&
            coincideAsesor
        );
    });
});

const limpiarFiltros = () => {
    buscar.value = '';
    filtroTipo.value = '';
    filtroEstado.value = '';
    filtroAsesor.value = '';
};

const mostrarEstadoModal = ref(false);
const expedienteSeleccionado = ref(null);

const estadoForm = useForm({
    estado: ''
});

const abrirEstadoModal = (expediente) => {
    expedienteSeleccionado.value = expediente;

    estadoForm.estado =
        expediente.estado || 'Abierto';

    mostrarEstadoModal.value = true;
};

const cerrarEstadoModal = () => {
    mostrarEstadoModal.value = false;
    expedienteSeleccionado.value = null;

    estadoForm.reset();
    estadoForm.clearErrors();
};

const cambiarEstado = () => {
    if (!expedienteSeleccionado.value) {
        return;
    }

    estadoForm.put(
        `/expedientes/${expedienteSeleccionado.value.id}/estado`,
        {
            preserveScroll: true,

            onSuccess: () => {
                cerrarEstadoModal();
            }
        }
    );
};

const mostrarArchivoModal = ref(false);
const expedienteParaArchivar = ref(null);

const archivarForm = useForm({});

const abrirArchivoModal = (expediente) => {
    expedienteParaArchivar.value = expediente;
    mostrarArchivoModal.value = true;
};

const cerrarArchivoModal = () => {
    mostrarArchivoModal.value = false;
    expedienteParaArchivar.value = null;

    archivarForm.reset();
    archivarForm.clearErrors();
};

const archivarExpediente = () => {
    if (!expedienteParaArchivar.value) {
        return;
    }

    archivarForm.put(
        `/expedientes/${expedienteParaArchivar.value.id}/archivar`,
        {
            preserveScroll: true,

            onSuccess: () => {
                cerrarArchivoModal();
            }
        }
    );
};

const formatDate = (dateString) => {
    if (!dateString) {
        return 'N/A';
    }

    const d = new Date(dateString);

    return d.toLocaleDateString(
        'es-ES',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }
    );
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'Abierto':
            return 'bg-sky-100 text-sky-700';

        case 'En Proceso':
            return 'bg-amber-100 text-amber-700';

        case 'Resuelto':
            return 'bg-emerald-100 text-emerald-700';

        case 'Cerrado':
            return 'bg-slate-100 text-slate-700';

        case 'Archivado':
            return 'bg-rose-100 text-rose-700';

        default:
            return 'bg-sky-100 text-sky-700';
    }
};

const exportarExcel = () => {
    const params = new URLSearchParams();

    if (buscar.value.trim()) {
        params.append(
            'buscar',
            buscar.value.trim()
        );
    }

    if (filtroTipo.value) {
        params.append(
            'tipo_proceso',
            filtroTipo.value
        );
    }

    if (filtroEstado.value) {
        params.append(
            'estado',
            filtroEstado.value
        );
    }

    if (filtroAsesor.value) {
        params.append(
            'asesor_id',
            filtroAsesor.value
        );
    }

    const query = params.toString();

    window.location.href =
        '/expedientes/exportar/excel' +
        (query ? '?' + query : '');
};
</script>

<template>
    <Head title="Gestión de Expedientes" />

    <AppLayout>
        <div class="db">

            <div class="db__header">
                <div>
                    <h1 class="db__titulo">
                        Gestión de Expedientes
                    </h1>

                    <p class="db__sub">
                        Administra y consulta los procesos jurídicos activos
                    </p>
                </div>

                <div class="db__accesos">

                    <button
                        v-if="puedeExportar"
                        type="button"
                        class="btn btn-excel"
                        @click="exportarExcel"
                    >
                        <Download class="btn__ico" />
                        Exportar a Excel
                    </button>

                    <Link
                        v-if="isSecretario"
                        href="/expedientes/create"
                        class="btn"
                    >
                        <Plus class="btn__ico" />
                        Nuevo Expediente
                    </Link>

                </div>
            </div>

            <div
                v-if="page.props.flash && page.props.flash.exito"
                class="alerta alerta--exito"
            >
                <CheckCircle2 class="alerta__ico" />

                <span>
                    {{ page.props.flash.exito }}
                </span>
            </div>

            <div class="panel filtros-panel">

                <div class="filtros-header">
                    <div>
                        <h2 class="panel__titulo">
                            Buscar y filtrar expedientes
                        </h2>

                        <p class="filtros-sub">
                            Filtra por número, cliente, tipo, estado o asesor.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-limpiar"
                        @click="limpiarFiltros"
                    >
                        <X />
                        Limpiar filtros
                    </button>
                </div>

                <div class="filtros">

                    <div class="filtro filtro-busqueda">
                        <label>
                            Buscar
                        </label>

                        <div class="input-wrapper">
                            <Search />

                            <input
                                v-model="buscar"
                                type="text"
                                placeholder="Número o cliente..."
                            />
                        </div>
                    </div>

                    <div class="filtro">
                        <label>
                            Tipo de proceso
                        </label>

                        <select v-model="filtroTipo">
                            <option value="">
                                Todos
                            </option>

                            <option value="Civil">
                                Civil
                            </option>

                            <option value="Penal">
                                Penal
                            </option>

                            <option value="Laboral">
                                Laboral
                            </option>

                            <option value="Familia">
                                Familia
                            </option>

                            <option value="Administrativo">
                                Administrativo
                            </option>
                        </select>
                    </div>

                    <div class="filtro">
                        <label>
                            Estado
                        </label>

                        <select v-model="filtroEstado">
                            <option value="">
                                Todos
                            </option>

                            <option value="Abierto">
                                Abierto
                            </option>

                            <option value="En Proceso">
                                En Proceso
                            </option>

                            <option value="Resuelto">
                                Resuelto
                            </option>

                            <option value="Cerrado">
                                Cerrado
                            </option>

                            <option value="Archivado">
                                Archivado
                            </option>
                        </select>
                    </div>

                    <div class="filtro">
                        <label>
                            Asesor
                        </label>

                        <select v-model="filtroAsesor">
                            <option value="">
                                Todos
                            </option>

                            <option
                                v-for="asesor in asesores"
                                :key="asesor.id"
                                :value="asesor.id"
                            >
                                {{ asesor.nombre }}
                                {{ asesor.apellido }}
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="panel">

                <div class="panel__head">
                    <div>
                        <h2 class="panel__titulo">
                            Expedientes Registrados
                        </h2>

                        <p class="resultados">
                            Mostrando
                            {{ expedientesFiltrados.length }}
                            de
                            {{ expedientes.length }}
                            expedientes
                        </p>
                    </div>
                </div>

                <div
                    v-if="expedientesFiltrados.length > 0"
                    class="table-container"
                >
                    <table class="table">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Tipo de Proceso</th>
                                <th>Estado</th>
                                <th>Asesor Asignado</th>
                                <th>Última Modificación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr
                                v-for="exp in expedientesFiltrados"
                                :key="exp.id"
                            >

                                <td>
                                    <div class="flex items-center gap-2">

                                        <FileText
                                            class="w-4 h-4 text-slate-400"
                                        />

                                        <Link
                                            v-if="puedeVerDetalle"
                                            :href="`/expedientes/${exp.id}`"
                                            class="numero-expediente"
                                        >
                                            {{ exp.numero_expediente }}
                                        </Link>

                                        <span
                                            v-else
                                            class="font-semibold text-slate-800"
                                        >
                                            {{ exp.numero_expediente }}
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    {{ exp.cliente }}
                                </td>

                                <td>
                                    <span
                                        class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-semibold uppercase tracking-wide"
                                    >
                                        {{ exp.tipo_proceso }}
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="estado-badge"
                                        :class="estadoClass(exp.estado)"
                                    >
                                        {{ exp.estado || 'Abierto' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="flex flex-col">

                                        <span
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                exp.asesor
                                                    ? exp.asesor.nombre + ' ' + exp.asesor.apellido
                                                    : 'Sin asignar'
                                            }}
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    <div class="flex flex-col">

                                        <span
                                            class="text-sm text-slate-600 font-semibold"
                                        >
                                            {{ formatDate(exp.updated_at) }}
                                        </span>

                                        <span class="text-xs text-slate-500">
                                            Por:
                                            {{
                                                exp.modificador
                                                    ? exp.modificador.nombre
                                                    : (
                                                        exp.creador
                                                            ? exp.creador.nombre
                                                            : 'Sistema'
                                                    )
                                            }}
                                        </span>

                                    </div>
                                </td>

                                <td>

                                    <div class="acciones">

                                        <Link
                                            v-if="puedeVerDetalle"
                                            :href="`/expedientes/${exp.id}`"
                                            class="action-btn action-btn--view"
                                            title="Ver detalle"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>

                                        <Link
                                            v-if="isSecretario && exp.estado !== 'Archivado'"
                                            :href="`/expedientes/${exp.id}/edit`"
                                            class="action-btn"
                                            title="Editar Expediente"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </Link>

                                        <button
                                            v-if="isSecretario && exp.estado !== 'Archivado'"
                                            type="button"
                                            class="action-btn action-btn--status"
                                            title="Cambiar estado"
                                            @click="abrirEstadoModal(exp)"
                                        >
                                            <RefreshCw class="w-4 h-4" />
                                        </button>

                                        <button
                                            v-if="isSecretario && exp.estado !== 'Archivado'"
                                            type="button"
                                            class="action-btn action-btn--archive"
                                            title="Archivar expediente"
                                            @click="abrirArchivoModal(exp)"
                                        >
                                            <Archive class="w-4 h-4" />
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        </tbody>

                    </table>
                </div>

                <div
                    v-else
                    class="empty-state"
                >
                    <div class="empty-state__icon">
                        <FolderOpen class="empty-state__svg" />
                    </div>

                    <h3 class="empty-state__title">
                        {{
                            expedientes.length > 0
                                ? 'No se encontraron expedientes'
                                : 'No hay expedientes registrados'
                        }}
                    </h3>

                    <p class="empty-state__desc">
                        {{
                            expedientes.length > 0
                                ? 'Prueba modificando los filtros de búsqueda.'
                                : 'Aún no se ha ingresado ningún caso al sistema.'
                        }}
                    </p>
                </div>

            </div>

            <div
                v-if="mostrarEstadoModal"
                class="modal-overlay"
                @click.self="cerrarEstadoModal"
            >

                <div class="modal">

                    <div class="modal__header">

                        <div>
                            <h3 class="modal__titulo">
                                Cambiar estado
                            </h3>

                            <p class="modal__sub">
                                Expediente:
                                {{
                                    expedienteSeleccionado?.numero_expediente
                                }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="modal__close"
                            @click="cerrarEstadoModal"
                        >
                            <X />
                        </button>

                    </div>

                    <div class="modal__body">

                        <label class="modal__label">
                            Nuevo estado
                        </label>

                        <select
                            v-model="estadoForm.estado"
                            class="modal__select"
                        >
                            <option value="Abierto">
                                Abierto
                            </option>

                            <option value="En Proceso">
                                En Proceso
                            </option>

                            <option value="Resuelto">
                                Resuelto
                            </option>

                            <option value="Cerrado">
                                Cerrado
                            </option>
                        </select>

                        <p
                            v-if="estadoForm.errors.estado"
                            class="form-error"
                        >
                            {{ estadoForm.errors.estado }}
                        </p>

                    </div>

                    <div class="modal__footer">

                        <button
                            type="button"
                            class="btn-secundario"
                            @click="cerrarEstadoModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="btn"
                            :disabled="estadoForm.processing"
                            @click="cambiarEstado"
                        >
                            {{
                                estadoForm.processing
                                    ? 'Guardando...'
                                    : 'Confirmar cambio'
                            }}
                        </button>

                    </div>

                </div>

            </div>

            <div
                v-if="mostrarArchivoModal"
                class="modal-overlay"
                @click.self="cerrarArchivoModal"
            >

                <div class="modal modal--small">

                    <div class="modal__header">

                        <div>
                            <h3 class="modal__titulo">
                                Archivar expediente
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="modal__close"
                            @click="cerrarArchivoModal"
                        >
                            <X />
                        </button>

                    </div>

                    <div class="modal__body">

                        <div class="confirmacion-icon">
                            <Archive />
                        </div>

                        <p class="confirmacion-texto">
                            ¿Seguro que deseas archivar el expediente
                            <strong>
                                {{
                                    expedienteParaArchivar?.numero_expediente
                                }}
                            </strong>?
                        </p>

                        <p class="confirmacion-sub">
                            El expediente dejará de aparecer como activo.
                        </p>

                        <p
                            v-if="archivarForm.errors.estado"
                            class="form-error"
                        >
                            {{ archivarForm.errors.estado }}
                        </p>

                    </div>

                    <div class="modal__footer">

                        <button
                            type="button"
                            class="btn-secundario"
                            @click="cerrarArchivoModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="archivarForm.processing"
                            @click="archivarExpediente"
                        >
                            {{
                                archivarForm.processing
                                    ? 'Archivando...'
                                    : 'Sí, archivar'
                            }}
                        </button>

                    </div>

                </div>

            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.db {
    font-family: 'Poppins', 'Inter', sans-serif;
    color: #1E293B;
    display: flex;
    flex-direction: column;
    gap: 22px;
    width: 100%;
    box-sizing: border-box;
}

.db__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.db__titulo {
    font-size: 22px;
    font-weight: 600;
    color: #1E293B;
    margin: 0 0 4px;
}

.db__sub {
    font-size: 13px;
    color: #64748B;
    margin: 0;
}

.db__accesos {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    background: #185FA5;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
    transition: background .15s;
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

.btn:hover:not(:disabled) {
    background: #144d87;
}

.btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.btn__ico {
    width: 16px;
    height: 16px;
}

.btn-excel {
    background: #15803D;
}

.btn-excel:hover {
    background: #166534;
}

.btn-danger {
    background: #DC2626;
}

.btn-danger:hover {
    background: #B91C1C;
}

.btn-secundario {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

.btn-secundario:hover {
    background: #F8FAFC;
}

.panel {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
    min-width: 0;
}

.panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.panel__titulo {
    font-size: 16px;
    font-weight: 600;
    color: #1E293B;
    margin: 0;
}

.resultados {
    font-size: 12px;
    color: #64748B;
    margin: 4px 0 0;
}

.table-container {
    overflow-x: auto;
    margin: -20px;
    margin-top: 0;
    padding: 20px;
    padding-top: 0;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
}

.table th {
    font-size: 11px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.table td {
    padding: 16px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}

.table tr:last-child td {
    border-bottom: none;
}

.table tr:hover td {
    background-color: #F8FAFC;
}

.flex {
    display: flex;
}

.flex-col {
    flex-direction: column;
}

.items-center {
    align-items: center;
}

.gap-2 {
    gap: 0.5rem;
}

.w-4 {
    width: 1rem;
}

.h-4 {
    height: 1rem;
}

.text-slate-400 {
    color: #94a3b8;
}

.text-slate-500 {
    color: #64748b;
}

.text-slate-600 {
    color: #475569;
}

.text-slate-700 {
    color: #334155;
}

.text-slate-800 {
    color: #1e293b;
}

.font-semibold {
    font-weight: 600;
}

.font-medium {
    font-weight: 500;
}

.text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.uppercase {
    text-transform: uppercase;
}

.tracking-wide {
    letter-spacing: 0.025em;
}

.bg-blue-50 {
    background-color: #eff6ff;
}

.text-blue-700 {
    color: #1d4ed8;
}

.px-2 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.py-1 {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}

.rounded {
    border-radius: 0.25rem;
}

.filtros-panel {
    padding: 20px;
}

.filtros-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
}

.filtros-sub {
    font-size: 12px;
    color: #64748B;
    margin: 4px 0 0;
}

.filtros {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 14px;
}

.filtro {
    min-width: 0;
}

.filtro label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper svg {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: #94A3B8;
}

.input-wrapper input,
.filtro select {
    width: 100%;
    height: 38px;
    box-sizing: border-box;
    border: 1px solid #CBD5E1;
    border-radius: 7px;
    background: #FFFFFF;
    color: #334155;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    outline: none;
}

.input-wrapper input {
    padding: 0 12px 0 36px;
}

.filtro select {
    padding: 0 10px;
}

.input-wrapper input:focus,
.filtro select:focus {
    border-color: #185FA5;
    box-shadow: 0 0 0 2px rgba(24, 95, 165, .10);
}

.btn-limpiar {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    background: transparent;
    color: #64748B;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    cursor: pointer;
}

.btn-limpiar:hover {
    color: #185FA5;
}

.btn-limpiar svg {
    width: 15px;
    height: 15px;
}

.estado-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.bg-sky-100 {
    background-color: #e0f2fe;
}

.text-sky-700 {
    color: #0369a1;
}

.bg-amber-100 {
    background-color: #fef3c7;
}

.text-amber-700 {
    color: #b45309;
}

.bg-emerald-100 {
    background-color: #d1fae5;
}

.text-emerald-700 {
    color: #047857;
}

.bg-slate-100 {
    background-color: #f1f5f9;
}

.text-slate-700 {
    color: #334155;
}

.bg-rose-100 {
    background-color: #ffe4e6;
}

.text-rose-700 {
    color: #be123c;
}

.numero-expediente {
    color: #185FA5;
    font-weight: 600;
    text-decoration: none;
}

.numero-expediente:hover {
    text-decoration: underline;
}

.acciones {
    display: flex;
    align-items: center;
    gap: 5px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: #F1F5F9;
    color: #475569;
    border: none;
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
}

.action-btn:hover {
    background: #E2E8F0;
}

.action-btn--view {
    color: #185FA5;
}

.action-btn--view:hover {
    background: #EFF6FF;
}

.action-btn--status {
    color: #B45309;
}

.action-btn--status:hover {
    background: #FEF3C7;
}

.action-btn--archive {
    color: #BE123C;
}

.action-btn--archive:hover {
    background: #FFE4E6;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #F8FAFC;
    border-radius: 12px;
    border: 1px dashed #CBD5E1;
}

.empty-state__icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: #EFF6FF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #185FA5;
}

.empty-state__svg {
    width: 32px;
    height: 32px;
}

.empty-state__title {
    font-size: 18px;
    font-weight: 600;
    color: #1E293B;
    margin: 0 0 8px;
}

.empty-state__desc {
    font-size: 14px;
    color: #64748B;
    margin: 0;
}

.alerta {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border-left: 4px solid currentColor;
    margin-bottom: 4px;
}

.alerta--exito {
    background: #F0FDF4;
    color: #15803D;
}

.alerta__ico {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, .45);
}

.modal {
    width: 100%;
    max-width: 460px;
    background: #FFFFFF;
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .18);
    overflow: hidden;
}

.modal--small {
    max-width: 420px;
}

.modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #E2E8F0;
}

.modal__titulo {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    color: #1E293B;
}

.modal__sub {
    margin: 4px 0 0;
    font-size: 12px;
    color: #64748B;
}

.modal__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #64748B;
    cursor: pointer;
}

.modal__close:hover {
    background: #F1F5F9;
}

.modal__close svg {
    width: 18px;
    height: 18px;
}

.modal__body {
    padding: 20px;
}

.modal__label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.modal__select {
    width: 100%;
    height: 40px;
    padding: 0 10px;
    border: 1px solid #CBD5E1;
    border-radius: 7px;
    background: #FFFFFF;
    color: #334155;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    outline: none;
}

.modal__select:focus {
    border-color: #185FA5;
}

.modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
}

.form-error {
    margin: 6px 0 0;
    color: #DC2626;
    font-size: 11px;
}

.confirmacion-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 15px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FFE4E6;
    color: #BE123C;
}

.confirmacion-icon svg {
    width: 23px;
    height: 23px;
}

.confirmacion-texto {
    margin: 0;
    text-align: center;
    font-size: 14px;
    line-height: 1.6;
    color: #334155;
}

.confirmacion-sub {
    margin: 8px 0 0;
    text-align: center;
    font-size: 12px;
    color: #64748B;
}

@media (max-width: 1000px) {
    .filtros {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 700px) {
    .filtros-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .filtros {
        grid-template-columns: 1fr;
    }

    .db__header {
        flex-direction: column;
    }

    .db__accesos {
        width: 100%;
    }

    .btn {
        flex: 1;
    }

    .acciones {
        flex-wrap: wrap;
    }
}
</style>