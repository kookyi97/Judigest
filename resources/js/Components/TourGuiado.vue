<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  X, 
  ChevronRight, 
  ChevronLeft, 
  Check, 
  HelpCircle, 
  Compass, 
  Sparkles, 
  Users, 
  FolderOpen, 
  Scale, 
  ShieldAlert, 
  Settings, 
  BookOpen, 
  Bell, 
  FileText, 
  UserCheck, 
  Calendar,
  Lock,
  UploadCloud
} from 'lucide-vue-next';

const props = defineProps({
  usuario: {
    type: Object,
    default: () => ({})
  },
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'cerrar', 'cambiarAModoEnVivo']);

const pasoActual = ref(0);
const noMostrarDeNuevo = ref(false);

const rolUsuario = computed(() => {
  return (props.usuario?.rol || 'usuario').toLowerCase();
});

const storageKey = computed(() => {
  const id = props.usuario?.id || 'anon';
  return `judigest_tour_completado_${id}_${rolUsuario.value}`;
});

// Definición de tours completos y especializados por cada ROL
const toursPorRol = {
  administrador: [
    {
      titulo: '1. Dashboard y Gobernanza Institucional',
      subtitulo: 'Centro de Comando, Métricas y Supervisión General',
      icono: Compass,
      descripcion: 'Como Administrador tienes el control total del sistema. Desde aquí supervisas métricas en tiempo real: expedientes activos, audiencias programadas, usuarios registrados y eventos de auditoría.',
      tip: 'Tu menú lateral izquierdo te da acceso completo a todas las secciones judiciales y de administración.',
      tipoPreview: 'dashboard_admin',
      previewData: {
        tarjetas: [
          { label: 'Usuarios Activos', valor: '24', badge: 'En 4 roles' },
          { label: 'Total Expedientes', valor: '148', badge: 'En juzgados' },
          { label: 'Eventos Auditados', valor: '1,240', badge: '100% inmutable' },
        ]
      }
    },
    {
      titulo: '2. Supervisión General de Expedientes',
      subtitulo: 'Control de Casos, Estados Procesales y Documentación',
      icono: FolderOpen,
      descripcion: 'En el módulo "Expedientes" puedes consultar el listado completo de todos los casos del despacho, filtrar por cliente, estado procesal (Abierto, En Proceso, Resuelto, Archivado) o Asesor Jurídico Responsable.',
      tip: 'Puedes abrir cualquier expediente para verificar su historial procesal y los documentos aportados por el equipo legal.',
      tipoPreview: 'expedientes_secretario',
      previewData: {
        formulario: ['Visualización Global de Casos', 'Filtro por Estado Procesal', 'Asesor Asignado', 'Historial Inmutable']
      }
    },
    {
      titulo: '3. Agenda y Control de Audiencias',
      subtitulo: 'Calendario Maestro de Fechas Judiciales',
      icono: Scale,
      descripcion: 'En "Audiencias" supervisas la agenda integral de todas las citas y audiencias judiciales fijadas por los distintos juzgados para evitar traslapes o conflictos de horarios.',
      tip: 'Permite constatar la disponibilidad de salas y el cumplimiento de plazos del personal.',
      tipoPreview: 'calendario_asesor',
      previewData: {
        audiencias: ['Control Maestro de Audiencias', 'Verificación de Traslapes', 'Salas y Juzgados']
      }
    },
    {
      titulo: '4. Trazabilidad y Auditoría Inmutable',
      subtitulo: 'Respaldo de Seguridad y Exportación a Excel/CSV',
      icono: ShieldAlert,
      descripcion: 'El módulo de "Auditoría" registra cronológicamente cada ingreso, visualización de manual, cambio de expediente o modificación sensible. Este registro es legalmente inmutable: nadie puede borrarlo ni alterarlo.',
      tip: 'Puedes filtrar por usuario, rango de fechas o incidente, y exportar reportes completos a Excel (.xlsx) o CSV en la esquina superior derecha.',
      tipoPreview: 'auditoria_admin',
      previewData: {
        metricas: ['Total Acciones: 349', 'Incidentes: 8', 'Exportar Excel (.xlsx)', 'Exportar CSV']
      }
    },
    {
      titulo: '5. Gestión Integral de Usuarios y Cuentas',
      subtitulo: 'Creación, Roles y Recuperación de Accesos',
      icono: Users,
      descripcion: 'En el módulo "Usuarios" puedes dar de alta nuevos integrantes pulsando el botón azul "+ Nuevo Usuario". Podrás asignar su rol (Secretario, Asesor, Practicante), definir su estado y restablecer contraseñas de forma segura con el botón de llave.',
      tip: 'Si un funcionario olvida su clave o se bloquea por intentos fallidos, tú puedes asignarle una nueva clave temporal desde la tabla de usuarios.',
      tipoPreview: 'usuarios_admin',
      previewData: {
        acciones: ['+ Nuevo Usuario', 'Asignar Nueva Clave (Llave)', 'Cambiar Rol', 'Desactivar Cuenta']
      }
    },
    {
      titulo: '6. Configuración Global del Despacho',
      subtitulo: 'Límites, Formatos Permitidos y Reglas Judiciales',
      icono: Settings,
      descripcion: 'En "Configuración" ajustas las reglas operativas de JUDIGEST sin necesidad de modificar código: tamaño máximo de documentos (MB), tipos de archivo permitidos, prefijos de expedientes y datos de la institución.',
      tip: 'Cada ajuste queda guardado en la pestaña "Historial de Auditoría" para que sepas quién y cuándo modificó cada parámetro.',
      tipoPreview: 'configuracion_admin',
      previewData: {
        pestanas: ['Gestión de Expedientes', 'Gestión Documental (Max 10 MB)', 'Seguridad y Accesos', 'Despacho Institucional']
      }
    },
    {
      titulo: '7. Manual de Usuario y Mi Perfil',
      subtitulo: 'Tu Seguridad y Documentación Controlada (JD051)',
      icono: BookOpen,
      descripcion: 'En el menú lateral tienes el botón "Manual de usuario" para abrir tu PDF oficial adaptado a tus funciones. Además, en la esquina superior derecha tienes "Mi Perfil" para actualizar tu teléfono y cambiar tu contraseña.',
      tip: 'El manual se abre de forma privada e inline en tu navegador garantizando acceso controlado según tu rol activo.',
      tipoPreview: 'perfil_manual',
      previewData: {
        enlaces: ['Manual de Usuario Administrador (PDF)', 'Mi Perfil (Datos y Clave)']
      }
    }
  ],

  secretario: [
    {
      titulo: '¡Bienvenido(a) Secretario(a) a JUDIGEST!',
      subtitulo: 'Gestión Documental Oficial y Radicación Judicial',
      icono: Compass,
      descripcion: 'Como Secretario(a), eres el motor del despacho: tienes la responsabilidad de abrir nuevos expedientes, actualizar etapas procesales, archivar casos concluidos, custodiar documentos y coordinar la agenda de audiencias.',
      tip: 'En tu Dashboard verás el conteo de expedientes activos, audiencias de la semana y accesos rápidos a tus tareas diarias.',
      tipoPreview: 'dashboard_secretario',
      previewData: {
        tarjetas: [
          { label: 'Expedientes Activos', valor: '86', badge: 'En trámite' },
          { label: 'Audiencias de Hoy', valor: '4', badge: 'Programadas' },
          { label: 'Documentos Registrados', valor: '312', badge: 'Verificados' },
        ]
      }
    },
    {
      titulo: 'Radicación de Expedientes Judiciales',
      subtitulo: 'Apertura de Nuevos Casos con Datos Obligatorios',
      icono: FolderOpen,
      descripcion: 'En el menú "Expedientes", haz clic en el botón azul "+ Nuevo Expediente". Completa la información del cliente, tipo de proceso (Civil, Penal, Laboral, etc.), juzgado, fecha de radicación y selecciona al Asesor Jurídico Responsable.',
      tip: 'Puedes filtrar la tabla de expedientes por cliente, número o tipo de proceso, y exportar la planilla completa a Excel.',
      tipoPreview: 'expedientes_secretario',
      previewData: {
        formulario: ['Información del Cliente *', 'Tipo de Proceso *', 'Asesor Responsable *', 'Estado Inicial: Abierto']
      }
    },
    {
      titulo: 'Cambio de Estado y Archivado de Casos',
      subtitulo: 'Control del Ciclo de Vida Procesal',
      icono: Scale,
      descripcion: 'Al hacer clic en "Editar" sobre cualquier expediente, puedes modificar su estado a "En Proceso", "Resuelto" o pasarlo a "Archivado" cuando el proceso judicial haya concluido definitivamente.',
      tip: 'Al archivar un caso, este deja de figurar como carga activa pero su historial y documentos permanecen resguardados para consultas futuras.',
      tipoPreview: 'estado_secretario',
      previewData: {
        estados: ['Abierto (Nuevo)', 'En Proceso (Trámite)', 'Resuelto (Fallo)', 'Archivado (Custodia final)']
      }
    },
    {
      titulo: 'Carga y Custodia de Documentos Digitales',
      subtitulo: 'Soporte hasta 10 MB con Verificación de Integridad',
      icono: UploadCloud,
      descripcion: 'Dentro del detalle de cada expediente dispones del bloque "Subir documento al expediente". Puedes adjuntar demandas, autos, pruebas o resoluciones en formato PDF, Word, Excel o imágenes de hasta 10 MB.',
      tip: 'Cada documento muestra quién lo subió, su fecha y hora exacta, y permite visualización o descarga segura con un solo clic.',
      tipoPreview: 'documentos_secretario',
      previewData: {
        formatos: ['PDF', 'Word (.docx)', 'Excel (.xlsx)', 'Imágenes (.png, .jpg)', 'Límite: 10 MB']
      }
    },
    {
      titulo: 'Programación y Control de Audiencias',
      subtitulo: 'Detección de Conflictos de Horario y Calendario General',
      icono: Calendar,
      descripcion: 'En el módulo "Audiencias", pulsa "+ Registrar audiencia" para vincular una fecha judicial a un expediente. El sistema te alertará automáticamente si detecta un "Conflicto de horario" con otra audiencia en la misma sala.',
      tip: 'Si necesitas cancelar una audiencia, el sistema cambiará su estado a "Cancelada" y notificará de inmediato al practicante asignado.',
      tipoPreview: 'audiencias_secretario',
      previewData: {
        campos: ['Expediente vinculado', 'Tipo de audiencia', 'Sala o Juzgado', 'Alerta de conflicto de horario']
      }
    }
  ],

  asesor: [
    {
      titulo: '¡Bienvenido(a) Asesor(a) Jurídico(a) a JUDIGEST!',
      subtitulo: 'Supervisión Técnica y Gestión de Practicantes',
      icono: Compass,
      descripcion: 'Tu rol lidera la dirección jurídica de los casos: supervisas el avance técnico de cada expediente, asignas y equilibras la carga de los practicantes a tu cargo, revisas sus escritos y apruebas sus actuaciones.',
      tip: 'Tu panel te muestra en tiempo real cuántos practicantes tienes asignados y cuántos casos activos supervisas.',
      tipoPreview: 'dashboard_asesor',
      previewData: {
        tarjetas: [
          { label: 'Casos Supervisados', valor: '34', badge: 'Bajo tu dirección' },
          { label: 'Practicantes a Cargo', valor: '6', badge: 'Equipo activo' },
          { label: 'Audiencias Próximas', valor: '5', badge: 'Mis expedientes' },
        ]
      }
    },
    {
      titulo: 'Asignación y Reasignación de Practicantes',
      subtitulo: 'Equilibrio de Carga en Tiempo Real (JD035 / JD036)',
      icono: UserCheck,
      descripcion: 'Al entrar al detalle de cualquier expediente de tu despacho, verás el panel "Asignar practicante". La lista desplegable te muestra el nombre de cada practicante junto con su cantidad exacta de casos activos para que distribuyas el trabajo de manera justa.',
      tip: 'Si un practicante concluye su periodo o cambia de área, puedes usar "Reasignar caso" para trasladarlo a otro con notificación automática.',
      tipoPreview: 'asignacion_asesor',
      previewData: {
        practicantes: [
          'Sofía Elena Ramírez — 2 casos activos',
          'Diego Alejandro Morales — 1 caso activo',
          'Carlos Daniel Vargas — 3 casos activos'
        ]
      }
    },
    {
      titulo: 'Revisión Documental y Trazabilidad de Casos',
      subtitulo: 'Lectura de Borradores, Pruebas e Historial',
      icono: FileText,
      descripcion: 'En la sección "Documentos vinculados al expediente" puedes examinar y descargar los borradores o informes aportados por tus practicantes. Además, la tarjeta "Historial del expediente" te ofrece la trazabilidad completa de cada movimiento.',
      tip: 'Puedes abrir cualquier documento en línea con el botón "Ver" o guardarlo en tu equipo con "Descargar" para hacer correcciones.',
      tipoPreview: 'documentos_asesor',
      previewData: {
        documentos: ['informe_avance_procesal.pdf', 'escrito_demanda_borrador.docx', 'anexo_pruebas.pdf']
      }
    },
    {
      titulo: 'Calendario de Mis Casos y Notificaciones',
      subtitulo: 'Seguimiento Exclusivo de tu Despacho (JD027 / JD040)',
      icono: Calendar,
      descripcion: 'En "Calendario" se filtran automáticamente solo las audiencias de tus casos supervisados (no verás las de otros asesores). Y en la campana de "Notificaciones" recibirás alertas cuando se suba un nuevo documento o se programe una fecha procesal.',
      tip: 'Haz clic en "Ver caso" dentro de cualquier notificación para ir directo al expediente correspondiente sin buscarlo manualmente.',
      tipoPreview: 'alertas_asesor',
      previewData: {
        avisos: ['Nuevo documento subido por practicante', 'Audiencia programada para la próxima semana']
      }
    }
  ],

  practicante: [
    {
      titulo: '¡Bienvenido(a) Practicante a JUDIGEST!',
      subtitulo: 'Tu Espacio de Trabajo Jurídico y Práctica Profesional',
      icono: Compass,
      descripcion: 'En JUDIGEST tienes tu espacio personalizado para consultar los expedientes que tu Asesor Jurídico te ha asignado, redactar y aportar documentos de trabajo, dar seguimiento a tus audiencias y recibir avisos inmediatos de tus casos.',
      tip: 'Por seguridad procesal, en el sistema solo tienes acceso a los casos en los que fuiste designado oficialmente.',
      tipoPreview: 'dashboard_practicante',
      previewData: {
        tarjetas: [
          { label: 'Mis Casos Asignados', valor: '6', badge: 'Activos bajo mi apoyo' },
          { label: 'Documentos Aportados', valor: '18', badge: 'Subidos por mí' },
          { label: 'Audiencias Próximas', valor: '2', badge: 'En mi calendario' },
        ]
      }
    },
    {
      titulo: 'Consulta de "Mis Casos" Asignados',
      subtitulo: 'Detalle de Expedientes y Etapas Procesales',
      icono: FolderOpen,
      descripcion: 'En el menú "Expedientes" verás la lista de los procesos que te asignó tu Asesor. Puedes buscar por cliente o número de expediente y entrar al detalle con el botón "Ver" para conocer la síntesis jurídica del caso.',
      tip: 'Verás siempre el porcentaje de avance del proceso y las directrices marcadas por tu Asesor.',
      tipoPreview: 'casos_practicante',
      previewData: {
        columnas: ['EXP-2026-001 | Cliente Demo Uno', 'Materia: Civil | Estado: En Proceso | Avance: 65%']
      }
    },
    {
      titulo: 'Subida y Descarga de Documentos del Caso',
      subtitulo: 'Aporte de Escritos, Memoriales y Pruebas',
      icono: UploadCloud,
      descripcion: 'Dentro de tu expediente asignado, baja al bloque "Subir documento". Selecciona tu escrito en formato PDF, Word (DOCX), Excel o imágenes (hasta 10 MB) y pulsa "Subir documento". Tu asesor podrá revisarlo inmediatamente.',
      tip: 'Si intentas entrar a un expediente no asignado a ti, el sistema te indicará con aviso preventivo que la carga está restringida a su responsable.',
      tipoPreview: 'subida_practicante',
      previewData: {
        pasos: ['1. Elegir archivo (.pdf o .docx)', '2. Verificar peso (< 10 MB)', '3. Presionar Subir Documento']
      }
    },
    {
      titulo: 'Consulta de tu Asesor Asignado en "Mi Perfil"',
      subtitulo: 'Información de Contacto y Cambio de Contraseña',
      icono: UserCheck,
      descripcion: 'Al hacer clic en tu nombre en la barra superior o en "Mi Perfil", verás de forma clara el nombre de tu Asesor Jurídico Responsable. Además, puedes actualizar tu número de teléfono y cambiar tu contraseña.',
      tip: 'Siempre mantén actualizado tu teléfono para que tu Asesor o el Secretario puedan contactarte oportunamente.',
      tipoPreview: 'perfil_practicante',
      previewData: {
        datos: ['Asesor asignado: Lic. Roberto Castellanos', 'Teléfono de contacto: Editable', 'Cambiar contraseña segura']
      }
    },
    {
      titulo: 'Notificaciones y Mi Calendario de Audiencias',
      subtitulo: 'Alertas Inmediatas y Preparación de Fechas Judiciales',
      icono: Bell,
      descripcion: 'Cada vez que te asignen un nuevo caso o se agende una audiencia, recibirás un aviso en la campana de notificaciones. En "Calendario" podrás consultar las fechas de tus audiencias en vista mensual o semanal para preparar tus actuaciones a tiempo.',
      tip: 'Al hacer clic sobre cualquier audiencia en el calendario verás la sala, juzgado y recomendaciones del caso.',
      tipoPreview: 'notificaciones_practicante',
      previewData: {
        alertas: ['Nuevo expediente asignado a tu cargo', 'Audiencia preliminar programada']
      }
    }
  ]
};

