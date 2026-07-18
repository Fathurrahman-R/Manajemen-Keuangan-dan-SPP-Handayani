<div class="p-4">
    @if(empty($credentials))
        <p class="text-center text-gray-500">Tidak ada data kredensial.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 font-medium text-gray-700">No</th>
                        <th class="px-4 py-2 font-medium text-gray-700">Nama</th>
                        <th class="px-4 py-2 font-medium text-gray-700">Username</th>
                        <th class="px-4 py-2 font-medium text-gray-700">Password</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($credentials as $index => $credential)
                        <tr>
                            <td class="px-4 py-2 text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $credential['nama'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-900 font-mono">{{ $credential['username'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $credential['password_pattern'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
