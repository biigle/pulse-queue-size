<x-pulse::card title="Queue Size" :cols="$cols">
    <x-pulse::card-header name="Queue Size"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}">
        <x-slot:icon>
            <x-pulse::icons.queue-list />
        </x-slot:icon>
    </x-pulse::card-header>
    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($queues->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="grid gap-3 mx-px mb-px">
                @foreach ($queues as $queue => $readings)
                    @php
                        $last = $readings->flatten()->last();
                        $queueInfo = explode(':', $queue);
                    @endphp
                    <div wire:key="{{ $queue }}">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-700 dark:text-gray-300">
                                {!! $queueInfo[1] !!}
                            </h3>
                            <h3 class="text-gray-600">
                                {!! '(' . $queueInfo[0] . ')' !!}
                            </h3>
                        </div>

                        <div class="mt-3 relative">
                            <div
                                class="absolute -left-px -top-2 max-w-fit h-4 flex items-center px-1 text-xs leading-none text-white font-bold bg-purple-500 rounded after:[--triangle-size:4px] after:border-l-purple-500 after:absolute after:right-[calc(-1*var(--triangle-size))] after:top-[calc(50%-var(--triangle-size))] after:border-t-[length:var(--triangle-size)] after:border-b-[length:var(--triangle-size)] after:border-l-[length:var(--triangle-size)] after:border-transparent">
                                {{ number_format($last) }}
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
        Alpine.data('queueSizeChart', (config) => ({
            init() {
                window.charts.push(
                    new Chart(
                        this.$refs[`canvas-${config.queue}`], {
                            type: 'line',
                            data: {
                                labels: [config.queue],
                                datasets: [{
                                    label: config.queue,
                                    borderColor: '#9333ea',
                                    data: config.readings,
                                }],
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
                                                .map(item => `${item.dataset.label}`)
                                                .join(', '),
                                            label: () => null,
                                        },
                                    },
                                },
                            },
                        }
                    ))

                Livewire.on('queues-sizes-chart-update', ({
                    queues
                }) => {
                    console.log(queues);
                    let q = Object.keys(queues)[0];

                    let chart = window.charts.filter((c) => c.canvas.getAttribute('x-ref').includes(q))[
                        0]

                    if (chart === undefined) {
                        return
                    }

                    chart.data.datasets[0].data = queues[q]
                    chart.options.scales.y.max = this.highest(queues[q])
                    chart.update()
                })
            },
            highest(values) {
                return Math.max(...Object.values(values)) + 1
            },
        }))
    </script>
@endscript
