<?php

/**
 * PHP Social Stream 2.9.2
 * Copyright 2015-2019 Axent Media (support@axentmedia.com)
 */

// Load the template class
require_once(INSTALLER_PATH . '/Installer_Template.php');
require_once(ROOT_PATH . '/library/SimpleCache.php');

/**
 * Class installer
 *
 */
class Installer {
    /**
     * View object
     *
     * @var object
     */
    protected $view;

    /**
     * Language array
     *
     * @var array
     */
    protected $lang = array();

    protected $default_lang = 'english';

    /**
     * Data options property
     *
     * @var array
     */
    protected $setoptions = array();

    /**
     * Constructor
     *
     */
    public function __construct() {

		if(isset($_POST["ClearCache"])){
			$this->clear_cache($_POST["ClearCache"]);
		}
    	
        # Do we have a cookie
        if ( isset($_COOKIE['lang']) && $_COOKIE['lang'] != '' ) {
            $this->default_lang = $_COOKIE['lang'];
        }

        # Change language
        if ( isset($_POST['lang']) && $_POST['lang'] != '' && $this->default_lang != $_POST['lang'] ) {
            $path = INSTALLER_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $_POST['lang'] . '.php';
            if ( file_exists($path) ) {
                $this->default_lang = $_POST['lang'];
                @setcookie('lang', $this->default_lang, time() + 60 * 60 * 24);
                $_POST['lang'] = 0;
                $this->nextStep('index');
            }
        }

        // Load the language file
        require_once( INSTALLER_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->default_lang . '.php' );

        // Load setup data file
        $data_file = file_exists('data.php') ? 'data.php' : 'data-sample.php';
        require($data_file);

        // Getting all API options
        $dataoptions = json_decode(ACCOUNTS, true);
        if ( is_array($dataoptions) )
            $this->setoptions = $dataoptions;

        // Build Language Select
        $lang_options = '';
        foreach ($this->buildLangSelect() as $lng)
        {
            $lang_options .= "<option value='{$lng}'>".ucfirst($lng)."</option>";
        }
        $lang['lang_options'] = $lang_options;
        $lang['message'] = $this->showMessage();

        // Assign lang vars
        $this->lang = $lang;

        # Load the template class
        $this->view = new Installer_Template($this->lang);

        // Allwed steps
        $allwed_steps = array(
            'index' => 'indexAction',
            'credentials' => 'credentialsAction',
            'general' => 'generalAction',
            'api' => 'apiAction',
            'login' => 'loginAction',
            'logout' => 'logoutAction'
        );
        
        // Set step
        $step = @$_REQUEST['step'];
        if ( ! in_array($step, array_keys($allwed_steps) ) ) {
            $step = 'index';
        }

        # Display the right step
        $this->{$allwed_steps[$step]}($_POST);
        
    }

    private function clear_cache() {
		$path = "/cache/";
		$cachefiles = @glob(ROOT_PATH . $path ."*");
		$cachefiles = array_values( preg_grep( '/^((?!index.html).)*$/', $cachefiles ) );
		if ( ! empty($cachefiles) ) {
			foreach($cachefiles as $file) {
				@unlink($file);
			}
		}
		$this->flashMessage('Cache history deleted successful');
	}
    
    /**
     * Display everything
     *
     */
    public function display()
    {
        $this->view->display();
    }

    private function login() {
        $_SESSION['login'] = time() + LOGIN_TIMEOUT;
    }

    private function login_check() {
        if ( isset($_SESSION['login']) )
            if ( $_SESSION['login'] > time() )
                return true;

        return false;
    }

    private function credentials_check() {
        return ! empty(USERNAME) && ! empty(PASSWORD) ? true : false;
    }

    /**
     * Show welcome page
     *
     */
    public function indexAction()
    {
        if ( $this->credentials_check() ) {
            $this->nextStep('login');
        } else {
            $this->view->render('credentials');
        }
    }

