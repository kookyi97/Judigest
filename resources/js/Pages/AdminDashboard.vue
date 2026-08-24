<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FileText, Users, Calendar, Activity, LayoutDashboard, AlertTriangle, CheckCircle, Clock, Download, Filter } from 'lucide-vue-next';

const props = defineProps({
  estadisticas: { type: Object, default: () => ({ expedientesActivos:0, usuariosActivos:0, audienciasEsteMes:0, accionesHoy:0 }) },
  ultimasActividades: { type: Array, default: () => [] },
  usuariosPorRol:     { type: Array, default: () => [] },
  alertas:            { type: Array, default: () => [] }
});

const stats = computed(() => [
  { label:'Expedientes activos',    valor:props.estadisticas.expedientesActivos??0, sub:'en el sistema',           bg:'#EFF6FF', color:'#185FA5', icono:FileText },
  { label:'Usuarios activos',       valor:props.estadisticas.usuariosActivos??0,    sub:'cuentas habilitadas',     bg:'#F0FDF4', color:'#16A34A', icono:Users },
  { label:'Audiencias este mes',    valor:props.estadisticas.audienciasEsteMes??0,  sub:'en el calendario',        bg:'#FFF7ED', color:'#D97706', icono:Calendar },
  { label:'Acciones registradas hoy',valor:props.estadisticas.accionesHoy??0,      sub:'en el log',               bg:'#FDF4FF', color:'#9333EA', icono:Activity },
]);

const coloresRol = { administrador:'#185FA5', secretario:'#16A34A', asesor:'#D97706', practicante:'#9333EA' };
</script>

