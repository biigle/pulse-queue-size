<x-pulse::card title="Queue Size" :cols="$cols">
    <x-pulse::card-header name="Queue Size"></x-pulse::card-header>
    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
    @if($queues->isEmpty())
        <x-pulse::no-results />
    @else
        <x-pulse::table>
            <colgroup>
                <col width="100%" />
                <col width="100%" />
            </colgroup>
            <x-pulse::thead>
                <tr>
                    <x-pulse::th>Queue</x-pulse::th>
                    <x-pulse::th>Size</x-pulse::th>
                </tr>
            </x-pulse::thead>
            <tbody>
            <div>
                @foreach ($queues as $queue)
                    <tr wire:key="{{ $queue->key }}-spacer" class="h-2 first:h-0"></tr>
                    <tr wire:key="{{ $queue->key }}-row">
                    <x-pulse::td class="max-w-[1px]">
                    {!! $queue->key !!}
                    </x-pulse::td>
                    <x-pulse::td class="max-w-[1px]">
                    {!! intval($queue->value) !!}
                    </x-pulse::td>
                @endforeach
            </div>
            </tbody>
        </x-pulse::table>
    @endif
    </x-pulse::scroll>
</x-pulse::card>
