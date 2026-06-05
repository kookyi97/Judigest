<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { FolderOpen, Users, Clock, FileText, Calendar, AlertTriangle, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
  estadisticas: { type:Object, default:()=>({ casosActivos:0, practicantesAsignados:0, casosSinActividad:0, documentosPendientes:0 }) },
  proximasAudiencias: { type:Array, default:()=>[] },
  practicantes:       { type:Array, default:()=>[] }
});

const stats = computed(() => [
  { label:'Casos activos a cargo',    valor:props.estadisticas.casosActivos??0,          sub:'bajo tu supervision', bg:'#EFF6FF', color:'#185FA5', icono:FolderOpen },
  { label:'Practicantes asignados',   valor:props.estadisticas.practicantesAsignados??0, sub:'en tu equipo',        bg:'#F0FDF4', color:'#16A34A', icono:Users },
  { label:'Sin actividad reciente',   valor:props.estadisticas.casosSinActividad??0,     sub:'sin movimiento',      bg:'#FFF7ED', color:'#D97706', icono:Clock },
  { label:'Documentos por revisar',   valor:props.estadisticas.documentosPendientes??0,  sub:'pendientes',          bg:'#FDF4FF', color:'#9333EA', icono:FileText },
]);

const cargaClass = (n) => n>=5?'carga--alta':n>=3?'carga--media':'carga--baja';
</script>