    public function credentialsAction()
    {
        if ( $this->credentials_check() ) {
            if ( ! $this->login_check() )
                $this->nextStep('login');
        }

        if ( isset($_POST['save']) && ! empty($_POST['username']) && ! empty($_POST['password']) )
        {
            $this->setupWrite('data',
                array(
                    'USERNAME' => $_POST['username'],
                    'PASSWORD' => md5( trim($_POST['password']) )
                )
            );

            $this->login();

            $this->flashMessage('Login credentials setting was successful.');
            $this->nextStep('general');
        }
        
        $this->view->render('credentials');
    }

    public function loginAction()
    {
        if ( $this->login_check() )
            $this->nextStep('general');

        if ( isset($_POST['login'])
            && ! empty($_POST['username']) && ! empty($_POST['password']) ) {

            $PASSWORD = PASSWORD;
            // r2.8.2 < compatibility fix
            if ( ! preg_match('/^[a-f0-9]{32}$/', PASSWORD) ) {
                $PASSWORD = md5(PASSWORD);
                $this->setupWrite('data',
                    array(
                        'PASSWORD' => $PASSWORD
                    )
                );
            }
            // END - r2.8.2 < compatibility fix

            if ($_POST['username'] == USERNAME && md5( trim($_POST['password']) ) == $PASSWORD) {
                $this->login();

                $this->flashMessage('Login was successful.');
                $this->nextStep('general');
            } else {
                $this->view->error($this->lang['wrong_user_pass']);
            }
        }

        $this->view->render('login');
    }

    public function logoutAction() {
        unset($_SESSION["login"]);
        $this->nextStep('index');
    }

	public function localeFix($locales) {
		$dir = ROOT_PATH . "/language";
		
		if ( is_dir($dir) ) {
			$files = scandir($dir);
			if ($files) {
				foreach ($locales as $k => $v) {
					$fileName = "social-stream-".$k.".php";
					if ( ! in_array($fileName, $files) ) {
						unset($locales[$k]);
					}
				}
			}
		}
		
		return $locales;
    }
    
