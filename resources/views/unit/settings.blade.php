<x-app-layout>
		<x-slot name="header">
				<h2 class="font-semibold text-xl text-gray-800 leading-tight">
						Indstillinger for <a href="{{route('view.unit', ['location_id' => $unit->location_id, 'unit_number' => $unit->unit_number])}}" class="underline font-bold">{{ $unit->name }}</a> i <a class="underline" href="{{ route('view.location', $unit->location_id) }}">{{ $unit->location->name }}</a>
				</h2>
		</x-slot>

		<div class="py-12">
				<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
						<div class="p-6 text-gray-900 bg-white overflow-hidden shadow-sm sm:rounded-lg">
<form action="{{ route('unit.settings_post') }}" method="POST">
		<button class="ml-3 bg-gray-100 font-bold cursor-pointer hover:bg-gray-300  w-auto h-12 mb-6 border-2 border-gray-500 rounded-lg px-4 inline-flex items-center justify-center" type="submit">Gem indstillinger</button>
@csrf

<input type="hidden" name="unit_id" value="{{ $unit->id }}" />

<div class="grid grid-cols-2 gap-4">
<div class="flex items-center ">
<label for="setting"><p>Navn</p></label>
<input type="text" name="name" id="name" class="ml-3 rounded-xl" value="{{ $unit->name }}" />
</div>
<div class="flex items-center">
<label for="orientation"><p>Orientering</p></label>
<select name="orientation" id="orientation" class="ml-3 rounded-xl">

	<option @if($unit->getSetting('orientation', 0) == "0") selected @endif value="0">Horisontal</option>
	<option @if($unit->getSetting('orientation', 0) == "+90") selected @endif value="+90">Vertikal (+90°)</option>
	<option @if($unit->getSetting('orientation', 0) == "-90") selected @endif value="-90">Vertikal (-90°)</option>
	<option @if($unit->getSetting('orientation', 0) == "+180") selected @endif value="+180">Horisontal (+180°)</option>

</select>
						</div>
						</div>

</form>



						</div>
				</div>
		</div>
</x-app-layout>
