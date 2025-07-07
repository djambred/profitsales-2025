<x-filament::widget>
    <x-filament::card>
        <h3 class="text-lg font-bold mb-4">💰 Pendapatan PO per Cabang</h3>

        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b">
                    <th class="px-2 py-1">Cabang</th>
                    <th class="px-2 py-1 text-right">Total PO</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->branches as $branch)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $branch['name'] }}</td>
                        <td class="px-2 py-1 text-right">Rp {{ $branch['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-2 py-2 text-center text-gray-500">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::card>
</x-filament::widget>
