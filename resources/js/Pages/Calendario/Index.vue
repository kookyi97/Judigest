<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ChevronLeft, ChevronRight, Plus, Clock, MapPin, X, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    audiencias: { type: Array, default: () => [] },
    rolUsuario: { type: String, default: '' },
});

const MESES      = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS_CORTOS= ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

const vistaActual = ref('mes');

// Fecha de navegación: se inicializa con año y mes actuales, día 1
const hoy = new Date();
const anioNav = ref(hoy.getFullYear());
const mesNav  = ref(hoy.getMonth());     // 0-11
const diaNav  = ref(hoy.getDate());

// ── Helpers ───────────────────────────────────────────────────────────────
const pad = (n) => String(n).padStart(2, '0');

// "2025-09-25" a partir de año, mes(0-11), día
const toYMD = (a, m, d) => `${a}-${pad(m + 1)}-${pad(d)}`;

// Date → "YYYY-MM-DD"
const dateToYMD = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

const hoyYMD = dateToYMD(hoy);

// ── Navegación ────────────────────────────────────────────────────────────
const navAnterior = () => {
    if (vistaActual.value === 'mes') {
        if (mesNav.value === 0) { mesNav.value = 11; anioNav.value--; }
        else mesNav.value--;
    } else if (vistaActual.value === 'semana') {
        // retroceder 7 días
        const d = new Date(anioNav.value, mesNav.value, diaNav.value);
        d.setDate(d.getDate() - 7);
        anioNav.value = d.getFullYear();
        mesNav.value  = d.getMonth();
        diaNav.value  = d.getDate();
    } else {
        const d = new Date(anioNav.value, mesNav.value, diaNav.value);
        d.setDate(d.getDate() - 1);
        anioNav.value = d.getFullYear();
        mesNav.value  = d.getMonth();
        diaNav.value  = d.getDate();
    }
};

const navSiguiente = () => {
    if (vistaActual.value === 'mes') {
        if (mesNav.value === 11) { mesNav.value = 0; anioNav.value++; }
        else mesNav.value++;
    } else if (vistaActual.value === 'semana') {
        const d = new Date(anioNav.value, mesNav.value, diaNav.value);
        d.setDate(d.getDate() + 7);
        anioNav.value = d.getFullYear();
        mesNav.value  = d.getMonth();
        diaNav.value  = d.getDate();
    } else {
        const d = new Date(anioNav.value, mesNav.value, diaNav.value);
        d.setDate(d.getDate() + 1);
        anioNav.value = d.getFullYear();
        mesNav.value  = d.getMonth();
        diaNav.value  = d.getDate();
    }
};

const irAHoy = () => {
    anioNav.value = hoy.getFullYear();
    mesNav.value  = hoy.getMonth();
    diaNav.value  = hoy.getDate();
};

const tituloNav = computed(() => {
    if (vistaActual.value === 'mes') {
        return `${MESES[mesNav.value]} ${anioNav.value}`;
    }
    if (vistaActual.value === 'semana') {
        const ini = inicioSemana.value;
        const fin = new Date(ini); fin.setDate(fin.getDate() + 6);
        return `${ini.getDate()} ${MESES[ini.getMonth()].slice(0,3)} — ${fin.getDate()} ${MESES[fin.getMonth()].slice(0,3)} ${fin.getFullYear()}`;
    }
    return `${DIAS_CORTOS[new Date(anioNav.value, mesNav.value, diaNav.value).getDay()]} ${diaNav.value} de ${MESES[mesNav.value]} ${anioNav.value}`;
});

// ── Semana ────────────────────────────────────────────────────────────────
const inicioSemana = computed(() => {
    const d = new Date(anioNav.value, mesNav.value, diaNav.value);
    d.setDate(d.getDate() - d.getDay()); // domingo de la semana
    return d;
});

const diasSemana = computed(() => {
    const dias = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(inicioSemana.value);
        d.setDate(d.getDate() + i);
        dias.push(d);
    }
    return dias;
});

