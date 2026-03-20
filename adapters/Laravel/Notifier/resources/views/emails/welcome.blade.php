<p>Welcome to Atomy.</p>

@if(isset($data['temporary_password']) && is_string($data['temporary_password']) && $data['temporary_password'] !== '')
<p>Your temporary password is: <strong>{{ $data['temporary_password'] }}</strong></p>
@endif

<p>If you didn’t request this, you can ignore this email.</p>

