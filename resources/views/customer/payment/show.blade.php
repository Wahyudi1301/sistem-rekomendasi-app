@extends('customer.layouts.master')

@section('page-title', 'Pembayaran Order ' . ($order->order_code ?? ''))

@push('styles')
@endpush

@section('content')
    <div class="page-heading mb-4">
        <h3>Pembayaran Order</h3>
        <p class="text-subtitle text-muted">Selesaikan pembayaran untuk order {{ $order->order_code ?? '' }}.</p>
    </div>

    <div class="page-content">
        <section class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                @include('customer.partials.alerts')

                @if ($snapToken && $order)
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">Total Pembayaran</h5>
                            <h2 class="text-primary fw-bold mb-4">
                                Rp{{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</h2>
                            <p class="text-muted">Klik tombol di bawah untuk memilih metode pembayaran Anda melalui Midtrans.
                            </p>

                            <button id="pay-button" class="btn btn-lg btn-success mt-3 px-5">
                                <i class="bi bi-shield-check-fill me-2"></i> Bayar Sekarang
                            </button>

                            <p class="mt-4 text-muted small">Anda akan diarahkan ke halaman pembayaran aman Midtrans.</p>
                            <p class="mt-2"><a
                                    href="{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}">Kembali
                                    ke Detail Order</a></p>
                        </div>
                    </div>
                @else
                    <div class="alert alert-danger text-center">
                        <h4 class="alert-heading">Error!</h4>
                        <p>Gagal memuat halaman pembayaran. Token pembayaran tidak valid atau order tidak ditemukan.</p>
                        <a href="{{ route('customer.dashboard') }}" class="btn btn-primary mt-2">Kembali ke Dashboard</a>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @if ($snapToken && $order)
        <script
            src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ config('midtrans.client_key') }}"></script>
        <script type="text/javascript">
            var payButton = document.getElementById('pay-button');
            if (payButton) {
                payButton.addEventListener('click', function() {
                    window.snap.pay('{{ $snapToken }}', {
                        onSuccess: function(result) {
                            console.log('Midtrans Payment Success:', result);
                            Swal.fire('Pembayaran Sukses!', 'Terima kasih, pembayaran Anda berhasil.',
                                'success').then(() => {
                                window.location.href =
                                    '{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}';
                            });
                        },
                        onPending: function(result) {
                            console.log('Midtrans Payment Pending:', result);
                            Swal.fire('Pembayaran Pending', 'Pembayaran Anda menunggu konfirmasi.', 'info')
                                .then(() => {
                                    window.location.href =
                                        '{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}';
                                });
                        },
                        onError: function(result) {
                            console.error('Midtrans Payment Error:', result);
                            Swal.fire('Pembayaran Gagal', 'Terjadi kesalahan saat proses pembayaran.',
                                    'error')
                                .then(() => {
                                    window.location.href =
                                        '{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}';
                                });
                        },
                        onClose: function() {
                            console.log('Customer closed the popup without finishing the payment');
                            Swal.fire({
                                title: 'Pembayaran Dibatalkan',
                                text: "Anda menutup jendela pembayaran. Apakah Anda ingin mencoba lagi?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Ya, Coba Lagi',
                                cancelButtonText: 'Tidak, Kembali'
                            }).then((result) => {
                                if (!result.isConfirmed) {
                                    window.location.href =
                                        '{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}';
                                } else {
                                    // Jika ingin coba lagi, mungkin perlu redirect ke initiate lagi
                                    // atau biarkan user klik tombol "Bayar Sekarang" lagi di halaman ini.
                                    // Untuk simplicity, biarkan user klik tombol lagi.
                                }
                            });
                        }
                    });
                });
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif
@endpush
