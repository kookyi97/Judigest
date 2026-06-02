<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import {
  FolderOpen, Calendar, Activity, Bell,
  CheckCircle, ChevronRight, Upload, Download
} from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const nombreUsuario = computed(() => page.props.auth?.usuario?.nombre ?? 'Practicante');

const props = defineProps({
  estadisticas: {
    type: Object,
    default: () => ({
      casosAsignados: 0,
      proximasAudiencias: 0,
      casosConActividad: 0,
      notificacionesNoLeidas: 0
    })
  },
  asesor:          { type: Object, default: null },
  casosAsignados:  { type: Array, default: () => [] },
  notificaciones:  { type: Array, default: () => [] }
});

const stats = computed(() => [
  {
    label: 'Casos asignados',
    valor: props.estadisticas.casosAsignados ?? 0,
    subtexto: 'expedientes activos',
    colorFondo: '#EFF6FF',
    colorIcono: '#185FA5',
    icono: FolderOpen
  },
  {
    label: 'Proximas audiencias',
    valor: props.estadisticas.proximasAudiencias ?? 0,
    subtexto: 'en tus casos',
    colorFondo: '#F0FDF4',
    colorIcono: '#16A34A',
    icono: Calendar
  },
  {
    label: 'Con actividad reciente',
    valor: props.estadisticas.casosConActividad ?? 0,
    subtexto: 'casos con cambios',
    colorFondo: '#FFF7ED',
    colorIcono: '#D97706',
    icono: Activity
  },
  {
    label: 'Notificaciones no leidas',
    valor: props.estadisticas.notificacionesNoLeidas ?? 0,
    subtexto: 'pendientes de revisar',
    colorFondo: '#FDF4FF',
    colorIcono: '#9333EA',
    icono: Bell
  }
]);

const colorEstado = {
  pendiente:  { bg: '#DBEAFE', txt: '#1E40AF' },
  en_proceso: { bg: '#FEF3C7', txt: '#92400E' },
  cerrado:    { bg: '#D1FAE5', txt: '#065F46' },
  urgente:    { bg: '#FEE2E2', txt: '#991B1B' }
};

// Icono por tipo de notificacion
const tipoNotif = {
  caso:       { color: '#EFF6FF', txtColor: '#185FA5' },
  audiencia:  { color: '#D1FAE5', txtColor: '#065F46' },
  estado:     { color: '#FEF3C7', txtColor: '#92400E' }
};
</script>

