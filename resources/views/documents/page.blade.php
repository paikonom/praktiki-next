@extends(in_array($role, ['student', 'provider'], true) ? 'layouts.portal' : 'layouts.app', [
    'portalRole' => $role,
    'activePortalPage' => 'documents',
])

@section('title', 'Έντυπα')

@section(in_array($role, ['student', 'provider'], true) ? 'portal-content' : 'content')
    @include('documents.index')
@endsection
