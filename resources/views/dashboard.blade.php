<x-app-layout>
		<x-slot name="header">
				<h2 class="font-semibold text-xl text-gray-800 leading-tight">
						{{ __('Lokationer') }}
				</h2>
		</x-slot>

		<div class="py-12">
				<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
						<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
								<div class="p-6 text-gray-900">
										{{ __("Klik på den ønskede lokation nedenunder.") }}

								<div class="p-6 text-gray-900 grid grid-cols-3 gap-4">
@foreach($locations as $location)
<a href="{{route('view.location', ['location_id' => $location->id])}}" class="rounded-xl bg-gray-100 cursor-pointer hover:bg-gray-300 p-4 border border-gray-400">
{{$location->name}} 
</a>
@endforeach

								</div>
								</div>
						</div>
				</div>
		</div>
</x-app-layout>
