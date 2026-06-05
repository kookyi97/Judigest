<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Calendar, FolderOpen, UserX, Bell, Plus, Clock, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
  estadisticas: { type:Object, default:()=>({ audienciasEstaSemana:0, expedientesAbiertos:0, casosSinPracticante:0, notificacionesPendientes:0 }) },
  ultimosExpedientes:  { type:Array, default:()=>[] },
  proximasAudiencias:  { type:Array, default:()=>[] }
});

const stats = computed(() => [
  { label:'Audiencias esta semana',    valor:props.estadisticas.audienciasEstaSemana??0,    sub:'programadas',         bg:'#EFF6FF', color:'#185FA5', icono:Calendar },
  { label:'Expedientes abiertos',      valor:props.estadisticas.expedientesAbiertos??0,     sub:'en el área jurídica', bg:'#F0FDF4', color:'#16A34A', icono:FolderOpen },
  { label:'Sin practicante asignado',  valor:props.estadisticas.casosSinPracticante??0,     sub:'casos sin asignar',   bg:'#FFF7ED', color:'#D97706', icono:UserX },
  { label:'Notificaciones pendientes', valor:props.estadisticas.notificacionesPendientes??0,sub:'por enviar',          bg:'#FDF4FF', color:'#9333EA', icono:Bell },
]);

const colorEstado = { pendiente:{bg:'#DBEAFE',txt:'#1E40AF'}, en_proceso:{bg:'#FEF3C7',txt:'#92400E'}, cerrado:{bg:'#D1FAE5',txt:'#065F46'}, urgente:{bg:'#FEE2E2',txt:'#991B1B'} };
const labelEstado = { pendiente:'Pendiente', en_proceso:'En proceso', cerrado:'Cerrado', urgente:'Urgente' };
</script>

