<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCost;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;

class ServiceCostController extends Controller
{
    public function index(): View
    {
        return view('admin.service_costs.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Penting: Pastikan kolom 'id' selalu di-select agar hashid bisa bekerja
            $query = ServiceCost::query()->select(['id', 'name', 'label', 'cost', 'description', 'is_active']);
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('cost', fn($row) => 'Rp' . number_format($row->cost, 0, ',', '.'))
                ->editColumn('is_active', fn($row) => $row->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-Aktif</span>')
                ->addColumn('action', function ($row) {
                    // Debugging: Cek apakah hashid ada
                    if (empty($row->hashid)) {
                        Log::error("ServiceCost hashid is empty for ID: " . ($row->id ?? 'UNKNOWN_ID') . ". Make sure Hashidable trait is used correctly in ServiceCost model and 'id' column is selected.");
                        return '<span class="text-danger">Error: Invalid ID</span>';
                    }

                    $editUrl = route('admin.service-costs.edit', $row->hashid); // Menggunakan hashid
                    $deleteUrl = route('admin.service-costs.destroy', $row->hashid); // Menggunakan hashid

                    $actionBtn = '<a href="' . $editUrl . '" class="btn btn-sm btn-primary me-1">Edit</a>';
                    $actionBtn .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Yakin ingin menghapus biaya ini?\');">' . csrf_field() . method_field("DELETE") . '<button type="submit" class="btn btn-sm btn-danger">Hapus</button></form>';
                    return $actionBtn;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }
        return response()->json(['error' => 'Invalid request'], 400);
    }


    public function create(): View
    {
        return view('admin.service_costs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:service_costs,name|max:255|regex:/^[a-z0-9_]+$/',
            'label' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.regex' => 'Kode Internal (Name) hanya boleh berisi huruf kecil, angka, dan underscore (_).'
        ]);

        $serviceCost = new ServiceCost();
        $serviceCost->name = $validated['name'];
        $serviceCost->label = $validated['label'];
        $serviceCost->cost = $validated['cost'];
        $serviceCost->description = $validated['description'];
        $serviceCost->is_active = $request->has('is_active');
        $serviceCost->save();

        return redirect()->route('admin.service-costs.index')->with('success', 'Biaya layanan baru berhasil ditambahkan.');
    }

    public function edit(string $service_cost_hash): View
    {
        $decodedId = Hashids::decode($service_cost_hash);
        if (empty($decodedId)) {
            abort(404, 'Biaya Layanan tidak ditemukan.');
        }
        $serviceCost = ServiceCost::findOrFail($decodedId[0]);
        return view('admin.service_costs.edit', compact('serviceCost'));
    }

    public function update(Request $request, string $service_cost_hash)
    {
        $decodedId = Hashids::decode($service_cost_hash);
        if (empty($decodedId)) {
            abort(404, 'Biaya Layanan tidak ditemukan.');
        }
        $serviceCost = ServiceCost::findOrFail($decodedId[0]);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $serviceCost->label = $validated['label'];
        $serviceCost->cost = $validated['cost'];
        $serviceCost->description = $validated['description'];
        $serviceCost->is_active = $request->has('is_active');
        $serviceCost->save();

        return redirect()->route('admin.service-costs.index')->with('success', 'Biaya layanan berhasil diperbarui.');
    }

    public function destroy(string $service_cost_hash)
    {
        $decodedId = Hashids::decode($service_cost_hash);
        if (empty($decodedId)) {
            return redirect()->route('admin.service-costs.index')->with('error', 'Biaya layanan tidak valid untuk dihapus.');
        }
        $serviceCost = ServiceCost::find($decodedId[0]);
        if ($serviceCost) {
            try {
                // Validasi tambahan sebelum hapus jika diperlukan (misal, apakah biaya ini sedang digunakan)
                $serviceCost->delete();
                return redirect()->route('admin.service-costs.index')->with('success', 'Biaya layanan berhasil dihapus.');
            } catch (\Exception $e) {
                Log::error("Error deleting service cost: " . $e->getMessage(), ['service_cost_id' => $serviceCost->id]);
                return redirect()->route('admin.service-costs.index')->with('error', 'Gagal menghapus biaya layanan. Mungkin masih terhubung dengan data lain.');
            }
        }
        return redirect()->route('admin.service-costs.index')->with('error', 'Biaya layanan tidak ditemukan.');
    }
}
