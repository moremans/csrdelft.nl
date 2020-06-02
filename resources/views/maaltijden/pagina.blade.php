@extends('maaltijden.base')

@section('titel', $titel)

@section('content')
	@parent

	{!! $content !!}
@endsection
