<?php

/**
 * NOTE: options defined here in utexas_wpsax_filter_option are the defaults for UT sites on Pantheon. If for some reason you want to override anything set here, create your own mini-plugin -- utexas-wp-saml-auth-overrides.php.template
 */
function utexas_wpsax_filter_option( $value, $option_name ) {
	$defaults = array(
		'connection_type'        => 'internal',
		'auto_provision'         => false,
		'permit_wp_login'        => false, // Setting to 'true' is not currently supported.
		'get_user_by'            => 'login',
		'user_login_attribute'   => 'username',
		'user_email_attribute'   => 'Email',
		'display_name_attribute' => 'full_name',
		'first_name_attribute'   => 'full_name',
		'default_role'           => get_option( 'default_role' ),
		'internal_config'        => array(
			'strict'   => true,
			'debug'    => false,
			'baseurl'  => home_url(),
			'sp'       => array(
				/* The entityId can be any arbitrary unique value, but it must match what the iDP has on record.
				Here we use the domain name of the site as a unique value, appended with 'onelogin'.
				The base url of network_site_url() here accommodates multisites; for a multisite, the
				value here must match the one record present in the iDP's metadata record; therefore
				we do not use home_url(), which on a multisite would be the individual site path.	*/
				'entityId'                 => trailingslashit( network_site_url() ) . 'onelogin',
				'assertionConsumerService' => array(
					/* The format 'saml/login/' with the trailing slash is required in order to match
					the metadata value provide to the iDP. See eis1-wcs/pantheon-stewardship-tasks .
					The base url of network_site_url() here accommodates multisites; for a multisite, the
					value here must match the one record present in the iDP's metadata record; therefore
					we do not use home_url(), which on a multisite would be the individual site path. */
					'url'     => trailingslashit( network_site_url() ) . 'saml/login/',
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				),
				'x509cert'                 => getCertificate('sp_x509_certificate'),
				'privateKey'               => getCertificate('sp_private_key'),
			),
			'idp'      => array(
				'entityId'                 => 'https://enterprise.login.utexas.edu/idp/shibboleth',
				'singleSignOnService'      => array(
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
					'url'     => 'https://enterprise.login.utexas.edu/idp/profile/SAML2/Redirect/SSO',
				),
				'singleLogoutService'      => array(
					'https://enterprise.login.utexas.edu/idp/profile/Logout',
				),
				'x509cert'                 => getCertificate('idp_cert'),
				'certFingerprint'          => '',
				'certFingerprintAlgorithm' => '',
			),
			'security' => array(
				'allowRepeatAttributeName' => true,
			),
		),
	);
	$value    = isset( $defaults[ $option_name ] ) ? $defaults[ $option_name ] : $value;
	return $value;
}

/**
 * Helper function to get certificates.
 *
 * @param string $name
 *   The machine name of the cert to get, matching the samlauth config value.
 *
 * @return mixed
 *   A string representing the cert, or the path to a file, or NULL.
 */
function getCertificate($name) {
  // First see if the value is provided by a Pantheon Organizational secret.
  if (function_exists('pantheon_get_secret')) {
    $certificate = pantheon_get_secret($name) ?? NULL;
    if (!is_null($certificate)) {
      return $certificate;
    }
  }
  return NULL;
}
