<?php
    //Twitter Symphony CMS Extension class
    Class extension_Twitter extends Extension
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

        //Constructor function includes required lib
        public function __construct() {
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
            //Extecute parent constructor
            parent::__construct();
        }

        //Delegate specification
        public function getSubscribedDelegates() {
            return array(
                array('page'    => '/backend/',
                'delegate'      => 'AppendPageAlert',
                'callback'      => 'oauth'),
                array('page'    => '/system/preferences/',
                'delegate'      => 'AddCustomPreferenceFieldsets',
                'callback'      => 'preferences')
            );
        }

        //Install procedure - inserts configuration items with default values
        public function install() {
            if (!Symphony::Configuration()->get($this->__name)) {
                Symphony::Configuration()->set('screen_name', null, $this->__name);
                Symphony::Configuration()->set('consumer_key', null, $this->__name);
                Symphony::Configuration()->set('consumer_secret', null, $this->__name);
                Symphony::Configuration()->set('oauth_token', null, $this->__name);
                Symphony::Configuration()->set('oauth_secret', null, $this->__name);
                Symphony::Configuration()->write();
                return true;
            }
            return false;
        }

        //Uninstall procedure - removes configuration items
        public function uninstall() {
            Symphony::Configuration()->remove($this->__name);
            Administration::instance()->saveConfig();
            return true;
        }

        //Inserts preferences on the /system/preferences page
        public function preferences($context) {
            //Get current configuration
            $screen_name = Symphony::Configuration()->get('screen_name', $this->__name);
            $consumer_key = Symphony::Configuration()->get('consumer_key', $this->__name);
            $consumer_secret = Symphony::Configuration()->get('consumer_secret', $this->__name);
            $oauth_token = Symphony::Configuration()->get('oauth_token', $this->__name);
            $oauth_secret = Symphony::Configuration()->get('oauth_secret', $this->__name);

            //Generate OAuth URL
            if (!empty($consumer_key) && !empty($consumer_secret)) {
                $twitter = new EpiTwitter($consumer_key, $consumer_secret);
                $twitter->useApiVersion(1.1);
                $oauth_link = $twitter->getAuthenticateUrl(null, array('oauth_callback' => SYMPHONY_URL."/extension/$this->__name/oauth"));
            }

            //Build HTML preferences
            $fieldset = new XMLElement('fieldset');
            $fieldset->setAttribute('id', 'twitter');
            $fieldset->setAttribute('class', 'settings condensed');
            $fieldset->appendChild(new XMLElement('legend', __('Twitter')));

            //twitter info
            $div = new XMLElement('div');
            $div->setAttribute('class', 'two columns');
            $div->appendChild($this->input('Screen Name', 'screen_name', $screen_name));
            if (!empty($consumer_key) && !empty($consumer_secret)) {
                $msg = (!empty($oauth_token) && !empty($oauth_secret)) ? "Token found! <a href=\"$oauth_link\">Regenerate OAuth Token</a>." : "Token not found <a href=\"$oauth_link\">Click Here To Generate OAuth Token</a>.";
            } else {
                $msg = "You must save a consumer key and secret before you can generate an OAuth token.";
            }
            $div->appendChild(new XMLElement('p', __("<br /><b>OAuth Status</b>: ".$msg), array('class' => 'column')));
            $fieldset->appendChild($div);
            $fieldset->appendChild(new XMLElement('br'));

            //consumer info
            $div = new XMLElement('div');
            $div->setAttribute('class', 'two columns');
            $div->appendChild($this->input('Consumer Key', 'consumer_key', $consumer_key));
            $div->appendChild($this->input('Consumer Secret', 'consumer_secret', $consumer_secret));
            $fieldset->appendChild($div);

            //consumer note
            $fieldset->appendChild(new XMLElement('p', __('<br />To obtain a Consumer Key and Secret please read the documentation avaliable at http://github.com/passbe/twitter/.'), array('class' => 'help')));

            $context['wrapper']->appendChild($fieldset);
        }

        //Appends backend message after oauth generation attempt
        public function oauth($context) {
            //Get session var
            if (array_key_exists($this->__session, $_SESSION)) {
                //Determine alert type
                $status = ($_SESSION[$this->__session]) ? Alert::SUCCESS : Alert::ERROR;
                $msg = ($_SESSION[$this->__session]) ? 'Twitter: OAuth token generation successful.' : 'Twitter: OAuth token generation failed, please check your configuration.' ;
                //Alert
                Administration::instance()->Page->pageAlert($msg, $status);
                //Remove temporary session variable
                unset($_SESSION[$this->__session]);
            }
        }

        //Generates preferences input
        private function input($label, $key, $value) {
            $label = Widget::Label(__($label));
            $label->setAttribute('class', 'column');
            $label->appendChild(Widget::Input("settings[$this->__name][$key]", $value));
            return $label;
        }

    }
