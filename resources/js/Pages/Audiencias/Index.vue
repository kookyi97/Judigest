<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Scale, Plus, Edit2, X, CheckCircle2,
    Calendar, Clock, MapPin, AlertTriangle, Search
} from 'lucide-vue-next';

const props = defineProps({
    audiencias:  { type: Array, default: () => [] },
    expedientes: { type: Array, default: () => [] },
});

const page     = usePage();
const usuario  = computed(() => page.props.auth.usuario);
const esSecretario = computed(() => usuario.value?.rol === 'secretario');

// ── Filtros reactivos ─────────────────────────────────────────────────────
const filtroBusqueda = ref('');
const filtroEstado   = ref('');

const audienciasFiltradas = computed(() => {
    return props.audiencias.filter(a => {
        const busq = filtroBusqueda.value.toLowerCase();
        if (busq && !a.numero_exp?.toLowerCase().includes(busq) &&
                    !a.cliente?.toLowerCase().includes(busq) &&
                    !a.sala_juzgado?.toLowerCase().includes(busq)) return false;
        if (filtroEstado.value && a.estado !== filtroEstado.value) return false;
        return true;
    });
});

// ── Modal cancelar (JD024) ────────────────────────────────────────────────
const modalCancelar    = ref(false);
const audienciaSelec   = ref(null);

const abrirCancelar = (a) => {
    audienciaSelec.value = a;
    modalCancelar.value  = true;
};

