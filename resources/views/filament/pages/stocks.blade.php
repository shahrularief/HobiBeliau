<x-filament-panels::page>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-950 dark:text-white">Stock List</h1>
        <x-filament::button tag="a" href="{{ route('filament.admin.pages.add-stock') }}">
            + Add Stock
        </x-filament::button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        {{-- If there are no stock items available, display a message to the user to start adding them. --}}
        @if($this->getStockItems() == [])
            <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-lg rounded-lg p-4 transition-transform transform hover:scale-105 hover:shadow-xl flex flex-col h-[350px]">
                <div class="flex-grow flex flex-col justify-between mt-4">
                    <div>
                        <h3 class="text-lg font-semibold text-black dark:text-white">No Stock Available.</h3>
                        <p class="text-gray-600 dark:text-gray-300">Start adding them!</p>
                    </div>
                </div>
            </div>
        @endif

        @foreach($this->getStockItems() as $stock)
            <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-lg rounded-lg p-4
                        transition-transform transform hover:scale-105 hover:shadow-xl w-full max-w-[280px] mx-auto relative">

                <!-- Action Buttons (Edit & Delete) - Positioned Outside Image -->
                <div class="flex justify-end space-x-2 mb-2">
                    <!-- Edit Button -->
                    <a href="{{ route('filament.admin.pages.edit-stock', ['id' => $stock['id']]) }}"  class="text-blue-500 hover:text-blue-700">
                        ✏️
                    </a>

                    <!-- Delete Button -->
                    <button onclick="confirmDelete({{ $stock['id'] }})" class="text-red-500 hover:text-red-700">
                        ❌
                    </button>
                </div>

                <!-- Fixed Square Image Container -->
                <div class="w-[200px] h-[200px] aspect-square bg-gray-200 dark:bg-gray-700 flex items-center justify-center rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $stock['image']) }}"
                         alt="{{ $stock['title'] }}"
                         class="w-[200px] h-[200px] object-cover">
                </div>

                <!-- Card Details -->
                <h3 class="text-lg font-semibold mt-4 text-gray-950 dark:text-white text-center">{{ $stock['title'] }}</h3>
                {{-- <p class="text-gray-600 dark:text-gray-500 text-center">{{ $stock['description'] }}</p> --}}
                <p class="text-gray-900 dark:text-gray-100 font-bold mt-2 text-center">Quantity: {{ $stock['quantity'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Delete Confirmation Script -->
    <script>
        function confirmDelete(stockId) {
            if (confirm("Are you sure you want to delete this stock? This action cannot be undone.")) {
                fetch(`/delete-stock/${stockId}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json"
                    }
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          alert("Stock deleted successfully!");
                          location.reload();
                      } else {
                          alert("Error deleting stock!");
                      }
                  });
            }
        }
    </script>
</x-filament-panels::page>
