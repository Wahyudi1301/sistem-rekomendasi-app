@extends('admin.layouts.master')

@section('page-title', 'Detail Pembayaran: ' . ($payment->gateway_reference_id ??
    optional($payment->order)->order_code))

    @push('styles')
        <style>
            body {
                background-color: #fff !important;
            }

            .invoice-box {
                max-width: 800px;
                margin: auto;
                padding: 30px;
                border: 1px solid #eee;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
                font-size: 16px;
                line-height: 24px;
                font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
                color: #555;
            }

            .invoice-box table {
                width: 100%;
                line-height: inherit;
                text-align: left;
                border-collapse: collapse;
            }

            .invoice-box table td {
                padding: 5px;
                vertical-align: top;
            }

            .invoice-box table tr.top table td.title {
                font-size: 45px;
                line-height: 45px;
                color: #333;
            }

            .invoice-box table tr.information table td {
                padding-bottom: 20px;
            }

            .invoice-box table tr.heading td {
                background: #eee;
                border-bottom: 1px solid #ddd;
                font-weight: bold;
            }

            .invoice-box table tr.details td {
                padding-bottom: 10px;
            }

            /* Mengurangi padding */
            .invoice-box table tr.item td {
                border-bottom: 1px solid #eee;
            }

            .invoice-box table tr.item.last td {
                border-bottom: none;
            }

            .invoice-box table tr.total td:nth-child(2) {
                border-top: 2px solid #eee;
                font-weight: bold;
            }

            .text-end {
                text-align: right !important;
            }

            .fw-bold {
                font-weight: bold !important;
            }

            .text-primary {
                color: #0d6efd !important;
            }

            .no-print {
                display: block;
            }

            @media print {

                body,
                .invoice-box {
                    margin: 0;
                    padding: 0;
                    box-shadow: none;
                    border: none;
                }

                .no-print,
                #sidebar,
                header,
                footer,
                .page-heading,
                .breadcrumb-header {
                    display: none !important;
                }

                #main,
                #main-content {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
            }
        </style>
    @endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Laporan Pembayaran</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail Pembayaran</li>
@endsection

