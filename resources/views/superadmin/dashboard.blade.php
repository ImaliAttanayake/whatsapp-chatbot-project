<x-app-layout>
    <x-slot name="header">
        <h2 class="sr-only">Super Admin Dashboard</h2>
    </x-slot>

    <div class="min-h-screen" style="background-color: #0b1e2d;">
        <div class="max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-[15px] bg-[#13293d] shadow-[0_25px_60px_rgba(0,0,0,0.25)] border border-white/10 overflow-hidden">
                <div class="px-6 py-8 sm:px-10">
                    <div class="mb-8">
                        <h1 class="text-3xl font-semibold text-white tracking-tight">Welcome to Super Admin Panel</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-300">View your key platform stats, manage admins, and keep the app secure from one dashboard.</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3 mb-8">
                        <div class="group rounded-[15px] bg-gradient-to-br from-[#2d6cdf] to-[#4a8cff] p-6 shadow-[0_18px_45px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(0,0,0,0.18)]">
                            <p class="text-sm font-medium text-white/80 uppercase tracking-[0.2em]">Total Admins</p>
                            <p class="mt-5 text-4xl font-semibold text-white">{{ \App\Models\User::whereIn('role', ['admin', 'superadmin'])->count() }}</p>
                        </div>

                        <div class="group rounded-[15px] bg-gradient-to-br from-[#27ae60] to-[#2ecc71] p-6 shadow-[0_18px_45px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(0,0,0,0.18)]">
                            <p class="text-sm font-medium text-white/80 uppercase tracking-[0.2em]">Total Contacts</p>
                            <p class="mt-5 text-4xl font-semibold text-white">{{ \App\Models\Contact::count() }}</p>
                        </div>

                        <div class="group rounded-[15px] bg-gradient-to-br from-[#f39c12] to-[#f1c40f] p-6 shadow-[0_18px_45px_rgba(0,0,0,0.12)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_22px_55px_rgba(0,0,0,0.18)]">
                            <p class="text-sm font-medium text-white/80 uppercase tracking-[0.2em]">Total Messages</p>
                            <p class="mt-5 text-4xl font-semibold text-white">{{ \App\Models\Message::count() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-start">
                        <a href="{{ route('superadmin.admins.index') }}" class="inline-flex items-center justify-center rounded-full bg-[#34495e] px-6 py-3 text-sm font-semibold text-white transition duration-300 hover:bg-[#4f6b85]">
                            Manage Admins
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-[#e74c3c] px-6 py-3 text-sm font-semibold text-white transition duration-300 hover:bg-[#c0392b]">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>