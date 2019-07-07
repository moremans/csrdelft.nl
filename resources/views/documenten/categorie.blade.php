@extends('documenten.base')

@section('titel')
Documenten in categorie: {{ $categorie->naam }}
@endsection

@section('breadcrumbs')
	@php(\CsrDelft\model\MenuModel::instance()->renderBreadcrumbs([
		(object) ['link' => '/', 'tekst' => 'main'],
		(object) ['link' => '/documenten', 'tekst' => 'Documenten'],
		(object) ['link' => '/', 'tekst' => $categorie->naam],
	]))
@endsection

@section('content')
	<div id="controls">
		@can(P_DOCS_MOD)
			<a class="btn" href="/documenten/toevoegen?catID={{$categorie->id}}">@icon('toevoegen') Toevoegen</a>
		@endcan
	</div>

	<h1>{{$categorie->naam}}</h1>

	<table id="documentencategorie" class="table table-striped">
		<thead>
		<tr>
			<th>Document</th>
			<th>Grootte</th>
			<th>Type</th>
			<th>Toegevoegd</th>
			<th>Eigenaar</th>
		</tr>
		</thead>
		<tbody>
		@forelse($documenten as $document)
			@include('documenten.documentregel', ['document' => $document])
		@empty
			<tr>
				<td class="document" colspan="5">Geen documenten in deze categorie.</td>
			</tr>
		@endforelse
		</tbody>
		<tfoot>
		<tr>
			<th>Document</th>
			<th>Grootte</th>
			<th>Type</th>
			<th>Toegevoegd</th>
			<th>Eigenaar</th>
		</tr>
		</tfoot>
	</table>
@endsection
