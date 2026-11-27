<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { 
  X, 
  ChevronRight, 
  ChevronLeft, 
  Check, 
  Compass, 
  Sparkles,
  ExternalLink,
  Layers,
  Play,
  Pause,
  MousePointer2
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

const emit = defineEmits(['update:modelValue', 'cerrar', 'cambiarAModoVentana']);

const pasoActual = ref(0);
const rectElemento = ref(null);
const posicionTooltip = ref({ top: 0, left: 0, placement: 'bottom' });
const elementoEncontrado = ref(false);

// Modo reproducción automática ("El solo se va ejecutando")
const reproduciendoAuto = ref(true); // Se inicia por defecto en modo automático interactivo
const progresoPaso = ref(0);
let intervaloAutoPlay = null;
const duracionPasoMs = 5000; // 5 segundos por paso
const intervaloTickMs = 100;

const iniciarAutoPlay = () => {
  detenerAutoPlay();
  progresoPaso.value = 0;
  intervaloAutoPlay = setInterval(() => {
    progresoPaso.value += (intervaloTickMs / duracionPasoMs) * 100;
    if (progresoPaso.value >= 100) {
      progresoPaso.value = 0;
      if (!esUltimoPaso.value) {
        pasoActual.value++;
      } else {
        detenerAutoPlay();
        reproduciendoAuto.value = false;
      }
    }
  }, intervaloTickMs);
};

const detenerAutoPlay = () => {
  if (intervaloAutoPlay) {
    clearInterval(intervaloAutoPlay);
    intervaloAutoPlay = null;
  }
};

const toggleAutoPlay = () => {
  reproduciendoAuto.value = !reproduciendoAuto.value;
  if (reproduciendoAuto.value) {
    iniciarAutoPlay();
  } else {
    detenerAutoPlay();
    progresoPaso.value = 0;
  }
};

const rolUsuario = computed(() => {
  return (props.usuario?.rol || 'usuario').toLowerCase();
});

// Pasos en vivo para cada rol que buscan elementos reales con selectores data-tour
const pasosEnVivoPorRol = {
  administrador: [
    {
      selector: '[data-tour="sidebar-dashboard"]',
      titulo: '1. Dashboard y Métricas',
      descripcion: 'Visualiza el estado general del despacho en tiempo real: total de expedientes activos, audiencias programadas, personal activo y eventos de auditoría.',
      instruccion: 'Haz clic aquí en cualquier momento para regresar a tu resumen operativo principal.'
    },
    {
      selector: '[data-tour="sidebar-expedientes"]',
      titulo: '2. Expedientes Judiciales',
      descripcion: 'Supervisa y consulta la totalidad de los casos radicados en el despacho, sus estados procesales (Abierto, En Proceso, Resuelto, Archivado) y la documentación adjunta.',
      instruccion: 'Como Administrador tienes visibilidad y supervisión integral sobre todos los expedientes.'
    },
    {
      selector: '[data-tour="sidebar-audiencias"]',
      titulo: '3. Agenda de Audiencias',
      descripcion: 'Controla el calendario general de todas las audiencias judiciales programadas en los distintos juzgados y salas.',
      instruccion: 'Permite verificar que no existan traslapes de horarios ni conflictos de sala en la gestión del despacho.'
    },
    {
      selector: '[data-tour="sidebar-auditoria"], [data-tour="sidebar-auditoría"]',
      titulo: '4. Auditoría y Trazabilidad Inmutable',
      descripcion: 'Aquí supervisas el historial cronológico inmutable de todas las acciones del sistema. Nadie puede borrar o alterar este registro.',
      instruccion: 'Incluye botones en la esquina superior para exportar reportes directos a Excel (.xlsx) y CSV.'
    },
    {
      selector: '[data-tour="sidebar-usuarios"]',
      titulo: '5. Gestión de Usuarios y Roles',
      descripcion: 'Desde este módulo das de alta al personal (+ Nuevo Usuario), asignas roles (Secretario, Asesor, Practicante) y restableces contraseñas con el botón de llave.',
      instruccion: 'Este módulo es de control de accesos exclusivo para tu rol de Administrador.'
    },
    {
      selector: '[data-tour="sidebar-configuracion"], [data-tour="sidebar-configuración"]',
      titulo: '6. Configuración Global del Sistema',
      descripcion: 'Ajusta los parámetros institucionales: límite de tamaño de archivos (10 MB), formatos permitidos, prefijos de radicación y seguridad.',
      instruccion: 'Cualquier modificación queda registrada en la pestaña "Historial de Auditoría".'
    },
    {
      selector: '[data-tour="sidebar-manual"]',
      titulo: '7. Manual de Usuario (PDF)',
      descripcion: 'Consulta y descarga en cualquier momento tu manual oficial exclusivo en PDF (JD051), con todas las directivas técnicas y operativas de tu rol.',
      instruccion: 'Se abre de forma privada en una nueva pestaña sin almacenar caché en tu navegador.'
    },
    {
      selector: '[data-tour="navbar-user"]',
      titulo: '8. Mi Perfil y Seguridad',
      descripcion: 'Haz clic en tu nombre para abrir el menú personal: puedes actualizar tu teléfono de contacto, cambiar tu contraseña y cerrar sesión.',
      instruccion: '¡Listo! Ya conoces todos los apartados de tu barra lateral y tus herramientas de Administrador.'
    }
  ],

  secretario: [
    {
      selector: '[data-tour="sidebar-dashboard"]',
      titulo: '1. Dashboard Operativo del Despacho',
      descripcion: 'Tu panel central te muestra los expedientes activos, audiencias de la semana y los accesos rápidos de tu jornada.',
      instruccion: 'Es tu punto de partida para supervisar el movimiento del área jurídica.'
    },
    {
      selector: '[data-tour="sidebar-expedientes"]',
      titulo: '2. Radicación y Gestión de Expedientes',
      descripcion: 'En esta sección radica nuevos casos con el botón "+ Nuevo Expediente", asigna el Asesor Responsable y actualiza etapas procesales.',
      instruccion: 'Podrás cambiar estados (Abierto → En Proceso → Resuelto) y enviar casos concluidos a "Archivado".'
    },
    {
      selector: '[data-tour="sidebar-audiencias"]',
      titulo: '3. Agenda y Control de Audiencias',
      descripcion: 'Registra nuevas audiencias vinculadas a cada expediente. El sistema te alertará automáticamente si existe un conflicto de horario.',
      instruccion: 'Al cancelar una audiencia, se notificará de inmediato al practicante asignado.'
    },
    {
      selector: '[data-tour="sidebar-manual"]',
      titulo: '4. Manual Oficial de Secretario (PDF)',
      descripcion: 'Accede a tu manual en PDF (JD051) con los procedimientos exactos de apertura de casos, exportación a Excel y custodia de documentos.',
      instruccion: 'Disponible de forma protegida para consulta inline o descarga.'
    },
    {
      selector: '[data-tour="navbar-user"]',
      titulo: '5. Mi Perfil y Datos Personales',
      descripcion: 'Desde aquí accedes a "Mi Perfil" para actualizar tu teléfono, cambiar tu contraseña de forma segura o cerrar tu sesión.',
      instruccion: '¡Listo! Ya conoces las herramientas esenciales de tu rol.'
    }
  ],

  asesor: [
    {
      selector: '[data-tour="sidebar-dashboard"]',
      titulo: '1. Panel de Dirección Jurídica',
      descripcion: 'Supervisa en tiempo real el progreso de los expedientes bajo tu responsabilidad y el número de practicantes a tu cargo.',
      instruccion: 'Te ofrece acceso directo a los casos que requieren revisión.'
    },
    {
      selector: '[data-tour="sidebar-expedientes"]',
      titulo: '2. Casos y Asignación de Practicantes',
      descripcion: 'Entra al detalle de tus expedientes para usar el panel "Asignar / Reasignar Practicante" con visualización de carga de trabajo activa.',
      instruccion: 'También puedes examinar los documentos subidos por los practicantes y revisar el historial de actividades.'
    },
    {
      selector: '[data-tour="sidebar-audiencias"]',
      titulo: '3. Calendario – Mis Casos',
      descripcion: 'El calendario filtra automáticamente solo las audiencias pertenecientes a tus casos supervisados (no verás expedientes ajenos).',
      instruccion: 'Puedes consultar vistas de Mes, Semana o Día con horarios y salas.'
    },
    {
      selector: '[data-tour="sidebar-notificaciones"]',
      titulo: '4. Centro de Notificaciones',
      descripcion: 'Recibe alertas instantáneas cuando un practicante suba un documento o el secretario registre una audiencia en tus casos.',
      instruccion: 'Pulsa el botón "Ver caso" dentro de cualquier aviso para ir directo al expediente.'
    },
    {
      selector: '[data-tour="sidebar-manual"]',
      titulo: '5. Manual Oficial de Asesor (PDF)',
      descripcion: 'Consulta tu manual de usuario en PDF (JD051) adaptado a las directrices de supervisión jurídica y delegación de casos.',
      instruccion: 'Se abre directamente en tu navegador con visor integrado.'
    },
    {
      selector: '[data-tour="navbar-user"]',
      titulo: '6. Perfil y Cambio de Contraseña',
      descripcion: 'Edita tus datos de contacto y actualiza tu contraseña con requisitos de seguridad institucional.',
      instruccion: '¡Completaste el recorrido de inducción para Asesor!'
    }
  ],

  practicante: [
    {
      selector: '[data-tour="sidebar-dashboard"]',
      titulo: '1. Tu Espacio de Práctica Profesional',
      descripcion: 'En tu panel de inicio ves cuántos expedientes tienes asignados por tu Asesor y tus documentos aportados.',
      instruccion: 'Solo tienes acceso a los casos en los que fuiste designado oficialmente.'
    },
    {
      selector: '[data-tour="sidebar-expedientes"]',
      titulo: '2. Mis Casos Asignados y Documentos',
      descripcion: 'Consulta los expedientes bajo tu responsabilidad y entra a la sección "Subir documento" para adjuntar escritos o memoriales (hasta 10 MB).',
      instruccion: 'Tus documentos son verificados y tu Asesor los revisa de forma inmediata.'
    },
    {
      selector: '[data-tour="sidebar-notificaciones"]',
      titulo: '3. Notificaciones de Asignación',
      descripcion: 'Cada vez que tu Asesor te asigne un nuevo caso o se programe una fecha judicial, recibirás una notificación aquí.',
      instruccion: 'Revisa la campana frecuentemente para no perder plazos procesales.'
    },
    {
      selector: '[data-tour="sidebar-manual"]',
      titulo: '4. Manual Oficial de Practicante (PDF)',
      descripcion: 'Accede a tu guía completa en PDF (JD051) con el paso a paso para redactar, subir archivos y verificar tu labor.',
      instruccion: 'Disponible en cualquier momento en tu menú lateral.'
    },
    {
      selector: '[data-tour="navbar-user"]',
      titulo: '5. Mi Perfil y Asesor Asignado',
      descripcion: 'En "Mi Perfil" puedes verificar el nombre de tu Asesor Jurídico Responsable, editar tu teléfono y actualizar tu clave.',
      instruccion: '¡Excelente! Ya conoces todo lo necesario para iniciar tus actividades en JUDIGEST.'
    }
  ]
};

const pasos = computed(() => {
  return pasosEnVivoPorRol[rolUsuario.value] || pasosEnVivoPorRol.practicante;
});

const paso = computed(() => {
  return pasos.value[pasoActual.value] || pasos.value[0];
});

const esUltimoPaso = computed(() => {
  return pasoActual.value === pasos.value.length - 1;
});

// Función para localizar el elemento objetivo y calcular la posición del foco y del tooltip
const calcularPosiciones = () => {
  if (!props.modelValue) return;

  const selector = paso.value.selector;
  const el = document.querySelector(selector);

  if (el) {
    elementoEncontrado.value = true;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    const rect = el.getBoundingClientRect();
    rectElemento.value = {
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height,
      bottom: rect.bottom,
      right: rect.right
    };

    // Calcular posición óptima del tooltip
    const margen = 14;
    const anchoTooltip = 360;
    const altoTooltip = 240;

    let left = rect.left;
    let top = rect.bottom + margen;
    let placement = 'bottom';

    // Si el elemento está en la barra lateral izquierda, colocar a la derecha
    if (rect.left < 280) {
      left = rect.right + margen;
      top = Math.max(16, rect.top - 10);
      placement = 'right';
    } 
    // Si está muy cerca del borde derecho
    else if (rect.right > window.innerWidth - 380) {
      left = Math.max(16, rect.left - anchoTooltip - margen);
      top = Math.max(16, rect.top);
      placement = 'left';
    }
    // Si queda muy abajo de la pantalla
    else if (top + altoTooltip > window.innerHeight) {
      top = Math.max(16, rect.top - altoTooltip - margen);
      placement = 'top';
    }

    posicionTooltip.value = {
      left: Math.min(Math.max(16, left), window.innerWidth - anchoTooltip - 16),
      top: Math.min(Math.max(16, top), window.innerHeight - altoTooltip - 16),
      placement
    };
  } else {
    // Si el elemento no existe en esta vista, centrar el tooltip como tarjeta flotante
    elementoEncontrado.value = false;
    rectElemento.value = null;
    posicionTooltip.value = {
      left: Math.max(16, (window.innerWidth - 380) / 2),
      top: Math.max(16, (window.innerHeight - 260) / 2),
      placement: 'center'
    };
  }
};

const siguientePaso = () => {
  progresoPaso.value = 0;
  if (!esUltimoPaso.value) {
    pasoActual.value++;
    if (reproduciendoAuto.value) {
      iniciarAutoPlay();
    }
  } else {
    finalizarTour();
  }
};

const anteriorPaso = () => {
  progresoPaso.value = 0;
  if (pasoActual.value > 0) {
    pasoActual.value--;
    if (reproduciendoAuto.value) {
      iniciarAutoPlay();
    }
  }
};

const irAPaso = (indice) => {
  progresoPaso.value = 0;
  pasoActual.value = indice;
  if (reproduciendoAuto.value) {
    iniciarAutoPlay();
  }
};

const cerrarTour = () => {
  detenerAutoPlay();
  emit('update:modelValue', false);
  emit('cerrar');
};

const finalizarTour = () => {
  detenerAutoPlay();
  try {
    const id = props.usuario?.id || 'anon';
    localStorage.setItem(`judigest_tour_envivo_visto_${id}_${rolUsuario.value}`, 'completado');
  } catch (e) {}
  emit('update:modelValue', false);
  emit('cerrar');
};

const cambiarModo = () => {
  detenerAutoPlay();
  emit('update:modelValue', false);
  emit('cambiarAModoVentana');
};

watch(() => props.modelValue, (nuevoValor) => {
  if (nuevoValor) {
    pasoActual.value = 0;
    progresoPaso.value = 0;
    reproduciendoAuto.value = true;
    nextTick(() => {
      calcularPosiciones();
      iniciarAutoPlay();
    });
  } else {
    detenerAutoPlay();
  }
});

watch(pasoActual, () => {
  if (props.modelValue) {
    nextTick(() => {
      calcularPosiciones();
    });
  }
});

const onScrollOrResize = () => {
  if (props.modelValue) {
    calcularPosiciones();
  }
};

onMounted(() => {
  window.addEventListener('resize', onScrollOrResize);
  window.addEventListener('scroll', onScrollOrResize, true);
  if (props.modelValue) {
    calcularPosiciones();
    iniciarAutoPlay();
  }
});

onUnmounted(() => {
  detenerAutoPlay();
  window.removeEventListener('resize', onScrollOrResize);
  window.removeEventListener('scroll', onScrollOrResize, true);
});
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="tour-envivo-root">
      
      <!-- Fondo oscuro SVG con corte transparente (Spotlight) exacto sobre el elemento -->
      <svg class="spotlight-svg-overlay" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <mask id="spotlight-mask">
            <!-- Rectángulo blanco = fondo opaco -->
            <rect x="0" y="0" width="100%" height="100%" fill="white" />
            <!-- Recuadro negro = foco transparente sobre el elemento -->
            <rect 
              v-if="rectElemento"
              :x="rectElemento.left - 6" 
              :y="rectElemento.top - 6" 
              :width="rectElemento.width + 12" 
              :height="rectElemento.height + 12" 
              rx="10"
              fill="black" 
            />
          </mask>
        </defs>
        <rect 
          x="0" 
          y="0" 
          width="100%" 
          height="100%" 
          fill="rgba(15, 23, 42, 0.72)" 
          mask="url(#spotlight-mask)" 
        />
      </svg>

      <!-- Borde animado brillante alrededor del elemento resaltado -->
      <div 
        v-if="rectElemento" 
        class="spotlight-highlight-box"
        :style="{
          top: `${rectElemento.top - 6}px`,
          left: `${rectElemento.left - 6}px`,
          width: `${rectElemento.width + 12}px`,
          height: `${rectElemento.height + 12}px`
        }"
      >
        <span class="spotlight-pulse-ring"></span>
        
        <!-- Puntero interactivo virtual simulado que apunta al elemento en tiempo real -->
        <div class="spotlight-virtual-pointer">
          <MousePointer2 class="w-6 h-6 text-amber-400 drop-shadow-md pointer-icon" />
          <span class="pointer-bubble">
            <span class="pointer-pulse"></span>
            {{ reproduciendoAuto ? 'Guiando...' : 'Examinar aquí' }}
          </span>
        </div>
      </div>

      <!-- Tarjeta / Bocadillo flotante interactivo pegado al elemento -->
      <div 
        class="tour-live-tooltip"
        :class="`placement-${posicionTooltip.placement}`"
        :style="{
          top: `${posicionTooltip.top}px`,
          left: `${posicionTooltip.left}px`
        }"
      >
        <!-- Barra de progreso automático superior -->
        <div class="live-auto-progress-track" title="Progreso automático del paso actual">
          <div 
            class="live-auto-progress-bar"
            :style="{ width: `${progresoPaso}%` }"
            :class="{ 'paused': !reproduciendoAuto }"
          ></div>
        </div>

        <!-- Encabezado del tooltip -->
        <div class="live-tooltip-header">
          <div class="live-badge-wrapper">
            <span class="live-role-badge">ROL: {{ rolUsuario.toUpperCase() }}</span>
            <span class="live-step-badge">Paso {{ pasoActual + 1 }} de {{ pasos.length }}</span>
          </div>

          <div class="flex items-center gap-1.5">
            <!-- Botón de Modo Automático / Pausa -->
            <button 
              @click="toggleAutoPlay" 
              class="live-autoplay-toggle-btn"
              :class="{ 'is-active': reproduciendoAuto }"
              :title="reproduciendoAuto ? 'Pausar avance automático' : 'Reanudar avance automático'"
            >
              <Pause v-if="reproduciendoAuto" class="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
              <Play v-else class="w-3.5 h-3.5 text-emerald-500 fill-emerald-500" />
              <span>{{ reproduciendoAuto ? 'Auto' : 'Pausado' }}</span>
            </button>

            <button @click="cerrarTour" class="live-close-btn" title="Cerrar tour (Esc)">
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Contenido explicativo del paso -->
        <div class="live-tooltip-body">
          <h3 class="live-tooltip-title">{{ paso.titulo }}</h3>
          <p class="live-tooltip-desc">{{ paso.descripcion }}</p>
          
          <div class="live-action-guide">
            <Sparkles class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" />
            <p class="live-action-text">{{ paso.instruccion }}</p>
          </div>
        </div>

        <!-- Barra de progreso interactiva por puntos -->
        <div class="live-dots-container">
          <span 
            v-for="(p, i) in pasos" 
            :key="i"
            class="live-dot"
            :class="{ 'active': i === pasoActual, 'passed': i < pasoActual }"
            @click="irAPaso(i)"
            :title="p.titulo"
          ></span>
        </div>

        <!-- Pie con botones de acción -->
        <div class="live-tooltip-footer">
          <button 
            @click="cambiarModo" 
            class="live-btn-mode"
            title="Cambiar a la guía explicativa con ventanas"
          >
            <Layers class="w-3.5 h-3.5 mr-1" /> Ventana flotante
          </button>

          <div class="live-nav-buttons">
            <button 
              v-if="pasoActual > 0" 
              @click="anteriorPaso" 
              class="live-btn-back"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <button 
              @click="siguientePaso" 
              class="live-btn-next"
            >
              <span v-if="!esUltimoPaso">Siguiente</span>
              <span v-else>¡Listo!</span>
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
.tour-envivo-root {
  position: fixed;
  inset: 0;
  z-index: 99990;
  pointer-events: auto;
}

