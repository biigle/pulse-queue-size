<x-pulse::card title="Queue Size" :cols="$cols">
    <x-pulse::card-header 
    name="Queue Size" 
    x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
    details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.queue-list/>
        </x-slot:icon>
    </x-pulse::card-header>
    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
    @if($queues->isEmpty())
        <x-pulse::no-results />
    @else
        <div class="grid gap-3 mx-px mb-px">
            @foreach ($queues as $queue => $readings)
                <div wire:key="{{ $queue }}">
                    <h3 class="font-bold text-gray-700 dark:text-gray-300">
                        {!! $queue !!}
                    </h3>

                    <div class="mt-3 relative">
                        {{-- <div class="absolute -left-px -top-2 max-w-fit h-4 flex items-center px-1 text-xs leading-none text-white font-bold bg-purple-500 rounded after:[--triangle-size:4px] after:border-l-purple-500 after:absolute after:right-[calc(-1*var(--triangle-size))] after:top-[calc(50%-var(--triangle-size))] after:border-t-[length:var(--triangle-size)] after:border-b-[length:var(--triangle-size)] after:border-l-[length:var(--triangle-size)] after:border-transparent">
                            {{ number_format($readings->sum) }}
                        </div> --}}

                        <div
                            wire:ignore
                            class="h-14"
                            x-data="queueSizeChart({
                                queue: '{{ $queue }}',
                                readings: @js($readings),
                            })"
                        >
                            <canvas x-ref="canvas" class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm"></canvas>
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
Alpine.data('queueSizeChart', (config) => ({
    init() {
        let chart = new Chart(
            this.$refs.canvas,
            {
                type: 'line',
                data: {
                    labels: ['queue_size'],
                    datasets: [
                        {
                            label: config.queue,
                            borderColor: '#9333ea',
                            data: config.readings,
                        }
                    ],
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
                                borderColor: (ctx) => ctx.p0.raw === 0 && ctx.p1.raw === 0 ? 'transparent' : undefined,
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false,
                        },
                        y: {
                            display: true,
                            min: 0,
                            max: this.highest(config.queue),
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                },
            }
        )

        Livewire.on('queues-sizes-chart-update', ({ queues }) => {
            if (chart === undefined) {
                return
            }

            let data = queues['default']['queue_size']

            chart.data.labels = ['queue_size']
            Object.keys(data).map(function (k) {
                if(!data[k]){
                    data[k] = 0;
                } else {
                    data[k] = parseInt(data[k])
                }
            } );
            
            chart.options.scales.y.max = this.highest(data)
            chart.data.datasets[0].data = data
            chart.update()
        })
    },
    highest(readings) {
        return Math.max(Object.values(readings));
    },
}))
</script>
@endscript
