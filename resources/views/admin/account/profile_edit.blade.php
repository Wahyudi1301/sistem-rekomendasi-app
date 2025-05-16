@extends('admin.layouts.master')

@section('page-title', 'Edit Profil Saya')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Profil Saya</li>
@endsection

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Edit Profil Saya</h3>
                    <p class="text-subtitle text-muted">Perbarui informasi akun Anda.</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Profil</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Formulir Edit Profil</h4>
                            <p class="text-muted small">Email, Role, dan Status tidak dapat diubah dari halaman ini. Kosongkan password jika tidak ingin mengubahnya.</p>
                        </div>
                        <div class="card-body">
                            @include('admin.partials.alerts')

                            <form action="{{ route('admin.account.profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="email_display" class="form-label">Email</label>
                                    <input type="email" id="email_display" class="form-control bg-light" value="{{ $user->email }}" readonly disabled title="Email tidak dapat diubah">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="password" class="form-label">Password Baru</label>
                                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" aria-describedby="passwordHelp">
                                            <small id="passwordHelp" class="form-text text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 8 karakter jika diisi.</small>
                                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="phone_number" class="form-label">No. Telepon</label>
                                    <input type="tel" name="phone_number" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number', $user->phone_number) }}" placeholder="Contoh: 081234567890">
                                    @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="address" class="form-label">Alamat</label>
                                    <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap">{{ old('address', $user->address) }}</textarea>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-3"> {{-- Gender sekarang mengambil lebar penuh karena role dihilangkan --}}
                                    <label for="gender" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('gender', $user->gender) === null ? 'selected' : '' }}>Pilih Jenis Kelamin...</option>
                                        <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- BLOK UNTUK MENAMPILKAN ROLE DIHAPUS --}}
                                {{-- <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="role_display" class="form-label">Role Anda</label>
                                        <input type="text" id="role_display" class="form-control bg-light" value="{{ $user->role_display }}" readonly disabled title="Role tidak dapat diubah dari halaman ini">
                                    </div>
                                </div> --}}
                                {{-- Akhir dari blok yang dihapus --}}


                                {{-- Status tidak ditampilkan atau diubah dari sini --}}

                                <div class="mt-4 d-flex justify-content-end">
                                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light-secondary me-2">Batal</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Update Profil</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    {{-- Script tambahan jika diperlukan --}}
@endpush
