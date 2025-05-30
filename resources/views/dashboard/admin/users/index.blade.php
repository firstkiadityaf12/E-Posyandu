@extends('dashboard.admin.layout.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Daftar Pengguna</h1>
        <a href="{{ route('user.create') }}" class="btn btn-primary">Tambah Akun</a>
    </div>

    {{-- ✅ Flash Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" dusk="flash-success">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('user.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="role" class="form-label">Role:</label>
                    <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        @foreach(['admin', 'petugas', 'orangtua'] as $role)
                            <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="verifikasi" class="form-label">Verifikasi:</label>
                    <select name="verifikasi" id="verifikasi" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="waiting" {{ request('verifikasi') === 'waiting' ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved" {{ request('verifikasi') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('verifikasi') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <div class="col-md-4 ms-auto">
                    <label for="search" class="form-label">Cari:</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, email, telepon..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Cari
                        </button>
                        @if(request()->has('search') || request()->has('role') || request()->has('verifikasi'))
                            <a href="{{ route('user.index') }}" class="btn btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Pengguna -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" dusk="user-table">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th>Verifikasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td dusk="role-{{ $user->id }}">
                                    <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'petugas' ? 'primary' : 'success') }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>

                                <td>{{ $user->phone }}</td>
                                <td>{{ Str::limit($user->address, 30) }}</td>
                                <td>
                                    @if($user->verifikasi === 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($user->verifikasi === 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @else
                                        <span class="badge bg-secondary">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button dusk="dropdown-{{ $user->id }}" class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="{{ route('user.edit', $user->id) }}" class="dropdown-item">
                                                    <i class="bi bi-pencil-square me-2"></i>Edit
                                                </a>
                                            </li>

                                            @if(auth()->check() && auth()->user()->role === 'admin')
                                                @if($user->verifikasi !== 'approved')
                                                    <li>
                                                        <form action="{{ route('user.updateStatus', $user->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status_akun" value="approved">
                                                            <button dusk="verify-{{ $user->id }}-approve" class="dropdown-item" type="submit">
                                                                <i class="bi bi-check-circle me-2"></i>Setujui
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                @if($user->verifikasi !== 'rejected')
                                                    <li>
                                                        <form action="{{ route('user.updateStatus', $user->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status_akun" value="rejected">
                                                            <button dusk="verify-{{ $user->id }}-reject" class="dropdown-item" type="submit">
                                                                <i class="bi bi-x-circle me-2"></i>Tolak
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endif

                                            <li>
                                                <form action="{{ route('user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button dusk="delete-{{ $user->id }}" type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Hapus
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        Tidak ada data pengguna ditemukan
                                        @if(request()->has('search') || request()->has('role') || request()->has('verifikasi'))
                                            dengan filter yang dipilih.
                                            <a href="{{ route('user.index') }}" class="alert-link">Reset filter</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination (opsional) -->
            @if(method_exists($users, 'links'))
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
