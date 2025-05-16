@extends('admin.layouts.master')

@section('page-title', 'Edit User: ' . $user->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Kelola Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit User: {{ $user->name }}</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Form Edit User: <span class="text-primary">{{ $user->name }}</span></h4>
                        <p class="text-muted">Isi semua field yang bertanda <span class="text-danger">*</span>. Kosongkan password jika tidak ingin mengubahnya.</p>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts') {{-- Pastikan path ini benar --}}

                        <form action="{{ route('admin.users.update', ['user' => $user->hashid]) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus>
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">Password Baru</label>
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 8 karakter jika diisi.</small>
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

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="gender" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                            <option value="" disabled {{ old('gender', $user->gender) === null ? 'selected' : '' }}>Pilih Jenis Kelamin...</option>
                                            <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                            <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                                        </select>
                                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="form-group mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                            <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                            <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                        </select>
                                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                            <option value="" disabled>Pilih Role...</option>
                                            {{-- Variabel $roles di-pass dari UserController@edit --}}
                                            @if(isset($roles) && !empty($roles))
                                                @foreach($roles as $value => $label)
                                                    <option value="{{ $value }}" {{ old('role', $user->role) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            @else
                                                 {{-- Fallback jika $roles tidak ada atau kosong --}}
                                                <option value="{{ \App\Models\User::ROLE_STAFF }}" {{ old('role', $user->role) == \App\Models\User::ROLE_STAFF ? 'selected' : '' }}>Staff</option>
                                                <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role', $user->role) == \App\Models\User::ROLE_ADMIN ? 'selected' : '' }}>Admin</option>
                                            @endif
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Update User</button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary ms-2">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    {{-- Script tambahan jika diperlukan --}}
@endpush
