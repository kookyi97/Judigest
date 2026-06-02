<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import {
  FolderOpen, Users, Clock, FileText,
  Calendar, AlertTriangle, ChevronRight, Eye
} from 'lucide-vue-next';

const props = defineProps({
  estadisticas: {
    type: Object,
    default: () => ({
      casosActivos: 0,
      practicantesAsignados: 0,
      casosSinActividad: 0,
      documentosPendientes: 0
    })
  },
  proximasAudiencias: { type: Array, default: () => [] },
  practicantes:       { type: Array, default: () => [] }
});

const stats = computed(() => [
  {
    label: 'Casos activos a cargo',
    valor: props.estadisticas.casosActivos ?? 0,
    subtexto: 'bajo tu supervision',
    colorFondo: '#EFF6FF',
    colorIcono: '#185FA5',
    icono: FolderOpen
  },
  {
    label: 'Practicantes asignados',
    valor: props.estadisticas.practicantesAsignados ?? 0,
    subtexto: 'en tu equipo',
    colorFondo: '#F0FDF4',
    colorIcono: '#16A34A',
    icono: Users
  },
  {
    label: 'Sin actividad reciente',
    valor: props.estadisticas.casosSinActividad ?? 0,
    subtexto: 'casos sin movimiento',
    colorFondo: '#FFF7ED',
    colorIcono: '#D97706',
    icono: Clock
  },
  {
    label: 'Documentos por revisar',
    valor: props.estadisticas.documentosPendientes ?? 0,
    subtexto: 'pendientes de revision',
    colorFondo: '#FDF4FF',
    colorIcono: '#9333EA',
    icono: FileText
  }
]);

const colorEstado = {
  pendiente:  { bg: '#DBEAFE', txt: '#1E40AF' },
  en_proceso: { bg: '#FEF3C7', txt: '#92400E' },
  cerrado:    { bg: '#D1FAE5', txt: '#065F46' },
  urgente:    { bg: '#FEE2E2', txt: '#991B1B' }
};

// Carga de trabajo: baja 1-2, media 3-4, alta 5+
const claseCarga = (n) => {
  if (n >= 5) return 'carga--alta';
  if (n >= 3) return 'carga--media';
  return 'carga--baja';
};
</script>