/* Capa SVG para el efecto Spotlight con corte transparente */
.spotlight-svg-overlay {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  z-index: 99991;
  pointer-events: auto;
}

/* Recuadro iluminado brillante sobre el elemento real */
.spotlight-highlight-box {
  position: fixed;
  border-radius: 12px;
  border: 2px solid #38bdf8;
  box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.35), 0 0 25px rgba(14, 165, 233, 0.5);
  z-index: 99992;
  pointer-events: none;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.spotlight-pulse-ring {
  position: absolute;
  inset: -6px;
  border-radius: 16px;
  border: 2px solid rgba(56, 189, 248, 0.6);
  animation: pulseHighlight 2s infinite;
}

/* Puntero virtual simulado */
.spotlight-virtual-pointer {
  position: absolute;
  right: -24px;
  bottom: -28px;
  display: flex;
  align-items: center;
  gap: 6px;
  pointer-events: none;
  animation: pointerFloat 2s ease-in-out infinite;
  z-index: 99993;
}

.pointer-icon {
  transform: rotate(-25deg);
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.4));
}

.pointer-bubble {
  background: #0f172a;
  color: #38bdf8;
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 9999px;
  border: 1px solid #38bdf8;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  white-space: nowrap;
}

.pointer-pulse {
  width: 6px;
  height: 6px;
  background: #38bdf8;
  border-radius: 9999px;
  animation: pulseRing 1.2s infinite;
}