<template>
  <AppLayout>
    <div class="dashboard">

      <!-- Cabecera con saludo y asesor -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Bienvenido, {{ nombreUsuario }}</h1>
          <p class="page-sub" v-if="asesor">
            Supervisado por
            <strong class="asesor-nombre">{{ asesor.nombre }} {{ asesor.apellido }}</strong>
          </p>
          <p class="page-sub" v-else>Sin asesor asignado aun</p>
        </div>
        <a href="/casos" class="btn-primario">
          <FolderOpen class="btn-icon" /> Mis casos
        </a>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div v-for="stat in stats" :key="stat.label" class="stat-card">
          <div class="stat-card__icono" :style="{ background: stat.colorFondo }">
            <component :is="stat.icono" class="stat-icon" :style="{ color: stat.colorIcono }" />
          </div>
          <div class="stat-card__body">
            <p class="stat-card__label">{{ stat.label }}</p>
            <p class="stat-card__valor">{{ stat.valor }}</p>
            <p class="stat-card__sub">{{ stat.subtexto }}</p>
          </div>
        </div>
      </div>

      <!-- Bloque inferior -->
      <div class="bloque-inferior">

        <!-- Mis casos asignados -->
        <div class="panel">
          <div class="panel__header">
            <h2 class="panel__titulo">Mis casos asignados</h2>
            <a href="/casos" class="panel__link">Ver todos</a>
          </div>

          <!-- Aviso: el practicante no puede crear ni editar expedientes -->
          <div class="aviso-restriccion">
            Solo puedes consultar y gestionar documentos en los expedientes asignados a ti.
          </div>

          <div class="tabla-wrapper">
            <div class="tabla-head">
              <span>Expediente</span>
              <span>Tipo</span>
              <span>Estado</span>
              <span>Proxima audiencia</span>
              <span>Documentos</span>
            </div>

            <template v-if="casosAsignados && casosAsignados.length > 0">
              <div v-for="caso in casosAsignados" :key="caso.id" class="tabla-row">
                <div>
                  <p class="tabla-nombre">{{ caso.numero }}</p>
                  <p class="tabla-sub">{{ caso.nombre }}</p>
                </div>
                <span class="tipo-tag">{{ caso.tipo }}</span>
                <span
                  class="estado-badge"
                  :style="{ background: colorEstado[caso.estado]?.bg, color: colorEstado[caso.estado]?.txt }"
                >{{ caso.estadoLabel ?? caso.estado }}</span>
                <span class="tabla-fecha">{{ caso.proximaAudiencia ?? 'Sin programar' }}</span>
                <span class="tabla-txt">{{ caso.documentos ?? 0 }} archivo(s)</span>
              </div>
            </template>

            <!-- Placeholder -->
            <template v-else>
              <div v-for="n in 5" :key="n" class="tabla-row tabla-row--ph">
                <div>
                  <div class="ph ph--numero"></div>
                  <div class="ph ph--nombre"></div>
                </div>
                <div class="ph ph--tipo"></div>
                <div class="ph ph--estado"></div>
                <div class="ph ph--fecha"></div>
                <div class="ph ph--docs"></div>
              </div>
            </template>
          </div>

          <!-- Acciones permitidas al practicante -->
          <div class="acciones-fila">
            <a href="/casos" class="btn-secundario-sm">
              <ChevronRight class="btn-icon" /> Ver detalle del caso
            </a>
            <a href="/casos" class="btn-secundario-sm">Ver historial del expediente</a>
            <a href="/documentos" class="btn-secundario-sm">
              <Upload class="btn-icon" /> Subir documento
            </a>
            <a href="/casos" class="btn-secundario-sm">
              <Download class="btn-icon" /> Descargar historial
            </a>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Centro de notificaciones -->
          <div class="panel">
            <div class="panel__header">
              <h2 class="panel__titulo">Mis notificaciones</h2>
              <a href="/notificaciones" class="panel__link">Ver todas</a>
            </div>

            <div v-if="!notificaciones || notificaciones.length === 0" class="panel__vacio">
              <Bell class="vacio-icon" />
              <p>Sin notificaciones nuevas.</p>
            </div>

            <ul v-else class="notif-lista">
              <li v-for="(notif, i) in notificaciones" :key="i" class="notif-item" :class="{ 'notif-item--no-leida': !notif.leida }">
                <div
                  class="notif-dot"
                  :style="{
                    background: tipoNotif[notif.tipo]?.color ?? '#F1F5F9',
                    color: tipoNotif[notif.tipo]?.txtColor ?? '#64748B'
                  }"
                >
                  <Calendar v-if="notif.tipo === 'audiencia'" class="notif-icon-sm" />
                  <CheckCircle v-else-if="notif.tipo === 'estado'" class="notif-icon-sm" />
                  <FolderOpen v-else class="notif-icon-sm" />
                </div>
                <div class="notif-body">
                  <p class="notif-texto">{{ notif.mensaje }}</p>
                  <p class="notif-meta">{{ notif.fecha }}</p>
                </div>
                <span v-if="!notif.leida" class="notif-punto"></span>
              </li>
            </ul>

            <!-- Placeholder notificaciones -->
            <template v-if="!notificaciones || notificaciones.length === 0">
              <ul class="notif-lista">
                <li v-for="n in 4" :key="n" class="notif-item">
                  <div class="notif-dot notif-dot--ph"></div>
                  <div class="notif-body">
                    <div class="ph ph--notif-txt"></div>
                    <div class="ph ph--notif-meta"></div>
                  </div>
                </li>
              </ul>
            </template>

            <!-- Tipos de notificacion que recibe -->
            <div class="notif-tipos">
              <p class="notif-tipos__titulo">Tipos que recibes:</p>
              <div class="notif-tipo-tag" style="background:#EFF6FF; color:#185FA5">Nuevo caso asignado</div>
              <div class="notif-tipo-tag" style="background:#D1FAE5; color:#065F46">Audiencia registrada</div>
              <div class="notif-tipo-tag" style="background:#FEF3C7; color:#92400E">Cambio de estado</div>
            </div>
          </div>

          <!-- Mi calendario (vista rapida) -->
          <div class="panel">
            <div class="panel__header">
              <h2 class="panel__titulo">Mi calendario</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>

            <div class="calendario-aviso">
              <Calendar class="cal-icon" />
              <div>
                <p class="cal-valor">{{ estadisticas.proximasAudiencias ?? 0 }}</p>
                <p class="cal-sub">audiencia(s) proxima(s) en tus casos</p>
              </div>
            </div>
            <p class="cal-nota">Solo puedes ver audiencias de tus casos. El secretario es quien las registra.</p>
            <a href="/calendario" class="btn-bloque">Ver mi calendario</a>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.dashboard {
  --azul:  #185FA5;
  --texto: #1E293B;
  --fondo: #F8FAFC;
  --borde: #E2E8F0;
  --blanc: #FFFFFF;
  font-family: 'Poppins', 'Inter', sans-serif;
  color: var(--texto);
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Cabecera */
.page-header {
  display: flex; align-items: flex-start;
  justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.page-title { font-size: 22px; font-weight: 600; color: var(--texto); margin: 0 0 4px; }
.page-sub   { font-size: 13px; color: #64748B; margin: 0; }
.asesor-nombre { color: var(--azul); }

/* Botones */
.btn-primario {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 16px; background: var(--azul); color: #fff;
  border-radius: 8px; font-size: 13px; font-weight: 500;
  text-decoration: none; white-space: nowrap; transition: background .15s;
}
.btn-primario:hover { background: #144d87; }
.btn-secundario-sm {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px; background: var(--fondo);
  border: 1px solid var(--borde); border-radius: 7px;
  font-size: 12px; font-weight: 500; color: #475569;
  text-decoration: none; white-space: nowrap;
  font-family: 'Poppins', sans-serif; transition: background .15s;
}
.btn-secundario-sm:hover { background: #EFF6FF; color: var(--azul); }
.btn-bloque {
  display: flex; align-items: center; justify-content: center;
  gap: 6px; width: 100%; padding: 10px;
  background: var(--azul); color: #fff;
  border-radius: 8px; font-size: 13px; font-weight: 500;
  text-decoration: none; margin-top: 12px; transition: background .15s;
}
.btn-bloque:hover { background: #144d87; }
.btn-icon { width: 14px; height: 14px; }

/* Stats */
.stats-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
}
.stat-card {
  background: var(--blanc); border: 1px solid var(--borde);
  border-radius: 12px; padding: 20px;
  display: flex; align-items: flex-start; gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.stat-card__icono {
  width: 46px; height: 46px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-icon { width: 22px; height: 22px; }
.stat-card__body { flex: 1; }
.stat-card__label { font-size: 12px; color: #64748B; margin: 0 0 4px; }
.stat-card__valor { font-size: 28px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1.1; }
.stat-card__sub   { font-size: 11px; color: #94A3B8; margin: 0; }

/* Layout inferior */
.bloque-inferior {
  display: grid; grid-template-columns: 1fr 290px; gap: 16px; align-items: start;
}
.panel-derecho { display: flex; flex-direction: column; gap: 16px; }

/* Panel */
.panel {
  background: var(--blanc); border: 1px solid var(--borde);
  border-radius: 12px; padding: 22px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.panel__header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
}
.panel__titulo { font-size: 14px; font-weight: 600; color: var(--texto); margin: 0; }
.panel__link   { font-size: 12px; color: var(--azul); text-decoration: none; font-weight: 500; }
.panel__link:hover { text-decoration: underline; }
.panel__vacio  {
  display: flex; flex-direction: column; align-items: center;
  gap: 8px; padding: 16px 0; color: #94A3B8; font-size: 13px;
}
.vacio-icon { width: 26px; height: 26px; }

/* Aviso restriccion */
.aviso-restriccion {
  background: #FFF7ED;
  color: #92400E;
  border-left: 3px solid #D97706;
  padding: 9px 12px;
  border-radius: 6px;
  font-size: 12px;
  margin-bottom: 14px;
}

/* Tabla */
.tabla-wrapper { overflow-x: auto; }
.tabla-head {
  display: grid;
  grid-template-columns: 160px 90px 110px 140px 100px;
  gap: 8px; padding: 8px 10px; background: #F8FAFC;
  border-radius: 6px; font-size: 11px; font-weight: 600;
  color: #94A3B8; text-transform: uppercase; letter-spacing: .04em;
  margin-bottom: 4px; min-width: 600px;
}
.tabla-row {
  display: grid;
  grid-template-columns: 160px 90px 110px 140px 100px;
  gap: 8px; padding: 10px;
  border-bottom: 1px solid #F1F5F9; align-items: center; min-width: 600px;
}
.tabla-row:last-child { border-bottom: none; }
.tabla-row--ph { opacity: .6; }
.tabla-nombre { font-size: 12px; font-weight: 500; color: var(--texto); margin: 0 0 2px; }
.tabla-sub    { font-size: 11px; color: #94A3B8; margin: 0; }
.tabla-txt    { font-size: 12px; color: #475569; }
.tabla-fecha  { font-size: 11px; color: #94A3B8; }
.tipo-tag {
  font-size: 11px; background: #F1F5F9; color: #475569;
  padding: 3px 8px; border-radius: 4px; text-transform: capitalize;
  white-space: nowrap; display: inline-block;
}
.estado-badge {
  display: inline-flex; align-items: center;
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 500; white-space: nowrap;
}

/* Placeholders */
.ph { height: 10px; background: #F1F5F9; border-radius: 4px; display: block; }
.ph--numero  { width: 70px; margin-bottom: 5px; }
.ph--nombre  { width: 110px; }
.ph--tipo    { width: 55px; }
.ph--estado  { width: 75px; }
.ph--fecha   { width: 90px; }
.ph--docs    { width: 60px; }
.ph--notif-txt  { width: 130px; height: 10px; margin-bottom: 6px; }
.ph--notif-meta { width: 80px; height: 8px; }

/* Acciones */
.acciones-fila {
  display: flex; gap: 8px; flex-wrap: wrap;
  margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--borde);
}

/* Notificaciones */
.notif-lista { list-style: none; margin: 0; padding: 0; }
.notif-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 0; border-bottom: 1px solid #F1F5F9; position: relative;
}
.notif-item:last-child { border-bottom: none; }
.notif-item--no-leida { background: #FAFCFF; margin: 0 -22px; padding: 10px 22px; }
.notif-dot {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-top: 1px;
}
.notif-dot--ph { background: #F1F5F9; }
.notif-icon-sm { width: 13px; height: 13px; }
.notif-body { flex: 1; }
.notif-texto { font-size: 12px; color: #334155; margin: 0 0 2px; }
.notif-meta  { font-size: 11px; color: #94A3B8; margin: 0; }
.notif-punto {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--azul); flex-shrink: 0; margin-top: 5px;
}
.notif-tipos {
  margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--borde);
  display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
}
.notif-tipos__titulo { font-size: 11px; color: #94A3B8; margin: 0; width: 100%; }
.notif-tipo-tag {
  font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 500;
}

/* Calendario */
.calendario-aviso {
  display: flex; align-items: center; gap: 12px;
  padding: 12px; background: #F0FDF4; border-radius: 8px; margin-bottom: 8px;
}
.cal-icon  { width: 26px; height: 26px; color: #16A34A; flex-shrink: 0; }
.cal-valor { font-size: 24px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1; }
.cal-sub   { font-size: 12px; color: #64748B; margin: 0; }
.cal-nota  { font-size: 11px; color: #94A3B8; margin: 0; }

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid      { grid-template-columns: repeat(2, 1fr); }
  .bloque-inferior { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .stats-grid  { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; }
  .btn-primario { width: 100%; justify-content: center; }
}
</style>
