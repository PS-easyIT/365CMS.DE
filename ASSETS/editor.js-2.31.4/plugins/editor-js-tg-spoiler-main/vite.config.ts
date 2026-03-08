import { defineConfig } from 'vite';
import dts from 'vite-plugin-dts';
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js'

export default defineConfig({
  build: {
    lib: {
      entry: 'src/index.ts',
      name: 'TgSpoilerEditorJS',
      fileName: (format) => `index.${format}.js`
    },
    rollupOptions: {
      external: ['@editorjs/editorjs']
    }
  },
  plugins: [
    cssInjectedByJsPlugin(),
    dts({
    insertTypesEntry: true,
  })]
});
