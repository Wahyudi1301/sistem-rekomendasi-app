@extends('admin.layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <div class="container mt-4">
        <h3>Edit Customer</h3>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary mb-3">Kembali</a>

        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama_customer" class="form-control" value="{{ $customer->nama_customer }}" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $customer->email }}" required>
            </div>

            <div class="mb-3">
                <label>Password (kosongkan jika tidak ingin mengubah)</label>
                <input type="password" name="password" class="form-control">
                <small class="text-muted">Isi hanya jika ingin mengubah password</small>
            </div>

            <div class="mb-3">
                <label>No. Telepon</label>
                <input type="text" name="phone_number" class="form-control" value="{{ $customer->phone_number }}">
            </div>

            <div class="mb-3">
                <label>Alamat</label>
                <input type="text" name="alamat" class="form-control" value="{{ $customer->alamat }}">
            </div>

            <div class="mb-3">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="Laki-laki" {{ $customer->jenis_kelamin == 'Laki-laki' ? 'selected' : '' }}>Laki-laki
                    </option>
                    <option value="Perempuan" {{ $customer->jenis_kelamin == 'Perempuan' ? 'selected' : '' }}>Perempuan
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Aktif" {{ $customer->status == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $customer->status == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection
