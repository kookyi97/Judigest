<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    expedientes: { type: Array, default: () => [] },
});

const form = useForm({
    expediente_id:  '',
    fecha:          '',
    hora:           '',
    tipo_audiencia: '',
    sala_juzgado:   '',
    observaciones:  '',
});

const confirmando = ref(false);

// Abre modal SOLO si todos los campos obligatorios están llenos
const abrirConfirmacion = () => {
    // Limpiar errores manuales previos
    form.clearErrors();

    let hayError = false;

    if (!form.expediente_id) { form.setError('expediente_id', 'Debe seleccionar un expediente.'); hayError = true; }
    if (!form.fecha)         { form.setError('fecha',         'La fecha es obligatoria.');         hayError = true; }
    if (!form.hora)          { form.setError('hora',          'La hora es obligatoria.');           hayError = true; }
    if (!form.tipo_audiencia){ form.setError('tipo_audiencia','El tipo de audiencia es obligatorio.'); hayError = true; }
    if (!form.sala_juzgado)  { form.setError('sala_juzgado',  'La sala o juzgado es obligatorio.'); hayError = true; }

    if (hayError) return;

    confirmando.value = true;
};

const guardar = () => {
    form.post('/audiencias', {
        onSuccess: () => { confirmando.value = false; },
        onError:   () => { confirmando.value = false; },
    });
};

const expSeleccionado = computed(() =>
    props.expedientes.find(e => e.id === parseInt(form.expediente_id)) ?? null
);
</script>