const pasos = computed(() => {
  return toursPorRol[rolUsuario.value] || toursPorRol.practicante;
});

const paso = computed(() => {
  return pasos.value[pasoActual.value] || pasos.value[0];
});

const esUltimoPaso = computed(() => {
  return pasoActual.value === pasos.value.length - 1;
});

const siguientePaso = () => {
  if (!esUltimoPaso.value) {
    pasoActual.value++;
  } else {
    finalizarTour();
  }
};

const anteriorPaso = () => {
  if (pasoActual.value > 0) {
    pasoActual.value--;
  }
};

const irAPaso = (index) => {
  if (index >= 0 && index < pasos.value.length) {
    pasoActual.value = index;
  }
};

const cerrarTour = () => {
  if (noMostrarDeNuevo.value) {
    try {
      localStorage.setItem(storageKey.value, 'completado');
    } catch (e) {
      // Ignorar si cookies/storage bloqueado
    }
  }
  emit('update:modelValue', false);
  emit('cerrar');
};

const finalizarTour = () => {
  try {
    localStorage.setItem(storageKey.value, 'completado');
  } catch (e) {}
  emit('update:modelValue', false);
  emit('cerrar');
};

// Reiniciar tour desde el paso 0 al abrirse
watch(() => props.modelValue, (val) => {
  if (val) {
    pasoActual.value = 0;
  }
});

