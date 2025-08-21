<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// #[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $tz = config('app.timezone');
        $types = array_keys(Cache::get('queue_size', []));

        [$tmp, $time, $runAt] = $this->remember(fn() => 
            DB::table('pulse_entries')
                ->whereIn('type',$types)
                ->orderBy('id')
                ->select('type', 'value', 'timestamp')
                ->where('timestamp', '>', Carbon::now()->subMinutes(30)->timestamp)
                ->get()
                ->groupBy('type')
            );

        $queues = collect([]);

        foreach ($tmp as $queue => $results) {
            $results = $results->values();
            Log::info($results->count());
            foreach ($results as $key => $value) {
                $date = Carbon::createFromTimestamp($value->timestamp, $tz)->toDateTimeString();

                if (!isset($queues[$queue])){
                    $queues[$queue] = collect([]);
                }

                $queues[$queue][$date] = $value->value;
                }
        }


        if (Livewire::isLivewireRequest()) {
            foreach ($queues->keys() as $key => $value) {
                $this->dispatch('queues-sizes-chart-update', 
                queues: [$value => $queues[$value]], 
                start: Carbon::now($tz)->subMinutes(30)->toDateTimeString(), 
                end: Carbon::now($tz)->toDateTimeString());
            }
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
            'start' => Carbon::now($tz)->subMinutes(30)->toDateTimeString(),
            'end' => Carbon::now($tz)->toDateTimeString(),
        ]);
    }
}
