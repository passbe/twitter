<?php

    //Require admin page
    require_once(TOOLKIT . '/class.administrationpage.php');

    //Set OAuth settings from Twitter OAuth callback
    class contentExtensionTwitterOauth extends AdministrationPage
    {
        //Configuration key
        private $__name = 'twitter';

        //Session key
        private $__session = 'sym-twitter-ext-oauth-status';

        //Dependencies
        private $__dependencies = array(
            'twitter-async' => array(
                'EpiOAuth.php',
                'EpiTwitter.php',
                'EpiCurl.php'
            )
        );

        //Parse OAuth callback
        public function __construct() {
            //check Administration and required params
            if (Administration::instance()->isLoggedIn() && array_key_exists('oauth_token', $_GET) && array_key_exists('oauth_verifier', $_GET)) {
                //Load twitter lib + first need to check if the git submodule has been init
                if (is_array($this->__dependencies)) {
                    foreach ($this->__dependencies as $k => $v) {
                        //check lib directory
                        if (is_dir(EXTENSIONS."/$this->__name/lib/$k")) {
                            if (is_array($v)) {
                                //include lib files
                                foreach ($v as $f) {
                                    //this will error if these files don't exist, user should re-check documentation
                                    include_once(EXTENSIONS."/$this->__name/lib/$k/$f");
                                }
                            }
                        }
                    }
                }
                //Get required details from config
                $consumer_key = Symphony::Configuration()->get('consumer_key', $this->__name);
                $consumer_secret = Symphony::Configuration()->get('consumer_secret', $this->__name);
                //Create twitter object
                $twitter = new EpiTwitter($consumer_key, $consumer_secret);
                $twitter->useApiVersion(1.1);
                //Set token
                $twitter->setToken($_GET['oauth_token']);
                //Get Token
                $token = $twitter->getAccessToken(array('oauth_verifier' => $_GET['oauth_verifier']));
                //Check token
                if (!is_null($token->oauth_token) && !is_null($token->oauth_token_secret)) {
                    //Store token in configuration
                    Symphony::Configuration()->set('oauth_token', $token->oauth_token, $this->__name);
                    Symphony::Configuration()->set('oauth_secret', $token->oauth_token_secret, $this->__name);
                    Administration::instance()->saveConfig();
                    //Message saying successful OAuth generation
                    //Note: This cannot be achieved without session because we redirect back to the preferences page
                    $_SESSION[$this->__session] = true;
                } else {
                    //Message saying OAuth generation failed
                    $_SESSION[$this->__session] = false;
                }
                //redirect
                redirect(SYMPHONY_URL.'/system/preferences/');
            }
        }
    }

?>
