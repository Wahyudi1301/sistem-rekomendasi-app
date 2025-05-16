<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        // Tetap lindungi SEMUA method di controller ini hanya untuk ADMIN
        // Ini berarti hanya admin yang bisa masuk ke menu "Kelola Users"
        $this->middleware(function ($request, $next) {
            if (Gate::denies('is-admin')) { // Menggunakan Gate 'is-admin' yang sudah ada
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Menampilkan halaman daftar user (Admin & Staff).
     */
    public function index()
    {
        return view('admin.users.index');
    }

    /**
     * Menyediakan data users (Admin & Staff) untuk DataTables.
     */
    public function getData(Request $request)
    {
        // Ambil user dengan role 'admin' ATAU 'staff'
        $users = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
                     ->select('users.*');

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.users.edit', ['user' => $row->hashid]);
                $deleteUrl = route('admin.users.destroy', ['user' => $row->hashid]);
                $deleteButton = '';

                // Admin tidak bisa menghapus dirinya sendiri
                if (Auth::id() !== $row->id) {
                    // Admin juga tidak bisa menghapus admin lain jika dia satu-satunya admin (atau aturan bisnis lain)
                    // Untuk contoh ini, kita izinkan hapus admin lain selama bukan diri sendiri
                    // dan bukan satu-satunya admin (jika role-nya admin)
                    if ($row->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1 && Auth::id() !== $row->id) {
                         $deleteButton = '<button type="button" class="btn btn-sm btn-danger" disabled title="Tidak bisa menghapus satu-satunya admin">Hapus</button>';
                    } else {
                        $deleteButton = '
                        <form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete-user">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="button" class="btn btn-sm btn-danger btn-delete-user">Hapus</button>
                        </form>';
                    }
                } else {
                    $deleteButton = '<button type="button" class="btn btn-sm btn-danger" disabled title="Tidak bisa menghapus diri sendiri">Hapus</button>';
                }


                return '<a href="' . $editUrl . '" class="btn btn-sm btn-warning me-1">Edit</a> ' . $deleteButton;
            })
            ->editColumn('status', function ($row) {
                if ($row->status === 'active') {
                    return '<span class="badge bg-light-success">Aktif</span>';
                } elseif ($row->status === 'inactive') {
                    return '<span class="badge bg-light-danger">Nonaktif</span>';
                }
                return '<span class="badge bg-light-secondary">' . ucfirst($row->status) . '</span>';
            })
            ->editColumn('gender', fn($row) => $row->gender ? ucfirst($row->gender) : '-')
            ->addColumn('role_display', fn($row) => $row->role_display ) // Menggunakan accessor role_display
            ->editColumn('created_at', fn($row) => $row->created_at?->format('d M Y, H:i') ?? '-')
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    /**
     * Menampilkan form tambah user baru (bisa Admin atau Staff).
     */
    public function create()
    {
        // Admin bisa membuat user Admin atau Staff
        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_STAFF => 'Staff',
        ];
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Menyimpan user baru (Admin atau Staff) ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]*$/',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|string|in:male,female',
            'status' => 'required|string|in:active,inactive',
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])], // Validasi role
        ]);

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'gender' => $request->gender,
                'status' => $request->status,
                'role' => $request->role, // Simpan role dari form
                'email_verified_at' => now(),
            ]);
            return redirect()->route('admin.users.index')->with('success', "User dengan role {$request->role} berhasil ditambahkan.");
        } catch (\Exception $e) {
            Log::error('User Creation Error: ' . $e->getMessage(), ['request' => $request->all()]);
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan user. Silakan coba lagi.');
        }
    }

    /**
     * Menampilkan form edit user (Admin atau Staff).
     */
    public function edit(User $user)
    {
        // Admin bisa mengedit user Admin atau Staff
        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_STAFF => 'Staff',
        ];
        // Anda mungkin ingin mencegah admin mengubah role dirinya sendiri atau role admin lain ke staff jika dia satu-satunya admin.
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Mengupdate data user (Admin atau Staff) di database.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]*$/',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|string|in:male,female',
            'status' => 'required|string|in:active,inactive',
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])], // Validasi role
        ]);

        try {
            // Logika untuk mencegah perubahan role yang tidak diinginkan (misal, admin terakhir)
            if ($user->role === User::ROLE_ADMIN && $request->role === User::ROLE_STAFF) {
                if (User::where('role', User::ROLE_ADMIN)->count() <= 1 && $user->id === Auth::id()) {
                    return redirect()->back()->withInput()->with('error', 'Tidak dapat mengubah role satu-satunya admin menjadi staff.');
                }
                if (User::where('role', User::ROLE_ADMIN)->count() <= 1 && $user->id !== Auth::id() && Auth::user()->isAdmin()) {
                     // Jika admin lain mencoba mengubah role satu-satunya admin yg tersisa
                     return redirect()->back()->withInput()->with('error', 'Tidak dapat mengubah role satu-satunya admin lain menjadi staff.');
                }
            }


            $dataToUpdate = [
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'gender' => $request->gender,
                'status' => $request->status,
                'role' => $request->role, // Update role
            ];

            if ($request->filled('password')) {
                $dataToUpdate['password'] = Hash::make($request->password);
            }

            $user->update($dataToUpdate);
            return redirect()->route('admin.users.index')->with('success', "User berhasil diperbarui.");
        } catch (\Exception $e) {
            Log::error('User Update Error: ' . $e->getMessage(), ['user_id' => $user->id, 'request' => $request->all()]);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui user. Silakan coba lagi.');
        }
    }

    /**
     * Menghapus user (Admin atau Staff) dari database.
     */
    public function destroy(User $user)
    {
        try {
            if (Auth::id() === $user->id) {
                 return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            }
            // Tambahan: Cek jika admin ini adalah satu-satunya admin dan role-nya admin
            if ($user->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
                return redirect()->route('admin.users.index')->with('error', 'Tidak dapat menghapus satu-satunya admin. Buat admin lain terlebih dahulu atau ubah role-nya.');
            }

            $userName = $user->name;
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', "User '{$userName}' berhasil dihapus.");
        } catch (\Exception $e) {
            Log::error('User Deletion Error: ' . $e->getMessage(), ['user_id' => $user->id]);
            return redirect()->route('admin.users.index')->with('error', 'Gagal menghapus user.');
        }
    }
}
