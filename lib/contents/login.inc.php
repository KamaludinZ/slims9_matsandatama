<?php

/**
 *
 * Librarian login page
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com), Hendro Wicaksono (hendrowicaksono@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

// be sure that this file not accessed directly
if (!defined('INDEX_AUTH')) {
    die("can not access this file directly");
} elseif (INDEX_AUTH != 1) {
    die("can not access this file directly");
}

#use SLiMS\AdvancedLogging;
use SLiMS\AlLibrarian;
use SLiMS\Captcha\Factory as Captcha;
use Volnix\CSRF\CSRF;
use SLiMS\Auth\Validator;
use SLiMS\Http\Cookie;

if ($sysconf['baseurl'] != '') {
    $_host = $sysconf['baseurl'];
    header("Access-Control-Allow-Origin: $_host", FALSE);
}

/*
if (defined('LIGHTWEIGHT_MODE')) {
    header('Location: index.php');
}
*/

// required file
require SIMBIO . 'simbio_DB/simbio_dbop.inc.php';

// create logon class instance
$logon = Validator::use(
    config(
    'auth.methods.' . config('auth.sections.user'),
    \SLiMS\Auth\Methods\Native::class
    )
);

$logon->getHook();

// https connection (if enabled)
if ($sysconf['https_enable']) {
    simbio_security::doCheckHttps($sysconf['https_port']);
}

// check if session browser cookie already exists
if ($logon->isUserLoggedIn()) {
    redirect()->to('admin/index.php');
}

// unified login: member (pengunjung/anggota) already logged in? go to member area
if (utility::isMemberLogin()) {
    redirect()->toPath('member');
}

// keep destination (used by member area, e.g. after login to post comment)
$_login_destination = (isset($_GET['destination']) && \SLiMS\Url::isValid($_GET['destination']) && \SLiMS\Url::isSelf($_GET['destination'])) ? $_GET['destination'] : '';

if (isset($_GET['wrongpass'])) {
    $message = Cookie::get('login_error');
    redirect()->withMessage('wrong_password', empty($message) ? __('Wrong Username or Password. ACCESS DENIED') : $message)->toPath('login');
}

// Captcha initialize
$captcha = Captcha::section('librarian');

// start the output buffering for main content
ob_start();

