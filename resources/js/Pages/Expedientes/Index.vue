<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { FolderOpen, Plus, Search, FileText, Edit2, CheckCircle2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
  expedientes: {
    type: Array,
    default: () => []
  }
});

const page = usePage();
const usuario = computed(() => page.props.auth.usuario);
const isSecretario = computed(() => usuario.value?.rol === 'secretario');

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const d = new Date(dateString);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
};

const estadoClass = (estado) => {
    switch(estado) {
        case 'Abierto': return 'bg-sky-100 text-sky-700';
        case 'En Proceso': return 'bg-amber-100 text-amber-700';
        case 'Resuelto': return 'bg-emerald-100 text-emerald-700';
        case 'Cerrado': return 'bg-slate-100 text-slate-700';
        case 'Archivado': return 'bg-rose-100 text-rose-700';
        default: return 'bg-sky-100 text-sky-700';
    }
};
</script>

<template>
  <Head title="Gestión de Expedientes" />
  <AppLayout>
    <div class="db">
      <!-- Cabecera -->
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Gestión de Expedientes</h1>
          <p class="db__sub">Administra y consulta los procesos jurídicos activos</p>
        </div>
        <div class="db__accesos" v-if="isSecretario">
          <Link href="/expedientes/create" class="btn"><Plus class="btn__ico"/> Nuevo Expediente</Link>
        </div>
      </div>

      <!-- Mensaje de Éxito -->
      <div v-if="page.props.flash && page.props.flash.exito" class="alerta alerta--exito">
        <CheckCircle2 class="alerta__ico" />
        <span>{{ page.props.flash.exito }}</span>
      </div>

      <!-- Contenido Principal -->
      <div class="panel">
        <div class="panel__head">
          <h2 class="panel__titulo">Expedientes Registrados</h2>
        </div>

        <div class="table-container" v-if="expedientes.length > 0">
          <table class="table">
            <thead>
              <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Tipo de Proceso</th>
                <th>Estado</th>
                <th>Asesor Asignado</th>
                <th>Última Modificación</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exp in expedientes" :key="exp.id">
                <td>
                  <div class="flex items-center gap-2">
                    <FileText class="w-4 h-4 text-slate-400" />
                    <span class="font-semibold text-slate-800">{{ exp.numero_expediente }}</span>
                  </div>
                </td>
                <td>{{ exp.cliente }}</td>
                <td>
                  <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-semibold uppercase tracking-wide">
                    {{ exp.tipo_proceso }}
                  </span>
                </td>
                <td>
                  <span class="estado-badge" :class="estadoClass(exp.estado)">
                    {{ exp.estado || 'Abierto' }}
                  </span>
                </td>
                <td>
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-700">{{ exp.asesor ? exp.asesor.nombre + ' ' + exp.asesor.apellido : 'Sin asignar' }}</span>
                  </div>
                </td>
                <td>
                  <div class="flex flex-col">
                    <span class="text-sm text-slate-600 font-semibold">{{ formatDate(exp.updated_at) }}</span>
                    <span class="text-xs text-slate-500">Por: {{ exp.modificador ? exp.modificador.nombre : (exp.creador ? exp.creador.nombre : 'Sistema') }}</span>
                  </div>
                </td>
                <td>
                  <Link v-if="isSecretario" :href="`/expedientes/${exp.id}/edit`" class="action-btn" title="Editar Expediente">
                    <Edit2 class="w-4 h-4 text-slate-600" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Estado Vacío -->
        <div class="empty-state" v-else>
          <div class="empty-state__icon">
            <FolderOpen class="empty-state__svg" />
          </div>
          <h3 class="empty-state__title">No hay expedientes registrados</h3>
          <p class="empty-state__desc">Aún no se ha ingresado ningún caso al sistema.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }
.db__accesos{ display:flex; gap:8px; flex-wrap:wrap; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; white-space:nowrap; transition:background .15s; border:none; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn:hover { background:#144d87; }
.btn__ico { width:16px; height:16px; }

.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.panel__titulo { font-size:16px; font-weight:600; color:#1E293B; margin:0; }

.table-container { overflow-x: auto; margin: -20px; margin-top: 0; padding: 20px; padding-top: 0; }
.table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
.table th { font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC; }
.table td { padding: 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background-color: #F8FAFC; }

.flex { display: flex; }
.flex-col { flex-direction: column; }
.items-center { align-items: center; }
.gap-2 { gap: 0.5rem; }
.w-4 { width: 1rem; }
.h-4 { height: 1rem; }
.text-slate-400 { color: #94a3b8; }
.text-slate-600 { color: #475569; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.text-xs { font-size: 0.75rem; line-height: 1rem; }
.text-sm { font-size: 0.875rem; line-height: 1.25rem; }
.uppercase { text-transform: uppercase; }
.tracking-wide { letter-spacing: 0.025em; }
.bg-blue-50 { background-color: #eff6ff; }
.text-blue-700 { color: #1d4ed8; }
.px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
.py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
.rounded { border-radius: 0.25rem; }

.empty-state { text-align: center; padding: 60px 20px; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1; }
.empty-state__icon { width: 64px; height: 64px; margin: 0 auto 16px; background: #EFF6FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #185FA5; }
.empty-state__svg { width: 32px; height: 32px; }
.empty-state__title { font-size: 18px; font-weight: 600; color: #1E293B; margin: 0 0 8px; }
.empty-state__desc { font-size: 14px; color: #64748B; margin: 0; }

.estado-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.bg-sky-100 { background-color: #e0f2fe; } .text-sky-700 { color: #0369a1; }
.bg-amber-100 { background-color: #fef3c7; } .text-amber-700 { color: #b45309; }
.bg-emerald-100 { background-color: #d1fae5; } .text-emerald-700 { color: #047857; }
.bg-slate-100 { background-color: #f1f5f9; } .text-slate-700 { color: #334155; }
.bg-rose-100 { background-color: #ffe4e6; } .text-rose-700 { color: #be123c; }
.text-slate-500 { color: #64748b; }

.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #F1F5F9; transition: all 0.2s; }
.action-btn:hover { background: #E2E8F0; }

.alerta { display:flex; align-items:flex-start; gap:8px; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border-left:4px solid currentColor; margin-bottom: 4px; }
.alerta--exito { background:#F0FDF4; color:#15803D; }
.alerta__ico { width:18px; height:18px; flex-shrink:0; }
</style>
