@extends('tenant.layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
<div class="w-full pb-12 mt-4">

    <x-tenant-card variant="flat" class="overflow-hidden mb-8">
        <div class="bg-gray-50 border-b border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 -mx-4 -mt-4 mb-6">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Detail Pemesanan
                </h1>
                <p class="text-gray-500 text-xs md:text-sm mt-2 font-mono">
                    No. Ref: <span class="font-bold text-gray-800">{{ $booking->mainPayment->reference_id ?? 'Menunggu Pembayaran' }}</span>
                </p>
            </div>

            <div class="flex flex-col items-start md:items-end gap-2">
                <span class="px-3 py-1 rounded-tenant-full text-xs font-bold border {{ $booking->badgeClass }} uppercase tracking-wider">
                    Status: {{ $booking->overallStatus }}
                </span>
                <p class="text-gray-400 text-xs">Dibuat: {{ $booking->created_at->format('d M Y, H:i') }} WIB</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50/50 rounded-tenant-lg p-5 border border-gray-100 flex flex-col justify-between">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Informasi Pemesan</h3>
                <div class="space-y-4 text-sm flex-1">
                    <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Nama Tim</span><span class="font-semibold text-gray-900 text-right">{{ $booking->team_name }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">No. HP</span><span class="font-medium text-gray-900 text-right">{{ $booking->customer_phone ?? '-' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Email</span><span class="font-medium text-gray-900 text-right break-all">{{ $booking->customer_email ?? '-' }}</span></div>
                </div>
            </div>

            <div class="bg-gray-50/50 rounded-tenant-lg p-5 border border-gray-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Informasi Lapangan</h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Lapangan</span><span class="font-semibold text-gray-900 text-right">{{ $booking->field->name ?? '-' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 shrink-0">Tgl Induk</span><span class="font-medium text-gray-900 text-right">{{ \Carbon\Carbon::parse($booking->booking_date)->format('d F Y') }}</span></div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200/60">
                    <span class="block text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Catatan Tambahan:</span>
                    <div class="text-sm font-medium text-gray-800 bg-white p-3 rounded-tenant-md border border-gray-100 break-words whitespace-pre-wrap min-h-12">
                        {{ $booking->notes ?: 'Tidak ada catatan khusus dari penyewa.' }}
                    </div>
                </div>
            </div>
        </div>

        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Rincian Sesi Disewa
        </h3>

        <div class="overflow-x-auto mb-8 border border-gray-200 rounded-tenant-lg shadow-tenant-sm">
            <table class="w-full text-left text-sm whitespace-nowrap min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr class="text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-semibold">Tanggal Main</th>
                        <th class="px-6 py-4 font-semibold">Sesi Jam</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Harga Sesi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($booking->details as $detail)
                        <tr class="hover:bg-gray-50/50 transition-colors {{ $detail->detailStatus === 'cancelled' ? 'opacity-60' : '' }}">
                            <td class="px-6 py-4 text-gray-900">{{ \Carbon\Carbon::parse($detail->play_date)->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-gray-900 font-medium">
                                {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-tenant-full text-xs font-semibold border {{ $detail->detailBadge }} capitalize">
                                    {{ $detail->detailStatus }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold {{ $detail->detailStatus === 'cancelled' ? 'line-through text-gray-400' : 'text-gray-900' }} text-right">
                                Rp {{ number_format($detail->price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="w-full text-sm bg-gray-50/50 p-6 rounded-tenant-lg border border-gray-200 shadow-tenant-sm mt-8">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Rincian Pembayaran</h3>

            <div class="flex flex-col gap-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Metode Pembayaran</span>
                    <span class="font-bold text-gray-900 uppercase">{{ $booking->mainPayment->method ?? '-' }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Tipe Pembayaran</span>
                    <span class="font-bold text-gray-900 capitalize">{{ $booking->mainPayment->payment_type ?? 'Menunggu' }}</span>
                </div>

                <div class="border-t border-dashed border-gray-200 my-1"></div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Tagihan Sesi Aktif</span>
                    <span class="font-bold text-gray-900">Rp {{ number_format($booking->tagihanAktif, 0, ',', '.') }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Total Dana Masuk</span>
                    <span class="font-bold text-emerald-600">+ Rp {{ number_format($booking->uangMasuk, 0, ',', '.') }}</span>
                </div>

                @if($booking->uangRefund > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Total Dana Dikembalikan (Refund)</span>
                        <span class="font-bold text-orange-500">- Rp {{ number_format($booking->uangRefund, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
                <span class="text-lg font-bold text-gray-900">Sisa Tagihan</span>

                @if($booking->overallStatus === 'cancelled' && $booking->sisaTagihan == 0)
                    <span class="px-4 py-2 bg-gray-100 text-gray-500 rounded-tenant-md font-black tracking-widest uppercase text-sm border border-gray-200 shadow-tenant-sm">
                        DIBATALKAN
                    </span>
                @elseif($booking->sisaTagihan == 0)
                    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-tenant-md font-black tracking-widest uppercase text-sm border border-green-200 shadow-tenant-sm">
                        LUNAS
                    </span>
                @else
                    <span class="text-2xl font-black text-red-500 tracking-tight">
                        Rp {{ number_format($booking->sisaTagihan, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        @if($booking->overallStatus === 'pending' && isset($booking->mainPayment->payment_url))
            <div class="flex justify-end mt-8">
                <x-tenant-button :href="$booking->mainPayment->payment_url" target="_blank" variant="primary" class="w-full md:w-auto py-3.5 px-8 gap-2 shadow-tenant-md">
                    Lanjutkan Pembayaran
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </x-tenant-button>
            </div>
        @endif

        <div class="mt-10 pt-8 border-t border-gray-200">
            <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Manajemen Jadwal Sesi
            </h3>

            <div class="flex flex-col gap-4">
                @foreach($booking->details as $detail)
                    @if (in_array($detail->detailStatus, ['active', 'waiting', 'success', 'booked', 'reschedule']))
                        <div class="bg-gray-50 border border-gray-200 p-5 rounded-tenant-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <p class="font-bold text-gray-900 text-base">
                                    Sesi {{ \Carbon\Carbon::parse($detail->play_date)->format('d M Y') }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Pukul: {{ \Carbon\Carbon::parse($detail->start_play_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($detail->end_play_time)->format('H:i') }}
                                </p>
                            </div>

                            <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                                @if ($detail->canReschedule && !$detail->alreadyRescheduled)
                                    <x-tenant-button :href="route('tenant.booking.form.reschedule', ['detail_booking_id' => $detail->id])" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 text-xs md:text-sm">
                                        Reschedule
                                    </x-tenant-button>
                                @endif

                                @if ($detail->canCancel)
                                    <x-tenant-button :href="route('tenant.booking.form.cancelled', ['detail_booking_id' => $detail->id])" variant="danger" class="px-5 py-2.5 text-xs md:text-sm">
                                        Batalkan
                                    </x-tenant-button>
                                @endif

                                @if ($detail->alreadyRescheduled)
                                    <div class="px-4 py-2 bg-amber-50 border border-amber-200 text-amber-700 rounded-tenant-md text-xs md:text-sm font-medium text-center">
                                        Sudah di-reschedule.
                                    </div>
                                @elseif (!$detail->canReschedule)
                                    <div class="px-4 py-2 bg-gray-100 border border-gray-200 text-gray-500 rounded-tenant-md text-xs md:text-sm font-medium text-center">
                                        Batas H-3 telah berlalu.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

    </x-tenant-card>
</div>
@endsection