// ── Mes ───────────────────────────────────────────────────────────────────
const diasMes = computed(() => {
    const primero  = new Date(anioNav.value, mesNav.value, 1);
    const ultimo   = new Date(anioNav.value, mesNav.value + 1, 0);
    const celdas   = [];

    // días del mes anterior para completar la primera semana
    for (let i = 0; i < primero.getDay(); i++) {
        celdas.push({ fecha: new Date(anioNav.value, mesNav.value, i - primero.getDay() + 1), otro: true });
    }
    for (let d = 1; d <= ultimo.getDate(); d++) {
        celdas.push({ fecha: new Date(anioNav.value, mesNav.value, d), otro: false });
    }
    // completar hasta 42 celdas
    let sig = 1;
    while (celdas.length < 42) {
        celdas.push({ fecha: new Date(anioNav.value, mesNav.value + 1, sig++), otro: true });
    }
    return celdas;
});

// ── Audiencias indexadas por fecha ────────────────────────────────────────
const porFecha = computed(() => {
    const mapa = {};
    for (const a of props.audiencias) {
        if (!mapa[a.fecha]) mapa[a.fecha] = [];
        mapa[a.fecha].push(a);
    }
    return mapa;
});

const enFecha = (fechaObj) => porFecha.value[dateToYMD(fechaObj)] ?? [];

const hayConflicto = (fechaObj) => {
    const lista = enFecha(fechaObj);
    if (lista.length < 2) return false;
    const horas = lista.map(a => a.hora);
    return horas.length !== new Set(horas).size;
};

// ── Modal detalle ─────────────────────────────────────────────────────────
const modalDetalle = ref(false);
const audVer       = ref(null);
const verDetalle   = (a) => { audVer.value = a; modalDetalle.value = true; };

// ── Textos por rol ────────────────────────────────────────────────────────
const titulo = computed(() => {
    if (props.rolUsuario === 'secretario') return 'Calendario General del Área';
    if (props.rolUsuario === 'asesor')     return 'Calendario — Mis Casos';
    return 'Mi Calendario de Audiencias';
});
const subtitulo = computed(() => {
    if (props.rolUsuario === 'secretario') return 'Todas las audiencias registradas en el área';
    if (props.rolUsuario === 'asesor')     return 'Solo audiencias de expedientes bajo tu supervisión';
    return 'Solo audiencias de tus expedientes asignados';
});
const esSecretario = computed(() => props.rolUsuario === 'secretario');
</script>

