@extends('admin.layouts.app')

@section('title', 'Tambah Customer')

@section('content')
    <div class="container mt-4">
        <h3>Tambah Customer</h3>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary mb-3">Kembali</a>

        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama_customer" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>No. Telepon</label>
                <input type="text" name="phone_number" class="form-control">
            </div>

            <div class="mb-3">
                <label>Alamat</label>
                <input type="text" name="alamat" class="form-control">
            </div>

            <div class="mb-3">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control" required>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Status</label>
                <input type="text" class="form-control" value="Aktif" disabled>
                <input type="hidden" name="status" value="Aktif">
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
@endsection
