<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Order - {{ $order->order_code }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img.logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: #0056b3;
        }

        .header p {
            margin: 3px 0;
            font-size: 11px;
        }

        .order-details-section,
        .customer-details-section,
        .item-details-section,
        .payment-details-section {
            margin-bottom: 20px;
        }

        .order-details-section h2,
        .customer-details-section h2,
        .item-details-section h2,
        .payment-details-section h2 {
            font-size: 16px;
            border-bottom: 1px solid #0056b3;
            padding-bottom: 4px;
            margin-bottom: 10px;
            color: #0056b3;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 170px 1fr;
            gap: 5px 10px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .detail-grid strong {
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        td.text-end {
            text-align: right;
        }

        td.text-center {
            text-align: center;
        }

        .summary-table {
            margin-top: 15px;
            width: 50%;
            float: right;
        }

        .summary-table td {
            border: none;
        }

        .summary-table strong {
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #333 !important;
            font-weight: bold;
            font-size: 1.1em;
        }

        .notes-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            font-size: 11px;
            white-space: pre-wrap;
            clear: both;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #777;
        }

        .btn-print-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-print:hover {
            background-color: #0056b3;
        }

        .badge {
            display: inline-block;
            padding: .3em .6em;
            font-size: .7em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 8mm;
                border: none;
                box-shadow: none;
            }

            .btn-print-container {
                display: none;
            }

            .footer {
                position: fixed;
                bottom: 5mm;
                left: 0;
                right: 0;
                text-align: center;
            }

            /* Ukuran font bisa disesuaikan untuk print */
        }
    </style>
</head>

<body>
    <div class="btn-print-container"><button onclick="window.print()" class="btn-print">Cetak Invoice</button></div>
    <div class="container">
        <div class="header">
            @if ($storeDetails && $storeDetails->logo_path && Storage::disk('public')->exists($storeDetails->logo_path))
                <img src="{{ Storage::url($storeDetails->logo_path) }}" alt="{{ $storeDetails->name ?? 'Logo' }}"
                    class="logo">
            @endif
            <h1>{{ $storeDetails->name ?? 'INVOICE' }}</h1>
            <p>{{ $storeDetails->address ?? 'Alamat Toko Anda' }}</p>
            <p>Telp: {{ $storeDetails->phone_number ?? '-' }} | Email: {{ $storeDetails->email ?? '-' }}</p>
        </div>

        <div class="order-details-section">
            <h2>Detail Order</h2>
            <div class="detail-grid">
                <strong>Nomor Order:</strong> <span
                    style="font-weight: bold; color: #0056b3;">{{ $order->order_code }}</span>
                <strong>Tanggal Order:</strong> <span>{{ $order->created_at->format('d M Y, H:i') }} WIB</span>
                <strong>Status Pembayaran:</strong>
                <span><span
                        class="badge {{ $order->payment_status == 'paid' ? 'bg-success' : ($order->payment_status == 'pending' ? 'bg-warning' : 'bg-secondary') }}">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span></span>
                <strong>Status Order:</strong>
                <span><span
                        class="badge bg-info">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span></span>
            </div>
        </div>

        <div class="customer-details-section">
            <h2>Informasi Customer</h2>
            <div class="detail-grid">
                <strong>Nama Customer:</strong> <span>{{ optional($order->customer)->name ?? 'N/A' }}</span>
                <strong>Email:</strong> <span>{{ optional($order->customer)->email ?? 'N/A' }}</span>
                <strong>No. Telepon:</strong> <span>{{ optional($order->customer)->phone_number ?? 'N/A' }}</span>
                @if ($order->delivery_method == 'delivery')
                    <strong>Alamat Pengiriman:</strong>
                    <span>{{ $order->delivery_address ?: optional($order->customer)->address ?: 'N/A' }}</span>
                @endif
            </div>
        </div>

        <div class="payment-details-section">
            <h2>Detail Pembayaran</h2>
            @php $latestPayment = $order->payments()->latest()->first(); @endphp
            <div class="detail-grid">
                <strong>Metode Pembayaran:</strong>
                <span>{{ ucwords(str_replace('_', ' ', $latestPayment->payment_method_gateway ?? 'Belum ada data')) }}</span>
                @if ($latestPayment && $latestPayment->payment_channel)
                    <strong>Channel:</strong>
                    <span>{{ Str::upper(str_replace('_', ' ', $latestPayment->payment_channel)) }}</span>
                @endif
                @if ($latestPayment && $latestPayment->gateway_transaction_id)
                    <strong>ID Transaksi Gateway:</strong> <span>{{ $latestPayment->gateway_transaction_id }}</span>
                @endif
            </div>
        </div>


        <div class="item-details-section">
            <h2>Item yang Dipesan</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 45%;">Nama Item</th>
                        <th class="text-center" style="width: 10%;">Jumlah</th>
                        <th class="text-end" style="width: 20%;">Harga Satuan</th>
                        <th class="text-end" style="width: 20%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $index => $item)
                        @php
                            $pivotData = $item->pivot;
                            $quantityOrdered = $pivotData->quantity;
                            $priceWhenOrdered = $pivotData->price_per_item;
                            $itemSubtotal = $priceWhenOrdered * $quantityOrdered;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $item->name }}
                                <small style="display: block; color: #666;">{{ optional($item->brand)->name }} /
                                    {{ optional($item->category)->name }}</small>
                            </td>
                            <td class="text-center">{{ $quantityOrdered }}</td>
                            <td class="text-end">Rp{{ number_format($priceWhenOrdered, 0, ',', '.') }}</td>
                            <td class="text-end">Rp{{ number_format($itemSubtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada item dalam order ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <table class="summary-table">
                <tr>
                    <td>Subtotal Barang:</td>
                    <td class="text-end">Rp{{ number_format($order->total_item_price, 0, ',', '.') }}</td>
                </tr>
                @if ($order->shipping_cost > 0)
                    <tr>
                        <td>Biaya Pengiriman:</td>
                        <td class="text-end">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if ($order->installation_cost > 0)
                    <tr>
                        <td>Biaya Pemasangan:</td>
                        <td class="text-end">Rp{{ number_format($order->installation_cost, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td><strong>Total Tagihan:</strong></td>
                    <td class="text-end"><strong>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
            <div style="clear:both;"></div>
        </div>

        @if ($order->customer_notes)
            <div class="notes-section">
                <strong>Catatan dari Customer:</strong><br>
                {{ $order->customer_notes }}
            </div>
        @endif

        @if ($order->admin_notes)
            <div class="notes-section" style="margin-top: 10px; background-color: #eef7ff;">
                <strong>Catatan dari Admin:</strong><br>
                {!! nl2br(e($order->admin_notes)) !!}
            </div>
        @endif

        <div class="footer">
            <p>Terima kasih telah berbelanja di {{ $storeDetails->name ?? 'Tempat Kami' }}.</p>
            <p>Dokumen ini dicetak pada: {{ now()->format('d M Y, H:i') }} WIB
                @if ($order->user)
                    | Diproses oleh: {{ $order->user->name }}
                @endif
            </p>
        </div>
    </div>
</body>

</html>
