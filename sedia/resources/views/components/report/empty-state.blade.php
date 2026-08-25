@props([
    'colspan' => 1,
    'message' => 'Tidak ada data.',
])

<tr>
    <td colspan="{{ $colspan }}" data-align="center" style="padding: 2rem 1rem; text-align: center; color: #6b7280;">
        {{ $message }}
    </td>
</tr>
