<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { UserPlus, Repeat, AlertCircle, UserX, AlertTriangle, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    expediente: { type: Object, required: true },
    practicantes: { type: Array, default: () => [] },
});

const form = useForm({ practicante_id: '' });

// Si ya hay practicante, la acción es REASIGNAR (JD036); si no, ASIGNAR (JD035).
const esReasignacion = computed(() => Boolean(props.expediente.practicante_id));

const nombreCompleto = (p) => (p ? `${p.nombre} ${p.apellido}` : null);

const iniciales = (p) => (p ? `${p.nombre?.[0] ?? ''}${p.apellido?.[0] ?? ''}`.toUpperCase() : '');

const practicanteActual = computed(() => nombreCompleto(props.expediente.practicante));

// Practicante elegido en la lista (el valor del select puede llegar como número o texto).
const practicanteNuevo = computed(
    () => props.practicantes.find((p) => String(p.id) === String(form.practicante_id)) ?? null
);

const textoBoton = computed(() => {
    if (form.processing) return 'Guardando...';
    return esReasignacion.value ? 'Reasignar caso' : 'Confirmar asignación';
});

/* ---------------------------- Guardado ---------------------------- */

const guardar = () => {
    const url = `/expedientes/${props.expediente.id}/practicante`;
    const opciones = { preserveScroll: true, onSuccess: () => form.reset() };

    if (esReasignacion.value) {
        form.put(url, opciones);
    } else {
        form.post(url, opciones);
    }
};

const enviar = () => {
    form.clearErrors();

    // Validación en cliente (el servidor vuelve a validar siempre).
    if (!form.practicante_id) {
        form.setError(
            'practicante_id',
            esReasignacion.value
                ? 'Debe seleccionar un nuevo practicante para reasignar el caso.'
                : 'Debe seleccionar un practicante para asignar al caso.'
        );
        return;
    }

    if (esReasignacion.value) {
        // Reasignar pide confirmación en un diálogo propio.
        mostrarConfirmacion.value = true;
        return;
    }

    guardar();
};

/* ------------------- Diálogo de confirmación -------------------- */

const mostrarConfirmacion = ref(false);
const btnCancelar = ref(null);
const btnConfirmar = ref(null);

const cancelarConfirmacion = () => {
    if (form.processing) return;
    mostrarConfirmacion.value = false;
};

const confirmarReasignacion = () => {
    mostrarConfirmacion.value = false;
    guardar();
};

// Mantiene el foco dentro del diálogo (Tab / Shift+Tab alternan entre los dos botones).
const atraparTab = (evento) => {
    const primero = btnCancelar.value;
    const ultimo = btnConfirmar.value;
    if (!primero || !ultimo) return;

    if (evento.shiftKey && document.activeElement === primero) {
        evento.preventDefault();
        ultimo.focus();
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
        evento.preventDefault();
        primero.focus();
    }
};

