<x-pulse::card title="Queue Size" :cols="$cols">
    <x-pulse::card-header name="Queue Sizes"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}">
        <x-slot:icon>
            <x-pulse::icons.queue-list />
        </x-slot:icon>
        </x-pulse::card-header>

    <div class="flex flex-wrap gap-4" style="margin-bottom: 15px;">
        <template x-for="(c,q) in $store.pulse.colors">
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                <div class="h-0.5 w-3 rounded-full" :style="`background-color: ${c}`"></div>
                <div x-text="q"></div>
            </div>
        </template>
    </div>


    <x-pulse::scroll :expand="$expand" wire:poll.5s="">   
        @if ($queues->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="grid gap-3 mx-px mb-px">
                @foreach ($queues as $queue => $readings)
                    @php
                        list($connection, $queueID) = explode(':', $queue);
                        $sum = $sums[$queue];
                    @endphp
                    <div wire:key="{{ $queue }}">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-700 dark:text-gray-300">
                                {!! $queueID !!}
                            </h3>
                            <h3 class="text-gray-600">
                                {!! '(' . $connection . ')' !!}
                            </h3>
                        </div>

                        <div class="mt-3 relative">
                            <div
                                class="absolute -left-px -top-2 max-w-fit h-4 flex items-center px-1 text-xs leading-none text-white font-bold bg-purple-500 rounded after:[--triangle-size:4px] after:border-l-purple-500 after:absolute after:right-[calc(-1*var(--triangle-size))] after:top-[calc(50%-var(--triangle-size))] after:border-t-[length:var(--triangle-size)] after:border-b-[length:var(--triangle-size)] after:border-l-[length:var(--triangle-size)] after:border-transparent">
                                {{ number_format($sum) }}
                            </div>
                            <div wire:ignore class="h-14" x-data="queueSizeChart({
                                        queue: '{{ $queue }}',
                                        readings: @js($readings),
                                    })">
                                <canvas x-ref="canvas-{{ $queue }}"
                                    class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm"></canvas>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

@script
<script>
    window.charts = []
    Alpine.store('pulse', {
        colors: {},
        createColorset(queues) {
            let colors = {};
            let s = 360 / Object.keys(queues).length;
            Object.keys(queues).forEach((q, i) => {
                colors[q] = "hsl(" + s * i + ", 100%, 75%)";
            });
            this.colors = colors;
            return colors;
        },
    }),
    Alpine.data('queueSizeChart', (config) => ({
        init() {
            window.charts.push(
                new Chart(
                    this.$refs[`canvas-${config.queue}`], {
                    type: 'line',
                    data: {
                        labels: [config.queue],
                        datasets: this.createDataset(config.readings),
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
                                max: this.highest(config.readings),
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
                ))

            Livewire.on('queues-sizes-chart-update', ({ queues }) => {
                let q = Object.keys(queues)[0];

                let chart = window.charts.filter((c) => c.canvas.getAttribute('x-ref').includes(q))[0]

                if (chart === undefined) {
                    return
                }

                let values = Object.values(Object.values(queues))[0];

                for (let i = 0; i < Object.keys(values).length; i++) {
                    let key = chart.data.datasets[i].label
                    chart.data.datasets[i].data = values[key];
                }

                chart.options.scales.y.max = this.highest(Object.values(queues)[0])
                chart.options.scales.y.min = 0

                chart.update()
            })
        },
        highest(datasets) {
            let values = Object.values(datasets).reduce((res, o) => {
                let v = Object.values(o);
                res.push(v);
                return res;
            }, []);
            return Math.max(...values.flat()) + 1
        },
        createDataset(readings) {
            let states = Object.keys(readings);
            let color = this.$store.pulse.createColorset(readings);

            let datasets = [];
            states.forEach(function (s,i) {
                datasets[i] = {
                            label: s,
                            borderColor: color[s],
                            data: readings[s],
                        };
            });

            return datasets;           
        }
    }))
</script>
@endscript