<x-app-layout>
		<x-slot name="header">
				<h2 class="font-semibold text-xl text-gray-800 leading-tight">
						<span class="font-bold">{{ $unit->name }}</span> i <a class="underline" href="{{ route('view.location', $unit->location_id) }}">{{ $unit->location->name }}</a>
				</h2>
		</x-slot>

		<div class="py-12">
				<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
						<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
						@if($unit->processing)
<p class="m-3 p-4 text-gray-900 bg-red-100 rounded-xl">Denne infoskærm er ved at behandle nyligt uploadede filer. Det kan tage op til 15 minutter før uploadede filer forefindes på infoskærmen. Uploader du nye filer her starter denne proces forfra.</p>
@endif
								<div class="p-6 text-gray-900 " id="app-elements" data-props='@json(["elements" => $elements, 'unit_id' => $unit->id, 'location_id' => $unit->location_id])'>
								</div>
						</div>
				</div>
		</div>
</x-app-layout>
