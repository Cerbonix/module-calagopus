<?php

return [
    'connection' => [
        'ok' => 'Connection successful. Calagopus panel :version, supported version.',
        'ok_unknown_version' => 'Connection successful, but the panel did not report a version number. Compatibility checks are disabled.',
        'ok_untested_version' => 'Connection successful, but the panel runs :version, outside the verified range (:min up to but excluding :below). Provisioning may fail if the API changed.',
        'empty_key' => 'No readable API key. Either the field is empty, or the stored key can no longer be decrypted, which happens when the site application key has been rotated. Enter the API key again.',
        'unreachable' => 'Panel unreachable. Check the hostname, the port and the TLS certificate. Detail: :detail',
        'malformed_key' => 'The API key is malformed. The panel expects exactly 48 characters: the key was likely truncated or pasted with a stray space.',
        'invalid_key' => 'API key rejected by the panel. It is invalid, expired, or has been revoked.',
        'ip_not_allowed' => 'The panel rejected the source IP address of this request. The key restricts allowed addresses and this one is not listed. Declare every egress address, not just the one observed today.',
        'missing_permission' => 'The panel denied access. The key, or the account owning it, lacks the required permission. Detail: :detail',
        'missing_permissions' => 'Connection established, but the key lacks some required permissions. Missing: :permissions',
        'conflict' => 'The panel reports a conflict with an existing resource. Detail: :detail',
        'panel_refused' => 'The panel refused to carry out the operation. Detail: :detail',
        'rate_limited' => 'Too many requests sent to the panel. Try again shortly.',
        'unexpected' => 'Unexpected response from the panel. Detail: :detail',
    ],

    'lifecycle' => [
        'created' => 'Server created on the panel.',
        'already_provisioned' => 'A server already exists on the panel for this service, nothing was created.',
        'suspended' => 'Server suspended.',
        'unsuspended' => 'Server unsuspended.',
        'nothing_to_do' => 'The server was already active, no action needed.',
        'customer_changed' => 'Server owner updated on the panel.',
        'terminated' => 'Server deleted from the panel. Customer backups were kept.',
        'already_gone' => 'No matching server on the panel, it was most likely already deleted.',
        'not_found' => 'No server on the panel matches this service. It may have been deleted by hand.',
        'wrong_type' => 'This service is not a Calagopus service.',
        'no_panel' => 'No panel is attached to this service.',
        'no_config' => 'The product has no Calagopus configuration. Fill it in before provisioning.',
        'panel_error' => 'The panel refused the operation. Detail: :detail',
    ],
];
