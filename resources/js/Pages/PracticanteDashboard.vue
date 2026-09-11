<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { FolderOpen, Calendar, Activity, Bell, CheckCircle, ChevronRight, Upload, Download, FileText } from 'lucide-vue-next';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const nombreUsuario = computed(() => page.props.auth?.usuario?.nombre ?? 'Practicante');

const props = defineProps({
  estadisticas: { type:Object, default:()=>({ casosAsignados:0, proximasAudiencias:0, casosConActividad:0, notificacionesNoLeidas:0 }) },
  asesor:         { type:Object, default:null },
  casosAsignados: { type:Array, default:()=>[] },
  notificaciones: { type:Array, default:()=>[] }
});

const stats = computed(() => [
  { label:'Casos asignados',       valor:props.estadisticas.casosAsignados??0,        sub:'expedientes activos',   bg:'#EFF6FF', color:'#185FA5', icono:FolderOpen },
  { label:'Proximas audiencias',   valor:props.estadisticas.proximasAudiencias??0,    sub:'en tus casos',          bg:'#F0FDF4', color:'#16A34A', icono:Calendar },
  { label:'Con actividad reciente',valor:props.estadisticas.casosConActividad??0,     sub:'casos con cambios',     bg:'#FFF7ED', color:'#D97706', icono:Activity },
  { label:'Notificaciones',        valor:props.estadisticas.notificacionesNoLeidas??0,sub:'no leidas',             bg:'#FDF4FF', color:'#9333EA', icono:Bell },
]);

const colorEstado = { pendiente:{bg:'#DBEAFE',txt:'#1E40AF'}, en_proceso:{bg:'#FEF3C7',txt:'#92400E'}, cerrado:{bg:'#D1FAE5',txt:'#065F46'}, urgente:{bg:'#FEE2E2',txt:'#991B1B'} };
const tipoNotif = { caso:{bg:'#EFF6FF',color:'#185FA5'}, audiencia:{bg:'#D1FAE5',color:'#065F46'}, estado:{bg:'#FEF3C7',color:'#92400E'} };
</script>

