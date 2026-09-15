<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Become a seller</h2></x-slot>
    <div class="py-12"><div class="max-w-3xl mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-6 rounded-lg shadow">
            @if(session('status'))<p class="mb-4">{{ session('status') }}</p>@endif
            @if($application)
                <h3 class="text-lg font-semibold">{{ $application->shop_name }}</h3>
                <p class="mt-3">Application status: {{ ucfirst($application->status) }}</p>
                <p class="mt-3">{{ $application->description }}</p>
                @if(auth()->user()->selling_suspended)
                    <p class="mt-4">Your selling access is suspended. Contact the administrator for assistance.</p><a href="{{ route('orders.sales') }}" class="underline">Manage existing sales</a>
                @elseif($application->status === 'approved')
                    <p class="mt-4">You are approved to sell.</p><a href="{{ route('seller.listings.index') }}" class="inline-block mt-4 underline">Manage my card listings</a>
                @elseif($application->status === 'pending')
                    <p class="mt-4">An administrator will review your application.</p>
                @else
                    <p class="mt-4">Your application was not approved. Contact the administrator for assistance.</p>
                @endif
            @else
                <p class="mb-6">Tell us about your shop and the cards you plan to sell. You can continue buying with this account.</p>
                <x-validation-errors class="mb-4" />
                <form method="POST" action="{{ route('seller.apply.store') }}" class="space-y-4">
                    @csrf
                    <div><x-label for="shop_name" value="Shop name" /><x-input id="shop_name" name="shop_name" class="block mt-1 w-full" :value="old('shop_name')" maxlength="100" required /></div>
                    <div><x-label for="description" value="About your shop" /><textarea id="description" name="description" rows="5" maxlength="2000" required class="block mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">{{ old('description') }}</textarea></div>
                    <x-button>Submit application</x-button>
                </form>
            @endif
        </div>
    </div></div>
</x-app-layout>