    public function generalAction() {
        if ( ! $this->login_check() )
            $this->nextStep('login');

        // Save data
        if ( isset($_POST['save']) )
            $this->generalWrite($_POST);

        $locale = array(
            'en' => 'English (United States)',
            'ar' => 'العربية',
            'az' => 'Azərbaycan dili',
            'bg_BG' => 'Български',
            'bs_BA' => 'Bosanski',
            'ca' => 'Català',
            'cy' => 'Cymraeg',
            'da_DK' => 'Dansk',
            'de_CH' => 'Deutsch (Schweiz)',
            'de_DE' => 'Deutsch',
            'el' => 'Ελληνικά',
            'en_CA' => 'English (Canada)',
            'en_AU' => 'English (Australia)',
            'en_GB' => 'English (UK)',
            'eo' => 'Esperanto',
            'es_PE' => 'Español de Perú',
            'es_ES' => 'Español',
            'es_MX' => 'Español de México',
            'es_CL' => 'Español de Chile',
            'eu' => 'Euskara',
            'fa_IR' => 'فارسی',
            'fi' => 'Suomi',
            'fr_FR' => 'Français',
            'gd' => 'Gàidhlig',
            'gl_ES' => 'Galego',
            'haz' => 'هزاره گی',
            'he_IL' => 'עִבְרִית',
            'hr' => 'Hrvatski',
            'hu_HU' => 'Magyar',
            'id_ID' => 'Bahasa Indonesia',
            'is_IS' => 'Íslenska',
            'it_IT' => 'Italiano',
            'ja' => '日本語',
            'ko_KR' => '한국어',
            'lt_LT' => 'Lietuvių kalba',
            'my_MM' => 'ဗမာစာ',
            'nb_NO' => 'Norsk bokmål',
            'nl_NL' => 'Nederlands',
            'nn_NO' => 'Norsk nynorsk',
            'oci' => 'Occitan',
            'pl_PL' => 'Polski',
            'ps' => 'پښتو',
            'pt_PT' => 'Português',
            'pt_BR' => 'Português do Brasil',
            'ro_RO' => 'Română',
            'ru_RU' => 'Русский',
            'sk_SK' => 'Slovenčina',
            'sl_SI' => 'Slovenščina',
            'sq' => 'Shqip',
            'sr_RS' => 'Српски језик',
            'sv_SE' => 'Svenska',
            'th' => 'ไทย',
            'tr_TR' => 'Türkçe',
            'ug_CN' => 'Uyƣurqə',
            'uk' => 'Українська',
            'zh_CN' => '简体中文',
            'zh_TW' => '繁體中文'
        );

		$locales = $this->localeFix($locale);
        
        $locOptions = '<select name="locale">';
        foreach ($locales as $locKey => $locVal) {
            $locSel = ($locKey == SB_LOCALE) ? ' selected="selected"' : '';
            $locOptions .= '<option value="'.$locKey.'"'.$locSel.'>'.$locVal.'</option>';
        }
        $locOptions .= '</select>';

        $curlOptions = $this->htmlRadio('curl',
            array(
                0 => $this->lang['no'],
                1 => $this->lang['yes']
            ),
            SB_CURL
        );

        $this->view->vars = array(
            'SB_PATH' =>  'http' . ( $this->isSSL() ? 's' : '') . '://'. $_SERVER['SERVER_NAME'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "setup/")),
            'SB_TIMEZONE' => SB_TIMEZONE,
            'SB_LOCALE' => $locOptions,
            'SB_DATE_FORMAT' => SB_DATE_FORMAT,
            'SB_TIME_FORMAT' => SB_TIME_FORMAT,
            'SB_NONCE_KEY' => SB_NONCE_KEY,
            'SB_CURL' => $curlOptions,
            'SB_API_TIMEOUT' => SB_API_TIMEOUT
        );
        