@keyframes pointerFloat {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(-8px) translateX(4px); }
}

@keyframes pulseRing {
  0% { transform: scale(0.9); opacity: 1; }
  50% { transform: scale(1.6); opacity: 0.4; }
  100% { transform: scale(0.9); opacity: 1; }
}

@keyframes pulseHighlight {
  0% { transform: scale(0.98); opacity: 0.9; }
  50% { transform: scale(1.04); opacity: 0.3; }
  100% { transform: scale(0.98); opacity: 0.9; }
}

/* Barra de progreso de auto-avance */
.live-auto-progress-track {
  width: 100%;
  height: 3px;
  background: #e2e8f0;
  overflow: hidden;
}

.live-auto-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #06b6d4);
  transition: width 0.1s linear;
}

.live-auto-progress-bar.paused {
  background: #cbd5e1;
}

/* Botón de Auto-reproducción / Pausa */
.live-autoplay-toggle-btn {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #334155;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  transition: all 0.15s;
}

.live-autoplay-toggle-btn:hover {
  background: #e2e8f0;
}

.live-autoplay-toggle-btn.is-active {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #065f46;
}

/* Tooltip / Bocadillo flotante */
.tour-live-tooltip {
  position: fixed;
  width: 360px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(226, 232, 240, 0.9);
  z-index: 99995;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: tooltipPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  transition: top 0.25s ease, left 0.25s ease;
}