// Auto-apertura inteligente para usuarios nuevos que nunca han visto la guía
onMounted(() => {
  try {
    const yaVisto = localStorage.getItem(storageKey.value);
    if (!yaVisto && props.usuario?.id) {
      // Abre el tour automáticamente en la primera visita tras un breve instante
      setTimeout(() => {
        emit('update:modelValue', true);
      }, 1000);
    }
  } catch (e) {}
});
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="tour-backdrop" @click.self="cerrarTour">
      
      <!-- Contenedor Principal del Modal de Tour -->
      <div class="tour-card" role="dialog" aria-modal="true">
        
        <!-- Encabezado con Rol, Barra de Progreso y Cerrar -->
        <div class="tour-header">
          <div class="tour-header-left">
            <span class="tour-role-badge">
              <span class="role-dot"></span>
              Rol: {{ rolUsuario.toUpperCase() }}
            </span>
            <span class="tour-step-counter">
              Paso {{ pasoActual + 1 }} de {{ pasos.length }}
            </span>
          </div>

          <button @click="cerrarTour" class="tour-close-btn" title="Cerrar guía (Esc)">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Barra de progreso superior interactiva -->
        <div class="tour-progress-bar-container">
          <div 
            v-for="(p, idx) in pasos" 
            :key="idx" 
            class="tour-progress-segment"
            :class="{ 
              'active': idx === pasoActual, 
              'completed': idx < pasoActual 
            }"
            @click="irAPaso(idx)"
            :title="`Ir a paso ${idx + 1}: ${p.titulo}`"
          ></div>
        </div>

        <!-- Cuerpo del Tour -->
        <div class="tour-body">
          
          <div class="tour-title-area">
            <div class="tour-icon-box">
              <component :is="paso.icono" class="tour-main-icon" />
            </div>
            <div class="tour-title-text">
              <h2 class="tour-title">{{ paso.titulo }}</h2>
              <p class="tour-subtitle">{{ paso.subtitulo }}</p>
            </div>
          </div>

          <!-- Descripción explicativa paso a paso -->
          <div class="tour-description">
            <p>{{ paso.descripcion }}</p>
          </div>

          <!-- Visualización tipo Pantalla / Mockup Interactiva -->
          <div class="tour-screen-mockup">
            <div class="mockup-topbar">
              <div class="mockup-dots">
                <span class="dot red"></span>
                <span class="dot yellow"></span>
                <span class="dot green"></span>
              </div>
              <span class="mockup-url">judigest.gob / {{ rolUsuario }} / {{ paso.subtitulo.toLowerCase().replace(/ /g, '-') }}</span>
            </div>

            <div class="mockup-content">
              <!-- Renderizado interactivo según el preview del paso -->
              <div v-if="paso.previewData?.tarjetas" class="mockup-cards-grid">
                <div v-for="(t, i) in paso.previewData.tarjetas" :key="i" class="mockup-stat-card">
                  <span class="mockup-stat-val">{{ t.valor }}</span>
                  <span class="mockup-stat-lbl">{{ t.label }}</span>
                  <span class="mockup-stat-badge">{{ t.badge }}</span>
                </div>
              </div>

              <div v-else-if="paso.previewData?.acciones" class="mockup-actions-list">
                <div class="mockup-section-header">Acciones Rápidas del Administrador</div>
                <div class="mockup-pills">
                  <span v-for="(a, i) in paso.previewData.acciones" :key="i" class="mockup-pill-btn">
                    <Check class="w-3.5 h-3.5 text-blue-500 mr-1.5" /> {{ a }}
                  </span>
                </div>
              </div>

              <div v-else-if="paso.previewData?.estados" class="mockup-states-flow">
                <div class="mockup-section-header">Flujo de Estados del Expediente</div>
                <div class="mockup-flow-pills">
                  <div v-for="(est, i) in paso.previewData.estados" :key="i" class="mockup-flow-item">
                    <span class="flow-step-num">{{ i + 1 }}</span>
                    <span>{{ est }}</span>
                  </div>
                </div>
              </div>

              <div v-else-if="paso.previewData?.practicantes" class="mockup-practicantes-list">
                <div class="mockup-section-header">Control de Carga de Trabajo de Practicantes</div>
                <div v-for="(pr, i) in paso.previewData.practicantes" :key="i" class="mockup-practicante-row">
                  <UserCheck class="w-4 h-4 text-emerald-600 mr-2" />
                  <span>{{ pr }}</span>
                </div>
              </div>

              <div v-else-if="paso.previewData?.documentos" class="mockup-docs-list">
                <div class="mockup-section-header">Documentos en Custodia del Caso</div>
                <div v-for="(doc, i) in paso.previewData.documentos" :key="i" class="mockup-doc-row">
                  <FileText class="w-4 h-4 text-indigo-500 mr-2" />
                  <span>{{ doc }}</span>
                  <span class="mockup-doc-tag">Verificado</span>
                </div>
              </div>

              <div v-else class="mockup-generic-box">
                <div class="mockup-section-header">Elementos Clave del Módulo</div>
                <ul class="mockup-bullet-list">
                  <li v-for="(item, i) in (paso.previewData?.campos || paso.previewData?.formatos || paso.previewData?.pasos || paso.previewData?.avisos || paso.previewData?.datos || paso.previewData?.metricas || [])" :key="i">
                    <Check class="w-4 h-4 text-blue-500 inline mr-1.5" /> {{ item }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Consejo Clave / Tip de Ayuda -->
          <div class="tour-tip-box">
            <Sparkles class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
            <p class="tour-tip-text"><strong>Consejo clave:</strong> {{ paso.tip }}</p>
          </div>

        </div>

        <!-- Pie de navegación del modal -->
        <div class="tour-footer">
          <div class="tour-footer-left">
            <button 
              @click="$emit('cambiarAModoEnVivo')" 
              class="tour-btn-mode-live"
              title="Probar el tour interactivo que ilumina los botones reales en pantalla"
            >
              <Compass class="w-4 h-4 mr-1 text-blue-600" />
              <span>Tour en vivo</span>
            </button>
            <label class="tour-checkbox-label">
              <input type="checkbox" v-model="noMostrarDeNuevo" class="tour-checkbox" />
              <span>No volver a mostrar automáticamente</span>
            </label>
          </div>

          <div class="tour-footer-right">
            <button 
              v-if="pasoActual > 0" 
              @click="anteriorPaso" 
              class="tour-btn tour-btn-secondary"
            >
              <ChevronLeft class="w-4 h-4 mr-1" /> Anterior
            </button>

            <button 
              @click="siguientePaso" 
              class="tour-btn tour-btn-primary"
            >
              <span v-if="!esUltimoPaso">Siguiente</span>
              <span v-else>¡Entendido, Comenzar!</span>
              <ChevronRight v-if="!esUltimoPaso" class="w-4 h-4 ml-1" />
              <Check v-else class="w-4 h-4 ml-1" />
            </button>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.tour-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background-color: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  animation: fadeIn 0.25s ease-out;
}

