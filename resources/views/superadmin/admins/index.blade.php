<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">Manage Admins</h2>
    </x-slot>

    <div class="py-12" style="background-color: #0b1e2d;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-[18px] bg-[#13293d] shadow-[0_20px_45px_rgba(0,0,0,0.15)] overflow-hidden border border-white/10">
                <!-- Header with Title and Add Admin Button -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-5 border-b border-white/10">
                    <div>
                        <h1 class="text-xl font-bold text-white">Manage Admins</h1>
                        <p class="text-sm text-slate-400 mt-1">Manage all system admins</p>
                    </div>

                    <a href="{{ route('superadmin.admins.create') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#3b82f6] to-[#2563eb] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all duration-200 hover:from-[#2563eb] hover:to-[#1d4ed8] hover:shadow-blue-500/50 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#3b82f6] focus:ring-offset-2 focus:ring-offset-[#13293d]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Admin
                    </a>
                </div>

                <div class="px-6 py-6 border-b border-white/10">
                    @if(session('success'))
                        <div class="rounded-2xl bg-[#1f4f2c] px-4 py-3 text-sm text-[#e9f9ec] shadow-sm border border-[#2ecc71]/30">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="rounded-2xl bg-[#4b1f1f] px-4 py-3 text-sm text-[#ffe7e7] shadow-sm border border-[#e74c3c]/20">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="text-left text-sm uppercase tracking-[0.12em] text-[#9fb3c8]">
                                    <th class="px-5 py-4">ID</th>
                                    <th class="px-5 py-4">Name</th>
                                    <th class="px-5 py-4">Email</th>
                                    <th class="px-5 py-4">Role</th>
                                    <th class="px-5 py-4">Created Date</th>
                                    <th class="px-5 py-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins as $admin)
                                    <tr class="transition hover:bg-white/5">
                                        <td class="px-5 py-4 align-middle text-sm text-slate-400">{{ $admin->id }}</td>
                                        <td class="px-5 py-4 align-middle text-sm text-white">{{ $admin->name }}</td>
                                        <td class="px-5 py-4 align-middle text-sm text-slate-300">{{ $admin->email }}</td>
                                        <td class="px-5 py-4 align-middle">
                                            @if($admin->role === 'superadmin')
                                                <span class="inline-flex rounded-full bg-[#8e44ad]/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#d6c7ee]">
                                                    Super Admin
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-[#2d6cdf]/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#c9ddff]">
                                                    Admin
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 align-middle text-sm text-slate-400">{{ $admin->created_at->format('M d, Y') }}</td>
                                        <td class="px-5 py-4 align-middle text-sm">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('superadmin.admins.edit', $admin) }}"
                                                   class="inline-flex items-center rounded-full bg-[#3498db] px-4 py-2 text-white transition hover:bg-[#2c80c9]">
                                                    Edit
                                                </a>

                                                @if($admin->role !== 'superadmin' && $admin->id !== auth()->id())
                                                    <form method="POST" action="{{ route('superadmin.admins.destroy', $admin) }}" class="inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-full bg-[#e74c3c] px-4 py-2 text-white transition hover:bg-[#cf4331]">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>