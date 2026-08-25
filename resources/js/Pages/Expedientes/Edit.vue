<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Save, X, FileText, User, Gavel, Calendar, AlertTriangle, Clock } from 'lucide-vue-next';

const props = defineProps({
  expediente: Object,
  asesores: {
    type: Array,
    default: () => []
  }
});

const form = useForm({
    numero_expediente: props.expediente.numero_expediente,
    cliente: props.expediente.cliente,
    tipo_proceso: props.expediente.tipo_proceso,
    asesor_id: props.expediente.asesor_id,
    fecha_ingreso: props.expediente.fecha_ingreso ? props.expediente.fecha_ingreso.split('T')[0] : '', // Extract YYYY-MM-DD
    estado: props.expediente.estado || 'Abierto',
    descripcion: props.expediente.descripcion || '',
});

const submit = () => {
    form.put(`/expedientes/${props.expediente.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <Head title="Editar Expediente" />
  <AppLayout>
    <div class="db">
      <!-- Cabecera -->
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Editar Expediente</h1>
          <p class="db__sub">Modifica la información del caso y actualiza su estado.</p>
        </div>
      </div>

      <div class="inferior">
        <!-- Contenido Principal -->
        <div class="panel">
          <form class="formulario" @submit.prevent="submit" novalidate>
            
            <div class="campos-fila">
              <!-- Número de Expediente -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.numero_expediente}">
                <label class="campo__label">Número de Expediente <span class="req">*</span></label>
                <div class="input-grupo">
                  <FileText class="input-ico" />
                  <input type="text" class="input-text" v-model="form.numero_expediente" placeholder="Ej. EXP-2026-001" />
                </div>
                <span class="error-msg" v-if="form.errors.numero_expediente">{{ form.errors.numero_expediente }}</span>
              </div>

              <!-- Cliente -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.cliente}">
                <label class="campo__label">Información del Cliente <span class="req">*</span></label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <input type="text" class="input-text" v-model="form.cliente" placeholder="Nombre completo del cliente" />
                </div>
                <span class="error-msg" v-if="form.errors.cliente">{{ form.errors.cliente }}</span>
              </div>
            </div>

            <div class="campos-fila">
              <!-- Tipo de Proceso -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.tipo_proceso}">
                <label class="campo__label">Tipo de Proceso Jurídico <span class="req">*</span></label>
                <div class="input-grupo">
                  <Gavel class="input-ico" />
                  <select class="input-select" v-model="form.tipo_proceso">
                    <option value="Civil">Civil</option>
                    <option value="Penal">Penal</option>
                    <option value="Laboral">Laboral</option>
                    <option value="Familia">Familia</option>
                    <option value="Administrativo">Administrativo</option>
                  </select>
                </div>
                <span class="error-msg" v-if="form.errors.tipo_proceso">{{ form.errors.tipo_proceso }}</span>
              </div>

              <!-- Asesor Responsable -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.asesor_id}">
                <label class="campo__label">Asesor Responsable <span class="req">*</span></label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <select class="input-select" v-model="form.asesor_id">
                    <option value="" disabled>Seleccione un asesor...</option>
                    <option v-for="asesor in asesores" :key="asesor.id" :value="asesor.id">
                      {{ asesor.nombre }} {{ asesor.apellido }}
                    </option>
                  </select>
                </div>
                <span class="error-msg" v-if="form.errors.asesor_id">{{ form.errors.asesor_id }}</span>
              </div>
            </div>

            <div class="campos-fila">
              <!-- Fecha de Ingreso -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.fecha_ingreso}">
                <label class="campo__label">Fecha de Ingreso <span class="req">*</span></label>
                <div class="input-grupo">
                  <Calendar class="input-ico" />
                  <input type="date" class="input-text" v-model="form.fecha_ingreso" />
                </div>
                <span class="error-msg" v-if="form.errors.fecha_ingreso">{{ form.errors.fecha_ingreso }}</span>
              </div>

              <!-- Estado -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.estado}">
                <label class="campo__label">Estado del Expediente <span class="req">*</span></label>
                <div class="input-grupo">
                  <FileText class="input-ico" />
                  <select class="input-select" v-model="form.estado">
                    <option value="Abierto">Abierto</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Resuelto">Resuelto</option>
                    <option value="Cerrado">Cerrado</option>
                    <option value="Archivado">Archivado</option>
                  </select>
                </div>
                <span class="error-msg" v-if="form.errors.estado">{{ form.errors.estado }}</span>
              </div>
            </div>

            <!-- Descripción -->
            <div class="campo" :class="{'has-error': form.errors.descripcion}">
              <label class="campo__label">Descripción del Caso</label>
              <div class="input-grupo" style="align-items: flex-start;">
                <textarea class="input-text" v-model="form.descripcion" rows="4" placeholder="Detalles adicionales sobre el caso..." style="padding-left: 12px; resize: vertical;"></textarea>
              </div>
              <span class="error-msg" v-if="form.errors.descripcion">{{ form.errors.descripcion }}</span>
            </div>

            <div class="alerta alerta--error mt-4" v-if="Object.keys(form.errors).length > 0">
              <AlertTriangle class="alerta__ico" /><span>Por favor revisa los errores en el formulario para poder continuar.</span>
            </div>

            <hr class="divider" />

            <!-- Botones de Acción -->
            <div class="formulario__acciones">
              <Link href="/expedientes" class="btn-sec">
                <X class="btn-sec__ico" /> Cancelar
              </Link>
              <button type="submit" class="btn" :disabled="form.processing" :class="{'opacity-50 cursor-not-allowed': form.processing}">
                <Save class="btn__ico" /> Guardar Cambios
              </button>
            </div>
          </form>
        </div>

        <!-- Panel Lateral Informativo -->
        <div class="panel panel--chico panel-info">
          <div class="panel__head">
            <h2 class="panel__titulo">Auditoría del Caso</h2>
          </div>
          <div class="info-lista">
            <div class="info-item">
              <span class="info-label">Fecha de creación</span>
              <span class="info-valor">{{ formatDate(expediente.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Registrado por</span>
              <span class="info-valor">{{ expediente.creador ? (expediente.creador.nombre + ' ' + expediente.creador.apellido) : 'Sistema' }}</span>
            </div>
            <div class="info-item mt-2">
              <span class="info-label">Última modificación</span>
              <span class="info-valor">{{ formatDate(expediente.updated_at) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Modificado por</span>
              <span class="info-valor">{{ expediente.modificador ? (expediente.modificador.nombre + ' ' + expediente.modificador.apellido) : 'N/A' }}</span>
            </div>
          </div>
          <hr class="divider" />
          <div class="alerta alerta--info">
            <Clock class="alerta__ico" />
            <span style="font-size: 11px;">Al cambiar el estado a "Cerrado" o "Archivado", el caso dejará de aparecer como activo en el Dashboard.</span>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ── Reset y base ── */
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }

.inferior { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }

.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel--chico { padding:20px; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.panel__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; }
.divider { border:0; border-top:1px solid #E2E8F0; margin:20px 0; }
.req { color: #ef4444; }

/* ── Formulario y Campos ── */
.formulario { display:flex; flex-direction:column; gap:20px; }
.campos-fila { display:flex; gap:16px; }
.campo { display:flex; flex-direction:column; gap:6px; flex: 1; }
.campo--mitad { width: 50%; }

.campo__label { font-size:13px; font-weight:500; color:#475569; }

.input-grupo { position: relative; display: flex; align-items: center; }
.input-ico { position: absolute; left: 12px; width: 16px; height: 16px; color: #94A3B8; }

.input-text, .input-select {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #CBD5E1;
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  color: #1E293B;
  background-color: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.input-text:focus, .input-select:focus { outline: none; border-color: #185FA5; box-shadow: 0 0 0 3px #EFF6FF; }
.has-error .input-text, .has-error .input-select { border-color: #ef4444; }
.has-error .input-text:focus, .has-error .input-select:focus { box-shadow: 0 0 0 3px #FEE2E2; }
.error-msg { font-size:11px; color:#ef4444; margin-top:2px; font-weight: 500; display:block; }

.input-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; }

/* ── Botones ── */
.formulario__acciones { display:flex; justify-content:flex-end; gap:12px; }

.btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; transition:background .15s; border:none; cursor:pointer; font-family:inherit; }
.btn:hover:not(:disabled) { background:#144d87; }
.btn__ico { width:16px; height:16px; }

.btn-sec { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; font-family:inherit; transition:background .15s; text-decoration: none;}
.btn-sec:hover { background:#F1F5F9; color:#1E293B; }
.btn-sec__ico { width:16px; height:16px; }

.opacity-50 { opacity: 0.5; }
.cursor-not-allowed { cursor: not-allowed; }

/* ── Info Lateral ── */
.info-lista { display:flex; flex-direction:column; gap:12px; }
.info-item { display:flex; flex-direction:column; gap:2px; }
.info-label { font-size:11px; color:#64748B; font-weight:500; text-transform:uppercase; letter-spacing:0.02em; }
.info-valor { font-size:13px; color:#1E293B; font-weight:500; }
.mt-2 { margin-top: 8px; }

/* ── Alertas ── */
.alerta { display:flex; align-items:flex-start; gap:8px; padding:10px 12px; border-radius:8px; font-size:12px; border-left:3px solid currentColor; line-height:1.4; }
.alerta--error   { background:#FEE2E2; color:#991B1B; }
.alerta--info    { background:#DBEAFE; color:#1E40AF; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; margin-top:2px; }
.mt-4 { margin-top: 16px; }

/* ── Responsive ── */
@media (max-width:1100px) {
  .inferior { grid-template-columns:1fr; }
  .panel-info { order: -1; }
}
@media (max-width:640px) {
  .campos-fila { flex-direction: column; }
  .campo--mitad { width: 100%; }
  .formulario__acciones { flex-direction: column-reverse; }
  .btn, .btn-sec { width: 100%; }
}
</style>
