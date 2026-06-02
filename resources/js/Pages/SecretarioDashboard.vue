<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import {
  Calendar, FolderOpen, UserX, Bell,
  Plus, FileText, Clock, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
  estadisticas: {
    type: Object,
    default: () => ({
      audienciasEstaSemana: 0,
      expedientesAbiertos: 0,
      casosSinPracticante: 0,
      notificacionesPendientes: 0
    })
  },
  ultimosExpedientes:  { type: Array, default: () => [] },
  proximasAudiencias:  { type: Array, default: () => [] }
});

const stats = computed(() => [
  {
    label: 'Audiencias esta semana',
    valor: props.estadisticas.audienciasEstaSemana ?? 0,
    subtexto: 'programadas',
    colorFondo: '#EFF6FF',
    colorIcono: '#185FA5',
    icono: Calendar
  },
  {
    label: 'Expedientes abiertos',
    valor: props.estadisticas.expedientesAbiertos ?? 0,
    subtexto: 'en el area juridica',
    colorFondo: '#F0FDF4',
    colorIcono: '#16A34A',
    icono: FolderOpen
  },
  {
    label: 'Sin practicante asignado',
    valor: props.estadisticas.casosSinPracticante ?? 0,
    subtexto: 'casos sin asignar',
    colorFondo: '#FFF7ED',
    colorIcono: '#D97706',
    icono: UserX
  },
  {
    label: 'Notificaciones pendientes',
    valor: props.estadisticas.notificacionesPendientes ?? 0,
    subtexto: 'por enviar',
    colorFondo: '#FDF4FF',
    colorIcono: '#9333EA',
    icono: Bell
  }
]);

// Colores de estado segun paleta del documento
const colorEstado = {
  pendiente:   { bg: '#DBEAFE', txt: '#1E40AF' },
  en_proceso:  { bg: '#FEF3C7', txt: '#92400E' },
  cerrado:     { bg: '#D1FAE5', txt: '#065F46' },
  urgente:     { bg: '#FEE2E2', txt: '#991B1B' }
};

const etiquetaEstado = {
  pendiente:  'Pendiente',
  en_proceso: 'En proceso',
  cerrado:    'Cerrado',
  urgente:    'Urgente'
};
</script>

