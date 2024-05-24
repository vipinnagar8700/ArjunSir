/* global zxcvbn */
window.wp = window.wp || {};

(function($){
	/**
	 * Contains functions to determine the password strength.
	 *
	 * @since 3.7.0
	 *
	 * @namespace
	 */
	wp.passwordStrength = {
		/**
		 * Determines the strength of a given password.
		 *
		 * Compares first password to the password confirmation.
		 *
		 * @since 3.7.0
		 *
		 * @param {string} password1       The subject password.
		 * @param {Array}  disallowedList An array of words that will lower the entropy of
		 *                                 the password.
		 * @param {string} password2       The password confirmation.
		 *
		 * @return {number} The password strength score.
		 */
		meter : function( password1, disallowedList, password2 ) {
			if ( ! Array.isArray( disallowedList ) )
				disallowedList = [ disallowedList.toString() ];

			if (password1 != password2 && password2 && password2.length > 0)
				return 5;

			if ( 'undefined' === typeof window.zxcvbn ) {
				// Password strength unknown.
				return -1;
			}

			var result = zxcvbn( password1, disallowedList );
			return result.score;
		},
		// Start with empty object - no policy failures.
		policyFails: { },
		/**
		 * Checks the password against the various different policy types as
		 * well as against a blacklist of words that are likely easy to guess.
		 */
		 policyCheck: function( password1, blacklist, password2 ) {

			var baseStrength = wp.passwordStrength.meter( password1, blacklist, password2 );

			// Skip these levels because it's not working properly. It can be
			// `%f*7Fv#pS` and it gives only level 3. Or `%f*7Fv#p` - level 2.
			if ( baseStrength === 2 || baseStrength === 3 ) {
				baseStrength = 4;
			}

			if ( baseStrength === -1 || baseStrength === 5 ) {
				return baseStrength;
			}
			// gets the policies to validate against - passed via wp_localize.
			var policies = ppmPolicyRules;

			if ( typeof policies === 'undefined' ) {
				return;
			}

			// add code to accommodate default entropy weakness.
			wp.passwordStrength.policyFails['strength'] = baseStrength;

			delete( wp.passwordStrength.policyFails['username'] );

			var getName  = $( '#user_login' ).val() || '';
			var username = getName.toLowerCase();

			// checks if the password contains the username = easy to guess.
			if ( username !== '' ) {
				var usernameSrchResult = password1.toLowerCase().search( username );
				if ( usernameSrchResult > -1 ) {
					baseStrength = 2;
					// policy fail as it contains the username in the password.
					wp.passwordStrength.policyFails['username'] = 'username';
				}
			}

			// namespace = policy name, policy = regex to match with.
			// `policyFails` is used in the user-profile.js file after setting.
			$.each( policies, function( namespace, policy ) {

				// if the regex doesn't match against the password then it will
				// result in a `-1` result during the search.
				var regex  = new RegExp( policy, 'g' );
				var result = password1.search( regex );

				if ( result < 0 ) {
					// strength of 2 won't be allowed to submit.
					baseStrength = 2;
					// store a flag that this policy failed for later use.
					wp.passwordStrength.policyFails[namespace] = namespace;
				} else {
					// a fail for the policy might exist but since it passed here delete it.
					delete( wp.passwordStrength.policyFails[namespace] );
				}
				delete( wp.passwordStrength.policyFails['mix_case'] );

			} );

			// Uppercase and lowercase are both in the 'mix_case' policy.
			if ( wp.passwordStrength.policyFails.hasOwnProperty( 'upper_case' ) || wp.passwordStrength.policyFails.hasOwnProperty( 'lower_case' ) ) {
				delete( wp.passwordStrength.policyFails['upper_case'] );
				delete( wp.passwordStrength.policyFails['lower_case'] );
				wp.passwordStrength.policyFails['mix_case'] = 'mix_case';
			}

			return baseStrength;
		},
		/**
		 * Builds an array of words that should be penalized.
		 *
		 * Certain words need to be penalized because it would lower the entropy of a
		 * password if they were used. The disallowedList is based on user input fields such
		 * as username, first name, email etc.
		 *
		 * @since 3.7.0
		 * @deprecated 5.5.0 Use {@see 'userInputDisallowedList()'} instead.
		 *
		 * @return {string[]} The array of words to be disallowed.
		 */
		userInputBlacklist : function() {
			// window.console.log(
			// 	sprintf(
			// 		/* translators: 1: Deprecated function name, 2: Version number, 3: Alternative function name. */
			// 		__( '%1$s is deprecated since version %2$s! Use %3$s instead. Please consider writing more inclusive code.' ),
			// 		'wp.passwordStrength.userInputBlacklist()',
			// 		'5.5.0',
			// 		'wp.passwordStrength.userInputDisallowedList()'
			// 	)
			// );

			return wp.passwordStrength.userInputDisallowedList();
		},

		/**
		 * Builds an array of words that should be penalized.
		 *
		 * Certain words need to be penalized because it would lower the entropy of a
		 * password if they were used. The disallowed list is based on user input fields such
		 * as username, first name, email etc.
		 *
		 * @since 5.5.0
		 *
		 * @return {string[]} The array of words to be disallowed.
		 */
		userInputDisallowedList : function() {
			var i, userInputFieldsLength, rawValuesLength, currentField,
				rawValues       = [],
				disallowedList  = [],
				userInputFields = [ 'user_login', 'first_name', 'last_name', 'nickname', 'display_name', 'email', 'url', 'description', 'weblog_title', 'admin_email' ];

			// Collect all the strings we want to disallow.
			rawValues.push( document.title );
			rawValues.push( document.URL );

			userInputFieldsLength = userInputFields.length;
			for ( i = 0; i < userInputFieldsLength; i++ ) {
				currentField = $( '#' + userInputFields[ i ] );

				if ( 0 === currentField.length ) {
					continue;
				}

				rawValues.push( currentField[0].defaultValue );
				rawValues.push( currentField.val() );
			}

			/*
			 * Strip out non-alphanumeric characters and convert each word to an
			 * individual entry.
			 */
			rawValuesLength = rawValues.length;
			for ( i = 0; i < rawValuesLength; i++ ) {
				if ( rawValues[ i ] ) {
					disallowedList = disallowedList.concat( rawValues[ i ].replace( /\W/g, ' ' ).split( ' ' ) );
				}
			}

			/*
			 * Remove empty values, short words and duplicates. Short words are likely to
			 * cause many false positives.
			 */
			disallowedList = $.grep( disallowedList, function( value, key ) {
				if ( '' === value || 4 > value.length ) {
					return false;
				}

				return $.inArray( value, disallowedList ) === key;
			});

			return disallowedList;
		}
	};

	// Backward compatibility.

	/**
	 * Password strength meter function.
	 *
	 * @since 2.5.0
	 * @deprecated 3.7.0 Use wp.passwordStrength.meter instead.
	 *
	 * @global
	 *
	 * @type {wp.passwordStrength.meter}
	 */
	window.passwordStrength = wp.passwordStrength.meter;
})(jQuery);