<template>
  <AppLayout>
    <div class="db">

      <div class="db__header">
        <div>
          <h1 class="db__titulo">Bienvenido, {{ nombreUsuario }}</h1>
          <p class="db__sub" v-if="asesor">Supervisado por <strong class="asesor-nom">{{ asesor.nombre }} {{ asesor.apellido }}</strong></p>
          <p class="db__sub" v-else>Sin asesor asignado aun</p>
        </div>
        <Link href="/expedientes" class="btn"><FolderOpen class="btn__ico"/>Mis expedientes</Link>
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

        <!-- Mis casos -->
        <div class="panel">
          <div class="panel__head">
            <h2 class="panel__titulo">Mis casos asignados</h2>
            <Link href="/expedientes" class="panel__link">Ver todos</Link>
          </div>

          <div class="aviso-restriccion">
            Solo puedes consultar y gestionar documentos en los expedientes asignados a ti.
          </div>

          <template v-if="casosAsignados && casosAsignados.length">
            <Link
              v-for="caso in casosAsignados"
              :key="caso.id"
              :href="`/expedientes/${caso.id}`"
              class="caso-card caso-card--interactive"
            >
              <div class="caso-card__info">
                <p class="caso-card__num">{{ caso.numero }}</p>
                <p class="caso-card__nom">{{ caso.nombre }}</p>
              </div>
              <span class="tipo-tag">{{ caso.tipo }}</span>
              <span class="doc-badge" :title="`${caso.totalDocumentos || 0} documentos subidos`">
                <FileText class="doc-badge__ico" /> {{ caso.totalDocumentos || 0 }} docs
              </span>
              <span class="estado-badge" :style="{background:colorEstado[caso.estado]?.bg,color:colorEstado[caso.estado]?.txt}">{{ caso.estadoLabel??caso.estado }}</span>
              <ChevronRight class="caso-card__chevron" />
            </Link>
          </template>
          <template v-else>
            <div v-for="n in 4" :key="n" class="caso-card caso-card--ph">
              <div class="caso-card__info"><div class="ph ph--num"></div><div class="ph ph--nom mt4"></div></div>
              <div class="ph ph--tipo"></div>
              <div class="ph ph--est"></div>
              <div class="ph ph--fecha"></div>
            </div>
            <p class="vacio-txt"><FolderOpen class="vacio-ico"/>No tienes casos asignados todavia.</p>
          </template>

          <div class="acciones">
            <Link href="/expedientes" class="btn-sec"><FolderOpen class="btn-sec__ico"/>Ver listado de expedientes</Link>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Notificaciones -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Mis notificaciones</h2>
              <a href="/notificaciones" class="panel__link">Ver todas</a>
            </div>

            <template v-if="notificaciones && notificaciones.length">
              <div v-for="(n,i) in notificaciones" :key="i" class="notif-item" :class="{'notif-item--nueva':!n.leida}">
                <div class="notif-dot" :style="{background:tipoNotif[n.tipo]?.bg??'#F1F5F9',color:tipoNotif[n.tipo]?.color??'#64748B'}">
                  <Calendar v-if="n.tipo==='audiencia'" class="notif-dot__ico"/>
                  <CheckCircle v-else-if="n.tipo==='estado'" class="notif-dot__ico"/>
                  <FolderOpen v-else class="notif-dot__ico"/>
                </div>
                <div class="notif-body">
                  <p class="notif-txt">{{ n.mensaje }}</p>
                  <p class="notif-meta">{{ n.fecha }}</p>
                </div>
                <span v-if="!n.leida" class="notif-punto"></span>
              </div>
            </template>
            <template v-else>
              <div v-for="n in 4" :key="n" class="notif-item">
                <div class="notif-dot notif-dot--ph"></div>
                <div class="notif-body"><div class="ph ph--ntxt"></div><div class="ph ph--nmeta mt4"></div></div>
              </div>
              <p class="vacio-txt"><Bell class="vacio-ico"/>Sin notificaciones nuevas.</p>
            </template>

            <div class="notif-tipos">
              <p class="notif-tipos__lbl">Tipos que recibes:</p>
              <span class="ntag" style="background:#EFF6FF;color:#185FA5">Nuevo caso asignado</span>
              <span class="ntag" style="background:#D1FAE5;color:#065F46">Audiencia registrada</span>
              <span class="ntag" style="background:#FEF3C7;color:#92400E">Cambio de estado</span>
            </div>
          </div>

          <!-- Calendario -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Mi calendario</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>
            <div class="cal-resumen">
              <Calendar class="cal-ico"/>
              <div>
                <p class="cal-val">{{ estadisticas.proximasAudiencias??0 }}</p>
                <p class="cal-sub">audiencia(s) proxima(s) en tus casos</p>
              </div>
            </div>
            <p class="cal-nota">El secretario es quien registra las audiencias.</p>
            <a href="/calendario" class="btn-bloque">Ver mi calendario</a>
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
.asesor-nom { color:#185FA5; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; white-space:nowrap; transition:background .15s; }
.btn:hover { background:#144d87; }
.btn__ico { width:14px; height:14px; }
.btn-sec { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; font-size:12px; font-weight:500; color:#475569; text-decoration:none; white-space:nowrap; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#EFF6FF; color:#185FA5; }
.btn-sec__ico { width:12px; height:12px; }
.btn-bloque { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:10px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; margin-top:12px; transition:background .15s; box-sizing:border-box; }
.btn-bloque:hover { background:#144d87; }

.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.stat { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:18px; display:flex; align-items:flex-start; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.stat__ico { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat__svg { width:22px; height:22px; }
.stat__label { font-size:12px; color:#64748B; margin:0 0 3px; }
.stat__valor { font-size:26px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1.1; }
.stat__sub { font-size:11px; color:#94A3B8; margin:0; }

.inferior { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }
.panel-derecho { display:flex; flex-direction:column; gap:16px; }

.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.panel__titulo { font-size:14px; font-weight:600; color:#1E293B; margin:0; }
.panel__link { font-size:12px; color:#185FA5; text-decoration:none; font-weight:500; }
.panel__link:hover { text-decoration:underline; }

.aviso-restriccion { background:#FFF7ED; color:#92400E; border-left:3px solid #D97706; padding:9px 12px; border-radius:6px; font-size:12px; margin-bottom:12px; }

/* Casos cards */
.caso-card { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #F1F5F9; flex-wrap:wrap; text-decoration:none; color:inherit; }
.caso-card--interactive { transition:all .15s ease; padding:10px 12px; margin:0 -12px; border-radius:8px; }
.caso-card--interactive:hover { background:#F8FAFC; transform:translateX(2px); }
.caso-card__chevron { width:16px; height:16px; color:#94A3B8; margin-left:auto; }
.doc-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#0369A1; background:#E0F2FE; padding:2px 8px; border-radius:12px; font-weight:500; }
.doc-badge__ico { width:12px; height:12px; }
.caso-card:last-of-type { border-bottom:none; }
.caso-card--ph { opacity:.6; }
.caso-card__info { flex:1; min-width:120px; }
.caso-card__num  { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.caso-card__nom  { font-size:11px; color:#94A3B8; margin:0; }
.caso-card__fecha{ font-size:11px; color:#94A3B8; }
.tipo-tag { font-size:11px; background:#F1F5F9; color:#475569; padding:3px 8px; border-radius:4px; text-transform:capitalize; white-space:nowrap; }
.estado-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; white-space:nowrap; }

.ph { height:10px; background:#F1F5F9; border-radius:4px; display:block; }
.ph--num { width:70px; } .ph--nom { width:110px; } .ph--tipo { width:55px; } .ph--est { width:75px; } .ph--fecha { width:80px; }
.ph--ntxt { width:130px; } .ph--nmeta { width:80px; }
.mt4 { margin-top:4px; }
.vacio-txt { display:flex; align-items:center; gap:6px; font-size:13px; color:#94A3B8; margin:8px 0 0; }
.vacio-ico { width:16px; height:16px; }

.acciones { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #E2E8F0; }

/* Notificaciones */
.notif-item { display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid #F1F5F9; }
.notif-item:last-of-type { border-bottom:none; }
.notif-item--nueva { background:#FAFCFF; margin:0 -20px; padding:10px 20px; }
.notif-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.notif-dot--ph { background:#F1F5F9; }
.notif-dot__ico { width:13px; height:13px; }
.notif-body { flex:1; min-width:0; }
.notif-txt  { font-size:12px; color:#334155; margin:0 0 2px; }
.notif-meta { font-size:11px; color:#94A3B8; margin:0; }
.notif-punto { width:7px; height:7px; border-radius:50%; background:#185FA5; flex-shrink:0; margin-top:5px; }
.notif-tipos { margin-top:12px; padding-top:12px; border-top:1px solid #E2E8F0; display:flex; flex-wrap:wrap; gap:5px; }
.notif-tipos__lbl { font-size:11px; color:#94A3B8; margin:0; width:100%; }
.ntag { font-size:11px; padding:3px 9px; border-radius:20px; font-weight:500; }

/* Calendario */
.cal-resumen { display:flex; align-items:center; gap:12px; padding:12px; background:#F0FDF4; border-radius:8px; margin-bottom:8px; }
.cal-ico  { width:26px; height:26px; color:#16A34A; flex-shrink:0; }
.cal-val  { font-size:24px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1; }
.cal-sub  { font-size:12px; color:#64748B; margin:0; }
.cal-nota { font-size:11px; color:#94A3B8; margin:0; }

@media (max-width:1100px) {
  .stats    { grid-template-columns:repeat(2,1fr); }
  .inferior { grid-template-columns:1fr; }
}
@media (max-width:640px) {
  .stats { grid-template-columns:1fr; }
  .db__header { flex-direction:column; }
  .btn  { width:100%; justify-content:center; }
  .acciones { flex-direction:column; }
  .btn-sec  { justify-content:center; }
}
</style>
