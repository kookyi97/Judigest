<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

import {
    Head,
    Link,
    usePage
} from '@inertiajs/vue3';

import {
    ArrowLeft,
    FileText,
    User,
    Clock,
    CheckCircle2,
    Eye
} from 'lucide-vue-next';

import {
    computed
} from 'vue';

const props = defineProps({
    expediente: {
        type: Object,
        required: true
    }
});

const page = usePage();

const usuario = computed(() =>
    page.props.auth.usuario
);

const formatDate = (dateString) => {
    if (!dateString) {
        return 'N/A';
    }

    return new Date(dateString).toLocaleDateString(
        'es-ES',
        {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        }
    );
};

const formatDateTime = (dateString) => {
    if (!dateString) {
        return 'N/A';
    }

    return new Date(dateString).toLocaleString(
        'es-ES',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    );
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'Abierto':
            return 'estado-abierto';

        case 'En Proceso':
            return 'estado-proceso';

        case 'Resuelto':
            return 'estado-resuelto';

        case 'Cerrado':
            return 'estado-cerrado';

        case 'Archivado':
            return 'estado-archivado';

        default:
            return 'estado-abierto';
    }
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
    <Head
        :title="`Expediente ${expediente.numero_expediente}`"
    />

    <AppLayout>

        <div class="show-page">

            <!-- ENCABEZADO -->
            <div class="page-header">

                <div>

                    <Link
                        href="/expedientes"
                        class="back-link"
                    >
                        <ArrowLeft />
                        Volver a expedientes
                    </Link>

                    <h1>
                        Expediente
                        {{ expediente.numero_expediente }}
                    </h1>

                    <p>
                        Detalle completo del proceso jurídico
                    </p>

                </div>

                <span
                    class="estado-badge"
                    :class="estadoClass(expediente.estado)"
                >
                    {{ expediente.estado }}
                </span>

            </div>

            <!-- MENSAJE DE ÉXITO -->
            <div
                v-if="page.props.flash?.exito"
                class="alerta"
            >
                <CheckCircle2 />

                {{ page.props.flash.exito }}
            </div>

            <div class="contenido">

                <!-- INFORMACIÓN DEL CASO -->
                <div class="card">

                    <div class="card-header">

                        <div class="card-title">

                            <FileText />

                            <h2>
                                Información del caso
                            </h2>

                        </div>

                    </div>

                    <div class="info-grid">

                        <div class="info-item">
                            <span>
                                Número de expediente
                            </span>

                            <strong>
                                {{ expediente.numero_expediente }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span>
                                Cliente
                            </span>

                            <strong>
                                {{ expediente.cliente }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span>
                                Tipo de proceso
                            </span>

                            <strong>
                                {{ expediente.tipo_proceso }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span>
                                Asesor asignado
                            </span>

                            <strong>
                                {{
                                    expediente.asesor
                                        ? expediente.asesor.nombre +
                                          ' ' +
                                          expediente.asesor.apellido
                                        : 'Sin asignar'
                                }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span>
                                Fecha de ingreso
                            </span>

                            <strong>
                                {{ formatDate(expediente.fecha_ingreso) }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span>
                                Estado
                            </span>

                            <strong>
                                {{ expediente.estado }}
                            </strong>
                        </div>

                    </div>

                    <div
                        v-if="expediente.descripcion"
                        class="descripcion"
                    >
                        <span>
                            Descripción
                        </span>

                        <p>
                            {{ expediente.descripcion }}
                        </p>
                    </div>

                </div>

                <!-- AUDITORÍA -->
                <div class="card">

                    <div class="card-header">

                        <div class="card-title">

                            <Clock />

                            <h2>
                                Auditoría
                            </h2>

                        </div>

                    </div>

                    <div class="auditoria">

                        <div class="auditoria-item">

                            <div class="auditoria-icon">
                                <User />
                            </div>

                            <div>

                                <span>
                                    Creado por
                                </span>

                                <strong>
                                    {{
                                        expediente.creador
                                            ? expediente.creador.nombre +
                                              ' ' +
                                              expediente.creador.apellido
                                            : 'Sistema'
                                    }}
                                </strong>

                                <small>
                                    {{
                                        formatDateTime(
                                            expediente.created_at
                                        )
                                    }}
                                </small>

                            </div>

                        </div>

                        <div class="auditoria-item">

                            <div class="auditoria-icon">
                                <Clock />
                            </div>

                            <div>

                                <span>
                                    Última modificación
                                </span>

                                <strong>
                                    {{
                                        expediente.modificador
                                            ? expediente.modificador.nombre +
                                              ' ' +
                                              expediente.modificador.apellido
                                            : 'Sin modificaciones'
                                    }}
                                </strong>

                                <small>
                                    {{
                                        formatDateTime(
                                            expediente.updated_at
                                        )
                                    }}
                                </small>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- DOCUMENTOS -->
                <div class="card">

                    <div class="card-header">

                        <div class="card-title">

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

            </div>

        </div>

    </AppLayout>
</template>

<style scoped>
.show-page {
    font-family: 'Poppins', 'Inter', sans-serif;
    color: #1E293B;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 9px;
    color: #64748B;
    font-size: 12px;
    text-decoration: none;
}

.back-link:hover {
    color: #185FA5;
}

.back-link svg {
    width: 15px;
}

.page-header h1 {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 600;
}

.page-header p {
    margin: 0;
    color: #64748B;
    font-size: 13px;
}

.estado-badge {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.estado-abierto {
    background: #E0F2FE;
    color: #0369A1;
}

.estado-proceso {
    background: #FEF3C7;
    color: #B45309;
}

.estado-resuelto {
    background: #D1FAE5;
    color: #047857;
}

.estado-cerrado {
    background: #F1F5F9;
    color: #334155;
}

.estado-archivado {
    background: #FFE4E6;
    color: #BE123C;
}

.alerta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    padding: 11px 14px;
    background: #F0FDF4;
    color: #15803D;
    border-left: 4px solid #15803D;
    border-radius: 8px;
    font-size: 12px;
}

.alerta svg {
    width: 17px;
}

.contenido {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
    overflow: hidden;
}

.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #E2E8F0;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 9px;
}

.card-title svg {
    width: 18px;
    height: 18px;
    color: #185FA5;
}

.card-title h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.card-title p {
    margin: 3px 0 0;
    color: #64748B;
    font-size: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    padding: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item span,
.descripcion > span {
    color: #64748B;
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 600;
}

.info-item strong {
    font-size: 13px;
    font-weight: 500;
}

.descripcion {
    padding: 18px 20px;
    border-top: 1px solid #E2E8F0;
}

.descripcion p {
    margin: 7px 0 0;
    color: #475569;
    font-size: 12px;
    line-height: 1.6;
}

.auditoria {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 20px;
}

.auditoria-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.auditoria-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #EFF6FF;
    color: #185FA5;
    border-radius: 50%;
}

.auditoria-icon svg {
    width: 16px;
}

.auditoria-item > div:last-child {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.auditoria-item span {
    color: #64748B;
    font-size: 9px;
    text-transform: uppercase;
    font-weight: 600;
}

.auditoria-item strong {
    font-size: 11px;
    font-weight: 500;
}

.auditoria-item small {
    color: #94A3B8;
    font-size: 9px;
}

/* LISTA DE DOCUMENTOS */

.documentos-lista {
    display: flex;
    flex-direction: column;
    margin: 0 20px 18px;
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

/* BOTÓN VER */

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

/* SIN DOCUMENTOS */

.sin-documentos {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0 20px 18px;
    padding: 20px;
    border-top: 1px solid #E2E8F0;
    color: #94A3B8;
    font-size: 11px;
}

.sin-documentos svg {
    width: 17px;
}

@media (max-width: 800px) {
    .info-grid {
        grid-template-columns: 1fr 1fr;
    }

    .auditoria {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 550px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .documento-item {
        align-items: flex-start;
    }
}
</style>