<template>
  <AppLayout>
    <div class="db">

      <div class="db__header">
        <div>
          <h1 class="db__titulo">Dashboard Asesor</h1>
          <p class="db__sub">Estado de tus casos y practicantes asignados</p>
        </div>
      </div>

      <!-- Alerta documentos -->
      <div v-if="estadisticas.documentosPendientes>0" class="alerta-docs">
        <AlertTriangle class="alerta-ico"/>
        <span>Tienes <strong>{{ estadisticas.documentosPendientes }}</strong> documento(s) pendiente(s) de revision.</span>
        <a href="/documentos" class="alerta-link">Revisar</a>
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
            <a href="/casos" class="panel__link">Ver todos</a>
          </div>

          <template v-if="proximasAudiencias && proximasAudiencias.length">
            <div v-for="caso in proximasAudiencias" :key="caso.id" class="caso-card">
              <div class="caso-card__info">
                <p class="caso-card__exp">{{ caso.expediente }}</p>
                <p class="caso-card__tipo">{{ caso.tipo }}</p>
              </div>
              <span class="caso-card__prac">{{ caso.practicante }}</span>
              <span class="caso-card__fecha">{{ caso.fecha }}</span>
            </div>
          </template>
          <template v-else>
            <div v-for="n in 5" :key="n" class="caso-card caso-card--ph">
              <div class="caso-card__info"><div class="ph ph--exp"></div><div class="ph ph--tipo mt4"></div></div>
              <div class="ph ph--prac"></div>
              <div class="ph ph--fecha"></div>
            </div>
            <p class="vacio-txt"><FolderOpen class="vacio-ico"/>Sin casos asignados todavia.</p>
          </template>

          <div class="acciones">
            <a href="/casos" class="btn-sec">Ver detalle del expediente</a>
            <a href="/casos" class="btn-sec">Ver historial de cambios</a>
            <a href="/casos" class="btn-sec">Filtrar por estado o practicante</a>
          </div>
        </div>

        <!-- Panel derecho -->
        <div class="panel-derecho">

          <!-- Practicantes -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Mis practicantes</h2>
              <a href="/asignacion" class="panel__link">Gestionar</a>
            </div>

            <template v-if="practicantes && practicantes.length">
              <div v-for="p in practicantes" :key="p.id" class="prac-item">
                <div class="prac-avatar">{{ p.iniciales??'?' }}</div>
                <div class="prac-info">
                  <p class="prac-nombre">{{ p.nombre }}</p>
                  <p class="prac-casos">{{ p.casosActivos??0 }} caso(s) activo(s)</p>
                </div>
                <div class="carga" :class="cargaClass(p.casosActivos??0)">{{ p.casosActivos??0 }}</div>
              </div>
            </template>
            <template v-else>
              <div v-for="n in 3" :key="n" class="prac-item">
                <div class="prac-avatar prac-avatar--ph"></div>
                <div class="prac-info"><div class="ph ph--pnombre"></div><div class="ph ph--pcasos mt4"></div></div>
              </div>
              <p class="vacio-txt"><Users class="vacio-ico"/>Sin practicantes asignados.</p>
            </template>

            <a href="/asignacion" class="btn-bloque">Asignar practicante a caso</a>
          </div>

          <!-- Proximas audiencias -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Proximas audiencias</h2>
              <a href="/calendario" class="panel__link">Ver calendario</a>
            </div>
            <template v-if="proximasAudiencias && proximasAudiencias.length">
              <div v-for="(a,i) in proximasAudiencias.slice(0,4)" :key="i" class="aud-mini">
                <div class="aud-fecha"><span class="aud-dia">{{ a.dia??'—' }}</span><span class="aud-mes">{{ a.mes??'' }}</span></div>
                <div class="aud-info"><p class="aud-exp">{{ a.expediente }}</p><p class="aud-det">{{ a.practicante }}</p></div>
                <ChevronRight class="aud-arrow"/>
              </div>
            </template>
            <template v-else>
              <div v-for="n in 3" :key="n" class="aud-mini">
                <div class="aud-fecha aud-fecha--ph"><div class="ph ph--dia"></div><div class="ph ph--mes mt4"></div></div>
                <div class="aud-info"><div class="ph ph--exp"></div><div class="ph ph--det mt4"></div></div>
              </div>
            </template>
          </div>

          <!-- Documentos -->
          <div class="panel">
            <div class="panel__head">
              <h2 class="panel__titulo">Revision de documentos</h2>
              <a href="/documentos" class="panel__link">Ver todos</a>
            </div>
            <div class="docs-resumen">
              <FileText class="docs-ico"/>
              <div>
                <p class="docs-val">{{ estadisticas.documentosPendientes??0 }}</p>
                <p class="docs-sub">documentos por revisar</p>
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
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__header { margin-bottom:2px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub { font-size:13px; color:#64748B; margin:0; }

.alerta-docs { display:flex; align-items:center; gap:8px; padding:11px 14px; background:#FEF3C7; color:#92400E; border-radius:8px; font-size:13px; border-left:3px solid #D97706; flex-wrap:wrap; }
.alerta-ico  { width:16px; height:16px; flex-shrink:0; }
.alerta-link { margin-left:auto; color:#185FA5; font-weight:600; text-decoration:none; }
.alerta-link:hover { text-decoration:underline; }

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

/* Casos cards */
.caso-card { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #F1F5F9; flex-wrap:wrap; }
.caso-card:last-of-type { border-bottom:none; }
.caso-card--ph { opacity:.6; }
.caso-card__info { flex:1; min-width:120px; }
.caso-card__exp  { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.caso-card__tipo { font-size:11px; color:#94A3B8; margin:0; }
.caso-card__prac { font-size:12px; color:#475569; }
.caso-card__fecha{ font-size:11px; color:#94A3B8; }

.ph { height:10px; background:#F1F5F9; border-radius:4px; display:block; }
.ph--exp { width:110px; } .ph--tipo { width:70px; } .ph--prac { width:90px; } .ph--fecha { width:70px; }
.ph--dia { width:24px; height:18px; } .ph--mes { width:18px; height:8px; } .ph--det { width:70px; }
.ph--pnombre { width:110px; } .ph--pcasos { width:80px; }
.mt4 { margin-top:4px; }

.vacio-txt { display:flex; align-items:center; gap:6px; font-size:13px; color:#94A3B8; margin:8px 0 0; }
.vacio-ico { width:16px; height:16px; }

.acciones { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid #E2E8F0; }
.btn-sec { display:inline-flex; align-items:center; padding:6px 11px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; font-size:12px; font-weight:500; color:#475569; text-decoration:none; white-space:nowrap; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#EFF6FF; color:#185FA5; }

/* Practicantes */
.prac-item { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #F1F5F9; }
.prac-item:last-of-type { border-bottom:none; }
.prac-avatar { width:32px; height:32px; border-radius:50%; background:#185FA5; color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; text-transform:uppercase; }
.prac-avatar--ph { background:#F1F5F9; }
.prac-info { flex:1; min-width:0; }
.prac-nombre { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.prac-casos  { font-size:11px; color:#94A3B8; margin:0; }
.carga { width:24px; height:24px; border-radius:50%; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.carga--baja  { background:#D1FAE5; color:#065F46; }
.carga--media { background:#FEF3C7; color:#92400E; }
.carga--alta  { background:#FEE2E2; color:#991B1B; }

/* Audiencias mini */
.aud-mini { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #F1F5F9; }
.aud-mini:last-of-type { border-bottom:none; }
.aud-fecha { width:40px; height:44px; background:#EFF6FF; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0; }
.aud-fecha--ph { opacity:.4; }
.aud-dia  { font-size:15px; font-weight:700; color:#185FA5; line-height:1; }
.aud-mes  { font-size:9px; font-weight:500; color:#64748B; text-transform:uppercase; }
.aud-info { flex:1; min-width:0; }
.aud-exp  { font-size:12px; font-weight:500; color:#1E293B; margin:0 0 2px; }
.aud-det  { font-size:11px; color:#64748B; margin:0; }
.aud-arrow { width:14px; height:14px; color:#94A3B8; flex-shrink:0; }

/* Documentos */
.docs-resumen { display:flex; align-items:center; gap:12px; padding:12px; background:#F8FAFC; border-radius:8px; }
.docs-ico { width:26px; height:26px; color:#9333EA; flex-shrink:0; }
.docs-val { font-size:24px; font-weight:700; color:#1E293B; margin:0 0 2px; line-height:1; }
.docs-sub { font-size:12px; color:#64748B; margin:0; }

.btn-bloque { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:10px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; margin-top:12px; transition:background .15s; box-sizing:border-box; }
.btn-bloque:hover { background:#144d87; }
.btn-bloque--outline { background:transparent; color:#185FA5; border:1px solid #185FA5; }
.btn-bloque--outline:hover { background:#EFF6FF; }

@media (max-width:1100px) {
  .stats    { grid-template-columns:repeat(2,1fr); }
  .inferior { grid-template-columns:1fr; }
}
@media (max-width:640px) {
  .stats { grid-template-columns:1fr; }
  .acciones { flex-direction:column; }
  .btn-sec  { justify-content:center; }
}
</style>
