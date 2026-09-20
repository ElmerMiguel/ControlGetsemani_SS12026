@props([
    'id' => 'chart-' . uniqid(),
    'labels' => [],
    'datasets' => [],
    'height' => '320',
])

<div
    x-data="{
        chartInstance: null,
        initChart() {
            if (!window.Chart) {
                console.error('Chart.js no está disponible en window.Chart');
                return;
            }
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }
            const canvas = document.getElementById('{{ $id }}');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            this.chartInstance = new window.Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {{ \Illuminate\Support\Js::from($labels) }},
                    datasets: {{ \Illuminate\Support\Js::from($datasets) }}
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: { family: 'system-ui', size: 12, weight: '600' },
                                boxWidth: 14,
                                boxHeight: 14,
                                padding: 16,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'Q ' + context.parsed.y.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'system-ui', size: 12 } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Q ' + value.toLocaleString('es-GT');
                                },
                                font: { family: 'system-ui', size: 11 }
                            },
                            grid: { color: 'rgba(226, 232, 240, 0.7)' }
                        }
                    }
                }
            });
        }
    }"
    x-init="$nextTick(() => initChart())"
    class="relative w-full"
    style="height: {{ $height }}px;"
>
    <canvas id="{{ $id }}"></canvas>
</div>