const confirmarCancelar = () => {
    router.patch(`/audiencias/${audienciaSelec.value.id}/cancelar`, {}, {
        preserveScroll: true,
        onSuccess: () => { modalCancelar.value = false; },
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────
const estadoClass = (estado) =>
    estado === 'cancelada' ? 'badge--cancelada' : 'badge--programada';

const hayFiltros = computed(() => filtroBusqueda.value || filtroEstado.value);

const limpiar = () => {
    filtroBusqueda.value = '';
    filtroEstado.value   = '';
};
</script>

<template>
    <Head title="Audiencias" />
    <AppLayout>
        <div class="db">

            <!-- Cabecera -->
            <div class="db__header">
                <div>
                    <h1 class="db__titulo">Gestión de Audiencias</h1>
                    <p class="db__sub">Registro y seguimiento de audiencias judiciales</p>
                </div>
                <div class="db__accesos">
                    <Link href="/calendario" class="btn btn--outline">
                        <Calendar class="btn__ico" /> Ver calendario
                    </Link>
                    <Link v-if="esSecretario" href="/audiencias/create" class="btn">
                        <Plus class="btn__ico" /> Registrar audiencia
                    </Link>
                </div>
            </div>

            <!-- Flash éxito -->
            <div v-if="page.props.flash?.exito" class="alerta alerta--exito">
                <CheckCircle2 class="alerta__ico" />
                <span>{{ page.props.flash.exito }}</span>
            </div>

            <!-- Filtros -->
            <div class="filtros-panel">
                <div class="filtro-busqueda">
                    <Search class="filtro-busqueda__ico" />
                    <input
                        type="text"
                        class="filtro-busqueda__input"
                        placeholder="Buscar por expediente, cliente o sala..."
                        v-model="filtroBusqueda"
                    />
                </div>
                <select class="filtro-select" v-model="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="programada">Programada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <button v-if="hayFiltros" class="btn-limpiar" @click="limpiar">
                    <X class="btn-limpiar__ico" /> Limpiar
                </button>
            </div>

            <!-- Tabla -->
            <div class="panel">
                <div class="panel__head">
                    <h2 class="panel__titulo">
                        Audiencias registradas
                        <span class="contador">{{ audienciasFiltradas.length }}</span>
                    </h2>
                </div>

                <!-- Vacío -->
                <div v-if="audienciasFiltradas.length === 0" class="empty-state">
                    <div class="empty-state__icon"><Scale class="empty-state__svg" /></div>
                    <h3 class="empty-state__title">
                        {{ hayFiltros ? 'Sin resultados' : 'No hay audiencias registradas' }}
                    </h3>
                    <p class="empty-state__desc">
                        {{ hayFiltros ? 'Prueba con otros filtros.' : 'Registra la primera audiencia usando el botón de arriba.' }}
                    </p>
                </div>

                <!-- Lista de audiencias -->
                <div v-else class="audiencias-lista">
                    <div
                        v-for="a in audienciasFiltradas"
                        :key="a.id"
                        class="audiencia-card"
                        :class="{ 'audiencia-card--cancelada': a.estado === 'cancelada', 'audiencia-card--conflicto': a.conflicto && a.estado !== 'cancelada' }"
                    >
                        <!-- Indicador de conflicto (JD026 criterio: destacar conflictos) -->
                        <div v-if="a.conflicto && a.estado !== 'cancelada'" class="conflicto-tag">
                            <AlertTriangle class="conflicto-tag__ico" />
                            Conflicto de horario
                        </div>

                        <!-- Fecha destacada -->
                        <div class="audiencia-card__fecha">
                            <span class="fecha-dia">{{ a.fecha_display?.split('/')[0] }}</span>
                            <span class="fecha-mes">
                                {{ ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][parseInt(a.fecha_display?.split('/')[1]) - 1] }}
                            </span>
                            <span class="fecha-anio">{{ a.fecha_display?.split('/')[2] }}</span>
                        </div>

                        <!-- Datos -->
                        <div class="audiencia-card__body">
                            <div class="audiencia-card__row1">
                                <span class="tipo-tag">{{ a.tipo_audiencia }}</span>
                                <span class="estado-badge" :class="estadoClass(a.estado)">{{ a.estado }}</span>
                            </div>
                            <p class="audiencia-card__exp">{{ a.numero_exp }} — {{ a.cliente }}</p>
                            <div class="audiencia-card__meta">
                                <span class="meta-item">
                                    <Clock class="meta-ico" /> {{ a.hora }}
                                </span>
                                <span class="meta-item">
                                    <MapPin class="meta-ico" /> {{ a.sala_juzgado }}
                                </span>
                            </div>
                            <p class="audiencia-card__prac" v-if="a.practicante !== 'Sin asignar'">
                                Practicante: {{ a.practicante }}
                            </p>
                            <p class="audiencia-card__obs" v-if="a.observaciones">
                                {{ a.observaciones }}
                            </p>
                            <p class="audiencia-card__reg">
                                Registrado por {{ a.registrado_por }} · {{ a.created_at }}
                            </p>
                        </div>

                        <!-- Acciones (solo secretario) -->
                        <div v-if="esSecretario && a.estado !== 'cancelada'" class="audiencia-card__acciones">
                            <Link :href="`/audiencias/${a.id}/edit`" class="action-btn action-btn--editar" title="Editar">
                                <Edit2 class="action-ico" />
                            </Link>
                            <button class="action-btn action-btn--cancelar" title="Cancelar audiencia" @click="abrirCancelar(a)">
                                <X class="action-ico" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal cancelar (JD024) -->
        <Teleport to="body">
            <div v-if="modalCancelar" class="modal-overlay" @click.self="modalCancelar = false">
                <div class="modal">
                    <div class="modal__head">
                        <h3 class="modal__titulo">Cancelar audiencia</h3>
                        <button class="modal__cerrar" @click="modalCancelar = false">
                            <X class="modal__cerrar-ico" />
                        </button>
                    </div>
                    <div class="modal__body">
                        <div class="alerta alerta--advertencia">
                            <AlertTriangle class="alerta__ico" />
                            <span>
                                Esta acción marcará la audiencia como <strong>cancelada</strong>
                                y notificará automáticamente al practicante asignado.
                            </span>
                        </div>
                        <div class="modal__detalle">
                            <p class="modal__label">Expediente</p>
                            <p class="modal__valor">{{ audienciaSelec?.numero_exp }} — {{ audienciaSelec?.cliente }}</p>
                            <p class="modal__label mt8">Fecha y hora</p>
                            <p class="modal__valor">{{ audienciaSelec?.fecha_display }} a las {{ audienciaSelec?.hora }}</p>
                            <p class="modal__label mt8">Sala / Juzgado</p>
                            <p class="modal__valor">{{ audienciaSelec?.sala_juzgado }}</p>
                        </div>
                    </div>
                    <div class="modal__footer">
                        <button class="btn-sec" @click="modalCancelar = false">Volver</button>
                        <button class="btn btn--rojo" @click="confirmarCancelar">
                            <X class="btn__ico" /> Confirmar cancelación
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:20px; width:100%; box-sizing:border-box; }
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub { font-size:13px; color:#64748B; margin:0; }
.db__accesos { display:flex; gap:8px; flex-wrap:wrap; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; border:none; cursor:pointer; font-family:'Poppins',sans-serif; transition:background .15s; white-space:nowrap; }
.btn:hover { background:#144d87; }
.btn--outline { background:transparent; color:#185FA5; border:1.5px solid #185FA5; }
.btn--outline:hover { background:#EFF6FF; }
.btn--rojo { background:#DC2626; }
.btn--rojo:hover { background:#b91c1c; }
.btn__ico { width:14px; height:14px; }
.btn-sec { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#F1F5F9; }

/* Filtros */
.filtros-panel { display:flex; gap:10px; flex-wrap:wrap; align-items:center; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.filtro-busqueda { position:relative; flex:1; min-width:220px; }
.filtro-busqueda__ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:#94A3B8; }
.filtro-busqueda__input { width:100%; padding:8px 12px 8px 32px; border:1px solid #CBD5E1; border-radius:7px; font-size:13px; font-family:inherit; color:#1E293B; background:#fff; box-sizing:border-box; }
.filtro-busqueda__input:focus { outline:none; border-color:#185FA5; box-shadow:0 0 0 3px #EFF6FF; }
.filtro-select { padding:8px 28px 8px 10px; border:1px solid #CBD5E1; border-radius:7px; font-size:13px; font-family:inherit; color:#1E293B; background:#fff; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 8px center; background-size:13px; }
.filtro-select:focus { outline:none; border-color:#185FA5; }
.btn-limpiar { display:inline-flex; align-items:center; gap:4px; padding:7px 12px; background:#FEE2E2; border:none; border-radius:7px; font-size:12px; font-weight:500; color:#991B1B; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn-limpiar__ico { width:13px; height:13px; }

/* Panel */
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.panel__head { margin-bottom:16px; }
.panel__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; display:flex; align-items:center; gap:8px; }
.contador { font-size:12px; font-weight:600; background:#EFF6FF; color:#185FA5; padding:2px 10px; border-radius:20px; }

/* Cards de audiencias */
.audiencias-lista { display:flex; flex-direction:column; gap:10px; }
.audiencia-card { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px; display:flex; gap:14px; align-items:flex-start; position:relative; transition:border-color .15s; }
.audiencia-card:hover { border-color:#185FA5; }
.audiencia-card--cancelada { opacity:.65; background:#F9FAFB; }
.audiencia-card--conflicto { border-color:#F59E0B; background:#FFFBEB; }

/* Tag de conflicto */
.conflicto-tag { position:absolute; top:-1px; right:80px; display:inline-flex; align-items:center; gap:4px; background:#F59E0B; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:0 0 6px 6px; text-transform:uppercase; letter-spacing:.04em; }
.conflicto-tag__ico { width:11px; height:11px; }

/* Bloque fecha */
.audiencia-card__fecha { display:flex; flex-direction:column; align-items:center; justify-content:center; width:50px; min-width:50px; background:#185FA5; border-radius:8px; padding:8px 4px; }
.fecha-dia  { font-size:22px; font-weight:700; color:#fff; line-height:1; }
.fecha-mes  { font-size:10px; font-weight:600; color:rgba(255,255,255,.8); text-transform:uppercase; }
.fecha-anio { font-size:9px; color:rgba(255,255,255,.6); }

/* Cuerpo */
.audiencia-card__body { flex:1; min-width:0; }
.audiencia-card__row1 { display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap; }
.audiencia-card__exp  { font-size:13px; font-weight:600; color:#1E293B; margin:0 0 6px; }
.audiencia-card__meta { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:4px; }
.meta-item { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#475569; }
.meta-ico  { width:13px; height:13px; color:#94A3B8; }
.audiencia-card__prac { font-size:12px; color:#64748B; margin:4px 0 0; }
.audiencia-card__obs  { font-size:12px; color:#64748B; font-style:italic; margin:4px 0 0; }
.audiencia-card__reg  { font-size:11px; color:#94A3B8; margin:6px 0 0; }

/* Acciones */
.audiencia-card__acciones { display:flex; flex-direction:column; gap:6px; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; border:none; cursor:pointer; transition:all .15s; background:#F1F5F9; text-decoration:none; }
.action-ico { width:13px; height:13px; }
.action-btn--editar  { color:#185FA5; } .action-btn--editar:hover  { background:#DBEAFE; }
.action-btn--cancelar{ color:#DC2626; } .action-btn--cancelar:hover{ background:#FEE2E2; }

/* Badges */
.tipo-tag { display:inline-flex; padding:2px 8px; background:#EFF6FF; color:#1D4ED8; border-radius:4px; font-size:11px; font-weight:600; white-space:nowrap; }
.estado-badge { display:inline-flex; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.badge--programada { background:#D1FAE5; color:#065F46; }
.badge--cancelada  { background:#FEE2E2; color:#991B1B; }

/* Empty */
.empty-state { text-align:center; padding:50px 20px; background:#F8FAFC; border-radius:10px; border:1px dashed #CBD5E1; }
.empty-state__icon { width:56px; height:56px; margin:0 auto 14px; background:#EFF6FF; border-radius:50%; display:flex; align-items:center; justify-content:center; }
.empty-state__svg  { width:28px; height:28px; color:#185FA5; }
.empty-state__title{ font-size:16px; font-weight:600; color:#1E293B; margin:0 0 6px; }
.empty-state__desc { font-size:13px; color:#64748B; margin:0; }

/* Alertas */
.alerta { display:flex; align-items:flex-start; gap:8px; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:500; border-left:4px solid currentColor; }
.alerta--exito       { background:#F0FDF4; color:#15803D; }
.alerta--advertencia { background:#FEF3C7; color:#92400E; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; margin-top:1px; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:999; padding:16px; }
.modal { background:#fff; border-radius:14px; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; }
.modal__head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #E2E8F0; }
.modal__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; }
.modal__cerrar { background:none; border:none; cursor:pointer; color:#94A3B8; display:flex; align-items:center; }
.modal__cerrar-ico { width:18px; height:18px; }
.modal__body { padding:20px; display:flex; flex-direction:column; gap:12px; }
.modal__detalle { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:14px; display:flex; flex-direction:column; gap:2px; }
.modal__label { font-size:11px; font-weight:600; color:#64748B; text-transform:uppercase; letter-spacing:.04em; margin:0; }
.modal__valor { font-size:13px; font-weight:500; color:#1E293B; margin:0 0 4px; }
.modal__footer { display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #E2E8F0; background:#F8FAFC; }
.mt8 { margin-top:8px !important; }

@media (max-width:640px) {
    .db__header { flex-direction:column; }
    .db__accesos { width:100%; }
    .btn { width:100%; justify-content:center; }
    .filtros-panel { flex-direction:column; }
    .filtro-busqueda { width:100%; }
    .filtro-select { width:100%; }
    .audiencia-card { flex-wrap:wrap; }
    .audiencia-card__acciones { flex-direction:row; }
}
</style>