        $this->view->render('general');
    }

    private function apiGet($url) {
        $cache = new SimpleCache;
        $cache->debug_log = false;
        $content = $cache->do_curl($url, true);

        return $content;
    }

    public function apiAction() {
        if ( ! $this->login_check() )
            $this->nextStep('login');
        
        // Save data
        if ( isset($_POST['save']) ) {
            $this->apiWrite($_POST);
        }

        // Encoded setup url
        $encoded_base_url = urlencode(API_PAGE_URL);

        // Getting API options
        $instagram_accounts = @$this->setoptions['instagram_accounts'];
        $facebook_accounts = @$this->setoptions['facebook_accounts'];

        // Update Instagram accounts
        if ( @$_REQUEST['api'] == 'instagram' ) {
            if ( ! empty( $_REQUEST['access_token'] ) ) {
				$access_token = $_REQUEST['access_token'];
				$feed_url = 'https://graph.instagram.com/me/?fields=account_type,id,media_count,username&access_token=' . $access_token;
                if ( $userDataString = $this->apiGet($feed_url) ) {
                    $userData = json_decode($userDataString);
                    if ( ! empty($userData->id) ) {
                        $userAccounts = array(
                            'id' => $userData->id,
                            'username' => @$userData->username,
                            'access_token' => $access_token
                        );
                        $newAccount = array($userData->id => $userAccounts);

                        // Append if account exists
                        if ( is_array($instagram_accounts) )
                            $instagramAccounts = $newAccount + $instagram_accounts;
                        else
                            $instagramAccounts = $newAccount;
                        
                        // Update accounts
                        $this->setoptions['instagram_accounts'] = $instagramAccounts;
                        $this->setupWrite('data', array('ACCOUNTS' => $this->setoptions) );
                    } else {
                        $this->view->error('Instagram authentication error: ' . @$userData->meta->error_message);
                    }
                }
            } elseif ( ! empty( $_REQUEST['remove_token'] ) ) {
                $remove_token = $_REQUEST['remove_token'];
                unset($this->setoptions['instagram_accounts'][$remove_token]);
                $this->setupWrite('data', array('ACCOUNTS' => $this->setoptions) );
            }
        }
        // Update Facebook accounts
        elseif ( @$_REQUEST['api'] == 'facebook' ) {
            if ( ! empty( $_REQUEST['access_token'] ) ) {
                // Get Facebook user info
                $access_token = $_REQUEST['access_token'];
                $feed_url = 'https://graph.facebook.com/v4.0/me?fields=id,name,email,picture&access_token=' . $access_token;
                if ( $userDataString = $this->apiGet($feed_url) ) {
                    $userData = json_decode($userDataString);
                    if ( ! empty($userData->id) ) {
                        $userAccount = array(
                            'id' => $userData->id,
                            'name' => @$userData->name,
                            'email' => @$userData->email,
                            'picture' => @$userData->picture->data->url,
                            'access_token' => $access_token
                        );
                        $newAccount = array($userData->id => $userAccount);

                        // Get Facebook user's managing pages
                        $feed_url = 'https://graph.facebook.com/v4.0/' . $userData->id . '/accounts?limit=100&fields=id,name,username,picture,access_token&access_token=' . $access_token;
                        if ( $pagesDataString = $this->apiGet($feed_url) ) {
                            $pagesData = json_decode($pagesDataString);
                            if ( ! empty($pagesData->data) ) {
                                foreach ($pagesData->data as $pageData) {
                                    $pageAccount = array(
                                        'id' => $pageData->id,
                                        'name' => @$pageData->name,
                                        'username' => @$pageData->username,
                                        'picture' => @$pageData->picture->data->url,
                                        'access_token' => $pageData->access_token
                                    );
                                    $newAccount[$userData->id]['pages'][$pageData->id] = $pageAccount;
                                }
                            } else {
                                $this->view->error('Facebook authentication error: ' . @$pagesData->error->message);
                            }
                        }

                        // Get Facebook Groups
                        $feed_url = 'https://graph.facebook.com/v7.0/' . $userData->id . '/groups?limit=100&fields=id,name,username,picture,access_token&access_token=' . $access_token;
						if ( $groupsDataString = $this->apiGet($feed_url) ) {
							$groupsData = json_decode($groupsDataString);
							if ( ! empty($groupsData->data) ) {
								foreach ($groupsData->data as $groupData) {
									$groupAccount = array(
										'id' => $groupData->id,
										'name' => @$groupData->name,
										'username' => @$groupData->username,
										'picture' => @$groupData->picture->data->url,
										'access_token' => @$access_token
									);
									$newAccount[$userData->id]['groups'][$groupData->id] = $groupAccount;
								}
							} 
						}

                        // Append if account exists
                        if ( is_array($facebook_accounts) )
                            $facebookAccounts = $newAccount + $facebook_accounts;
                        else
                            $facebookAccounts = $newAccount;
                        
                        // Update accounts
                        $this->setoptions['facebook_accounts'] = $facebookAccounts;
                        $this->setupWrite('data', array('ACCOUNTS' => $this->setoptions) );
                    } else {
                        $this->view->error('Facebook authentication error: ' . @$userData->error->message);
                    }
                } else {
                    $this->view->error('Could not connect to Facebook.');
                }
            } elseif ( ! empty( $_REQUEST['remove_token'] ) ) {
                $remove_token = $_REQUEST['remove_token'];
                unset($this->setoptions['facebook_accounts'][$remove_token]);
                $this->setupWrite('data', array('ACCOUNTS' => $this->setoptions) );
            }
        }

        // Getting API updated options
        $instagram_accounts = @$this->setoptions['instagram_accounts'];
        $facebook_accounts = @$this->setoptions['facebook_accounts'];

        // List Facebook connected accounts
        $faccounts = '';
        if ( ! empty($facebook_accounts) ) {
            foreach ($facebook_accounts as $faccount) {
                $fpages = '';
                if ( ! empty($faccount['pages']) ) {
                    foreach ($faccount['pages'] as $fpage) {
                        $fpages .= '
                        <img src="' . @$fpage['picture'] . '" alt="">
                        <p><u>ID:</u> ' . $fpage['id'] . '</p>
                        <p><u>Name:</u> ' . @$fpage['name'] . '</p>
                        <p><u>Username:</u> ' . @$fpage['username'] . '</p>
                        <p><u>Access token:</u> <input type="text" value="' . @$fpage['access_token'] . '" size="50"></p>';
                    }
                }

                $fgroups = '';
                if( ! empty($faccount['groups']) ) {
					foreach ($faccount['groups'] as $fgroup) {
						$fgroups .= '
                        <img src="' . @$fgroup['picture'] . '" alt="">
                        <p><u>ID:</u> ' . $fgroup['id'] . '</p>
                        <p><u>Name:</u> ' . @$fgroup['name'] . '</p>
                        <p><u>Username:</u> ' . @$fgroup['username'] . '</p>
                        <p><u>Access token:</u> <input type="text" value="' . @$fgroup['access_token'] . '" size="50"></p>';
					}
				}
                $faccounts .= '
                <div class="accounts-wrapper">
                    <img src="' . @$faccount['picture'] . '" alt="">
                    <p><u>ID:</u> ' . $faccount['id'] . '</p>
                    <p><u>Name:</u> ' . @$faccount['name'] . '</p>
                    <p><u>Email:</u> ' . @$faccount['email'] . '</p>
                    <p><u>Access token:</u> <input type="text" value="' . @$faccount['access_token'] . '" size="50"></p>
                    <a type="button" href="' . API_PAGE_URL . '&api=facebook&remove_token=' . @$faccount['id'] . '#sections-section_facebook" align="left">× Delete</a>
                    <div class="pages-wrapper">
                        <p><u>Connected Pages:</u></p>
                        ' . $fpages . '
                    </div>
                    <div class="pages-wrapper">
                        <p><u><strong>Connected Groups:</strong></u></p>
                        ' . $fgroups . '
                    </div>
                </div>';
            }
        }

        // List Instagram connected accounts
        $iaccounts = '';
        if ( ! empty($instagram_accounts) ) {
            foreach ($instagram_accounts as $iaccount) {
                $iaccounts .= '
                <div class="accounts-wrapper">
                    <p><u>ID:</u> ' . $iaccount['id'] . '</p>
                    <p><u>Username:</u> ' . @$iaccount['username'] . '</p>
                    <p><u>Access token:</u> <input type="text" value="' . @$iaccount['access_token'] . '" size="50"></p>
                    <a href="' . API_PAGE_URL . '&api=instagram&remove_token=' . @$iaccount['id'] . '#sections-section_instagram" align="left">× Delete</a>
                </div>';
            }
        }

        $this->view->vars = array(
            'doc_url' => DOC_URL,

            'facebook_app_id' => @$this->setoptions['facebook_api_key'],
            'facebook_app_secret' => @$this->setoptions['facebook_api_secret'],
            'facebook_accounts' => $faccounts,
            
			'instagram_client_id' => @$this->setoptions['instagram_client_id'],
			'instagram_client_secret' => @$this->setoptions['instagram_client_secret'],
            'instagram_accounts' => $iaccounts,
            'instagram_username' => @$GLOBALS['api']['instagram']['instagram_logins'][0]['username'],
            'instagram_password' => @$GLOBALS['api']['instagram']['instagram_logins'][0]['password'],

            // 'linkedin_api_key' => @$this->setoptions['linkedin_api_key'],
            // 'linkedin_api_secret' => @$this->setoptions['linkedin_api_secret'],

            'twitter_api_key' => @$GLOBALS['api']['twitter']['twitter_api_key'],
            'twitter_api_secret' => @$GLOBALS['api']['twitter']['twitter_api_secret'],
            'twitter_access_token' => @$GLOBALS['api']['twitter']['twitter_access_token'],
            'twitter_access_token_secret' => @$GLOBALS['api']['twitter']['twitter_access_token_secret'],

            'google_api_key' => @$GLOBALS['api']['google']['google_api_key'],
            'flickr_api_key' => @$GLOBALS['api']['flickr']['flickr_api_key'],
            'tumblr_api_key' => @$GLOBALS['api']['tumblr']['tumblr_api_key'],
            'soundcloud_client_id' => @$GLOBALS['api']['soundcloud']['soundcloud_client_id'],
            // 'linkedin_access_token' => @$GLOBALS['api']['linkedin']['linkedin_access_token'],
            'vimeo_access_token' => @$GLOBALS['api']['vimeo']['vimeo_access_token'],
            'vk_service_token' => @$GLOBALS['api']['vk']['vk_service_token'],

            // Set get token urls
            'twitter_token_url' => TOKEN_SERVER.'twitter-access-token/?return_url='.$encoded_base_url,
            'facebook_token_url' => TOKEN_SERVER.'facebook-access-token/?return_url='.$encoded_base_url,
            'instagram_token_url' => TOKEN_SERVER.'instagram-access-token/?action=get_instagram_token&return_url='.$encoded_base_url,
            // 'linkedin_token_url' => TOKEN_SERVER.'linkedin-access-token/?return_url='.$encoded_base_url
        );

        foreach ( array('facebook', 'twitter', 'linkedin', 'instagram') as $network ) {
            $this->view->vars[$network.'_app'] = $this->htmlRadio($network.'_app',
                array(
                    'yes' => $this->lang['our_app'],
                    'no' => $this->lang['your_app']
                ),
                @$this->setoptions[$network.'_app'] ? $this->setoptions[$network.'_app'] : 'yes'
            );
        }

        // Display next step
        $this->view->render('api');
    }

    /**
     * Write to the configuration File
     *
     */
    private function apiWrite(array $options) {
        // Save setup data file
		$this->setoptions['facebook_app'] = $options['facebook_app'];
		$this->setoptions['facebook_app_id'] = $options['facebook_api_key'];
		$this->setoptions['facebook_app_secret'] = $options['facebook_api_secret'];

		$this->setoptions['twitter_app'] = $options['twitter_app'];
		$this->setoptions['twitter_api_key'] = $options['twitter_api_key'];
		$this->setoptions['twitter_api_secret'] = $options['twitter_api_secret'];
		
		$this->setoptions['instagram_app'] = $options['instagram_app'];
		$this->setoptions['instagram_client_id'] = $options['instagram_api_key'];
		$this->setoptions['instagram_client_secret'] = $options['instagram_api_secret'];

        // $this->setoptions['linkedin_api_key'] = $options['linkedin_api_key'];
		// $this->setoptions['linkedin_api_secret'] = $options['linkedin_api_secret'];
		
        $this->setupWrite('data', array('ACCOUNTS' => $this->setoptions) );

        // Save config file
        $api['google'] = "
    'google' => array(
        'google_api_key' => '{$options['google_api_key']}'
    )";
        $api['flickr'] = "
    'flickr' => array(
        'flickr_api_key' => '{$options['flickr_api_key']}'
    )";
        $api['tumblr'] = "
    'tumblr' => array(
        'tumblr_api_key' => '{$options['tumblr_api_key']}'
    )";
        $api['soundcloud'] = "
    'soundcloud' => array(
        'soundcloud_client_id' => '{$options['soundcloud_client_id']}'
	)";
	/*
        $api['linkedin'] = "
    'linkedin' => array(
        'linkedin_access_token' => '{$options['linkedin_access_token']}'
    )";*/
        $api['vimeo'] = "
    'vimeo' => array(
        'vimeo_access_token' => '{$options['vimeo_access_token']}'
    )";
        $api['vk'] = "
    'vk' => array(
        'vk_service_token' => '{$options['vk_service_token']}'
    )";
        $api['twitter'] = "
    'twitter' => array(
        'twitter_api_key' => '{$options['twitter_api_key']}',
        'twitter_api_secret' => '{$options['twitter_api_secret']}',
        'twitter_access_token' => '{$options['twitter_access_token']}',
        'twitter_access_token_secret' => '{$options['twitter_access_token_secret']}'
    )";
        
        $fbaccounts = $fbpages = $fbgroups = array();
        if ( ! empty($this->setoptions['facebook_accounts']) ) {
            foreach ($this->setoptions['facebook_accounts'] as $faccount) {
                if ( isset($faccount['pages']) ) {
                    foreach ($faccount['pages'] as $fpage) {
                        $fbpages[] = "
                    '{$fpage['id']}' => array(
                        'access_token' => '{$fpage['access_token']}',
                        'username' => '{$fpage['username']}'
                    )";
                    }
                }
                if ( isset($faccount['groups']) ) {
                    foreach ($faccount['groups'] as $fgroup) {
                        $fbgroups[] = "
                    '{$fgroup['id']}' => array(
                        'access_token' => '{$fgroup['access_token']}',
                        'username' => '{$fgroup['username']}'
                    )";
                    }
                }
                $fbaccounts[] = "
            '{$faccount['id']}' => array(
                'access_token' => '{$faccount['access_token']}',
                'pages' => array(".implode(',', $fbpages)."),
                'groups' => array(".implode(',', $fbgroups).")
            )";
            }
        }
        $api['facebook'] = "
    'facebook' => array(
        'facebook_accounts' => array(".implode(',', $fbaccounts)."
        )
    )";
        
        $igaccounts = array();
        if ( ! empty($this->setoptions['instagram_accounts']) ) {
            foreach ($this->setoptions['instagram_accounts'] as $iaccount) {
                $igaccounts[] = "
            '{$iaccount['id']}' => array(
                'access_token' => '{$iaccount['access_token']}',
                'username' => '{$iaccount['username']}'
            )";
            }
        }

        $iglogins = array();
        if ( ! empty($options['instagram_username']) ) {
            $iglogins[] = "
            array(
                'username' => '{$options['instagram_username']}',
                'password' => '{$options['instagram_password']}'
            )";
        }

        $api['instagram'] = "
    'instagram' => array(
        'instagram_accounts' => array(".implode(',', $igaccounts)."
        ),
        'instagram_logins' => array(".implode(',', $iglogins)."
        )
    )";

        $apiValue = 'array('.implode(',', $api)."\n)";
        $this->setupReplace('../config', 'api', $apiValue);

        sleep(3);
        $this->flashMessage('API settings was successful.');
        $this->nextStep('api');
    }

    /**
     * Write to the configuration File
     *
     */
    private function generalWrite(array $options) {
        $this->setupWrite('../config',
            array(
                'SB_PATH' => ! empty($options['path']) ? $options['path'] : SB_PATH,
                'SB_TIMEZONE' => ! empty($options['timezone']) ? $options['timezone'] : SB_TIMEZONE,
                'SB_LOCALE' => ! empty($options['locale']) ? $options['locale'] : SB_LOCALE,
                'SB_DATE_FORMAT' => ! empty($options['date_format']) ? $options['date_format'] : SB_DATE_FORMAT,
                'SB_TIME_FORMAT' => ! empty($options['time_format']) ? $options['time_format'] : SB_TIME_FORMAT,
                'SB_NONCE_KEY' => ! empty($options['nonce_key']) ? $options['nonce_key'] : SB_NONCE_KEY,
                'SB_CURL' => isset($options['curl']) ? $options['curl'] : SB_CURL,
                'SB_API_TIMEOUT' => ! empty($options['timeout']) ? $options['timeout'] : SB_API_TIMEOUT,
            )
        );

        /*
        'SB_PROXY' => array(
            'proxy' => $GLOBALS['SB_PROXY']['proxy'],
            'proxy_port' => $GLOBALS['SB_PROXY']['proxy_port'],
            'proxy_userpass' => $GLOBALS['SB_PROXY']['proxy_userpass']
        ),
        */

        $this->flashMessage('General settings was successful.');
        $this->nextStep('api');
    }

    private function setupWrite($file_path, array $vars)
    {
        $file_name = $file_path.'.php';
        $setup_file = file_exists( $file_name ) ? $file_name : $file_path.'-sample.php';
        $file = file( $setup_file );
        if ( ! empty( $file ) ) {
            foreach ( $vars as $key => $val ) {
                if ( ! is_string( $val ) && ! is_numeric( $val ) ) {
                    $val = json_encode( $val );
                    $val = str_replace( "'", "\'", $val );
                }

                foreach ( $file as &$line ) {
                    if ( false !== strpos( $line, $key ) ) {
                        $line = "define( '{$key}', '{$val}' );\n";
                        break;
                    }
                }
            }
            file_put_contents($file_name, $file);
        }
    }

    private function setupReplace($file_path, $var, $val) {
        $file_name = $file_path.'.php';
        $setup_file = file_exists( $file_name ) ? $file_name : $file_path.'-sample.php';
        $content = file_get_contents( $setup_file );
        if ( ! empty($content) ) {
            $content = preg_replace('/(?<='.$var.'\W]).*?(?=;)/si', ' = '.$val, $content);
            file_put_contents( $file_name, $content );
        }
    }

    private function htmlRadio($name, $options, $checked)
    {
        $checkOptions = '';
        foreach ($options as $optKey => $optVal) {
            $optSel = ($optKey == $checked) ? ' checked="checked"' : '';
            $checkOptions .= '<label><input type="radio" name="'.$name.'" value="'.$optKey.'"'.$optSel.' />'.$optVal.'</label>';
        }

        return $checkOptions;
    }

    /**
     * Redirect to the next step
     *
     * @param string $step
     */
    public function nextStep($step)
    {
        $url = HOME_URL . '?step='.$step;
        if ( ! headers_sent() )
        {
            header('Location: '. $url);
            exit;
        }

        print "<html><body><meta http-equiv='refresh' content='1;url={$url}'></body></html>";
        exit;
    }

    /**
     * Build the language select box
     *
     */
    public function buildLangSelect()
    {
        $path = INSTALLER_PATH . DIRECTORY_SEPARATOR . 'lang';
        $dirs = scandir($path);
        $files = array();
        foreach ($dirs as $file)
        {
            if ($file == '.' || $file == '..' || $file == 'index.html')
            {
                continue;
            }
            elseif (is_dir($path.'/'.$file))
            {
                continue;
            }
            else
            {
                $files[] = str_replace('.php', '', $file);
            }
        }

        return $files;
    }

    // Determine if SSL is used
    function isSSL()
    {
    	if ( isset($_SERVER['HTTPS']) )
        {
    		if ( 'on' == strtolower($_SERVER['HTTPS']) )
    			return true;
    		if ( '1' == $_SERVER['HTTPS'] )
    			return true;
    	} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
    		return true;
    	}
    	return false;
    }

    private function flashMessage($message) {
        $_SESSION["message"] = $message;
    }

    public function showMessage() {
        if ( isset($_SESSION["message"]) ) {
            $message = $_SESSION["message"];
            unset($_SESSION["message"]);
            return '<p class="success">'.$message.'</p>';
        }
    }
}