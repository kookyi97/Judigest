<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import {
  FileText, Users, Calendar, Activity,
  LayoutDashboard, AlertTriangle, CheckCircle,
  Clock, Download, Filter
} from 'lucide-vue-next';

const props = defineProps({
  estadisticas: {
    type: Object,
    default: () => ({
      expedientesActivos: 0,
      usuariosActivos: 0,
      audienciasEsteMes: 0,
      accionesHoy: 0
    })
  },
  ultimasActividades: { type: Array, default: () => [] },
  usuariosPorRol:     { type: Array, default: () => [] },
  alertas:            { type: Array, default: () => [] }
});

// Datos de ejemplo para mostrar la interfaz completa
const stats = computed(() => [
  {
    label: 'Expedientes activos',
    valor: props.estadisticas.expedientesActivos ?? 0,
    subtexto: 'en el sistema actualmente',
    colorFondo: '#EFF6FF',
    colorIcono: '#185FA5',
    icono: FileText
  },
  {
    label: 'Usuarios activos',
    valor: props.estadisticas.usuariosActivos ?? 0,
    subtexto: 'cuentas habilitadas',
    colorFondo: '#F0FDF4',
    colorIcono: '#16A34A',
    icono: Users
  },
  {
    label: 'Audiencias este mes',
    valor: props.estadisticas.audienciasEsteMes ?? 0,
    subtexto: 'registradas en el calendario',
    colorFondo: '#FFF7ED',
    colorIcono: '#D97706',
    icono: Calendar
  },
  {
    label: 'Acciones registradas hoy',
    valor: props.estadisticas.accionesHoy ?? 0,
    subtexto: 'en el log de trazabilidad',
    colorFondo: '#FDF4FF',
    colorIcono: '#9333EA',
    icono: Activity
  }
]);

const coloresRol = {
  administrador: '#185FA5',
  secretario:    '#16A34A',
  asesor:        '#D97706',
  practicante:   '#9333EA'
};
</script>