<template>
    <Head title="Registrar Audiencia" />
    <AppLayout>
        <div class="db">

            <div class="db__header">
                <div>
                    <Link href="/audiencias" class="btn-back">
                        <ArrowLeft class="btn-back__ico" /> Volver a audiencias
                    </Link>
                    <h1 class="db__titulo">Registrar Audiencia</h1>
                    <p class="db__sub">Solo el Secretario puede registrar audiencias</p>
                </div>
            </div>

            <div class="alerta alerta--info">
                <CheckCircle2 class="alerta__ico" />
                <span>Al guardar, el sistema enviará automáticamente una notificación al practicante asignado al expediente seleccionado.</span>
            </div>

            <div class="panel">
                <h2 class="panel__titulo">Datos de la audiencia</h2>

                <div class="form-grid">

                    <div class="campo campo--full">
                        <label class="campo__label">Expediente vinculado <span class="campo__req">*</span></label>
                        <select class="campo__input" :class="{ 'campo__input--error': form.errors.expediente_id }" v-model="form.expediente_id">
                            <option value="">— Selecciona un expediente —</option>
                            <option v-for="e in expedientes" :key="e.id" :value="e.id">
                                {{ e.numero_expediente }} — {{ e.cliente }}
                            </option>
                        </select>
                        <span v-if="form.errors.expediente_id" class="campo__error">
                            <AlertCircle class="campo__error-ico" /> {{ form.errors.expediente_id }}
                        </span>
                        <span v-if="expSeleccionado" class="campo__ayuda">
                            Practicante asignado:
                            <strong>{{
                                expSeleccionado.practicante
                                    ? expSeleccionado.practicante.nombre + ' ' + expSeleccionado.practicante.apellido
                                    : 'Sin practicante — no se enviará notificación'
                            }}</strong>
                        </span>
                    </div>

                    <div class="campo">
                        <label class="campo__label">Fecha de la audiencia <span class="campo__req">*</span></label>
                        <input type="date" class="campo__input" :class="{ 'campo__input--error': form.errors.fecha }" v-model="form.fecha" />
                        <span v-if="form.errors.fecha" class="campo__error">
                            <AlertCircle class="campo__error-ico" /> {{ form.errors.fecha }}
                        </span>
                    </div>

                    <div class="campo">
                        <label class="campo__label">Hora de la audiencia <span class="campo__req">*</span></label>
                        <input type="time" class="campo__input" :class="{ 'campo__input--error': form.errors.hora }" v-model="form.hora" />
                        <span v-if="form.errors.hora" class="campo__error">
                            <AlertCircle class="campo__error-ico" /> {{ form.errors.hora }}
                        </span>
                    </div>

                    <div class="campo">
                        <label class="campo__label">Tipo de audiencia <span class="campo__req">*</span></label>
                        <select class="campo__input" :class="{ 'campo__input--error': form.errors.tipo_audiencia }" v-model="form.tipo_audiencia">
                            <option value="">— Selecciona un tipo —</option>
                            <option value="Inicial">Inicial</option>
                            <option value="Pruebas">Pruebas</option>
                            <option value="Sentencia">Sentencia</option>
                            <option value="Conciliación">Conciliación</option>
                            <option value="Apelación">Apelación</option>
                            <option value="Otra">Otra</option>
                        </select>
                        <span v-if="form.errors.tipo_audiencia" class="campo__error">
                            <AlertCircle class="campo__error-ico" /> {{ form.errors.tipo_audiencia }}
                        </span>
                    </div>

                    <div class="campo">
                        <label class="campo__label">Sala o Juzgado <span class="campo__req">*</span></label>
                        <input type="text" class="campo__input" :class="{ 'campo__input--error': form.errors.sala_juzgado }"
                            placeholder="Ej: Sala 1 — Civil" v-model="form.sala_juzgado" />
                        <span v-if="form.errors.sala_juzgado" class="campo__error">
                            <AlertCircle class="campo__error-ico" /> {{ form.errors.sala_juzgado }}
                        </span>
                    </div>

                    <div class="campo campo--full">
                        <label class="campo__label">Observaciones</label>
                        <textarea class="campo__input campo__textarea" placeholder="Notas adicionales (opcional)..." v-model="form.observaciones" rows="3"></textarea>
                        <span class="campo__ayuda">Máximo 500 caracteres.</span>
                    </div>
                </div>

                <div class="form-acciones">
                    <Link href="/audiencias" class="btn-sec">Cancelar</Link>
                    <button class="btn" type="button" :disabled="form.processing" @click="abrirConfirmacion">
                        Revisar y guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal confirmación -->
        <Teleport to="body">
            <div v-if="confirmando" class="modal-overlay" @click.self="confirmando = false">
                <div class="modal">
                    <div class="modal__head">
                        <h3 class="modal__titulo">Confirmar registro de audiencia</h3>
                        <button class="modal__cerrar" @click="confirmando = false">✕</button>
                    </div>
                    <div class="modal__body">
                        <p class="modal__intro">Verifica los datos antes de guardar.</p>
                        <div class="modal__resumen">
                            <div class="resumen-fila"><span class="resumen-label">Expediente</span><span class="resumen-valor">{{ expSeleccionado?.numero_expediente }} — {{ expSeleccionado?.cliente }}</span></div>
                            <div class="resumen-fila"><span class="resumen-label">Fecha</span><span class="resumen-valor">{{ form.fecha }}</span></div>
                            <div class="resumen-fila"><span class="resumen-label">Hora</span><span class="resumen-valor">{{ form.hora }}</span></div>
                            <div class="resumen-fila"><span class="resumen-label">Tipo</span><span class="resumen-valor">{{ form.tipo_audiencia }}</span></div>
                            <div class="resumen-fila"><span class="resumen-label">Sala / Juzgado</span><span class="resumen-valor">{{ form.sala_juzgado }}</span></div>
                        </div>
                        <div class="alerta alerta--info mt12">
                            <CheckCircle2 class="alerta__ico" />
                            <span>Se notificará automáticamente al practicante <strong>{{ expSeleccionado?.practicante ? expSeleccionado.practicante.nombre + ' ' + expSeleccionado.practicante.apellido : 'sin asignar' }}</strong>.</span>
                        </div>
                    </div>
                    <div class="modal__footer">
                        <button class="btn-sec" @click="confirmando = false">Corregir datos</button>
                        <button class="btn" :disabled="form.processing" @click="guardar">
                            {{ form.processing ? 'Guardando...' : 'Confirmar y guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:20px; width:100%; box-sizing:border-box; }
.db__header { display:flex; flex-direction:column; gap:6px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }
.btn-back { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#64748B; text-decoration:none; font-weight:500; margin-bottom:6px; }
.btn-back:hover { color:#185FA5; }
.btn-back__ico { width:14px; height:14px; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; border:none; cursor:pointer; font-family:'Poppins',sans-serif; transition:background .15s; }
.btn:hover:not(:disabled) { background:#144d87; }
.btn:disabled { opacity:.6; cursor:not-allowed; }
.btn-sec { display:inline-flex; align-items:center; padding:9px 20px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; text-decoration:none; font-family:'Poppins',sans-serif; }
.btn-sec:hover { background:#F1F5F9; }
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.panel__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0 0 20px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.campo { display:flex; flex-direction:column; gap:5px; }
.campo--full { grid-column:1 / -1; }
.campo__label { font-size:13px; font-weight:500; color:#374151; }
.campo__req   { color:#EF4444; }
.campo__input { padding:9px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; font-family:'Poppins',sans-serif; color:#1E293B; background:#fff; transition:border-color .15s; }
.campo__input:focus { outline:none; border-color:#185FA5; box-shadow:0 0 0 3px #EFF6FF; }
.campo__input--error { border-color:#EF4444; background:#FFF5F5; }
.campo__input--error:focus { box-shadow:0 0 0 3px #FEE2E2; }
.campo__textarea { resize:vertical; min-height:80px; }
.campo__error { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#EF4444; font-weight:600; }
.campo__error-ico { width:13px; height:13px; flex-shrink:0; }
.campo__ayuda { font-size:11px; color:#64748B; }
.form-acciones { display:flex; justify-content:flex-end; gap:10px; margin-top:24px; padding-top:20px; border-top:1px solid #F1F5F9; }
.alerta { display:flex; align-items:flex-start; gap:8px; padding:10px 14px; border-radius:8px; font-size:13px; border-left:4px solid currentColor; }
.alerta--info { background:#EFF6FF; color:#1E40AF; border-color:#185FA5; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; margin-top:1px; }
.mt12 { margin-top:12px; }
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:999; padding:16px; }
.modal { background:#fff; border-radius:14px; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; }
.modal__head { display:flex; align-items:center; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #E2E8F0; }
.modal__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; }
.modal__cerrar { background:none; border:none; cursor:pointer; color:#94A3B8; font-size:18px; line-height:1; }
.modal__body { padding:20px; }
.modal__intro { font-size:13px; color:#64748B; margin:0 0 14px; }
.modal__resumen { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; overflow:hidden; }
.resumen-fila { display:flex; gap:12px; padding:10px 14px; border-bottom:1px solid #F1F5F9; }
.resumen-fila:last-child { border-bottom:none; }
.resumen-label { font-size:12px; font-weight:600; color:#64748B; min-width:110px; }
.resumen-valor { font-size:12px; color:#1E293B; }
.modal__footer { display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #E2E8F0; background:#F8FAFC; }
@media (max-width:640px) {
    .form-grid { grid-template-columns:1fr; }
    .campo--full { grid-column:1; }
    .form-acciones { flex-direction:column; }
    .btn, .btn-sec { width:100%; justify-content:center; }
}
</style>