<template>
    <Head title="Calendario de Audiencias" />
    <AppLayout>
        <div class="db">

            <!-- Cabecera -->
            <div class="db__header">
                <div>
                    <h1 class="db__titulo">{{ titulo }}</h1>
                    <p class="db__sub">{{ subtitulo }}</p>
                </div>
                <div class="db__accesos">
                    <Link href="/audiencias" class="btn btn--outline">Ver listado</Link>
                    <Link v-if="esSecretario" href="/audiencias/create" class="btn">
                        <Plus class="btn__ico" /> Registrar audiencia
                    </Link>
                </div>
            </div>

            <!-- Controles -->
            <div class="cal-controles">
                <div class="vista-tabs">
                    <button class="vista-tab" :class="{ 'vista-tab--activo': vistaActual === 'mes' }"    @click="vistaActual = 'mes'">Mes</button>
                    <button class="vista-tab" :class="{ 'vista-tab--activo': vistaActual === 'semana' }" @click="vistaActual = 'semana'">Semana</button>
                    <button class="vista-tab" :class="{ 'vista-tab--activo': vistaActual === 'dia' }"    @click="vistaActual = 'dia'">Día</button>
                </div>
                <div class="cal-nav">
                    <button class="cal-nav__btn" @click="navAnterior"><ChevronLeft class="cal-nav__ico" /></button>
                    <span class="cal-nav__titulo">{{ tituloNav }}</span>
                    <button class="cal-nav__btn" @click="navSiguiente"><ChevronRight class="cal-nav__ico" /></button>
                </div>
                <button class="btn-hoy" @click="irAHoy">Hoy</button>
            </div>

            <!-- VISTA MES -->
            <div v-if="vistaActual === 'mes'" class="panel">
                <div class="cal-mes__header">
                    <div v-for="d in DIAS_CORTOS" :key="d" class="cal-mes__dia-nombre">{{ d }}</div>
                </div>
                <div class="cal-mes__grid">
                    <div
                        v-for="(c, i) in diasMes" :key="i"
                        class="cal-celda"
                        :class="{
                            'cal-celda--otro':     c.otro,
                            'cal-celda--hoy':      dateToYMD(c.fecha) === hoyYMD,
                            'cal-celda--conflicto':!c.otro && hayConflicto(c.fecha),
                        }"
                    >
                        <span class="cal-celda__num"
                              :class="{ 'cal-celda__num--hoy': dateToYMD(c.fecha) === hoyYMD }">
                            {{ c.fecha.getDate() }}
                        </span>
                        <span v-if="!c.otro && hayConflicto(c.fecha)" class="conflicto-ico" title="Conflicto de horario">
                            <AlertTriangle class="conflicto-svg" />
                        </span>
                        <div v-for="a in enFecha(c.fecha)" :key="a.id" class="cal-evento" @click="verDetalle(a)">
                            <span class="cal-evento__hora">{{ a.hora }}</span>
                            <span class="cal-evento__tipo">{{ a.tipo_audiencia }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISTA SEMANA -->
            <div v-if="vistaActual === 'semana'" class="panel">
                <div class="cal-semana__grid">
                    <div
                        v-for="dia in diasSemana" :key="dateToYMD(dia)"
                        class="cal-semana__col"
                        :class="{
                            'cal-semana__col--hoy':      dateToYMD(dia) === hoyYMD,
                            'cal-semana__col--conflicto': hayConflicto(dia),
                        }"
                    >
                        <div class="cal-semana__header">
                            <span class="cal-semana__dia-nombre">{{ DIAS_CORTOS[dia.getDay()] }}</span>
                            <span class="cal-semana__dia-num"
                                  :class="{ 'cal-semana__dia-num--hoy': dateToYMD(dia) === hoyYMD }">
                                {{ dia.getDate() }}
                            </span>
                        </div>
                        <div class="cal-semana__eventos">
                            <div v-for="a in enFecha(dia)" :key="a.id" class="cal-evento-sem" @click="verDetalle(a)">
                                <p class="cal-evento-sem__hora">{{ a.hora }}</p>
                                <p class="cal-evento-sem__tipo">{{ a.tipo_audiencia }}</p>
                                <p class="cal-evento-sem__exp">{{ a.numero_exp }}</p>
                            </div>
                            <p v-if="enFecha(dia).length === 0" class="cal-semana__vacio">—</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISTA DÍA -->
            <div v-if="vistaActual === 'dia'" class="panel">
                <div class="cal-dia__header">
                    <h3 class="cal-dia__titulo">
                        {{ DIAS_CORTOS[new Date(anioNav, mesNav, diaNav).getDay()] }}
                        {{ diaNav }} de {{ MESES[mesNav] }} {{ anioNav }}
                    </h3>
                    <span v-if="hayConflicto(new Date(anioNav, mesNav, diaNav))" class="conflicto-badge">
                        <AlertTriangle class="conflicto-svg-sm" /> Conflicto de horario
                    </span>
                </div>
                <div v-if="enFecha(new Date(anioNav, mesNav, diaNav)).length === 0" class="empty-txt">
                    No hay audiencias programadas para este día.
                </div>
                <div v-else class="cal-dia__lista">
                    <div v-for="a in enFecha(new Date(anioNav, mesNav, diaNav))" :key="a.id"
                         class="cal-dia__item" @click="verDetalle(a)">
                        <div class="cal-dia__hora">{{ a.hora }}</div>
                        <div class="cal-dia__info">
                            <p class="cal-dia__tipo">{{ a.tipo_audiencia }} — {{ a.numero_exp }}</p>
                            <p class="cal-dia__meta"><MapPin class="meta-ico" /> {{ a.sala_juzgado }}</p>
                            <p class="cal-dia__prac" v-if="rolUsuario !== 'practicante'">{{ a.practicante }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal detalle -->
        <Teleport to="body">
            <div v-if="modalDetalle" class="modal-overlay" @click.self="modalDetalle = false">
                <div class="modal">
                    <div class="modal__head">
                        <h3 class="modal__titulo">Detalle de audiencia</h3>
                        <button class="modal__cerrar" @click="modalDetalle = false"><X class="modal__cerrar-ico" /></button>
                    </div>
                    <div class="modal__body">
                        <div class="detalle-grid">
                            <div class="detalle-fila"><span class="detalle-label">Expediente</span><span class="detalle-valor">{{ audVer?.numero_exp }} — {{ audVer?.cliente }}</span></div>
                            <div class="detalle-fila"><span class="detalle-label">Tipo</span><span class="detalle-valor">{{ audVer?.tipo_audiencia }}</span></div>
                            <div class="detalle-fila"><span class="detalle-label">Fecha</span><span class="detalle-valor">{{ audVer?.fecha }}</span></div>
                            <div class="detalle-fila"><span class="detalle-label">Hora</span><span class="detalle-valor">{{ audVer?.hora }}</span></div>
                            <div class="detalle-fila"><span class="detalle-label">Sala / Juzgado</span><span class="detalle-valor">{{ audVer?.sala_juzgado }}</span></div>
                            <div class="detalle-fila" v-if="rolUsuario !== 'practicante'"><span class="detalle-label">Practicante</span><span class="detalle-valor">{{ audVer?.practicante }}</span></div>
                            <div class="detalle-fila" v-if="rolUsuario === 'secretario' || rolUsuario === 'administrador'"><span class="detalle-label">Asesor</span><span class="detalle-valor">{{ audVer?.asesor }}</span></div>
                            <div class="detalle-fila" v-if="audVer?.observaciones"><span class="detalle-label">Observaciones</span><span class="detalle-valor">{{ audVer?.observaciones }}</span></div>
                        </div>
                    </div>
                    <div class="modal__footer">
                        <button class="btn-sec" @click="modalDetalle = false">Cerrar</button>
                        <Link v-if="esSecretario" :href="`/audiencias/${audVer?.id}/edit`" class="btn" @click="modalDetalle = false">Editar</Link>
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
.db__sub    { font-size:13px; color:#64748B; margin:0; }
.db__accesos{ display:flex; gap:8px; flex-wrap:wrap; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; border:none; cursor:pointer; font-family:'Poppins',sans-serif; transition:background .15s; white-space:nowrap; }
.btn:hover { background:#144d87; }
.btn--outline { background:transparent; color:#185FA5; border:1.5px solid #185FA5; }
.btn--outline:hover { background:#EFF6FF; }
.btn__ico { width:14px; height:14px; }
.btn-sec { display:inline-flex; align-items:center; padding:8px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; text-decoration:none; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#F1F5F9; }

/* Controles */
.cal-controles { display:flex; align-items:center; gap:12px; flex-wrap:wrap; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:12px 16px; }
.vista-tabs { display:flex; background:#F1F5F9; border-radius:8px; padding:2px; gap:2px; }
.vista-tab { padding:6px 16px; border:none; background:transparent; border-radius:6px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; font-family:'Poppins',sans-serif; transition:all .15s; }
.vista-tab--activo { background:#185FA5; color:#fff; }
.cal-nav { display:flex; align-items:center; gap:8px; }
.cal-nav__btn { display:flex; align-items:center; justify-content:center; width:30px; height:30px; border:1px solid #E2E8F0; border-radius:6px; background:#fff; cursor:pointer; transition:background .15s; }
.cal-nav__btn:hover { background:#EFF6FF; }
.cal-nav__ico { width:16px; height:16px; color:#475569; }
.cal-nav__titulo { font-size:14px; font-weight:600; color:#1E293B; min-width:180px; text-align:center; }
.btn-hoy { padding:6px 14px; border:1px solid #E2E8F0; border-radius:8px; background:#fff; font-size:13px; font-weight:500; color:#475569; cursor:pointer; font-family:'Poppins',sans-serif; margin-left:auto; }
.btn-hoy:hover { background:#EFF6FF; color:#185FA5; border-color:#185FA5; }

/* Panel */
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,.05); overflow:hidden; }

/* Vista Mes */
.cal-mes__header { display:grid; grid-template-columns:repeat(7,1fr); border-bottom:1px solid #E2E8F0; margin-bottom:2px; }
.cal-mes__dia-nombre { text-align:center; font-size:11px; font-weight:600; color:#64748B; text-transform:uppercase; padding:8px 0; }
.cal-mes__grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:#E2E8F0; }
.cal-celda { background:#fff; min-height:80px; padding:4px; position:relative; cursor:default; }
.cal-celda--otro { background:#F8FAFC; }
.cal-celda--otro .cal-celda__num { color:#CBD5E1; }
.cal-celda--hoy { background:#EFF6FF; }
.cal-celda--conflicto { background:#FFFBEB; }
.cal-celda__num { font-size:12px; font-weight:600; color:#374151; display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; margin-bottom:3px; }
.cal-celda__num--hoy { background:#185FA5; color:#fff; border-radius:50%; }
.conflicto-ico { position:absolute; top:3px; right:3px; color:#F59E0B; }
.conflicto-svg { width:12px; height:12px; }
.cal-evento { background:#185FA5; color:#fff; border-radius:3px; padding:2px 4px; margin-bottom:2px; cursor:pointer; display:flex; gap:3px; align-items:center; overflow:hidden; }
.cal-evento:hover { background:#144d87; }
.cal-evento__hora { font-size:9px; font-weight:700; opacity:.85; white-space:nowrap; }
.cal-evento__tipo { font-size:9px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Vista Semana */
.cal-semana__grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.cal-semana__col { border:1px solid #E2E8F0; border-radius:8px; overflow:hidden; }
.cal-semana__col--hoy { border-color:#185FA5; }
.cal-semana__col--conflicto { border-color:#F59E0B; }
.cal-semana__header { text-align:center; padding:8px 4px; border-bottom:1px solid #E2E8F0; background:#F8FAFC; }
.cal-semana__dia-nombre { display:block; font-size:10px; font-weight:600; color:#64748B; text-transform:uppercase; }
.cal-semana__dia-num { display:inline-flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#1E293B; width:28px; height:28px; border-radius:50%; }
.cal-semana__dia-num--hoy { background:#185FA5; color:#fff; }
.cal-semana__eventos { padding:4px; min-height:70px; display:flex; flex-direction:column; gap:3px; }
.cal-semana__vacio { font-size:12px; color:#CBD5E1; text-align:center; margin-top:8px; }
.cal-evento-sem { background:#EFF6FF; border-left:3px solid #185FA5; border-radius:4px; padding:4px 5px; cursor:pointer; }
.cal-evento-sem:hover { background:#DBEAFE; }
.cal-evento-sem__hora { font-size:10px; font-weight:700; color:#185FA5; margin:0; }
.cal-evento-sem__tipo { font-size:10px; font-weight:600; color:#1E293B; margin:0; }
.cal-evento-sem__exp  { font-size:9px; color:#64748B; margin:0; }

/* Vista Día */
.cal-dia__header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.cal-dia__titulo { font-size:16px; font-weight:600; color:#1E293B; margin:0; }
.conflicto-badge { display:inline-flex; align-items:center; gap:5px; background:#FEF3C7; color:#92400E; font-size:12px; font-weight:600; padding:4px 10px; border-radius:6px; }
.conflicto-svg-sm { width:13px; height:13px; }
.empty-txt { font-size:13px; color:#94A3B8; text-align:center; padding:30px 0; }
.cal-dia__lista { display:flex; flex-direction:column; gap:8px; }
.cal-dia__item { display:flex; gap:14px; align-items:flex-start; padding:12px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; cursor:pointer; transition:border-color .15s; }
.cal-dia__item:hover { border-color:#185FA5; background:#EFF6FF; }
.cal-dia__hora { font-size:15px; font-weight:700; color:#185FA5; min-width:48px; }
.cal-dia__info { flex:1; }
.cal-dia__tipo { font-size:13px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.cal-dia__meta { font-size:12px; color:#64748B; margin:0 0 2px; display:flex; align-items:center; gap:4px; }
.cal-dia__prac { font-size:12px; color:#94A3B8; margin:0; }
.meta-ico { width:13px; height:13px; color:#94A3B8; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:999; padding:16px; }
.modal { background:#fff; border-radius:14px; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; }
.modal__head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #E2E8F0; }
.modal__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; }
.modal__cerrar { background:none; border:none; cursor:pointer; color:#94A3B8; display:flex; align-items:center; }
.modal__cerrar-ico { width:18px; height:18px; }
.modal__body { padding:20px; }
.detalle-grid { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; overflow:hidden; }
.detalle-fila { display:flex; gap:12px; padding:10px 14px; border-bottom:1px solid #F1F5F9; }
.detalle-fila:last-child { border-bottom:none; }
.detalle-label { font-size:12px; font-weight:600; color:#64748B; min-width:100px; }
.detalle-valor { font-size:12px; color:#1E293B; }
.modal__footer { display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #E2E8F0; background:#F8FAFC; }

@media (max-width:900px) { .cal-semana__grid { grid-template-columns:repeat(4,1fr); } }
@media (max-width:640px) {
    .db__header { flex-direction:column; }
    .cal-controles { flex-direction:column; align-items:stretch; }
    .btn-hoy { margin-left:0; }
    .cal-semana__grid { grid-template-columns:repeat(2,1fr); }
}
</style>
