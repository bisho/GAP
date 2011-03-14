GAP: Google Authenticator for PHP
=================================

This small library let you add support for 2-step authentication like
the one implemented in some Google websites.

Users can use the very same Google Authenticator client applications,
just with a different secret code for your website.

You can download client applications for your mobile from
[here](http://goo.gl/dfszH).

For now just TOTP codes are implemented, following the draft
Time-based One-time Password Algorithm:
http://tools.ietf.org/html/draft-mraihi-totp-timebased-00

Usage
-----

Usage is very simple, but will require you to modify your application
to add more fields to your login data schema.

### 1. The secret key:

Store a 16 char secret in base32 together with the login info of the
user.

To generate a new secret:

    require_once 'path/to/GAP_Authenticator.php';

    $gap = new GAP_Authenticator();
    $key = $gap->generateSecret();

The secret should be only displayed to the user **JUST ONCE**, during
the setup. If there is a way to see it later, any attacker will be
able to steal the secret at a later time, without being noticed,
and that makes 2-step authentication useless.

After displaying it to the user, it's recommended to ask for a TOTP
token for confirmation, to ensure the user has received the key correctly.
Once the secret is stored associated to the user session, he might not
be able to login anymore.

### 2. Checking TOTP codes:

Once the user enters a valid email and password and has enabled TOTP,
we will request a extra authentication step: a One Time Password code.

The mobile device of the user, previously configured with the secret
key will generate a TOTP code.

To check the code back in the server, you simply need to:

    require_once 'path/to/GAP_Authenticator.php';

    $gap = new GAP_Authenticator();
    $validCode = $gap->checkTOTPCode($code, $key);

The codes change every 30 seconds, but the authentication allows to use
the adjacent codes, to allow some clock skew between client and
server (not to mention the time elapsed since user reads and inputs the
code and finally arrives to the server).

### 3. Further notes & security measures

If you want to make your site really secure, please also consider some
of this recommendations:

* For security, it's recommended to store the TOTP secret key crypted
with the password of the user. Reseting TOTP codes is a really cumbersome
for users.
* You should also try to avoid storing unsalted hashes of the passwords
in your database. Even better is to use some computational expensive
Password-Based Key Derivation like: http://en.wikipedia.org/wiki/PBKDF2
* You should log login attempts with invalid codes and limit the number
of attempts from a given IP. The codes are small numeric to make it's
usage simple, so brute force can be an option for attackers.

Contributing
------------

Any contribution is welcomed. I have plans to add more advanced TOTPs
in the future.

Also since this is just a library for TOTP codes, and auth systems are
deeply integrated into applications, this project is still far from
being a drop in replacement in applications. Any implementations for
some widely used frameworks like CakePHP, Zend, Wordpess... could
boost adoption into mainstream.

Testing
-------

This lib was developed carefully for easy testability and good coverage.

To run the tests:

    $ phpunit tests/

There are also some testing examples in `utils/` folder:

    $ ./util/gen_secret_key

    * Random secret key:
        4QF5 SRS6 KQXR HFGU

    $ ./util/gen_totp_code '4QF5 SRS6 KQXR HFGU'

    * TOTP code:
        535815