@keyframes tooltipPop {
  from { opacity: 0; transform: scale(0.92) translateY(8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.live-tooltip-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px 8px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.live-badge-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.live-role-badge {
  background: #0284c7;
  color: #ffffff;
  font-size: 10px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 9999px;
  letter-spacing: 0.05em;
}

.live-step-badge {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
}

.live-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  transition: all 0.15s;
}
.live-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.live-tooltip-body {
  padding: 14px 16px;
}

.live-tooltip-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
  line-height: 1.3;
}

.live-tooltip-desc {
  font-size: 12.5px;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 10px 0;
}

.live-action-guide {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.live-action-text {
  font-size: 11.5px;
  font-weight: 600;
  color: #1e40af;
  margin: 0;
  line-height: 1.4;
}

/* Dots */
.live-dots-container {
  display: flex;
  justify-content: center;
  gap: 6px;
  padding: 4px 16px 8px;
}

.live-dot {
  width: 7px;
  height: 7px;
  border-radius: 9999px;
  background: #cbd5e1;
  cursor: pointer;
  transition: all 0.2s;
}
.live-dot.passed {
  background: #93c5fd;
}
.live-dot.active {
  background: #2563eb;
  width: 18px;
}

/* Footer */
.live-tooltip-footer {
  padding: 10px 16px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.live-btn-mode {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  padding: 4px 6px;
  border-radius: 6px;
  transition: all 0.15s;
}
.live-btn-mode:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.live-nav-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.live-btn-back {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}
.live-btn-back:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.live-btn-next {
  background: #2563eb;
  color: #ffffff;
  border: none;
  font-size: 12px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35);
  transition: all 0.15s;
}
.live-btn-next:hover {
  background: #1d4ed8;
}

@media (max-width: 480px) {
  .tour-live-tooltip {
    width: calc(100vw - 32px) !important;
    left: 16px !important;
  }
}
</style>