@section('content')
    <div class="page-heading no-print">
        <h3>Detail Transaksi Pembayaran</h3>
        <div class="d-flex justify-content-between align-items-center">
            <p class="text-subtitle text-muted">Ref. Gateway: {{ $payment->gateway_reference_id ?? 'N/A' }}</p>
            <div>
                <button onclick="window.print()" class="btn btn-primary me-1"><i class="bi bi-printer-fill"></i>
                    Cetak</button>
                @if (
                    !in_array($payment->transaction_status, [
                        'settlement',
                        'capture',
                        'paid',
                        'refund',
                        'partial_refund',
                        'cancel',
                        'deny',
                    ]))
                    <a href="{{ route('admin.payments.edit', $payment->hashid) }}" class="btn btn-info"><i
                            class="bi bi-pencil-fill"></i> Edit Status</a>
                @endif
            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="invoice-box">
                            <table>
                                <tr class="top">
                                    <td colspan="2">
                                        <table>
                                            <tr>
                                                <td class="title">
                                                    <h2 class="text-primary">DETAIL PEMBAYARAN</h2>
                                                </td>
                                                <td>
                                                    ID Pembayaran: {{ $payment->hashid }}<br />
                                                    Dibuat: {{ $payment->created_at->format('d M Y, H:i') }}<br />
                                                    Status: <span
                                                        class="fw-bold">{{ ucwords(str_replace('_', ' ', $payment->transaction_status)) }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr class="information">
                                    <td colspan="2">
                                        <table>
                                            <tr>
                                                <td>
                                                    <strong>Info Order:</strong><br />
                                                    Kode Order: <a
                                                        href="{{ route('admin.orders.show', optional($payment->order)->hashid) }}">{{ optional($payment->order)->order_code ?? 'N/A' }}</a><br />
                                                    Customer:
                                                    {{ optional(optional($payment->order)->customer)->name ?? 'N/A' }}<br />
                                                    Email: {{ optional(optional($payment->order)->customer)->email ?? '' }}
                                                </td>
                                                <td>
                                                    <strong>{{ App\Models\Store::first()->name ?? config('app.name') }}</strong><br />
                                                    {{ App\Models\Store::first()->address ?? 'Alamat Toko Anda' }}<br />
                                                    {{ App\Models\Store::first()->email ?? 'Email Toko Anda' }}<br />
                                                    {{ App\Models\Store::first()->phone_number ?? 'Telepon Toko Anda' }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr class="heading">
                                    <td>Deskripsi</td>
                                    <td class="text-end">Jumlah</td>
                                </tr>
                                <tr class="item">
                                    <td>Pembayaran untuk Order {{ optional($payment->order)->order_code }}</td>
                                    <td class="text-end">Rp{{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>

                                <tr class="total">
                                    <td></td>
                                    <td class="text-end fw-bold">Total:
                                        Rp{{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>

                                <tr class="heading">
                                    <td>Informasi Gateway Pembayaran</td>
                                    <td class="text-end"></td>
                                </tr>
                                <tr class="details">
                                    <td>Metode Pembayaran (Gateway)</td>
                                    <td class="text-end">
                                        {{ ucwords(str_replace('_', ' ', $payment->payment_method_gateway ?? '-')) }}</td>
                                </tr>
                                @if ($payment->payment_channel)
                                    <tr class="details">
                                        <td>Channel Pembayaran</td>
                                        <td class="text-end">
                                            {{ Str::upper(str_replace('_', ' ', $payment->payment_channel)) }}</td>
                                    </tr>
                                @endif
                                <tr class="details">
                                    <td>ID Transaksi Gateway</td>
                                    <td class="text-end">{{ $payment->gateway_transaction_id ?? '-' }}</td>
                                </tr>
                                <tr class="details">
                                    <td>Referensi Order Gateway</td>
                                    <td class="text-end">{{ $payment->gateway_reference_id ?? '-' }}</td>
                                </tr>
                                <tr class="details">
                                    <td>Waktu Transaksi (Gateway)</td>
                                    <td class="text-end">
                                        {{ $payment->transaction_time ? $payment->transaction_time->format('d M Y, H:i:s') : '-' }}
                                    </td>
                                </tr>
                                @if ($payment->settlement_time)
                                    <tr class="details">
                                        <td>Waktu Settlement (Gateway)</td>
                                        <td class="text-end">{{ $payment->settlement_time->format('d M Y, H:i:s') }}</td>
                                    </tr>
                                @endif
                                @if ($payment->expiry_time)
                                    <tr class="details">
                                        <td>Waktu Kedaluwarsa</td>
                                        <td class="text-end">{{ $payment->expiry_time->format('d M Y, H:i:s') }}</td>
                                    </tr>
                                @endif
                                @if ($payment->fraud_status)
                                    <tr class="details">
                                        <td>Status Fraud</td>
                                        <td class="text-end">{{ ucwords($payment->fraud_status) }}</td>
                                    </tr>
                                @endif
                                {{-- Jika ada kolom notes di tabel payments --}}
                                {{-- @if ($payment->notes)
                                    <tr class="heading"><td colspan="2">Catatan Admin</td></tr>
                                    <tr class="details"><td colspan="2">{{ $payment->notes }}</td></tr>
                                @endif --}}
                            </table>
                            @if ($payment->gateway_response_payload)
                                <div class="mt-4 no-print">
                                    <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                        href="#gatewayFullPayload" role="button" aria-expanded="false"
                                        aria-controls="gatewayFullPayload">
                                        Lihat Full Payload Gateway (Debug)
                                    </a>
                                    <div class="collapse mt-2" id="gatewayFullPayload">
                                        <pre style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border-radius: 4px;"><code>{{ json_encode($payment->gateway_response_payload, JSON_PRETTY_PRINT) }}</code></pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