<template>
  <AppLayout>
    <div class="dashboard">

      <!-- Cabecera -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard Operativo</h1>
          <p class="page-sub">Resumen de actividad del area juridica</p>
        </div>
        <a href="/audiencias/nueva" class="btn-primario">
          <Plus class="btn-icon" />
          Registrar audiencia
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

        <!-- Ultimos expedientes modificados -->
        <div class="panel">
          <div class="panel__header">
            <h2 class="panel__titulo">Ultimos expedientes modificados</h2>
            <a href="/expedientes" class="panel__link">Ver todos</a>
          </div>

          <!-- Tabla de expedientes -->
          <div class="tabla-wrapper">
            <div class="tabla-head">
              <span>Expediente</span>
              <span>Tipo</span>
              <span>Estado</span>
              <span>Asesor</span>
              <span>Practicante</span>
              <span>Modificado</span>
            </div>

            <!-- Filas reales si hay datos -->
            <template v-if="ultimosExpedientes && ultimosExpedientes.length > 0">
              <div v-for="exp in ultimosExpedientes" :key="exp.id" class="tabla-row">
                <div>
                  <p class="tabla-nombre">{{ exp.numero }}</p>
                  <p class="tabla-sub">{{ exp.nombre }}</p>
                </div>
                <span class="tipo-tag">{{ exp.tipo }}</span>
                <span
                  class="estado-badge"
                  :style="{ background: colorEstado[exp.estado]?.bg, color: colorEstado[exp.estado]?.txt }"
                >{{ etiquetaEstado[exp.estado] ?? exp.estado }}</span>
                <span class="tabla-txt">{{ exp.asesor }}</span>
                <span class="tabla-txt">{{ exp.practicante ?? '—' }}</span>
                <span class="tabla-fecha">{{ exp.modificado }}</span>
              </div>
            </template>

            <!-- Filas placeholder -->
            <template v-else>
              <div v-for="n in 6" :key="n" class="tabla-row tabla-row--ph">
                <div>
                  <div class="ph ph--numero"></div>
                  <div class="ph ph--nombre"></div>
                </div>
                <div class="ph ph--tipo"></div>
                <div class="ph ph--estado"></div>
                <div class="ph ph--asesor"></div>
                <div class="ph ph--prac"></div>
                <div class="ph ph--fecha"></div>
              </div>
            </template>
          </div>

          <!-- Acciones del modulo -->
          <div class="acciones-fila">
            <a href="/expedientes/nuevo" class="btn-primario-sm">
              <Plus class="btn-icon" /> Crear expediente
            </a>
            <a href="/expedientes" class="btn-secundario-sm">Editar expediente</a>
            <a href="/expedientes" class="btn-secundario-sm">Cambiar estado</a>
            <a href="/expedientes" class="btn-secundario-sm">Archivar caso</a>
            <a href="/expedientes" class="btn-secundario-sm">Exportar a Excel</a>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Proximas audiencias -->
          <div class="panel">
            <div class="panel__header">
              <h2 class="panel__titulo">Proximas audiencias</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>

            <div v-if="!proximasAudiencias || proximasAudiencias.length === 0" class="panel__vacio">
              <Calendar class="vacio-icon" />
              <p>No hay audiencias proximas.</p>
            </div>

            <ul v-else class="audiencias-lista">
              <li v-for="(aud, i) in proximasAudiencias" :key="i" class="audiencia-item">
                <div class="aud-fecha">
                  <span class="aud-dia">{{ aud.dia }}</span>
                  <span class="aud-mes">{{ aud.mes }}</span>
                </div>
                <div class="aud-info">
                  <p class="aud-exp">{{ aud.expediente }}</p>
                  <p class="aud-det">{{ aud.hora }} · {{ aud.sala }}</p>
                  <p class="aud-prac">{{ aud.practicante }}</p>
                </div>
                <ChevronRight class="aud-arrow" />
              </li>
            </ul>

            <!-- placeholder -->
            <template v-if="!proximasAudiencias || proximasAudiencias.length === 0">
              <ul class="audiencias-lista">
                <li v-for="n in 4" :key="n" class="audiencia-item">
                  <div class="aud-fecha aud-fecha--ph">
                    <div class="ph ph--dia"></div>
                    <div class="ph ph--mes"></div>
                  </div>
                  <div class="aud-info">
                    <div class="ph ph--aud-exp"></div>
                    <div class="ph ph--aud-det"></div>
                  </div>
                </li>
              </ul>
            </template>

            <a href="/audiencias/nueva" class="btn-bloque">
              <Plus class="btn-icon" /> Registrar nueva audiencia
            </a>
          </div>

          <!-- Notificaciones pendientes -->
          <div class="panel panel--notif">
            <div class="panel__header">
              <h2 class="panel__titulo">Notificaciones pendientes</h2>
            </div>
            <div class="notif-info">
              <Bell class="notif-icon" />
              <div>
                <p class="notif-valor">{{ estadisticas.notificacionesPendientes ?? 0 }}</p>
                <p class="notif-sub">practicantes por notificar</p>
              </div>
            </div>
            <a href="/notificaciones" class="btn-bloque btn-bloque--outline">Ver notificaciones</a>
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
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.page-title { font-size: 22px; font-weight: 600; color: var(--texto); margin: 0 0 4px; }
.page-sub   { font-size: 13px; color: #64748B; margin: 0; }

/* Botones */
.btn-primario {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  background: var(--azul);
  color: #fff;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s;
}
.btn-primario:hover { background: #144d87; }
.btn-primario-sm {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  background: var(--azul);
  color: #fff;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s;
}
.btn-primario-sm:hover { background: #144d87; }
.btn-secundario-sm {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  background: var(--fondo);
  border: 1px solid var(--borde);
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s;
}
.btn-secundario-sm:hover { background: #EFF6FF; color: var(--azul); }
.btn-bloque {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 10px;
  background: var(--azul);
  color: #fff;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  margin-top: 14px;
  transition: background .15s;
}
.btn-bloque:hover { background: #144d87; }
.btn-bloque--outline {
  background: transparent;
  color: var(--azul);
  border: 1px solid var(--azul);
}
.btn-bloque--outline:hover { background: #EFF6FF; }
.btn-icon { width: 14px; height: 14px; }

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.stat-card {
  background: var(--blanc);
  border: 1px solid var(--borde);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.stat-card__icono {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon { width: 22px; height: 22px; }
.stat-card__body { flex: 1; }
.stat-card__label { font-size: 12px; color: #64748B; margin: 0 0 4px; }
.stat-card__valor { font-size: 28px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1.1; }
.stat-card__sub   { font-size: 11px; color: #94A3B8; margin: 0; }

/* Bloque inferior */
.bloque-inferior {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 16px;
  align-items: start;
}
.panel-derecho { display: flex; flex-direction: column; gap: 16px; }

/* Panel */
.panel {
  background: var(--blanc);
  border: 1px solid var(--borde);
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.panel__titulo { font-size: 14px; font-weight: 600; color: var(--texto); margin: 0; }
.panel__link   { font-size: 12px; color: var(--azul); text-decoration: none; font-weight: 500; }
.panel__link:hover { text-decoration: underline; }
.panel__vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 0;
  color: #94A3B8;
  font-size: 13px;
}
.vacio-icon { width: 26px; height: 26px; }

/* Tabla expedientes */
.tabla-wrapper { overflow-x: auto; }
.tabla-head {
  display: grid;
  grid-template-columns: 160px 90px 110px 130px 130px 100px;
  gap: 8px;
  padding: 8px 10px;
  background: #F8FAFC;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #94A3B8;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 4px;
  min-width: 720px;
}
.tabla-row {
  display: grid;
  grid-template-columns: 160px 90px 110px 130px 130px 100px;
  gap: 8px;
  padding: 10px;
  border-bottom: 1px solid #F1F5F9;
  align-items: center;
  min-width: 720px;
}
.tabla-row:last-child { border-bottom: none; }
.tabla-nombre { font-size: 12px; font-weight: 500; color: var(--texto); margin: 0 0 2px; }
.tabla-sub    { font-size: 11px; color: #94A3B8; margin: 0; }
.tabla-txt    { font-size: 12px; color: #475569; }
.tabla-fecha  { font-size: 11px; color: #94A3B8; white-space: nowrap; }
.tipo-tag {
  font-size: 11px;
  background: #F1F5F9;
  color: #475569;
  padding: 3px 8px;
  border-radius: 4px;
  text-transform: capitalize;
  white-space: nowrap;
  display: inline-block;
}
.estado-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
}

/* Placeholders */
.tabla-row--ph { opacity: .6; }
.ph {
  height: 10px;
  background: #F1F5F9;
  border-radius: 4px;
  display: block;
}
.ph--numero  { width: 70px; margin-bottom: 5px; }
.ph--nombre  { width: 110px; }
.ph--tipo    { width: 60px; }
.ph--estado  { width: 75px; }
.ph--asesor  { width: 100px; }
.ph--prac    { width: 100px; }
.ph--fecha   { width: 70px; }
.ph--dia     { width: 26px; height: 18px; margin-bottom: 4px; }
.ph--mes     { width: 20px; height: 8px; }
.ph--aud-exp { width: 110px; height: 10px; margin-bottom: 6px; }
.ph--aud-det { width: 80px; height: 8px; }

/* Acciones */
.acciones-fila {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--borde);
}

/* Audiencias */
.audiencias-lista { list-style: none; margin: 0; padding: 0; }
.audiencia-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid #F1F5F9;
}
.audiencia-item:last-child { border-bottom: none; }
.aud-fecha {
  width: 42px;
  height: 46px;
  background: #EFF6FF;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.aud-fecha--ph { opacity: .5; }
.aud-dia  { font-size: 17px; font-weight: 700; color: var(--azul); line-height: 1; }
.aud-mes  { font-size: 9px; font-weight: 500; color: #64748B; text-transform: uppercase; }
.aud-info { flex: 1; }
.aud-exp  { font-size: 12px; font-weight: 500; color: var(--texto); margin: 0 0 2px; }
.aud-det  { font-size: 11px; color: #64748B; margin: 0 0 2px; }
.aud-prac { font-size: 11px; color: #94A3B8; margin: 0; }
.aud-arrow { width: 14px; height: 14px; color: #94A3B8; flex-shrink: 0; }

/* Notificaciones panel */
.panel--notif .notif-info {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  background: #F8FAFC;
  border-radius: 8px;
}
.notif-icon  { width: 28px; height: 28px; color: var(--azul); flex-shrink: 0; }
.notif-valor { font-size: 26px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1; }
.notif-sub   { font-size: 12px; color: #64748B; margin: 0; }

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
