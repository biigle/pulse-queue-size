    <x-pulse::card title="Queue Size" :cols="$cols">
        <x-pulse::card-header
            name="Queue Sizes"
            x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
            details="past {{ $this->periodForHumans() }}">

            <x-slot:icon>
                <x-pulse::icons.queue-list />
            </x-slot:icon>
        </x-pulse::card-header>
    
        <div class="flex flex-wrap gap-4">
            <template x-for="(c,q) in $store.pulse.colors">
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full" :style="`background-color: ${c}`"></div>
                    <div x-text="q" style="text-transform: capitalize"></div>
                </div>
            </template>
        </div>

        <x-pulse::scroll :expand="$expand" wire:poll.5s="">
            @if ($queues->isEmpty())
                <x-pulse::no-results />
            @else
                <div class="grid gap-3 mx-px mb-px">
                    <div class="mt-3 relative">
                        <div wire:ignore class="h-14" x-data="queueSizeChart({
                            queues: @js($queues),
                        })">
                            <canvas x-ref="canvas"
                                class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </x-pulse::scroll>
    </x-pulse::card>

@script
    <script>
        Alpine.store('pulse', {
            colors: {},
            createColorset(queues) {
                let colors = {};
                Object.keys(queues).forEach((q) => {
                    colors[q] = "hsl(" + Math.random() * 360 + ", 100%, 75%)";
                });
                this.colors = colors;
                return colors;
            },
        }),
        Alpine.data('queueSizeChart', (config) => ({
            init() {
                let chart = new Chart(
                    this.$refs.canvas,
                    {
                        type: 'line',
                        data: {
                            labels: [Object.keys(config.queues)],
                            datasets: this.createDataset(config.queues),
                            colors: []
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                autoPadding: false,
                                padding: {
                                    top: 1,
                                },
                            },
                            datasets: {
                                line: {
                                    borderWidth: 2,
                                    borderCapStyle: 'round',
                                    pointHitRadius: 10,
                                    pointStyle: false,
                                    tension: 0.2,
                                    spanGaps: false,
                                    segment: {
                                        borderColor: (ctx) => ctx.p0.raw === 0 && ctx.p1.raw === 0 ?
                                            'transparent' : undefined,
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    display: false,
                                    },
                                y: {
                                    display: false,
                                    min: 0,
                                    max: this.highest(Object.values(config.queues)),
                                },
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    mode: 'index',
                                    position: 'nearest',
                                    intersect: false,
                                    callbacks: {
                                        beforeBody: (context) => context
                                            .map(item => `${item.dataset.label}: ${item.formattedValue}`)
                                            .join(', '),
                                        label: () => null,
                                    },
                                },
                            },
                        },
                    }
                )

                Livewire.on('queues-sizes-chart-update', ({queues, start, end}) => {

                    if (chart === undefined) {
                        return
                    }

                    Object.keys(queues).forEach((q,i) => {
                        chart.data.datasets[i].data = queues[q]
                    });
                    chart.options.scales.y.max = this.highest(queues)
                    chart.update()
                })
            },
            highest(queues) {
                let values = Object.values(queues);
                return Math.max(values.map((q) => Math.max(Object.values(q)))) + 1
            },
            createDataset(queues) {
                let res = [];
                let colors = this.$store.pulse.createColorset(queues);
                Object.keys(queues).forEach((q) => {
                    res.push({
                        label: q,
                        borderColor: colors[q],
                        data: queues[q],
                    })
                });
                return res;
            },
        }));
    </script>
@endscript