// if there is login action
if (isset($_POST['logMeIn'])) {
    if (!CSRF::validate($_POST)) {
        session_unset();
        redirect()->withMessage('csrf_failed', __('Invalid login form!'))->back();
    }

    # <!-- Captcha form processing - start -->
    if ($captcha->isSectionActive() && $captcha->isValid() === false) {
        // set error message
        $message = isDev() ? $captcha->getError() : __('Wrong Captcha Code entered, Please write the right code!'); 
        // What happens when the CAPTCHA was entered incorrectly
        session_unset();
        redirect()->withMessage('captchaInvalid', $message)->back();
    }

    $username = strip_tags($_POST['userName']);
    $password = strip_tags($_POST['passWord']);

    // Empty username or password
    if (empty($username) or empty($password)) redirect()->withMessage('empty_field', __('Please supply valid username and password'))->back();

    if ($logon->process('admin')) {
        // remember me
        if (isset($_POST['remember']) && $_POST['remember'] == 1) {
            $_SESSION['remember_me'] = true;
            $logon->rememberMe(config('remember_me_timeout', 30));
        }

        # <!-- Captcha form processing - end -->
        // destroy previous session set in OPAC
        simbio_security::destroySessionCookie(null, MEMBER_COOKIES_NAME, SWB, false);
        require SB . 'admin/default/session.inc.php';
        // regenerate session ID to prevent session hijacking
        session_regenerate_id(true);

        // set cookie admin flag
        Cookie::withPath(SWB)
                ->withExpires(time() + 14400)
                ->withHttponly()
                ->withSamesite('Lax')
                ->set('admin_logged_in', TRUE);

        // Set admin language cookie if available
        if ($defaultLang = $logon->getData('template')['default_lang'] ?? null) {
            Cookie::withPath(SWB . 'admin')
                ->withExpires(time() + 14400)
                ->set('admin_lang', $defaultLang);
        }

        // write log
        writeLog('staff', $username, 'Login', 'Login success for user ' . $username . ' from address ' . ip());

        $logon->getMethodInstance()->generateSession();

        # ADV LOG SYSTEM - STILL EXPERIMENTAL
        $log = new AlLibrarian('1001', array("username" => $username, "realname" => $logon->getData('real_name')));

        if ($sysconf['login_message']) utility::jsAlert(__('Welcome to Library Automation, ') . $logon->getData('real_name'));

        redirect('admin/index.php');
    } else {
        // ---------------------------------------------------------------
        // Unified login: not a librarian account, try as library member
        // (pengunjung/anggota) using the same username (Member ID) & password
        // ---------------------------------------------------------------
        $_POST['memberID'] = $username;
        $_POST['memberPassWord'] = $password;
        $memberLogon = Validator::use(
            config(
            'auth.methods.' . config('auth.sections.member'),
            \SLiMS\Auth\Methods\Native::class
            )
        );
        $memberLogon->getHook();

        if ($memberLogon->process('member')) {
            // regenerate session ID to prevent session hijacking
            session_regenerate_id(true);
            // write log
            writeLog('member', $username, 'Login', sprintf(__('Login success for member %s from address %s'), $username, ip()));
            if (!empty($_login_destination)) redirect($_login_destination);
            redirect()->toPath('member');
            exit();
        }
        writeLog('member', $username, 'Login', sprintf(__('Login FAILED for member %s from address %s'), $username, ip()));

        // write log
        writeLog('staff', $username, 'Login', 'Login FAILED for user ' . $username . ' from address ' . ip());

        // message
        simbio_security::destroySessionCookie($logon->getError(), COOKIES_NAME, SWB . 'admin', false);
        Cookie::withPath(SWB)
                ->withExpires(time() + 60)
                ->withHttponly()
                ->withSamesite('Lax')
                ->set('login_error', __('Wrong Username or Password. ACCESS DENIED'));

        redirect('?p=login&wrongpass=true');
        exit();
    }
}

// uname
$_uname = (isset($_COOKIE['uname'])) ? trim($_COOKIE['uname']) : '';

