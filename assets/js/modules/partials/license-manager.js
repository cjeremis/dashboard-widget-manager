/**
 * Dashboard Widget Manager - License Manager
 *
 * Handles pro license key activation and deactivation.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

export function initLicense() {
	const $section = $('#dwm-inline-license');
	if (!$section.length) return;

	if ($section.data('dwm-license-initialized')) return;
	$section.data('dwm-license-initialized', true);

	const $keyInput      = $('#dwm-pro-license-key');
	const $statusBadge   = $('#dwm-license-status-badge');
	const $activateBtn   = $('#dwm-license-activate');
	const $deactivateBtn = $('#dwm-license-deactivate');
	const ajaxUrl        = dwmAdminVars.ajaxUrl;
	const nonce          = dwmAdminVars.licenseNonce || dwmAdminVars.nonce;
	const minLength      = parseInt($section.attr('data-min-length'), 10) || 19;

	let currentStatus    = $section.attr('data-license-status') || 'inactive';
	let activateLabel    = $activateBtn.length ? $activateBtn.text() : 'Activate';
	let deactivateLabel  = $deactivateBtn.length ? $deactivateBtn.text() : 'Remove License';
	let validationTimer  = null;

	function validateKey() {
		const key = ($keyInput.val() || '').trim();
		const isValidLength = key.length >= minLength;
		const isActive = currentStatus === 'active';

		if (!isActive && !isValidLength) {
			$activateBtn.prop('disabled', true);
		} else {
			$activateBtn.prop('disabled', false);
		}
	}

	$keyInput.on('input keyup paste', function() {
		clearTimeout(validationTimer);
		validationTimer = setTimeout(validateKey, 150);
	});

	validateKey();

	function updateStatus(status, message) {
		const configs = {
			active:        { label: 'Active',   badgeClass: 'dwm-license-badge-success',  enableDeactivate: true },
			inactive:      { label: 'Inactive', badgeClass: 'dwm-license-badge-inactive', enableDeactivate: false },
			expired:       { label: 'Expired',  badgeClass: 'dwm-license-badge-expired',  enableDeactivate: false },
			revoked:       { label: 'Revoked',  badgeClass: 'dwm-license-badge-expired',  enableDeactivate: false },
			limit_reached: { label: 'Limit Reached', badgeClass: 'dwm-license-badge-expired', enableDeactivate: true },
		};
		const config = configs[status] || configs.inactive;
		currentStatus = status;

		$statusBadge
			.text(config.label)
			.removeClass('dwm-license-badge-success dwm-license-badge-inactive dwm-license-badge-expired')
			.addClass(config.badgeClass);

		$section.attr('data-license-status', status);

		if (message) {
			status === 'active' ? DWMToast.success(message) : DWMToast.error(message);
		}

		if ($deactivateBtn.length) {
			$deactivateBtn.prop('disabled', !config.enableDeactivate);
		}

		validateKey();
	}

	// Activate
	$activateBtn.on('click', function() {
		const key = ($keyInput.val() || '').trim();
		if (!key || key.length < minLength) return;

		$activateBtn.prop('disabled', true).text('Activating...');

		$.post(ajaxUrl, {
			action:      'dwm_pro_activate_license',
			nonce:       nonce,
			license_key: key,
		}).done(function(resp) {
			if (resp && resp.success) {
				updateStatus('active', (resp.data && resp.data.message) || 'License activated.');
				setTimeout(function() { window.location.reload(); }, 1500);
			} else {
				updateStatus('inactive', (resp && resp.data && resp.data.message) || 'Activation failed.');
				$activateBtn.prop('disabled', false).text(activateLabel);
			}
		}).fail(function() {
			updateStatus('inactive', 'Activation failed. Please try again.');
			$activateBtn.prop('disabled', false).text(activateLabel);
		});
	});

	// Deactivate / Remove
	$deactivateBtn.on('click', function() {
		$deactivateBtn.prop('disabled', true).text('Removing...');

		$.post(ajaxUrl, {
			action: 'dwm_pro_deactivate_license',
			nonce:  nonce,
		}).done(function(resp) {
			if (resp && resp.success) {
				updateStatus('inactive', (resp.data && resp.data.message) || 'License removed.');
				setTimeout(function() { window.location.reload(); }, 1500);
			} else {
				DWMToast.error((resp && resp.data && resp.data.message) || 'Could not remove license.');
				$deactivateBtn.prop('disabled', false).text(deactivateLabel);
			}
		}).fail(function() {
			DWMToast.error('Request failed. Please try again.');
			$deactivateBtn.prop('disabled', false).text(deactivateLabel);
		});
	});
}
