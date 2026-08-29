<script setup>
import {
    Head,
    Link
} from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';

import {
    ArrowLeft,
    History,
    User,
    Calendar,
    FileText,
    Upload,
    Trash2,
    RefreshCw,
    Plus,
    Edit,
    Archive,
    ShieldCheck
} from 'lucide-vue-next';

defineProps({
    expediente: {
        type: Object,
        required: true
    },

    historial: {
        type: Array,
        default: () => []
    }
});

const formatearFecha = (fecha) => {
    if (!fecha) {
        return 'Fecha no disponible';
    }

    return new Date(fecha).toLocaleString(
        'es-SV',
        {
            dateStyle: 'medium',
            timeStyle: 'short'
        }
    );
};

const nombreUsuario = (movimiento) => {
    if (!movimiento.usuario) {
        return 'Usuario no disponible';
    }

    return [
        movimiento.usuario.nombre,
        movimiento.usuario.apellido
    ]
        .filter(Boolean)
        .join(' ');
};

const iconoAccion = (accion) => {
    const iconos = {
        CREACION_EXPEDIENTE: Plus,
        EDICION_EXPEDIENTE: Edit,
        CAMBIO_ESTADO: RefreshCw,
        CARGA_DOCUMENTO: Upload,
        DESCARGA_DOCUMENTO: FileText,
        ELIMINACION_DOCUMENTO: Trash2,
        ARCHIVADO_EXPEDIENTE: Archive
    };

    return iconos[accion] || History;
};

const etiquetaAccion = (accion) => {
    const etiquetas = {
        CREACION_EXPEDIENTE: 'Creación de expediente',
        EDICION_EXPEDIENTE: 'Edición de expediente',
        CAMBIO_ESTADO: 'Cambio de estado',
        CARGA_DOCUMENTO: 'Carga de documento',
        DESCARGA_DOCUMENTO: 'Descarga de documento',
        ELIMINACION_DOCUMENTO: 'Eliminación de documento',
        ARCHIVADO_EXPEDIENTE: 'Expediente archivado'
    };

    return etiquetas[accion] || accion;
};

const formatearDetalles = (detalles) => {
    if (!detalles) {
        return [];
    }

    if (typeof detalles === 'string') {
        try {
            detalles = JSON.parse(detalles);
        } catch {
            return [];
        }
    }

    return Object.entries(detalles);
};
</script>

<template>
    <Head title="Historial del expediente" />

    <AppLayout>
        <div class="page">

            <div class="page-header">

                <div>
                    <Link
                        :href="`/expedientes/${expediente.id}`"
                        class="back-link"
                    >
                        <ArrowLeft />
                        Volver al expediente
                    </Link>

                    <div class="title-row">
                        <div class="title-icon">
                            <History />
                        </div>

                        <div>
                            <h1>
                                Historial de cambios
                            </h1>

                            <p>
                                Trazabilidad de las acciones realizadas
                                sobre el expediente.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="expediente-resumen">

                <div>
                    <span>
                        Expediente
                    </span>

                    <strong>
                        #{{ expediente.numero_expediente }}
                    </strong>
                </div>

                <div>
                    <span>
                        Cliente
                    </span>

                    <strong>
                        {{ expediente.cliente }}
                    </strong>
                </div>

                <div>
                    <span>
                        Estado
                    </span>

                    <strong>
                        {{ expediente.estado }}
                    </strong>
                </div>

                <div>
                    <span>
                        Movimientos
                    </span>

                    <strong>
                        {{ historial.length }}
                    </strong>
                </div>

            </div>

            <div
                v-if="historial.length === 0"
                class="empty-state"
            >
                <ShieldCheck />

                <h2>
                    Sin movimientos registrados
                </h2>

                <p>
                    Este expediente no posee movimientos
                    registrados en el historial.
                </p>
            </div>

            <div
                v-else
                class="historial"
            >

                <div
                    v-for="movimiento in historial"
                    :key="movimiento.id"
                    class="historial-item"
                >

                    <div class="timeline-line"></div>

                    <div class="timeline-icon">
                        <component
                            :is="iconoAccion(movimiento.accion)"
                        />
                    </div>

                    <div class="historial-card">

                        <div class="historial-header">

                            <div>
                                <span class="accion">
                                    {{ etiquetaAccion(movimiento.accion) }}
                                </span>

                                <h2>
                                    {{ movimiento.descripcion }}
                                </h2>
                            </div>

                            <div class="fecha">
                                <Calendar />
                                {{ formatearFecha(movimiento.fecha_hora) }}
                            </div>

                        </div>

                        <div class="usuario">

                            <div class="usuario-icon">
                                <User />
                            </div>

                            <div>
                                <span>
                                    Usuario responsable
                                </span>

                                <strong>
                                    {{ nombreUsuario(movimiento) }}
                                </strong>

                                <small
                                    v-if="movimiento.usuario?.rol"
                                >
                                    {{ movimiento.usuario.rol }}
                                </small>
                            </div>

                        </div>

                        <div
                            v-if="
                                formatearDetalles(movimiento.detalles).length
                            "
                            class="detalles"
                        >

                            <div class="detalles-title">
                                <FileText />
                                Detalles de la acción
                            </div>

                            <div
                                v-for="[
                                    clave,
                                    valor
                                ] in formatearDetalles(movimiento.detalles)"
                                :key="clave"
                                class="detalle"
                            >

                                <span>
                                    {{ clave }}
                                </span>

                                <strong>
                                    {{
                                        typeof valor === 'object'
                                            ? JSON.stringify(valor)
                                            : valor
                                    }}
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.page {
    padding: 26px 28px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 22px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 16px;
    color: #64748B;
    text-decoration: none;
    font-size: 12px;
}

