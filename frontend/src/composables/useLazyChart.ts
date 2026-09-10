import Chart, { type ChartConfiguration, type ChartTypeRegistry } from 'chart.js/auto'
import { onUnmounted, type Ref } from 'vue'

/**
 * Renders a Chart.js chart into a canvas that starts out hidden behind a
 * loading state: mutates the existing instance's data in place on repeat
 * calls instead of recreating it, and destroys it when the component unmounts.
 */
export function useLazyChart<TType extends keyof ChartTypeRegistry>(canvasRef: Ref<HTMLCanvasElement | null>) {
  let instance: Chart<TType> | null = null

  function render(config: ChartConfiguration<TType> | null): void {
    if (!canvasRef.value || !config) {
      return
    }

    if (instance) {
      instance.data = config.data
      instance.update()
      return
    }

    instance = new Chart(canvasRef.value, config)
  }

  onUnmounted(() => {
    instance?.destroy()
  })

  return { render }
}
