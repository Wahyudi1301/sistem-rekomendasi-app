@extends('customer.layouts.master')

@section('page-title', 'Keranjang Belanja Saya')

@push('styles')
    <style>
        #cart-table .quantity-input {
            max-width: 80px;
        }

        .payment-option-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .payment-option-card.selected {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 .25rem rgba(var(--bs-primary-rgb), .25);
        }

        .payment-option-card .form-check-input {
            position: absolute;
            top: 1rem;
            right: 1rem;
            transform: scale(1.5);
        }

        .address-display-field {
            background-color: #e9ecef;
            padding: .375rem .75rem;
            border-radius: .25rem;
            border: 1px solid #ced4da;
            min-height: calc(1.5em + .75rem + 2px);
            word-break: break-word;
        }
    </style>
@endpush

@section('content')
    <div class="page-heading mb-4">
        <h3>Keranjang Belanja Anda</h3>
        <p class="text-subtitle text-muted">Periksa item Anda, pilih metode pengiriman dan pembayaran.</p>
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12">
                @include('customer.partials.alerts')

                @if (!$cartItems->isEmpty())
                    <form action="{{ route('customer.order.place') }}" method="POST" id="checkout-form">
                        @csrf

                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Item di Keranjang</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle" id="cart-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;"><input class="form-check-input" type="checkbox"
                                                        id="select-all-items" title="Pilih Semua"></th>
                                                <th style="width: 10%;">Gambar</th>
                                                <th>Nama Item</th>
                                                <th style="width: 15%;">Harga Satuan</th>
                                                <th style="width: 18%;">Jumlah</th>
                                                <th style="width: 15%;">Subtotal</th>
                                                <th style="width: 10%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-table-body">
                                            @foreach ($cartItems as $cartItem)
                                                @if ($cartItem->item)
                                                    @php
                                                        $item = $cartItem->item;
                                                        $itemHashid = $item->hashid;
                                                        $cartItemHashid = $cartItem->hashid;
                                                        $subtotalItem = $item->price * $cartItem->quantity;
                                                    @endphp
                                                    <tr data-item-price="{{ $item->price }}"
                                                        data-cart-item-hashid="{{ $cartItemHashid }}">
                                                        <td>
                                                            <input type="checkbox" class="form-check-input item-checkbox"
                                                                name="selected_items[]" value="{{ $cartItemHashid }}"
                                                                data-subtotal="{{ $subtotalItem }}">
                                                        </td>
                                                        <td>
                                                            <a
                                                                href="{{ route('customer.catalog.show', ['item_hash' => $itemHashid]) }}">
                                                                @if ($item->img && File::exists(public_path('assets/compiled/items/' . $item->img)))
                                                                    <img src="{{ asset('assets/compiled/items/' . $item->img) }}"
                                                                        alt="{{ $item->name }}" class="img-fluid rounded"
                                                                        style="width: 70px; height: 70px; object-fit: cover;">
                                                                @else
                                                                    <img src="{{ asset('assets/compiled/svg/no-image.svg') }}"
                                                                        alt="No image"
                                                                        class="img-fluid rounded bg-light p-2"
                                                                        style="width: 70px; height: 70px; object-fit: contain;">
                                                                @endif
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('customer.catalog.show', ['item_hash' => $itemHashid]) }}"
                                                                class="text-dark fw-bold text-decoration-none">
                                                                {{ $item->name }}
                                                            </a>
                                                            <br>
                                                            <small class="text-muted">Stok: {{ $item->stock }}</small>
                                                        </td>
                                                        <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                                                        <td>
                                                            <div
                                                                class="d-inline-flex align-items-center cart-update-form-container">
                                                                <input type="number"
                                                                    name="quantities[{{ $cartItemHashid }}]"
                                                                    value="{{ $cartItem->quantity }}"
                                                                    class="form-control form-control-sm text-center me-2 quantity-input"
                                                                    min="1" max="{{ $item->stock }}"
                                                                    data-cartitemhash="{{ $cartItemHashid }}">
                                                            </div>
                                                        </td>
                                                        <td class="item-subtotal">
                                                            Rp{{ number_format($subtotalItem, 0, ',', '.') }}</td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger btn-remove-cart"
                                                                data-cartitemhash="{{ $cartItemHashid }}"
                                                                title="Hapus Item">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td></td>
                                                        <td colspan="5" class="text-center text-danger fst-italic">Item
                                                            ini sudah tidak tersedia.</td>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger btn-remove-cart"
                                                                data-cartitemhash="{{ $cartItem->hashid }}">Hapus</button>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div class="row mt-4" id="checkout-details-section">
                            <div class="col-lg-7 mb-4 mb-lg-0">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Opsi Pengiriman</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Metode Pengiriman <span
                                                    class="text-danger">*</span></label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="delivery_method"
                                                        id="pickup" value="pickup"
                                                        {{ old('delivery_method', 'pickup') == 'pickup' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pickup">Ambil di Tempat</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="delivery_method"
                                                        id="delivery" value="delivery"
                                                        {{ old('delivery_method') == 'delivery' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="delivery">Di Antar ke
                                                        Alamat</label>
                                                </div>
                                            </div>
                                            @error('delivery_method')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div id="delivery-options-section" class="mb-3"
                                            style="{{ old('delivery_method') == 'delivery' ? '' : 'display: none;' }}">
                                            <label class="form-label fw-bold">Opsi Pengantaran <span
                                                    class="text-danger">*</span></label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="delivery_option"
                                                        id="delivery_only" value="delivery_only"
                                                        {{ old('delivery_option', 'delivery_only') == 'delivery_only' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="delivery_only">Hanya Antar
                                                        (Biaya: Rp<span
                                                            id="label-cost-delivery-only">{{ number_format($costShippingOnly ?? 0, 0, ',', '.') }}</span>)</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="delivery_option"
                                                        id="delivery_install" value="delivery_install"
                                                        {{ old('delivery_option') == 'delivery_install' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="delivery_install">Antar + Pasang
                                                        (Biaya Total: Rp<span
                                                            id="label-cost-delivery-install">{{ number_format($costShippingInstallTotal ?? 0, 0, ',', '.') }}</span>)</label>
                                                </div>
                                            </div>
                                            @error('delivery_option')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror

                                            <div class="row mt-3">
                                                <div class="col-md-6 mb-3">
                                                    <label for="preferred_delivery_date" class="form-label">Tgl.
                                                        Pengiriman <span class="text-danger">*</span></label>
                                                    <input type="date"
                                                        class="form-control @error('preferred_delivery_date') is-invalid @enderror"
                                                        id="preferred_delivery_date" name="preferred_delivery_date"
                                                        value="{{ old('preferred_delivery_date', now()->addDay()->format('Y-m-d')) }}"
                                                        min="{{ now()->addDay()->format('Y-m-d') }}">
                                                    @error('preferred_delivery_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Alamat Pengiriman</label>
                                                <div class="address-display-field">
                                                    {{ Auth::guard('customer')->user()->address ?: 'Belum diatur di profil Anda.' }}
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">No. HP Penerima</label>
                                                <div class="address-display-field">
                                                    {{ Auth::guard('customer')->user()->phone_number ?: 'Belum diatur di profil Anda.' }}
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Untuk mengubah alamat atau nomor HP, silakan update melalui <a
                                                    href="{{ route('customer.profile.edit') }}" target="_blank">Profil
                                                    Saya</a>.
                                            </small>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label for="customer_notes" class="form-label">Catatan Tambahan
                                                (Opsional)</label>
                                            <textarea name="customer_notes" id="customer_notes" class="form-control" rows="2"
                                                placeholder="Contoh: Barang diterima oleh Satpam jika saya tidak di tempat.">{{ old('customer_notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Ringkasan & Pembayaran</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between"><span>Subtotal Barang:</span>
                                                <strong id="total-item-price-display">Rp0</strong>
                                            </div>
                                            <div class="d-flex justify-content-between"><span>Biaya Pengiriman:</span>
                                                <strong id="shipping-cost-display">Rp0</strong>
                                            </div>
                                            <div class="d-flex justify-content-between" id="installation-cost-row"
                                                style="display:none;"><span>Biaya Pemasangan:</span> <strong
                                                    id="installation-cost-display">Rp0</strong></div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">Total Pembayaran:</h5>
                                            <h5 class="text-primary fw-bold mb-0" id="grand-total-price-display">Rp0</h5>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Metode Pembayaran <span
                                                    class="text-danger">*</span></label>
                                            <input type="hidden" name="payment_method" id="selected_payment_method"
                                                value="{{ old('payment_method', 'qris') }}">
                                            <div class="row g-2">
                                                <div class="col-6 payment-option-container"
                                                    id="payment-qris-option-container">
                                                    <div class="card payment-option-card {{ old('payment_method', 'qris') == 'qris' ? 'selected' : '' }}"
                                                        data-payment-value="qris">
                                                        <div class="card-body text-center p-2">
                                                            <input class="form-check-input" type="radio"
                                                                name="payment_method_radio" id="pay_qris" value="qris"
                                                                {{ old('payment_method', 'qris') == 'qris' ? 'checked' : '' }}>
                                                            <img src="{{ asset('assets/static/images/payment/qris_logo.svg') }}"
                                                                alt="QRIS" height="30" class="my-1">
                                                            <p class="mb-0 small fw-medium">QRIS</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6 payment-option-container"
                                                    id="payment-cash-option-container"
                                                    style="{{ old('delivery_method', 'pickup') == 'pickup' ? '' : 'display: none;' }}">
                                                    <div class="card payment-option-card {{ old('payment_method') == 'cash' ? 'selected' : '' }}"
                                                        data-payment-value="cash">
                                                        <div class="card-body text-center p-2">
                                                            <input class="form-check-input" type="radio"
                                                                name="payment_method_radio" id="pay_cash" value="cash"
                                                                {{ old('payment_method') == 'cash' ? 'checked' : '' }}>
                                                            <i class="bi bi-cash-coin fs-2 text-success my-1"></i>
                                                            <p class="mb-0 small fw-medium">Cash (di Toko)</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('payment_method')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small id="payment-info" class="form-text text-muted">Pembayaran QRIS akan
                                            diproses melalui Midtrans.</small>


                                        <div class="d-grid mt-3">
                                            <button type="submit" class="btn btn-lg btn-primary" id="btn-checkout"
                                                disabled>
                                                <i class="bi bi-shield-check-fill me-2"></i>
                                                <span id="checkout-button-text">Lanjutkan ke Pembayaran</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="alert alert-info text-center" id="empty-cart-message">
                        <h4 class="alert-heading"><i class="bi bi-cart-x-fill"></i> Keranjang Anda Kosong!</h4>
                        <p>Silakan tambahkan beberapa alat ke keranjang Anda terlebih dahulu.</p>
                        <a href="{{ route('customer.catalog.index') }}" class="btn btn-primary mt-2">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Kembali ke Katalog
                        </a>
                    </div>
                    <div class="row mt-4" id="checkout-details-section-empty" style="display:none;"></div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cartTableBody = document.getElementById('cart-table-body');
            const selectAllCheckbox = document.getElementById('select-all-items');
            let itemCheckboxes = cartTableBody ? Array.from(cartTableBody.querySelectorAll('.item-checkbox')) : [];
            let quantityInputs = cartTableBody ? Array.from(cartTableBody.querySelectorAll('.quantity-input')) : [];

            const btnCheckout = document.getElementById('btn-checkout');
            const checkoutButtonText = document.getElementById('checkout-button-text');
            const checkoutDetailsSection = document.getElementById('checkout-details-section');
            const emptyCartMessageSection = document.getElementById('empty-cart-message');
            const SELECTED_ITEMS_STORAGE_KEY = 'selectedCartItemsV2';

            const deliveryMethodRadios = document.querySelectorAll('input[name="delivery_method"]');
            const deliveryOptionsSection = document.getElementById('delivery-options-section');
            const deliveryOptionRadios = document.querySelectorAll('input[name="delivery_option"]');

            const paymentOptionCards = document.querySelectorAll('.payment-option-card');
            const selectedPaymentMethodInput = document.getElementById('selected_payment_method');
            const paymentCashOptionContainer = document.getElementById('payment-cash-option-container');
            const paymentQrisOptionContainer = document.getElementById('payment-qris-option-container');
            const paymentInfoText = document.getElementById('payment-info');

            const totalItemPriceDisplay = document.getElementById('total-item-price-display');
            const shippingCostDisplay = document.getElementById('shipping-cost-display');
            const installationCostRow = document.getElementById('installation-cost-row');
            const installationCostDisplay = document.getElementById('installation-cost-display');
            const grandTotalPriceDisplay = document.getElementById('grand-total-price-display');

            const COST_DELIVERY_ONLY = parseFloat("{{ $costShippingOnly ?? 0 }}");
            const COST_DELIVERY_INSTALL_TOTAL = parseFloat("{{ $costShippingInstallTotal ?? 0 }}");
            let COST_INSTALLATION_FOR_DELIVERY = COST_DELIVERY_INSTALL_TOTAL - COST_DELIVERY_ONLY;
            if (COST_INSTALLATION_FOR_DELIVERY < 0) COST_INSTALLATION_FOR_DELIVERY = 0;

            function formatCurrency(amount) {
                return 'Rp' + new Intl.NumberFormat('id-ID').format(amount);
            }

            function updatePaymentOptionsVisibility() {
                const selectedDeliveryMethodRadio = document.querySelector('input[name="delivery_method"]:checked');
                if (!selectedDeliveryMethodRadio) return;

                const selectedDeliveryMethod = selectedDeliveryMethodRadio.value;
                const currentSelectedPayment = selectedPaymentMethodInput.value;

                if (selectedDeliveryMethod === 'pickup') {
                    paymentCashOptionContainer.style.display = 'block';
                    paymentQrisOptionContainer.classList.remove('col-12');
                    paymentQrisOptionContainer.classList.add('col-6');
                    document.getElementById('pay_cash').disabled = false;


                    if (currentSelectedPayment === 'cash') {
                        paymentInfoText.textContent = 'Pembayaran tunai dilakukan saat pengambilan barang di toko.';
                        checkoutButtonText.textContent = 'Buat Pesanan (Bayar di Toko)';
                    } else {
                        paymentInfoText.textContent = 'Pembayaran QRIS akan diproses melalui Midtrans.';
                        checkoutButtonText.textContent = 'Lanjutkan ke Pembayaran QRIS';
                    }

                } else {
                    paymentCashOptionContainer.style.display = 'none';
                    paymentQrisOptionContainer.classList.add('col-12');
                    paymentQrisOptionContainer.classList.remove('col-6');
                    document.getElementById('pay_cash').disabled = true;


                    if (currentSelectedPayment === 'cash') {
                        document.getElementById('pay_qris').checked = true;
                        selectedPaymentMethodInput.value = 'qris';
                        paymentOptionCards.forEach(c => c.classList.remove('selected'));
                        document.querySelector('.payment-option-card[data-payment-value="qris"]').classList.add(
                            'selected');
                    }
                    paymentInfoText.textContent =
                        'Pembayaran QRIS akan diproses melalui Midtrans sebelum barang dikirim.';
                    checkoutButtonText.textContent = 'Lanjutkan ke Pembayaran QRIS';
                }
            }


            function calculateTotalsAndCosts() {
                if (!cartTableBody || !btnCheckout) return;

                let currentItemTotal = 0;
                let hasCheckedItem = false;
                let allChecked = true;

                itemCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (!row) return;
                    const quantityInput = row.querySelector('.quantity-input');
                    const price = parseFloat(row.dataset.itemPrice);
                    const quantity = parseInt(quantityInput ? quantityInput.value : 0);

                    if (checkbox.checked) {
                        hasCheckedItem = true;
                        if (!isNaN(price) && !isNaN(quantity) && quantity > 0) {
                            currentItemTotal += price * quantity;
                        }
                    } else {
                        allChecked = false;
                    }
                    const subtotalEl = row.querySelector('.item-subtotal');
                    if (subtotalEl && !isNaN(price) && !isNaN(quantity)) subtotalEl.textContent =
                        formatCurrency(price * quantity);
                });

                totalItemPriceDisplay.textContent = formatCurrency(currentItemTotal);

                let calculatedShippingCost = 0;
                let calculatedInstallationCost = 0;

                const selectedDeliveryMethodRadio = document.querySelector('input[name="delivery_method"]:checked');
                if (!selectedDeliveryMethodRadio) {
                    // Do nothing or set defaults if needed
                } else {
                    const selectedDeliveryMethod = selectedDeliveryMethodRadio.value;
                    if (selectedDeliveryMethod === 'delivery') {
                        const selectedDeliveryOptionRadio = document.querySelector(
                            'input[name="delivery_option"]:checked');
                        if (selectedDeliveryOptionRadio) {
                            const selectedDeliveryOption = selectedDeliveryOptionRadio.value;
                            if (selectedDeliveryOption === 'delivery_install') {
                                calculatedShippingCost = COST_DELIVERY_ONLY;
                                calculatedInstallationCost = COST_INSTALLATION_FOR_DELIVERY;
                            } else if (selectedDeliveryOption === 'delivery_only') {
                                calculatedShippingCost = COST_DELIVERY_ONLY;
                            }
                        }
                    }
                }


                shippingCostDisplay.textContent = formatCurrency(calculatedShippingCost);
                if (calculatedInstallationCost > 0) {
                    installationCostDisplay.textContent = formatCurrency(calculatedInstallationCost);
                    installationCostRow.style.display = 'flex';
                } else {
                    installationCostDisplay.textContent = formatCurrency(0);
                    installationCostRow.style.display = 'none';
                }

                const grandTotal = currentItemTotal + calculatedShippingCost + calculatedInstallationCost;
                grandTotalPriceDisplay.textContent = formatCurrency(grandTotal);

                btnCheckout.disabled = !hasCheckedItem || grandTotal < 0;

                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked && itemCheckboxes.length > 0;
                    selectAllCheckbox.indeterminate = !allChecked && hasCheckedItem && itemCheckboxes.length > 0;
                }
                updatePaymentOptionsVisibility();
            }

            deliveryMethodRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'delivery') {
                        deliveryOptionsSection.style.display = 'block';
                        const currentDeliveryOption = document.querySelector(
                            'input[name="delivery_option"]:checked');
                        if (!currentDeliveryOption) {
                            document.getElementById('delivery_only').checked = true;
                            document.getElementById('delivery_only').dispatchEvent(new Event(
                                'change', {
                                    bubbles: true
                                }));
                        } else {
                            currentDeliveryOption.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    } else {
                        deliveryOptionsSection.style.display = 'none';
                        deliveryOptionRadios.forEach(opt => opt.checked = false);
                    }
                    calculateTotalsAndCosts();
                });
            });

            deliveryOptionRadios.forEach(radio => {
                radio.addEventListener('change', calculateTotalsAndCosts);
            });

            paymentOptionCards.forEach(card => {
                card.addEventListener('click', function() {
                    const radioInput = this.querySelector('input[type="radio"]');
                    if (radioInput && !radioInput.disabled) {
                        paymentOptionCards.forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        radioInput.checked = true;
                        selectedPaymentMethodInput.value = radioInput.value;
                        calculateTotalsAndCosts();
                    }
                });
            });


            function updateCartItemQuantity(cartItemHash, newQuantity) {
                const updateUrl = "{{ route('customer.cart.update') }}";
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('cart_item_hashid', cartItemHash);
                formData.append('quantity', newQuantity);

                fetch(updateUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.ok ? response.json() : response.json().then(err => {
                        throw err;
                    }))
                    .then(data => {
                        /* console.log('Qty updated:', data.message); */
                    })
                    .catch(error => {
                        console.error("Update Qty Error:", error);
                        Swal.fire('Error!', error.error || 'Gagal update jumlah item.', 'error');
                    });
            }

            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    let savedSelection = JSON.parse(localStorage.getItem(
                        SELECTED_ITEMS_STORAGE_KEY) || '[]');
                    if (this.checked) {
                        if (!savedSelection.includes(this.value)) savedSelection.push(this.value);
                    } else {
                        savedSelection = savedSelection.filter(hash => hash !== this.value);
                    }
                    localStorage.setItem(SELECTED_ITEMS_STORAGE_KEY, JSON.stringify(
                        savedSelection));
                    calculateTotalsAndCosts();
                });
            });

            let debounceTimer;
            quantityInputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const currentInput = this;
                    const row = currentInput.closest('tr');
                    const cartItemHash = currentInput.dataset.cartitemhash;

                    debounceTimer = setTimeout(() => {
                        const maxStock = parseInt(currentInput.getAttribute('max'));
                        let currentValue = parseInt(currentInput.value);

                        if (isNaN(currentValue) || currentValue < 1) {
                            currentValue = 1;
                            currentInput.value = 1;
                        } else if (currentValue > maxStock) {
                            currentValue = maxStock;
                            currentInput.value = maxStock;
                            Swal.fire('Stok Tidak Cukup',
                                `Maksimal stok tersedia: ${maxStock}.`, 'warning');
                        }

                        const price = parseFloat(row.dataset.itemPrice);
                        const subtotalEl = row.querySelector('.item-subtotal');
                        if (subtotalEl && !isNaN(price)) {
                            subtotalEl.textContent = formatCurrency(price * currentValue);
                        }
                        calculateTotalsAndCosts();
                        updateCartItemQuantity(cartItemHash, currentValue);
                    }, 750);
                });
            });

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    let currentSelection = JSON.parse(localStorage.getItem(SELECTED_ITEMS_STORAGE_KEY) ||
                        '[]');
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                        if (isChecked) {
                            if (!currentSelection.includes(checkbox.value)) currentSelection.push(
                                checkbox.value);
                        } else {
                            currentSelection = currentSelection.filter(hash => hash !== checkbox
                                .value);
                        }
                    });
                    localStorage.setItem(SELECTED_ITEMS_STORAGE_KEY, JSON.stringify(currentSelection));
                    calculateTotalsAndCosts();
                });
            }

            document.querySelectorAll('.btn-remove-cart').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const cartItemRow = this.closest('tr');
                    const cartItemHashToRemove = this.dataset.cartitemhash;
                    const url = "{{ route('customer.cart.remove') }}";
                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('cart_item_hashid', cartItemHashToRemove);


                    Swal.fire({
                        title: 'Yakin Hapus Item?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(url, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content'),
                                        'Accept': 'application/json',
                                    }
                                })
                                .then(response => response.ok ? response.json() : response
                                    .json().then(err => {
                                        throw err;
                                    }))
                                .then(data => {
                                    Swal.fire({
                                        title: 'Terhapus!',
                                        text: data.message,
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    if (cartItemRow) cartItemRow.remove();

                                    itemCheckboxes = cartTableBody ? Array.from(
                                        cartTableBody.querySelectorAll(
                                            '.item-checkbox')) : [];
                                    quantityInputs = cartTableBody ? Array.from(
                                        cartTableBody.querySelectorAll(
                                            '.quantity-input')) : [];

                                    let savedSelection = JSON.parse(localStorage
                                        .getItem(SELECTED_ITEMS_STORAGE_KEY) || '[]'
                                    );
                                    savedSelection = savedSelection.filter(hash =>
                                        hash !== cartItemHashToRemove);
                                    localStorage.setItem(SELECTED_ITEMS_STORAGE_KEY,
                                        JSON.stringify(savedSelection));

                                    calculateTotalsAndCosts();
                                    updateCartBadge();
                                    checkIfCartEmpty();
                                })
                                .catch(error => {
                                    let errMsg = error.error || error.message ||
                                        'Gagal menghapus item.';
                                    Swal.fire('Error!', errMsg, 'error');
                                });
                        }
                    });
                });
            });

            function updateCartBadge() {
                const cartBadge = document.querySelector('.cart-badge-count');
                if (cartBadge && cartTableBody) {
                    const itemCount = cartTableBody.querySelectorAll('tr').length;
                    cartBadge.textContent = itemCount;
                    cartBadge.style.display = itemCount > 0 ? 'inline-block' : 'none';
                }
            }

            function checkIfCartEmpty() {
                if (!cartTableBody || !checkoutDetailsSection || !emptyCartMessageSection) return;
                const hasItems = cartTableBody.querySelectorAll('tr').length > 0;
                const tableContainer = cartTableBody.closest('.card');

                if (tableContainer) tableContainer.style.display = hasItems ? '' : 'none';
                checkoutDetailsSection.style.display = hasItems ? '' : 'none';
                emptyCartMessageSection.style.display = hasItems ? 'none' : 'block';
                if (selectAllCheckbox && selectAllCheckbox.closest('th')) {
                    selectAllCheckbox.closest('th').style.display = hasItems ? '' : 'none';
                }
            }

            const savedItems = JSON.parse(localStorage.getItem(SELECTED_ITEMS_STORAGE_KEY) || '[]');
            itemCheckboxes.forEach(checkbox => {
                if (savedItems.includes(checkbox.value)) checkbox.checked = true;
            });

            const initialDeliveryMethodRadio = document.querySelector('input[name="delivery_method"]:checked') ||
                document.getElementById('pickup');
            if (initialDeliveryMethodRadio) {
                if (!document.querySelector('input[name="delivery_method"]:checked')) {
                    initialDeliveryMethodRadio.checked = true;
                }
                initialDeliveryMethodRadio.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }


            const initialPaymentMethodRadio = document.querySelector('input[name="payment_method_radio"]:checked');
            if (initialPaymentMethodRadio) {
                selectedPaymentMethodInput.value = initialPaymentMethodRadio.value;
                paymentOptionCards.forEach(c => c.classList.remove('selected'));
                initialPaymentMethodRadio.closest('.payment-option-card').classList.add('selected');
            } else if (document.getElementById('pay_qris')) {
                document.getElementById('pay_qris').checked = true;
                selectedPaymentMethodInput.value = 'qris';
                document.querySelector('.payment-option-card[data-payment-value="qris"]').classList.add('selected');
            }


            calculateTotalsAndCosts();
            updateCartBadge();
            checkIfCartEmpty();

            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(event) {
                    const selectedDeliveryMethodVal = document.querySelector(
                        'input[name="delivery_method"]:checked').value;
                    const selectedPaymentMethodVal = selectedPaymentMethodInput.value;

                    if (selectedDeliveryMethodVal === 'delivery' && selectedPaymentMethodVal === 'cash') {
                        event.preventDefault();
                        Swal.fire('Error!',
                            'Pembayaran tunai tidak tersedia untuk metode pengiriman diantar. Silakan pilih metode pembayaran QRIS.',
                            'error');
                        return false;
                    }

                    if (selectedDeliveryMethodVal === 'delivery') {
                        const customerAddressIsSet =
                            "{{ Auth::guard('customer')->user()->address ? 'true' : '' }}";
                        const customerPhoneIsSet =
                            "{{ Auth::guard('customer')->user()->phone_number ? 'true' : '' }}"; // Menggunakan phone_number
                        if (!customerAddressIsSet || !customerPhoneIsSet) {
                            event.preventDefault();
                            Swal.fire('Data Belum Lengkap!',
                                'Alamat atau nomor HP Anda di profil belum lengkap. Harap update profil Anda untuk melanjutkan dengan pengiriman diantar.',
                                'warning');
                            return false;
                        }
                    }


                    localStorage.removeItem(SELECTED_ITEMS_STORAGE_KEY);
                    btnCheckout.disabled = true;
                    btnCheckout.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
                });
            }
        });
    </script>
@endpush
