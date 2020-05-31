<?php
/**
 * @var \CsrDelft\Component\DataTable\DataTableInstance $view
 */
?>
@extends('fiscaat.base')

@section('titel', $titel)

@section('civisaldocontent')
	{!!$view->toResponse()->getContent() !!}
@endsection
