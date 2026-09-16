<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  FolderOpen,
  FileText,
  ShieldCheck,
  Building2,
  History,
  Save,
  RotateCcw,
  CheckCircle2,
  AlertTriangle,
  AlertCircle,
  X,
  Lock,
  Search,
  Check,
  ShieldAlert,
  Sliders,
  Calendar,
  Phone,
  Mail,
  HardDrive
} from 'lucide-vue-next';

const props = defineProps({
  categorias: {
    type: Array,
    required: true,
  },
  historial: {
    type: Array,
    default: () => [],
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

// Pestaña activa
const pestanaActiva = ref(props.categorias[0]?.id || 'expedientes');

// Mapeo de iconos por categoría
const iconosCategoria = {
  expedientes: FolderOpen,
  documentos: FileText,
  seguridad: ShieldCheck,
  institucional: Building2,
};

// Inicializamos el formulario con los valores actuales
const valoresIniciales = {};
props.categorias.forEach((cat) => {
  cat.parametros.forEach((p) => {
    valoresIniciales[p.clave] = p.valor;
  });
});

const form = useForm({ ...valoresIniciales });

// Notificación Toast Personalizada Judigest
const notificacion = ref({
  visible: false,
  tipo: 'exito',
  titulo: '',
  mensaje: '',
});

let temporizadorNotif = null;

const mostrarNotificacion = (tipo, titulo, mensaje) => {
  if (temporizadorNotif) clearTimeout(temporizadorNotif);
  notificacion.value = {
    visible: true,
    tipo,
    titulo,
    mensaje,
  };
  temporizadorNotif = setTimeout(() => {
    notificacion.value.visible = false;
  }, 4500);
};

const cerrarNotificacion = () => {
  if (temporizadorNotif) clearTimeout(temporizadorNotif);
  notificacion.value.visible = false;
};

// Escuchar flash messages de Inertia
watch(
  () => props.flash,
  (nuevoFlash) => {
    if (nuevoFlash?.success) {
      mostrarNotificacion('exito', 'Configuración Actualizada', nuevoFlash.success);
    } else if (nuevoFlash?.error) {
      mostrarNotificacion('error', 'Error en Configuración', nuevoFlash.error);
    }
  },
  { immediate: true, deep: true }
);

// Errores reactivos de validación en tiempo real
const erroresEnVivo = computed(() => {
  const errs = {};

  // Prefijo expediente
  if (!form.expedientes_prefijo || !form.expedientes_prefijo.trim()) {
    errs.expedientes_prefijo = 'El prefijo de radicación no puede estar vacío.';
  } else if (form.expedientes_prefijo.length < 2 || form.expedientes_prefijo.length > 6) {
    errs.expedientes_prefijo = 'Debe tener entre 2 y 6 caracteres.';
  } else if (!/^[A-Z0-9]+$/.test(form.expedientes_prefijo)) {
    errs.expedientes_prefijo = 'Solo se permiten letras mayúsculas y números (sin espacios).';
  }

  // Max practicantes
  const maxPrac = Number(form.expedientes_max_practicantes);
  if (isNaN(maxPrac) || maxPrac < 1 || maxPrac > 5) {
    errs.expedientes_max_practicantes = 'El rango normativo es entre 1 y 5 practicantes.';
  }

  // Días inactividad
  const diasInac = Number(form.expedientes_dias_alerta_inactividad);
  if (isNaN(diasInac) || diasInac < 5 || diasInac > 60) {
    errs.expedientes_dias_alerta_inactividad = 'Los días de alerta deben estar entre 5 y 60 días.';
  }

  // Tipos de proceso
  if (!Array.isArray(form.expedientes_tipos_proceso) || form.expedientes_tipos_proceso.length === 0) {
    errs.expedientes_tipos_proceso = 'Debe mantener seleccionada al menos una materia legal habilitada.';
  }

  // Tamaño de documentos
  const tamMb = Number(form.documentos_max_tamano_mb);
  if (isNaN(tamMb) || tamMb < 2 || tamMb > 50) {
    errs.documentos_max_tamano_mb = 'El tamaño permitido debe estar entre 2 y 50 MB.';
  }

  // Formatos permitidos
  if (!form.documentos_formatos_permitidos || !form.documentos_formatos_permitidos.trim()) {
    errs.documentos_formatos_permitidos = 'Debe especificar al menos una extensión permitida.';
  }

  // Intentos login
  const intLogin = Number(form.seguridad_max_intentos_login);
  if (isNaN(intLogin) || intLogin < 3 || intLogin > 10) {
    errs.seguridad_max_intentos_login = 'Los intentos permitidos deben estar entre 3 y 10.';
  }

  // Tiempo de sesión
  const tSesion = Number(form.seguridad_tiempo_sesion_minutos);
  if (isNaN(tSesion) || tSesion < 15 || tSesion > 240) {
    errs.seguridad_tiempo_sesion_minutos = 'El tiempo de sesión debe estar entre 15 y 240 minutos.';
  }

  // Longitud contraseña
  const longPass = Number(form.seguridad_longitud_min_password);
  if (isNaN(longPass) || longPass < 6 || longPass > 20) {
    errs.seguridad_longitud_min_password = 'La longitud mínima debe estar entre 6 y 20 caracteres.';
  }

  // Nombre entidad
  if (!form.institucional_nombre_entidad || !form.institucional_nombre_entidad.trim()) {
    errs.institucional_nombre_entidad = 'El nombre del despacho no puede estar vacío.';
  }

  // Correo notificaciones
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!form.institucional_correo_notificaciones || !emailRegex.test(form.institucional_correo_notificaciones.trim())) {
    errs.institucional_correo_notificaciones = 'Ingrese una dirección de correo institucional válida.';
  }

  // Teléfono
  if (!form.institucional_telefono_contacto || !form.institucional_telefono_contacto.trim()) {
    errs.institucional_telefono_contacto = 'El teléfono de atención es obligatorio.';
  }

  return errs;
});

const esFormularioValido = computed(() => {
  return Object.keys(erroresEnVivo.value).length === 0;
});

// Guardar cambios
const guardarCambios = () => {
  if (!esFormularioValido.value) {
    mostrarNotificacion(
      'error',
      'Valores Fuera de Normativa',
      'Por favor corrija los campos marcados con advertencia antes de guardar.'
    );
    return;
  }

  form.put('/configuracion', {
    preserveScroll: true,
    onSuccess: () => {
      // Éxito gestionado por el watcher de flash
    },
    onError: (errors) => {
      const primerError = Object.values(errors)[0] || 'Ocurrió un error al guardar los parámetros.';
      mostrarNotificacion('error', 'Error al Guardar', primerError);
    },
  });
};

// Restablecer cambios
const restablecerValores = () => {
  form.reset();
  form.clearErrors();
  mostrarNotificacion('info', 'Valores Revertidos', 'Se han restaurado los valores actuales de la base de datos.');
};

// Manejo de multiselect para tipos de proceso
const toggleTipoProceso = (materia) => {
  if (!Array.isArray(form.expedientes_tipos_proceso)) {
    form.expedientes_tipos_proceso = [];
  }
  const index = form.expedientes_tipos_proceso.indexOf(materia);
  if (index >= 0) {
    if (form.expedientes_tipos_proceso.length > 1) {
      form.expedientes_tipos_proceso.splice(index, 1);
    } else {
      mostrarNotificacion('error', 'Acción no permitida', 'Debe conservar al menos una materia legal activa.');
    }
  } else {
    form.expedientes_tipos_proceso.push(materia);
  }
};

// Búsqueda en historial de auditoría
const busquedaHistorial = ref('');
const historialFiltrado = computed(() => {
  if (!busquedaHistorial.value.trim()) {
    return props.historial;
  }
  const q = busquedaHistorial.value.toLowerCase();
  return props.historial.filter(
    (h) =>
      h.parametro_nombre?.toLowerCase().includes(q) ||
      h.parametro_clave?.toLowerCase().includes(q) ||
      h.categoria?.toLowerCase().includes(q) ||
      h.usuario?.nombre?.toLowerCase().includes(q) ||
      h.ip_address?.toLowerCase().includes(q)
  );
});

// Color de badges por categoría
const getBadgeClass = (categoria) => {
  const map = {
    expedientes: 'badge--azul',
    documentos: 'badge--morado',
    seguridad: 'badge--ambar',
    institucional: 'badge--verde',
  };
  return map[categoria] || 'badge--gris';
};
</script>

<template>
  <AppLayout>
    <Head title="Configuración Global del Sistema" />

    <div class="config-page">
      <!-- Encabezado Principal -->
      <header class="config-header">
        <div class="config-header__info">
          <div class="badge-role">
            <Sliders class="badge-role__icon" />
            <span>Módulo de Administración</span>
          </div>
          <h1 class="config-header__title">Configuración Global del Sistema</h1>
          <p class="config-header__desc">
            Gestiona los parámetros y reglas operativas de <strong>Judigest</strong> para adaptarlos a la normativa del área jurídica.
          </p>
        </div>

        <div class="config-header__actions">
          <button
            v-if="pestanaActiva !== 'historial'"
            type="button"
            @click="restablecerValores"
            class="btn btn--secondary"
            :disabled="form.processing || !form.isDirty"
          >
            <RotateCcw class="btn__icon" />
            <span>Descartar</span>
          </button>

          <button
            v-if="pestanaActiva !== 'historial'"
            type="button"
            @click="guardarCambios"
            class="btn btn--primary"
            :disabled="form.processing || !esFormularioValido"
          >
            <Save class="btn__icon" />
            <span>{{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}</span>
          </button>
        </div>
      </header>

      <!-- Barra de advertencia si hay cambios sin guardar o formulario inválido -->
      <div v-if="form.isDirty && pestanaActiva !== 'historial'" class="dirty-banner" :class="{ 'dirty-banner--error': !esFormularioValido }">
        <div class="dirty-banner__content">
          <AlertCircle v-if="!esFormularioValido" class="dirty-banner__icon text-red-500" />
          <AlertTriangle v-else class="dirty-banner__icon text-amber-500" />
          <span v-if="!esFormularioValido">
            Hay parámetros con valores fuera de rango o campos vacíos. Corríjalos para poder guardar.
          </span>
          <span v-else>
            Tienes cambios pendientes por aplicar. Presiona <strong>Guardar Cambios</strong> para sincronizarlos en la base de datos.
          </span>
        </div>
      </div>

      <!-- Navegación por Pestañas -->
      <div class="tabs-nav">
        <button
          v-for="cat in categorias"
          :key="cat.id"
          type="button"
          class="tab-btn"
          :class="{ 'tab-btn--active': pestanaActiva === cat.id }"
          @click="pestanaActiva = cat.id"
        >
          <component :is="iconosCategoria[cat.id] || Sliders" class="tab-btn__icon" />
          <span>{{ cat.titulo }}</span>
          <span class="tab-btn__count">{{ cat.parametros.length }}</span>
        </button>

        <button
          type="button"
          class="tab-btn tab-btn--audit"
          :class="{ 'tab-btn--active': pestanaActiva === 'historial' }"
          @click="pestanaActiva = 'historial'"
        >
          <History class="tab-btn__icon" />
          <span>Historial de Auditoría</span>
          <span class="tab-btn__count tab-btn__count--audit">{{ historial.length }}</span>
        </button>
      </div>

      <!-- Contenido de las Pestañas de Configuración -->
      <div class="tabs-content">
        <div
          v-for="cat in categorias"
          :key="cat.id"
          v-show="pestanaActiva === cat.id"
          class="category-panel"
        >
          <div class="category-panel__header">
            <div>
              <h2 class="category-panel__title">{{ cat.titulo }}</h2>
              <p class="category-panel__desc">{{ cat.subtitulo }}</p>
            </div>
          </div>

          <div class="params-grid">
            <div
              v-for="param in cat.parametros"
              :key="param.clave"
              class="param-card"
              :class="{ 'param-card--error': erroresEnVivo[param.clave] }"
            >
              <div class="param-card__header">
                <div>
                  <label :for="param.clave" class="param-card__label">
                    {{ param.nombre }}
                  </label>
                  <p class="param-card__desc">{{ param.descripcion }}</p>
                </div>
                <span class="param-card__key"><code>{{ param.clave }}</code></span>
              </div>

              <!-- Input: TIPO STRING (Prefijo, Nombre, Teléfono) -->
              <div v-if="param.tipo === 'string' && !param.opciones?.posibles" class="param-card__body">
                <div class="input-wrapper">
                  <input
                    :id="param.clave"
                    v-model="form[param.clave]"
                    :type="param.opciones?.tipo_input || 'text'"
                    class="form-input"
                    :class="{ 'form-input--error': erroresEnVivo[param.clave] }"
                    :maxlength="param.opciones?.max || 150"
                    :placeholder="param.opciones?.ayuda || 'Ingrese el valor...'"
                  />
                  <span v-if="param.opciones?.max" class="input-char-counter">
                    {{ (form[param.clave] || '').length }} / {{ param.opciones.max }}
                  </span>
                </div>
              </div>

              <!-- Input: TIPO NUMBER (Límites, MB, Días, Intentos, Minutos) -->
              <div v-else-if="param.tipo === 'number'" class="param-card__body">
                <div class="number-control">
                  <div class="number-input-box">
                    <input
                      :id="param.clave"
                      v-model.number="form[param.clave]"
                      type="number"
                      class="form-input form-input--number"
                      :class="{ 'form-input--error': erroresEnVivo[param.clave] }"
                      :min="param.opciones?.min"
                      :max="param.opciones?.max"
                      :step="param.opciones?.step || 1"
                    />
                    <span v-if="param.opciones?.unidad" class="number-unit">
                      {{ param.opciones.unidad }}
                    </span>
                  </div>

                  <div v-if="param.opciones?.min !== undefined" class="range-limits">
                    <span class="range-limits__tag">Mín: {{ param.opciones.min }} {{ param.opciones.unidad || '' }}</span>
                    <span class="range-limits__tag">Máx: {{ param.opciones.max }} {{ param.opciones.unidad || '' }}</span>
                  </div>
                </div>
              </div>

              <!-- Input: TIPO BOOLEAN (Toggles) -->
              <div v-else-if="param.tipo === 'boolean'" class="param-card__body">
                <div class="toggle-container">
                  <label class="toggle-switch">
                    <input
                      :id="param.clave"
                      v-model="form[param.clave]"
                      type="checkbox"
                      class="toggle-checkbox"
                    />
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label" :class="{ 'text-emerald-700 font-semibold': form[param.clave] }">
                    {{ form[param.clave] ? 'Habilitado y exigido por normativa' : 'Desactivado temporalmente' }}
                  </span>
                </div>
              </div>

              <!-- Input: TIPO ARRAY / MULTISELECT (Materias Jurídicas) -->
              <div v-else-if="param.tipo === 'array'" class="param-card__body">
                <div class="tags-picker">
                  <button
                    v-for="materia in (param.opciones?.posibles || [])"
                    :key="materia"
                    type="button"
                    class="tag-chip"
                    :class="{ 'tag-chip--active': form[param.clave]?.includes(materia) }"
                    @click="toggleTipoProceso(materia)"
                  >
                    <Check v-if="form[param.clave]?.includes(materia)" class="tag-chip__icon" />
                    <span>{{ materia }}</span>
                  </button>
                </div>
                <p class="text-xs text-slate-500 mt-2">
                  Haz clic en las materias para habilitarlas o excluirlas de la radicación de expedientes.
                </p>
              </div>

              <!-- Input: SELECT (Días Hábiles) -->
              <div v-else-if="param.opciones?.posibles" class="param-card__body">
                <select
                  :id="param.clave"
                  v-model="form[param.clave]"
                  class="form-select"
                  :class="{ 'form-input--error': erroresEnVivo[param.clave] }"
                >
                  <option v-for="opc in param.opciones.posibles" :key="opc" :value="opc">
                    {{ opc }}
                  </option>
                </select>
              </div>

              <!-- Mensaje de Error Reactivo en Tiempo Real -->
              <div v-if="erroresEnVivo[param.clave]" class="param-card__error-msg">
                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                <span>{{ erroresEnVivo[param.clave] }}</span>
              </div>

              <!-- Ayuda y Metadata de última modificación -->
              <div class="param-card__footer">
                <span class="param-card__help">{{ param.opciones?.ayuda }}</span>
                <span v-if="param.modificado_por" class="param-card__author">
                  Última modif.: {{ param.modificado_por.nombre_completo }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pestaña de Historial de Auditoría -->
        <div v-show="pestanaActiva === 'historial'" class="audit-panel">
          <div class="audit-panel__header">
            <div>
              <h2 class="category-panel__title">Historial de Auditoría de Configuración</h2>
              <p class="category-panel__desc">
                Registro inmutable de cada cambio de parámetros: administrador responsable, fecha, hora, valores anteriores y nuevos.
              </p>
            </div>

            <!-- Buscador en tiempo real del historial -->
            <div class="audit-search">
              <Search class="audit-search__icon" />
              <input
                v-model="busquedaHistorial"
                type="text"
                placeholder="Buscar por parámetro, usuario o IP..."
                class="audit-search__input"
              />
            </div>
          </div>

          <!-- Tabla de Auditoría -->
          <div class="audit-table-wrapper">
            <table v-if="historialFiltrado.length > 0" class="audit-table">
              <thead>
                <tr>
                  <th>Fecha y Hora</th>
                  <th>Administrador</th>
                  <th>Categoría</th>
                  <th>Parámetro</th>
                  <th>Valor Anterior</th>
                  <th>Nuevo Valor</th>
                  <th>Dirección IP</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in historialFiltrado" :key="item.id">
                  <td class="audit-table__date">
                    <div class="font-medium text-slate-800">{{ item.fecha_hora }}</div>
                    <div class="text-xs text-slate-400">{{ item.hace_tiempo }}</div>
                  </td>
                  <td>
                    <div class="user-chip">
                      <div class="user-chip__avatar">
                        {{ item.usuario.nombre.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-medium text-slate-800">{{ item.usuario.nombre }}</div>
                        <span class="text-xs text-slate-400 capitalize">{{ item.usuario.rol }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge" :class="getBadgeClass(item.categoria)">
                      {{ item.categoria }}
                    </span>
                  </td>
                  <td>
                    <div class="font-semibold text-slate-800">{{ item.parametro_nombre }}</div>
                    <code class="text-xs text-slate-400">{{ item.parametro_clave }}</code>
                  </td>
                  <td>
                    <span class="val-pill val-pill--old">
                      {{ item.valor_anterior || '—' }}
                    </span>
                  </td>
                  <td>
                    <span class="val-pill val-pill--new">
                      {{ item.valor_nuevo }}
                    </span>
                  </td>
                  <td class="text-xs font-mono text-slate-500">
                    {{ item.ip_address }}
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-else class="audit-empty">
              <History class="audit-empty__icon" />
              <p class="audit-empty__title">Sin registros que coincidan</p>
              <p class="audit-empty__desc">
                {{ busquedaHistorial ? 'No se encontraron eventos para los términos buscados.' : 'Aún no se han registrado modificaciones de parámetros en la plataforma.' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notificación Toast Personalizada Judigest -->
    <Transition name="toast">
      <div
        v-if="notificacion.visible"
        class="toast-card"
        :class="'toast-card--' + notificacion.tipo"
      >
        <div class="toast-icono-contenedor">
          <CheckCircle2 v-if="notificacion.tipo === 'exito'" class="toast-ico" />
          <AlertTriangle v-else class="toast-ico" />
        </div>
        <div class="toast-cuerpo">
          <div class="toast-titulo">{{ notificacion.titulo }}</div>
          <div class="toast-mensaje">{{ notificacion.mensaje }}</div>
        </div>
        <button
          @click="cerrarNotificacion"
          class="toast-btn-cerrar"
          type="button"
          title="Cerrar"
        >
          <X class="toast-ico-cerrar" />
        </button>
      </div>
    </Transition>
  </AppLayout>
</template>

<style scoped>
/* ── Estructura General ── */
.config-page {
  padding: 24px 32px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ── Encabezado ── */
.config-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
  margin-bottom: 24px;
}

.badge-role {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #eff6ff;
  color: #185fa5;
  border: 1px solid #bfdbfe;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
}

.badge-role__icon {
  width: 13px;
  height: 13px;
}

.config-header__title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.025em;
  margin: 0 0 6px 0;
}

.config-header__desc {
  font-size: 0.925rem;
  color: #64748b;
  margin: 0;
}

.config-header__actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

/* ── Botones ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn__icon {
  width: 16px;
  height: 16px;
}

.btn--primary {
  background-color: #185fa5;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(24, 95, 165, 0.25);
}

.btn--primary:hover:not(:disabled) {
  background-color: #134e87;
  transform: translateY(-1px);
}

.btn--primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  box-shadow: none;
}

.btn--secondary {
  background-color: #ffffff;
  color: #475569;
  border-color: #cbd5e1;
}

.btn--secondary:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #0f172a;
}

.btn--secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Banner de Cambios Pendientes ── */
.dirty-banner {
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #f59e0b;
  padding: 12px 18px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.dirty-banner--error {
  background-color: #fef2f2;
  border-color: #fee2e2;
  border-left-color: #ef4444;
}

.dirty-banner__content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.875rem;
  color: #1e293b;
}

.dirty-banner__icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

/* ── Navegación por Pestañas (Tabs) ── */
.tabs-nav {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 24px;
  overflow-x: auto;
  padding-bottom: 2px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 18px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-btn:hover {
  color: #185fa5;
  background-color: #f8fafc;
  border-radius: 6px 6px 0 0;
}

.tab-btn--active {
  color: #185fa5;
  border-bottom-color: #185fa5;
}

.tab-btn__icon {
  width: 17px;
  height: 17px;
}

.tab-btn__count {
  font-size: 0.725rem;
  background-color: #f1f5f9;
  color: #64748b;
  padding: 2px 7px;
  border-radius: 9999px;
  font-weight: 700;
}

.tab-btn--active .tab-btn__count {
  background-color: #e0f2fe;
  color: #185fa5;
}

.tab-btn__count--audit {
  background-color: #fef3c7;
  color: #b45309;
}

/* ── Contenedor de Categorías ── */
.category-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.category-panel__header {
  background-color: #ffffff;
  padding: 18px 24px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.category-panel__title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.category-panel__desc {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

/* ── Grid de Parámetros ── */
.params-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
  gap: 20px;
}

.param-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}

.param-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.param-card--error {
  border-color: #fca5a5 !important;
  background-color: #fffaf9;
}

.param-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.param-card__label {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  display: block;
  margin-bottom: 4px;
}

.param-card__desc {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.4;
  margin: 0;
}

.param-card__key code {
  font-size: 0.7rem;
  color: #64748b;
  background-color: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.param-card__body {
  margin-bottom: 12px;
}

.param-card__error-msg {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #dc2626;
  font-size: 0.775rem;
  font-weight: 600;
  margin-bottom: 10px;
}

.param-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: #94a3b8;
  border-top: 1px dashed #f1f5f9;
  padding-top: 10px;
  margin-top: auto;
}

.param-card__help {
  color: #64748b;
  font-style: italic;
}

.param-card__author {
  color: #185fa5;
  font-weight: 500;
}

/* ── Inputs y Controles ── */
.input-wrapper {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
  color: #0f172a;
  background-color: #ffffff;
  outline: none;
  transition: all 0.15s ease;
}

.form-input:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

.form-input--error {
  border-color: #ef4444 !important;
}

.input-char-counter {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.75rem;
  color: #94a3b8;
}

.form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
  color: #0f172a;
  background-color: #ffffff;
  outline: none;
}

.form-select:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

/* Number Control */
.number-control {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.number-input-box {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input--number {
  padding-right: 60px;
}

.number-unit {
  position: absolute;
  right: 14px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  pointer-events: none;
}

.range-limits {
  display: flex;
  gap: 8px;
}

.range-limits__tag {
  font-size: 0.725rem;
  background-color: #f8fafc;
  color: #475569;
  border: 1px solid #e2e8f0;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 500;
}

/* Toggle Switch */
.toggle-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}

.toggle-checkbox {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: 0.25s;
  border-radius: 34px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.25s;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-checkbox:checked + .toggle-slider {
  background-color: #10b981;
}

.toggle-checkbox:checked + .toggle-slider:before {
  transform: translateX(22px);
}

.toggle-label {
  font-size: 0.85rem;
  color: #475569;
}

/* Tags Picker */
.tags-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.825rem;
  font-weight: 600;
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tag-chip:hover {
  background-color: #e2e8f0;
  color: #1e293b;
}

.tag-chip--active {
  background-color: #eff6ff;
  color: #185fa5;
  border-color: #bfdbfe;
}

.tag-chip__icon {
  width: 14px;
  height: 14px;
}

/* ── Pestaña de Auditoría ── */
.audit-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.audit-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #ffffff;
  padding: 18px 24px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  gap: 16px;
}

.audit-search {
  position: relative;
  min-width: 320px;
}

.audit-search__icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.audit-search__input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  outline: none;
}

.audit-search__input:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.1);
}

.audit-table-wrapper {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.85rem;
}

.audit-table th {
  background-color: #f8fafc;
  padding: 12px 18px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.775rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.audit-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.audit-table tr:hover {
  background-color: #fcfdfe;
}

.audit-table__date {
  white-space: nowrap;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-chip__avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #185fa5;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.val-pill {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-family: monospace;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.val-pill--old {
  background-color: #fef2f2;
  color: #991b1b;
  text-decoration: line-through;
}

.val-pill--new {
  background-color: #ecfdf5;
  color: #065f46;
  font-weight: 600;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.badge--azul {
  background-color: #eff6ff;
  color: #1d4ed8;
}

.badge--morado {
  background-color: #faf5ff;
  color: #7e22ce;
}

.badge--ambar {
  background-color: #fffbeb;
  color: #b45309;
}

.badge--verde {
  background-color: #ecfdf5;
  color: #047857;
}

.badge--gris {
  background-color: #f1f5f9;
  color: #475569;
}

.audit-empty {
  padding: 48px 24px;
  text-align: center;
  color: #94a3b8;
}

.audit-empty__icon {
  width: 42px;
  height: 42px;
  margin: 0 auto 12px auto;
  opacity: 0.4;
}

.audit-empty__title {
  font-size: 1rem;
  font-weight: 700;
  color: #475569;
  margin: 0 0 4px 0;
}

.audit-empty__desc {
  font-size: 0.85rem;
  margin: 0;
}

/* ── Notificación Toast Personalizada Judigest ── */
.toast-card {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  background-color: #ffffff;
  border-radius: 10px;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
  max-width: 420px;
  border-left: 5px solid transparent;
}

.toast-card--exito {
  border-left-color: #10b981;
}

.toast-card--error {
  border-left-color: #ef4444;
}

.toast-card--info {
  border-left-color: #3b82f6;
}

.toast-icono-contenedor {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  flex-shrink: 0;
}

.toast-card--exito .toast-icono-contenedor {
  background-color: #d1fae5;
  color: #059669;
}

.toast-card--error .toast-icono-contenedor {
  background-color: #fee2e2;
  color: #dc2626;
}

.toast-card--info .toast-icono-contenedor {
  background-color: #dbeafe;
  color: #2563eb;
}

.toast-ico {
  width: 20px;
  height: 20px;
}

.toast-cuerpo {
  flex-grow: 1;
}

.toast-titulo {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 2px;
}

.toast-mensaje {
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.35;
}

.toast-btn-cerrar {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #94a3b8;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.toast-btn-cerrar:hover {
  background-color: #f1f5f9;
  color: #475569;
}

.toast-ico-cerrar {
  width: 16px;
  height: 16px;
}

/* Animaciones Toast */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

/* Responsive */
@media (max-width: 768px) {
  .config-page {
    padding: 16px;
  }

  .config-header {
    flex-direction: column;
    align-items: stretch;
  }

  .config-header__actions {
    justify-content: flex-end;
  }

  .params-grid {
    grid-template-columns: 1fr;
  }

  .audit-panel__header {
    flex-direction: column;
    align-items: stretch;
  }

  .audit-search {
    min-width: 100%;
  }
}
</style>
