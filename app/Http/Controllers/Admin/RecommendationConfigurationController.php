<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecommendationConfiguration;
// User model mungkin tidak perlu di-import lagi di sini jika tidak ada relasi
// use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
// Auth facade tidak perlu lagi untuk mengambil Auth::id()
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // Untuk Str::limit

class RecommendationConfigurationController extends Controller
{
    public function index()
    {
        return view('admin.recommendation_configurations.index');
    }

    public function getData(Request $request)
    {
        // Tidak perlu with('updatedByUser') atau with('user')
        $configurations = RecommendationConfiguration::select('recommendation_configurations.*');

        return DataTables::of($configurations)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.recommendation_configurations.edit', ['configuration' => $row->hashid ?? $row->id]);
                $deleteUrl = route('admin.recommendation_configurations.destroy', ['configuration' => $row->hashid ?? $row->id]);
                return '
                <a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Yakin ingin menghapus parameter ini?\');">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
                ';
            })
            // Kolom 'Oleh' (updated_by) dihapus
            ->editColumn('parameter_value', fn($row) => Str::limit($row->parameter_value, 70, '...'))
            ->editColumn('updated_at', fn($row) => $row->updated_at?->format('d M Y H:i') ?? '-')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.recommendation_configurations.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'parameter_name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('recommendation_configurations', 'parameter_name')
            ],
            'parameter_value' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            // Tidak perlu menambahkan user_id
            RecommendationConfiguration::create($validatedData);
            return redirect()->route('admin.recommendation_configurations.index')
                ->with('success', 'Parameter konfigurasi berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('RecommendationConfiguration Creation Error: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan parameter konfigurasi.');
        }
    }

    public function edit(RecommendationConfiguration $configuration)
    {
        return view('admin.recommendation_configurations.edit', compact('configuration'));
    }

    public function update(Request $request, RecommendationConfiguration $configuration)
    {
        $validatedData = $request->validate([
            'parameter_name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('recommendation_configurations', 'parameter_name')->ignore($configuration->id)
            ],
            'parameter_value' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            // Tidak perlu menambahkan user_id
            $configuration->update($validatedData);
            return redirect()->route('admin.recommendation_configurations.index')
                ->with('success', 'Parameter konfigurasi berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('RecommendationConfiguration Update Error: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui parameter konfigurasi.');
        }
    }

    public function destroy(RecommendationConfiguration $configuration)
    {
        try {
            $parameterName = $configuration->parameter_name;
            $configuration->delete();
            // Jika menggunakan AJAX delete, ini sudah benar.
            // Jika non-AJAX, gunakan redirect:
            // return redirect()->route('admin.recommendation_configurations.index')->with('success', "Parameter konfigurasi '{$parameterName}' berhasil dihapus.");
            return response()->json(['message' => "Parameter konfigurasi '{$parameterName}' berhasil dihapus."]);
        } catch (\Exception $e) {
            Log::error('RecommendationConfiguration Deletion Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat menghapus parameter konfigurasi.'], 500);
        }
    }
}
