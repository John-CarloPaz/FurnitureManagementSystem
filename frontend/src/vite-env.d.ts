/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Backend origin for production (e.g. https://your-app.laravel.cloud). Empty in dev (uses the Vite proxy). */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