.tour-card {
  background: #ffffff;
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.8);
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.tour-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem 0.75rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.tour-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.tour-role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  letter-spacing: 0.025em;
}

.role-dot {
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background: #0284c7;
}

.tour-step-counter {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
}

.tour-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.375rem;
  border-radius: 0.375rem;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tour-close-btn:hover {
  background: #e2e8f0;
  color: #1e293b;
}

/* Barra de progreso */
.tour-progress-bar-container {
  display: flex;
  gap: 0.375rem;
  padding: 0.5rem 1.5rem 0;
  background: #ffffff;
}

.tour-progress-segment {
  flex: 1;
  height: 4px;
  background: #e2e8f0;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tour-progress-segment.completed {
  background: #3b82f6;
}

.tour-progress-segment.active {
  background: #1d4ed8;
  height: 6px;
}

/* Cuerpo */
.tour-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tour-title-area {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.tour-icon-box {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  width: 48px;
  height: 48px;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.tour-main-icon {
  width: 26px;
  height: 26px;
}

.tour-title-text {
  flex: 1;
}

.tour-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.tour-subtitle {
  font-size: 0.8125rem;
  color: #3b82f6;
  font-weight: 600;
  margin-top: 0.2rem;
  margin-bottom: 0;
}

.tour-description {
  font-size: 0.875rem;
  color: #334155;
  line-height: 1.55;
}

/* Mockup de pantalla */
.tour-screen-mockup {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 0.625rem;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}

.mockup-topbar {
  background: #e2e8f0;
  padding: 0.375rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid #cbd5e1;
}

.mockup-dots {
  display: flex;
  gap: 0.25rem;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 9999px;
}
.dot.red { background: #ef4444; }
.dot.yellow { background: #f59e0b; }
.dot.green { background: #10b981; }

.mockup-url {
  font-size: 0.7rem;
  color: #64748b;
  font-family: monospace;
}

.mockup-content {
  padding: 0.875rem;
}

.mockup-section-header {
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.mockup-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.mockup-stat-card {
  background: #ffffff;
  padding: 0.625rem;
  border-radius: 0.375rem;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  text-align: center;
}

.mockup-stat-val {
  font-size: 1.125rem;
  font-weight: 800;
  color: #1e293b;
}

.mockup-stat-lbl {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
}

.mockup-stat-badge {
  font-size: 0.65rem;
  color: #2563eb;
  margin-top: 0.25rem;
  font-weight: 600;
}

.mockup-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.mockup-pill-btn {
  background: #ffffff;
  border: 1px solid #93c5fd;
  color: #1e3a8a;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.3rem 0.6rem;
  border-radius: 0.375rem;
  display: inline-flex;
  align-items: center;
}

.mockup-flow-pills {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.mockup-flow-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 0.375rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.flow-step-num {
  background: #3b82f6;
  color: #ffffff;
  width: 18px;
  height: 18px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
}

.mockup-practicante-row,
.mockup-doc-row {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 0.375rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  color: #1e293b;
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
}

.mockup-doc-tag {
  margin-left: auto;
  font-size: 0.65rem;
  background: #dcfce7;
  color: #15803d;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-weight: 600;
}

.mockup-bullet-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.75rem;
  color: #334155;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

/* Tip */
.tour-tip-box {
  background: #fffbeb;
  border: 1px solid #fef3c7;
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.tour-tip-text {
  font-size: 0.8125rem;
  color: #92400e;
  line-height: 1.4;
  margin: 0;
}

/* Footer */
.tour-footer {
  padding: 0.875rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.tour-footer-left {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.tour-btn-mode-live {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  width: fit-content;
  transition: all 0.15s;
}

.tour-btn-mode-live:hover {
  background: #dbeafe;
  border-color: #93c5fd;
}

.tour-checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #64748b;
  cursor: pointer;
  user-select: none;
}

.tour-checkbox {
  cursor: pointer;
  accent-color: #2563eb;
}

.tour-footer-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tour-btn {
  font-size: 0.8125rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  border: none;
}

.tour-btn-secondary {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}
.tour-btn-secondary:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.tour-btn-primary {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
}
.tour-btn-primary:hover {
  background: #1d4ed8;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { 
    opacity: 0; 
    transform: translateY(16px) scale(0.98); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}

@media (max-width: 640px) {
  .mockup-cards-grid {
    grid-template-columns: 1fr;
  }
  .tour-footer {
    flex-direction: column;
    align-items: stretch;
  }
  .tour-footer-right {
    justify-content: flex-end;
  }
}
</style>