<template>
  <AppLayout>
    <div class="dashboard">

      <!-- Cabecera de pagina -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard General</h1>
          <p class="page-sub">Estado del sistema en tiempo real</p>
        </div>
        <div class="header-accesos">
          <a href="/usuarios" class="btn-header">Gestión de Usuarios</a>
          <a href="/auditoria" class="btn-header">Log de Actividad</a>
          <a href="/configuracion" class="btn-header btn-header--outline">Configuración</a>
        </div>
      </div>

      <!-- Alertas del sistema -->
      <div v-if="alertas && alertas.length > 0" class="alertas-bloque">
        <div v-for="(alerta, i) in alertas" :key="i" class="alerta-item" :class="`alerta-item--${alerta.tipo ?? 'info'}`">
          <AlertTriangle class="alerta-icono" />
          <span>{{ alerta.mensaje }}</span>
        </div>
      </div>

      <!-- Tarjetas de estadisticas -->
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

        <!-- Ultimas actividades del sistema -->
        <div class="panel panel--grande">
          <div class="panel__header">
            <h2 class="panel__titulo">Ultimas actividades del sistema</h2>
            <a href="/auditoria" class="panel__link">Ver log completo</a>
          </div>

          <div v-if="!ultimasActividades || ultimasActividades.length === 0" class="panel__vacio">
            <Clock class="vacio-icon" />
            <p>Sin actividad registrada todavia.</p>
          </div>

          <div v-else class="actividad-lista">
            <div v-for="(act, i) in ultimasActividades" :key="i" class="actividad-fila">
              <div class="actividad-dot" :class="act.resultado === 'fallido' ? 'actividad-dot--error' : 'actividad-dot--ok'">
                <CheckCircle v-if="act.resultado !== 'fallido'" class="dot-icon" />
                <AlertTriangle v-else class="dot-icon" />
              </div>
              <div class="actividad-body">
                <p class="actividad-texto">
                  <strong>{{ act.usuario }}</strong>
                  <span class="actividad-accion"> — {{ act.accion }}</span>
                  <span v-if="act.expediente" class="actividad-tag">{{ act.expediente }}</span>
                </p>
                <p class="actividad-meta">{{ act.hora }} · IP: {{ act.ip }}</p>
              </div>
              <span v-if="act.resultado === 'fallido'" class="badge badge--urgente">Fallido</span>
            </div>
          </div>

          <!-- Tabla placeholder cuando no hay datos reales -->
          <div v-if="!ultimasActividades || ultimasActividades.length === 0" class="tabla-placeholder">
            <div class="tabla-header-row">
              <span>Fecha / Hora</span>
              <span>Usuario</span>
              <span>Accion</span>
              <span>Expediente</span>
              <span>IP</span>
              <span>Resultado</span>
            </div>
            <div v-for="n in 5" :key="n" class="tabla-placeholder-row">
              <span class="ph-bar ph-bar--fecha"></span>
              <span class="ph-bar ph-bar--usuario"></span>
              <span class="ph-bar ph-bar--accion"></span>
              <span class="ph-bar ph-bar--exp"></span>
              <span class="ph-bar ph-bar--ip"></span>
              <span class="ph-bar ph-bar--res"></span>
            </div>
          </div>

          <!-- Acciones del log -->
          <div class="log-acciones">
            <button class="btn-secundario">
              <Filter class="btn-icon" /> Filtrar por usuario
            </button>
            <button class="btn-secundario">
              <Filter class="btn-icon" /> Filtrar por fecha
            </button>
            <button class="btn-secundario">
              <Filter class="btn-icon" /> Filtrar por tipo
            </button>
            <button class="btn-primario">
              <Download class="btn-icon" /> Exportar a Excel
            </button>
          </div>
        </div>

        <!-- Panel derecho: usuarios por rol -->
        <div class="panel panel--chico">

          <!-- Distribucion por rol -->
          <div class="panel__header">
            <h2 class="panel__titulo">Usuarios por rol</h2>
            <a href="/usuarios" class="panel__link">Ver todos</a>
          </div>

          <div class="roles-lista">
            <div v-for="rol in usuariosPorRol" :key="rol.nombre" class="rol-fila">
              <div class="rol-dot" :style="{ background: coloresRol[rol.nombre.toLowerCase()] ?? '#94A3B8' }"></div>
              <span class="rol-nombre">{{ rol.nombre }}</span>
              <span class="rol-total">{{ rol.total }}</span>
            </div>

            <!-- placeholder si no hay datos -->
            <template v-if="!usuariosPorRol || usuariosPorRol.length === 0">
              <div v-for="(r, i) in ['Administrador','Secretario','Asesor','Practicante']" :key="i" class="rol-fila">
                <div class="rol-dot" :style="{ background: Object.values(coloresRol)[i] }"></div>
                <span class="rol-nombre">{{ r }}</span>
                <span class="rol-total">—</span>
              </div>
            </template>
          </div>

          <div class="barra-roles">
            <template v-if="usuariosPorRol && usuariosPorRol.length > 0">
              <div
                v-for="rol in usuariosPorRol"
                :key="rol.nombre"
                class="barra-segmento"
                :style="{ width: `${rol.porcentaje}%`, background: coloresRol[rol.nombre.toLowerCase()] ?? '#94A3B8' }"
                :title="`${rol.nombre}: ${rol.total}`"
              ></div>
            </template>
            <template v-else>
              <div v-for="(c,i) in Object.values(coloresRol)" :key="i" class="barra-segmento" :style="{ width: '25%', background: c }"></div>
            </template>
          </div>

          <!-- Separador -->
          <hr class="panel__divider" />

          <!-- Accesos directos a modulos -->
          <h3 class="panel__subtitulo">Accesos directos</h3>
          <div class="accesos-grid">
            <a href="/usuarios" class="acceso-item">
              <Users class="acceso-icon" />
              <span>Usuarios</span>
            </a>
            <a href="/auditoria" class="acceso-item">
              <Activity class="acceso-icon" />
              <span>Auditoria</span>
            </a>
            <a href="/expedientes" class="acceso-item">
              <FileText class="acceso-icon" />
              <span>Expedientes</span>
            </a>
            <a href="/configuracion" class="acceso-item">
              <LayoutDashboard class="acceso-icon" />
              <span>Config.</span>
            </a>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ── Variables ── */
