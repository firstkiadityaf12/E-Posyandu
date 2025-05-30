<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balita;
use App\Models\User;
use App\Models\Inspection;

class BalitaController extends Controller
{
    public function index(Request $request)
    {
        $query = Balita::with(['user', 'latestInspection']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_anak', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedFields = ['nama_anak', 'nik', 'tanggal_lahir', 'created_at', 'updated_at'];
        
        if (in_array($sortField, $allowedFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $balitas = $query->paginate(10)->withQueryString();

        return view('dashboard.admin.balita.index', [
            'balitas' => $balitas
        ]);
    }

    public function create()
    {
        return view('dashboard.admin.balita.create', [
            'users' => User::where('role', 'orangtua')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nama_anak' => 'required|string|max:255',
            'nik' => 'required|string|size:16|unique:table_child',
            'tanggal_lahir' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . now()->subYears(6)->format('Y-m-d')
            ],
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
        ], [
            'nik.size' => 'NIK harus terdiri dari 16 digit',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh lebih dari hari ini',
            'tanggal_lahir.after' => 'Usia anak tidak boleh lebih dari 5 tahun',
        ]);

        Balita::create($validatedData);

        return redirect()
            ->route('dashboard.admin.balita.index')
            ->with('success', 'Data balita berhasil ditambahkan!');
    }

    public function show(Balita $balita)
    {
        $balita->load(['user', 'inspections' => function($query) {
            $query->orderBy('tanggal_pemeriksaan', 'asc');
        }]);

        // Format inspection data for charts
        $inspectionData = $balita->inspections->map(function($inspection) {
            return [
                'tanggal_pemeriksaan' => \Carbon\Carbon::parse($inspection->tanggal_pemeriksaan)->format('d/m/Y'),
                'berat_badan' => $inspection->berat_badan,
                'tinggi_badan' => $inspection->tinggi_badan,
                'lingkar_kepala' => $inspection->lingkar_kepala,
            ];
        });

        return view('dashboard.admin.balita.show', [
            'balita' => $balita,
            'inspectionData' => $inspectionData
        ]);
    }

    public function edit(Balita $balita)
    {
        return view('dashboard.admin.balita.edit', [
            'balita' => $balita->load('latestInspection'),
            'users' => User::where('role', 'orangtua')->get()
        ]);
    }

    public function update(Request $request, Balita $balita)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'nama_anak' => 'required|string|max:255',
            'tanggal_lahir' => [
                'required',
                'date',
                'before_or_equal:today',
                'after:' . now()->subYears(6)->format('Y-m-d')
            ],
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
        ];

        if ($request->nik != $balita->nik) {
            $rules['nik'] = 'required|string|size:16|unique:table_child';
        }

        $validatedData = $request->validate($rules, [
            'nik.size' => 'NIK harus terdiri dari 16 digit',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh lebih dari hari ini',
            'tanggal_lahir.after' => 'Usia anak tidak boleh lebih dari 5 tahun',
        ]);

        $balita->update($validatedData);

        return redirect()
            ->route('dashboard.admin.balita.index')
            ->with('success', 'Data balita berhasil diperbarui!');
    }

    public function destroy(Balita $balita)
    {
        // Delete related inspections first
        $balita->inspections()->delete();
        
        // Then delete the balita record
        $balita->delete();
        
        return redirect()
            ->route('dashboard.admin.balita.index')
            ->with('success', 'Data balita berhasil dihapus!');
    }
} 