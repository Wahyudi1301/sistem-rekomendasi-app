<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Pastikan namespace model User benar
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule; // Tidak digunakan di sini karena email & role tidak diubah
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    /**
     * Menampilkan form edit profil untuk user yang sedang login (Admin atau Staff).
     * Method ini akan dipanggil oleh route 'admin.account.profile.edit'.
     */
    public function edit()
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        if (!$user) {
            // Seharusnya tidak terjadi jika route dilindungi middleware auth
            // Middleware 'auth' (default guard 'web') akan melindungi ini.
            abort(401, 'Anda harus login terlebih dahulu.');
        }

        // Tidak perlu cek role di sini, karena ini untuk profil PRIBADI user yang login.
        // Admin atau Staff bisa mengakses ini.
        return view('admin.account.profile_edit', compact('user')); // Ganti nama view
    }

    /**
     * Mengupdate profil user yang sedang login.
     * Method ini akan dipanggil oleh route 'admin.account.profile.update'.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        // Validasi untuk field yang BISA diubah oleh user dari halaman profilnya
        $rules = [
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed', // Password opsional
            'phone_number' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]*$/',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|string|in:male,female',
        ];
        // Email, Role, dan Status tidak diubah dari halaman edit profil pribadi ini.

        $request->validate($rules);

        try {
            $dataToUpdate = [
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'gender' => $request->gender,
            ];

            if ($request->filled('password')) {
                $dataToUpdate['password'] = Hash::make($request->password);
            }

            $user->update($dataToUpdate);

            return redirect()->route('admin.account.profile.edit')->with('success', 'Profil Anda berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('User Profile Update Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request' => $request->except('password', 'password_confirmation') // Jangan log password
            ]);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui profil. Silakan coba lagi.');
        }
    }
}