<template>
  <AppLayout>
    <div class="db">

      <!-- Cabecera -->
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Dashboard General</h1>
          <p class="db__sub">Estado del sistema en tiempo real</p>
        </div>
        <div class="db__accesos">
          <Link href="/usuarios" class="btn">Gestión de Usuarios</Link>
          <Link href="/auditoria" class="btn">Log de Actividad</Link>
          <Link href="/configuracion" class="btn btn--outline">Configuración</Link>
        </div>
      </div>

      <!-- Alertas -->
      <div v-if="alertas && alertas.length" class="alertas">
        <div v-for="(a,i) in alertas" :key="i" class="alerta" :class="`alerta--${a.tipo??'info'}`">
          <AlertTriangle class="alerta__ico" /><span>{{ a.mensaje }}</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <div class="stat__ico" :style="{background:s.bg}">
            <component :is="s.icono" class="stat__svg" :style="{color:s.color}" />
          </div>
          <div>
            <p class="stat__label">{{ s.label }}</p>
            <p class="stat__valor">{{ s.valor }}</p>
            <p class="stat__sub">{{ s.sub }}</p>
          </div>
        </div>
      </div>

      <!-- Inferior -->
      <div class="inferior">

        <!-- Actividad reciente -->
        <div class="panel">
          <div class="panel__head">
            <h2 class="panel__titulo">Ultimas actividades</h2>
            <a href="/auditoria" class="panel__link">Ver log completo</a>
          </div>

          <!-- Columnas de log (solo desktop) -->
          <div class="log-cols">
            <span>Fecha / Hora</span><span>Usuario</span><span>Accion</span>
            <span>Expediente</span><span>IP</span><span>Resultado</span>
          </div>

          <div v-if="ultimasActividades && ultimasActividades.length">
            <div v-for="(a,i) in ultimasActividades" :key="i" class="act-fila">
              <div class="act-dot" :class="a.resultado==='fallido'?'act-dot--err':'act-dot--ok'">
                <CheckCircle v-if="a.resultado!=='fallido'" class="act-dot__svg"/>
                <AlertTriangle v-else class="act-dot__svg"/>
              </div>
              <div class="act-body">
                <p class="act-txt"><strong>{{ a.usuario }}</strong> — {{ a.accion }}
                  <span v-if="a.expediente" class="act-tag">{{ a.expediente }}</span>
                </p>
                <p class="act-meta">{{ a.hora }} · {{ a.ip }}</p>
              </div>
            </div>
          </div>

          <!-- Placeholder filas -->
          <div v-else>
            <div v-for="n in 5" :key="n" class="act-fila">
              <div class="act-dot act-dot--ok"><Clock class="act-dot__svg"/></div>
              <div class="act-body">
                <div class="ph ph--lg"></div>
                <div class="ph ph--sm mt4"></div>
              </div>
            </div>
          </div>

          <!-- Botones log -->
          <div class="log-btns">
            <button class="btn-sec"><Filter class="btn-sec__ico"/>Filtrar usuario</button>
            <button class="btn-sec"><Filter class="btn-sec__ico"/>Filtrar fecha</button>
            <button class="btn-sec"><Filter class="btn-sec__ico"/>Filtrar tipo</button>
            <button class="btn btn--sm ml-auto"><Download class="btn-sec__ico"/>Exportar Excel</button>
          </div>
        </div>

        <!-- Panel usuarios por rol -->
        <div class="panel panel--chico">
          <div class="panel__head">
            <h2 class="panel__titulo">Usuarios por rol</h2>
            <a href="/usuarios" class="panel__link">Ver todos</a>
          </div>

          <div class="roles">
            <template v-if="usuariosPorRol && usuariosPorRol.length">
              <div v-for="r in usuariosPorRol" :key="r.nombre" class="rol-fila">
                <div class="rol-dot" :style="{background:coloresRol[r.nombre.toLowerCase()]??'#94A3B8'}"></div>
                <span class="rol-nombre">{{ r.nombre }}</span>
                <span class="rol-total">{{ r.total }}</span>
              </div>
            </template>
            <template v-else>
              <div v-for="(label,i) in ['Administrador','Secretario','Asesor','Practicante']" :key="i" class="rol-fila">
                <div class="rol-dot" :style="{background:Object.values(coloresRol)[i]}"></div>
                <span class="rol-nombre">{{ label }}</span>
                <span class="rol-total">—</span>
              </div>
            </template>
          </div>

          <div class="barra-roles">
            <template v-if="usuariosPorRol && usuariosPorRol.length">
              <div v-for="r in usuariosPorRol" :key="r.nombre" class="barra-seg"
                :style="{width:`${r.porcentaje}%`,background:coloresRol[r.nombre.toLowerCase()]??'#94A3B8'}"></div>
            </template>
            <template v-else>
              <div v-for="(c,i) in Object.values(coloresRol)" :key="i" class="barra-seg" :style="{width:'25%',background:c}"></div>
            </template>
          </div>

          <hr class="divider"/>
          <h3 class="panel__subtitulo">Accesos directos</h3>
          <div class="accesos-grid">
            <a href="/usuarios"      class="acceso"><Users class="acceso__ico"/><span>Usuarios</span></a>
            <a href="/auditoria"     class="acceso"><Activity class="acceso__ico"/><span>Auditoria</span></a>
            <a href="/expedientes"   class="acceso"><FileText class="acceso__ico"/><span>Expedientes</span></a>
            <a href="/configuracion" class="acceso"><LayoutDashboard class="acceso__ico"/><span>Config.</span></a>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ── Reset y base ── */
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }

/* ── Cabecera ── */
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }
.db__accesos{ display:flex; gap:8px; flex-wrap:wrap; }