?>
<div id="loginForm">
    <noscript>
        <div style="font-weight: bold; color: #FF0000;"><?php echo __('Your browser does not support Javascript or Javascript is disabled. Application won\'t run without Javascript!'); ?><div>
    </noscript>
    <form action="index.php?p=<?php echo $path; ?><?= $_login_destination ? '&destination=' . urlencode($_login_destination) : '' ?>" method="post">
        <?php
        if (isset($_GET['update']) && !empty($_GET['update'])) { ?>
            <?php if (isset($_COOKIE['token']) && $_GET['update'] === $_COOKIE['token']) { ?>
                <div class="alert alert-danger"><?php echo str_replace('{username}', $_uname, __('Hi {username}, please update your password!')) ?></div>
                <div class="heading1"><?php echo __('Current Password'); ?></div>
                <div class="login_input"><input type="password" name="currentPasswd" id="userName" class="login_input" /></div>
                <div class="heading1"><?php echo __('New Password'); ?></div>
                <div class="login_input"><input type="password" name="newPasswd" class="login_input" /></div>
                <div class="heading1"><?php echo __('Confirm New Password'); ?></div>
                <div class="login_input"><input type="password" name="newPasswd2" class="login_input" /></div>
                <!-- Captcha in form - start -->
                <?php if ($sysconf['captcha']['smc']['enable']) { ?>
                    <?php if ($sysconf['captcha']['smc']['type'] == "recaptcha") { ?>
                        <div class="captchaAdmin">
                            <?php
                            require_once LIB . $sysconf['captcha']['smc']['folder'] . '/' . $sysconf['captcha']['smc']['incfile'];
                            $publickey = $sysconf['captcha']['smc']['publickey'];
                            echo recaptcha_get_html($publickey);
                            ?>
                        </div>
                        <!-- <div><input type="text" name="captcha_code" id="captcha-form" style="width: 80%;" /></div> -->
                <?php
                    } elseif ($sysconf['captcha']['smc']['type'] == "others") {
                    }
                    #debugging
                    #echo SWB.'lib/'.$sysconf['captcha']['folder'].'/'.$sysconf['captcha']['webfile'];
                } ?>
                <!-- Captcha in form - end -->

                <div class="marginTop">
                    <input type="submit" name="updatePassword" value="<?php echo __('Update'); ?>" class="loginButton" />
                    <input type="button" value="Home" class="homeButton" onclick="javascript: location.href = 'index.php';" />
                </div>
            <?php } else { ?>
                <div class="alert alert-danger"><?= __('Not valid token!') ?></div>
                <a class="homeButton" href="index.php"><?= __('Go Home') ?></a>
            <?php } ?>
        <?php
        } else {
            if ($key = flash()->includes('wrong_password', 'csrf_failed', 'empty_field', 'captchaInvalid')) {
                flash()->danger($key);
            }
        ?>

            <div class="loginInfo small mb-2"><?php echo __('Librarian: use your username. Library member: use your Member ID.'); ?></div>
            <div class="heading1"><?php echo __('Username / Member ID'); ?></div>
            <div class="login_input"><input type="text" name="userName" id="userName" class="login_input" required /></div>
            <div class="heading1"><?php echo __('Password'); ?></div>
            <div class="login_input"><input type="password" name="passWord" class="login_input" autocomplete="off" required /></div>
            <?= \Volnix\CSRF\CSRF::getHiddenInputString() ?>
            
            <!-- Captcha in form - start -->
            <?php 
            if ($captcha->isSectionActive()) { ?>
                <div class="captchaAdmin">
                    <?= $captcha->getCaptcha() ?>
                </div>
                <?php
            }
            ?>
            <!-- Captcha in form - end -->

            <div class="marginTop">
                <div class="remember_forgot">
                    <div class="remember">
                        <?php if ($sysconf['always_user_login']) : ?>
                            <input type="checkbox" id="remember_me" name="remember" value="1">
                            <label for="remember_me"><?= __('Remember me') ?> <small>(<?= __('Librarian') ?>)</small></label>
                        <?php endif; ?>
                    </div>
                </div>
                <input type="submit" name="logMeIn" value="<?php echo __('Login'); ?>" class="loginButton" />
                <input type="button" value="Home" class="homeButton" onclick="javascript: location.href = 'index.php';" />
            </div>
        <?php } ?>
    </form>
</div>
<script type="text/javascript">
    jQuery('#userName').focus();
</script>

<?php
// main content
$main_content = ob_get_clean();

// page title
$opac->page_title = __('Librarian & Member Login') . ' | ' . $sysconf['library_name'];
$page_title = $opac->page_title;

// Load common SLiMS template variables ($language_select, $location_list, etc.)
// Some themes (e.g. "lightweight") need these variables on the login page,
// without them the login page crashed after the theme was changed.
if (!isset($language_select)) {
    $available_languages = $available_languages ?? [];
    require LIB . 'contents/common.inc.php';
}
$metadata = $metadata ?? '<meta name="robots" content="noindex, follow">';

if ($sysconf['template']['base'] == 'html') {
    // create the template object
    $template = new simbio_template_parser($sysconf['template']['dir'] . '/' . $sysconf['template']['theme'] . '/login_template.html');
    // assign content to markers
    $template->assign('<!--PAGE_TITLE-->', $page_title);
    $template->assign('<!--CSS-->', $sysconf['template']['css']);
    $template->assign('<!--MAIN_CONTENT-->', $main_content);
    // print out the template
    $template->printOut();
} else if ($sysconf['template']['base'] == 'php') {
    require_once $sysconf['template']['dir'] . '/' . $sysconf['template']['theme'] . '/login_template.inc.php';
}
exit();
