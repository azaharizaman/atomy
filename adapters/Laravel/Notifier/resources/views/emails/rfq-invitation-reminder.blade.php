<p>Hello {{ $data['vendor_name'] ?? 'Vendor' }},</p>
<p>This is a reminder that RFQ <strong>{{ $data['rfq_title'] ?? $data['rfq_id'] ?? 'your request' }}</strong> is awaiting your response.</p>
@if (!empty($data['submission_deadline']))
<p>Submission deadline: {{ $data['submission_deadline'] }}</p>
@endif
<p>Delivery channel: {{ $data['channel'] ?? 'email' }}</p>