.dashboard {
  --azul:    #185FA5;
  --texto:   #1E293B;
  --fondo:   #F8FAFC;
  --borde:   #E2E8F0;
  --blanco:  #FFFFFF;
  font-family: 'Poppins', 'Inter', sans-serif;
  color: var(--texto);
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── Cabecera ── */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.page-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--texto);
  margin: 0 0 4px;
}
.page-sub {
  font-size: 13px;
  color: #64748B;
  margin: 0;
}
.header-accesos {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.btn-header {
  padding: 8px 14px;
  background: var(--azul);
  color: #fff;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  transition: background .15s;
  white-space: nowrap;
}
.btn-header:hover { background: #144d87; }
.btn-header--outline {
  background: transparent;
  color: var(--azul);
  border: 1px solid var(--azul);
}
.btn-header--outline:hover { background: #EFF6FF; }

/* ── Alertas ── */
.alertas-bloque { display: flex; flex-direction: column; gap: 6px; }
.alerta-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  border-left: 3px solid currentColor;
}
.alerta-item--info    { background: #DBEAFE; color: #1E40AF; }
.alerta-item--advertencia { background: #FEF3C7; color: #92400E; }
.alerta-item--error   { background: #FEE2E2; color: #991B1B; }
.alerta-icono { width: 16px; height: 16px; flex-shrink: 0; }

/* ── Stats grid ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.stat-card {
  background: var(--blanco);
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
.stat-card__body { flex: 1; min-width: 0; }
.stat-card__label {
  font-size: 12px;
  color: #64748B;
  margin: 0 0 4px;
}
.stat-card__valor {
  font-size: 28px;
  font-weight: 700;
  color: var(--texto);
  margin: 0 0 2px;
  line-height: 1.1;
}
.stat-card__sub {
  font-size: 11px;
  color: #94A3B8;
  margin: 0;
}

/* ── Bloque inferior ── */
.bloque-inferior {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 16px;
  align-items: start;
}

/* ── Panel generico ── */
.panel {
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: 12px;
  padding: 22px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}
.panel__titulo {
  font-size: 14px;
  font-weight: 600;
  color: var(--texto);
  margin: 0;
}
.panel__subtitulo {
  font-size: 13px;
  font-weight: 600;
  color: var(--texto);
  margin: 0 0 12px;
}
.panel__link {
  font-size: 12px;
  color: var(--azul);
  text-decoration: none;
  font-weight: 500;
}
.panel__link:hover { text-decoration: underline; }
.panel__vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 0 8px;
  color: #94A3B8;
  font-size: 13px;
}
.vacio-icon { width: 28px; height: 28px; }
.panel__divider {
  border: 0;
  border-top: 1px solid var(--borde);
  margin: 18px 0;
}

/* ── Actividad ── */
.actividad-lista { display: flex; flex-direction: column; }
.actividad-fila {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid #F1F5F9;
}
.actividad-fila:last-child { border-bottom: none; }
.actividad-dot {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.actividad-dot--ok    { background: #D1FAE5; color: #065F46; }
.actividad-dot--error { background: #FEE2E2; color: #991B1B; }
.dot-icon { width: 13px; height: 13px; }
.actividad-body { flex: 1; }
.actividad-texto { font-size: 12px; color: #334155; margin: 0 0 2px; }
.actividad-accion { color: #64748B; }
.actividad-tag {
  margin-left: 6px;
  font-size: 11px;
  background: #EFF6FF;
  color: var(--azul);
  padding: 1px 7px;
  border-radius: 4px;
}
.actividad-meta { font-size: 11px; color: #94A3B8; margin: 0; }

/* ── Tabla placeholder ── */
.tabla-placeholder { margin-top: 8px; }
.tabla-header-row {
  display: grid;
  grid-template-columns: 130px 140px 1fr 120px 110px 90px;
  gap: 8px;
  padding: 8px 12px;
  background: #F8FAFC;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #94A3B8;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 4px;
}
.tabla-placeholder-row {
  display: grid;
  grid-template-columns: 130px 140px 1fr 120px 110px 90px;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid #F1F5F9;
  align-items: center;
}
.ph-bar {
  height: 10px;
  background: #F1F5F9;
  border-radius: 4px;
  display: block;
}
.ph-bar--fecha   { width: 100px; }
.ph-bar--usuario { width: 110px; }
.ph-bar--accion  { width: 80%; }
.ph-bar--exp     { width: 90px; }
.ph-bar--ip      { width: 80px; }
.ph-bar--res     { width: 60px; }

/* ── Log acciones ── */
.log-acciones {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--borde);
}
.btn-secundario {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 12px;
  background: var(--fondo);
  border: 1px solid var(--borde);
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  font-family: 'Poppins', sans-serif;
  transition: background .15s;
}
.btn-secundario:hover { background: #EFF6FF; color: var(--azul); }
.btn-primario {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  background: var(--azul);
  border: none;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  color: #fff;
  cursor: pointer;
  font-family: 'Poppins', sans-serif;
  transition: background .15s;
  margin-left: auto;
}
.btn-primario:hover { background: #144d87; }
.btn-icon { width: 13px; height: 13px; }

/* ── Roles ── */
.roles-lista { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
.rol-fila { display: flex; align-items: center; gap: 8px; }
.rol-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.rol-nombre { flex: 1; font-size: 13px; color: #475569; }
.rol-total { font-size: 13px; font-weight: 700; color: var(--texto); }
.barra-roles {
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  display: flex;
  background: #F1F5F9;
  margin-bottom: 18px;
}
.barra-segmento { height: 100%; }

/* ── Accesos directos ── */
.accesos-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.acceso-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 8px;
  background: #F8FAFC;
  border: 1px solid var(--borde);
  border-radius: 10px;
  text-decoration: none;
  color: #475569;
  font-size: 11px;
  font-weight: 500;
  transition: all .15s;
}
.acceso-item:hover { background: #EFF6FF; color: var(--azul); border-color: var(--azul); }
.acceso-icon { width: 20px; height: 20px; }

/* ── Badges ── */
.badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.badge--urgente { background: #FEE2E2; color: #991B1B; }

/* ── Responsive ── */
@media (max-width: 1200px) {
  .stats-grid        { grid-template-columns: repeat(2, 1fr); }
  .bloque-inferior   { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .stats-grid        { grid-template-columns: 1fr; }
  .page-header       { flex-direction: column; }
  .tabla-header-row,
  .tabla-placeholder-row { grid-template-columns: 1fr 1fr 1fr; }
}
</style>