watch(mostrarConfirmacion, async (abierto) => {
    document.body.style.overflow = abierto ? 'hidden' : '';

    if (abierto) {
        await nextTick();
        btnCancelar.value?.focus(); // la opción segura queda enfocada por defecto
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <section class="asig">
        <header class="asig__head">
            <div class="asig__icono">
                <component :is="esReasignacion ? Repeat : UserPlus" />
            </div>
            <div>
                <h2>{{ esReasignacion ? 'Reasignar practicante' : 'Asignar practicante' }}</h2>
                <p>
                    {{ esReasignacion
                        ? 'Traslada este caso a otro practicante.'
                        : 'Elige quién trabajará este caso.' }}
                </p>
            </div>
        </header>

        <div class="asig__cuerpo">
            <!-- Practicante actual -->
            <div class="asig__actual">
                <span class="asig__etiqueta">Practicante actual</span>

                <div v-if="practicanteActual" class="asig__persona">
                    <span class="asig__avatar">{{ iniciales(expediente.practicante) }}</span>
                    <strong>{{ practicanteActual }}</strong>
                </div>

                <div v-else class="asig__sin">
                    <UserX />
                    <span>Sin practicante asignado</span>
                </div>
            </div>

            <p v-if="practicantes.length === 0" class="asig__vacio">
                No hay practicantes disponibles en este momento.
            </p>

            <form v-else class="asig__form" novalidate @submit.prevent="enviar">
                <label class="asig__etiqueta" for="practicante_id">
                    {{ esReasignacion ? 'Nuevo practicante' : 'Practicante' }}
                </label>

                <select
                    id="practicante_id"
                    v-model="form.practicante_id"
                    :disabled="form.processing"
                    :class="{ 'asig__select--error': form.errors.practicante_id }"
                    :aria-invalid="Boolean(form.errors.practicante_id)"
                    aria-describedby="practicante_error"
                >
                    <option value="">Seleccione un practicante...</option>
                    <option v-for="p in practicantes" :key="p.id" :value="p.id">
                        {{ p.nombre }} {{ p.apellido }} · {{ p.casos_activos }} caso(s)
                    </option>
                </select>

                <p
                    v-if="form.errors.practicante_id"
                    id="practicante_error"
                    class="asig__error"
                    role="alert"
                >
                    <AlertCircle />
                    <span>{{ form.errors.practicante_id }}</span>
                </p>

                <button type="submit" class="asig__btn" :disabled="form.processing">
                    {{ textoBoton }}
                </button>
            </form>
        </div>
    </section>

    <!-- DIÁLOGO DE CONFIRMACIÓN (solo al reasignar) -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="mostrarConfirmacion"
                class="modal"
                @click.self="cancelarConfirmacion"
                @keydown.esc="cancelarConfirmacion"
                @keydown.tab="atraparTab"
            >
                <div
                    class="modal__caja"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="reasignar-titulo"
                    aria-describedby="reasignar-descripcion"
                >
                    <div class="modal__icono">
                        <AlertTriangle />
                    </div>

                    <h3 id="reasignar-titulo">¿Reasignar este caso?</h3>

                    <p id="reasignar-descripcion" class="modal__texto">
                        Vas a trasladar el expediente
                        <strong>{{ expediente.numero_expediente }}</strong>
                        a otro practicante.
                    </p>

                    <div class="modal__cambio">
                        <div class="modal__persona">
                            <span class="modal__rol">Practicante actual</span>
                            <span class="modal__avatar modal__avatar--actual">
                                {{ iniciales(expediente.practicante) }}
                            </span>
                            <strong>{{ practicanteActual }}</strong>
                        </div>

                        <ArrowRight class="modal__flecha" />

                        <div class="modal__persona">
                            <span class="modal__rol">Nuevo practicante</span>
                            <span class="modal__avatar modal__avatar--nuevo">
                                {{ iniciales(practicanteNuevo) }}
                            </span>
                            <strong>{{ nombreCompleto(practicanteNuevo) }}</strong>
                        </div>
                    </div>

                    <ul class="modal__avisos">
                        <li>{{ practicanteActual }} dejará de tener acceso a este expediente.</li>
                        <li>{{ nombreCompleto(practicanteNuevo) }} recibirá una notificación.</li>
                        <li>El cambio quedará registrado en el historial del expediente.</li>
                    </ul>

                    <div class="modal__acciones">
                        <button
                            ref="btnCancelar"
                            type="button"
                            class="modal__btn modal__btn--sec"
                            :disabled="form.processing"
                            @click="cancelarConfirmacion"
                        >
                            Cancelar
                        </button>

                        <button
                            ref="btnConfirmar"
                            type="button"
                            class="modal__btn modal__btn--prim"
                            :disabled="form.processing"
                            @click="confirmarReasignacion"
                        >
                            Sí, reasignar caso
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Estilos propios: no dependen de los de la página que lo contiene. */
.asig {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
    overflow: hidden;
    font-family: 'Poppins', 'Inter', sans-serif;
    color: #1E293B;
}

.asig__head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 18px;
    background: #F8FBFF;
    border-bottom: 1px solid #E2E8F0;
}

.asig__icono {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #185FA5;
    color: #fff;
    border-radius: 10px;
}

.asig__icono svg { width: 18px; height: 18px; }

.asig__head h2 { margin: 0; font-size: 14px; font-weight: 600; }
.asig__head p  { margin: 2px 0 0; font-size: 11px; color: #64748B; }

.asig__cuerpo {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 18px;
}

.asig__etiqueta {
    display: block;
    margin-bottom: 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #64748B;
}

.asig__persona {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #F1F5F9;
    border-radius: 10px;
}

.asig__persona strong { font-size: 13px; font-weight: 600; }

.asig__avatar {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #DBEAFE;
    color: #1E40AF;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
}

.asig__sin {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: #FFFBEB;
    color: #92400E;
    border: 1px dashed #FCD34D;
    border-radius: 10px;
    font-size: 12px;
}

.asig__sin svg { width: 16px; height: 16px; flex-shrink: 0; }

.asig__vacio {
    margin: 0;
    padding: 10px 12px;
    background: #FEF3C7;
    color: #92400E;
    border-radius: 8px;
    font-size: 12px;
}

.asig__form {
    display: flex;
    flex-direction: column;
    padding-top: 16px;
    border-top: 1px solid #E2E8F0;
}

.asig__form select {
    width: 100%;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 12px;
    color: #1E293B;
}

.asig__form select:focus {
    outline: 2px solid #185FA5;
    outline-offset: 1px;
}

.asig__select--error { border-color: #DC2626 !important; }

.asig__error {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    margin: 8px 0 0;
    font-size: 11px;
    color: #B91C1C;
}

.asig__error svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }

.asig__btn {
    width: 100%;
    margin-top: 14px;
    padding: 10px 16px;
    background: #185FA5;
    color: #fff;
    border: 0;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}

.asig__btn:hover:not(:disabled) { background: #124A80; }
.asig__btn:disabled { opacity: .6; cursor: not-allowed; }

/* ------------------------- Diálogo ------------------------- */

.modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(15, 23, 42, .55);
    font-family: 'Poppins', 'Inter', sans-serif;
}

.modal__caja {
    width: 100%;
    max-width: 460px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    padding: 26px 24px 22px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .3);
    text-align: center;
    color: #1E293B;
}

.modal__icono {
    width: 52px;
    height: 52px;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FEF3C7;
    color: #B45309;
    border-radius: 50%;
}

.modal__icono svg { width: 26px; height: 26px; }

.modal__caja h3 { margin: 0 0 6px; font-size: 17px; font-weight: 600; }

.modal__texto { margin: 0 0 18px; font-size: 13px; color: #475569; line-height: 1.5; }

.modal__cambio {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 16px;
}

.modal__persona {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 8px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
}

.modal__persona strong {
    max-width: 100%;
    font-size: 12px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.modal__rol {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #64748B;
}

.modal__avatar {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 13px;
    font-weight: 700;
}

.modal__avatar--actual { background: #E2E8F0; color: #475569; }
.modal__avatar--nuevo  { background: #DBEAFE; color: #1E40AF; }

.modal__flecha { width: 20px; height: 20px; flex-shrink: 0; color: #94A3B8; }

.modal__avisos {
    margin: 0 0 20px;
    padding: 12px 14px 12px 30px;
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 10px;
    text-align: left;
    font-size: 12px;
    line-height: 1.5;
    color: #92400E;
}

.modal__avisos li + li { margin-top: 4px; }

.modal__acciones { display: flex; gap: 10px; }

.modal__btn {
    flex: 1;
    padding: 11px 14px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, border-color .15s;
}

.modal__btn:focus-visible { outline: 2px solid #185FA5; outline-offset: 2px; }
.modal__btn:disabled { opacity: .6; cursor: not-allowed; }

.modal__btn--sec { background: #fff; color: #475569; border: 1px solid #CBD5E1; }
.modal__btn--sec:hover:not(:disabled) { background: #F8FAFC; border-color: #94A3B8; }

.modal__btn--prim { background: #185FA5; color: #fff; border: 1px solid #185FA5; }
.modal__btn--prim:hover:not(:disabled) { background: #124A80; }

/* Transición de entrada y salida */
.modal-enter-active, .modal-leave-active { transition: opacity .18s ease; }
.modal-enter-active .modal__caja, .modal-leave-active .modal__caja { transition: transform .18s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal__caja, .modal-leave-to .modal__caja { transform: translateY(8px) scale(.97); }

@media (max-width: 420px) {
    .modal__cambio { flex-direction: column; }
    .modal__flecha { transform: rotate(90deg); }
    .modal__acciones { flex-direction: column-reverse; }
}
</style>