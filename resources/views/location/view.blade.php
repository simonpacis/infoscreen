<x-app-layout>
		<x-slot name="header">
				<h2 class="font-semibold text-xl text-gray-800 leading-tight">
						Infoskærme i {{ $location->name }}
				</h2>
		</x-slot>

		<div class="py-12">
				<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
						<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
								<div class="p-6 text-gray-900 grid grid-cols-3 gap-4">
@foreach($location->units as $unit)
<a href="{{route('view.unit', ['location_id' => $location->id, 'unit_number' => $unit->unit_number])}}" class="rounded-xl bg-gray-100 cursor-pointer hover:bg-gray-300 p-4 border border-gray-400">
{{$unit->name}} @if($unit->processing) <span class="ml-2 text-gray-500 text-sm">Behandler</span>@endif
</a>
@endforeach

								</div>
						</div>
				</div>
		</div>
</x-app-layout>
