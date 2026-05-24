<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    correo: '',
    contrasena: '',
    recordar: false,
});

const verContrasena = ref(false);

const submit = () => {
    form.post('/login', { onFinish: () => form.reset('contrasena') });
};
</script>

<template>
  <div class="login-container">
    <div class="login-card">
      <div class="header-section">
        <h2>Iniciar Sesión</h2>
        <p class="subtitle">Ingresa tus credenciales para acceder al sistema</p>
      </div>

      <form @submit.prevent="submit" novalidate>
        <div class="field" :class="{ 'has-error': form.errors.correo }">
          <label for="correo">Correo electrónico</label>
          <div class="input-wrapper">
            <input 
              id="correo"
              v-model="form.correo" 
              type="email"
              placeholder="correo@empresa.com"
              autocomplete="email"
            />
          </div>
          <span class="error-msg" v-if="form.errors.correo">
            {{ form.errors.correo }}
          </span>
        </div>

        <div class="field" :class="{ 'has-error': form.errors.contrasena }">
          <label for="contrasena">Contraseña</label>
          <div class="pass-wrap">
            <input 
              id="contrasena"
              v-model="form.contrasena"
              :type="verContrasena ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
            />
            <button 
              type="button" 
              class="eye-btn"
              @click="verContrasena = !verContrasena"
              :aria-label="verContrasena ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            >
              <svg v-if="verContrasena" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 1-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </button>
          </div>
          <span class="error-msg" v-if="form.errors.contrasena">
            {{ form.errors.contrasena }}
          </span>
        </div>

        <div class="form-actions">
          <label class="remember-me">
            <input type="checkbox" v-model="form.recordar" />
            <span class="checkmark"></span>
            <span class="label-text">Recordarme</span>
          </label>
        </div>

        <button type="submit" class="btn-primary" :disabled="form.processing">
          <span v-if="form.processing" class="spinner"></span>
          <span>{{ form.processing ? 'Verificando...' : 'Ingresar al sistema' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* -----------------------------------------
   Variables de Diseño (Design Tokens)
----------------------------------------- */
:root {
  --primary-color: #185fa5;
  --primary-hover: #124980;
  --primary-light: rgba(24, 95, 165, 0.08);
  --text-main: #0f172a;
  --text-muted: #64748b;
  --bg-surface: #ffffff;
  --bg-app: #f8fafc;
  --border-color: #e2e8f0;
  --border-hover: #cbd5e1;
  --error-color: #dc2626;
  --error-bg: #fef2f2;
  --radius-md: 8px;
  --radius-lg: 16px;
  --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Base resets localizados */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  -webkit-font-smoothing: antialiased;
}

.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at top right, #f1f5f9 0%, #f8fafc 100%);
  padding: 1.5rem;
}

.login-card {
  background: var(--bg-surface, #ffffff);
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.03), 
              0 20px 25px -5px rgba(15, 23, 42, 0.08);
  width: 100%;
  max-width: 440px;
  padding: 3rem 2.5rem;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

/* -----------------------------------------
   Encabezado (Estructura y alineación)
----------------------------------------- */
.header-section {
  text-align: center;
  margin-bottom: 2.25rem;
}

h2 {
  font-size: 1.75rem;
  color: #0f172a;
  font-weight: 700;
  letter-spacing: -0.025em;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #64748b;
  font-size: 0.925rem;
  line-height: 1.5;
}

/* -----------------------------------------
   Campos de Entrada (Estilo Pro)
----------------------------------------- */
.field {
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  font-size: 0.85rem;
  color: #334155;
  letter-spacing: 0.01em;
}

.input-wrapper, .pass-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

input[type="email"],
input[type="password"],
input[type="text"] {
  width: 100%;
  padding: 0.8rem 1rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #0f172a;
  background-color: #ffffff;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Estados de las cajas de texto */
input:hover {
  border-color: #cbd5e1;
}

input:focus {
  outline: none;
  border-color: #185fa5;
  box-shadow: 0 0 0 4px rgba(24, 95, 165, 0.12);
}

/* Manejo de Errores Profesional */
.field.has-error input {
  border-color: #dc2626;
  background-color: #fef2f2;
}

.field.has-error input:focus {
  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
}

.error-msg {
  color: #dc2626;
  font-size: 0.8rem;
  margin-top: 0.5rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  animation: fadeIn 0.2s ease-in-out;
}

/* Ajuste del campo password */
.pass-wrap input {
  padding-right: 3rem;
}

.eye-btn {
  position: absolute;
  right: 0.5rem;
  height: 2.2rem;
  width: 2.2rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.2s;
}

.eye-btn:hover {
  color: #475569;
  background-color: #f1f5f9;
}

.eye-btn:focus-visible {
  outline: 2px solid #185fa5;
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* -----------------------------------------
   Checkbox Customizado (Sin usar nativo feo)
----------------------------------------- */
.form-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 1.5rem 0 1.75rem 0;
}

.remember-me {
  display: flex;
  align-items: center;
  position: relative;
  cursor: pointer;
  user-select: none;
}

.remember-me input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  height: 18px;
  width: 18px;
  background-color: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 4px;
  margin-right: 0.5rem;
  display: inline-block;
  position: relative;
  transition: all 0.2s;
}

.remember-me:hover input ~ .checkmark {
  border-color: #94a3b8;
}

.remember-me input:checked ~ .checkmark {
  background-color: #185fa5;
  border-color: #185fa5;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 5px;
  top: 2px;
  width: 4px;
  height: 8px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.remember-me input:checked ~ .checkmark:after {
  display: block;
}

.label-text {
  font-size: 0.875rem;
  color: #475569;
  font-weight: 500;
}

/* -----------------------------------------
   Botón Principal (Efectos de Micro-Interacción)
----------------------------------------- */
.btn-primary {
  width: 100%;
  padding: 0.85rem 1.5rem;
  background: #185fa5;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 6px -1px rgba(24, 95, 165, 0.2), 
              0 2px 4px -1px rgba(24, 95, 165, 0.1);
}

.btn-primary:hover:not(:disabled) {
  background: #124980;
  box-shadow: 0 10px 15px -3px rgba(24, 95, 165, 0.25), 
              0 4px 6px -2px rgba(24, 95, 165, 0.15);
}

.btn-primary:active:not(:disabled) {
  transform: scale(0.985);
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  box-shadow: none;
}

/* Spinner de Carga Profesional */
.spinner {
  width: 1.1rem;
  height: 1.1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
}

/* -----------------------------------------
   Animaciones del Sistema
----------------------------------------- */
@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>