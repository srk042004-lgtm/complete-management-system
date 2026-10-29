@extends('frontdesk.quick-actions.layout')

@section('title', 'Add Guest')
@section('description', 'Create a guest profile for this hotel workspace.')

@section('content')
<form method="POST" action="{{ route('frontdesk.guests.store') }}" enctype="multipart/form-data" class="panel">
    @csrf
    <div class="form-grid">
        <div class="field"><label for="name">Full name *</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="255" required></div>
        <div class="field"><label for="mobile">Mobile *</label><input id="mobile" name="mobile" type="tel" value="{{ old('mobile') }}" placeholder="03XX-XXXXXXX" autocomplete="tel" required maxlength="50"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255"></div>
        <div class="field"><label for="opening_balance">Opening balance *</label><input id="opening_balance" name="opening_balance" type="number" step="0.01" value="{{ old('opening_balance', '0.00') }}" required></div>
        <div class="field"><label for="id_type">CNIC / ID type *</label><select id="id_type" name="id_type" required><option value="cnic_national_id" @selected(old('id_type', 'cnic_national_id') === 'cnic_national_id')>CNIC / National ID</option><option value="passport" @selected(old('id_type') === 'passport')>Passport</option><option value="drivers_license" @selected(old('id_type') === 'drivers_license')>Driver's license</option></select></div>
        <div class="field"><label for="id_number">ID number *</label><input id="id_number" name="id_number" value="{{ old('id_number') }}" maxlength="255" required></div>
        <div class="field full"><label for="address">Address</label><textarea id="address" name="address" autocomplete="street-address" maxlength="2000">{{ old('address') }}</textarea></div>
        <div class="field"><label for="photo">Guest photo</label><input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp"><small class="muted">JPG, PNG, WebP; maximum 5 MB.</small></div>
        <div class="field"><label for="id_document">CNIC / ID upload</label><input id="id_document" name="id_document" type="file" accept=".jpg,.jpeg,.png,.pdf"><small class="muted">JPG, PNG, PDF; maximum 2 MB.</small></div>
    </div>
    <div class="actions"><button class="button" type="submit">Save guest</button></div>
</form>
@endsection