<template>
  <AppLayout>
    <div class="dashboard">

      <!-- Cabecera -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard Asesor</h1>
          <p class="page-sub">Estado de tus casos y practicantes asignados</p>
        </div>
      </div>

      <!-- Alerta de documentos pendientes -->
      <div v-if="estadisticas.documentosPendientes > 0" class="alerta-docs">
        <AlertTriangle class="alerta-icon" />
        <span>Tienes <strong>{{ estadisticas.documentosPendientes }}</strong> documento(s) pendiente(s) de revision.</span>
        <a href="/documentos" class="alerta-link">Revisar ahora</a>
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

        <!-- Mis casos -->
        <div class="panel">
          <div class="panel__header">
            <h2 class="panel__titulo">Mis casos asignados</h2>
            <a href="/casos" class="panel__link">Ver todos</a>
          </div>

          <div class="tabla-wrapper">
            <div class="tabla-head">
              <span>Expediente</span>
              <span>Tipo</span>
              <span>Estado</span>
              <span>Practicante</span>
              <span>Proxima audiencia</span>
              <span>Ultima modificacion</span>
            </div>

            <template v-if="proximasAudiencias && proximasAudiencias.length > 0">
              <div v-for="aud in proximasAudiencias" :key="aud.id" class="tabla-row">
                <div>
                  <p class="tabla-nombre">{{ aud.expediente }}</p>
                  <p class="tabla-sub">{{ aud.tipo }}</p>
                </div>
                <span class="tipo-tag">{{ aud.tipoLabel ?? '—' }}</span>
                <span
                  class="estado-badge"
                  :style="{ background: colorEstado[aud.estado]?.bg, color: colorEstado[aud.estado]?.txt }"
                >{{ aud.estadoLabel ?? '—' }}</span>
                <span class="tabla-txt">{{ aud.practicante }}</span>
                <span class="tabla-fecha">{{ aud.fecha }}</span>
                <span class="tabla-fecha">{{ aud.ultimaMod ?? '—' }}</span>
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
                <div class="ph ph--prac"></div>
                <div class="ph ph--fecha"></div>
                <div class="ph ph--fecha"></div>
              </div>
            </template>
          </div>

          <!-- Acciones -->
          <div class="acciones-fila">
            <a href="/casos" class="btn-secundario-sm">
              <Eye class="btn-icon" /> Ver detalle del expediente
            </a>
            <a href="/casos" class="btn-secundario-sm">Ver historial de cambios</a>
            <a href="/casos" class="btn-secundario-sm">Filtrar por estado o practicante</a>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Mis practicantes -->
          <div class="panel">
            <div class="panel__header">
              <h2 class="panel__titulo">Mis practicantes</h2>
              <a href="/asignacion" class="panel__link">Gestionar</a>
            </div>

            <div v-if="!practicantes || practicantes.length === 0" class="panel__vacio">
              <Users class="vacio-icon" />
              <p>Sin practicantes asignados.</p>
            </div>

            <ul v-else class="practicantes-lista">
              <li v-for="prac in practicantes" :key="prac.id" class="practicante-item">
                <div class="prac-avatar">{{ prac.iniciales ?? '?' }}</div>
                <div class="prac-info">
                  <p class="prac-nombre">{{ prac.nombre }}</p>
                  <p class="prac-casos">{{ prac.casosActivos ?? 0 }} caso(s) activo(s)</p>
                </div>
                <div class="carga-badge" :class="claseCarga(prac.casosActivos ?? 0)">
                  {{ prac.casosActivos ?? 0 }}
                </div>
              </li>
            </ul>

            <!-- placeholder -->
            <template v-if="!practicantes || practicantes.length === 0">
              <ul class="practicantes-lista">
                <li v-for="n in 3" :key="n" class="practicante-item">
                  <div class="prac-avatar prac-avatar--ph"></div>
                  <div class="prac-info">
                    <div class="ph ph--prac-nombre"></div>
                    <div class="ph ph--prac-casos"></div>
                  </div>
                </li>
              </ul>
            </template>

            <div class="asignacion-acciones">
              <a href="/asignacion" class="btn-bloque">Asignar practicante a caso</a>
            </div>
          </div>

          <!-- Calendario de audiencias -->
          <div class="panel">
            <div class="panel__header">
              <h2 class="panel__titulo">Proximas audiencias</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>

            <ul class="audiencias-mini">
              <template v-if="proximasAudiencias && proximasAudiencias.length > 0">
                <li v-for="(aud, i) in proximasAudiencias.slice(0, 4)" :key="i" class="aud-mini-item">
                  <div class="aud-mini-fecha">
                    <span class="aud-dia">{{ aud.dia ?? '—' }}</span>
                    <span class="aud-mes">{{ aud.mes ?? '' }}</span>
                  </div>
                  <div class="aud-mini-info">
                    <p class="aud-exp">{{ aud.expediente }}</p>
                    <p class="aud-det">{{ aud.practicante }} · {{ aud.sala ?? '' }}</p>
                  </div>
                  <ChevronRight class="aud-arrow" />
                </li>
              </template>
              <template v-else>
                <li v-for="n in 3" :key="n" class="aud-mini-item">
                  <div class="aud-mini-fecha aud-mini-fecha--ph">
                    <div class="ph ph--dia"></div>
                    <div class="ph ph--mes"></div>
                  </div>
                  <div class="aud-mini-info">
                    <div class="ph ph--aud-exp"></div>
                    <div class="ph ph--aud-det"></div>
                  </div>
                </li>
              </template>
            </ul>
          </div>

          <!-- Revision de documentos -->
          <div class="panel panel--docs">
            <div class="panel__header">
              <h2 class="panel__titulo">Revision de documentos</h2>
              <a href="/documentos" class="panel__link">Ver todos</a>
            </div>
            <div class="docs-resumen">
              <FileText class="docs-icon" />
              <div>
                <p class="docs-valor">{{ estadisticas.documentosPendientes ?? 0 }}</p>
                <p class="docs-sub">documentos por revisar de mis casos</p>
              </div>
            </div>
            <a href="/documentos" class="btn-bloque btn-bloque--outline">Ver documentos</a>
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
.page-header { margin-bottom: 4px; }
.page-title { font-size: 22px; font-weight: 600; color: var(--texto); margin: 0 0 4px; }
.page-sub   { font-size: 13px; color: #64748B; margin: 0; }

/* Alerta documentos */
.alerta-docs {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  background: #FEF3C7;
  color: #92400E;
  border-radius: 8px;
  font-size: 13px;
  border-left: 3px solid #D97706;
  flex-wrap: wrap;
}
.alerta-icon { width: 16px; height: 16px; flex-shrink: 0; }
.alerta-link { margin-left: auto; color: #185FA5; font-weight: 600; text-decoration: none; }
.alerta-link:hover { text-decoration: underline; }

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
  width: 46px; height: 46px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.stat-icon { width: 22px; height: 22px; }
.stat-card__body { flex: 1; }
.stat-card__label { font-size: 12px; color: #64748B; margin: 0 0 4px; }
.stat-card__valor { font-size: 28px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1.1; }
.stat-card__sub   { font-size: 11px; color: #94A3B8; margin: 0; }

/* Layout inferior */
.bloque-inferior {
  display: grid;
  grid-template-columns: 1fr 290px;
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
.panel__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.panel__titulo { font-size: 14px; font-weight: 600; color: var(--texto); margin: 0; }
.panel__link   { font-size: 12px; color: var(--azul); text-decoration: none; font-weight: 500; }
.panel__link:hover { text-decoration: underline; }
.panel__vacio  { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px 0; color: #94A3B8; font-size: 13px; }
.vacio-icon    { width: 26px; height: 26px; }

/* Tabla */
.tabla-wrapper { overflow-x: auto; }
.tabla-head {
  display: grid;
  grid-template-columns: 150px 80px 110px 130px 130px 130px;
  gap: 8px;
  padding: 8px 10px;
  background: #F8FAFC;
  border-radius: 6px;
  font-size: 11px; font-weight: 600; color: #94A3B8;
  text-transform: uppercase; letter-spacing: .04em;
  margin-bottom: 4px;
  min-width: 700px;
}
.tabla-row {
  display: grid;
  grid-template-columns: 150px 80px 110px 130px 130px 130px;
  gap: 8px;
  padding: 10px;
  border-bottom: 1px solid #F1F5F9;
  align-items: center;
  min-width: 700px;
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
.ph--prac    { width: 100px; }
.ph--fecha   { width: 80px; }
.ph--dia     { width: 26px; height: 18px; margin-bottom: 4px; }
.ph--mes     { width: 20px; height: 8px; }
.ph--aud-exp { width: 110px; height: 10px; margin-bottom: 6px; }
.ph--aud-det { width: 80px; height: 8px; }
.ph--prac-nombre { width: 120px; height: 10px; margin-bottom: 6px; }
.ph--prac-casos  { width: 80px; height: 8px; }

/* Acciones */
.acciones-fila {
  display: flex; gap: 8px; flex-wrap: wrap;
  margin-top: 16px; padding-top: 16px;
  border-top: 1px solid var(--borde);
}
.btn-secundario-sm {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px;
  background: var(--fondo); border: 1px solid var(--borde);
  border-radius: 7px; font-size: 12px; font-weight: 500;
  color: #475569; text-decoration: none; white-space: nowrap;
  font-family: 'Poppins', sans-serif;
  transition: background .15s;
}
.btn-secundario-sm:hover { background: #EFF6FF; color: var(--azul); }
.btn-icon { width: 13px; height: 13px; }

/* Practicantes */
.practicantes-lista { list-style: none; margin: 0; padding: 0; }
.practicante-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 0; border-bottom: 1px solid #F1F5F9;
}
.practicante-item:last-child { border-bottom: none; }
.prac-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: var(--azul); color: #fff;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; text-transform: uppercase;
}
.prac-avatar--ph { background: #F1F5F9; }
.prac-info { flex: 1; }
.prac-nombre { font-size: 12px; font-weight: 500; color: var(--texto); margin: 0 0 2px; }
.prac-casos  { font-size: 11px; color: #94A3B8; margin: 0; }
.carga-badge {
  width: 24px; height: 24px; border-radius: 50%;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.carga--baja  { background: #D1FAE5; color: #065F46; }
.carga--media { background: #FEF3C7; color: #92400E; }
.carga--alta  { background: #FEE2E2; color: #991B1B; }
.asignacion-acciones { margin-top: 14px; }

/* Audiencias mini */
.audiencias-mini { list-style: none; margin: 0; padding: 0; }
.aud-mini-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 0; border-bottom: 1px solid #F1F5F9;
}
.aud-mini-item:last-child { border-bottom: none; }
.aud-mini-fecha {
  width: 40px; height: 44px;
  background: #EFF6FF; border-radius: 8px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.aud-mini-fecha--ph { opacity: .4; }
.aud-dia  { font-size: 15px; font-weight: 700; color: var(--azul); line-height: 1; }
.aud-mes  { font-size: 9px; font-weight: 500; color: #64748B; text-transform: uppercase; }
.aud-mini-info { flex: 1; }
.aud-exp  { font-size: 12px; font-weight: 500; color: var(--texto); margin: 0 0 2px; }
.aud-det  { font-size: 11px; color: #64748B; margin: 0; }
.aud-arrow { width: 14px; height: 14px; color: #94A3B8; flex-shrink: 0; }

/* Documentos */
.panel--docs .docs-resumen {
  display: flex; align-items: center; gap: 12px;
  padding: 12px; background: #F8FAFC; border-radius: 8px;
}
.docs-icon  { width: 26px; height: 26px; color: #9333EA; flex-shrink: 0; }
.docs-valor { font-size: 24px; font-weight: 700; color: var(--texto); margin: 0 0 2px; line-height: 1; }
.docs-sub   { font-size: 12px; color: #64748B; margin: 0; }

/* Botones */
.btn-bloque {
  display: flex; align-items: center; justify-content: center;
  gap: 6px; width: 100%; padding: 10px;
  background: var(--azul); color: #fff;
  border-radius: 8px; font-size: 13px; font-weight: 500;
  text-decoration: none; margin-top: 14px; transition: background .15s;
}
.btn-bloque:hover { background: #144d87; }
.btn-bloque--outline {
  background: transparent; color: var(--azul);
  border: 1px solid var(--azul);
}
.btn-bloque--outline:hover { background: #EFF6FF; }

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid      { grid-template-columns: repeat(2, 1fr); }
  .bloque-inferior { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