<template>
  <AppLayout>
    <div class="db">

      <div class="db__header">
        <div>
          <h1 class="db__titulo">Dashboard Operativo</h1>
          <p class="db__sub">Resumen de actividad del area juridica</p>
        </div>
        <a href="/audiencias/nueva" class="btn"><Plus class="btn__ico"/>Registrar audiencia</a>
      </div>

      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <div class="stat__ico" :style="{background:s.bg}"><component :is="s.icono" class="stat__svg" :style="{color:s.color}"/></div>
          <div>
            <p class="stat__label">{{ s.label }}</p>
            <p class="stat__valor">{{ s.valor }}</p>
            <p class="stat__sub">{{ s.sub }}</p>
          </div>
        </div>
      </div>

      <div class="inferior">

        <!-- Expedientes -->
        <div class="panel">
          <div class="panel__head">
            <h2 class="panel__titulo">Ultimos expedientes modificados</h2>
            <a href="/expedientes" class="panel__link">Ver todos</a>
          </div>

          <!-- Cards responsivas en vez de tabla fija -->
          <template v-if="ultimosExpedientes && ultimosExpedientes.length">
            <div v-for="exp in ultimosExpedientes" :key="exp.id" class="exp-card">
              <div class="exp-card__info">
                <p class="exp-card__num">{{ exp.numero }}</p>
                <p class="exp-card__nom">{{ exp.nombre }}</p>
              </div>
              <span class="tipo-tag">{{ exp.tipo }}</span>
              <span class="estado-badge" :style="{background:colorEstado[exp.estado]?.bg,color:colorEstado[exp.estado]?.txt}">{{ labelEstado[exp.estado]??exp.estado }}</span>
              <span class="exp-card__fecha">{{ exp.modificado }}</span>
            </div>
          </template>
          <template v-else>
            <div v-for="n in 5" :key="n" class="exp-card exp-card--ph">
              <div class="exp-card__info"><div class="ph ph--num"></div><div class="ph ph--nom mt4"></div></div>
              <div class="ph ph--tipo"></div>
              <div class="ph ph--est"></div>
              <div class="ph ph--fecha"></div>
            </div>
          </template>

          <div class="acciones">
            <a href="/expedientes/nuevo" class="btn btn--sm"><Plus class="btn__ico"/>Crear expediente</a>
            <a href="/expedientes" class="btn-sec">Editar expediente</a>
            <a href="/expedientes" class="btn-sec">Cambiar estado</a>
            <a href="/expedientes" class="btn-sec">Archivar caso</a>
            <a href="/expedientes" class="btn-sec">Exportar Excel</a>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Proximas audiencias -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Proximas audiencias</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>

            <template v-if="proximasAudiencias && proximasAudiencias.length">
              <div v-for="(a,i) in proximasAudiencias" :key="i" class="aud-item">
                <div class="aud-fecha"><span class="aud-dia">{{ a.dia }}</span><span class="aud-mes">{{ a.mes }}</span></div>
                <div class="aud-info">
                  <p class="aud-exp">{{ a.expediente }}</p>
                  <p class="aud-det">{{ a.hora }} · {{ a.sala }}</p>
                  <p class="aud-prac">{{ a.practicante }}</p>
                </div>
                <ChevronRight class="aud-arrow"/>
              </div>
            </template>
            <template v-else>
              <div v-for="n in 4" :key="n" class="aud-item">
                <div class="aud-fecha aud-fecha--ph"><div class="ph ph--dia"></div><div class="ph ph--mes mt4"></div></div>
                <div class="aud-info"><div class="ph ph--exp"></div><div class="ph ph--det mt4"></div></div>
              </div>
              <p class="vacio-txt"><Calendar class="vacio-ico"/>Sin audiencias proximas</p>
            </template>

            <a href="/audiencias/nueva" class="btn-bloque"><Plus class="btn__ico"/>Registrar audiencia</a>
          </div>

          <!-- Notificaciones -->
          <div class="panel panel--notif">
            <div class="panel__head"><h2 class="panel__titulo">Notificaciones pendientes</h2></div>
            <div class="notif-resumen">
              <Bell class="notif-ico"/>
              <div>
                <p class="notif-val">{{ estadisticas.notificacionesPendientes??0 }}</p>
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
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub { font-size:13px; color:#64748B; margin:0; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; white-space:nowrap; transition:background .15s; }
.btn:hover { background:#144d87; }
.btn--sm { padding:6px 12px; font-size:12px; }
.btn__ico { width:14px; height:14px; }
.btn-sec { display:inline-flex; align-items:center; padding:6px 11px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; font-size:12px; font-weight:500; color:#475569; text-decoration:none; white-space:nowrap; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#EFF6FF; color:#185FA5; }
.btn-bloque { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:10px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; margin-top:12px; transition:background .15s; box-sizing:border-box; }
.btn-bloque:hover { background:#144d87; }
.btn-bloque--outline { background:transparent; color:#185FA5; border:1px solid #185FA5; }
.btn-bloque--outline:hover { background:#EFF6FF; }

.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.stat { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:18px; display:flex; align-items:flex-start; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat__ico { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat__svg { width:22px; height:22px; }
.stat__label { font-size:12px; color:#64748B; margin:0 0 3px; }
.stat__valor { font-size:26px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1.1; }
.stat__sub { font-size:11px; color:#94A3B8; margin:0; }

.inferior { display:grid; grid-template-columns:1fr 290px; gap:16px; align-items:start; }
.panel-derecho { display:flex; flex-direction:column; gap:16px; }

.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.panel__titulo { font-size:14px; font-weight:600; color:#1E293B; margin:0; }
.panel__link { font-size:12px; color:#185FA5; text-decoration:none; font-weight:500; }
.panel__link:hover { text-decoration:underline; }

/* Expediente cards */
.exp-card { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #F1F5F9; flex-wrap:wrap; }
.exp-card:last-of-type { border-bottom:none; }
.exp-card--ph { opacity:.6; }
.exp-card__info { flex:1; min-width:120px; }
.exp-card__num { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.exp-card__nom { font-size:11px; color:#94A3B8; margin:0; }
.exp-card__fecha { font-size:11px; color:#94A3B8; }
.tipo-tag { font-size:11px; background:#F1F5F9; color:#475569; padding:3px 8px; border-radius:4px; text-transform:capitalize; white-space:nowrap; }
.estado-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; white-space:nowrap; }

.ph { height:10px; background:#F1F5F9; border-radius:4px; display:block; }
.ph--num { width:70px; } .ph--nom { width:110px; } .ph--tipo { width:55px; } .ph--est { width:75px; } .ph--fecha { width:70px; }
.ph--dia { width:24px; height:18px; } .ph--mes { width:18px; height:8px; } .ph--exp { width:100px; } .ph--det { width:70px; }
.mt4 { margin-top:4px; }

.acciones { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #E2E8F0; }

/* Audiencias */
.aud-item { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #F1F5F9; }
.aud-item:last-of-type { border-bottom:none; }
.aud-fecha { width:42px; height:44px; background:#EFF6FF; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0; }
.aud-fecha--ph { opacity:.4; }
.aud-dia  { font-size:16px; font-weight:700; color:#185FA5; line-height:1; }
.aud-mes  { font-size:9px; font-weight:500; color:#64748B; text-transform:uppercase; }
.aud-info { flex:1; min-width:0; }
.aud-exp  { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.aud-det  { font-size:11px; color:#64748B; margin:0 0 2px; }
.aud-prac { font-size:11px; color:#94A3B8; margin:0; }
.aud-arrow { width:14px; height:14px; color:#94A3B8; flex-shrink:0; }
.vacio-txt { display:flex; align-items:center; gap:6px; font-size:13px; color:#94A3B8; margin:8px 0 0; }
.vacio-ico { width:16px; height:16px; }

/* Notificaciones */
.notif-resumen { display:flex; align-items:center; gap:12px; padding:12px; background:#F8FAFC; border-radius:8px; }
.notif-ico { width:28px; height:28px; color:#185FA5; flex-shrink:0; }
.notif-val { font-size:24px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1; }
.notif-sub { font-size:12px; color:#64748B; margin:0; }

@media (max-width:1100px) {
  .stats    { grid-template-columns:repeat(2,1fr); }
  .inferior { grid-template-columns:1fr; }
}
@media (max-width:640px) {
  .stats { grid-template-columns:1fr; }
  .db__header { flex-direction:column; }
  .btn { width:100%; justify-content:center; }
  .acciones { flex-direction:column; }
  .btn-sec { justify-content:center; }
}
</style>
