<p>Dear {{ $member->first_name }},</p>

<p>
    Your alliance profile has been created successfully by the church admin.
</p>

<p>
    <strong>Name:</strong> {{ $alliance->first_name }} {{ $alliance->last_name }}<br>
    <strong>Type:</strong> {{ ucfirst($alliance->alliance_type) }}
</p>

<p>
    Your profile will be published after approval.
</p>

<p>
    Blessings,<br>
    CSI Centenary Wesley Church
</p>