.back-link svg {
    width: 15px;
    height: 15px;
}

.title-row {
    display: flex;
    align-items: center;
    gap: 13px;
}

.title-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.title-icon svg {
    width: 22px;
    height: 22px;
    color: #475569;
}

h1 {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    font-size: 22px;
    font-weight: 600;
    color: #1E293B;
}

.title-row p {
    margin: 3px 0 0;
    font-size: 12px;
    color: #64748B;
}

.expediente-resumen {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 28px;
}

.expediente-resumen > div {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 9px;
    padding: 14px 16px;
}

.expediente-resumen span {
    display: block;
    margin-bottom: 4px;
    font-size: 10px;
    color: #64748B;
}

.expediente-resumen strong {
    display: block;
    font-size: 13px;
    color: #1E293B;
}

.historial {
    position: relative;
}

.historial-item {
    position: relative;
    display: flex;
    gap: 14px;
    padding-bottom: 20px;
}

.timeline-line {
    position: absolute;
    top: 39px;
    bottom: 0;
    left: 18px;
    width: 1px;
    background: #E2E8F0;
}

.historial-item:last-child .timeline-line {
    display: none;
}

.timeline-icon {
    position: relative;
    z-index: 2;
    width: 37px;
    height: 37px;
    flex-shrink: 0;
    border: 1px solid #CBD5E1;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-icon svg {
    width: 17px;
    height: 17px;
    color: #475569;
}

.historial-card {
    flex: 1;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 16px;
}

.historial-header {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.accion {
    display: inline-block;
    margin-bottom: 5px;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748B;
}

.historial-header h2 {
    margin: 0;
    font-size: 13px;
    font-weight: 500;
    color: #1E293B;
}

.fecha {
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    font-size: 10px;
    color: #64748B;
}

.fecha svg {
    width: 14px;
    height: 14px;
}

.usuario {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-top: 15px;
    padding-top: 13px;
    border-top: 1px solid #F1F5F9;
}

.usuario-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #F8FAFC;
    display: flex;
    align-items: center;
    justify-content: center;
}

.usuario-icon svg {
    width: 15px;
    height: 15px;
    color: #64748B;
}

.usuario span {
    display: block;
    font-size: 9px;
    color: #94A3B8;
}

.usuario strong {
    display: block;
    font-size: 11px;
    color: #334155;
}

.usuario small {
    display: block;
    margin-top: 1px;
    font-size: 9px;
    color: #94A3B8;
}

.detalles {
    margin-top: 13px;
    padding: 11px;
    border-radius: 7px;
    background: #F8FAFC;
}

.detalles-title {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 8px;
    font-size: 10px;
    font-weight: 600;
    color: #475569;
}

.detalles-title svg {
    width: 13px;
    height: 13px;
}

.detalle {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 5px 0;
    border-top: 1px solid #E2E8F0;
}

.detalle span {
    font-size: 9px;
    color: #64748B;
}

.detalle strong {
    max-width: 65%;
    text-align: right;
    word-break: break-word;
    font-size: 9px;
    font-weight: 500;
    color: #334155;
}

.empty-state {
    padding: 55px 20px;
    text-align: center;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.empty-state svg {
    width: 35px;
    height: 35px;
    margin-bottom: 10px;
    color: #94A3B8;
}

.empty-state h2 {
    margin: 0 0 5px;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.empty-state p {
    margin: 0;
    font-size: 11px;
    color: #94A3B8;
}

@media (max-width: 700px) {
    .expediente-resumen {
        grid-template-columns: repeat(2, 1fr);
    }

    .historial-header {
        flex-direction: column;
    }

    .fecha {
        white-space: normal;
    }
}

@media (max-width: 500px) {
    .page {
        padding: 20px 15px;
    }

    .expediente-resumen {
        grid-template-columns: 1fr;
    }

    .detalle {
        flex-direction: column;
        gap: 3px;
    }

    .detalle strong {
        max-width: 100%;
        text-align: left;
    }
}
</style>