/* ── Botones ── */
.btn { padding:8px 14px; background:#185FA5; color:#fff; border-radius:8px; font-size:12px; font-weight:500; text-decoration:none; white-space:nowrap; transition:background .15s; border:none; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn:hover { background:#144d87; }
.btn--outline { background:transparent; color:#185FA5; border:1px solid #185FA5; }
.btn--outline:hover { background:#EFF6FF; }
.btn--sm { padding:6px 12px; font-size:12px; }
.ml-auto { margin-left:auto; }

/* ── Alertas ── */
.alertas { display:flex; flex-direction:column; gap:6px; }
.alerta { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; font-size:13px; border-left:3px solid currentColor; }
.alerta--info    { background:#DBEAFE; color:#1E40AF; }
.alerta--advertencia { background:#FEF3C7; color:#92400E; }
.alerta--error   { background:#FEE2E2; color:#991B1B; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; }

/* ── Stats ── */
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.stat { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:18px; display:flex; align-items:flex-start; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat__ico { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat__svg { width:22px; height:22px; }
.stat__label { font-size:12px; color:#64748B; margin:0 0 3px; }
.stat__valor { font-size:26px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1.1; }
.stat__sub   { font-size:11px; color:#94A3B8; margin:0; }

/* ── Inferior ── */
.inferior { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }

/* ── Panel ── */
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel--chico {}
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.panel__titulo { font-size:14px; font-weight:600; color:#1E293B; margin:0; }
.panel__subtitulo { font-size:13px; font-weight:600; color:#1E293B; margin:0 0 10px; }
.panel__link { font-size:12px; color:#185FA5; text-decoration:none; font-weight:500; }
.panel__link:hover { text-decoration:underline; }
.divider { border:0; border-top:1px solid #E2E8F0; margin:16px 0; }

/* ── Columnas log (solo desktop, ocultas en móvil) ── */
.log-cols { display:grid; grid-template-columns:120px 130px 1fr 110px 100px 80px; gap:8px; padding:7px 10px; background:#F8FAFC; border-radius:6px; font-size:10px; font-weight:600; color:#94A3B8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }

/* ── Actividad ── */
.act-fila { display:flex; align-items:flex-start; gap:10px; padding:9px 0; border-bottom:1px solid #F1F5F9; }
.act-fila:last-child { border-bottom:none; }
.act-dot { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.act-dot--ok  { background:#D1FAE5; color:#065F46; }
.act-dot--err { background:#FEE2E2; color:#991B1B; }
.act-dot__svg { width:13px; height:13px; }
.act-body { flex:1; min-width:0; }
.act-txt  { font-size:12px; color:#334155; margin:0 0 2px; }
.act-tag  { margin-left:6px; font-size:11px; background:#EFF6FF; color:#185FA5; padding:1px 7px; border-radius:4px; }
.act-meta { font-size:11px; color:#94A3B8; margin:0; }

/* ── Placeholders ── */
.ph { height:10px; background:#F1F5F9; border-radius:4px; display:block; }
.ph--lg { width:70%; }
.ph--sm { width:40%; }
.mt4 { margin-top:4px; }

/* ── Botones log ── */
.log-btns { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #E2E8F0; align-items:center; }
.btn-sec { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; font-size:12px; font-weight:500; color:#475569; cursor:pointer; font-family:'Poppins',sans-serif; transition:background .15s; white-space:nowrap; }
.btn-sec:hover { background:#EFF6FF; color:#185FA5; }
.btn-sec__ico { width:12px; height:12px; }

/* ── Roles ── */
.roles { display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
.rol-fila { display:flex; align-items:center; gap:8px; }
.rol-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.rol-nombre { flex:1; font-size:13px; color:#475569; }
.rol-total  { font-size:13px; font-weight:700; color:#1E293B; }
.barra-roles { height:7px; border-radius:4px; overflow:hidden; display:flex; background:#F1F5F9; margin-bottom:4px; }
.barra-seg   { height:100%; }

/* ── Accesos ── */
.accesos-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.acceso { display:flex; flex-direction:column; align-items:center; gap:5px; padding:12px 8px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; text-decoration:none; color:#475569; font-size:11px; font-weight:500; transition:all .15s; }
.acceso:hover { background:#EFF6FF; color:#185FA5; border-color:#185FA5; }
.acceso__ico { width:20px; height:20px; }

/* ── Responsive ── */
@media (max-width:1100px) {
  .stats    { grid-template-columns:repeat(2,1fr); }
  .inferior { grid-template-columns:1fr; }
  .log-cols { display:none; }
}
@media (max-width:640px) {
  .stats        { grid-template-columns:1fr; }
  .db__header   { flex-direction:column; }
  .db__accesos  { width:100%; }
  .btn          { flex:1; text-align:center; justify-content:center; }
  .log-btns     { flex-direction:column; align-items:stretch; }
  .btn-sec      { justify-content:center; }
  .ml-auto      { margin-left:0; }
}
</style>
