<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';

import {
    Save,
    X,
    FileText,
    User,
    Gavel,
    Calendar,
    AlertTriangle,
    Clock,
    Upload,
    Eye
} from 'lucide-vue-next';

import {
    computed,
    ref
} from 'vue';

const props = defineProps({
    expediente: Object,
    asesores: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

const usuario = computed(() =>
    page.props.auth.usuario
);

const archivo = ref(null);

const form = useForm({
    numero_expediente: props.expediente.numero_expediente,
    cliente: props.expediente.cliente,
    tipo_proceso: props.expediente.tipo_proceso,
    asesor_id: props.expediente.asesor_id,
    fecha_ingreso: props.expediente.fecha_ingreso
        ? props.expediente.fecha_ingreso.split('T')[0]
        : '',
    estado: props.expediente.estado || 'Abierto',
    descripcion: props.expediente.descripcion || '',
});

const documentoForm = useForm({
    documento: null
});

const submit = () => {
    form.put(`/expedientes/${props.expediente.id}`, {
        preserveScroll: true,
    });
};

const seleccionarArchivo = (event) => {
    if (
        event.target.files &&
        event.target.files.length > 0
    ) {
        archivo.value = event.target.files[0];
        documentoForm.documento = event.target.files[0];
    }
};

const subirDocumento = () => {
    if (!documentoForm.documento) {
        return;
    }

    documentoForm.post(
        `/expedientes/${props.expediente.id}/documentos`,
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                documentoForm.reset();
                archivo.value = null;

                const input =
                    document.getElementById('documento');

                if (input) {
                    input.value = '';
                }
            }
        }
    );
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';

    const d = new Date(dateString);

    return d.toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatearTamano = (tamano) => {
    if (!tamano) {
        return 'Tamaño desconocido';
    }

    if (tamano < 1024) {
        return `${tamano} B`;
    }

    if (tamano < 1024 * 1024) {
        return `${(tamano / 1024).toFixed(1)} KB`;
    }

    return `${(tamano / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <Head title="Editar Expediente" />

    <AppLayout>
        <div class="db">

            <!-- Cabecera -->
            <div class="db__header">
                <div>
                    <h1 class="db__titulo">
                        Editar Expediente
                    </h1>

                    <p class="db__sub">
                        Modifica la información del caso y actualiza su estado.
                    </p>
                </div>
            </div>

            <div class="inferior">

                <!-- Contenido Principal -->
                <div class="panel">

                    <form
                        class="formulario"
                        @submit.prevent="submit"
                        novalidate
                    >

                        <div class="campos-fila">

                            <!-- Número de Expediente (Solo lectura) -->
                            <div class="campo campo--mitad">
                                <label class="campo__label">
                                    Número de Expediente
                                </label>

                                <div class="input-grupo">
                                    <FileText class="input-ico" />

                                    <input
                                        type="text"
                                        class="input-text"
                                        v-model="form.numero_expediente"
                                        readonly
                                        style="background-color: #F8FAFC; color: #64748B; cursor: not-allowed;"
                                    />
                                </div>

                                <span
                                    class="error-msg"
                                    v-if="form.errors.numero_expediente"
                                >
                                    {{ form.errors.numero_expediente }}
                                </span>
                            </div>

                            <!-- Cliente -->
                            <div
                                class="campo campo--mitad"
                                :class="{
                                    'has-error': form.errors.cliente
                                }"
                            >
                                <label class="campo__label">
                                    Información del Cliente
                                    <span class="req">*</span>
                                </label>

                                <div class="input-grupo">
                                    <User class="input-ico" />

                                    <input
                                        type="text"
                                        class="input-text"
                                        v-model="form.cliente"
                                        placeholder="Nombre completo del cliente"
                                    />
                                </div>

                                <span
                                    class="error-msg"
                                    v-if="form.errors.cliente"
                                >
                                    {{ form.errors.cliente }}
                                </span>
                            </div>

                        </div>

                        <div class="campos-fila">

                            <!-- Tipo de Proceso -->
                            <div
                                class="campo campo--mitad"
                                :class="{
                                    'has-error': form.errors.tipo_proceso
                                }"
                            >
                                <label class="campo__label">
                                    Tipo de Proceso Jurídico
                                    <span class="req">*</span>
                                </label>

                                <div class="input-grupo">
                                    <Gavel class="input-ico" />

                                    <select
                                        class="input-select"
                                        v-model="form.tipo_proceso"
                                    >
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

                                <span
                                    class="error-msg"
                                    v-if="form.errors.tipo_proceso"
                                >
                                    {{ form.errors.tipo_proceso }}
                                </span>
                            </div>

                            <!-- Asesor Responsable -->
                            <div
                                class="campo campo--mitad"
                                :class="{
                                    'has-error': form.errors.asesor_id
                                }"
                            >
                                <label class="campo__label">
                                    Asesor Responsable
                                    <span class="req">*</span>
                                </label>

                                <div class="input-grupo">
                                    <User class="input-ico" />

                                    <select
                                        class="input-select"
                                        v-model="form.asesor_id"
                                    >
                                        <option
                                            value=""
                                            disabled
                                        >
                                            Seleccione un asesor...
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

                                <span
                                    class="error-msg"
                                    v-if="form.errors.asesor_id"
                                >
                                    {{ form.errors.asesor_id }}
                                </span>
                            </div>

                        </div>

                        <div class="campos-fila">

                            <!-- Fecha de Ingreso -->
                            <div
                                class="campo campo--mitad"
                                :class="{
                                    'has-error': form.errors.fecha_ingreso
                                }"
                            >
                                <label class="campo__label">
                                    Fecha de Ingreso
                                    <span class="req">*</span>
                                </label>

                                <div class="input-grupo">
                                    <Calendar class="input-ico" />

                                    <input
                                        type="date"
                                        class="input-text"
                                        v-model="form.fecha_ingreso"
                                    />
                                </div>

                                <span
                                    class="error-msg"
                                    v-if="form.errors.fecha_ingreso"
                                >
                                    {{ form.errors.fecha_ingreso }}
                                </span>
                            </div>

                            <!-- Estado -->
                            <div
                                class="campo campo--mitad"
                                :class="{
                                    'has-error': form.errors.estado
                                }"
                            >
                                <label class="campo__label">
                                    Estado del Expediente
                                    <span class="req">*</span>
                                </label>

                                <div class="input-grupo">
                                    <FileText class="input-ico" />

                                    <select
                                        class="input-select"
                                        v-model="form.estado"
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

                                        <option value="Archivado">
                                            Archivado
                                        </option>
                                    </select>
                                </div>

                                <span
                                    class="error-msg"
                                    v-if="form.errors.estado"
                                >
                                    {{ form.errors.estado }}
                                </span>
                            </div>

                        </div>

                        <!-- Descripción -->
                        <div
                            class="campo"
                            :class="{
                                'has-error': form.errors.descripcion
                            }"
                        >
                            <label class="campo__label">
                                Descripción del Caso
                            </label>

                            <div
                                class="input-grupo"
                                style="align-items: flex-start;"
                            >
                                <textarea
                                    class="input-text"
                                    v-model="form.descripcion"
                                    rows="4"
                                    placeholder="Detalles adicionales sobre el caso..."
                                    style="padding-left: 12px; resize: vertical;"
                                ></textarea>
                            </div>

                            <span
                                class="error-msg"
                                v-if="form.errors.descripcion"
                            >
                                {{ form.errors.descripcion }}
                            </span>
                        </div>

                        <!-- DOCUMENTOS -->
                        <div class="documentos-seccion">

                            <div class="documentos-header">
                                <div class="documentos-titulo">
                                    <FileText />

                                    <div>
                                        <h2>
                                            Documentos
                                        </h2>

                                        <p>
                                            Documentos vinculados al expediente
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- SUBIR DOCUMENTO -->
                            <div class="upload-box">

                                <div class="upload-info">
                                    <Upload />

                                    <div>
                                        <strong>
                                            Subir documento
                                        </strong>

                                        <span>
                                            PDF, Word, Excel o imagen.
                                            Máximo 10 MB.
                                        </span>
                                    </div>
                                </div>

                                <input
                                    id="documento"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                    @change="seleccionarArchivo"
                                />

                                <button
                                    v-if="archivo"
                                    type="button"
                                    class="btn-upload"
                                    :disabled="documentoForm.processing"
                                    @click="subirDocumento"
                                >
                                    <Upload class="btn-upload__ico" />

                                    Subir documento
                                </button>

                            </div>

                            <!-- ERROR DEL DOCUMENTO -->
                            <div
                                v-if="documentoForm.errors.documento"
                                class="error"
                            >
                                <AlertTriangle />

                                {{ documentoForm.errors.documento }}
                            </div>

                            <!-- LISTA DE DOCUMENTOS -->
                            <div
                                v-if="
                                    expediente.documentos &&
                                    expediente.documentos.length > 0
                                "
                                class="documentos-lista"
                            >

                                <div
                                    v-for="documento in expediente.documentos"
                                    :key="documento.id"
                                    class="documento-item"
                                >

                                    <div class="documento-info">

                                        <div class="documento-icono">
                                            <FileText />
                                        </div>

                                        <div class="documento-datos">

                                            <strong>
                                                {{ documento.nombre_original }}
                                            </strong>

                                            <span>
                                                {{ formatearTamano(documento.tamano) }}
                                                ·
                                                {{
                                                    documento.usuario
                                                        ? documento.usuario.nombre +
                                                          ' ' +
                                                          documento.usuario.apellido
                                                        : 'Usuario'
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                    <a
                                        :href="`/documentos/${documento.id}/ver`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn-ver"
                                    >
                                        <Eye class="btn-ver__ico" />
                                        Ver
                                    </a>

                                </div>

                            </div>

                            <!-- SIN DOCUMENTOS -->
                            <div
                                v-else
                                class="sin-documentos"
                            >
                                <FileText />

                                <span>
                                    No hay documentos vinculados a este expediente.
                                </span>
                            </div>

                        </div>

                        <!-- Errores generales -->
                        <div
                            class="alerta alerta--error mt-4"
                            v-if="Object.keys(form.errors).length > 0"
                        >
                            <AlertTriangle class="alerta__ico" />

                            <span>
                                Por favor revisa los errores en el formulario
                                para poder continuar.
                            </span>
                        </div>

                        <hr class="divider" />

                        <!-- Botones de Acción -->
                        <div class="formulario__acciones">

                            <Link
                                href="/expedientes"
                                class="btn-sec"
                            >
                                <X class="btn-sec__ico" />
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                class="btn"
                                :disabled="form.processing"
                                :class="{
                                    'opacity-50 cursor-not-allowed':
                                        form.processing
                                }"
                            >
                                <Save class="btn__ico" />

                                Guardar Cambios
                            </button>

                        </div>

                    </form>

                </div>

                <!-- Panel Lateral Informativo -->
                <div class="panel panel--chico panel-info">

                    <div class="panel__head">
                        <h2 class="panel__titulo">
                            Auditoría del Caso
                        </h2>
                    </div>

                    <div class="info-lista">

                        <div class="info-item">
                            <span class="info-label">
                                Fecha de creación
                            </span>

                            <span class="info-valor">
                                {{ formatDate(expediente.created_at) }}
                            </span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Registrado por
                            </span>

                            <span class="info-valor">
                                {{
                                    expediente.creador
                                        ? expediente.creador.nombre +
                                          ' ' +
                                          expediente.creador.apellido
                                        : 'Sistema'
                                }}
                            </span>
                        </div>

                        <div class="info-item mt-2">
                            <span class="info-label">
                                Última modificación
                            </span>

                            <span class="info-valor">
                                {{ formatDate(expediente.updated_at) }}
                            </span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Modificado por
                            </span>

                            <span class="info-valor">
                                {{
                                    expediente.modificador
                                        ? expediente.modificador.nombre +
                                          ' ' +
                                          expediente.modificador.apellido
                                        : 'N/A'
                                }}
                            </span>
                        </div>

                    </div>

                    <hr class="divider" />

                    <div class="alerta alerta--info">
                        <Clock class="alerta__ico" />

                        <span style="font-size: 11px;">
                            Al cambiar el estado a "Cerrado" o "Archivado",
                            el caso dejará de aparecer como activo en el
                            Dashboard.
                        </span>
                    </div>

                </div>

            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* ── Reset y base ── */

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

.inferior {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 16px;
    align-items: start;
}

.panel {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
    min-width: 0;
}

.panel--chico {
    padding: 20px;
}

.panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.panel__titulo {
    font-size: 15px;
    font-weight: 600;
    color: #1E293B;
    margin: 0;
}

.divider {
    border: 0;
    border-top: 1px solid #E2E8F0;
    margin: 20px 0;
}

.req {
    color: #ef4444;
}

/* ── Formulario y Campos ── */

.formulario {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.campos-fila {
    display: flex;
    gap: 16px;
}

.campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.campo--mitad {
    width: 50%;
}

.campo__label {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
}

.input-grupo {
    position: relative;
    display: flex;
    align-items: center;
}

.input-ico {
    position: absolute;
    left: 12px;
    width: 16px;
    height: 16px;
    color: #94A3B8;
}

.input-text,
.input-select {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #1E293B;
    background-color: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
}

.input-text:focus,
.input-select:focus {
    outline: none;
    border-color: #185FA5;
    box-shadow: 0 0 0 3px #EFF6FF;
}

.has-error .input-text,
.has-error .input-select {
    border-color: #ef4444;
}

.has-error .input-text:focus,
.has-error .input-select:focus {
    box-shadow: 0 0 0 3px #FEE2E2;
}

.error-msg {
    font-size: 11px;
    color: #ef4444;
    margin-top: 2px;
    font-weight: 500;
    display: block;
}

.input-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
}

/* ── DOCUMENTOS ── */

.documentos-seccion {
    display: flex;
    flex-direction: column;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    overflow: hidden;
    background: #FFFFFF;
}

.documentos-header {
    padding: 16px 18px;
    border-bottom: 1px solid #E2E8F0;
}

.documentos-titulo {
    display: flex;
    align-items: center;
    gap: 9px;
}

.documentos-titulo > svg {
    width: 18px;
    height: 18px;
    color: #185FA5;
}

.documentos-titulo h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1E293B;
}

.documentos-titulo p {
    margin: 3px 0 0;
    color: #64748B;
    font-size: 10px;
}

/* ── SUBIR DOCUMENTO ── */

.upload-box {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 16px 18px;
    padding: 15px;
    border: 1px dashed #93C5FD;
    background: #F8FAFC;
    border-radius: 9px;
}

.upload-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.upload-info > svg {
    width: 19px;
    color: #185FA5;
}

.upload-info div {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.upload-info strong {
    font-size: 12px;
}

.upload-info span {
    color: #64748B;
    font-size: 10px;
}

.upload-box input {
    max-width: 220px;
    font-size: 10px;
}

.btn-upload {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 13px;
    border: none;
    border-radius: 7px;
    background: #185FA5;
    color: #FFFFFF;
    font-family: 'Poppins', sans-serif;
    font-size: 11px;
    cursor: pointer;
}

.btn-upload:hover {
    background: #144D87;
}

.btn-upload:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.btn-upload__ico {
    width: 15px;
    height: 15px;
}

/* ── LISTA DE DOCUMENTOS ── */

.documentos-lista {
    display: flex;
    flex-direction: column;
    margin: 0 18px 16px;
    border-top: 1px solid #E2E8F0;
}

.documento-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 13px 0;
    border-bottom: 1px solid #E2E8F0;
}

.documento-item:last-child {
    border-bottom: none;
}

.documento-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.documento-icono {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #EFF6FF;
    color: #185FA5;
    border-radius: 7px;
    flex-shrink: 0;
}

.documento-icono svg {
    width: 17px;
}

.documento-datos {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.documento-datos strong {
    font-size: 11px;
    font-weight: 500;
    color: #1E293B;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.documento-datos span {
    color: #94A3B8;
    font-size: 9px;
}

/* ── BOTÓN VER ── */

.btn-ver {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 11px;
    border: 1px solid #BFDBFE;
    border-radius: 7px;
    background: #EFF6FF;
    color: #185FA5;
    font-family: 'Poppins', sans-serif;
    font-size: 10px;
    font-weight: 500;
    text-decoration: none;
    flex-shrink: 0;
    transition: background .15s;
}

.btn-ver:hover {
    background: #DBEAFE;
}

.btn-ver__ico {
    width: 14px;
    height: 14px;
}

/* ── SIN DOCUMENTOS ── */

.sin-documentos {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0 18px 16px;
    padding: 20px;
    border-top: 1px solid #E2E8F0;
    color: #94A3B8;
    font-size: 11px;
}

.sin-documentos svg {
    width: 17px;
}

/* ── BOTONES ── */

.formulario__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    background: #185FA5;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: background .15s;
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.btn:hover:not(:disabled) {
    background: #144d87;
}

.btn__ico {
    width: 16px;
    height: 16px;
}

.btn-sec {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s;
    text-decoration: none;
}

.btn-sec:hover {
    background: #F1F5F9;
    color: #1E293B;
}

.btn-sec__ico {
    width: 16px;
    height: 16px;
}

.opacity-50 {
    opacity: 0.5;
}

.cursor-not-allowed {
    cursor: not-allowed;
}

/* ── Info Lateral ── */

.info-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.info-label {
    font-size: 11px;
    color: #64748B;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.info-valor {
    font-size: 13px;
    color: #1E293B;
    font-weight: 500;
}

.mt-2 {
    margin-top: 8px;
}

/* ── Alertas ── */

.alerta {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 12px;
    border-left: 3px solid currentColor;
    line-height: 1.4;
}

.alerta--error {
    background: #FEE2E2;
    color: #991B1B;
}

.alerta--info {
    background: #DBEAFE;
    color: #1E40AF;
}

.alerta__ico {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin-top: 2px;
}

.mt-4 {
    margin-top: 16px;
}

.error {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0 18px 15px;
    padding: 9px 12px;
    background: #FEE2E2;
    color: #991B1B;
    border-radius: 7px;
    font-size: 11px;
}

.error svg {
    width: 15px;
}

/* ── Responsive ── */

@media (max-width: 1100px) {
    .inferior {
        grid-template-columns: 1fr;
    }

    .panel-info {
        order: -1;
    }
}

@media (max-width: 800px) {
    .upload-box {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .campos-fila {
        flex-direction: column;
    }

    .campo--mitad {
        width: 100%;
    }

    .formulario__acciones {
        flex-direction: column-reverse;
    }

    .btn,
    .btn-sec {
        width: 100%;
    }
}
</style>